<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Academic\Services\PracticeQuizService;
use App\Domain\User\Models\User;
use App\Models\ClassTest;
use App\Models\PracticeQuiz;
use App\Models\QuestionBankItem;
use App\Models\Section;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Faculty question bank: per-course reusable questions (manual + imported
 * from class tests), draft class tests from a selection, and deterministic
 * student practice quizzes sampled from the bank.
 */
class QuestionBankTest extends TestCase
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

    /**
     * @return array{faculty: User, section: Section}
     */
    private function facultySection(): array
    {
        $student = $this->student();
        $section = Section::whereIn('id', $student->enrolledSectionIds())
            ->whereNotNull('faculty_id')
            ->firstOrFail();

        return ['faculty' => User::findOrFail($section->faculty_id), 'section' => $section];
    }

    private function bankMcq(Section $section, User $faculty, string $text = 'Which structure is FIFO?'): QuestionBankItem
    {
        return QuestionBankItem::create([
            'course_id' => $section->course_id,
            'created_by' => $faculty->id,
            'type' => 'mcq',
            'question_text' => $text,
            'options' => [
                ['key' => 'A', 'text' => 'Stack'],
                ['key' => 'B', 'text' => 'Queue'],
            ],
            'correct_answer' => 'B',
            'marks' => 2,
            'topic' => 'Data structures',
        ]);
    }

    public function test_faculty_can_add_and_delete_bank_questions_for_their_courses(): void
    {
        ['faculty' => $faculty, 'section' => $section] = $this->facultySection();

        $this->actingAs($faculty)
            ->post('/faculty/question-bank', [
                'course_id' => $section->course_id,
                'type' => 'true_false',
                'question_text' => 'A queue is LIFO.',
                'marks' => 1,
                'correct_answer' => 'false',
                'difficulty' => 'easy',
            ])
            ->assertRedirect();

        $item = QuestionBankItem::where('question_text', 'A queue is LIFO.')->firstOrFail();
        $this->assertSame($section->course_id, $item->course_id);

        $this->actingAs($faculty)
            ->delete("/faculty/question-bank/{$item->id}")
            ->assertRedirect();
        $this->assertNull(QuestionBankItem::find($item->id));
    }

    public function test_faculty_cannot_touch_other_courses_banks(): void
    {
        ['faculty' => $faculty] = $this->facultySection();

        $otherSection = Section::where('faculty_id', '!=', $faculty->id)
            ->whereNotNull('faculty_id')
            ->whereNotIn('course_id', Section::where('faculty_id', $faculty->id)->pluck('course_id'))
            ->first();
        if (! $otherSection) {
            $this->markTestSkipped('No foreign course available.');
        }

        $this->actingAs($faculty)
            ->postJson('/faculty/question-bank', [
                'course_id' => $otherSection->course_id,
                'type' => 'true_false',
                'question_text' => 'Should fail.',
                'marks' => 1,
                'correct_answer' => 'true',
            ])
            ->assertForbidden();
    }

    public function test_importing_a_class_test_copies_questions_and_skips_duplicates(): void
    {
        ['faculty' => $faculty, 'section' => $section] = $this->facultySection();

        $test = ClassTest::create([
            'course_id' => $section->course_id,
            'section_id' => $section->id,
            'title' => 'Bank Import Source',
            'status' => 'closed',
            'total_marks' => 3,
        ]);
        $test->questions()->create([
            'type' => 'mcq',
            'question_text' => 'Pick B.',
            'options' => [['key' => 'A', 'text' => 'No'], ['key' => 'B', 'text' => 'Yes']],
            'correct_answer' => 'B',
            'marks' => 2,
            'position' => 0,
        ]);
        $test->questions()->create([
            'type' => 'true_false',
            'question_text' => 'True is true.',
            'options' => null,
            'correct_answer' => 'true',
            'marks' => 1,
            'position' => 1,
        ]);

        $this->actingAs($faculty)
            ->post("/faculty/question-bank/import/{$test->id}")
            ->assertRedirect();
        $this->assertSame(2, QuestionBankItem::where('course_id', $section->course_id)
            ->where('topic', 'Bank Import Source')->count());

        // Re-import adds nothing.
        $this->actingAs($faculty)->post("/faculty/question-bank/import/{$test->id}");
        $this->assertSame(2, QuestionBankItem::where('course_id', $section->course_id)
            ->where('topic', 'Bank Import Source')->count());
    }

    public function test_selected_items_become_a_draft_class_test(): void
    {
        ['faculty' => $faculty, 'section' => $section] = $this->facultySection();
        $itemA = $this->bankMcq($section, $faculty, 'Draft question one?');
        $itemB = $this->bankMcq($section, $faculty, 'Draft question two?');

        $this->actingAs($faculty)
            ->post('/faculty/question-bank/create-test', [
                'section_id' => $section->id,
                'title' => 'Bank Draft Test',
                'item_ids' => [$itemA->id, $itemB->id],
            ])
            ->assertRedirect();

        $test = ClassTest::where('title', 'Bank Draft Test')->firstOrFail();
        $this->assertSame('draft', $test->status);
        $this->assertSame(2, $test->questions()->count());
        $this->assertSame(4, $test->total_marks);
    }

    public function test_students_can_practice_from_the_bank_and_answers_grade_correctly(): void
    {
        $student = $this->student();
        ['faculty' => $faculty, 'section' => $section] = $this->facultySection();
        $this->bankMcq($section, $faculty);

        $this->actingAs($student)
            ->post('/practice/from-bank', ['course_id' => $section->course_id, 'question_count' => 5])
            ->assertRedirect();

        $quiz = $student->fresh()->id
            ? PracticeQuiz::where('user_id', $student->id)->latest('id')->firstOrFail()
            : null;
        $this->assertStringContainsString('question bank', $quiz->topic);

        // The mcq answer maps from the option KEY ('B') to its TEXT ('Queue'),
        // so the practice grader accepts the option text.
        $graded = app(PracticeQuizService::class)->grade($quiz, [1 => 'Queue']);
        $this->assertSame(1, $graded['score']);
    }

    public function test_students_cannot_practice_from_unenrolled_courses(): void
    {
        $student = $this->student();
        $foreign = Section::whereNotIn('course_id', $student->enrolledCourses()->pluck('courses.id'))
            ->whereNotNull('faculty_id')
            ->first();
        if (! $foreign) {
            $this->markTestSkipped('No unenrolled course available.');
        }
        $this->bankMcq($foreign, User::findOrFail($foreign->faculty_id));

        $this->actingAs($student)
            ->postJson('/practice/from-bank', ['course_id' => $foreign->course_id])
            ->assertForbidden();
    }
}
