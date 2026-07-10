<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Academic\Services\EnrollmentService;
use App\Domain\User\Models\User;
use App\Models\Course;
use App\Models\Notification;
use App\Models\Section;
use App\Models\SectionWaitlist;
use App\Models\Term;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Course prerequisites (registration blocked until completed) and section
 * waitlists (admin assignment to a full section queues the student; a drop
 * auto-promotes the head of the queue to a pending placement).
 */
class PrerequisiteWaitlistTest extends TestCase
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

    private function admin(): User
    {
        $admin = User::where('email', 'admin@university.edu')->first();
        if (! $admin) {
            $this->markTestSkipped('Demo admin not seeded.');
        }

        return $admin;
    }

    /**
     * A current-term section the student has a pending placement in.
     */
    private function assignedSection(User $student): Section
    {
        $term = Term::where('is_current', true)->first();
        if (! $term || ! $term->is_registration_open) {
            Term::where('is_current', true)->update(['is_registration_open' => true]);
            $term = Term::where('is_current', true)->first();
        }
        if (! $term) {
            $this->markTestSkipped('No current term seeded.');
        }

        $section = Section::where('term_id', $term->id)
            ->where('is_active', true)
            ->whereDoesntHave('students', fn ($q) => $q->where('users.id', $student->id))
            ->first();
        if (! $section) {
            $this->markTestSkipped('No assignable section in the current term.');
        }

        app(EnrollmentService::class)->assign($section, $student);

        return $section->fresh();
    }

    public function test_registration_is_blocked_until_prerequisites_are_completed(): void
    {
        $student = $this->student();
        $section = $this->assignedSection($student);

        $prerequisite = Course::where('id', '!=', $section->course_id)
            ->whereDoesntHave('students', fn ($q) => $q->where('users.id', $student->id))
            ->firstOrFail();
        $section->course->prerequisites()->sync([$prerequisite->id]);

        // Unmet prerequisite → registration refused with a clear error.
        $this->actingAs($student)
            ->post('/register', ['section_id' => $section->id])
            ->assertRedirect();
        $this->assertSame('pending', $section->course->students()
            ->where('users.id', $student->id)->first()->pivot->status);

        $error = app(EnrollmentService::class)->eligibilityError($section, $student);
        $this->assertStringContainsString($prerequisite->code, (string) $error);

        // Completing the prerequisite unblocks registration.
        $prerequisite->students()->syncWithoutDetaching([
            $student->id => ['role' => 'student', 'status' => 'completed'],
        ]);

        $this->actingAs($student)
            ->post('/register', ['section_id' => $section->id])
            ->assertRedirect();
        $this->assertSame('enrolled', $section->course->students()
            ->where('users.id', $student->id)->first()->pivot->status);
    }

    public function test_assigned_courses_expose_prerequisite_status(): void
    {
        $student = $this->student();
        $section = $this->assignedSection($student);

        $prerequisite = Course::where('id', '!=', $section->course_id)->firstOrFail();
        $section->course->prerequisites()->sync([$prerequisite->id]);

        $assigned = app(EnrollmentService::class)->assignedFor($student)
            ->firstWhere('sectionId', $section->id);

        $this->assertNotNull($assigned);
        $this->assertSame($prerequisite->code, $assigned['prerequisites'][0]['code']);
        $this->assertIsBool($assigned['prerequisites'][0]['met']);
    }

    public function test_admin_assignment_to_a_full_section_waitlists_the_student(): void
    {
        $student = $this->student();
        $admin = $this->admin();

        $section = Section::whereDoesntHave('students', fn ($q) => $q->where('users.id', $student->id))
            ->where('is_active', true)
            ->firstOrFail();
        // Freeze capacity at the current reserved count → the section is full.
        $section->update(['max_enrollment' => app(EnrollmentService::class)->activeCount($section)]);

        $this->actingAs($admin)
            ->post("/admin/sections/{$section->id}/enrollments", ['student_ids' => [$student->id]])
            ->assertRedirect();

        $this->assertTrue(
            SectionWaitlist::where('section_id', $section->id)->where('user_id', $student->id)->exists(),
            'Full section assignment queues the student.',
        );
        $this->assertFalse(
            $section->course->students()->where('users.id', $student->id)->exists(),
            'No pivot row is created while waitlisted.',
        );
        $this->assertTrue(
            Notification::where('user_id', $student->id)->where('title', 'Added to a waitlist')->exists(),
        );
    }

    public function test_a_drop_promotes_the_waitlist_head_to_a_pending_placement(): void
    {
        $student = $this->student();

        // A section with at least one enrolled student we can drop.
        $section = Section::where('is_active', true)
            ->whereHas('students', fn ($q) => $q->where('course_user.status', 'enrolled'))
            ->whereDoesntHave('students', fn ($q) => $q->where('users.id', $student->id))
            ->firstOrFail();
        $section->update(['max_enrollment' => app(EnrollmentService::class)->activeCount($section)]);

        app(EnrollmentService::class)->waitlist($section, $student);

        $enrolled = $section->students()->wherePivot('status', 'enrolled')->firstOrFail();
        app(EnrollmentService::class)->drop($section, $enrolled);

        $this->assertFalse(
            SectionWaitlist::where('section_id', $section->id)->where('user_id', $student->id)->exists(),
            'The promoted student leaves the queue.',
        );
        $this->assertSame(
            'pending',
            $section->course->students()->where('users.id', $student->id)->first()?->pivot->status,
            'Promotion creates a pending placement for the student to confirm.',
        );
        $this->assertTrue(
            Notification::where('user_id', $student->id)->where('title', 'A seat opened up')->exists(),
        );
    }

    public function test_admin_can_manage_course_prerequisites(): void
    {
        $admin = $this->admin();
        $course = Course::firstOrFail();
        $prerequisite = Course::where('id', '!=', $course->id)->firstOrFail();

        $this->actingAs($admin)
            ->patch("/admin/courses/{$course->id}", [
                'code' => $course->code,
                'name' => $course->name,
                'description' => $course->description,
                'department_id' => $course->department_id,
                'semester' => $course->semester,
                'credits' => $course->credits,
                // Self-reference must be silently ignored.
                'prerequisites' => [$prerequisite->id, $course->id],
            ])
            ->assertRedirect();

        $this->assertSame([$prerequisite->id], $course->fresh()->prerequisites()->pluck('courses.id')->all());
    }
}
