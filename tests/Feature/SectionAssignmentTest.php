<?php

namespace Tests\Feature;

use App\Domain\Academic\Services\EnrollmentService;
use App\Domain\User\Models\User;
use App\Models\Course;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The admin-controlled placement flow: assigning a student to a section creates a
 * pending placement that grants NO access until the student registers, and
 * section labels are constrained to a consistent A–J set, unique per course+term.
 */
class SectionAssignmentTest extends TestCase
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

    private function sectionB(): Section
    {
        $section = Section::whereHas('course', fn ($q) => $q->where('code', 'CS301'))
            ->where('label', 'B')
            ->first();
        if (! $section) {
            $this->markTestSkipped('CS301 Section B not seeded.');
        }

        return $section;
    }

    public function test_pending_placement_grants_no_section_access_until_registered(): void
    {
        $student = $this->user('student@university.edu');
        $section = $this->sectionB();
        $enrollment = app(EnrollmentService::class);

        // Admin assigns the student → pending. The section must NOT count as one
        // of the student's enrolled sections (no materials/exams/etc. leak).
        $enrollment->assign($section, $student);
        $this->assertFalse(
            $student->enrolledSectionIds()->contains($section->id),
            'A pending placement must not grant section access.',
        );

        // Once the student registers (confirms), the section becomes accessible.
        $enrollment->enroll($section, $student);
        $this->assertTrue(
            $student->fresh()->enrolledSectionIds()->contains($section->id),
            'A confirmed registration must grant section access.',
        );
    }

    public function test_section_label_is_normalised_to_uppercase(): void
    {
        $course = Course::where('code', 'CS301')->first();
        if (! $course) {
            $this->markTestSkipped('CS301 not seeded.');
        }

        // 'g' is free for CS301 this term; it must be stored as 'G'.
        $this->actingAs($this->user('admin@university.edu'))
            ->post(route('admin.sections.store', $course->id), [
                'label' => 'g',
                'term_id' => Term::where('is_current', true)->value('id'),
                'max_enrollment' => 30,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sections', [
            'course_id' => $course->id,
            'label' => 'G',
        ]);
    }

    public function test_duplicate_section_label_is_rejected(): void
    {
        $course = Course::where('code', 'CS301')->first();
        if (! $course) {
            $this->markTestSkipped('CS301 not seeded.');
        }

        // CS301 already has Section A this term — a second A must be rejected.
        $this->actingAs($this->user('admin@university.edu'))
            ->post(route('admin.sections.store', $course->id), [
                'label' => 'A',
                'term_id' => Term::where('is_current', true)->value('id'),
                'max_enrollment' => 30,
            ])
            ->assertSessionHasErrors('label');
    }

    public function test_invalid_section_label_is_rejected(): void
    {
        $course = Course::where('code', 'CS301')->first();
        if (! $course) {
            $this->markTestSkipped('CS301 not seeded.');
        }

        // 'Z' is outside the A–J set.
        $this->actingAs($this->user('admin@university.edu'))
            ->post(route('admin.sections.store', $course->id), [
                'label' => 'Z',
                'term_id' => Term::where('is_current', true)->value('id'),
                'max_enrollment' => 30,
            ])
            ->assertSessionHasErrors('label');
    }
}
