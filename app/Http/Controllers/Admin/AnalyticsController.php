<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Analytics\Services\AnalyticsService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Analytics', [
            'overview' => $this->analytics->overview(),
            'departmentStats' => $this->analytics->departmentBreakdown(),
            'topQueries' => $this->analytics->topQueries(),
            'usersByRole' => $this->analytics->usersByRole(),
        ]);
    }
}
