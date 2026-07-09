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

        $tests = ClassTest::where('status', 'published')
            ->with(['section.students' => fn ($q) => $q->wherePivot('status', 'enrolled')])
            ->get();

        $rows = [];

        foreach ($tests as $test) {
            $totalMarks = (int) $test->total_marks;
            if ($totalMarks <= 0) {
                $totalMarks = (int) $test->questions()->sum('marks');
            }
            if ($totalMarks <= 0) {
                continue;
            }

            $submittedAt = ($test->available_from ?? $now->copy()->subDays(3))->copy()->addHours(random_int(1, 48));

            foreach ($test->section?->students ?? [] as $student) {
                if (random_int(1, 100) > $rate) {
                    continue;
                }

                // Plausible spread: most score 55–100% of the paper.
                $score = (int) round($totalMarks * random_int(55, 100) / 100);

                $rows[] = [
                    'class_test_id' => $test->id,
                    'user_id' => $student->id,
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
            }
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('class_test_attempts')->insertOrIgnore($chunk);
        }

        $this->command->info('   ✓ Class-test attempts seeded ('.count($rows).')');
    }
}
