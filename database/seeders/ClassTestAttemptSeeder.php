<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ClassTest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeds submitted class-test attempts (with plausible scores) for enrolled
 * students. This is the effort signal behind two of the new features:
 * Learning Analytics (score trend) and the Leaderboard (XP). Idempotent via the
 * (class_test_id, user_id) unique index + insertOrIgnore.
 */
class ClassTestAttemptSeeder extends Seeder
{
    public function run(): void
    {
        $rate = max(0, min(100, (int) config('seeder.class_test_attempt_rate', 70)));
        $now = Carbon::now();

        // Demo accounts are left untouched so the demo student always has a full
        // slate of takeable exams; their history is seeded deliberately by
        // DemoFeatureShowcaseSeeder instead. Resolve their ids once so we can
        // exclude them without hydrating User models.
        $demoEmails = (array) config('seeder.demo_emails', []);
        $demoUserIds = $demoEmails === []
            ? []
            : DB::table('users')->whereIn('email', $demoEmails)->pluck('id')->all();
        $demoUserIds = array_flip($demoUserIds);

        // Stream rows into the DB in batches instead of building one giant array —
        // seeding every enrolled student across every test can be tens of thousands
        // of rows, which exhausts the CLI memory_limit if held at once.
        $buffer = [];
        $seeded = 0;

        $flush = function () use (&$buffer, &$seeded): void {
            if ($buffer === []) {
                return;
            }
            DB::table('class_test_attempts')->insertOrIgnore($buffer);
            $seeded += count($buffer);
            $buffer = [];
        };

        ClassTest::where('status', 'published')
            ->select(['id', 'section_id', 'total_marks', 'available_from', 'duration_minutes'])
            ->chunkById(200, function ($tests) use ($rate, $now, $demoUserIds, &$buffer, $flush): void {
                foreach ($tests as $test) {
                    $totalMarks = (int) $test->total_marks;
                    if ($totalMarks <= 0) {
                        $totalMarks = (int) $test->questions()->sum('marks');
                    }
                    if ($totalMarks <= 0) {
                        continue;
                    }

                    $submittedAt = ($test->available_from ?? $now->copy()->subDays(3))->copy()->addHours(random_int(1, 48));

                    $studentIds = DB::table('course_user')
                        ->where('section_id', $test->section_id)
                        ->where('status', 'enrolled')
                        ->pluck('user_id');

                    foreach ($studentIds as $studentId) {
                        if (isset($demoUserIds[$studentId])) {
                            continue;
                        }

                        if (random_int(1, 100) > $rate) {
                            continue;
                        }

                        // Plausible spread: most score 55–100% of the paper.
                        $score = (int) round($totalMarks * random_int(55, 100) / 100);

                        $buffer[] = [
                            'class_test_id' => $test->id,
                            'user_id' => $studentId,
                            'status' => 'submitted',
                            'started_at' => $submittedAt->copy()->subMinutes(random_int(5, $test->duration_minutes ?: 15)),
                            'submitted_at' => $submittedAt,
                            'score' => $score,
                            'total_marks' => $totalMarks,
                            'violation_count' => 0,
                            'risk_score' => random_int(0, 15),
                            'created_at' => $submittedAt,
                            'updated_at' => $submittedAt,
                        ];

                        if (count($buffer) >= 1000) {
                            $flush();
                        }
                    }
                }
            });

        $flush();

        $this->command->info("   ✓ Class-test attempts seeded ({$seeded})");
    }
}
