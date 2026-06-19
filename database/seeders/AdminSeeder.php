<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Department;
use Database\Seeders\Concerns\SeedsUsers;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds bulk administrator accounts (in addition to the canonical
 * admin@university.edu). Employee ids are namespaced away from the demo admin
 * (ADM001).
 */
class AdminSeeder extends Seeder
{
    use SeedsUsers;

    public function run(): void
    {
        $count = (int) config('seeder.admins', 10);

        if (DB::table('users')->where('email', 'admin1@university.edu')->exists()) {
            $this->command->info('   Admins already seeded; skipping.');

            return;
        }

        $departmentIds = Department::orderBy('id')->pluck('id')->all();

        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'name' => fake()->name(),
                'email' => "admin{$i}@university.edu",
                'department_id' => $departmentIds === [] ? null : $departmentIds[($i - 1) % count($departmentIds)],
                'employee_id' => 'ADM'.str_pad((string) (100 + $i), 4, '0', STR_PAD_LEFT),
                'bio' => 'University administrator managing platform operations and academic records.',
            ];
        }

        $seeded = $this->createUsersWithRole($rows, UserRole::ADMIN);

        $this->command->info("   ✓ Admins seeded ({$seeded})");
    }
}
