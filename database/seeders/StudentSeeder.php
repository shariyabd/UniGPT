<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Department;
use Database\Seeders\Concerns\SeedsUsers;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the bulk student body with a realistic, unbalanced distribution across
 * departments (CSE/BBA larger, niche departments smaller) and a junior-heavy
 * spread across semesters. Students are only placed into a (department, semester)
 * bucket that actually has courses, so EnrollmentSeeder can always give them a
 * full load.
 */
class StudentSeeder extends Seeder
{
    use SeedsUsers;

    /** Relative department popularity (slug => weight). Unlisted = weight 1. */
    private const DEPARTMENT_WEIGHTS = [
        'computer-science-engineering' => 5,
        'business-administration' => 4,
        'electrical-engineering' => 3,
        'mechanical-engineering' => 3,
        'civil-engineering' => 2,
        'psychology' => 2,
        'mathematics' => 2,
    ];

    public function run(): void
    {
        $count = (int) config('seeder.students', 500);

        if (DB::table('users')->where('email', 'student1@university.edu')->exists()) {
            $this->command->info('   Students already seeded; skipping.');

            return;
        }

        // Which semesters each department actually offers courses in.
        $semestersByDepartment = Course::query()
            ->select('department_id', 'semester')
            ->whereNotNull('department_id')
            ->whereNotNull('semester')
            ->distinct()
            ->get()
            ->groupBy('department_id')
            ->map(fn ($rows) => $rows->pluck('semester')->unique()->sort()->values()->all());

        if ($semestersByDepartment->isEmpty()) {
            $this->command->warn('   No catalog courses; run CourseSeeder first.');

            return;
        }

        $departmentPool = $this->weightedDepartmentPool($semestersByDepartment->keys()->all());

        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $departmentId = $departmentPool[array_rand($departmentPool)];
            $semester = $this->pickSemester($semestersByDepartment[$departmentId]);

            $rows[] = [
                'name' => fake()->name(),
                'email' => "student{$i}@university.edu",
                'department_id' => $departmentId,
                'student_id' => '2026'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'semester' => $semester,
            ];
        }

        $seeded = $this->createUsersWithRole($rows, UserRole::STUDENT);

        $this->command->info("   ✓ Students seeded ({$seeded})");
    }

    /**
     * Build a weighted pool of department ids (departments repeated by weight),
     * restricted to departments that have courses.
     *
     * @param  array<int, int>  $departmentIds
     * @return array<int, int>
     */
    private function weightedDepartmentPool(array $departmentIds): array
    {
        $weightBySlug = self::DEPARTMENT_WEIGHTS;
        $slugById = Department::whereIn('id', $departmentIds)->pluck('slug', 'id');

        $pool = [];
        foreach ($departmentIds as $id) {
            $weight = $weightBySlug[$slugById[$id] ?? ''] ?? 1;
            for ($w = 0; $w < $weight; $w++) {
                $pool[] = $id;
            }
        }

        return $pool;
    }

    /**
     * Pick a semester with a junior-heavy bias (lower semesters more likely).
     *
     * @param  array<int, int>  $semesters
     */
    private function pickSemester(array $semesters): int
    {
        $pool = [];
        foreach ($semesters as $semester) {
            $weight = max(1, 9 - $semester);
            for ($w = 0; $w < $weight; $w++) {
                $pool[] = $semester;
            }
        }

        return $pool[array_rand($pool)];
    }
}
