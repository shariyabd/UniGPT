<?php

declare(strict_types=1);

namespace App\Domain\Academic\Services;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\User\Models\User;
use App\Enums\NotificationType;
use App\Models\CourseFeedback;
use App\Models\Section;
use Illuminate\Support\Collection;

/**
 * Anonymous mid-semester course feedback. Faculty open a collection window
 * per section; enrolled students submit one rating + comment each (editable
 * while open). Faculty see aggregates and anonymized comments only once
 * MIN_RESPONSES exist — below that, only the count — and comments come back
 * shuffled with no timestamps, so ordering can't de-anonymize early
 * submitters.
 */
class CourseFeedbackService
{
    /**
     * Comments stay hidden until this many students have responded, so a
     * lone early response can't be attributed.
     */
    public const MIN_RESPONSES = 3;

    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * The student's enrolled sections with each window's state and their own
     * submission (their own is fine to show them back).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function sectionsForStudent(User $student): Collection
    {
        $sectionIds = $student->enrolledSectionIds();
        if ($sectionIds->isEmpty()) {
            return collect();
        }

        $own = CourseFeedback::where('user_id', $student->id)
            ->whereIn('section_id', $sectionIds)
            ->get()
            ->keyBy('section_id');

        return Section::whereIn('id', $sectionIds)
            ->with(['course:id,code,name', 'faculty:id,name'])
            ->get()
            ->map(fn (Section $section) => [
                'sectionId' => $section->id,
                'label' => $section->label,
                'course' => $section->course?->only(['code', 'name']),
                'faculty' => $section->faculty?->name,
                'open' => (bool) $section->feedback_open,
                'submitted' => $own->has($section->id),
                'own' => $own->has($section->id) ? [
                    'rating' => $own[$section->id]->rating,
                    'comment' => $own[$section->id]->comment,
                ] : null,
            ])
            ->sortByDesc('open')
            ->values();
    }

    /**
     * Create or update the student's single response for a section.
     */
    public function submit(User $student, Section $section, int $rating, ?string $comment): CourseFeedback
    {
        abort_unless(
            $student->enrolledSectionIds()->contains($section->id),
            403,
            'You can only give feedback for sections you are enrolled in.',
        );
        abort_unless((bool) $section->feedback_open, 422, 'Feedback is not open for this section.');

        return CourseFeedback::updateOrCreate(
            ['section_id' => $section->id, 'user_id' => $student->id],
            ['rating' => max(1, min(5, $rating)), 'comment' => $comment !== null ? trim($comment) : null],
        );
    }

    /**
     * The faculty member's sections with anonymized aggregates. Comments (and
     * the rating distribution) are withheld below MIN_RESPONSES.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function sectionsForFaculty(User $faculty): Collection
    {
        return Section::where('faculty_id', $faculty->id)
            ->with('course:id,code,name')
            ->withCount('feedback')
            ->withAvg('feedback', 'rating')
            ->get()
            ->map(function (Section $section) {
                $count = (int) $section->feedback_count;
                $revealed = $count >= self::MIN_RESPONSES;

                return [
                    'sectionId' => $section->id,
                    'label' => $section->label,
                    'course' => $section->course?->only(['code', 'name']),
                    'open' => (bool) $section->feedback_open,
                    'responseCount' => $count,
                    'minResponses' => self::MIN_RESPONSES,
                    'revealed' => $revealed,
                    'averageRating' => $revealed && $section->feedback_avg_rating !== null
                        ? round((float) $section->feedback_avg_rating, 1)
                        : null,
                    'ratingDistribution' => $revealed ? $this->ratingDistribution($section) : null,
                    // Shuffled, stripped of every identifier and timestamp.
                    'comments' => $revealed ? CourseFeedback::where('section_id', $section->id)
                        ->whereNotNull('comment')
                        ->where('comment', '!=', '')
                        ->pluck('comment')
                        ->shuffle()
                        ->values()
                        ->all() : [],
                ];
            })
            ->sortByDesc('responseCount')
            ->values();
    }

    /**
     * Open/close the collection window; opening notifies the roster.
     */
    public function toggle(User $faculty, Section $section): Section
    {
        abort_unless($section->faculty_id === $faculty->id, 403);

        $section->update(['feedback_open' => ! $section->feedback_open]);

        if ($section->feedback_open) {
            $this->notifications->notifyMany(
                users: $section->students()->get(),
                type: NotificationType::ANNOUNCEMENT,
                title: 'Course feedback requested',
                message: "Share anonymous mid-semester feedback for {$section->course?->code} ({$section->label}).",
                link: route('course-feedback'),
            );
        }

        return $section;
    }

    /**
     * @return array<int, int> rating (1-5) → count
     */
    private function ratingDistribution(Section $section): array
    {
        $counts = CourseFeedback::where('section_id', $section->id)
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        return collect(range(1, 5))
            ->mapWithKeys(fn (int $rating) => [$rating => (int) ($counts[$rating] ?? 0)])
            ->all();
    }
}
