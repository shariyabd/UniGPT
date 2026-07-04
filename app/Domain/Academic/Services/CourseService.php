<?php

namespace App\Domain\Academic\Services;

use App\Domain\User\Models\User;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read/query service for the academic domain (courses, enrollment, materials).
 */
class CourseService
{
    /**
     * The full course catalog with each course's sections (offerings) — for the
     * admin course-management screen.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function catalog(): Collection
    {
        return Course::with(['department', 'sections.faculty', 'sections.term', 'sections.students'])
            ->latest()
            ->get()
            ->map(fn (Course $course) => $this->presentCourse($course));
    }

    /**
     * The admin course catalog, paginated at the QUERY level with filters applied
     * in the database — so only the current page's courses (and their rosters) are
     * hydrated. This keeps memory bounded on large catalogs; loading every course's
     * full roster at once exhausts PHP's memory_limit on production-sized data.
     *
     * @param  array{search?: string|null, department_id?: mixed, semester?: mixed}  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function catalogPage(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $departmentId = $filters['department_id'] ?? null;
        $semester = $filters['semester'] ?? null;

        return Course::with(['department', 'sections.faculty', 'sections.term', 'sections.students'])
            ->when($departmentId, fn ($query, $value) => $query->where('department_id', $value))
            ->when($semester, fn ($query, $value) => $query->where('semester', $value))
            ->when($search, fn ($query, $value) => $query->where(fn ($sub) => $sub
                ->where('code', 'like', "%{$value}%")
                ->orWhere('name', 'like', "%{$value}%")
            ))
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Course $course) => $this->presentCourse($course));
    }

    /**
     * Distinct, sorted semester values across the WHOLE catalog (for the filter
     * dropdown) — a lightweight query that never hydrates course models.
     *
     * @return Collection<int, int|string>
     */
    public function semesterOptions(): Collection
    {
        return Course::query()
            ->whereNotNull('semester')
            ->distinct()
            ->orderBy('semester')
            ->pluck('semester')
            ->values();
    }

