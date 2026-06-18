<?php

declare(strict_types=1);

namespace App\Domain\Academic\Services;

use App\Domain\User\Models\User;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Student-facing assignment submissions: listing assignments for the courses a
 * student is enrolled in, building the detail view, and recording a submission.
 */
class SubmissionService
{
    /**
     * Published assignments across the student's enrolled courses, each annotated
     * with this student's submission state. Ordered soonest-due first.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listFor(User $student): array
    {
        // Assignments belong to the student's enrolled section, so a student only
        // sees the assignments set by their own section's instructor.
        $assignments = Assignment::whereIn('section_id', $student->enrolledSectionIds())
            ->where('status', 'published')
            ->with('course:id,code,name')
            ->orderByRaw('due_at is null, due_at asc')
            ->get();

        $submissions = AssignmentSubmission::where('user_id', $student->id)
            ->whereIn('assignment_id', $assignments->pluck('id'))
            ->get()
            ->keyBy('assignment_id');

        return $assignments
            ->map(fn (Assignment $assignment) => $this->presentListItem(
                assignment: $assignment,
                submission: $submissions->get($assignment->id),
            ))
            ->all();
    }

    /**
     * The assignment detail plus this student's submission (if any).
     *
     * @return array<string, mixed>
     */
    public function detailFor(User $student, Assignment $assignment): array
    {
        $assignment->loadMissing('course:id,code,name');

        $submission = AssignmentSubmission::where('user_id', $student->id)
            ->where('assignment_id', $assignment->id)
            ->first();

        return [
            'assignment' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'type' => $assignment->type,
                'totalPoints' => $assignment->total_points,
                'dueAt' => $assignment->due_at?->toIso8601String(),
                'dueLabel' => $assignment->due_at?->format('M j, Y g:i A'),
                'isPastDue' => $assignment->due_at !== null && $assignment->due_at->isPast(),
                'rubric' => $assignment->rubric ?? [],
                'course' => [
                    'id' => $assignment->course?->id,
                    'code' => $assignment->course?->code,
                    'name' => $assignment->course?->name,
                ],
            ],
            'submission' => $submission ? $this->presentSubmission($submission) : null,
        ];
    }

    /**
     * Create or update the student's submission for an assignment. Submitting
     * after the due date marks the submission late; a graded submission is locked.
     *
     * @param  array<string, mixed>  $data
     */
    public function submit(
        User $student,
        Assignment $assignment,
        array $data,
        ?UploadedFile $file = null,
    ): AssignmentSubmission {
        $submission = AssignmentSubmission::firstOrNew([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);

        abort_if($submission->status === 'graded', 403, 'This submission has already been graded.');

        if ($file !== null) {
            if ($submission->file_path !== null) {
                Storage::disk('local')->delete($submission->file_path);
            }
            $submission->file_path = $file->store("submissions/{$assignment->id}", 'local');
            $submission->original_filename = $file->getClientOriginalName();
        }

        $isLate = $assignment->due_at !== null && $assignment->due_at->isPast();

        $submission->fill([
            'content' => $data['content'] ?? null,
            'status' => $isLate ? 'late' : 'submitted',
            'submitted_at' => now(),
        ]);

        $submission->save();

        return $submission;
    }

    /**
     * @return array<string, mixed>
     */
    private function presentListItem(Assignment $assignment, ?AssignmentSubmission $submission): array
    {
        $isPastDue = $assignment->due_at !== null && $assignment->due_at->isPast();

        return [
            'id' => $assignment->id,
            'title' => $assignment->title,
            'type' => $assignment->type,
            'totalPoints' => $assignment->total_points,
            'dueAt' => $assignment->due_at?->toIso8601String(),
            'dueLabel' => $assignment->due_at?->format('M j, Y'),
            'course' => [
                'id' => $assignment->course?->id,
                'code' => $assignment->course?->code,
                'name' => $assignment->course?->name,
            ],
            // 'pending' means the student has not submitted yet.
            'status' => $submission?->status ?? 'pending',
            'isOverdue' => $submission === null && $isPastDue,
            'grade' => $submission?->grade !== null ? (float) $submission->grade : null,
            'submittedAt' => $submission?->submitted_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentSubmission(AssignmentSubmission $submission): array
    {
        return [
            'id' => $submission->id,
            'content' => $submission->content,
            'fileName' => $submission->original_filename,
            'hasFile' => $submission->file_path !== null,
            'status' => $submission->status,
            'isGraded' => $submission->status === 'graded',
            'grade' => $submission->grade !== null ? (float) $submission->grade : null,
            'feedback' => $submission->feedback,
            'submittedAt' => $submission->submitted_at?->toIso8601String(),
            'submittedLabel' => $submission->submitted_at?->format('M j, Y g:i A'),
            'gradedAt' => $submission->graded_at?->toIso8601String(),
        ];
    }
}
