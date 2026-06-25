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
        $sections = $faculty->teachingSections()->with('course')->get();
        $courses = $sections->pluck('course')->filter()->unique('id')->sortBy('code')->values();

        // "All Courses" mode (no course selected): aggregate every assignment and
        // submission across all sections the faculty teaches, so the grading list
        // shows all records up front. Faculty then narrow by course/section.
        if (! $courseId) {
            return $this->allCoursesOverview($sections, $courses);
        }

        // Scoped to ONE section of ONE course. A faculty who teaches several
        // sections of a course switches between them.
        $course = $courses->firstWhere('id', $courseId);

        if (! $course) {
            return $this->emptyOverview($courses);
        }

        $courseSections = $sections->where('course_id', $course->id)->sortBy('id')->values();
        $active = $courseSections->firstWhere('id', $sectionId) ?? $courseSections->first();
        $scopeIds = collect([$active?->id])->filter();

        $course->load([
            'assignments' => fn ($q) => $q->whereIn('section_id', $scopeIds),
            'assignments.submissions.student',
            'assignments.course',
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
            'allCourses' => false,
        ];
    }

    /**
     * Aggregate assignments and submissions across every section the faculty
     * teaches (the default "All Courses / All Assignments" grading view).
     *
     * @param  Collection<int, \App\Models\Section>  $sections
     * @param  Collection<int, Course>  $courses
     * @return array<string, mixed>
     */
    private function allCoursesOverview(Collection $sections, Collection $courses): array
    {
        $sectionIds = $sections->pluck('id')->filter()->values();

        $assignments = Assignment::query()
            ->whereIn('section_id', $sectionIds)
            ->with(['submissions.student', 'course'])
            ->get();

        $students = $courses->sum(function (Course $course) use ($sections): int {
            $ids = $sections->where('course_id', $course->id)->pluck('id')->filter();

            return $course->students()->wherePivotIn('section_id', $ids)->count();
        });

        return [
            'courseData' => [
                'id' => null,
                'code' => 'All Courses',
                'name' => 'Every teaching section',
                'students' => $students,
            ],
            'courses' => $this->courseOptions($courses),
            'sections' => [],
            'activeSectionId' => null,
            'assignments' => $assignments->map(fn (Assignment $a) => $this->presentAssignment($a))->values(),
            'submissions' => $assignments->flatMap(
                fn (Assignment $a) => $a->submissions->map(fn ($s) => $this->presentSubmission($s, $a))
            )->values(),
            'allCourses' => true,
        ];
    }

    /**
     * @param  Collection<int, Course>  $courses
     * @return array<string, mixed>
     */
    private function emptyOverview(Collection $courses): array
    {
        return [
            'courseData' => null,
            'courses' => $this->courseOptions($courses),
            'sections' => [],
            'activeSectionId' => null,
            'assignments' => [],
            'submissions' => [],
            'allCourses' => true,
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
            'courseCode' => $assignment->course?->code,
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
            'assignmentTitle' => $assignment->title,
            'courseCode' => $assignment->course?->code,
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
