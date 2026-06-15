<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Models\AttendanceRecord;
use App\Models\Course;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AttendanceTest extends TestCase
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

    private function student(): User
    {
        $student = User::where('email', 'student@university.edu')->first();

        if (! $student) {
            $this->markTestSkipped('Demo student not seeded; run php artisan db:seed.');
        }

        return $student;
    }

    private function courseWithRoster(User $faculty): Course
    {
        $course = $faculty->teachingCourses()->whereHas('students')->first();

        if (! $course) {
            $this->markTestSkipped('No seeded course with an enrolled student.');
        }

        return $course;
    }

    public function test_faculty_can_view_attendance_page_for_own_course(): void
    {
        $faculty = $this->faculty();
        $course = $this->courseWithRoster($faculty);

        $this->actingAs($faculty)
            ->get("/faculty/courses/{$course->id}/attendance")
            ->assertOk();
    }

    public function test_faculty_can_mark_and_upsert_attendance(): void
    {
        $faculty = $this->faculty();
        $course = $this->courseWithRoster($faculty);
        $student = $course->students()->first();
        $date = now()->toDateString();

        // First mark: present.
        $this->actingAs($faculty)
            ->post("/faculty/courses/{$course->id}/attendance", [
                'date' => $date,
                'entries' => [['user_id' => $student->id, 'status' => 'present']],
            ])
            ->assertRedirect();

        // Re-mark same day: absent. Should update, not duplicate.
        $this->actingAs($faculty)
            ->post("/faculty/courses/{$course->id}/attendance", [
                'date' => $date,
                'entries' => [['user_id' => $student->id, 'status' => 'absent']],
            ])
            ->assertRedirect();

        $records = AttendanceRecord::where('course_id', $course->id)
            ->where('user_id', $student->id)
            ->whereDate('date', $date)
            ->get();

        $this->assertCount(1, $records);
        $this->assertSame('absent', $records->first()->status->value);
        $this->assertEquals($faculty->id, $records->first()->marked_by);
    }

    public function test_faculty_cannot_mark_attendance_for_unowned_course(): void
    {
        $faculty = $this->faculty();
        $student = $this->student();

        // A course owned by someone else (the student id stands in as a non-owner).
        $foreign = Course::create([
            'code' => 'ZZ999',
            'name' => 'Foreign Course',
            'faculty_id' => $student->id,
            'semester' => 1,
            'credits' => 3,
            'is_active' => true,
        ]);

        $this->actingAs($faculty)
            ->post("/faculty/courses/{$foreign->id}/attendance", [
                'date' => now()->toDateString(),
                'entries' => [['user_id' => $student->id, 'status' => 'present']],
            ])
            ->assertForbidden();
    }

    public function test_student_can_view_their_attendance(): void
    {
        $this->actingAs($this->student())
            ->get('/attendance')
            ->assertOk();
    }

    public function test_student_cannot_mark_attendance(): void
    {
        $faculty = $this->faculty();
        $course = $this->courseWithRoster($faculty);

        // Role middleware should reject a student hitting a faculty route.
        $this->actingAs($this->student())
            ->post("/faculty/courses/{$course->id}/attendance", [
                'date' => now()->toDateString(),
                'entries' => [],
            ])
            ->assertRedirect();
    }
}
