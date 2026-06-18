<?php

namespace App\Http\Controllers\Faculty;

use App\Domain\Academic\Services\AttendanceService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Faculty\MarkAttendanceRequest;
use App\Models\Course;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendance,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(Request $request, Course $course): Response
    {
        Gate::authorize('manage', $course);

        $section = $this->facultySection($course, $request);
        $date = $request->date('date')?->toDateString() ?? now()->toDateString();

        return Inertia::render('Faculty/Attendance', array_merge(
            $this->attendance->rosterForDate($section, $date),
            [
                'course' => [
                    'id' => $course->id,
                    'code' => $course->code,
                    'name' => $course->name,
                    'section' => $section->label,
                ],
                // Sections this faculty teaches for the course, so they can switch
                // between rosters when they teach more than one.
                'sections' => $this->facultySections($course, $request)->map(fn ($s) => [
                    'id' => $s->id,
                    'label' => $s->label,
                ])->values(),
                'activeSectionId' => $section->id,
            ],
        ));
    }

    public function store(MarkAttendanceRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $section = $this->facultySection($course, $request);
        $validated = $request->validated();

        $this->attendance->mark(
            $section,
            $validated['date'],
            $validated['entries'],
            $request->user(),
        );

        $this->activity->log('attendance.marked', 'Marked attendance', $course, [
            'date' => $validated['date'],
            'count' => count($validated['entries']),
        ], $request->user());

        return back()->with('success', 'Attendance saved.');
    }

    /**
     * The section being marked: the requested section when the faculty teaches
     * it for this course, otherwise their default section. Attendance is always
     * scoped to one of the instructor's own sections.
     */
    private function facultySection(Course $course, Request $request): \App\Models\Section
    {
        $requested = $request->integer('section') ?: null;

        $section = ($requested !== null
            ? $this->facultySections($course, $request)->firstWhere('id', $requested)
            : null)
            ?? $course->sectionFor($request->user());

        abort_if($section === null, 404, 'You do not teach a section of this course.');

        return $section;
    }

    /**
     * The sections of this course taught by the current faculty member.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Section>
     */
    private function facultySections(Course $course, Request $request): \Illuminate\Support\Collection
    {
        return $course->sections()
            ->where('faculty_id', $request->user()->id)
            ->orderBy('id')
            ->get();
    }
}
