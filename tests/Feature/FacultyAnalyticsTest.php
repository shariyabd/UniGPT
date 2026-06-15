<?php

namespace Tests\Feature;

use App\Domain\Analytics\Services\FacultyAnalyticsService;
use App\Domain\User\Models\User;
use App\Models\Course;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FacultyAnalyticsTest extends TestCase
{
    use DatabaseTransactions;

    private function faculty(): User
    {
        $faculty = User::where('email', 'prof.smith@university.edu')->first();

        if (! $faculty) {
            $this->markTestSkipped('Demo faculty not seeded; run php artisan db:seed.');
        }

        return $faculty;
    }

    public function test_faculty_can_view_analytics_overview(): void
    {
        $this->actingAs($this->faculty())
            ->get('/faculty/analytics')
            ->assertOk();
    }

    public function test_faculty_can_view_course_scoped_analytics(): void
    {
        $faculty = $this->faculty();
        $course = $faculty->teachingCourses()->first();

        if (! $course) {
            $this->markTestSkipped('Demo faculty teaches no courses.');
        }

        $this->actingAs($faculty)
            ->get("/faculty/courses/{$course->id}/analytics")
            ->assertOk();
    }

    public function test_faculty_cannot_view_analytics_for_an_unowned_course(): void
    {
        $faculty = $this->faculty();

        $foreign = Course::create([
            'code' => 'ZZ900',
            'name' => 'Unowned',
            'faculty_id' => User::where('email', 'student@university.edu')->value('id'),
            'credits' => 3,
            'max_enrollment' => 30,
            'is_active' => true,
        ]);

        $this->actingAs($faculty)
            ->get("/faculty/courses/{$foreign->id}/analytics")
            ->assertForbidden();
    }

    public function test_service_returns_a_structured_report(): void
    {
        $faculty = $this->faculty();

        $analytics = app(FacultyAnalyticsService::class)->build($faculty);

        $this->assertArrayHasKey('overview', $analytics);
        $this->assertArrayHasKey('report', $analytics);
        $this->assertArrayHasKey('totalStudents', $analytics['overview']);

        if ($analytics['report'] !== null) {
            $this->assertArrayHasKey('gradeDistribution', $analytics['report']);
            $this->assertArrayHasKey('atRisk', $analytics['report']);
            $this->assertArrayHasKey('attendance', $analytics['report']);
        }
    }
}
