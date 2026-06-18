<?php

namespace Tests\Feature;

use App\Domain\Academic\Services\CourseService;
use App\Domain\User\Models\User;
use App\Models\Term;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TermSeparationTest extends TestCase
{
    use DatabaseTransactions;

    private function student(): User
    {
        $student = User::where('email', 'student@university.edu')->first();

        if (! $student) {
            $this->markTestSkipped('Demo student not seeded; run php artisan db:seed.');
        }

        return $student;
    }

    public function test_exactly_one_current_term_exists(): void
    {
        if (Term::count() === 0) {
            $this->markTestSkipped('No terms seeded.');
        }

        $this->assertSame(1, Term::where('is_current', true)->count());
    }

    public function test_student_semester_is_an_integer(): void
    {
        $semester = $this->student()->semester;

        $this->assertTrue($semester === null || is_int($semester));
    }

    public function test_courses_are_annotated_with_term_and_current_flag(): void
    {
        $courses = app(CourseService::class)->studentCourses($this->student());

        if ($courses->isEmpty()) {
            $this->markTestSkipped('Student has no enrollments.');
        }

        foreach ($courses as $course) {
            $this->assertArrayHasKey('isCurrent', $course);
            $this->assertArrayHasKey('termName', $course);
        }
    }

    public function test_dashboard_only_lists_current_term_courses(): void
    {
        $student = $this->student();
        $all = app(CourseService::class)->studentCourses($student);

        if ($all->where('isCurrent', false)->isEmpty()) {
            $this->markTestSkipped('No past-term enrollment to exclude.');
        }

        $response = $this->actingAs($student)->get('/dashboard')->assertOk();

        $shownIds = collect($response->viewData('page')['props']['courses'])->pluck('id');
        $pastIds = $all->where('isCurrent', false)->pluck('id');

        // No past-term course should appear in the dashboard's course list.
        $this->assertTrue($shownIds->intersect($pastIds)->isEmpty());
        $this->assertEqualsCanonicalizing(
            $all->where('isCurrent', true)->pluck('id')->all(),
            $shownIds->all(),
        );
    }
}
