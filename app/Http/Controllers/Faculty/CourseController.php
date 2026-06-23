<?php

namespace App\Http\Controllers\Faculty;

use App\Domain\Academic\Services\CourseService;
use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Faculty read-only access to the courses/sections they teach. Creating and
 * editing catalog courses (and assigning faculty to sections) is admin-owned —
 * see {@see \App\Http\Controllers\Admin\CourseController}.
 */
class CourseController extends Controller
{
    public function __construct(
        private readonly CourseService $courses,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Faculty/Courses', [
            'courses' => $this->courses->facultyCourses(request()->user()),
        ]);
    }

    public function show(Course $course): Response
    {
        Gate::authorize('manage', $course);

        $sectionId = request()->integer('section') ?: null;

        return Inertia::render('Faculty/CourseDetail', [
            'course' => $this->courses->courseDetail($course, request()->user(), $sectionId),
        ]);
    }
}
