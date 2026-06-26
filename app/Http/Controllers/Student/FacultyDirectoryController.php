<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Domain\User\Models\User;
use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Student "My Faculty" directory.
 *
 * Resolves the real faculty teaching the student's current-term sections from
 * enrolment data — there is no hardcoded course/faculty list. A faculty member's
 * department always equals the section's course's owning department (enforced at
 * assignment time in SectionRequest), so a student only ever sees faculty under
 * their own programme's departments.
 *
 * Real-time student↔faculty chat is fully implemented (see the Messenger
 * controllers); this directory supplies the contact/launch points into it.
 */
class FacultyDirectoryController extends Controller
{
    /**
     * Directory list of the student's faculty.
     */
    public function index(): Response
    {
        return Inertia::render('Student/Faculty', [
            'faculty' => $this->facultyFor(request()->user()),
        ]);
    }

    /**
     * Dedicated messenger view. Opens empty unless deep-linked from the
     * directory with "?to={facultyId}", which pre-selects that conversation.
     */
    public function messages(Request $request): Response
    {
        $contactId = $request->integer('to') ?: null;

        return Inertia::render('Student/Messages', [
            'faculty' => $this->facultyFor($request->user()),
            'initialContactId' => $contactId,
        ]);
    }

    /**
     * One entry per current-term section the student is enrolled in (so a faculty
     * teaching the student two courses appears once per course). Sections without
     * an assigned faculty are skipped — there is nobody to message.
     *
     * @return array<int, array<string, mixed>>
     */
    private function facultyFor(User $student): array
    {
        $currentTermId = Term::currentTerm()?->id;

        return $student->enrolledSections()
            ->wherePivotNotIn('status', ['dropped', 'pending'])
            ->when($currentTermId, fn ($query) => $query->where('sections.term_id', $currentTermId))
            ->with(['faculty', 'course.department'])
            ->get()
            ->filter(fn (Section $section) => $section->faculty !== null && $section->course !== null)
            ->map(fn (Section $section) => [
                'id' => $section->faculty->id,   // chat target (the person)
                'rowId' => $section->id,         // unique per directory row
                'name' => $section->faculty->name,
                'email' => $section->faculty->email,
                'department' => $section->course->department?->name,
                'course' => ['code' => $section->course->code, 'name' => $section->course->name],
                'section' => $section->label,
                'semester' => $section->course->semester,
                'avatar' => $this->avatarFor($section->faculty, '10b981'),
            ])
            ->sortBy('name')
            ->values()
            ->all();
    }

    private function avatarFor(User $user, string $background): string
    {
        return $user->avatar
            ?: 'https://ui-avatars.com/api/?name='.urlencode($user->name)."&background={$background}&color=fff&size=128";
    }
}
