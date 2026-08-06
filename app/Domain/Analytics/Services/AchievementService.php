<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Services;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\User\Models\User;
use App\Enums\Achievement;
use App\Enums\NotificationType;
use App\Models\ActivityLog;
use App\Models\Flashcard;
use App\Models\PracticeAttempt;
use App\Models\UserAchievement;
use Illuminate\Support\Collection;

/**
 * Gamified badges layered on top of the signals the app already records
 * (streaks, XP, practice, flashcards, assignments…). Definitions live in the
 * {@see Achievement} enum; this service computes a student's current metric
 * snapshot, awards any newly-earned badges (once), and presents the full
 * catalogue with per-badge progress for the UI.
 */
class AchievementService
{
    /** Points awarded per attended (present/late) session — matches LeaderboardService. */
    private const ATTENDANCE_POINTS = 5;

    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * Award any badges the student now qualifies for that they don't already
     * hold, notify them, and return the freshly-earned ones.
     *
     * @return Collection<int, Achievement>
     */
    public function evaluate(User $student): Collection
    {
        $snapshot = $this->snapshot($student);

        $alreadyEarned = $student->achievements()
            ->pluck('achievement')
            ->map(fn (Achievement $a) => $a->value)
            ->flip();

        $newlyEarned = collect(Achievement::cases())
            ->reject(fn (Achievement $badge) => $alreadyEarned->has($badge->value))
            ->filter(fn (Achievement $badge) => ($snapshot[$badge->metric()] ?? 0) >= $badge->threshold());

        foreach ($newlyEarned as $badge) {
            UserAchievement::create([
                'user_id' => $student->id,
                'achievement' => $badge,
                'earned_at' => now(),
            ]);

            $this->notifications->notify(
                user: $student,
                type: NotificationType::ACHIEVEMENT,
                title: "Achievement unlocked: {$badge->title()}",
                message: $badge->description(),
                link: route('achievements'),
                data: ['achievement' => $badge->value],
            );
        }

        return $newlyEarned->values();
    }

    /**
     * The full badge catalogue with earned state + progress for the given
     * student. Runs {@see evaluate()} first so the page always reflects the
     * latest awards.
     *
     * @return array<string, mixed>
     */
    public function forUser(User $student): array
    {
        $this->evaluate($student);

        $snapshot = $this->snapshot($student);

        $earnedAt = $student->achievements()
            ->get()
            ->keyBy(fn (UserAchievement $a) => $a->achievement->value);

        $badges = collect(Achievement::cases())
            ->map(function (Achievement $badge) use ($snapshot, $earnedAt): array {
                $earned = $earnedAt->get($badge->value);
                $current = (int) ($snapshot[$badge->metric()] ?? 0);
                $threshold = $badge->threshold();

                return [
                    ...$badge->toArray(),
                    'earned' => $earned !== null,
                    'earnedAt' => $earned?->earned_at?->toIso8601String(),
                    'progress' => min($current, $threshold),
                    'percent' => $threshold > 0 ? (int) min(100, round($current / $threshold * 100)) : 0,
                ];
            });

        return [
            'badges' => $badges->values()->all(),
            'earnedCount' => $badges->where('earned', true)->count(),
            'totalCount' => $badges->count(),
        ];
    }

    /**
     * Compute every metric a badge can be measured against, in as few queries
     * as practical.
     *
     * @return array<string, int>
     */
    private function snapshot(User $student): array
    {
        $deckIds = $student->flashcardDecks()->pluck('id');

        return [
            'currentStreak' => $this->currentStreak($student),
            'xp' => $this->xp($student),
            'practiceAttempts' => PracticeAttempt::whereHas(
                'quiz',
                fn ($q) => $q->where('user_id', $student->id),
            )->count(),
            'perfectPractice' => PracticeAttempt::whereHas(
                'quiz',
                fn ($q) => $q->where('user_id', $student->id),
            )->whereColumn('score', '>=', 'total')->where('total', '>', 0)->count(),
            'flashcardsLearned' => $deckIds->isEmpty() ? 0 : Flashcard::whereIn('deck_id', $deckIds)
                ->where('repetitions', '>=', 2)
                ->where('interval_days', '>=', 6)
                ->count(),
            'assignmentsSubmitted' => $student->submissions()->count(),
            'classTestsTaken' => $student->classTestAttempts()->where('status', 'submitted')->count(),
            'notesCount' => $student->notes()->count(),
            'chatSessions' => $student->chatSessions()->count(),
        ];
    }

    /**
     * Leaderboard XP: submitted class-test marks + graded assignment marks +
     * attendance points. Mirrors LeaderboardService so badges and the
     * leaderboard agree on "XP".
     */
    private function xp(User $student): int
    {
        $test = (float) $student->classTestAttempts()->where('status', 'submitted')->sum('score');
        $assignment = (float) $student->submissions()->where('status', 'graded')->sum('grade');
        $attendance = $student->attendanceRecords()->whereIn('status', ['present', 'late'])->count()
            * self::ATTENDANCE_POINTS;

        return (int) round($test + $assignment + $attendance);
    }

    /**
     * Consecutive days (ending today, or yesterday with a one-day grace) on
     * which the student has any recorded activity. Bounded to the last year.
     */
    private function currentStreak(User $student): int
    {
        $days = ActivityLog::where('user_id', $student->id)
            ->where('created_at', '>=', now()->subDays(366)->startOfDay())
            ->orderByDesc('created_at')
            ->get(['created_at'])
            ->map(fn (ActivityLog $log) => $log->created_at->toDateString())
            ->unique()
            ->flip();

        if ($days->isEmpty()) {
            return 0;
        }

        $cursor = now()->startOfDay();
        if (! $days->has($cursor->toDateString()) && $days->has($cursor->copy()->subDay()->toDateString())) {
            $cursor = $cursor->subDay();
        }

        $streak = 0;
        while ($days->has($cursor->toDateString())) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }
}
