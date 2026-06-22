<?php

declare(strict_types=1);

namespace App\Http\Controllers\Faculty;

use App\Domain\User\Models\User;
use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Faculty "My Students" directory.
 *
 * Resolves the real roster from the sections the faculty teaches (current term),
 * scoped strictly to their own sections — never the whole student database. A
 * student's `department` is their OWN major/home department, which may differ
 * from the course's department (cross-major electives are legitimate). Course →
 * faculty department ownership is enforced at assignment time (SectionRequest).
 *
 * Chat itself is still a UI/UX skeleton ("Upcoming Feature"); only the directory
 * and contact data are real.
 */
class StudentDirectoryController extends Controller
{
    /**
     * Directory list of the faculty's students.
     */
    public function index(): Response
    {
        return Inertia::render('Faculty/Students', [
            'students' => $this->studentsFor(request()->user()),
        ]);
    }

    /**
     * Dedicated messenger view. Opens empty unless deep-linked from the
     * directory with "?to={studentId}", which pre-selects that conversation.
     */
    public function messages(Request $request): Response
    {
        $contactId = $request->integer('to') ?: null;

        return Inertia::render('Faculty/Messages', [
            'students' => $this->studentsFor($request->user()),
            'initialContactId' => $contactId,
        ]);
    }

    /**
     * One entry per (section, enrolled student) the faculty teaches this term, so
     * a student in two of the faculty's courses appears once per course.
     *
     * @return array<int, array<string, mixed>>
     */
    private function studentsFor(User $faculty): array
    {
        $currentTermId = Term::currentTerm()?->id;

        return $faculty->teachingSections()
            ->when($currentTermId, fn ($query) => $query->where('term_id', $currentTermId))
            ->with([
                'course',
                'students' => fn ($query) => $query->wherePivot('status', 'enrolled')->with('department'),
            ])
            ->get()
            ->flatMap(fn (Section $section) => $section->students->map(fn (User $student) => [
                'id' => $student->id,                       // chat target (the person)
                'rowId' => $section->id.'-'.$student->id,   // unique per directory row
                'name' => $student->name,
                'studentId' => $student->student_id,
                'department' => $student->department?->name, // student's own major
                'semester' => $student->semester,
                'section' => $section->label,
                'course' => ['code' => $section->course->code, 'name' => $section->course->name],
                'email' => $student->email,
                'avatar' => $this->avatarFor($student, '6366f1'),
            ]))
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
