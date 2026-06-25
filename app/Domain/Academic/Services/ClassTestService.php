<?php

declare(strict_types=1);

namespace App\Domain\Academic\Services;

use App\Domain\Chat\Services\TeachingAssistantService;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\User\Models\User;
use App\Enums\NotificationType;
use App\Models\ClassTest;
use App\Models\ClassTestAttempt;
use App\Models\ClassTestQuestion;
use App\Models\Section;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Faculty-authored interactive class tests / quizzes: authoring, attempt
 * lifecycle and server-authoritative auto-grading. Correct answers never leave
 * the server for an in-progress attempt.
 */
class ClassTestService
{
    /** Grace window (seconds) added to the deadline to absorb network/clock skew. */
    private const SUBMIT_GRACE_SECONDS = 10;

    public function __construct(
        private readonly NotificationService $notifications,
        private readonly TeachingAssistantService $assistant,
    ) {}

    /**
     * Generate draft questions with AI for the authoring form. Faculty can edit
     * every question before saving — this only seeds the builder. Restricted to
     * the auto-gradable types this feature supports (MCQ + True/False), and
     * returned in the exact shape the form expects.
     *
     * @param  array<string, mixed>  $params
     * @return array<int, array<string, mixed>>
     */
    public function generateQuestions(array $params): array
    {
        $count = max(1, min(20, (int) ($params['questionCount'] ?? 5)));
        $marks = max(1, (int) ($params['marks'] ?? 1));

        $requested = (array) ($params['types'] ?? ['mcq', 'true_false']);
        $aiTypes = [];
        if (in_array('mcq', $requested, true)) {
            $aiTypes[] = 'multiple-choice';
        }
        if (in_array('true_false', $requested, true)) {
            $aiTypes[] = 'true-false';
        }
        if ($aiTypes === []) {
            $aiTypes = ['multiple-choice', 'true-false'];
        }

        $quiz = $this->assistant->generateQuiz([
            'topic' => $params['topic'] ?? 'General Knowledge',
            'questionCount' => $count,
            'difficulty' => $params['difficulty'] ?? 'medium',
            'questionTypes' => $aiTypes,
            'includeExplanations' => false,
        ]);

        return collect($quiz['questions'] ?? [])
            ->map(fn (array $question) => $this->mapGeneratedQuestion($question, $marks))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Map one AI-generated question (types "multiple-choice"/"true-false", options
     * as plain strings, answer as the correct option text/bool) into the form's
     * {type, question_text, options:[{key,text}], correct_answer, marks} shape.
     *
     * @param  array<string, mixed>  $question
     * @return array<string, mixed>|null
     */
    private function mapGeneratedQuestion(array $question, int $marks): ?array
    {
        $text = trim((string) ($question['question'] ?? ''));
        if ($text === '') {
            return null;
        }

        if (($question['type'] ?? 'multiple-choice') === 'true-false') {
            $answer = $question['answer'] ?? true;
            $isTrue = is_bool($answer)
                ? $answer
                : in_array(strtolower(trim((string) $answer)), ['true', 't', 'yes', '1'], true);

            return [
                'type' => 'true_false',
                'question_text' => $text,
                'options' => [],
                'correct_answer' => $isTrue ? 'true' : 'false',
                'marks' => $marks,
            ];
        }

        $keys = ['A', 'B', 'C', 'D', 'E', 'F'];
        $rawOptions = array_values(array_filter(
            array_map('strval', (array) ($question['options'] ?? [])),
            fn (string $option) => trim($option) !== '',
        ));
        if (count($rawOptions) < 2) {
            $rawOptions = ['Option A', 'Option B', 'Option C', 'Option D'];
        }
        $rawOptions = array_slice($rawOptions, 0, count($keys));

        $options = [];
        foreach ($rawOptions as $i => $optionText) {
            $options[] = ['key' => $keys[$i], 'text' => $optionText];
        }

        // The AI gives the correct answer as the option TEXT — resolve it to a key,
        // defaulting to the first option if no exact match is found.
        $answerText = strtolower(trim((string) ($question['answer'] ?? '')));
        $correctKey = $options[0]['key'];
        foreach ($options as $option) {
            if (strtolower(trim((string) $option['text'])) === $answerText) {
                $correctKey = $option['key'];
                break;
            }
        }

        return [
            'type' => 'mcq',
            'question_text' => $text,
            'options' => $options,
            'correct_answer' => $correctKey,
            'marks' => $marks,
        ];
    }

    // ---------------------------------------------------------------------
    // Faculty: authoring
    // ---------------------------------------------------------------------

    /**
     * Tests for the sections a faculty member teaches, newest first, paginated.
     * An optional search term matches the test title or its course code/name.
     *
     * @return LengthAwarePaginator<array<string, mixed>>
     */
    public function facultyList(User $faculty, ?string $search = null): LengthAwarePaginator
    {
        return ClassTest::with(['course', 'section'])
            ->withCount(['questions', 'attempts'])
            ->whereIn('section_id', $faculty->teachingSectionIds())
            ->when($search, function ($query, string $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhereHas('course', fn ($course) => $course
                            ->where('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (ClassTest $test) => $this->presentSummary($test));
    }

    /**
     * The (course → section) pairs a faculty member may target when creating a
     * test — used to populate the authoring form's pickers.
     *
     * @return array<int, array<string, mixed>>
     */
    public function sectionOptions(User $faculty): array
    {
        return $faculty->teachingSections()
            ->with('course.department')
            ->get()
            ->map(fn (Section $section) => [
                'sectionId' => $section->id,
                'sectionLabel' => $section->label,
                'courseId' => $section->course?->id,
                'courseCode' => $section->course?->code,
                'courseName' => $section->course?->name,
                'semester' => $section->course?->semester,
                'department' => $section->course?->department?->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $faculty): ClassTest
    {
        return DB::transaction(function () use ($data, $faculty): ClassTest {
            $section = Section::findOrFail($data['section_id']);

            $test = ClassTest::create([
                'course_id' => $section->course_id,
                'section_id' => $section->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'duration_minutes' => $data['duration_minutes'],
                'pass_marks' => $data['pass_marks'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'available_from' => $data['available_from'] ?? null,
                'available_until' => $data['available_until'] ?? null,
                'shuffle_questions' => $data['shuffle_questions'] ?? true,
                'created_by' => $faculty->id,
            ]);

            $this->syncQuestions($test, $data['questions']);

            if ($test->status === 'published') {
                $this->notifyStudents($test);
            }

            return $test->fresh(['questions']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ClassTest $test, array $data): ClassTest
    {
        return DB::transaction(function () use ($test, $data): ClassTest {
            $wasPublished = $test->status === 'published';

            $test->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'duration_minutes' => $data['duration_minutes'],
                'pass_marks' => $data['pass_marks'] ?? null,
                'status' => $data['status'] ?? $test->status,
                'available_from' => $data['available_from'] ?? null,
                'available_until' => $data['available_until'] ?? null,
                'shuffle_questions' => $data['shuffle_questions'] ?? true,
            ]);

            $this->syncQuestions($test, $data['questions']);

            if (! $wasPublished && $test->status === 'published') {
                $this->notifyStudents($test);
            }

            return $test->fresh(['questions']);
        });
    }

    public function setStatus(ClassTest $test, string $status): ClassTest
    {
        $wasPublished = $test->status === 'published';
        $test->update(['status' => $status]);

        if (! $wasPublished && $status === 'published') {
            $this->notifyStudents($test);
        }

        return $test;
    }

    public function delete(ClassTest $test): void
    {
        $test->delete();
    }

    /**
     * Full test shape for the authoring form (includes correct answers — faculty
     * only).
     *
     * @return array<string, mixed>
     */
    public function authoringPayload(ClassTest $test): array
    {
        $test->loadMissing('questions', 'course', 'section');

        return [
            'id' => $test->id,
            'sectionId' => $test->section_id,
            'title' => $test->title,
            'description' => $test->description,
            'durationMinutes' => $test->duration_minutes,
            'passMarks' => $test->pass_marks,
            'status' => $test->status,
            'availableFrom' => $test->available_from?->format('Y-m-d\TH:i'),
            'availableUntil' => $test->available_until?->format('Y-m-d\TH:i'),
            'shuffleQuestions' => $test->shuffle_questions,
            'course' => ['code' => $test->course?->code, 'name' => $test->course?->name],
            'section' => $test->section?->label,
            'questions' => $test->questions->map(fn (ClassTestQuestion $q) => [
                'type' => $q->type,
                'question_text' => $q->question_text,
                'options' => $q->options ?? [],
                'correct_answer' => $q->correct_answer,
                'marks' => $q->marks,
            ])->values(),
        ];
    }

    /**
     * Faculty results view: the test plus every attempt with its score/status.
     *
     * @return array<string, mixed>
     */
    public function resultsFor(ClassTest $test): array
    {
        $test->loadCount('questions');
        $attempts = $test->attempts()
            ->with('student')
            ->orderByDesc('submitted_at')
            ->get();

        $graded = $attempts->whereIn('status', ['submitted', 'expired']);

        return [
            'test' => $this->presentSummary($test),
            'attempts' => $attempts->map(fn (ClassTestAttempt $a) => [
                'id' => $a->id,
                'student' => ['id' => $a->student?->id, 'name' => $a->student?->name, 'studentId' => $a->student?->student_id],
                'status' => $a->status,
                'score' => $a->score,
                'totalMarks' => $a->total_marks,
                'violationCount' => $a->violation_count,
                'submittedAt' => $a->submitted_at?->toDayDateTimeString(),
            ])->values(),
            'stats' => [
                'attempts' => $attempts->count(),
                'disqualified' => $attempts->where('status', 'disqualified')->count(),
                'averageScore' => $graded->isNotEmpty() ? round((float) $graded->avg('score'), 1) : null,
            ],
        ];
    }

    // ---------------------------------------------------------------------
    // Student: attempts
    // ---------------------------------------------------------------------

    /**
     * Published, in-window tests for the student's enrolled sections, each tagged
     * with the student's attempt state. Paginated (newest first).
     */
    public function studentList(User $student): LengthAwarePaginator
    {
        $sectionIds = $student->enrolledSectionIds();

        if ($sectionIds->isEmpty()) {
            return new LengthAwarePaginator(items: [], total: 0, perPage: 25);
        }

        $attempts = ClassTestAttempt::where('user_id', $student->id)
            ->get()
            ->keyBy('class_test_id');

        return ClassTest::with(['course', 'section'])
            ->withCount('questions')
            ->published()
            ->whereIn('section_id', $sectionIds)
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(function (ClassTest $test) use ($attempts) {
                $attempt = $attempts->get($test->id);

                return [
                    ...$this->presentSummary($test),
                    'isOpen' => $test->isOpen(),
                    'attemptStatus' => $attempt?->status,
                    'score' => $attempt && $attempt->isFinalised() ? $attempt->score : null,
                ];
            });
    }

    /**
     * Resolve the student's attempt for a test, creating an in-progress one if the
     * test is open and untaken. Returns the attempt (caller routes draft vs result).
     */
    public function startAttempt(User $student, ClassTest $test): ClassTestAttempt
    {
        $attempt = ClassTestAttempt::firstOrCreate(
            ['class_test_id' => $test->id, 'user_id' => $student->id],
            [
                'status' => 'in_progress',
                'started_at' => now(),
                'total_marks' => (int) $test->questions()->sum('marks'),
            ],
        );

        // An in-progress attempt whose deadline has already passed is finalised as
        // expired (grading whatever — if anything — was saved).
        if ($attempt->status === 'in_progress' && $this->deadline($test, $attempt)->isPast()) {
            $this->finaliseExpiredWithoutAnswers($attempt);
        }

        return $attempt;
    }

    /**
     * The proctored-exam payload: questions WITHOUT correct answers, plus the
     * server-authoritative remaining time.
     *
     * @return array<string, mixed>
     */
    public function takePayload(ClassTest $test, ClassTestAttempt $attempt): array
    {
        $questions = $test->questions->map(fn (ClassTestQuestion $q) => $this->presentQuestionForStudent($q));

        if ($test->shuffle_questions) {
            $questions = $questions->shuffle();
        }

        return [
            'test' => [
                'id' => $test->id,
                'title' => $test->title,
                'description' => $test->description,
                'durationMinutes' => $test->duration_minutes,
                'totalMarks' => $attempt->total_marks,
                'maxWarnings' => $test->max_warnings,
                'questionCount' => $questions->count(),
                'course' => ['code' => $test->course?->code, 'name' => $test->course?->name],
                'section' => $test->section?->label,
            ],
            'attempt' => [
                'id' => $attempt->id,
                'remainingSeconds' => $this->remainingSeconds($test, $attempt),
            ],
            'questions' => $questions->values(),
        ];
    }

    /**
     * Grade and finalise an attempt. Server-authoritative: answers are matched
     * against stored correct answers here, never on the client. A disqualified
     * attempt always scores 0; a late submission is recorded as expired but still
     * graded on whatever was answered.
     *
     * @param  array<int, string|null>  $answers  question_id => selected answer
     */
    public function submit(ClassTestAttempt $attempt, array $answers, bool $disqualified = false): ClassTestAttempt
    {
        if ($attempt->isFinalised()) {
            return $attempt;
        }

        return DB::transaction(function () use ($attempt, $answers, $disqualified): ClassTestAttempt {
            $test = $attempt->classTest()->with('questions')->first();
            $isLate = $this->deadline($test, $attempt)->isPast();
            $score = 0;

            foreach ($test->questions as $question) {
                $selected = $answers[$question->id] ?? null;
                $correct = $selected !== null
                    && (string) $selected === (string) $question->correct_answer;
                $awarded = ($correct && ! $disqualified) ? $question->marks : 0;
                $score += $awarded;

                $attempt->answers()->updateOrCreate(
                    ['question_id' => $question->id],
                    [
                        'selected_answer' => $selected,
                        'is_correct' => $selected === null ? null : $correct,
                        'marks_awarded' => $awarded,
                    ],
                );
            }

            $attempt->update([
                'status' => $disqualified ? 'disqualified' : ($isLate ? 'expired' : 'submitted'),
                'score' => $disqualified ? 0 : $score,
                'total_marks' => (int) $test->questions->sum('marks'),
                'submitted_at' => now(),
            ]);

            $this->notifyFaculty($test, $attempt);

            return $attempt->fresh();
        });
    }

    /**
     * Record a proctoring violation. Returns true once the configured warning
     * threshold has been exceeded (caller should then disqualify + auto-submit).
     */
    public function recordViolation(ClassTestAttempt $attempt): bool
    {
        $attempt->increment('violation_count');

        $max = $attempt->classTest?->max_warnings ?? 1;

        return $attempt->violation_count > $max;
    }

    /**
     * The student's own result breakdown for a finalised attempt.
     *
     * @return array<string, mixed>
     */
    public function resultFor(ClassTestAttempt $attempt): array
    {
        $test = $attempt->classTest;
        $answers = $attempt->answers()->get()->keyBy('question_id');

        return [
            'test' => [
                'id' => $test->id,
                'title' => $test->title,
                'course' => ['code' => $test->course?->code, 'name' => $test->course?->name],
                'section' => $test->section?->label,
                'passMarks' => $test->pass_marks,
            ],
            'attempt' => [
                'status' => $attempt->status,
                'score' => $attempt->score,
                'totalMarks' => $attempt->total_marks,
                'violationCount' => $attempt->violation_count,
                'passed' => $test->pass_marks !== null ? $attempt->score >= $test->pass_marks : null,
                'submittedAt' => $attempt->submitted_at?->toDayDateTimeString(),
            ],
            'questions' => $test->questions->map(function (ClassTestQuestion $q) use ($answers) {
                $answer = $answers->get($q->id);

                return [
                    'id' => $q->id,
                    'type' => $q->type,
                    'questionText' => $q->question_text,
                    'options' => $q->options,
                    'marks' => $q->marks,
                    'correctAnswer' => $q->correct_answer,
                    'selectedAnswer' => $answer?->selected_answer,
                    'isCorrect' => $answer?->is_correct,
                    'marksAwarded' => $answer?->marks_awarded ?? 0,
                ];
            })->values(),
        ];
    }

    // ---------------------------------------------------------------------
    // Internals
    // ---------------------------------------------------------------------

    /**
     * Replace the test's questions and recompute its total marks.
     *
     * @param  array<int, array<string, mixed>>  $questions
     */
    private function syncQuestions(ClassTest $test, array $questions): void
    {
        $test->questions()->delete();

        $total = 0;
        foreach (array_values($questions) as $index => $question) {
            $isMcq = ($question['type'] ?? 'mcq') === 'mcq';
            $marks = (int) ($question['marks'] ?? 1);
            $total += $marks;

            $test->questions()->create([
                'type' => $question['type'] ?? 'mcq',
                'question_text' => $question['question_text'],
                'options' => $isMcq ? array_values($question['options'] ?? []) : null,
                'correct_answer' => (string) $question['correct_answer'],
                'marks' => $marks,
                'position' => $index,
            ]);
        }

        $test->update(['total_marks' => $total]);
    }

    private function notifyStudents(ClassTest $test): void
    {
        $test->loadMissing('section', 'course');
        $section = $test->section;

        if (! $section) {
            return;
        }

        $this->notifications->notifyMany(
            users: $section->students()->wherePivot('status', 'enrolled')->get(),
            type: NotificationType::CLASS_TEST,
            title: "New class test — {$test->course?->code}",
            message: "\"{$test->title}\" (Section {$section->label}) is now available.",
            link: route('class-tests'),
            data: ['class_test_id' => $test->id, 'section_id' => $section->id],
        );
    }

    private function notifyFaculty(ClassTest $test, ClassTestAttempt $attempt): void
    {
        $test->loadMissing('section.faculty', 'course');
        $faculty = $test->section?->faculty;

        if (! $faculty) {
            return;
        }

        $student = $attempt->student;
        $this->notifications->notify(
            user: $faculty,
            type: NotificationType::CLASS_TEST,
            title: 'Class test submitted',
            message: "{$student?->name} finished \"{$test->title}\" ({$attempt->score}/{$attempt->total_marks}).",
            link: route('faculty.class-tests.results', $test->id),
            data: ['class_test_id' => $test->id, 'attempt_id' => $attempt->id],
        );
    }

    private function finaliseExpiredWithoutAnswers(ClassTestAttempt $attempt): void
    {
        $attempt->update([
            'status' => 'expired',
            'submitted_at' => now(),
            'score' => 0,
        ]);
    }

    private function deadline(ClassTest $test, ClassTestAttempt $attempt): Carbon
    {
        return ($attempt->started_at ?? now())
            ->copy()
            ->addMinutes($test->duration_minutes)
            ->addSeconds(self::SUBMIT_GRACE_SECONDS);
    }

    /**
     * Seconds left until the attempt's deadline. The clock is anchored to
     * started_at (set the moment the student starts), NOT to when the test was
     * published — so a 5-minute test gives every student a full 5 minutes from
     * their own start. Uses raw timestamps to stay correct across Carbon versions.
     */
    private function remainingSeconds(ClassTest $test, ClassTestAttempt $attempt): int
    {
        $deadline = ($attempt->started_at ?? now())->copy()->addMinutes($test->duration_minutes);

        return max(0, $deadline->getTimestamp() - now()->getTimestamp());
    }

    /**
     * @return array<string, mixed>
     */
    private function presentSummary(ClassTest $test): array
    {
        return [
            'id' => $test->id,
            'title' => $test->title,
            'description' => $test->description,
            'status' => $test->status,
            'durationMinutes' => $test->duration_minutes,
            'totalMarks' => $test->total_marks,
            'passMarks' => $test->pass_marks,
            'questionCount' => $test->questions_count ?? $test->questions()->count(),
            'attemptCount' => $test->attempts_count ?? null,
            'availableFrom' => $test->available_from?->toDateTimeString(),
            'availableUntil' => $test->available_until?->toDateTimeString(),
            'course' => [
                'id' => $test->course?->id,
                'code' => $test->course?->code,
                'name' => $test->course?->name,
            ],
            'sectionId' => $test->section_id,
            'section' => $test->section?->label,
        ];
    }

    /**
     * Student-facing question shape — deliberately WITHOUT the correct answer.
     *
     * @return array<string, mixed>
     */
    private function presentQuestionForStudent(ClassTestQuestion $question): array
    {
        $options = $question->type === 'true_false'
            ? [['key' => 'true', 'text' => 'True'], ['key' => 'false', 'text' => 'False']]
            : ($question->options ?? []);

        return [
            'id' => $question->id,
            'type' => $question->type,
            'questionText' => $question->question_text,
            'options' => $options,
            'marks' => $question->marks,
        ];
    }
}
