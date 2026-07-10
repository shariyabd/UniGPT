<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Domain\Academic\Services\CourseFeedbackService;
use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Student side of anonymous course feedback: see which enrolled sections are
 * collecting, submit or revise one response per section while the window is
 * open.
 */
class CourseFeedbackController extends Controller
{
    public function __construct(private readonly CourseFeedbackService $feedback) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Student/CourseFeedback', [
            'sections' => $this->feedback->sectionsForStudent($request->user()),
        ]);
    }

    public function store(Request $request, Section $section): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->feedback->submit(
            student: $request->user(),
            section: $section,
            rating: (int) $validated['rating'],
            comment: $validated['comment'] ?? null,
        );

        return back()->with('success', 'Thanks — your anonymous feedback was recorded.');
    }
}
