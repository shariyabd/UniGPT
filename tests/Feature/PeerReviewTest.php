<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Academic\Services\GradingService;
use App\Domain\Academic\Services\PeerReviewService;
use App\Domain\User\Models\User;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Notification;
use App\Models\PeerReview;
use App\Models\Section;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Anonymous peer review on assignments: faculty enable it per assignment;
 * students who submitted get up to two classmates' submissions to review
 * (load-balanced, anonymous both ways); faculty see average peer ratings in
 * the grading overview.
 */
class PeerReviewTest extends TestCase
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
     * @return array{assignment: Assignment, student: User, classmates: Collection}
     */
    private function scenario(int $classmateCount = 2): array
    {
        $student = $this->student();
        $section = Section::whereIn('id', $student->enrolledSectionIds())
            ->whereNotNull('faculty_id')
            ->firstOrFail();

        $classmates = $section->students()
            ->where('users.id', '!=', $student->id)
            ->limit($classmateCount)
            ->get();
        if ($classmates->count() < $classmateCount) {
            $this->markTestSkipped('Not enough classmates in the demo section.');
        }

        $assignment = Assignment::create([
            'course_id' => $section->course_id,
            'section_id' => $section->id,
            'title' => 'Peer Reviewed Essay',
            'type' => 'homework',
            'status' => 'published',
            'total_points' => 10,
            'peer_review_enabled' => true,
        ]);

        AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'content' => 'My own essay about databases.',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        foreach ($classmates as $index => $classmate) {
            AssignmentSubmission::create([
                'assignment_id' => $assignment->id,
                'user_id' => $classmate->id,
                'content' => "Classmate essay {$index}",
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
        }

        return ['assignment' => $assignment, 'student' => $student, 'classmates' => $classmates];
    }

    public function test_tasks_are_assigned_lazily_never_own_and_capped(): void
    {
        ['assignment' => $assignment, 'student' => $student] = $this->scenario();

        $tasks = app(PeerReviewService::class)->tasksFor($student, $assignment);

        $this->assertCount(2, $tasks);
        foreach ($tasks as $task) {
            $this->assertNotSame('My own essay about databases.', $task['content'], 'Students never review their own work.');
            $this->assertFalse($task['completed']);
            $this->assertArrayNotHasKey('student', $task);
            $this->assertArrayNotHasKey('author', $task);
        }

        // Re-requesting does not create duplicates.
        app(PeerReviewService::class)->tasksFor($student, $assignment);
        $this->assertSame(2, PeerReview::where('assignment_id', $assignment->id)->where('reviewer_id', $student->id)->count());
    }

    public function test_no_tasks_without_own_submission_or_when_disabled(): void
    {
        ['assignment' => $assignment, 'student' => $student, 'classmates' => $classmates] = $this->scenario();

        // A classmate who has NOT submitted gets no tasks.
        $nonSubmitter = Section::findOrFail($assignment->section_id)->students()
            ->whereNotIn('users.id', [$student->id, ...$classmates->pluck('id')])
            ->first();
        if ($nonSubmitter) {
            $this->assertSame([], app(PeerReviewService::class)->tasksFor($nonSubmitter, $assignment));
        }

        $assignment->update(['peer_review_enabled' => false]);
        $this->assertSame([], app(PeerReviewService::class)->tasksFor($student, $assignment));
    }

    public function test_submitting_a_review_notifies_the_reviewee_anonymously(): void
    {
        ['assignment' => $assignment, 'student' => $student, 'classmates' => $classmates] = $this->scenario();

        app(PeerReviewService::class)->tasksFor($student, $assignment);
        $review = PeerReview::where('assignment_id', $assignment->id)->where('reviewer_id', $student->id)->firstOrFail();

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}/peer-reviews/{$review->id}", [
                'rating' => 4,
                'comments' => 'Clear argument; add citations.',
            ])
            ->assertRedirect();

        $review->refresh();
        $this->assertSame(4, $review->rating);
        $this->assertNotNull($review->completed_at);

        $notification = Notification::where('user_id', $review->submission->user_id)
            ->where('title', 'Peer feedback received')
            ->first();
        $this->assertNotNull($notification);
        $this->assertStringNotContainsString($student->name, (string) $notification->message, 'The reviewer stays anonymous.');
    }

    public function test_reviewers_cannot_submit_someone_elses_task(): void
    {
        ['assignment' => $assignment, 'student' => $student, 'classmates' => $classmates] = $this->scenario();

        // A task belonging to a classmate reviewer, not to $student.
        app(PeerReviewService::class)->tasksFor($classmates->first(), $assignment);
        $foreign = PeerReview::where('assignment_id', $assignment->id)
            ->where('reviewer_id', $classmates->first()->id)
            ->firstOrFail();

        $this->actingAs($student)
            ->postJson("/assignments/{$assignment->id}/peer-reviews/{$foreign->id}", ['rating' => 5])
            ->assertForbidden();
    }

    public function test_received_feedback_is_anonymous_and_grading_shows_peer_average(): void
    {
        ['assignment' => $assignment, 'student' => $student, 'classmates' => $classmates] = $this->scenario();
        $service = app(PeerReviewService::class);

        // Both classmates review the student's submission.
        $studentSubmission = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('user_id', $student->id)->firstOrFail();
        foreach ([$classmates[0] ?? null, $classmates[1] ?? null] as $index => $classmate) {
            $review = PeerReview::create([
                'assignment_id' => $assignment->id,
                'submission_id' => $studentSubmission->id,
                'reviewer_id' => $classmate->id,
            ]);
            $service->submitReview($classmate, $review, $index === 0 ? 5 : 4, 'Nice work');
        }

        $received = $service->receivedFor($student, $assignment);
        $this->assertCount(2, $received);
        $this->assertStringNotContainsString('reviewer', (string) json_encode($received));

        $faculty = User::findOrFail(Section::findOrFail($assignment->section_id)->faculty_id);
        $overview = app(GradingService::class)->overview($faculty);
        $row = collect($overview['submissions'])->firstWhere('id', $studentSubmission->id);

        $this->assertNotNull($row);
        $this->assertSame(4.5, $row['peerReview']['average']);
        $this->assertSame(2, $row['peerReview']['completed']);
    }
}