    /**
     * Present a single course (with its sections + rosters) as the array shape the
     * admin catalog UI consumes. Shared by catalog() and catalogPage().
     *
     * @return array<string, mixed>
     */
    private function presentCourse(Course $course): array
    {
        return [
            'id' => $course->id,
            'code' => $course->code,
            'name' => $course->name,
            'description' => $course->description,
            'departmentId' => $course->department_id,
            'department' => $course->department?->name,
            'semester' => $course->semester,
            'credits' => $course->credits,
            'sections' => $course->sections->map(fn (Section $section) => [
                'id' => $section->id,
                'label' => $section->label,
                'termId' => $section->term_id,
                'term' => $section->term?->name,
                'facultyId' => $section->faculty_id,
                'faculty' => $section->faculty?->name,
                'maxEnrollment' => $section->max_enrollment,
                'isActive' => $section->is_active,
                'schedule' => $section->schedule,
                // Reserved seats: confirmed enrolments + pending placements.
                'studentCount' => $section->students->whereIn('pivot.status', ['enrolled', 'pending'])->count(),
                'roster' => $section->students->map(fn (User $student) => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'studentId' => $student->student_id,
                    'status' => $student->pivot->status,
                ])->values(),
            ])->values(),
        ];
    }

    /**
     * Courses a student is enrolled in, with progress + material counts.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function studentCourses(User $student): Collection
    {
        $currentTermId = Term::query()->where('is_current', true)->value('id');
        $termNames = Term::query()->pluck('name', 'id');

        // Pending placements (assigned but not yet registered by the student)
        // belong on the registration page, not in the student's course list.
        $courses = $student->enrolledCourses()
            ->wherePivotNotIn('status', ['pending'])
            ->with(['faculty', 'department'])
            ->get();

        // The student attends a specific section per course; the instructor,
        // section label and material count must reflect THAT section, not the
        // course's denormalized owner or the sum across every section.
        $sections = Section::with('faculty')
            ->withCount(['materials' => fn ($q) => $q->where('is_published', true)])
            ->findMany($courses->pluck('pivot.section_id')->filter()->unique())
            ->keyBy('id');

        return $courses->map(function (Course $course) use ($currentTermId, $termNames, $sections) {
            $termId = $course->pivot->term_id;
            $section = $sections->get($course->pivot->section_id);

            return [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
                // Section instructor first; fall back to the course owner for
                // legacy rows that predate sectioning.
                'instructor' => $section?->faculty?->name ?? $course->faculty?->name,
                'section' => $section?->label,
                'credits' => $course->credits,
                'semester' => $course->semester,
                'progress' => (int) $course->pivot->progress,
                'grade' => $course->pivot->grade,
                'status' => $course->pivot->status,
                'totalMaterials' => $section?->materials_count ?? 0,
                'termId' => $termId,
                'termName' => $termId ? ($termNames[$termId] ?? null) : null,
                'isCurrent' => $this->isCurrentEnrollment(
                    termId: $termId,
                    status: $course->pivot->status,
                    currentTermId: $currentTermId,
                ),
            ];
        });
    }

    /**
     * An enrollment is "current" when it belongs to the current term and has not
     * been dropped. Legacy rows without a term (or when no current term is
     * configured) are treated as current so nothing disappears unexpectedly.
     */
    private function isCurrentEnrollment(?int $termId, ?string $status, ?int $currentTermId): bool
    {
        // A finished enrollment is always "past", regardless of term.
        if (in_array($status, ['dropped', 'completed'], strict: true)) {
            return false;
        }

        if ($currentTermId === null || $termId === null) {
            return true;
        }

        return $termId === $currentTermId;
    }

    /**
     * Materials available to a student, grouped by course.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function studentMaterials(User $student): Collection
    {
        $completedIds = $student->completedMaterials()->pluck('course_materials.id')->all();

        // Materials belong to the student's enrolled SECTION — each instructor's
        // section has its own resources, so a student must only see the materials
        // of the section they are in, grouped under their course.
        return $student->enrolledSections()
            ->wherePivotNotIn('status', ['dropped', 'pending'])
            ->with([
                'course',
                'materials' => fn ($q) => $q->where('is_published', true)->orderBy('week'),
                'materials.document',
            ])
            ->get()
            ->map(fn (Section $section) => [
                'id' => $section->course?->id,
                'code' => $section->course?->code,
                'name' => $section->course?->name,
                'materials' => $section->materials->map(fn (CourseMaterial $m) => [
                    'id' => $m->id,
                    'title' => $m->title,
                    'description' => $m->description,
                    'type' => $m->type,
                    'week' => $m->week,
                    'downloads' => $m->downloads,
                    'fileSize' => $m->file_size,
                    'uploadedAt' => $m->created_at?->toIso8601String(),
                    'documentId' => $m->document_id,
                    'hasFile' => $m->hasFile(),
                    'completed' => in_array($m->id, $completedIds, strict: true),
                    'downloadUrl' => $this->studentMaterialDownloadUrl($m),
                ])->values(),
            ]);
    }

    /**
     * Courses taught by a faculty member, with roster + progress stats.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function facultyCourses(User $faculty): Collection
    {
        // A faculty member teaches sections, not courses — so the dashboard is
        // built from the sections they teach. Counts (students, materials,
        // assignments) and progress are scoped to THOSE sections, so a faculty
        // teaching one section of a course never sees another section's roster.
        $sections = $faculty->teachingSections()
            ->with([
                'course.department',
                'students' => fn ($q) => $q->wherePivot('status', 'enrolled'),
            ])
            ->withCount(['materials', 'assignments'])
            ->get();

        return $sections->groupBy('course_id')
            ->map(function (Collection $group) {
                $course = $group->first()->course;
                $students = $group->flatMap(fn (Section $section) => $section->students);

                return [
                    'id' => $course->id,
                    'code' => $course->code,
                    'name' => $course->name,
                    'semester' => $course->semester,
                    'credits' => $course->credits,
                    'students' => $students->count(),
                    'materials' => (int) $group->sum('materials_count'),
                    'assignments' => (int) $group->sum('assignments_count'),
                    'progress' => (int) round($students->avg('pivot.progress') ?? 0),
                    'schedule' => $course->schedule,
                    // Section labels this faculty teaches for the course (e.g. ["A"]).
                    'sections' => $group->pluck('label')->sort()->values()->all(),
                ];
            })
            ->values();
    }

    /**
     * Full detail for a single course (roster, materials, assignments).
     *
     * Scoped to ONE section so an instructor only ever sees a single section's
     * roster, materials and assignments at a time. When a viewer is given, the
     * sections they may view are the ones they teach; $sectionId selects which
     * (defaulting to their first). With no viewer (e.g. admin) the whole course
     * is shown across all sections.
     *
     * @return array<string, mixed>
     */
    public function courseDetail(Course $course, ?User $viewer = null, ?int $sectionId = null): array
    {
        // The sections this viewer may look at: the ones they teach (faculty),
        // or every section (admin / no viewer).
        $availableSections = ($viewer
            ? $course->sections()->where('faculty_id', $viewer->id)
            : $course->sections())
            ->with('faculty')
            ->orderBy('id')
            ->get();

        // Resolve the active section: the requested one if the viewer may see it,
        // otherwise the first available.
        $active = $availableSections->firstWhere('id', $sectionId) ?? $availableSections->first();

        // Admin (no viewer) with no specific section still spans every section.
        $scopeIds = $viewer
            ? collect([$active?->id])->filter()
            : ($sectionId ? collect([$sectionId]) : $availableSections->pluck('id'));

        $course->load([
            'faculty', 'department',
            'students' => fn ($q) => $q->wherePivotIn('section_id', $scopeIds),
            'materials' => fn ($q) => $q->whereIn('section_id', $scopeIds)->orderBy('week'),
            'assignments' => fn ($q) => $q->whereIn('section_id', $scopeIds),
            'assignments.submissions',
        ]);

        return [
            'id' => $course->id,
            'code' => $course->code,
            'name' => $course->name,
            'description' => $course->description,
            'credits' => $course->credits,
            'semester' => $course->semester,
            // The instructor of the active section, not the course owner.
            'instructor' => $active?->faculty?->name ?? $course->faculty?->name,
            'department' => $course->department?->name,
            'schedule' => $course->schedule,
            // The sections the viewer may switch between, plus the active one.
            'sections' => $availableSections->map(fn (Section $s) => [
                'id' => $s->id,
                'label' => $s->label,
                'faculty' => $s->faculty?->name,
            ])->values(),
            'activeSectionId' => $active?->id,
            'enrollment' => [
                'current' => $course->students->count(),
                'maximum' => $course->max_enrollment,
            ],
            'students' => $course->students->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'email' => $s->email,
                'studentId' => $s->student_id,
                'currentGrade' => $s->pivot->grade,
                'progress' => (int) $s->pivot->progress,
                'status' => $s->pivot->status,
            ])->values(),
            'materials' => $course->materials->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'description' => $m->description,
                'type' => $m->type,
                'week' => $m->week,
                'downloads' => $m->downloads,
                'isPublished' => $m->is_published,
                'hasFile' => $m->file_path !== null,
                'fileName' => $m->original_filename,
                'downloadUrl' => $m->file_path !== null
                    ? route('faculty.courses.materials.download', [$course->id, $m->id])
                    : null,
            ])->values(),
            'assignments' => $course->assignments->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'description' => $a->description,
                'type' => $a->type,
                'isQuiz' => $a->type === 'quiz',
                'status' => $a->status,
                'totalPoints' => $a->total_points,
                'dueDate' => $a->due_at?->toDateString(),
                'rubric' => $a->rubric ?? [],
                'submissions' => $a->submissions->count(),
                'graded' => $a->submissions->whereNotNull('grade')->count(),
            ])->values(),
        ];
    }

    /**
     * Raw course fields for the faculty edit form.
     *
     * @return array<string, mixed>
     */
    public function editableCourse(Course $course): array
    {
        return [
            'id' => $course->id,
            'code' => $course->code,
            'name' => $course->name,
            'description' => $course->description,
            'department_id' => $course->department_id,
            'semester' => $course->semester,
            'credits' => $course->credits,
            'max_enrollment' => $course->max_enrollment,
            'is_active' => $course->is_active,
            'schedule' => [
                'lectures' => $course->schedule['lectures'] ?? '',
                'classroom' => $course->schedule['classroom'] ?? '',
                'office_hours' => $course->schedule['office_hours'] ?? '',
            ],
        ];
    }

    /**
     * Download URL for a student: prefer an uploaded material file, fall back
     * to a linked knowledge-base document.
     */
    private function studentMaterialDownloadUrl(CourseMaterial $material): ?string
    {
        if ($material->file_path !== null) {
            return route('materials.download', $material->id);
        }

        return $material->document_id ? route('documents.download', $material->document_id) : null;
    }
}
