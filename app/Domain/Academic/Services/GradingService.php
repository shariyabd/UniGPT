<?php

namespace App\Domain\Academic\Services;

use App\Domain\User\Models\User;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use Illuminate\Support\Collection;

/**
 * Faculty grading: assignment/submission overviews and grade recording.
 */
class GradingService
{
    /**
     * Build the grading overview for a faculty member, optionally scoped to one course.
     *
     * @return array<string, mixed>
     */
    public function overview(User $faculty, ?int $courseId = null, ?int $sectionId = null): array
    {
        // Grading is scoped to ONE section the faculty teaches, so only that
        // section's assignments, submissions and roster appear. A faculty who
        // teaches several sections of a course switches between them.
        $sections = $faculty->teachingSections()->with('course')->get();
        $courses = $sections->pluck('course')->filter()->unique('id')->sortBy('code')->values();
        $course = $courseId ? $courses->firstWhere('id', $courseId) : $courses->first();

        if (! $course) {
            return ['courseData' => null, 'courses' => $this->courseOptions($courses), 'sections' => [], 'activeSectionId' => null, 'assignments' => [], 'submissions' => []];
        }

        $courseSections = $sections->where('course_id', $course->id)->sortBy('id')->values();
        $active = $courseSections->firstWhere('id', $sectionId) ?? $courseSections->first();
        $scopeIds = collect([$active?->id])->filter();

        $course->load([
            'assignments' => fn ($q) => $q->whereIn('section_id', $scopeIds),
            'assignments.submissions.student',
        ]);

        return [
            'courseData' => [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
                'students' => $course->students()->wherePivotIn('section_id', $scopeIds)->count(),
            ],
            'courses' => $this->courseOptions($courses),
            'sections' => $courseSections->map(fn ($s) => ['id' => $s->id, 'label' => $s->label])->values()->all(),
            'activeSectionId' => $active?->id,
            'assignments' => $course->assignments->map(fn (Assignment $a) => $this->presentAssignment($a))->values(),
            'submissions' => $course->assignments->flatMap(
                fn (Assignment $a) => $a->submissions->map(fn ($s) => $this->presentSubmission($s, $a))
            )->values(),
        ];
    }

    /**
     * Record a grade for a submission.
     *
     * @param  array<int, mixed>|null  $rubricScores
     */
    public function grade(
        AssignmentSubmission $submission,
        float $grade,
        ?string $feedback,
        ?array $rubricScores,
        User $grader,
    ): AssignmentSubmission {
        $submission->update([
            'grade' => $grade,
            'feedback' => $feedback,
            'rubric_scores' => $rubricScores,
            'status' => 'graded',
            'graded_at' => now(),
            'graded_by' => $grader->id,
        ]);

        return $submission->fresh();
    }

    /**
     * @param  Collection<int, Course>  $courses
     * @return array<int, array<string, mixed>>
     */
    private function courseOptions(Collection $courses): array
    {
        return $courses->map(fn (Course $c) => [
            'id' => $c->id,
            'code' => $c->code,
            'name' => $c->name,
        ])->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentAssignment(Assignment $assignment): array
    {
        $subs = $assignment->submissions;
        $graded = $subs->whereNotNull('grade');

        return [
            'id' => $assignment->id,
            'title' => $assignment->title,
            'type' => $assignment->type,
            'dueDate' => $assignment->due_at?->toDateString(),
            'totalPoints' => $assignment->total_points,
            'status' => $assignment->status,
            'rubric' => ['criteria' => $assignment->rubric ?? []],
            'submissions' => [
                'total' => $subs->count(),
                'graded' => $graded->count(),
                'pending' => $subs->count() - $graded->count(),
                'late' => $subs->where('status', 'late')->count(),
                'missing' => 0,
            ],
            'averageGrade' => $graded->isEmpty() ? null : round($graded->avg('grade'), 1),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentSubmission(AssignmentSubmission $submission, Assignment $assignment): array
    {
        $student = $submission->student;

        return [
            'id' => $submission->id,
            'assignmentId' => $assignment->id,
            'student' => [
                'id' => $student?->id,
                'name' => $student?->name,
                'email' => $student?->email,
                'avatar' => 'https://ui-avatars.com/api/?name='.urlencode($student?->name ?? 'S').'&background=6366f1&color=fff',
            ],
            'submittedAt' => $submission->submitted_at?->toIso8601String(),
            'content' => $submission->content,
            'fileName' => $submission->original_filename,
            'fileUrl' => $submission->file_path !== null
                ? route('faculty.submissions.download', $submission->id)
                : null,
            'status' => $submission->status,
            'isLate' => $submission->status === 'late',
            'grade' => $submission->grade !== null ? (float) $submission->grade : null,
            'feedback' => $submission->feedback,
            'rubricScores' => $submission->rubric_scores,
            'totalPoints' => $assignment->total_points,
        ];
    }
}
