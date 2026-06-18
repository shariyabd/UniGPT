<?php

declare(strict_types=1);

namespace App\Domain\Academic\Services;

use App\Domain\User\Models\User;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Support\Collection;

/**
 * Registrar enrollment: place students into a section (offering) or drop them.
 *
 * Enrollment lives on the course_user pivot, which is unique per (course, user)
 * — so a student holds one enrollment per course, stamped with the section and
 * term they were placed in.
 *
 * Placement is a two-step flow: the admin {@see assign()}s a student to a section
 * (status `pending`, which reserves a seat), then the student confirms it on the
 * registration page via {@see enroll()} (status `enrolled`). A student only ever
 * sees — and can only register for — sections the admin has assigned to them.
 */
class EnrollmentService
{
    /**
     * Statuses that occupy a seat: confirmed enrolments plus pending placements
     * the admin has reserved for a student awaiting their confirmation.
     *
     * @var list<string>
     */
    private const RESERVING_STATUSES = ['enrolled', 'pending'];

    /**
     * Whether the section has room for another active enrollment.
     */
    public function hasCapacity(Section $section): bool
    {
        return $this->activeCount($section) < $section->max_enrollment;
    }

    /**
     * Reserved seats: confirmed enrolments + pending (assigned) placements.
     */
    public function activeCount(Section $section): int
    {
        return $section->students()->wherePivotIn('status', self::RESERVING_STATUSES)->count();
    }

    /**
     * Admin action: assign a student to a section as a pending placement (reserves
     * a seat). The student confirms it later via {@see enroll()}. Caller is
     * responsible for checking {@see hasCapacity()} first.
     */
    public function assign(Section $section, User $student): void
    {
        $section->course->students()->syncWithoutDetaching([
            $student->id => [
                'role' => 'student',
                'status' => 'pending',
                'section_id' => $section->id,
                'term_id' => $section->term_id,
                'enrolled_at' => null,
            ],
        ]);
    }

    /**
     * Student action: confirm a pending placement, turning it into an active
     * enrollment. The seat is already reserved by {@see assign()}, so no capacity
     * re-check is needed. Eligibility is validated via {@see eligibilityError()}.
     */
    public function enroll(Section $section, User $student): void
    {
        $section->course->students()->syncWithoutDetaching([
            $student->id => [
                'role' => 'student',
                'status' => 'enrolled',
                'section_id' => $section->id,
                'term_id' => $section->term_id,
                'enrolled_at' => now(),
            ],
        ]);
    }

    /**
     * Drop a student from a section (keeps the row, marks it dropped).
     */
    public function drop(Section $section, User $student): void
    {
        $section->course->students()->updateExistingPivot($student->id, ['status' => 'dropped']);
    }

    /* ---- Student self-registration ---- */

    public function currentTerm(): ?Term
    {
        return Term::query()->where('is_current', true)->first();
    }

    /**
     * Whether students may self-register/drop right now.
     */
    public function registrationOpen(): bool
    {
        $term = $this->currentTerm();

        return $term !== null && $term->is_registration_open;
    }

    /**
     * Sections the admin has assigned to a student (pending placements) in the
     * current term, awaiting the student's confirmation. These are the only
     * courses the student may register for — placement is admin-controlled.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function assignedFor(User $student): Collection
    {
        $term = $this->currentTerm();

        if ($term === null) {
            return collect();
        }

        return Section::where('term_id', $term->id)
            ->where('is_active', true)
            ->whereHas('students', fn ($q) => $q->where('users.id', $student->id)->where('course_user.status', 'pending'))
            ->with(['course', 'faculty'])
            ->get()
            ->map(fn (Section $section) => $this->presentSection($section))
            ->values();
    }

    /**
     * The student's registered (enrolled) sections this term.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function registeredFor(User $student): Collection
    {
        $term = $this->currentTerm();

        if ($term === null) {
            return collect();
        }

        return $student->enrolledCourses()
            ->wherePivot('status', 'enrolled')
            ->wherePivot('term_id', $term->id)
            ->get()
            ->map(function ($course) {
                $section = Section::with('faculty')->find($course->pivot->section_id);

                return [
                    'sectionId' => $course->pivot->section_id,
                    'courseId' => $course->id,
                    'code' => $course->code,
                    'name' => $course->name,
                    'credits' => $course->credits,
                    'label' => $section?->label,
                    'faculty' => $section?->faculty?->name,
                ];
            })
            ->values();
    }

    /**
     * Validate a student's registration (confirmation) request; returns an error
     * string or null. A student may only register for a section the admin has
     * assigned to them (a pending placement) — the seat is already reserved, so
     * no capacity check is needed here.
     */
    public function eligibilityError(Section $section, User $student): ?string
    {
        $term = $this->currentTerm();

        if ($term === null || ! $term->is_registration_open) {
            return 'Registration is currently closed.';
        }

        if ($section->term_id !== $term->id || ! $section->is_active) {
            return 'This section is not open for registration.';
        }

        $isAssigned = $section->students()
            ->where('users.id', $student->id)
            ->wherePivot('status', 'pending')
            ->exists();

        if (! $isAssigned) {
            return 'This course has not been assigned to you. Contact the registrar.';
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function presentSection(Section $section): array
    {
        return [
            'sectionId' => $section->id,
            'courseId' => $section->course_id,
            'code' => $section->course?->code,
            'name' => $section->course?->name,
            'credits' => $section->course?->credits,
            'semester' => $section->course?->semester,
            'label' => $section->label,
            'faculty' => $section->faculty?->name,
            'seatsLeft' => max($section->max_enrollment - $this->activeCount($section), 0),
            'maxEnrollment' => $section->max_enrollment,
        ];
    }
}
