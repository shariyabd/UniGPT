<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Academic\Services\ExamService;
use App\Enums\ExamType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExamRequest;
use App\Models\Course;
use App\Models\Department;
use App\Models\Exam;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    public function __construct(
        private readonly ExamService $exams,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'course_id' => $request->input('course_id'),
            'department_id' => $request->input('department_id'),
            'section' => $request->string('section')->trim()->value() ?: null,
            'type' => $request->input('type'),
            'date_from' => $request->date('date_from')?->toDateString(),
            'date_to' => $request->date('date_to')?->toDateString(),
        ];

        return Inertia::render('Admin/Exams', [
            // Filtered + paginated at the query level so only the current page's
            // exams hydrate — bounded memory on a growing exam table.
            'exams' => $this->exams->adminListPage($filters),
            'courses' => Course::with('sections:id,course_id,label')
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'sections' => $this->exams->sectionOptions(),
            'types' => ExamType::options(),
            'filters' => $filters,
        ]);
    }

    public function store(ExamRequest $request): RedirectResponse
    {
        $exams = $this->exams->create($request->validated(), $request->user());
        $first = $exams->first();
        $count = $exams->count();

        $this->activity->log(
            'exam.created',
            "Scheduled {$first?->title}".($count > 1 ? " for {$count} sections" : ''),
            $first,
            [],
            $request->user(),
        );

        return back()->with('success', $count > 1
            ? "Exam scheduled for {$count} sections."
            : 'Exam scheduled.');
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
