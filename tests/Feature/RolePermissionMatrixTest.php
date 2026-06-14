<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RolePermissionMatrixTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $email): User
    {
        return User::create([
            'name' => 'Test '.$email,
            'email' => $email,
            'password' => Hash::make('password123'),
            'is_active' => true,
            'email_verified_at' => now(),
            'department_id' => Department::query()->value('id'),
        ]);
    }

    public function test_another_users_future_expiry_role_does_not_leak(): void
    {
        $alice = $this->makeUser('alice.matrix@university.edu');
        $bob = $this->makeUser('bob.matrix@university.edu');

        // Alice is a plain student (no expiry).
        $alice->assignRole(UserRole::STUDENT);

        // Bob holds a temporary admin role expiring in the future.
        $bob->assignRole(UserRole::ADMIN, expiresAt: now()->addDay());

        $aliceRoles = $alice->fresh()->roles->pluck('slug')->all();

        // Before the fix, Bob's future-expiry admin row leaked into every
        // user's role set because the OR escaped the user_id scope.
        $this->assertSame(['student'], $aliceRoles);
        $this->assertFalse($alice->fresh()->isAdmin());
    }

    public function test_owners_own_expired_role_is_excluded_but_active_one_kept(): void
    {
        $carol = $this->makeUser('carol.matrix@university.edu');

        $carol->assignRole(UserRole::STUDENT);
        $carol->assignRole(UserRole::FACULTY, expiresAt: now()->subDay());

        $roles = $carol->fresh()->roles->pluck('slug')->all();

        $this->assertContains('student', $roles);
        $this->assertNotContains('faculty', $roles);
    }

    public function test_seeded_student_can_reach_permission_gated_routes(): void
    {
        $student = User::where('email', 'student@university.edu')->first();
        if (! $student) {
            $this->markTestSkipped('Demo student not seeded; run php artisan db:seed.');
        }

        foreach (['/chat', '/documents', '/materials', '/roadmap', '/saved'] as $uri) {
            $this->actingAs($student)->get($uri)->assertOk();
        }
    }

    public function test_route_is_denied_when_role_lacks_required_permission(): void
    {
        $student = User::where('email', 'student@university.edu')->first();
        if (! $student) {
            $this->markTestSkipped('Demo student not seeded; run php artisan db:seed.');
        }

        // Revoke use_ai_chat from the student role (rolled back by the transaction).
        $role = Role::findByEnum(UserRole::STUDENT);
        $permission = Permission::where('slug', 'use_ai_chat')->first();
        $role->permissions()->detach($permission->id);

        $this->actingAs($student)->get('/chat')
            ->assertRedirect(route('dashboard'));

        // A route gated on a still-held permission remains reachable.
        $this->actingAs($student)->get('/documents')->assertOk();
    }
}
