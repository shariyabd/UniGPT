<?php

namespace App\Http\Controllers\Faculty;

use App\Domain\Academic\Services\CourseManagementService;
use App\Domain\Academic\Services\CourseService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Faculty\CourseRequest;
use App\Models\Course;
use App\Models\Department;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
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
        return Inertia::render('Faculty/Dashboard', [
            'activeCourses' => $this->courses->facultyCourses(request()->user()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Faculty/CourseForm', [
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        $course = $this->management->createCourse($request->user(), $request->validated());

        $this->activity->log('course.created', "Created course {$course->code}", $course, [], $request->user());

        return redirect()->route('faculty.courses.show', $course->id)
            ->with('success', 'Course created.');
    }

    public function show(Course $course): Response
    {
        Gate::authorize('manage', $course);

        return Inertia::render('Faculty/CourseDetail', [
            'course' => $this->courses->courseDetail($course),
        ]);
    }

    public function edit(Course $course): Response
    {
        Gate::authorize('manage', $course);

        return Inertia::render('Faculty/CourseForm', [
            'course' => $this->courses->editableCourse($course),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $this->management->updateCourse($course, $request->validated());

        $this->activity->log('course.updated', "Updated course {$course->code}", $course, [], $request->user());

        return redirect()->route('faculty.courses.show', $course->id)
            ->with('success', 'Course updated.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $code = $course->code;
        $this->management->deleteCourse($course);

        $this->activity->log('course.deleted', "Deleted course {$code}", null, ['code' => $code], request()->user());

        return redirect()->route('faculty.courses')->with('success', 'Course deleted.');
    }
}
