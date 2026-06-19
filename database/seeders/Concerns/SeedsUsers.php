<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use App\Enums\UserRole;
use App\Models\Role;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Bulk user creation for the population seeders. Inserts users via the query
 * builder (one shared bcrypt hash, reused for every account) and wires the
 * role_user pivot — orders of magnitude faster than per-model creation at the
 * 500+ account scale, while keeping a single hashing cost.
 */
trait SeedsUsers
{
    /**
     * Insert the given user rows and assign them all the given role.
     *
     * @param  array<int, array<string, mixed>>  $rows  partial user attributes
     *                                                  (name, email, department_id,
     *                                                  employee_id|student_id, bio,
     *                                                  semester, …)
     */
    protected function createUsersWithRole(array $rows, UserRole $role): int
    {
        if ($rows === []) {
            return 0;
        }

        $password = Hash::make((string) config('seeder.password', 'password'));
        $now = Carbon::now();

        $prepared = array_map(static function (array $row) use ($password, $now): array {
            return array_merge([
                'password' => $password,
                'email_verified_at' => $now,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ], $row);
        }, $rows);

        foreach (array_chunk($prepared, 200) as $chunk) {
            DB::table('users')->insert($chunk);
        }

        $roleId = Role::where('slug', $role->getSlug())->value('id');
        $emails = array_column($rows, 'email');

        $userIds = DB::table('users')->whereIn('email', $emails)->pluck('id');

        $pivot = $userIds->map(static fn (int $id): array => [
            'user_id' => $id,
            'role_id' => $roleId,
            'assigned_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        foreach (array_chunk($pivot, 500) as $chunk) {
            DB::table('role_user')->insert($chunk);
        }

        return count($rows);
    }
}
