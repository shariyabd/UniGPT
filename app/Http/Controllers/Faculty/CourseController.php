<?php

namespace App\Http\Controllers\Faculty;

use App\Domain\Academic\Services\CourseService;
use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function __construct(private readonly CourseService $courses) {}

    public function index(): Response
    {
        return Inertia::render('Faculty/Dashboard', [
            'activeCourses' => $this->courses->facultyCourses(request()->user()),
        ]);
    }

    public function show(Course $course): Response
    {
        Gate::authorize('manage', $course);

        return Inertia::render('Faculty/CourseDetail', [
            'course' => $this->courses->courseDetail($course),
        ]);
    }
}
