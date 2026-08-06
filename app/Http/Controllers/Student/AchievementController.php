<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Domain\Analytics\Services\AchievementService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Student achievements ("badges") — earned + locked with progress. Viewing the
 * page evaluates and awards any newly-qualified badges.
 */
class AchievementController extends Controller
{
    public function __construct(private readonly AchievementService $achievements) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Student/Achievements', $this->achievements->forUser($request->user()));
    }
}
