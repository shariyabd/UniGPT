<?php

declare(strict_types=1);

namespace App\Domain\Academic\Services;

use App\Domain\User\Models\User;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Registrar enrollment: place students into a section (offering) or drop them.
 *
 * Enrollment lives on the course_user pivot, which is unique per (course, user)
 * — so a student holds one enrollment per course, stamped with the section and
 * term they were placed in.
 */
class EnrollmentService
{
    /**
     * Whether the section has room for another active enrollment.
     */
    public function hasCapacity(Section $section): bool
    {
        return $this->activeCount($section) < $section->max_enrollment;
    }

    public function activeCount(Section $section): int
    {
        return $section->students()->wherePivot('status', 'enrolled')->count();
    }

    /**
     * Enroll (or re-enroll) a student into a section. Caller is responsible for
     * checking {@see hasCapacity()} first.
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
     * Sections a student may still register for: current term, their curriculum
     * semester, not full, and not a course they're already registered in.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function availableFor(User $student): Collection
    {
        $term = $this->currentTerm();

        if ($term === null || $student->semester === null) {
            return collect();
        }

        $registeredCourseIds = DB::table('course_user')
            ->where('user_id', $student->id)
            ->where('status', 'enrolled')
            ->pluck('course_id');

        return Section::where('term_id', $term->id)
            ->where('is_active', true)
            ->whereHas('course', fn ($q) => $q->where('semester', $student->semester))
            ->whereNotIn('course_id', $registeredCourseIds)
            ->with(['course', 'faculty'])
            ->get()
            ->filter(fn (Section $section) => $this->hasCapacity($section))
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
     * Validate a self-registration request; returns an error string or null.
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

        if ((int) $section->course->semester !== (int) $student->semester) {
            return 'This course is not part of your semester.';
        }

        $alreadyRegistered = DB::table('course_user')
            ->where('user_id', $student->id)
            ->where('course_id', $section->course_id)
            ->where('status', 'enrolled')
            ->exists();

        if ($alreadyRegistered) {
            return 'You are already registered for this course.';
        }

        if (! $this->hasCapacity($section)) {
            return 'This section is full.';
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
            'label' => $section->label,
            'faculty' => $section->faculty?->name,
            'seatsLeft' => max($section->max_enrollment - $this->activeCount($section), 0),
            'maxEnrollment' => $section->max_enrollment,
        ];
    }
}
