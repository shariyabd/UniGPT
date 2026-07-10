<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Academic\Services\GradingService;
use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\User\Models\User;
use App\Infrastructure\AI\MockProvider;
use App\Models\Assignment;
use App\Models\SubmissionEmbedding;
use App\Models\SubmissionSimilarity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Submission similarity screening: submitting an assignment chunks + embeds
 * the text (queue is sync in tests, so the screening job runs inline) and
 * flags high-similarity pairs within the assignment, which the faculty
 * grading overview surfaces per submission.
 *
 * MockProvider's lexical hash embeddings make identical text score ~1.0 and
 * unrelated text score low, so thresholds behave deterministically.
 */
class SubmissionSimilarityTest extends TestCase
{
    use DatabaseTransactions;

    private const COPIED_TEXT = 'Binary search trees keep their keys in sorted order so that lookup, insertion and '
        .'deletion follow the branch comparisons from the root downwards, giving logarithmic average complexity. '
        .'Balancing strategies such as AVL rotations or red-black recoloring keep the height bounded even under '
        .'adversarial insertion orders, which preserves the logarithmic guarantee for every operation.';

    private const ORIGINAL_TEXT = 'Photosynthesis converts light energy into chemical energy inside chloroplasts. '
        .'Chlorophyll molecules absorb photons and drive electron transport across the thylakoid membrane, producing '
        .'ATP and NADPH which the Calvin cycle later consumes to fix carbon dioxide into glucose molecules for the plant.';

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

    private function publishedAssignment(User $student): Assignment
    {
        $assignment = Assignment::whereIn('section_id', $student->enrolledSectionIds())
            ->where('status', 'published')
            ->whereNotNull('section_id')
            ->first();

        if (! $assignment) {
            $this->markTestSkipped('No published assignment in the demo student\'s sections.');
        }

        return $assignment;
    }

    /**
     * A classmate enrolled in the same section as the assignment.
     */
    private function classmate(User $student, Assignment $assignment): User
    {
        $classmate = $assignment->course->students()
            ->wherePivot('section_id', $assignment->section_id)
            ->wherePivotNotIn('status', ['pending', 'dropped'])
            ->where('users.id', '!=', $student->id)
            ->first();

        if (! $classmate) {
            $this->markTestSkipped('No classmate in the demo student\'s section.');
        }

        return $classmate;
    }

    public function test_submitting_stores_chunk_embeddings(): void
    {
        $student = $this->student();
        $assignment = $this->publishedAssignment($student);

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}/submit", ['content' => self::ORIGINAL_TEXT])
            ->assertRedirect();

        $this->assertGreaterThan(0, SubmissionEmbedding::query()
            ->where('assignment_id', $assignment->id)
            ->whereHas('submission', fn ($q) => $q->where('user_id', $student->id))
            ->count());
    }

    public function test_near_identical_submissions_are_flagged_both_ways(): void
    {
        $student = $this->student();
        $assignment = $this->publishedAssignment($student);
        $classmate = $this->classmate($student, $assignment);

        $this->actingAs($classmate)
            ->post("/assignments/{$assignment->id}/submit", ['content' => self::COPIED_TEXT])
            ->assertRedirect();

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}/submit", ['content' => self::COPIED_TEXT])
            ->assertRedirect();

        $flags = SubmissionSimilarity::where('assignment_id', $assignment->id)->get();

        $this->assertCount(2, $flags, 'A flagged pair is stored in both directions.');
        $this->assertGreaterThanOrEqual(
            (float) config('rag.submission_screening.flag_threshold'),
            $flags->first()->score,
        );
        $this->assertNotEmpty($flags->first()->matched_chunks, 'Excerpt pairs are kept for review.');
    }

    public function test_unrelated_submissions_are_not_flagged(): void
    {
        $student = $this->student();
        $assignment = $this->publishedAssignment($student);
        $classmate = $this->classmate($student, $assignment);

        $this->actingAs($classmate)
            ->post("/assignments/{$assignment->id}/submit", ['content' => self::COPIED_TEXT])
            ->assertRedirect();

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}/submit", ['content' => self::ORIGINAL_TEXT])
            ->assertRedirect();

        $this->assertSame(0, SubmissionSimilarity::where('assignment_id', $assignment->id)->count());
    }

    public function test_resubmission_with_original_work_clears_the_flag(): void
    {
        $student = $this->student();
        $assignment = $this->publishedAssignment($student);
        $classmate = $this->classmate($student, $assignment);

        $this->actingAs($classmate)
            ->post("/assignments/{$assignment->id}/submit", ['content' => self::COPIED_TEXT])
            ->assertRedirect();
        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}/submit", ['content' => self::COPIED_TEXT])
            ->assertRedirect();

        $this->assertSame(2, SubmissionSimilarity::where('assignment_id', $assignment->id)->count());

        // Rewriting the answer must remove stale flags in BOTH directions.
        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}/submit", ['content' => self::ORIGINAL_TEXT])
            ->assertRedirect();

        $this->assertSame(0, SubmissionSimilarity::where('assignment_id', $assignment->id)->count());
    }

    public function test_grading_overview_surfaces_similarity_for_faculty(): void
    {
        $student = $this->student();
        $assignment = $this->publishedAssignment($student);
        $classmate = $this->classmate($student, $assignment);

        $faculty = $assignment->section?->faculty_id
            ? User::find($assignment->section->faculty_id)
            : null;
        if (! $faculty) {
            $this->markTestSkipped('Assignment section has no faculty.');
        }

        $this->actingAs($classmate)
            ->post("/assignments/{$assignment->id}/submit", ['content' => self::COPIED_TEXT])
            ->assertRedirect();
        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}/submit", ['content' => self::COPIED_TEXT])
            ->assertRedirect();

        $overview = app(GradingService::class)->overview($faculty);

        $flagged = collect($overview['submissions'])
            ->first(fn (array $s) => $s['assignmentId'] === $assignment->id && $s['similarity'] !== null);

        $this->assertNotNull($flagged, 'The grading overview should badge the flagged submission.');
        $this->assertNotEmpty($flagged['similarity']['matches']);
        $this->assertIsFloat($flagged['similarity']['maxScore']);
        $this->assertSame(
            $flagged['similarity']['matches'][0]['student'],
            $flagged['student']['name'] === $student->name ? $classmate->name : $student->name,
        );
    }
}
