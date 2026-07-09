<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Services;

use App\Domain\User\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;

/**
 * Opt-in gamified leaderboard. Points (XP) are derived from academic effort —
 * class-test marks, graded assignment marks, and attended sessions — and are
 * intentionally decoupled from official grades/GPA. Only students who opt in
 * are ranked, and an alias is shown when they set one.
 */
class LeaderboardService
{
    /** Points awarded per attended (present/late) session. */
    private const ATTENDANCE_POINTS = 5;

    /**
     * Ranked entries for a scope, plus the viewer's own rank (even if outside
     * the returned page).
     *
     * @return array<string, mixed>
     */
    public function rankings(User $viewer, string $scope, ?int $sectionId = null): array
    {
        $entries = $this->scopedQuery($viewer, $scope, $sectionId)
            ->get()
            ->map(fn (User $student) => [
                'userId' => $student->id,
                'name' => $student->leaderboard_alias ?: $student->name,
                'points' => $this->pointsFor($student),
                'isViewer' => $student->id === $viewer->id,
            ])
            ->sortByDesc('points')
            ->values()
            ->map(fn (array $entry, int $index) => [...$entry, 'rank' => $index + 1]);

        $viewerEntry = $entries->firstWhere('isViewer');

        return [
            'entries' => $entries->take(100)->all(),
            'viewerRank' => $viewerEntry,
            'totalRanked' => $entries->count(),
        ];
    }

    /**
     * Base query of rankable students for the given scope.
     */
    private function scopedQuery(User $viewer, string $scope, ?int $sectionId): Builder
    {
        $query = User::query()
            ->where('is_active', true)
            ->where('leaderboard_opt_in', true)
            ->whereHas('roles', fn (Builder $q) => $q->where('slug', UserRole::STUDENT->getSlug()))
            ->withSum(['classTestAttempts as test_points' => fn (Builder $q) => $q->where('status', 'submitted')], 'score')
            ->withSum(['submissions as assignment_points' => fn (Builder $q) => $q->where('status', 'graded')], 'grade')
            ->withCount(['attendanceRecords as present_count' => fn (Builder $q) => $q->whereIn('status', ['present', 'late'])]);

        return match ($scope) {
            'section' => $query->whereHas(
                'enrolledSections',
                fn (Builder $q) => $q->where('sections.id', $sectionId),
            ),
            'semester' => $query
                ->where('department_id', $viewer->department_id)
                ->where('semester', $viewer->semester),
            default => $query->where('department_id', $viewer->department_id),
        };
    }

    private function pointsFor(User $student): int
    {
        $test = (float) ($student->test_points ?? 0);
        $assignment = (float) ($student->assignment_points ?? 0);
        $attendance = (int) ($student->present_count ?? 0) * self::ATTENDANCE_POINTS;

        return (int) round($test + $assignment + $attendance);
    }
}
