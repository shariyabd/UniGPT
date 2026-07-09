<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Opts students into the leaderboard so it has a populated ranking. The demo
 * student is always opted in (so the demo login sees itself ranked); a
 * configurable share of bulk students opt in, some with a playful alias. Points
 * (XP) themselves are derived at read time from class-test / attendance data.
 */
class LeaderboardSeeder extends Seeder
{
    private const ALIASES = [
        'CodeNinja', 'ByteMe', 'NullPointer', 'StackSmasher', 'LoopWizard',
        'SyntaxError', 'BigOh', 'CacheMoney', 'GitGud', 'SegFault',
    ];

    public function run(): void
    {
        $rate = max(0, min(100, (int) config('seeder.leaderboard_opt_in_rate', 60)));

        // Demo student: always visible, no alias (real name reads clearly in review).
        User::where('email', 'student@university.edu')
            ->update(['leaderboard_opt_in' => true, 'leaderboard_alias' => null]);

        $students = User::whereHas('roles', fn ($q) => $q->where('slug', 'student'))
            ->where('email', '!=', 'student@university.edu')
            ->pluck('id');

        $optedIn = 0;
        foreach ($students as $index => $id) {
            if (random_int(1, 100) > $rate) {
                continue;
            }

            DB::table('users')->where('id', $id)->update([
                'leaderboard_opt_in' => true,
                // ~1 in 3 opted-in students pick an alias.
                'leaderboard_alias' => random_int(1, 3) === 1
                    ? self::ALIASES[$index % count(self::ALIASES)].random_int(1, 99)
                    : null,
            ]);
            $optedIn++;
        }

        $this->command->info('   ✓ Leaderboard opt-ins seeded ('.($optedIn + 1).')');
    }
}
