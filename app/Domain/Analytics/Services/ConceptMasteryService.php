<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Services;

use App\Domain\User\Models\User;
use App\Models\ClassTestAttempt;
use App\Models\FlashcardDeck;
use App\Models\PracticeQuiz;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Per-concept mastery map for a student, aggregated from signal the app
 * already collects: practice-quiz attempts (by quiz topic), flashcard SM-2
 * state (by deck title) and class-test scores (by test title). Everything is
 * deterministic — no AI calls.
 *
 * The blended mastery weights proctored class tests highest, then practice,
 * then flashcard recall, renormalized over whichever sources exist for the
 * concept. Weak concepts (< WEAK_BELOW) drive the "practice this" /
 * "make flashcards" adaptive-review actions on the progress page.
 */
class ConceptMasteryService
{
    private const WEIGHTS = ['classTests' => 0.5, 'practice' => 0.35, 'flashcards' => 0.15];

    private const WEAK_BELOW = 60;

    /**
     * A flashcard counts as "learned" once SM-2 has promoted it past the
     * first two successful repetitions (interval ≥ 6 days).
     */
    private const LEARNED_REPETITIONS = 2;

    private const LEARNED_INTERVAL_DAYS = 6;

    /**
     * @return array{concepts: array<int, array<string, mixed>>, weakCount: int}
     */
    public function build(User $student): array
    {
        $concepts = [];

        $this->mergePractice($student, $concepts);
        $this->mergeFlashcards($student, $concepts);
        $this->mergeClassTests($student, $concepts);

        $entries = collect($concepts)
            ->map(fn (array $entry) => $this->finalize($entry))
            ->sortBy([['mastery', 'asc'], ['concept', 'asc']])
            ->values();

        return [
            'concepts' => $entries->all(),
            'weakCount' => $entries->where('weak', true)->count(),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $concepts
     */
    private function mergePractice(User $student, array &$concepts): void
    {
        $quizzes = PracticeQuiz::query()
            ->where('user_id', $student->id)
            ->with(['course:id,code', 'attempts:id,practice_quiz_id,score,total,completed_at'])
            ->get();

        foreach ($quizzes as $quiz) {
            $attempts = $quiz->attempts->whereNotNull('completed_at');
            if ($attempts->isEmpty()) {
                continue;
            }

            $entry = &$this->entry($concepts, (string) $quiz->topic, $quiz->course?->code);

            $entry['practice']['score'] += (int) $attempts->sum('score');
            $entry['practice']['total'] += (int) $attempts->sum('total');
            $entry['practice']['attempts'] += $attempts->count();
            $this->touch($entry, $attempts->max('completed_at'));
            unset($entry);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $concepts
     */
    private function mergeFlashcards(User $student, array &$concepts): void
    {
        $decks = FlashcardDeck::query()
            ->where('user_id', $student->id)
            ->with(['course:id,code', 'cards:id,deck_id,repetitions,interval_days,last_reviewed_at'])
            ->get();

        foreach ($decks as $deck) {
            $reviewed = $deck->cards->whereNotNull('last_reviewed_at');
            // A deck that was never studied carries no mastery signal.
            if ($reviewed->isEmpty()) {
                continue;
            }

            $learned = $deck->cards
                ->filter(fn ($card) => $card->repetitions >= self::LEARNED_REPETITIONS
                    && $card->interval_days >= self::LEARNED_INTERVAL_DAYS)
                ->count();

            $entry = &$this->entry($concepts, $this->deckConcept((string) $deck->title), $deck->course?->code);

            $entry['flashcards']['learned'] += $learned;
            $entry['flashcards']['total'] += $deck->cards->count();
            $this->touch($entry, $reviewed->max('last_reviewed_at'));
            unset($entry);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $concepts
     */
    private function mergeClassTests(User $student, array &$concepts): void
    {
        $attempts = ClassTestAttempt::query()
            ->where('user_id', $student->id)
            ->where('status', 'submitted')
            ->whereNotNull('submitted_at')
            ->where('total_marks', '>', 0)
            ->with('classTest:id,title,course_id', 'classTest.course:id,code')
            ->get();

        foreach ($attempts as $attempt) {
            $test = $attempt->classTest;
            if ($test === null) {
                continue;
            }

            $entry = &$this->entry($concepts, (string) $test->title, $test->course?->code);

            $entry['classTests']['score'] += (int) $attempt->score;
            $entry['classTests']['total'] += (int) $attempt->total_marks;
            $entry['classTests']['attempts']++;
            $this->touch($entry, $attempt->submitted_at);
            unset($entry);
        }
    }

    /**
     * Find-or-create the merged entry for a concept, keyed case-insensitively.
     *
     * @param  array<string, array<string, mixed>>  $concepts
     * @return array<string, mixed>
     */
    private function &entry(array &$concepts, string $concept, ?string $courseCode): array
    {
        $display = Str::of($concept)->squish()->toString();
        $key = mb_strtolower($display);

        if (! isset($concepts[$key])) {
            $concepts[$key] = [
                'concept' => $display,
                'course' => $courseCode,
                'practice' => ['score' => 0, 'total' => 0, 'attempts' => 0],
                'flashcards' => ['learned' => 0, 'total' => 0],
                'classTests' => ['score' => 0, 'total' => 0, 'attempts' => 0],
                'lastActivity' => null,
            ];
        }

        $concepts[$key]['course'] ??= $courseCode;

        return $concepts[$key];
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function touch(array &$entry, mixed $moment): void
    {
        if ($moment instanceof CarbonInterface
            && ($entry['lastActivity'] === null || $moment->greaterThan($entry['lastActivity']))) {
            $entry['lastActivity'] = $moment;
        }
    }

    /**
     * Blend per-source accuracies into one mastery figure and shape the entry
     * for the frontend.
     *
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    private function finalize(array $entry): array
    {
        $sources = [];
        if ($entry['classTests']['total'] > 0) {
            $sources['classTests'] = $entry['classTests']['score'] / $entry['classTests']['total'];
        }
        if ($entry['practice']['total'] > 0) {
            $sources['practice'] = $entry['practice']['score'] / $entry['practice']['total'];
        }
        if ($entry['flashcards']['total'] > 0) {
            $sources['flashcards'] = $entry['flashcards']['learned'] / $entry['flashcards']['total'];
        }

        $weightSum = array_sum(array_intersect_key(self::WEIGHTS, $sources));
        $blended = 0.0;
        foreach ($sources as $source => $accuracy) {
            $blended += $accuracy * (self::WEIGHTS[$source] / $weightSum);
        }

        $mastery = (int) round($blended * 100);

        return [
            'concept' => $entry['concept'],
            'course' => $entry['course'],
            'mastery' => $mastery,
            'weak' => $mastery < self::WEAK_BELOW,
            'sources' => [
                'practice' => $entry['practice']['attempts'] > 0 ? [
                    'accuracy' => (int) round($sources['practice'] * 100),
                    'attempts' => $entry['practice']['attempts'],
                ] : null,
                'flashcards' => $entry['flashcards']['total'] > 0 ? [
                    'learned' => $entry['flashcards']['learned'],
                    'total' => $entry['flashcards']['total'],
                ] : null,
                'classTests' => $entry['classTests']['attempts'] > 0 ? [
                    'accuracy' => (int) round($sources['classTests'] * 100),
                    'attempts' => $entry['classTests']['attempts'],
                ] : null,
            ],
            'lastActivity' => $entry['lastActivity']?->toIso8601String(),
        ];
    }

    /**
     * Practice-review decks are titled "Review: {topic}" — fold them into the
     * underlying topic so the map doesn't split one concept in two.
     */
    private function deckConcept(string $title): string
    {
        return Str::of($title)->replaceMatches('/^review:\s*/i', '')->toString();
    }
}
