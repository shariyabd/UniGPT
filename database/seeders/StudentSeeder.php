<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use Database\Seeders\Concerns\PlansAcademicLoad;
use Database\Seeders\Concerns\SeedsUsers;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the bulk student body straight from the academic load plan: every active
 * (department, semester) bucket receives exactly the head-count needed to fill
 * each of its courses' sections to the target size (see PlansAcademicLoad). This
 * replaces the old flat 2000-student count, which scattered students too thinly
 * for sections to fill. Departments stay unbalanced (CSE/BBA larger) because the
 * plan gives popular departments more sections and CSE more courses.
 */
class StudentSeeder extends Seeder
{
    use PlansAcademicLoad;
    use SeedsUsers;

    public function run(): void
    {
        if (DB::table('users')->where('email', 'student1@university.edu')->exists()) {
            $this->command->info('   Students already seeded; skipping.');

            return;
        }

        $plan = $this->academicLoadPlan();

        if ($plan->isEmpty()) {
            $this->command->warn('   No catalog courses; run CourseSeeder first.');

            return;
        }

        $rows = [];
        $index = 0;

        foreach ($plan as $bucket) {
            for ($n = 0; $n < $bucket['student_count']; $n++) {
                $index++;

                $rows[] = [
                    'name' => fake()->name(),
                    'email' => "student{$index}@university.edu",
                    'department_id' => $bucket['department_id'],
                    'student_id' => '2026'.str_pad((string) $index, 6, '0', STR_PAD_LEFT),
                    'semester' => $bucket['semester'],
                ];
            }
        }

        $seeded = $this->createUsersWithRole($rows, UserRole::STUDENT);

        $this->command->info("   ✓ Students seeded ({$seeded} across {$plan->count()} active buckets)");
    }
}
