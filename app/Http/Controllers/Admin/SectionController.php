<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Academic\Services\CourseManagementService;
use App\Domain\Academic\Services\EnrollmentService;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\User\Models\User;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SectionRequest;
use App\Models\Course;
use App\Models\Section;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    public function __construct(
        private readonly CourseManagementService $management,
        private readonly EnrollmentService $enrollment,
        private readonly NotificationService $notifications,
        private readonly ActivityLogger $activity,
    ) {}

    public function store(SectionRequest $request, Course $course): RedirectResponse
    {
        $section = $this->management->createSection($course, $request->validated());

        $this->activity->log(
            'section.created',
            "Added section {$section->label} to {$course->code}",
            $section,
            [],
            $request->user(),
        );

        return back()->with('success', "Section {$section->label} added.");
    }

    public function update(SectionRequest $request, Section $section): RedirectResponse
    {
        $this->management->updateSection($section, $request->validated());

        $this->activity->log('section.updated', "Updated a section of {$section->course->code}", $section, [], $request->user());

        return back()->with('success', 'Section updated.');
    }

    public function destroy(Section $section): RedirectResponse
    {
        // Don't delete a section that still has enrolled students.
        if ($section->students()->exists()) {
            return back()->with('error', 'Cannot delete a section that still has enrolled students.');
        }

        $section->delete();

        return back()->with('success', 'Section deleted.');
    }

    /**
     * Assign one or more students to a section (registrar action), capacity-enforced.
     * Each creates a pending placement that reserves a seat; the student confirms it
     * on their registration page to become enrolled. Capacity is re-checked per
     * student, so a partial batch fills the remaining seats and reports the rest.
     */
    public function assign(Request $request, Section $section): RedirectResponse
    {
        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', Rule::exists('users', 'id')],
        ]);

        $students = User::whereIn('id', $data['student_ids'])->get();
        $course = $section->course;

        $assigned = [];
        $skipped = [];

        foreach ($students as $student) {
            if (! $student->isStudent()) {
                $skipped[] = "{$student->name} (not a student)";

                continue;
            }

            if ($section->students()->whereKey($student->id)->exists()) {
                $skipped[] = "{$student->name} (already in section)";

                continue;
            }

            if (! $this->enrollment->hasCapacity($section)) {
                $skipped[] = "{$student->name} (section full)";

                continue;
            }

            $this->enrollment->assign($section, $student);
            $assigned[] = $student;

            $this->notifications->notify(
                user: $student,
                type: NotificationType::ENROLLMENT,
                title: 'Course assigned — register to confirm',
                message: "You have been assigned to {$course->code} — {$course->name} (Section {$section->label}). Go to Registration and click Register to confirm your seat.",
                link: route('register'),
                data: ['course_id' => $course->id, 'section_id' => $section->id],
            );
        }

        if ($assigned !== []) {
            $count = count($assigned);
            $this->activity->log(
                'enrollment.assigned',
                "Assigned {$count} student(s) to {$course->code} ({$section->label})",
                $section,
                ['student_ids' => collect($assigned)->pluck('id')->all()],
                $request->user(),
            );
        }

        $message = $assigned !== []
            ? count($assigned).' student(s) assigned to Section '.$section->label.'.'
            : 'No students were assigned.';

        if ($skipped !== []) {
            $message .= ' Skipped: '.implode(', ', $skipped).'.';
        }

        return back()->with($assigned !== [] ? 'success' : 'error', $message);
    }

    /**
     * Drop a student from a section.
     */
    public function drop(Request $request, Section $section, User $user): RedirectResponse
    {
        $this->enrollment->drop($section, $user);

        $course = $section->course;
        $this->activity->log('enrollment.dropped', "Dropped {$user->name} from {$course->code} ({$section->label})", $section, [], $request->user());

        $this->notifications->notify(
            user: $user,
            type: NotificationType::ENROLLMENT,
            title: 'Dropped from a course',
            message: "You have been dropped from {$course->code} — {$course->name} (Section {$section->label}).",
            link: route('dashboard'),
            data: ['course_id' => $course->id, 'section_id' => $section->id],
        );

        return back()->with('success', "Dropped {$user->name}.");
    }
}
