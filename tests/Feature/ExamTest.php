<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Enums\NotificationType;
use App\Models\Course;
use App\Models\Exam;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExamTest extends TestCase
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

    public function test_admin_can_schedule_an_exam_and_notify_enrolled_students(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $course = $student->enrolledCourses()->first();

        if (! $course) {
            $this->markTestSkipped('Demo student has no enrolments.');
        }

        $this->actingAs($admin)
            ->post('/admin/exams', [
                'course_id' => $course->id,
                'title' => 'Pop Quiz',
                'type' => 'quiz',
                'exam_date' => now()->addDays(5)->toDateString(),
                'start_time' => '11:00',
                'duration_minutes' => 45,
                'location' => 'Room 9',
                'total_marks' => 25,
            ])
            ->assertRedirect();

        $exam = Exam::where('title', 'Pop Quiz')->where('course_id', $course->id)->first();
        $this->assertNotNull($exam);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'type' => NotificationType::EXAM->value,
        ]);
    }

    public function test_admin_can_update_and_delete_an_exam(): void
    {
        $admin = $this->admin();
        $course = Course::first();

        $exam = Exam::create([
            'course_id' => $course->id,
            'title' => 'Temp Exam',
            'type' => 'midterm',
            'exam_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($admin)
            ->patch("/admin/exams/{$exam->id}", [
                'course_id' => $course->id,
                'title' => 'Renamed Exam',
                'type' => 'final',
                'exam_date' => now()->addDays(4)->toDateString(),
            ])
            ->assertRedirect();

        $this->assertSame('Renamed Exam', $exam->fresh()->title);

        $this->actingAs($admin)
            ->delete("/admin/exams/{$exam->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
    }

    public function test_validation_rejects_an_invalid_exam_type(): void
    {
        $admin = $this->admin();
        $course = Course::first();

        $this->actingAs($admin)
            ->post('/admin/exams', [
                'course_id' => $course->id,
                'title' => 'Bad',
                'type' => 'not-a-type',
                'exam_date' => now()->addDay()->toDateString(),
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_student_can_view_exam_schedule(): void
    {
        $this->actingAs($this->student())
            ->get('/exams')
            ->assertOk();
    }

    public function test_faculty_can_view_course_exams(): void
    {
        $this->actingAs($this->faculty())
            ->get('/faculty/exams')
            ->assertOk();
    }

    public function test_student_cannot_manage_exams(): void
    {
        $this->actingAs($this->student())
            ->get('/admin/exams')
            ->assertRedirect();
    }
}
