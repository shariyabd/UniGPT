<?php

namespace Tests\Feature;

use App\Domain\Academic\Services\EnrollmentService;
use App\Domain\User\Models\User;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SelfRegistrationTest extends TestCase
{
    use DatabaseTransactions;

    private function student(): User
    {
        $student = User::where('email', 'student@university.edu')->first();

        if (! $student) {
            $this->markTestSkipped('Demo student not seeded.');
        }

        return $student;
    }

    private function sectionByCode(string $code, string $label = 'A'): Section
    {
        $section = Section::whereHas('course', fn ($q) => $q->where('code', $code))
            ->where('label', $label)
            ->first();

        if (! $section) {
            $this->markTestSkipped("{$code} section {$label} not seeded.");
        }

        return $section;
    }

    public function test_student_can_view_registration_page(): void
    {
        $this->actingAs($this->student())->get('/register')->assertOk();
    }

    public function test_assigned_list_shows_only_admin_assigned_pending_placements(): void
    {
        $assigned = app(EnrollmentService::class)->assignedFor($this->student());
        $codes = $assigned->pluck('code');

        // The seeder assigns the demo student CS330 (pending) — it must appear.
        $this->assertTrue($codes->contains('CS330'));
        // Already-enrolled courses are not pending placements.
        $this->assertFalse($codes->contains('CS301'));
        // CS340 is a sem-5 offering the admin never assigned — it must NOT appear.
        $this->assertFalse($codes->contains('CS340'));
    }

    public function test_student_can_register_for_an_assigned_course(): void
    {
        $student = $this->student();
        $section = $this->sectionByCode('CS330'); // seeded as a pending placement

        $this->actingAs($student)
            ->post(route('register.store'), ['section_id' => $section->id])
            ->assertRedirect();

        $this->assertDatabaseHas('course_user', [
            'user_id' => $student->id,
            'section_id' => $section->id,
            'status' => 'enrolled',
        ]);
    }

    public function test_student_cannot_register_for_an_unassigned_course(): void
    {
        $student = $this->student();
        $section = $this->sectionByCode('CS340'); // never assigned to the student

        $this->actingAs($student)
            ->post(route('register.store'), ['section_id' => $section->id])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('course_user', [
            'user_id' => $student->id,
            'section_id' => $section->id,
            'status' => 'enrolled',
        ]);
    }

    public function test_cannot_register_when_registration_is_closed(): void
    {
        $student = $this->student();
        $section = $this->sectionByCode('CS330');

        Term::where('is_current', true)->update(['is_registration_open' => false]);

        $this->actingAs($student)
            ->post(route('register.store'), ['section_id' => $section->id])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('course_user', [
            'user_id' => $student->id,
            'section_id' => $section->id,
            'status' => 'enrolled',
        ]);
    }

    public function test_student_can_drop_a_registered_course(): void
    {
        $student = $this->student();
        $section = $this->sectionByCode('CS330'); // pending → register → drop

        $this->actingAs($student)->post(route('register.store'), ['section_id' => $section->id]);
        $this->actingAs($student)->delete(route('register.drop', $section->id))->assertRedirect();

        $this->assertDatabaseHas('course_user', [
            'user_id' => $student->id,
            'section_id' => $section->id,
            'status' => 'dropped',
        ]);
    }
}
