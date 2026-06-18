<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Models\Course;
use App\Models\Term;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use DatabaseTransactions;

    private function user(string $email): User
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->markTestSkipped("Demo user {$email} not seeded.");
        }

        return $user;
    }

    public function test_admin_can_view_the_course_catalog(): void
    {
        $this->actingAs($this->user('admin@university.edu'))
            ->get('/admin/courses')
            ->assertOk();
    }

    public function test_admin_can_create_a_catalog_course(): void
    {
        $this->actingAs($this->user('admin@university.edu'))
            ->post('/admin/courses', [
                'code' => 'TST999',
                'name' => 'Test Course',
                'credits' => 3,
                'semester' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('courses', ['code' => 'TST999', 'name' => 'Test Course']);
    }

    public function test_admin_can_add_a_section_and_assign_faculty(): void
    {
        $course = Course::where('code', 'CS305')->first();
        $faculty = $this->user('prof.jones@university.edu');

        if (! $course) {
            $this->markTestSkipped('CS305 not seeded.');
        }

        $this->actingAs($this->user('admin@university.edu'))
            ->post("/admin/courses/{$course->id}/sections", [
                'label' => 'Z',
                'term_id' => Term::where('is_current', true)->value('id'),
                'faculty_id' => $faculty->id,
                'max_enrollment' => 40,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sections', [
            'course_id' => $course->id,
            'label' => 'Z',
            'faculty_id' => $faculty->id,
        ]);
    }

    public function test_faculty_can_no_longer_create_courses(): void
    {
        $faculty = $this->user('prof.smith@university.edu');

        $this->assertFalse(Route::has('faculty.courses.create'));
        $this->assertFalse($faculty->hasPermission('create_course'));
    }

    public function test_faculty_cannot_access_admin_catalog(): void
    {
        // The role middleware redirects non-admins away from admin routes.
        $response = $this->actingAs($this->user('prof.smith@university.edu'))->get('/admin/courses');

        $this->assertContains($response->status(), [302, 403]);
        $response->assertDontSee('Course Catalog');
    }

    public function test_faculty_assigned_to_a_section_can_manage_that_course(): void
    {
        $jones = $this->user('prof.jones@university.edu');
        $cs301 = Course::where('code', 'CS301')->first();

        if (! $cs301) {
            $this->markTestSkipped('CS301 not seeded.');
        }

        // Jones teaches CS301 section B (assigned by the seeder), so may manage it.
        $this->assertTrue($jones->can('manage', $cs301));
    }
}
