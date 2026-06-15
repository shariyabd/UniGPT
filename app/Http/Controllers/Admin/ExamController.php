<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Academic\Services\ExamService;
use App\Enums\ExamType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExamRequest;
use App\Models\Course;
use App\Models\Exam;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    public function __construct(
        private readonly ExamService $exams,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Exams', [
            'exams' => $this->exams->adminList(),
            'courses' => Course::orderBy('code')->get(['id', 'code', 'name']),
            'types' => ExamType::options(),
        ]);
    }

    public function store(ExamRequest $request): RedirectResponse
    {
        $exam = $this->exams->create($request->validated(), $request->user());

        $this->activity->log('exam.created', "Scheduled {$exam->title}", $exam, [], $request->user());

        return back()->with('success', 'Exam scheduled.');
    }

    public function update(ExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->exams->update($exam, $request->validated());

        $this->activity->log('exam.updated', "Updated {$exam->title}", $exam, [], $request->user());

        return back()->with('success', 'Exam updated.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->exams->delete($exam);

        return back()->with('success', 'Exam removed.');
    }
}
