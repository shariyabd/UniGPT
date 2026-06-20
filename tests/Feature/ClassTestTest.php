<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Models\ClassTest;
use App\Models\ClassTestAttempt;
use App\Models\Course;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClassTestTest extends TestCase
{
    use DatabaseTransactions;

    private function faculty(): User
    {
        return User::where('email', 'prof.smith@university.edu')->firstOr(
            fn () => $this->markTestSkipped('Demo faculty not seeded.')
        );
    }

    private function student(): User
    {
        return User::where('email', 'student@university.edu')->firstOr(
            fn () => $this->markTestSkipped('Demo student not seeded.')
        );
    }

    /**
     * Build an isolated course + section taught by the faculty, with the student
     * enrolled — so the flow doesn't depend on seed-data overlap.
     */
    private function setUpSection(User $faculty, User $student): Section
    {
        $course = Course::create([
            'code' => 'CT'.substr((string) microtime(true), -6),
            'name' => 'Class Test Course',
            'department_id' => $faculty->department_id,
            'faculty_id' => $faculty->id,
            'semester' => 5,
            'credits' => 3,
        ]);

        $section = Section::create([
            'course_id' => $course->id,
            'term_id' => Term::first()?->id,
            'faculty_id' => $faculty->id,
            'label' => 'A',
        ]);

        $student->enrolledSections()->attach($section->id, [
            'role' => 'student',
            'status' => 'enrolled',
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        return $section;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(int $sectionId): array
    {
        return [
            'section_id' => $sectionId,
            'title' => 'Week 5 Class Test',
            'description' => 'No copying.',
            'duration_minutes' => 15,
            'status' => 'published',
            'shuffle_questions' => false,
            'questions' => [
                [
                    'type' => 'mcq',
                    'question_text' => '2 + 2 = ?',
                    'marks' => 2,
                    'correct_answer' => 'B',
                    'options' => [
                        ['key' => 'A', 'text' => '3'],
                        ['key' => 'B', 'text' => '4'],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'question_text' => 'The sky is blue.',
                    'marks' => 1,
                    'correct_answer' => 'true',
                ],
            ],
        ];
    }

    public function test_faculty_can_create_a_published_class_test(): void
    {
        $faculty = $this->faculty();
        $section = $this->setUpSection($faculty, $this->student());

        $this->actingAs($faculty)
            ->post('/faculty/class-tests', $this->payload($section->id))
            ->assertRedirect(route('faculty.class-tests'));

        $test = ClassTest::where('section_id', $section->id)->first();
        $this->assertNotNull($test);
        $this->assertSame('published', $test->status);
        $this->assertSame(3, $test->total_marks); // 2 + 1, derived from questions
        $this->assertCount(2, $test->questions);
    }

    public function test_create_accepts_true_false_question_with_blank_option_scaffolding(): void
    {
        $faculty = $this->faculty();
        $section = $this->setUpSection($faculty, $this->student());

        // The authoring form keeps a hidden options array on every question for the
        // builder UI. A True/False question therefore arrives with blank-text
        // options — this must NOT trip validation and silently 422.
        $payload = [
            'section_id' => $section->id,
            'title' => 'TF scaffolding test',
            'duration_minutes' => 10,
            'status' => 'published',
            'shuffle_questions' => false,
            'questions' => [
                [
                    'type' => 'true_false',
                    'question_text' => 'Laravel is a PHP framework.',
                    'marks' => 1,
                    'correct_answer' => 'true',
                    'options' => [
                        ['key' => 'A', 'text' => ''],
                        ['key' => 'B', 'text' => ''],
                    ],
                ],
            ],
        ];

        $this->actingAs($faculty)
            ->post('/faculty/class-tests', $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('faculty.class-tests'));

        $test = ClassTest::where('section_id', $section->id)->where('title', 'TF scaffolding test')->first();
        $this->assertNotNull($test);
        $this->assertSame('true', $test->questions->first()->correct_answer);
        $this->assertNull($test->questions->first()->options); // options dropped for True/False
    }

    public function test_student_submission_is_graded_instantly(): void
    {
        $faculty = $this->faculty();
        $student = $this->student();
        $section = $this->setUpSection($faculty, $student);

        $this->actingAs($faculty)->post('/faculty/class-tests', $this->payload($section->id));
        $test = ClassTest::where('section_id', $section->id)->firstOrFail();

        $this->actingAs($student)->post("/class-tests/{$test->id}/start")->assertRedirect();

        // Answer the first question correctly (B) and the second wrong (false).
        $answers = [];
        foreach ($test->questions as $q) {
            $answers[$q->id] = $q->question_text === '2 + 2 = ?' ? 'B' : 'false';
        }

        $this->actingAs($student)
            ->post("/class-tests/{$test->id}/submit", ['answers' => $answers])
            ->assertRedirect(route('class-tests.result', $test->id));

        $attempt = ClassTestAttempt::where('class_test_id', $test->id)->where('user_id', $student->id)->firstOrFail();
        $this->assertSame('submitted', $attempt->status);
        $this->assertSame(2, $attempt->score); // only the 2-mark MCQ was correct
        $this->assertSame(3, $attempt->total_marks);
    }

    public function test_disqualified_attempt_scores_zero_even_with_correct_answers(): void
    {
        $faculty = $this->faculty();
        $student = $this->student();
        $section = $this->setUpSection($faculty, $student);

        $this->actingAs($faculty)->post('/faculty/class-tests', $this->payload($section->id));
        $test = ClassTest::where('section_id', $section->id)->firstOrFail();

        $this->actingAs($student)->post("/class-tests/{$test->id}/start");

        // All answers correct, but disqualified — score must be 0.
        $answers = [];
        foreach ($test->questions as $q) {
            $answers[$q->id] = $q->question_text === '2 + 2 = ?' ? 'B' : 'true';
        }

        $this->actingAs($student)
            ->post("/class-tests/{$test->id}/submit", ['answers' => $answers, 'disqualified' => true])
            ->assertRedirect(route('class-tests.result', $test->id));

        $attempt = ClassTestAttempt::where('class_test_id', $test->id)->where('user_id', $student->id)->firstOrFail();
        $this->assertSame('disqualified', $attempt->status);
        $this->assertSame(0, $attempt->score);
    }

    public function test_start_payload_never_exposes_correct_answers(): void
    {
        $faculty = $this->faculty();
        $student = $this->student();
        $section = $this->setUpSection($faculty, $student);

        $this->actingAs($faculty)->post('/faculty/class-tests', $this->payload($section->id));
        $test = ClassTest::where('section_id', $section->id)->firstOrFail();

        $this->actingAs($student)->post("/class-tests/{$test->id}/start");

        $props = $this->actingAs($student)
            ->get("/class-tests/{$test->id}/take")
            ->viewData('page')['props'];

        foreach ($props['questions'] as $question) {
            $this->assertArrayNotHasKey('correctAnswer', $question);
            $this->assertArrayNotHasKey('correct_answer', $question);
        }
    }

    public function test_faculty_can_generate_questions_with_ai(): void
    {
        $faculty = $this->faculty();

        $response = $this->actingAs($faculty)
            ->postJson('/faculty/class-tests/generate', [
                'topic' => 'Binary search trees',
                'questionCount' => 4,
                'difficulty' => 'medium',
                'types' => ['mcq', 'true_false'],
                'marks' => 2,
            ])
            ->assertOk();

        $questions = $response->json('questions');
        $this->assertNotEmpty($questions);

        foreach ($questions as $question) {
            $this->assertContains($question['type'], ['mcq', 'true_false']);
            $this->assertNotEmpty($question['question_text']);
            $this->assertSame(2, $question['marks']);
            $this->assertArrayHasKey('correct_answer', $question);

            if ($question['type'] === 'mcq') {
                $this->assertGreaterThanOrEqual(2, count($question['options']));
                $this->assertArrayHasKey('key', $question['options'][0]);
                $this->assertArrayHasKey('text', $question['options'][0]);
                // The correct answer must reference an actual option key.
                $keys = array_column($question['options'], 'key');
                $this->assertContains($question['correct_answer'], $keys);
            } else {
                $this->assertContains($question['correct_answer'], ['true', 'false']);
            }
        }
    }

    public function test_timer_is_anchored_to_when_the_student_starts(): void
    {
        $faculty = $this->faculty();
        $student = $this->student();
        $section = $this->setUpSection($faculty, $student);

        // 15-minute test (payload duration) published now.
        $this->actingAs($faculty)->post('/faculty/class-tests', $this->payload($section->id));
        $test = ClassTest::where('section_id', $section->id)->firstOrFail();

        // Student starts → the countdown should begin from this moment, giving a
        // near-full 15 minutes regardless of when the test was published.
        $this->actingAs($student)->post("/class-tests/{$test->id}/start");

        $props = $this->actingAs($student)
            ->get("/class-tests/{$test->id}/take")
            ->viewData('page')['props'];

        $remaining = $props['attempt']['remainingSeconds'];
        $this->assertGreaterThan(15 * 60 - 15, $remaining); // ~900s, allowing a few seconds of test latency
        $this->assertLessThanOrEqual(15 * 60, $remaining);
    }

    public function test_student_not_in_section_cannot_view_test(): void
    {
        $faculty = $this->faculty();
        $section = $this->setUpSection($faculty, $this->student());

        $this->actingAs($faculty)->post('/faculty/class-tests', $this->payload($section->id));
        $test = ClassTest::where('section_id', $section->id)->firstOrFail();

        // A second student who is NOT enrolled in this section.
        $outsider = User::where('email', '!=', 'student@university.edu')
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))
            ->first();

        if (! $outsider) {
            $this->markTestSkipped('No second student available.');
        }

        $this->actingAs($outsider)
            ->get("/class-tests/{$test->id}")
            ->assertNotFound();
    }
}
