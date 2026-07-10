<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Analytics\Services\ConceptMasteryService;
use App\Domain\User\Models\User;
use App\Models\ClassTest;
use App\Models\ClassTestAttempt;
use App\Models\FlashcardDeck;
use App\Models\PracticeQuiz;
use App\Models\Section;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Concept mastery map: per-topic mastery aggregated deterministically from
 * practice-quiz attempts (topic), flashcard SM-2 state (deck title) and
 * class-test scores (test title), blended weakest-first, and served to the
 * student progress page for adaptive review.
 */
class ConceptMasteryTest extends TestCase
{
    use DatabaseTransactions;

    private function student(): User
    {
        $student = User::where('email', 'student@university.edu')->first();
        if (! $student || $student->enrolledSectionIds()->isEmpty()) {
            $this->markTestSkipped('Demo student not seeded or has no sections.');
        }

        return $student;
    }

    private function makeQuizWithAttempt(User $student, string $topic, int $score, int $total): PracticeQuiz
    {
        $quiz = PracticeQuiz::create([
            'user_id' => $student->id,
            'title' => "Quiz: {$topic}",
            'topic' => $topic,
            'difficulty' => 'medium',
            'questions' => [
                ['id' => 1, 'type' => 'true-false', 'question' => 'Q?', 'answer' => true, 'points' => 1],
            ],
        ]);

        $quiz->attempts()->create([
            'answers' => [1 => true],
            'score' => $score,
            'total' => $total,
            'completed_at' => now(),
        ]);

        return $quiz;
    }

    public function test_practice_attempts_build_topic_mastery(): void
    {
        $student = $this->student();
        $this->makeQuizWithAttempt($student, 'Recursion Basics', 9, 10);

        $map = app(ConceptMasteryService::class)->build($student);

        $entry = collect($map['concepts'])->firstWhere('concept', 'Recursion Basics');
        $this->assertNotNull($entry);
        $this->assertSame(90, $entry['mastery']);
        $this->assertFalse($entry['weak']);
        $this->assertSame(1, $entry['sources']['practice']['attempts']);
        $this->assertNull($entry['sources']['flashcards']);
    }

    public function test_weak_concepts_are_flagged_and_sorted_first(): void
    {
        $student = $this->student();
        $this->makeQuizWithAttempt($student, 'Strong Topic Alpha', 10, 10);
        $this->makeQuizWithAttempt($student, 'Weak Topic Omega', 2, 10);

        $map = app(ConceptMasteryService::class)->build($student);
        $concepts = collect($map['concepts']);

        $weak = $concepts->firstWhere('concept', 'Weak Topic Omega');
        $strong = $concepts->firstWhere('concept', 'Strong Topic Alpha');

        $this->assertTrue($weak['weak']);
        $this->assertFalse($strong['weak']);
        $this->assertGreaterThanOrEqual(1, $map['weakCount']);
        $this->assertLessThan(
            $concepts->search(fn ($c) => $c['concept'] === 'Strong Topic Alpha'),
            $concepts->search(fn ($c) => $c['concept'] === 'Weak Topic Omega'),
            'Weakest concepts come first.',
        );
    }

    public function test_flashcard_recall_counts_only_studied_decks_and_merges_by_topic(): void
    {
        $student = $this->student();
        $this->makeQuizWithAttempt($student, 'Graph Theory', 8, 10);

        // "Review: {topic}" decks fold into the same concept as the quiz topic.
        $deck = FlashcardDeck::create([
            'user_id' => $student->id,
            'title' => 'Review: Graph Theory',
            'source' => 'practice',
        ]);
        $deck->cards()->create([
            'front' => 'A', 'back' => 'B', 'position' => 0,
            'repetitions' => 3, 'interval_days' => 12, 'last_reviewed_at' => now(),
        ]);
        $deck->cards()->create([
            'front' => 'C', 'back' => 'D', 'position' => 1,
            'repetitions' => 0, 'interval_days' => 0, 'last_reviewed_at' => now(),
        ]);

        // An untouched deck contributes no signal at all.
        FlashcardDeck::create(['user_id' => $student->id, 'title' => 'Untouched Deck'])
            ->cards()->create(['front' => 'X', 'back' => 'Y', 'position' => 0]);

        $map = app(ConceptMasteryService::class)->build($student);
        $concepts = collect($map['concepts']);

        $entry = $concepts->firstWhere('concept', 'Graph Theory');
        $this->assertNotNull($entry);
        $this->assertSame(['learned' => 1, 'total' => 2], $entry['sources']['flashcards']);
        $this->assertNotNull($entry['sources']['practice']);
        // Blend: practice 0.8 (w 0.35) + cards 0.5 (w 0.15) → 71%.
        $this->assertSame(71, $entry['mastery']);

        $this->assertNull($concepts->firstWhere('concept', 'Untouched Deck'));
    }

    public function test_class_test_scores_feed_the_map(): void
    {
        $student = $this->student();
        $sectionId = $student->enrolledSectionIds()->first();
        $section = Section::findOrFail($sectionId);

        $test = ClassTest::create([
            'course_id' => $section->course_id,
            'section_id' => $section->id,
            'title' => 'Normalization Deep Dive',
            'status' => 'closed',
            'total_marks' => 10,
        ]);

        ClassTestAttempt::create([
            'class_test_id' => $test->id,
            'user_id' => $student->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(20),
            'submitted_at' => now(),
            'score' => 4,
            'total_marks' => 10,
        ]);

        $map = app(ConceptMasteryService::class)->build($student);

        $entry = collect($map['concepts'])->firstWhere('concept', 'Normalization Deep Dive');
        $this->assertNotNull($entry);
        $this->assertSame(40, $entry['mastery']);
        $this->assertTrue($entry['weak']);
        $this->assertSame(40, $entry['sources']['classTests']['accuracy']);
    }

    public function test_progress_page_receives_the_concept_mastery_prop(): void
    {
        $student = $this->student();
        $this->makeQuizWithAttempt($student, 'Inertia Props', 5, 10);

        $this->actingAs($student)
            ->get('/progress')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Student/LearningAnalytics', false)
                ->has('conceptMastery.concepts')
                ->where('conceptMastery.concepts.0.weak', true));
    }
}
