<?php

declare(strict_types=1);

namespace App\Domain\Notification\Services;

use App\Domain\Academic\Services\StudyPlannerService;
use App\Domain\User\Models\User;
use App\Models\AssignmentSubmission;
use App\Models\Flashcard;
use App\Models\OfficeHourSlot;

/**
 * Builds the weekly email digest for a student: deadlines in the next 7 days,
 * grades posted in the last 7, booked office hours and due flashcards.
 * Returns null when there is nothing worth emailing, and honours the
 * `email_digest` preference (opt-out; on by default).
 */
class EmailDigestService
{
    private const DEADLINE_DAYS = 7;

    private const GRADES_LOOKBACK_DAYS = 7;

    public function __construct(private readonly StudyPlannerService $planner) {}

    /**
     * Whether this user wants digest/reminder emails at all.
     */
    public function wantsEmails(User $user): bool
    {
        return ($user->preferences['email_digest'] ?? true) !== false;
    }

    /**
     * The digest payload for a student, or null when it would be empty.
     *
     * @return array<string, mixed>|null
     */
    public function digestFor(User $student): ?array
    {
        $digest = [
            'deadlines' => $this->upcomingDeadlines($student),
            'grades' => $this->recentGrades($student),
            'officeHours' => $this->bookedOfficeHours($student),
            'flashcardsDue' => $this->dueFlashcards($student),
        ];

        $hasContent = $digest['deadlines'] !== []
            || $digest['grades'] !== []
            || $digest['officeHours'] !== []
            || $digest['flashcardsDue'] > 0;

        return $hasContent ? $digest : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function upcomingDeadlines(User $student): array
    {
        return collect($this->planner->deadlines($student))
            ->filter(fn (array $deadline) => $deadline['daysRemaining'] <= self::DEADLINE_DAYS)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentGrades(User $student): array
    {
        return AssignmentSubmission::query()
            ->where('user_id', $student->id)
            ->where('status', 'graded')
            ->where('graded_at', '>=', now()->subDays(self::GRADES_LOOKBACK_DAYS))
            ->with('assignment.course:id,code')
            ->get()
            ->map(fn (AssignmentSubmission $submission) => [
                'assignment' => $submission->assignment?->title,
                'course' => $submission->assignment?->course?->code,
                'grade' => (float) $submission->grade,
                'total' => $submission->assignment?->total_points,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function bookedOfficeHours(User $student): array
    {
        return OfficeHourSlot::query()
            ->where('booked_by', $student->id)
            ->whereBetween('starts_at', [now(), now()->addDays(self::DEADLINE_DAYS)])
            ->with('faculty:id,name')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (OfficeHourSlot $slot) => [
                'faculty' => $slot->faculty?->name,
                'when' => $slot->starts_at->format('D, M j · g:i A'),
                'location' => $slot->location,
            ])
            ->values()
            ->all();
    }

    private function dueFlashcards(User $student): int
    {
        return Flashcard::query()
            ->whereHas('deck', fn ($query) => $query->where('user_id', $student->id))
            ->due()
            ->count();
    }
}
