<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Academic\Services\PracticeQuizService;
use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\User\Models\User;
use App\Infrastructure\AI\MockProvider;
use App\Models\FlashcardDeck;
use App\Models\PracticeQuiz;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Student self-quizzing: AI generation, answer-stripped taking, server-side
 * grading, retakes, and missed-questions-to-flashcards.
 */
class PracticeQuizTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Keyless deterministic provider — generation falls back to the
        // synthesized question set, so tests never call OpenAI.
        $this->app->bind(AIProviderInterface::class, fn () => new MockProvider);
    }

    private function student(): User
    {
        $student = User::where('email', 'student@university.edu')->first();
        if (! $student) {
            $this->markTestSkipped('Demo student not seeded; run php artisan db:seed.');
        }

        return $student;
    }

    private function otherStudent(User $not): User
    {
        $other = User::where('id', '!=', $not->id)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))
            ->first();

        if (! $other) {
            $this->markTestSkipped('Second demo student not seeded.');
        }

        return $other;
    }

    /**
     * A quiz with a known question set, bypassing generation.
     */
    private function makeQuiz(User $owner): PracticeQuiz
    {
        return PracticeQuiz::create([
            'user_id' => $owner->id,
            'title' => 'Medium Quiz: Sorting',
            'topic' => 'Sorting',
            'difficulty' => 'medium',
            'questions' => [
                [
                    'id' => 1,
                    'type' => 'multiple-choice',
                    'question' => 'Which sort is O(n log n) in the worst case?',
                    'options' => ['Merge sort', 'Quick sort', 'Bubble sort', 'Insertion sort'],
                    'answer' => 'Merge sort',
                    'explanation' => 'Merge sort divides and merges deterministically.',
                    'points' => 1,
                ],
                [
                    'id' => 2,
                    'type' => 'true-false',
                    'question' => 'Bubble sort is stable.',
                    'options' => [],
                    'answer' => true,
                    'explanation' => 'Equal elements never swap past each other.',
                    'points' => 1,
                ],
            ],
        ]);
    }

    public function test_student_can_generate_a_practice_quiz(): void
    {
        $student = $this->student();

        $this->actingAs($student)
            ->post('/practice/generate', [
                'topic' => 'Graph Traversal',
                'question_count' => 5,
                'difficulty' => 'easy',
            ])
            ->assertRedirect();

        $quiz = PracticeQuiz::where('user_id', $student->id)->where('topic', 'Graph Traversal')->first();

        $this->assertNotNull($quiz);
        $this->assertCount(5, $quiz->questions);
        // Practice quizzes only use instantly-gradable types.
        foreach ($quiz->questions as $question) {
            $this->assertContains($question['type'], ['multiple-choice', 'true-false']);
            $this->assertArrayHasKey('answer', $question);
        }
    }

    public function test_take_page_never_leaks_answers_or_explanations(): void
    {
        $student = $this->student();
        $quiz = $this->makeQuiz($student);

        $response = $this->actingAs($student)->get("/practice/{$quiz->id}");

        $response->assertOk();
        // The Inertia payload must not contain the correct answers or
        // explanations — grading is strictly server-side.
        $response->assertDontSee('Merge sort divides and merges deterministically', false);
        $response->assertDontSee('correctAnswer', false);
        $response->assertDontSee('explanation', false);
    }

    public function test_submission_is_graded_and_persisted(): void
    {
        $student = $this->student();
        $quiz = $this->makeQuiz($student);

        $response = $this->actingAs($student)->postJson("/practice/{$quiz->id}/submit", [
            'answers' => [1 => 'Quick sort', 2 => true],
        ]);

        $response->assertOk()
            ->assertJsonPath('score', 1)
            ->assertJsonPath('total', 2)
            ->assertJsonPath('results.0.correct', false)
            ->assertJsonPath('results.0.correctAnswer', 'Merge sort')
            ->assertJsonPath('results.1.correct', true);

        $this->assertSame(1, $quiz->attempts()->count());
        $this->assertSame(1, $quiz->attempts()->first()->score);
    }

    public function test_retakes_create_separate_attempts(): void
    {
        $student = $this->student();
        $quiz = $this->makeQuiz($student);

        $this->actingAs($student)->postJson("/practice/{$quiz->id}/submit", ['answers' => [1 => 'Merge sort', 2 => true]]);
        $this->actingAs($student)->postJson("/practice/{$quiz->id}/submit", ['answers' => [1 => 'Bubble sort', 2 => false]]);

        $this->assertSame(2, $quiz->attempts()->count());
        $this->assertSame([2, 0], $quiz->attempts()->orderBy('id')->pluck('score')->all());
    }

    public function test_missed_questions_become_a_flashcard_deck(): void
    {
        $student = $this->student();
        $quiz = $this->makeQuiz($student);

        $attemptId = $this->actingAs($student)
            ->postJson("/practice/{$quiz->id}/submit", ['answers' => [1 => 'Quick sort', 2 => true]])
            ->json('attemptId');

        $response = $this->actingAs($student)->postJson("/practice/{$quiz->id}/attempts/{$attemptId}/flashcards");

        $response->assertOk();
        $deck = FlashcardDeck::find($response->json('deckId'));

        $this->assertNotNull($deck);
        $this->assertSame('practice', $deck->source);
        $this->assertSame(1, $deck->cards()->count());
        $this->assertStringContainsString('Merge sort', $deck->cards()->first()->back);
    }

    public function test_perfect_attempt_creates_no_flashcard_deck(): void
    {
        $student = $this->student();
        $quiz = $this->makeQuiz($student);

        $graded = app(PracticeQuizService::class)->grade($quiz, [1 => 'merge sort ', 2 => 'true']);

        // Grading tolerates case/whitespace and boolean-ish strings.
        $this->assertSame(2, $graded['score']);

        $deck = app(PracticeQuizService::class)->missedToFlashcards($student, $quiz, $graded['attempt']);
        $this->assertNull($deck);
    }

    public function test_students_cannot_access_each_others_quizzes(): void
    {
        $student = $this->student();
        $other = $this->otherStudent($student);
        $quiz = $this->makeQuiz($student);

        $this->actingAs($other)->get("/practice/{$quiz->id}")->assertForbidden();
        $this->actingAs($other)->postJson("/practice/{$quiz->id}/submit", ['answers' => []])->assertForbidden();
        $this->actingAs($other)->delete("/practice/{$quiz->id}")->assertForbidden();
    }
}
