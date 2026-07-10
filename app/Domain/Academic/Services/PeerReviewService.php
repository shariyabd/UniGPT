<?php

declare(strict_types=1);

namespace App\Domain\Academic\Services;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\User\Models\User;
use App\Enums\NotificationType;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\PeerReview;
use Illuminate\Support\Collection;

/**
 * Anonymous peer review on assignments. When a faculty member enables it,
 * every student who has submitted gets up to REVIEWS_PER_STUDENT classmates'
 * submissions to review (rating + comments). Assignment is lazy and
 * load-balanced (least-reviewed submissions first); anonymity holds both
 * ways — reviewers never see authors, reviewees never see reviewers.
 */
class PeerReviewService
{
    public const REVIEWS_PER_STUDENT = 2;

    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * The student's review tasks for an assignment, topping up to
     * REVIEWS_PER_STUDENT from classmates' submissions when possible.
     * Only students who submitted themselves get tasks.
     *
     * @return array<int, array<string, mixed>>
     */
    public function tasksFor(User $student, Assignment $assignment): array
    {
        if (! $assignment->peer_review_enabled) {
            return [];
        }

        $ownSubmission = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('user_id', $student->id)
            ->first();

        if ($ownSubmission === null) {
            return [];
        }

        $existing = PeerReview::where('assignment_id', $assignment->id)
            ->where('reviewer_id', $student->id)
            ->get();

        $missing = self::REVIEWS_PER_STUDENT - $existing->count();
        if ($missing > 0) {
            $existing = $existing->merge(
                $this->assignTasks($student, $assignment, $existing->pluck('submission_id'), $missing),
            );
        }

        return $existing
            ->load('submission:id,content')
            ->map(fn (PeerReview $review) => [
                'id' => $review->id,
                // The submission content only — never the author.
                'content' => (string) ($review->submission?->content ?? ''),
                'completed' => $review->rating !== null,
                'rating' => $review->rating,
                'comments' => $review->comments,
            ])
            ->values()
            ->all();
    }

    /**
     * Record a completed review; the reviewee is notified anonymously.
     */
    public function submitReview(User $reviewer, PeerReview $review, int $rating, ?string $comments): PeerReview
    {
        abort_unless($review->reviewer_id === $reviewer->id, 403);
        abort_unless((bool) $review->assignment?->peer_review_enabled, 422, 'Peer review is not enabled for this assignment.');

        $firstCompletion = $review->rating === null;

        $review->update([
            'rating' => max(1, min(5, $rating)),
            'comments' => $comments !== null ? trim($comments) : null,
            'completed_at' => now(),
        ]);

        if ($firstCompletion && ($reviewee = $review->submission?->student) !== null) {
            $this->notifications->notify(
                user: $reviewee,
                type: NotificationType::ASSIGNMENT,
                title: 'Peer feedback received',
                message: "A classmate reviewed your \"{$review->assignment->title}\" submission.",
                link: route('assignments.show', $review->assignment_id),
            );
        }

        return $review;
    }

    /**
     * Completed reviews of the student's own submission — anonymized and
     * shuffled so ordering can't identify reviewers.
     *
     * @return array<int, array{rating: int, comments: ?string}>
     */
    public function receivedFor(User $student, Assignment $assignment): array
    {
        if (! $assignment->peer_review_enabled) {
            return [];
        }

        return PeerReview::query()
            ->where('assignment_id', $assignment->id)
            ->whereHas('submission', fn ($query) => $query->where('user_id', $student->id))
            ->completed()
            ->get(['rating', 'comments'])
            ->map(fn (PeerReview $review) => [
                'rating' => $review->rating,
                'comments' => $review->comments,
            ])
            ->shuffle()
            ->values()
            ->all();
    }

    /**
     * Average peer rating + counts per submission, for the faculty grading
     * screen.
     *
     * @param  array<int, int>  $submissionIds
     * @return Collection<int, array{average: float, completed: int}> keyed by submission id
     */
    public function statsFor(array $submissionIds): Collection
    {
        if ($submissionIds === []) {
            return collect();
        }

        return PeerReview::query()
            ->whereIn('submission_id', $submissionIds)
            ->completed()
            ->selectRaw('submission_id, AVG(rating) as average, COUNT(*) as completed')
            ->groupBy('submission_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->submission_id => [
                'average' => round((float) $row->average, 1),
                'completed' => (int) $row->completed,
            ]]);
    }

    /**
     * Pick the least-reviewed classmate submissions and create pending tasks.
     *
     * @param  Collection<int, int>  $alreadyAssigned
     * @return Collection<int, PeerReview>
     */
    private function assignTasks(User $student, Assignment $assignment, Collection $alreadyAssigned, int $count): Collection
    {
        $candidates = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('user_id', '!=', $student->id)
            ->whereNotIn('id', $alreadyAssigned)
            ->withCount('peerReviews')
            ->orderBy('peer_reviews_count')
            ->orderBy('id')
            ->limit($count)
            ->get();

        return $candidates->map(fn (AssignmentSubmission $submission) => PeerReview::create([
            'assignment_id' => $assignment->id,
            'submission_id' => $submission->id,
            'reviewer_id' => $student->id,
        ]));
    }
}
