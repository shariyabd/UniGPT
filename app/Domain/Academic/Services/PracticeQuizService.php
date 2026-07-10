<?php

declare(strict_types=1);

namespace App\Domain\Academic\Services;

use App\Domain\Chat\Services\TeachingAssistantService;
use App\Domain\User\Models\User;
use App\Models\FlashcardDeck;
use App\Models\PracticeAttempt;
use App\Models\PracticeQuiz;
use Illuminate\Support\Collection;

/**
 * Student self-quizzing: AI-generated practice quizzes graded instantly
 * server-side (correct answers never reach the client before submission),
 * with missed questions convertible into a flashcard deck for review.
 */
class PracticeQuizService
{
    /**
     * Practice quizzes are limited to instantly auto-gradable types.
     */
    private const QUESTION_TYPES = ['multiple-choice', 'true-false'];

    public function __construct(
        private readonly TeachingAssistantService $assistant,
        private readonly FlashcardService $flashcards,
    ) {}

    /**
     * The student's quizzes with attempt stats, newest first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function quizzesFor(User $student): Collection
    {
        return PracticeQuiz::where('user_id', $student->id)
            ->with(['course:id,code', 'attempts' => fn ($q) => $q->latest('completed_at')])
            ->latest()
            ->get()
            ->map(function (PracticeQuiz $quiz) {
                $last = $quiz->attempts->first();

                return [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'topic' => $quiz->topic,
                    'difficulty' => $quiz->difficulty,
                    'course' => $quiz->course?->code,
                    'questionCount' => count($quiz->questions ?? []),
                    'attemptCount' => $quiz->attempts->count(),
                    'bestScore' => $quiz->attempts->max('score'),
                    'lastScore' => $last?->score,
                    'total' => $last?->total ?? count($quiz->questions ?? []),
                    'createdAt' => $quiz->created_at?->diffForHumans(),
                ];
            });
    }

    /**
     * Generate and store a practice quiz on a topic.
     *
     * @param  array<string, mixed>  $data
     */
    public function generate(User $student, array $data): PracticeQuiz
    {
        $generated = $this->assistant->generateQuiz([
            'topic' => $data['topic'],
            'questionCount' => $data['question_count'] ?? 5,
            'difficulty' => $data['difficulty'] ?? 'medium',
            'questionTypes' => self::QUESTION_TYPES,
            'includeExplanations' => true,
        ]);

        return PracticeQuiz::create([
            'user_id' => $student->id,
            'course_id' => $data['course_id'] ?? null,
            'title' => $generated['title'],
            'topic' => $generated['topic'],
            'difficulty' => $generated['difficulty'],
            'questions' => $generated['questions'],
        ]);
    }

    /**
     * The quiz's questions shaped for taking — correct answers and
     * explanations stripped.
     *
     * @return array<int, array<string, mixed>>
     */
    public function questionsForTaking(PracticeQuiz $quiz): array
    {
        return collect($quiz->questions)->map(fn (array $question) => [
            'id' => $question['id'],
            'type' => $question['type'],
            'question' => $question['question'],
            'options' => $question['type'] === 'multiple-choice' ? ($question['options'] ?? []) : [],
        ])->all();
    }

    /**
     * Grade a submission, persist the attempt, and return per-question
     * results (now including the correct answers and explanations).
     *
     * @param  array<int|string, mixed>  $answers  question id → submitted answer
     * @return array{attempt: PracticeAttempt, score: int, total: int, results: array<int, array<string, mixed>>}
     */
    public function grade(PracticeQuiz $quiz, array $answers): array
    {
        $results = collect($quiz->questions)->map(function (array $question) use ($answers) {
            $submitted = $answers[$question['id']] ?? $answers[(string) $question['id']] ?? null;
            $correct = $this->isCorrect($question, $submitted);

            return [
                'id' => $question['id'],
                'type' => $question['type'],
                'question' => $question['question'],
                'options' => $question['type'] === 'multiple-choice' ? ($question['options'] ?? []) : [],
                'submitted' => $submitted,
                'correctAnswer' => $question['answer'],
                'correct' => $correct,
                'explanation' => $question['explanation'] ?? null,
            ];
        })->values();

        $score = $results->where('correct', true)->count();
        $total = $results->count();

        $attempt = $quiz->attempts()->create([
            'answers' => collect($answers)->map(fn ($answer) => is_scalar($answer) ? $answer : null)->all(),
            'score' => $score,
            'total' => $total,
            'completed_at' => now(),
        ]);

        return [
            'attempt' => $attempt,
            'score' => $score,
            'total' => $total,
            'results' => $results->all(),
        ];
    }

    /**
     * Turn an attempt's missed questions into a flashcard deck (front =
     * question, back = correct answer + explanation). Returns null when the
     * attempt had no misses.
     */
    public function missedToFlashcards(User $student, PracticeQuiz $quiz, PracticeAttempt $attempt): ?FlashcardDeck
    {
        $submitted = $attempt->answers ?? [];

        $missed = collect($quiz->questions)
            ->reject(fn (array $question) => $this->isCorrect(
                $question,
                $submitted[$question['id']] ?? $submitted[(string) $question['id']] ?? null,
            ))
            ->values();

        if ($missed->isEmpty()) {
            return null;
        }

        $deck = $this->flashcards->createDeck($student, [
            'course_id' => $quiz->course_id,
            'title' => 'Review: '.$quiz->topic,
            'description' => "Missed questions from practice quiz \"{$quiz->title}\".",
        ], source: 'practice');

        $this->flashcards->replaceCards($deck, $missed->map(fn (array $question) => [
            'front' => $question['question'],
            'back' => $this->answerLabel($question)
                .(! empty($question['explanation']) ? "\n\n".$question['explanation'] : ''),
        ])->all());

        return $deck;
    }

    /**
     * Compare a submitted answer against the stored one, tolerating the
     * shapes the generator and the client each produce.
     */
    private function isCorrect(array $question, mixed $submitted): bool
    {
        if ($submitted === null) {
            return false;
        }

        if ($question['type'] === 'true-false') {
            $expected = filter_var($question['answer'], FILTER_VALIDATE_BOOLEAN);
            $given = filter_var($submitted, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            return $given !== null && $given === $expected;
        }

        return is_scalar($submitted)
            && mb_strtolower(trim((string) $submitted)) === mb_strtolower(trim((string) $question['answer']));
    }

    private function answerLabel(array $question): string
    {
        if ($question['type'] === 'true-false') {
            return filter_var($question['answer'], FILTER_VALIDATE_BOOLEAN) ? 'True' : 'False';
        }

        return (string) $question['answer'];
    }
}
