<?php

namespace App\Http\Controllers\Faculty;

use App\Domain\Academic\Services\GradingService;
use App\Domain\Academic\Services\SubmissionTextService;
use App\Domain\Chat\Services\TeachingAssistantService;
use App\Domain\Notification\Services\NotificationService;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Faculty\GradeSubmissionRequest;
use App\Infrastructure\FileStorage\DocumentStorageService;
use App\Models\AssignmentSubmission;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradingController extends Controller
{
    public function __construct(
        private readonly GradingService $grading,
        private readonly ActivityLogger $activity,
        private readonly NotificationService $notifications,
        private readonly TeachingAssistantService $assistant,
    ) {}

    public function index(?string $courseId = null): Response
    {
        $sectionId = request()->integer('section') ?: null;

        $overview = $this->grading->overview(
            request()->user(),
            $courseId ? (int) $courseId : null,
            $sectionId,
        );

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

    /**
     * Download a student's submitted file. Only the owning faculty (or an admin)
     * may retrieve it.
     */
    public function downloadSubmission(AssignmentSubmission $submission): StreamedResponse
    {
        Gate::authorize('manage', $submission->assignment->course);

        abort_if($submission->file_path === null, 404);

        return Storage::disk('local')->download(
            $submission->file_path,
            $submission->original_filename ?? "submission-{$submission->id}",
            ['Content-Type' => DocumentStorageService::contentType($submission->file_path)],
        );
    }

    /**
     * Draft AI feedback for a submission. Returns JSON for the faculty to edit
     * before saving — it does not persist anything.
     */
    /**
     * AI grade draft: per-rubric-criterion scores + suggested overall grade +
     * feedback, generated from the actual submission text. Returned to the
     * grading panel as a prefill — the faculty member reviews, edits and
     * saves; nothing reaches the student until they do.
     */
    public function draftGrade(AssignmentSubmission $submission, SubmissionTextService $text): JsonResponse
    {
        Gate::authorize('manage', $submission->assignment->course);

        $assignment = $submission->assignment;

        $criteria = collect($assignment->rubric ?? [])
            ->map(fn (array $row) => [
                'name' => trim((string) ($row['name'] ?? $row['criterion'] ?? '')),
                'points' => (float) ($row['points'] ?? 0),
            ])
            ->filter(fn (array $criterion) => $criterion['name'] !== '' && $criterion['points'] > 0)
            ->values()
            ->all();

        $draft = $this->assistant->draftRubricGrade([
            'assignmentTitle' => $assignment->title,
            'totalPoints' => $assignment->total_points,
            'criteria' => $criteria,
            'submissionText' => $text->textFor($submission, 6000),
        ]);

        return response()->json($draft);
    }

    public function suggestFeedback(AssignmentSubmission $submission): JsonResponse
    {
        Gate::authorize('manage', $submission->assignment->course);

        $assignment = $submission->assignment;

        $draft = $this->assistant->generateFeedback([
            'assignmentTitle' => $assignment->title,
            'grade' => $submission->grade !== null ? (float) $submission->grade : null,
            'totalPoints' => $assignment->total_points,
            'submissionExcerpt' => (string) $submission->content,
            'rubricCriteria' => collect($assignment->rubric ?? [])
                ->pluck('criterion')
                ->filter()
                ->all(),
        ]);

        return response()->json($draft);
    }
}
