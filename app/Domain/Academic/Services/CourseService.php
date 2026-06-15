<?php

namespace App\Domain\Academic\Services;

use App\Domain\User\Models\User;
use App\Models\Course;
use App\Models\CourseMaterial;
use Illuminate\Support\Collection;

/**
 * Read/query service for the academic domain (courses, enrollment, materials).
 */
class CourseService
{
    /**
     * Courses a student is enrolled in, with progress + material counts.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function studentCourses(User $student): Collection
    {
        return $student->enrolledCourses()
            ->with(['faculty', 'department'])
            ->withCount('materials')
            ->get()
            ->map(fn (Course $course) => [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
                'instructor' => $course->faculty?->name,
                'credits' => $course->credits,
                'semester' => $course->semester,
                'progress' => (int) $course->pivot->progress,
                'grade' => $course->pivot->grade,
                'status' => $course->pivot->status,
                'totalMaterials' => $course->materials_count,
            ]);
    }

    /**
     * Materials available to a student, grouped by course.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function studentMaterials(User $student): Collection
    {
        $courseIds = $student->enrolledCourses()->pluck('courses.id');

        return Course::whereIn('id', $courseIds)
            ->with(['materials' => fn ($q) => $q->where('is_published', true)->orderBy('week'), 'materials.document'])
            ->get()
            ->map(fn (Course $course) => [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
                'materials' => $course->materials->map(fn ($m) => [
                    'id' => $m->id,
                    'title' => $m->title,
                    'description' => $m->description,
                    'type' => $m->type,
                    'week' => $m->week,
                    'downloads' => $m->downloads,
                    'documentId' => $m->document_id,
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
        return $faculty->teachingCourses()
            ->with('department')
            ->withCount(['students', 'materials', 'assignments'])
            ->get()
            ->map(fn (Course $course) => [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
                'semester' => $course->semester,
                'credits' => $course->credits,
                'students' => $course->students_count,
                'materials' => $course->materials_count,
                'assignments' => $course->assignments_count,
                'progress' => (int) round($course->students->avg('pivot.progress') ?? 0),
                'schedule' => $course->schedule,
            ]);
    }

    /**
     * Full detail for a single course (roster, materials, assignments).
     *
     * @return array<string, mixed>
     */
    public function courseDetail(Course $course): array
    {
        $course->load([
            'faculty', 'department',
            'students',
            'materials' => fn ($q) => $q->orderBy('week'),
            'assignments.submissions',
        ]);

        return [
            'id' => $course->id,
            'code' => $course->code,
            'name' => $course->name,
            'description' => $course->description,
            'credits' => $course->credits,
            'semester' => $course->semester,
            'instructor' => $course->faculty?->name,
            'department' => $course->department?->name,
            'schedule' => $course->schedule,
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
                'type' => $a->type,
                'totalPoints' => $a->total_points,
                'dueDate' => $a->due_at?->toDateString(),
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
