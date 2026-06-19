<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Department;
use Database\Seeders\Concerns\SeedsUsers;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the bulk faculty body, evenly distributed across departments. Each member
 * carries an academic designation (folded into the bio, since the schema has no
 * designation column) and an employee id namespaced away from the demo profs
 * (FAC001/FAC002). Teaching assignments are made later by SectionSeeder.
 */
class FacultySeeder extends Seeder
{
    use SeedsUsers;

    private const DESIGNATIONS = [
        'Lecturer',
        'Senior Lecturer',
        'Assistant Professor',
        'Associate Professor',
        'Professor',
    ];

    public function run(): void
    {
        $count = (int) config('seeder.faculty', 80);

        if (DB::table('users')->where('email', 'faculty1@university.edu')->exists()) {
            $this->command->info('   Faculty already seeded; skipping.');

            return;
        }

        $departmentIds = Department::orderBy('id')->pluck('id')->all();

        if ($departmentIds === []) {
            $this->command->warn('   No departments; run RBACSeeder first.');

            return;
        }

        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $departmentId = $departmentIds[($i - 1) % count($departmentIds)];
            $designation = self::DESIGNATIONS[($i - 1) % count(self::DESIGNATIONS)];

            $rows[] = [
                'name' => fake()->name(),
                'email' => "faculty{$i}@university.edu",
                'department_id' => $departmentId,
                'employee_id' => 'FAC'.str_pad((string) (100 + $i), 4, '0', STR_PAD_LEFT),
                'bio' => "{$designation} in the department, with research and teaching interests across the core curriculum.",
            ];
        }

        $seeded = $this->createUsersWithRole($rows, UserRole::FACULTY);

        $this->command->info("   ✓ Faculty seeded ({$seeded})");
    }
}
