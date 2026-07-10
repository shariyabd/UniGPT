<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Domain\Academic\Services\PracticeQuizService;
use App\Domain\Academic\Services\QuestionBankService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\PracticeGenerateRequest;
use App\Models\PracticeAttempt;
use App\Models\PracticeQuiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Student self-quizzing: generate AI practice quizzes, take them without
 * proctoring, get instant graded results, and turn misses into flashcards.
 */
class PracticeQuizController extends Controller
{
    public function __construct(private readonly PracticeQuizService $practice,
        private readonly QuestionBankService $questionBank,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Student/Practice/Index', [
            'quizzes' => $this->practice->quizzesFor($user),
            'courses' => $user->enrolledCourses()
                ->wherePivotNotIn('status', ['pending'])
                ->get(['courses.id', 'courses.code', 'courses.name']),
        ]);
    }

    public function generate(PracticeGenerateRequest $request): RedirectResponse
    {
        $quiz = $this->practice->generate($request->user(), $request->validated());

        return redirect()
            ->route('practice.show', $quiz)
            ->with('success', 'Practice quiz generated — good luck!');
    }

    /**
     * Deterministic self-quiz sampled from the course's question bank (no AI
     * involved — so no AI gate). Reuses the normal take/grade flow.
     */
    public function fromBank(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'question_count' => ['nullable', 'integer', 'min:3', 'max:20'],
        ]);

        $quiz = $this->questionBank->practiceQuizFromBank(
            student: $request->user(),
            courseId: (int) $data['course_id'],
            count: (int) ($data['question_count'] ?? 10),
        );

        return redirect()
            ->route('practice.show', $quiz)
            ->with('success', 'Practice quiz built from the question bank — good luck!');
    }

    public function show(Request $request, PracticeQuiz $quiz): Response
    {
        $this->authorizeOwner($request, $quiz);

        return Inertia::render('Student/Practice/Take', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'topic' => $quiz->topic,
                'difficulty' => $quiz->difficulty,
                'course' => $quiz->course?->code,
                // Stripped of answers/explanations — grading happens server-side.
                'questions' => $this->practice->questionsForTaking($quiz),
            ],
            'attempts' => $quiz->attempts()
                ->latest('completed_at')
                ->get()
                ->map(fn (PracticeAttempt $attempt) => [
                    'id' => $attempt->id,
                    'score' => $attempt->score,
                    'total' => $attempt->total,
                    'completedAt' => $attempt->completed_at?->diffForHumans(),
                ]),
        ]);
    }

    /**
     * Grade a submission and return the detailed results as JSON (the page
     * swaps into its results state without a reload).
     */
    public function submit(Request $request, PracticeQuiz $quiz): JsonResponse
    {
        $this->authorizeOwner($request, $quiz);

        $validated = $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $graded = $this->practice->grade($quiz, $validated['answers']);

        return response()->json([
            'attemptId' => $graded['attempt']->id,
            'score' => $graded['score'],
            'total' => $graded['total'],
            'results' => $graded['results'],
        ]);
    }

    /**
     * Turn an attempt's missed questions into a flashcard deck.
     */
    public function toFlashcards(Request $request, PracticeQuiz $quiz, PracticeAttempt $attempt): JsonResponse
    {
        $this->authorizeOwner($request, $quiz);
        abort_unless($attempt->practice_quiz_id === $quiz->id, 404);

        $deck = $this->practice->missedToFlashcards($request->user(), $quiz, $attempt);

        return response()->json([
            'deckId' => $deck?->id,
            'deckUrl' => $deck ? route('flashcards.show', $deck) : null,
        ]);
    }

    public function destroy(Request $request, PracticeQuiz $quiz): RedirectResponse
    {
        $this->authorizeOwner($request, $quiz);

        $quiz->delete();

        return redirect()->route('practice.index')->with('success', 'Practice quiz deleted.');
    }

    private function authorizeOwner(Request $request, PracticeQuiz $quiz): void
    {
        abort_unless($quiz->user_id === $request->user()->id, 403);
    }
}
