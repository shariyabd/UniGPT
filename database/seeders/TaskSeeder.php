<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Section;
use App\Models\Term;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeds academic to-do tasks for bulk students (lab reports, presentations,
 * reading), tied to a course they're enrolled in. Mix of pending and completed.
 */
class TaskSeeder extends Seeder
{
    private const TITLES = [
        'Submit Lab Report',
        'Prepare Class Presentation',
        'Assignment Discussion',
        'Complete Reading Assignment',
        'Group Project Meeting',
    ];

    private const PRIORITIES = ['low', 'medium', 'high'];

    public function run(): void
    {
        $term = Term::currentTerm();
        if (! $term) {
            return;
        }

        $perStudent = (int) config('seeder.tasks_per_student', 3);
        $demoCodes = (array) config('seeder.demo_course_codes', []);
        $now = Carbon::now();

        $sectionIds = Section::query()
            ->where('term_id', $term->id)
            ->whereHas('course', fn ($q) => $q->whereNotIn('code', $demoCodes))
            ->pluck('id')->all();

        $coursesByStudent = DB::table('course_user')
            ->whereIn('section_id', $sectionIds)
            ->where('status', 'enrolled')
            ->get(['user_id', 'course_id'])
            ->groupBy('user_id');

        $rows = [];
        foreach ($coursesByStudent as $userId => $enrollments) {
            $courseIds = $enrollments->pluck('course_id')->all();

            for ($t = 0; $t < $perStudent; $t++) {
                $completed = random_int(1, 100) <= 35;
                $title = self::TITLES[$t % count(self::TITLES)];

                $rows[] = [
                    'user_id' => $userId,
                    'course_id' => $courseIds[array_rand($courseIds)],
                    'title' => $title,
                    'description' => $title.' — coursework item to track this semester.',
                    'due_date' => $now->copy()->addDays(random_int(-7, 21))->toDateString(),
                    'priority' => self::PRIORITIES[array_rand(self::PRIORITIES)],
                    'is_completed' => $completed,
                    'completed_at' => $completed ? $now : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('tasks')->insert($chunk);
        }

        $this->command->info('   ✓ Tasks seeded ('.count($rows).')');
    }
}
