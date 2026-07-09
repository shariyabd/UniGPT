<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Domain\Analytics\Services\LearningAnalyticsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Student-facing "My Progress" analytics — the student's own academic
 * performance and learning engagement, rendered as charts.
 */
class LearningAnalyticsController extends Controller
{
    public function __construct(private readonly LearningAnalyticsService $analytics) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Student/LearningAnalytics', [
            'analytics' => $this->analytics->build($request->user()),
        ]);
    }
}
