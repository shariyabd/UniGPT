<?php

declare(strict_types=1);

namespace App\Http\Controllers\Faculty;

use App\Domain\Academic\Services\CourseFeedbackService;
use App\Domain\Chat\Services\TeachingAssistantService;
use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Faculty side of anonymous course feedback: open/close each section's
 * window, read anonymized aggregates (once enough responses exist), and get
 * an AI theme summary.
 */
class CourseFeedbackController extends Controller
{
    public function __construct(
        private readonly CourseFeedbackService $feedback,
        private readonly TeachingAssistantService $assistant,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Faculty/CourseFeedback', [
            'sections' => $this->feedback->sectionsForFaculty($request->user()),
        ]);
    }

    public function toggle(Request $request, Section $section): RedirectResponse
    {
        $section = $this->feedback->toggle($request->user(), $section);

        return back()->with(
            'success',
            $section->feedback_open
                ? 'Feedback window opened — your students have been notified.'
                : 'Feedback window closed.',
        );
    }

    public function summarize(Request $request, Section $section): JsonResponse
    {
        abort_unless($section->faculty_id === $request->user()->id, 403);

        $data = $this->feedback->sectionsForFaculty($request->user())
            ->firstWhere('sectionId', $section->id);

        abort_unless($data !== null && $data['revealed'], 422, 'Not enough responses to summarize yet.');

        return response()->json($this->assistant->summarizeFeedback(
            courseLabel: trim(($data['course']['code'] ?? '')." ({$data['label']})"),
            comments: $data['comments'],
            ratingDistribution: $data['ratingDistribution'] ?? [],
        ));
    }
}
