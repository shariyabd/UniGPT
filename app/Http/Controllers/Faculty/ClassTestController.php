<?php

declare(strict_types=1);

namespace App\Http\Controllers\Faculty;

use App\Domain\Academic\Services\ClassTestService;
use App\Domain\User\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Faculty\GenerateClassTestRequest;
use App\Http\Requests\Faculty\StoreClassTestRequest;
use App\Http\Requests\Faculty\UpdateClassTestRequest;
use App\Models\ClassTest;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClassTestController extends Controller
{
    public function __construct(
        private readonly ClassTestService $classTests,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(): Response
    {
        $search = trim((string) request()->input('search', '')) ?: null;

        return Inertia::render('Faculty/ClassTests/Index', [
            'tests' => $this->classTests->facultyList($this->user(), $search),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Faculty/ClassTests/Form', [
            'mode' => 'create',
            'sections' => $this->classTests->sectionOptions($this->user()),
        ]);
    }

    /**
     * AI-draft questions for the authoring form. Returns JSON consumed in-place by
     * the form — nothing is persisted until the faculty saves the test.
     */
    public function generate(GenerateClassTestRequest $request): JsonResponse
    {
        return response()->json([
            'questions' => $this->classTests->generateQuestions($request->validated()),
        ]);
    }

    public function store(StoreClassTestRequest $request): RedirectResponse
    {
        $test = $this->classTests->create($request->validated(), $request->user());

        $this->activity->log('class_test.created', "Created class test \"{$test->title}\"", $test, [], $request->user());

        return redirect()->route('faculty.class-tests')->with('success', 'Class test created.');
    }

    public function edit(ClassTest $classTest): Response
    {
        $this->authorizeTest($classTest);

        return Inertia::render('Faculty/ClassTests/Form', [
            'mode' => 'edit',
            'test' => $this->classTests->authoringPayload($classTest),
            'sections' => $this->classTests->sectionOptions($this->user()),
        ]);
    }

    public function update(UpdateClassTestRequest $request, ClassTest $classTest): RedirectResponse
    {
        $this->authorizeTest($classTest);

        $this->classTests->update($classTest, $request->validated());

        $this->activity->log('class_test.updated', "Updated class test \"{$classTest->title}\"", $classTest, [], $request->user());

        return redirect()->route('faculty.class-tests')->with('success', 'Class test updated.');
    }

    public function toggleStatus(ClassTest $classTest): RedirectResponse
    {
        $this->authorizeTest($classTest);

        $next = $classTest->status === 'published' ? 'closed' : 'published';
        $this->classTests->setStatus($classTest, $next);

        return back()->with('success', $next === 'published' ? 'Class test published.' : 'Class test closed.');
    }

    public function destroy(ClassTest $classTest): RedirectResponse
    {
        $this->authorizeTest($classTest);

        $title = $classTest->title;
        $this->classTests->delete($classTest);

        $this->activity->log('class_test.deleted', "Deleted class test \"{$title}\"", null, ['title' => $title], request()->user());

        return redirect()->route('faculty.class-tests')->with('success', 'Class test deleted.');
    }

    public function results(ClassTest $classTest): Response
    {
        $this->authorizeTest($classTest);

        return Inertia::render('Faculty/ClassTests/Results', $this->classTests->resultsFor($classTest));
    }

    /**
     * A faculty member may only manage a test for a section they teach.
     */
    private function authorizeTest(ClassTest $classTest): void
    {
        $user = $this->user();

        abort_unless(
            $user->isAdmin() || $user->teachingSectionIds()->contains($classTest->section_id),
            403,
        );
    }

    private function user(): User
    {
        return request()->user();
    }
}
