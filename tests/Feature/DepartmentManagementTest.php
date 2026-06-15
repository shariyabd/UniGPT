<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Models\Department;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::where('email', 'admin@university.edu')->first();

        if (! $admin) {
            $this->markTestSkipped('Demo admin not seeded; run php artisan db:seed.');
        }

        return $admin;
    }

    public function test_admin_can_create_a_department(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/departments', [
                'name' => 'Aerospace Engineering',
                'code' => 'AERO',
                'description' => 'Flight and space systems.',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('departments', [
            'name' => 'Aerospace Engineering',
            'slug' => 'aerospace-engineering',
            'code' => 'AERO',
        ]);
    }

    public function test_admin_can_update_a_department(): void
    {
        $dept = Department::create([
            'name' => 'Temp Dept',
            'slug' => 'temp-dept',
            'code' => 'TMP',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->patch("/admin/departments/{$dept->id}", [
                'name' => 'Renamed Dept',
                'code' => 'RNM',
                'is_active' => false,
            ])
            ->assertRedirect();

        $dept->refresh();
        $this->assertSame('Renamed Dept', $dept->name);
        $this->assertSame('renamed-dept', $dept->slug);
        $this->assertFalse($dept->is_active);
    }

    public function test_duplicate_department_name_is_rejected(): void
    {
        Department::create(['name' => 'Unique Dept', 'slug' => 'unique-dept', 'is_active' => true]);

        $this->actingAs($this->admin())
            ->post('/admin/departments', ['name' => 'Unique Dept'])
            ->assertSessionHasErrors('name');
    }

    public function test_department_with_users_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $dept = Department::where('id', $admin->department_id)->first();

        if (! $dept) {
            $this->markTestSkipped('Admin has no department.');
        }

        $this->actingAs($admin)
            ->delete("/admin/departments/{$dept->id}")
            ->assertRedirect();

        $this->assertDatabaseHas('departments', ['id' => $dept->id]);
    }

    public function test_empty_department_can_be_deleted(): void
    {
        $dept = Department::create(['name' => 'Empty Dept', 'slug' => 'empty-dept', 'is_active' => true]);

        $this->actingAs($this->admin())
            ->delete("/admin/departments/{$dept->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('departments', ['id' => $dept->id]);
    }

    public function test_non_admin_cannot_manage_departments(): void
    {
        $student = User::where('email', 'student@university.edu')->first();

        if (! $student) {
            $this->markTestSkipped('Demo student not seeded.');
        }

        $this->actingAs($student)
            ->get('/admin/departments')
            ->assertRedirect();
    }
}
