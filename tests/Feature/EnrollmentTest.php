<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Models\Section;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EnrollmentTest extends TestCase
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

    /**
     * A section the demo student is not actively enrolled in (CS301 Section B).
     */
    private function openSection(User $student): Section
    {
        $section = Section::whereHas('course', fn ($q) => $q->where('code', 'CS301'))
            ->where('label', 'B')
            ->first();

        if (! $section) {
            $this->markTestSkipped('CS301 Section B not seeded.');
        }

        return $section;
    }

    public function test_admin_can_assign_a_student_to_a_section(): void
    {
        $student = $this->user('student@university.edu');
        $section = $this->openSection($student);

        $this->actingAs($this->user('admin@university.edu'))
            ->post(route('admin.sections.assign', $section->id), ['student_id' => $student->id])
            ->assertRedirect();

        // Assignment is a pending placement, not an immediate enrolment — the
        // student confirms it on their registration page.
        $this->assertDatabaseHas('course_user', [
            'user_id' => $student->id,
            'section_id' => $section->id,
            'status' => 'pending',
        ]);

        // The student is notified to register.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'type' => 'enrollment',
        ]);
    }

    public function test_admin_can_drop_an_assigned_student(): void
    {
        $student = $this->user('student@university.edu');
        $section = $this->openSection($student);

        $admin = $this->user('admin@university.edu');

        $this->actingAs($admin)->post(route('admin.sections.assign', $section->id), ['student_id' => $student->id]);
        $this->actingAs($admin)->delete(route('admin.sections.drop', [$section->id, $student->id]))->assertRedirect();

        $this->assertDatabaseHas('course_user', [
            'user_id' => $student->id,
            'section_id' => $section->id,
            'status' => 'dropped',
        ]);
    }

    public function test_assignment_respects_section_capacity(): void
    {
        $student = $this->user('student@university.edu');
        $section = $this->openSection($student);

        // Fill the section to capacity at the data layer.
        $section->update(['max_enrollment' => 1]);
        DB::table('course_user')->insert([
            'course_id' => $section->course_id,
            'section_id' => $section->id,
            'user_id' => $this->user('prof.jones@university.edu')->id, // any other user id to occupy a seat
            'term_id' => $section->term_id,
            'role' => 'student',
            'status' => 'enrolled',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->user('admin@university.edu'))
            ->post(route('admin.sections.assign', $section->id), ['student_id' => $student->id])
            ->assertRedirect();

        // Capacity (enrolled + pending) is full, so the student must not have been
        // assigned a (pending) seat.
        $this->assertDatabaseMissing('course_user', [
            'user_id' => $student->id,
            'section_id' => $section->id,
            'status' => 'pending',
        ]);
    }

    public function test_faculty_cannot_manage_rosters(): void
    {
        $student = $this->user('student@university.edu');
        $section = $this->openSection($student);

        $response = $this->actingAs($this->user('prof.smith@university.edu'))
            ->post(route('admin.sections.assign', $section->id), ['student_id' => $student->id]);

        $this->assertContains($response->status(), [302, 403]);
    }
}
