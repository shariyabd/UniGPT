<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Academic\Services\CourseManagementService;
use App\Domain\Academic\Services\CourseService;
use App\Domain\User\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseRequest;
use App\Models\Course;
use App\Models\Department;
use App\Models\Term;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function __construct(
        private readonly CourseService $courses,
        private readonly CourseManagementService $management,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Courses', [
            'courses' => $this->courses->catalog(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'faculty' => User::query()
                ->whereHas('roles', fn ($q) => $q->where('roles.slug', 'faculty'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'students' => User::query()
                ->whereHas('roles', fn ($q) => $q->where('roles.slug', 'student'))
                ->orderBy('name')
                ->get(['id', 'name', 'student_id']),
            'terms' => Term::orderByDesc('is_current')->orderByDesc('start_date')->get(['id', 'name', 'is_current']),
        ]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        $course = $this->management->createCourse($request->validated());

        $this->activity->log('course.created', "Created course {$course->code}", $course, [], $request->user());

        return back()->with('success', 'Course created.');
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        $this->management->updateCourse($course, $request->validated());

        $this->activity->log('course.updated', "Updated course {$course->code}", $course, [], $request->user());

        return back()->with('success', 'Course updated.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        // Don't delete a course that still has enrolled students in any section.
        if ($course->students()->exists()) {
            return back()->with('error', 'Cannot delete a course that still has enrolled students.');
        }

        $code = $course->code;
        $this->management->deleteCourse($course);

        $this->activity->log('course.deleted', "Deleted course {$code}", null, ['code' => $code], request()->user());

        return back()->with('success', 'Course deleted.');
    }
}
