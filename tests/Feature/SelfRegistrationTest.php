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

    public function test_available_list_excludes_already_registered_courses(): void
    {
        $available = app(EnrollmentService::class)->availableFor($this->student());

        // The student is already enrolled in CS301/CS305/CS310 — none should be offered.
        $codes = $available->pluck('code');
        $this->assertFalse($codes->contains('CS301'));
        // ...but the un-enrolled sem-5 offering should be available.
        $this->assertTrue($codes->contains('CS330'));
    }

    public function test_student_can_register_for_an_offered_course(): void
    {
        $student = $this->student();
        $section = $this->sectionByCode('CS330');

        $this->actingAs($student)
            ->post(route('register.store'), ['section_id' => $section->id])
            ->assertRedirect();

        $this->assertDatabaseHas('course_user', [
            'user_id' => $student->id,
            'section_id' => $section->id,
            'status' => 'enrolled',
        ]);
    }

    public function test_student_cannot_register_for_another_semesters_course(): void
    {
        $student = $this->student();
        $section = $this->sectionByCode('CS320'); // semester 6; student is semester 5

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
        $section = $this->sectionByCode('CS330');

        $this->actingAs($student)->post(route('register.store'), ['section_id' => $section->id]);
        $this->actingAs($student)->delete(route('register.drop', $section->id))->assertRedirect();

        $this->assertDatabaseHas('course_user', [
            'user_id' => $student->id,
            'section_id' => $section->id,
            'status' => 'dropped',
        ]);
    }
}
