<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Academic\Services\ExamService;
use App\Enums\ExamType;
use App\Http\Controllers\Concerns\PaginatesCollections;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExamRequest;
use App\Models\Course;
use App\Models\Department;
use App\Models\Exam;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    use PaginatesCollections;

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

        $allExams = $this->exams->adminList();

        // Distinct section labels available across every exam, so the section
        // filter (and the department + section combination) offers real choices.
        $sectionOptions = $allExams
            ->pluck('section')
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL)
            ->values();

        // Filter the full exam list server-side BEFORE paginating, so a chosen
        // course/department/section/type/search/date-range matches across every
        // page — not just the current 25.
        $exams = $allExams
            ->when($filters['course_id'], fn ($collection, $courseId) => $collection->filter(
                fn (array $exam) => (string) ($exam['course']['id'] ?? '') === (string) $courseId
            ))
            ->when($filters['department_id'], fn ($collection, $departmentId) => $collection->filter(
                fn (array $exam) => (string) ($exam['course']['departmentId'] ?? '') === (string) $departmentId
            ))
            ->when($filters['section'], fn ($collection, $section) => $collection->filter(
                fn (array $exam) => ($exam['section'] ?? null) === $section
            ))
            ->when($filters['type'], fn ($collection, $type) => $collection->filter(
                fn (array $exam) => $exam['type'] === $type
            ))
            ->when($filters['date_from'], fn ($collection, $from) => $collection->filter(
                fn (array $exam) => $exam['date'] >= $from
            ))
            ->when($filters['date_to'], fn ($collection, $to) => $collection->filter(
                fn (array $exam) => $exam['date'] <= $to
            ))
            ->when($filters['search'], function ($collection, $search) {
                $needle = Str::lower($search);

                return $collection->filter(fn (array $exam) => Str::contains(Str::lower(
                    $exam['title']
                    .' '.($exam['course']['code'] ?? '')
                    .' '.($exam['course']['name'] ?? '')
                    .' '.($exam['section'] ? 'section '.$exam['section'] : '')
                    .' '.($exam['location'] ?? '')
                ), $needle));
            })
            ->values();

        return Inertia::render('Admin/Exams', [
            'exams' => $this->paginateCollection($exams),
            'courses' => Course::with('sections:id,course_id,label')
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'sections' => $sectionOptions,
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
