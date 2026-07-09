<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Domain\Academic\Services\StudyPlannerService;
use App\Enums\TaskPriority;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudyPlanRequest;
use App\Http\Requests\Student\StudyPlanTasksRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AI Study Planner — turns the student's upcoming deadlines into a reviewable
 * study schedule, and lets them save chosen sessions as personal Tasks.
 */
class StudyPlannerController extends Controller
{
    public function __construct(private readonly StudyPlannerService $planner) {}

    public function index(Request $request): Response
    {
        $student = $request->user();

        return Inertia::render('Student/StudyPlanner', [
            'deadlines' => $this->planner->deadlines($student),
        ]);
    }

    public function generate(StudyPlanRequest $request): JsonResponse
    {
        $plan = $this->planner->generatePlan($request->user(), $request->validated());

        return response()->json(['plan' => $plan]);
    }

    public function storeTasks(StudyPlanTasksRequest $request): RedirectResponse
    {
        $student = $request->user();

        $rows = collect($request->validated()['sessions'])->map(fn (array $session) => [
            'title' => $session['title'],
            'description' => $session['focus'] ?? null,
            'due_date' => $session['date'] ?? null,
            'priority' => TaskPriority::MEDIUM->value,
            'is_completed' => false,
        ])->all();

        $student->tasks()->createMany($rows);

        return back()->with('success', count($rows).' study session'.(count($rows) === 1 ? '' : 's').' added to your tasks.');
    }
}
