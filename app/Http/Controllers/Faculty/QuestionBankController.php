<?php

declare(strict_types=1);

namespace App\Http\Controllers\Faculty;

use App\Domain\Academic\Services\QuestionBankService;
use App\Http\Controllers\Controller;
use App\Models\ClassTest;
use App\Models\Course;
use App\Models\QuestionBankItem;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Faculty question bank: curate reusable questions per course, import from
 * existing class tests, and spin draft class tests out of a selection.
 */
class QuestionBankController extends Controller
{
    public function __construct(private readonly QuestionBankService $bank) {}

    public function index(Request $request): Response
    {
        $faculty = $request->user();
        $courseIds = $this->bank->teachableCourseIds($faculty);
        $courseFilter = $request->integer('course') ?: null;

        return Inertia::render('Faculty/QuestionBank', [
            'items' => $this->bank->itemsFor($faculty, $courseFilter),
            'courses' => Course::whereIn('id', $courseIds)->orderBy('code')->get(['id', 'code', 'name']),
            'sections' => Section::where('faculty_id', $faculty->id)
                ->with('course:id,code')
                ->get()
                ->map(fn (Section $section) => [
                    'id' => $section->id,
                    'label' => ($section->course?->code ?? '').' — Section '.$section->label,
                    'courseId' => $section->course_id,
                ])
                ->values(),
            'tests' => ClassTest::whereIn('course_id', $courseIds)
                ->latest()
                ->get(['id', 'title', 'course_id'])
                ->map(fn (ClassTest $test) => ['id' => $test->id, 'title' => $test->title, 'courseId' => $test->course_id]),
            'courseFilter' => $courseFilter,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'type' => ['required', Rule::in(['mcq', 'true_false'])],
            'question_text' => ['required', 'string', 'max:2000'],
            'marks' => ['required', 'integer', 'min:1', 'max:100'],
            'correct_answer' => ['required', 'string'],
            'options' => ['array'],
            'options.*.key' => ['nullable', 'string', 'max:10'],
            'options.*.text' => ['nullable', 'string', 'max:500'],
            'topic' => ['nullable', 'string', 'max:120'],
            'difficulty' => ['nullable', Rule::in(['easy', 'medium', 'hard'])],
        ]);

        if ($data['type'] === 'mcq') {
            $keys = collect($data['options'] ?? [])->pluck('key')->filter();
            abort_if($keys->count() < 2, 422, 'MCQ questions need at least two options.');
            abort_unless($keys->contains($data['correct_answer']), 422, 'The correct answer must be one of the option keys.');
        } else {
            abort_unless(in_array(strtolower($data['correct_answer']), ['true', 'false'], true), 422, 'True/false answers must be "true" or "false".');
        }

        $this->bank->store($request->user(), $data);

        return back()->with('success', 'Question added to the bank.');
    }

    public function destroy(Request $request, QuestionBankItem $item): RedirectResponse
    {
        $this->bank->delete($request->user(), $item);

        return back()->with('success', 'Question removed.');
    }

    public function import(Request $request, ClassTest $classTest): RedirectResponse
    {
        $imported = $this->bank->importFromTest($request->user(), $classTest);

        return back()->with('success', $imported > 0
            ? "Imported {$imported} question(s) from \"{$classTest->title}\"."
            : 'Nothing new to import — those questions are already in the bank.');
    }

    public function createTest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'title' => ['required', 'string', 'max:255'],
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer', 'exists:question_bank_items,id'],
        ]);

        $test = $this->bank->createDraftTest(
            faculty: $request->user(),
            section: Section::findOrFail($data['section_id']),
            title: $data['title'],
            itemIds: array_map('intval', $data['item_ids']),
        );

        return redirect()
            ->route('faculty.class-tests.edit', $test)
            ->with('success', 'Draft test created from the bank — review and publish it here.');
    }
}
