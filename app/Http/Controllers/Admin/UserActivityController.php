<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\Tracking\VisitReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin → User Activity: a filterable feed of tracked page views showing who visited,
 * from where (referrer), on what device, and an IP-derived location.
 */
class UserActivityController extends Controller
{
    public function __construct(private readonly VisitReportService $visits) {}

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString() ?: null,
            'role' => $request->string('role')->toString() ?: null,
            'device' => $request->string('device')->toString() ?: null,
            'country' => $request->string('country')->toString() ?: null,
            'from' => $request->date('from')?->toDateString(),
            'to' => $request->date('to')?->toDateString(),
        ];

        return Inertia::render('Admin/UserActivity', [
            'visits' => $this->visits->paginate($filters)->withQueryString(),
            'stats' => $this->visits->stats($filters),
            'options' => $this->visits->filterOptions(),
            'roles' => Role::orderBy('level', 'desc')->get(['id', 'name', 'slug']),
            'filters' => $filters,
        ]);
    }
}
