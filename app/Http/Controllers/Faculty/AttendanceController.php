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

        $date = $request->date('date')?->toDateString() ?? now()->toDateString();

        return Inertia::render('Faculty/Attendance', array_merge(
            $this->attendance->rosterForDate($course, $date),
            [
                'course' => [
                    'id' => $course->id,
                    'code' => $course->code,
                    'name' => $course->name,
                ],
            ],
        ));
    }

    public function store(MarkAttendanceRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $validated = $request->validated();

        $this->attendance->mark(
            $course,
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
}
