<?php

namespace Tests\Feature;

use App\Domain\Chat\Services\TeachingAssistantService;
use App\Domain\User\Models\User;
use App\Models\AssignmentSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FeedbackGenerationTest extends TestCase
{
    use DatabaseTransactions;

    private function faculty(): User
    {
        $faculty = User::where('email', 'prof.smith@university.edu')->first();

        if (! $faculty) {
            $this->markTestSkipped('Demo faculty not seeded; run php artisan db:seed.');
        }

        return $faculty;
    }

    private function submissionFor(User $faculty): AssignmentSubmission
    {
        $submission = AssignmentSubmission::whereHas(
            'assignment.course',
            fn (Builder $q) => $q->where('faculty_id', $faculty->id)
        )->first();

        if (! $submission) {
            $this->markTestSkipped('No seeded submission for the demo faculty.');
        }

        return $submission;
    }

    public function test_service_drafts_structured_feedback(): void
    {
        $draft = app(TeachingAssistantService::class)->generateFeedback([
            'assignmentTitle' => 'Problem Set 1',
            'grade' => 82,
            'totalPoints' => 100,
            'submissionExcerpt' => 'My answer explores sorting algorithms.',
            'rubricCriteria' => ['Correctness', 'Clarity'],
        ]);

        $this->assertArrayHasKey('feedback', $draft);
        $this->assertArrayHasKey('strengths', $draft);
        $this->assertArrayHasKey('improvements', $draft);
        $this->assertNotEmpty($draft['feedback']);
    }

    public function test_faculty_can_draft_feedback_for_their_submission(): void
    {
        $faculty = $this->faculty();
        $submission = $this->submissionFor($faculty);

        $this->actingAs($faculty)
            ->postJson("/faculty/submissions/{$submission->id}/feedback")
            ->assertOk()
            ->assertJsonStructure(['feedback', 'strengths', 'improvements']);
    }

    public function test_faculty_cannot_draft_feedback_for_an_unowned_submission(): void
    {
        $faculty = $this->faculty();
        $student = User::where('email', 'student@university.edu')->first();

        $foreign = AssignmentSubmission::whereHas(
            'assignment.course',
            fn (Builder $q) => $q->where('faculty_id', '!=', $faculty->id)
        )->first();

        if (! $foreign) {
            $this->markTestSkipped('No submission owned by another faculty to test against.');
        }

        $this->actingAs($faculty)
            ->postJson("/faculty/submissions/{$foreign->id}/feedback")
            ->assertForbidden();
    }

    public function test_student_cannot_draft_feedback(): void
    {
        $student = User::where('email', 'student@university.edu')->first();
        $submission = $this->submissionFor($this->faculty());

        $this->actingAs($student)
            ->post("/faculty/submissions/{$submission->id}/feedback")
            ->assertRedirect();
    }
}
