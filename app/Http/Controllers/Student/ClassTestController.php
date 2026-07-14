<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Domain\Academic\Services\ClassTestService;
use App\Domain\Academic\Services\ExamSecurityService;
use App\Domain\User\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\CaptureFingerprintRequest;
use App\Http\Requests\Student\LogClassTestEventsRequest;
use App\Http\Requests\Student\SubmitClassTestRequest;
use App\Http\Requests\Student\UploadClassTestRecordingRequest;
use App\Http\Requests\Student\UploadClassTestSnapshotRequest;
use App\Models\ClassTest;
use App\Models\ClassTestAttempt;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClassTestController extends Controller
{
    public function __construct(
        private readonly ClassTestService $classTests,
        private readonly ExamSecurityService $security,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Student/ClassTests/Index', [
            'tests' => $this->classTests->studentList($this->user()),
        ]);
    }

    /**
     * Instructions / rules page shown before the student starts.
     */
    public function show(ClassTest $classTest): Response|RedirectResponse
    {
        $this->ensureEnrolled($classTest);

        $attempt = $this->attemptFor($classTest);
        if ($attempt && $attempt->isFinalised()) {
            return redirect()->route('class-tests.result', $classTest->id);
        }

        $classTest->loadCount('questions');

        return Inertia::render('Student/ClassTests/Instructions', [
            'test' => [
                'id' => $classTest->id,
                'title' => $classTest->title,
                'description' => $classTest->description,
                'durationMinutes' => $classTest->duration_minutes,
                'totalMarks' => $classTest->total_marks,
                'passMarks' => $classTest->pass_marks,
                'maxWarnings' => $classTest->max_warnings,
                'questionCount' => $classTest->questions_count,
                'isOpen' => $classTest->isOpen(),
                'course' => ['code' => $classTest->course?->code, 'name' => $classTest->course?->name],
                'section' => $classTest->section?->label,
            ],
            'security' => $this->security->clientConfig($classTest),
            'inProgress' => (bool) $attempt,
        ]);
    }

    /**
     * Begin (or resume) the attempt, then hand off to the proctored take page.
     */
    public function start(ClassTest $classTest): RedirectResponse
    {
        $this->ensureEnrolled($classTest);
        abort_unless($classTest->isOpen(), 403, 'This class test is not currently open.');

        $attempt = $this->classTests->startAttempt($this->user(), $classTest);

        if ($attempt->isFinalised()) {
            return redirect()->route('class-tests.result', $classTest->id);
        }

        return redirect()->route('class-tests.take', $classTest->id);
    }

    /**
     * The proctored exam screen (fullscreen, timer, anti-cheat). Requires a live
     * in-progress attempt.
     */
    public function take(ClassTest $classTest): Response|RedirectResponse
    {
        $this->ensureEnrolled($classTest);

        $attempt = $this->attemptFor($classTest);

        if (! $attempt) {
            return redirect()->route('class-tests.show', $classTest->id);
        }

        if ($attempt->isFinalised()) {
            return redirect()->route('class-tests.result', $classTest->id);
        }

        $classTest->load('questions', 'course', 'section');

        return Inertia::render('Student/ClassTests/Take', $this->classTests->takePayload($classTest, $attempt));
    }

    /**
     * Record a proctoring violation (tab switch / fullscreen exit). Returns whether
     * the student has now crossed the warning threshold and should be disqualified.
     */
    public function violation(ClassTest $classTest): JsonResponse
    {
        $this->ensureEnrolled($classTest);

        $attempt = $this->attemptFor($classTest);

        if (! $attempt || $attempt->isFinalised()) {
            return response()->json(['disqualified' => true]);
        }

        $disqualified = $this->classTests->recordViolation($attempt);

        return response()->json([
            'disqualified' => $disqualified,
            'violationCount' => $attempt->violation_count,
        ]);
    }

    /**
     * Store the browser fingerprint for the live attempt (fired once on load).
     */
    public function fingerprint(CaptureFingerprintRequest $request, ClassTest $classTest): JsonResponse
    {
        $this->ensureEnrolled($classTest);

        $attempt = $this->attemptFor($classTest);

        if ($attempt && ! $attempt->isFinalised()) {
            $this->security->captureFingerprint($attempt, $request->components());
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Ingest a batch of proctoring/behaviour events. Violation-severity events
     * bump the warning counter; the response tells the client whether the
     * threshold has now been crossed (client then auto-submits as disqualified).
     */
    public function events(LogClassTestEventsRequest $request, ClassTest $classTest): JsonResponse
    {
        $this->ensureEnrolled($classTest);

        $attempt = $this->attemptFor($classTest);

        if (! $attempt || $attempt->isFinalised()) {
            return response()->json(['disqualified' => true, 'violationCount' => $attempt?->violation_count ?? 0]);
        }

        $result = $this->security->recordEvents($attempt, $request->events());

        return response()->json([
            'disqualified' => $result['disqualified'],
            'violationCount' => $result['violationCount'],
        ]);
    }

    /**
     * Store one webcam/screen recording chunk. Rejected unless that media layer
     * is actually enabled for the test, so stray uploads never hit disk.
     */
    public function recording(UploadClassTestRecordingRequest $request, ClassTest $classTest): JsonResponse
    {
        $this->ensureEnrolled($classTest);

        $kind = $request->string('kind')->value();
        $layer = $kind === 'screen' ? 'screen_record' : 'webcam';
        abort_unless($this->security->isEnabled($classTest, $layer), 403, 'Recording is not enabled for this test.');

        $attempt = $this->attemptFor($classTest);

        if (! $attempt || $attempt->isFinalised()) {
            return response()->json(['ok' => false], 409);
        }

        $this->security->storeRecordingChunk(
            attempt: $attempt,
            kind: $kind,
            sequence: (int) $request->integer('sequence'),
            file: $request->file('chunk'),
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Store one snapshot-evidence frame. Layer-gated and capped per attempt so
     * a misbehaving client can never flood the disk.
     */
    public function snapshot(UploadClassTestSnapshotRequest $request, ClassTest $classTest): JsonResponse
    {
        $this->ensureEnrolled($classTest);

        abort_unless($this->security->isEnabled($classTest, 'snapshot_evidence'), 403, 'Snapshot evidence is not enabled for this test.');

        $attempt = $this->attemptFor($classTest);

        if (! $attempt || $attempt->isFinalised()) {
            return response()->json(['ok' => false], 409);
        }

        $stored = $this->security->storeSnapshot(
            attempt: $attempt,
            trigger: $request->string('trigger')->value(),
            sequence: (int) $request->integer('sequence'),
            file: $request->file('frame'),
        );

        return response()->json(['ok' => $stored !== null, 'capped' => $stored === null]);
    }

    public function submit(SubmitClassTestRequest $request, ClassTest $classTest): RedirectResponse
    {
        $this->ensureEnrolled($classTest);

        $attempt = $this->attemptFor($classTest);
        if (! $attempt) {
            return redirect()->route('class-tests.show', $classTest->id);
        }

        if (! $attempt->isFinalised()) {
            $attempt = $this->classTests->submit(
                attempt: $attempt,
                answers: $request->answerMap(),
                disqualified: $request->boolean('disqualified'),
            );

            $this->activity->log(
                $attempt->status === 'disqualified' ? 'class_test.disqualified' : 'class_test.submitted',
                "Finished class test \"{$classTest->title}\"",
                $attempt,
                ['status' => $attempt->status, 'score' => $attempt->score],
                $this->user(),
            );
        }

        return redirect()->route('class-tests.result', $classTest->id);
    }

    public function result(ClassTest $classTest): Response|RedirectResponse
    {
        $this->ensureEnrolled($classTest);

        $attempt = $this->attemptFor($classTest);
        if (! $attempt || ! $attempt->isFinalised()) {
            return redirect()->route('class-tests.show', $classTest->id);
        }

        $classTest->load('questions', 'course', 'section');

        return Inertia::render('Student/ClassTests/Result', $this->classTests->resultFor($attempt));
    }

    private function attemptFor(ClassTest $classTest): ?ClassTestAttempt
    {
        return ClassTestAttempt::where('class_test_id', $classTest->id)
            ->where('user_id', $this->user()->id)
            ->first();
    }

    /**
     * A student may only reach a published test in a section they're enrolled in.
     */
    private function ensureEnrolled(ClassTest $classTest): void
    {
        abort_unless(
            $classTest->status !== 'draft'
                && $this->user()->enrolledSectionIds()->contains($classTest->section_id),
            404,
        );
    }

    private function user(): User
    {
        return request()->user();
    }
}
