<?php

declare(strict_types=1);

namespace App\Http\Controllers\Faculty;

use App\Domain\Academic\Services\ClassTestService;
use App\Domain\Academic\Services\ExamSecurityService;
use App\Domain\User\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Faculty\GenerateClassTestRequest;
use App\Http\Requests\Faculty\StoreClassTestRequest;
use App\Http\Requests\Faculty\UpdateClassTestRequest;
use App\Models\ClassTest;
use App\Models\ClassTestAttempt;
use App\Models\ClassTestRecording;
use App\Models\ClassTestSnapshot;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClassTestController extends Controller
{
    public function __construct(
        private readonly ClassTestService $classTests,
        private readonly ExamSecurityService $security,
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
            'securityLayers' => $this->security->availableLayers(),
            'defaultSecurity' => $this->security->defaultSelection(),
            'maxWarningsDefault' => (int) config('exam_security.max_warnings_default'),
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
            'securityLayers' => $this->security->availableLayers(),
            'defaultSecurity' => $this->security->defaultSelection(),
            'maxWarningsDefault' => (int) config('exam_security.max_warnings_default'),
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
     * The full proctoring dossier for one student's attempt (timeline, risk,
     * fingerprint, recordings, answers).
     */
    public function attempt(ClassTest $classTest, ClassTestAttempt $attempt): Response
    {
        $this->authorizeTest($classTest);
        abort_unless($attempt->class_test_id === $classTest->id, 404);

        return Inertia::render('Faculty/ClassTests/Attempt', $this->classTests->attemptReview($classTest, $attempt));
    }

    /**
     * Stream one recording chunk from the private disk. Faculty/admin only, and
     * strictly scoped to a chunk belonging to this test's attempt.
     */
    public function recording(ClassTest $classTest, ClassTestAttempt $attempt, ClassTestRecording $recording): StreamedResponse
    {
        $this->authorizeTest($classTest);
        abort_unless(
            $attempt->class_test_id === $classTest->id && $recording->attempt_id === $attempt->id,
            404,
        );

        $disk = Storage::disk($recording->disk);
        abort_unless($disk->exists($recording->path), 404);

        return $disk->response($recording->path, null, [
            'Content-Type' => $recording->mime ?: 'video/webm',
        ]);
    }

    /**
     * Stream one snapshot-evidence frame from the private disk. Same access
     * rules and scoping as recording chunks.
     */
    public function snapshot(ClassTest $classTest, ClassTestAttempt $attempt, ClassTestSnapshot $snapshot): StreamedResponse
    {
        $this->authorizeTest($classTest);
        abort_unless(
            $attempt->class_test_id === $classTest->id && $snapshot->attempt_id === $attempt->id,
            404,
        );

        $disk = Storage::disk($snapshot->disk);
        abort_unless($disk->exists($snapshot->path), 404);

        return $disk->response($snapshot->path, null, [
            'Content-Type' => 'image/jpeg',
        ]);
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
