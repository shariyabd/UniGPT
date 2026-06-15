<?php

namespace App\Http\Controllers\Faculty;

use App\Domain\Analytics\Services\FacultyAnalyticsService;
use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly FacultyAnalyticsService $analytics,
    ) {}

    public function index(Request $request, ?Course $course = null): Response
    {
        if ($course?->exists) {
            Gate::authorize('manage', $course);
        }

        return Inertia::render('Faculty/Analytics', $this->analytics->build(
            $request->user(),
            $course?->id,
        ));
    }
}
