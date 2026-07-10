<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Academic\Services\GradingService;
use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\Chat\DataObjects\ChatResult;
use App\Domain\User\Models\User;
use App\Infrastructure\AI\MockProvider;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Section;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * AI-assisted grading: the draft-grade endpoint returns per-rubric-criterion
 * scores (clamped to each criterion's maximum), a suggested grade and draft
 * feedback built from the submission text — as a prefill for the grading
 * panel, never an auto-release. Falls back to a labelled heuristic draft when
 * the provider gives no usable JSON.
 */
class AiGradeDraftTest extends TestCase
{
    use DatabaseTransactions;

    private const RUBRIC = [
        ['criterion' => 'Correctness', 'points' => 6],
        ['criterion' => 'Clarity', 'points' => 4],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(AIProviderInterface::class, fn () => new MockProvider);
    }

    private function student(): User
    {
        $student = User::where('email', 'student@university.edu')->first();
        if (! $student || $student->enrolledSectionIds()->isEmpty()) {
            $this->markTestSkipped('Demo student not seeded or has no sections.');
        }

        return $student;
    }

    /**
     * @return array{faculty: User, submission: AssignmentSubmission}
     */
    private function gradedScenario(): array
    {
        $student = $this->student();
        $section = Section::whereIn('id', $student->enrolledSectionIds())
            ->whereNotNull('faculty_id')
            ->firstOrFail();

        $assignment = Assignment::create([
            'course_id' => $section->course_id,
            'section_id' => $section->id,
            'title' => 'Sorting Algorithms Essay',
            'type' => 'homework',
            'status' => 'published',
            'due_at' => now()->addDays(3),
            'total_points' => 10,
            'rubric' => self::RUBRIC,
        ]);

        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'content' => 'Merge sort guarantees n log n by dividing the list and merging sorted halves. '
                .'Quick sort is faster in practice but degrades without good pivots. The essay explains '
                .'correctness proofs and compares clarity of the two implementations.',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return ['faculty' => User::findOrFail($section->faculty_id), 'submission' => $submission];
    }

    public function test_draft_returns_clamped_rubric_scores_and_feedback(): void
    {
        ['faculty' => $faculty, 'submission' => $submission] = $this->gradedScenario();

        $draft = $this->actingAs($faculty)
            ->postJson("/faculty/submissions/{$submission->id}/draft-grade")
            ->assertOk()
            ->json();

        $this->assertCount(2, $draft['criteria']);
        foreach ($draft['criteria'] as $criterion) {
            $this->assertGreaterThanOrEqual(0, $criterion['score']);
            $this->assertLessThanOrEqual($criterion['points'], $criterion['score']);
            $this->assertNotSame('', $criterion['justification']);
        }
        $this->assertEqualsWithDelta(
            array_sum(array_column($draft['criteria'], 'score')),
            $draft['suggestedGrade'],
            0.01,
        );
        $this->assertNotSame('', $draft['feedback']);
        // The keyless MockProvider returns prose, not JSON → heuristic draft.
        $this->assertSame('heuristic', $draft['source']);
    }

    public function test_ai_json_scores_are_used_and_clamped(): void
    {
        // A provider that "grades" with an out-of-range score for Correctness.
        $this->app->bind(AIProviderInterface::class, fn () => new class extends MockProvider
        {
            public function chat(array $messages, array $options = []): ChatResult
            {
                return new ChatResult(
                    content: json_encode([
                        'criteria' => [
                            ['name' => 'Correctness', 'score' => 99, 'justification' => 'Thorough proof of complexity.'],
                            ['name' => 'clarity', 'score' => 3.5, 'justification' => 'Readable structure.'],
                        ],
                        'feedback' => 'Strong essay overall.',
                        'strengths' => ['Rigorous analysis'],
                        'improvements' => ['Add pivot-selection discussion'],
                    ]),
                    model: 'fake-grader',
                );
            }
        });

        ['faculty' => $faculty, 'submission' => $submission] = $this->gradedScenario();

        $draft = $this->actingAs($faculty)
            ->postJson("/faculty/submissions/{$submission->id}/draft-grade")
            ->assertOk()
            ->json();

        $this->assertSame('ai', $draft['source']);
        $byName = collect($draft['criteria'])->keyBy('name');
        $this->assertSame(6.0, (float) $byName['Correctness']['score'], 'Scores are clamped to the criterion maximum.');
        $this->assertSame(3.5, (float) $byName['Clarity']['score'], 'Criterion names match case-insensitively.');
        $this->assertSame(9.5, (float) $draft['suggestedGrade']);
        $this->assertSame('Strong essay overall.', $draft['feedback']);
    }

    public function test_students_cannot_reach_the_draft_endpoint(): void
    {
        ['submission' => $submission] = $this->gradedScenario();

        $response = $this->actingAs($this->student())
            ->postJson("/faculty/submissions/{$submission->id}/draft-grade");

        $this->assertTrue(
            $response->isRedirection() || $response->isForbidden(),
            'Role middleware must bounce students.',
        );
    }

    public function test_grading_overview_normalizes_rubric_criterion_keys(): void
    {
        ['faculty' => $faculty, 'submission' => $submission] = $this->gradedScenario();

        $overview = app(GradingService::class)->overview($faculty);

        $assignment = collect($overview['assignments'])
            ->firstWhere('id', $submission->assignment_id);

        $this->assertNotNull($assignment);
        // Stored rows use `criterion`; the UI contract is `name` + `points`.
        $this->assertSame('Correctness', $assignment['rubric']['criteria'][0]['name']);
        $this->assertSame(6, $assignment['rubric']['criteria'][0]['points']);
    }
}
