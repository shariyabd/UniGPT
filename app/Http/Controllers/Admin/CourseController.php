<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Academic\Services\CourseManagementService;
use App\Domain\Academic\Services\CourseService;
use App\Domain\User\Models\User;
use App\Http\Controllers\Concerns\PaginatesCollections;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseRequest;
use App\Models\Course;
use App\Models\Department;
use App\Models\Term;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    use PaginatesCollections;

    public function __construct(
        private readonly CourseService $courses,
        private readonly CourseManagementService $management,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'department_id' => $request->input('department_id'),
            'semester' => $request->input('semester'),
        ];

        $catalog = $this->courses->catalog();

        // Semester options span the WHOLE catalog, not just the current page.
        $semesterOptions = $catalog
            ->pluck('semester')
            ->filter(fn ($semester) => $semester !== null)
            ->unique()
            ->sort()
            ->values();

        // Filter server-side before paginating so department/semester/search
        // match across every page rather than only the current 25 rows.
        $courses = $catalog
            ->when($filters['department_id'], fn ($collection, $departmentId) => $collection->filter(
                fn (array $course) => (string) ($course['departmentId'] ?? '') === (string) $departmentId
            ))
            ->when($filters['semester'], fn ($collection, $semester) => $collection->filter(
                fn (array $course) => (string) ($course['semester'] ?? '') === (string) $semester
            ))
            ->when($filters['search'], function ($collection, $search) {
                $needle = Str::lower($search);

                return $collection->filter(fn (array $course) => Str::contains(
                    Str::lower($course['code'].' '.$course['name']),
                    $needle
                ));
            })
            ->values();

        return Inertia::render('Admin/Courses', [
            'courses' => $this->paginateCollection($courses),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'semesterOptions' => $semesterOptions,
            'filters' => $filters,
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
