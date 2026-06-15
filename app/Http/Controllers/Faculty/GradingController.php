<?php

namespace App\Http\Controllers\Faculty;

use App\Domain\Academic\Services\GradingService;
use App\Domain\Notification\Services\NotificationService;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Faculty\GradeSubmissionRequest;
use App\Models\AssignmentSubmission;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GradingController extends Controller
{
    public function __construct(
        private readonly GradingService $grading,
        private readonly ActivityLogger $activity,
        private readonly NotificationService $notifications,
    ) {}

    public function index(?string $courseId = null): Response
    {
        $overview = $this->grading->overview(request()->user(), $courseId ? (int) $courseId : null);

        return Inertia::render('Faculty/Grading', array_merge($overview, [
            'courseId' => $courseId ? (int) $courseId : null,
        ]));
    }

    public function grade(GradeSubmissionRequest $request, AssignmentSubmission $submission): RedirectResponse
    {
        // Only the course's faculty (or an admin) may grade.
        Gate::authorize('manage', $submission->assignment->course);

        $validated = $request->validated();

        $this->grading->grade(
            $submission,
            (float) $validated['grade'],
            $validated['feedback'] ?? null,
            $validated['rubric_scores'] ?? null,
            $request->user(),
        );

        $this->activity->log('grading.graded', 'Graded a submission', $submission, [
            'grade' => $validated['grade'],
        ], $request->user());

        $assignment = $submission->assignment;
        if ($student = $submission->student) {
            $this->notifications->notify(
                user: $student,
                type: NotificationType::GRADE,
                title: 'Assignment graded',
                message: "Your submission for \"{$assignment->title}\" was graded: {$validated['grade']}/{$assignment->total_points}.",
                link: route('transcript'),
                data: ['submission_id' => $submission->id, 'course_id' => $assignment->course_id],
            );
        }

        return back()->with('success', 'Grade saved.');
    }
}
