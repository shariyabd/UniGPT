<?php

namespace App\Domain\Academic\Services;

use App\Domain\User\Models\User;
use App\Models\Course;
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
                    'downloadUrl' => $m->document_id ? route('documents.download', $m->document_id) : null,
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
                'type' => $m->type,
                'week' => $m->week,
                'downloads' => $m->downloads,
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
}
