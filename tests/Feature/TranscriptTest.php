<?php

namespace Tests\Feature;

use App\Domain\Academic\Services\TranscriptService;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TranscriptTest extends TestCase
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

    public function test_student_can_view_their_transcript(): void
    {
        $this->actingAs($this->student())
            ->get('/transcript')
            ->assertOk();
    }

    public function test_service_groups_courses_by_semester_with_a_cgpa(): void
    {
        $student = $this->student();

        $transcript = app(TranscriptService::class)->build($student);

        $this->assertArrayHasKey('semesters', $transcript);
        $this->assertArrayHasKey('summary', $transcript);
        $this->assertArrayHasKey('cgpa', $transcript['summary']);
        $this->assertSame($student->name, $transcript['student']['name']);

        // CGPA is either null (no graded courses) or within the 0–4 scale.
        $cgpa = $transcript['summary']['cgpa'];
        if ($cgpa !== null) {
            $this->assertGreaterThanOrEqual(0, $cgpa);
            $this->assertLessThanOrEqual(4, $cgpa);
        }
    }
}
