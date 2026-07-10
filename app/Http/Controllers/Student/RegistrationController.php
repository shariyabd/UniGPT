<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Domain\Academic\Services\EnrollmentService;
use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Student registration: confirm (register for) / drop the sections the admin has
 * assigned to the student in the current term, while registration is open. The
 * student can only register for assigned (pending) placements — section
 * placement is admin-controlled, not self-service.
 */
class RegistrationController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $enrollment,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Student/Registration', [
            'assigned' => $this->enrollment->assignedFor($user),
            'registered' => $this->enrollment->registeredFor($user),
            'waitlisted' => $this->enrollment->waitlistFor($user),
            'registrationOpen' => $this->enrollment->registrationOpen(),
            'term' => $this->enrollment->currentTerm()?->name,
            'semester' => $user->semester,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'section_id' => ['required', 'exists:sections,id'],
        ]);

        $section = Section::with('course')->findOrFail($data['section_id']);
        $student = $request->user();

        if ($error = $this->enrollment->eligibilityError($section, $student)) {
            return back()->with('error', $error);
        }

        $this->enrollment->enroll($section, $student);

        return back()->with('success', "Registered for {$section->course->code}.");
    }

    public function destroy(Request $request, Section $section): RedirectResponse
    {
        $student = $request->user();

        if (! $this->enrollment->registrationOpen()) {
            return back()->with('error', 'Registration is closed — you cannot drop right now.');
        }

        $isEnrolled = $section->students()
            ->where('users.id', $student->id)
            ->wherePivot('status', 'enrolled')
            ->exists();

        if (! $isEnrolled) {
            return back()->with('error', 'You are not registered in this section.');
        }

        $this->enrollment->drop($section, $student);

        return back()->with('success', 'Course dropped.');
    }
}
