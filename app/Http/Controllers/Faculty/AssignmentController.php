<?php

namespace App\Http\Controllers\Faculty;

use App\Domain\Notification\Services\NotificationService;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Faculty\UpdateAssignmentRequest;
use App\Models\Assignment;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activity,
        private readonly NotificationService $notifications,
    ) {}

    public function update(UpdateAssignmentRequest $request, Assignment $assignment): RedirectResponse
    {
        Gate::authorize('manage', $assignment->course);

        $assignment->update($request->validated());

        $this->activity->log(
            'assignment.updated',
            "Updated \"{$assignment->title}\"",
            $assignment,
            [],
            $request->user(),
        );

        // Notify this section's students of the change — a due-date change is
        // called out. Only the section's own students are notified.
        if ($assignment->status === 'published') {
            $dueChanged = $assignment->wasChanged('due_at');
            $course = $assignment->course;
            $recipients = $assignment->section
                ? $assignment->section->students()->wherePivot('status', 'enrolled')->get()
                : collect();

            $this->notifications->notifyMany(
                users: $recipients,
                type: NotificationType::ASSIGNMENT,
                title: $dueChanged ? 'Assignment due date changed' : 'Assignment updated',
                message: $dueChanged && $assignment->due_at
                    ? "The due date for \"{$assignment->title}\" is now {$assignment->due_at->format('M j, Y g:i A')}."
                    : "\"{$assignment->title}\" in {$course->code} was updated.",
                link: route('assignments.show', $assignment->id),
                data: ['assignment_id' => $assignment->id, 'course_id' => $assignment->course_id, 'due_changed' => $dueChanged],
            );
        }

        return back()->with('success', 'Assignment updated.');
    }

    public function toggleStatus(Assignment $assignment): RedirectResponse
    {
        Gate::authorize('manage', $assignment->course);

        $assignment->update([
            'status' => $assignment->status === 'published' ? 'draft' : 'published',
        ]);

        return back()->with('success', $assignment->status === 'published'
            ? 'Assignment published.'
            : 'Assignment moved to draft.');
    }

    public function destroy(Assignment $assignment): RedirectResponse
    {
        Gate::authorize('manage', $assignment->course);

        $title = $assignment->title;
        $assignment->delete();

        $this->activity->log('assignment.deleted', "Deleted \"{$title}\"", null, ['title' => $title], request()->user());

        return back()->with('success', 'Assignment deleted.');
    }
}
