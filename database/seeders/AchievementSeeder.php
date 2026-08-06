<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Analytics\Services\AchievementService;
use App\Domain\User\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;

/**
 * Backfills achievements for demo/showcase students by evaluating the badge
 * catalogue against the data earlier seeders produced (streaks, class-test and
 * assignment marks, practice, flashcards, notes, chat).
 *
 * Scoped to the demo student + leaderboard opt-ins (a curated cohort) so
 * seeding stays fast and doesn't emit a notification storm across the full
 * multi-thousand student population. Runs last: it depends on every
 * signal-producing seeder having already run.
 */
class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(AchievementService::class);

        User::query()
            ->whereHas('roles', fn ($q) => $q->where('slug', UserRole::STUDENT->getSlug()))
            ->where(function ($q) {
                $q->where('email', 'student@university.edu')
                    ->orWhere('leaderboard_opt_in', true);
            })
            ->chunkById(200, function ($students) use ($service) {
                foreach ($students as $student) {
                    $service->evaluate($student);
                }
            });
    }
}
