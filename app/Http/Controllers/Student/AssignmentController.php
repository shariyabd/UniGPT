<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Domain\Academic\Services\SubmissionService;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\User\Models\User;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SubmitAssignmentRequest;
use App\Models\Assignment;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly SubmissionService $submissions,
        private readonly NotificationService $notifications,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Student/Assignments', [
            'assignments' => $this->submissions->listFor(request()->user()),
        ]);
    }

    public function show(Assignment $assignment): Response
    {
        $user = request()->user();
        $this->ensureAccessible($user, $assignment);

        return Inertia::render('Student/AssignmentDetail', $this->submissions->detailFor($user, $assignment));
    }

    public function store(SubmitAssignmentRequest $request, Assignment $assignment): RedirectResponse
    {
        $user = $request->user();
        $this->ensureAccessible($user, $assignment);

        $submission = $this->submissions->submit(
            student: $user,
            assignment: $assignment,
            data: $request->safe()->only(['content']),
            file: $request->file('file'),
        );

        $this->activity->log(
            'submission.created',
            "Submitted \"{$assignment->title}\"",
            $submission,
            ['assignment_id' => $assignment->id, 'status' => $submission->status],
            $user,
        );

        // Let the section's instructor know a submission arrived — the faculty
        // who actually teaches this section, falling back to the course owner.
        $assignment->loadMissing('section.faculty', 'course.faculty');
        if ($faculty = ($assignment->section?->faculty ?? $assignment->course?->faculty)) {
            $this->notifications->notify(
                user: $faculty,
                type: NotificationType::SUBMISSION,
                title: 'New submission',
                message: "{$user->name} submitted \"{$assignment->title}\" in {$assignment->course->code}.",
                link: route('faculty.course.grading', $assignment->course_id),
                data: ['assignment_id' => $assignment->id, 'submission_id' => $submission->id],
            );
        }

        return back()->with('success', $submission->status === 'late'
            ? 'Submitted (after the due date).'
            : 'Submission saved.');
    }

    /**
     * A student may only see/submit a published assignment in a course they are
     * enrolled in.
     */
    private function ensureAccessible(User $user, Assignment $assignment): void
    {
        abort_unless(
            $assignment->status === 'published'
                && $user->enrolledSectionIds()->contains($assignment->section_id),
            404,
        );
    }
}
