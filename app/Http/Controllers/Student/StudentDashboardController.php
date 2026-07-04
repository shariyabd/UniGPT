<?php

namespace App\Http\Controllers\Student;

use App\Domain\Academic\Services\AttendanceService;
use App\Domain\Academic\Services\CalendarService;
use App\Domain\Academic\Services\CourseService;
use App\Domain\Academic\Services\ExamService;
use App\Domain\Academic\Services\TranscriptService;
use App\Domain\Chat\Document\Services\DocumentService;
use App\Domain\Chat\Support\AiSettings;
use App\Domain\User\Models\User;
use App\Enums\TaskPriority;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateProfileRequest;
use App\Http\Requests\Student\UpdateSettingsRequest;
use App\Http\Resources\DocumentResource;
use App\Infrastructure\FileStorage\DocumentStorageService;
use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ChatSession;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\Department;
use App\Models\Document;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StudentDashboardController extends Controller
{
    public function __construct(
        private readonly DocumentService $documents,
        private readonly CourseService $courses,
        private readonly AttendanceService $attendance,
        private readonly TranscriptService $transcript,
        private readonly ExamService $exams,
        private readonly CalendarService $calendar,
        private readonly DocumentStorageService $documentStorage,
    ) {}

    public function index(): Response
    {
        $user = $this->user();
        // The dashboard shows the student's *current-term* courses; past-term
        // enrollments remain available via Transcript, Roadmap and Materials.
        $currentCourses = $this->courses->studentCourses($user)
            ->where('isCurrent', true)
            ->values();
        $deadlines = $this->upcomingDeadlines($user);

        // Material stats count only the student's own sections, so they reflect
        // the resources actually shared with them (not every section's total).
        $currentSectionIds = $user->enrolledSections()
            ->wherePivotNotIn('status', ['dropped', 'completed', 'pending'])
            ->pluck('sections.id');

        $materialsTotal = CourseMaterial::whereIn('section_id', $currentSectionIds)
            ->where('is_published', true)
            ->count();
        $materialsViewed = $user->completedMaterials()
            ->where('is_published', true)
            ->whereIn('section_id', $currentSectionIds)
            ->count();

        return Inertia::render('Student/Dashboard', [
            'student' => $this->studentProfile($user),
            'stats' => [
                ['label' => 'Courses', 'value' => (string) $currentCourses->count(), 'change' => null, 'trend' => 'neutral', 'icon' => 'AcademicCapIcon', 'color' => 'blue'],
                ['label' => 'CGPA', 'value' => (string) $this->cgpa($user), 'change' => null, 'trend' => 'neutral', 'icon' => 'ChartBarIcon', 'color' => 'green'],
                ['label' => 'Saved Answers', 'value' => (string) $user->savedAnswers()->count(), 'change' => null, 'trend' => 'neutral', 'icon' => 'BookmarkIcon', 'color' => 'purple'],
                ['label' => 'Chat Sessions', 'value' => (string) $user->chatSessions()->count(), 'change' => null, 'trend' => 'neutral', 'icon' => 'ChatBubbleLeftRightIcon', 'color' => 'orange'],
            ],
            'recentChats' => $this->recentChats($user),
            'upcomingDeadlines' => $deadlines,
            'courses' => $currentCourses,
            'quickActions' => $this->quickActions(),
            'attendanceRate' => $this->attendanceRate($user),
            'studyStreak' => $this->studyStreak($user),
            'materialsSummary' => [
                'total' => $materialsTotal,
                'viewed' => $materialsViewed,
                'pending' => max($materialsTotal - $materialsViewed, 0),
            ],
        ]);
    }

    public function documents(Request $request): Response
    {
        $user = $this->user();

        // All filters are applied SERVER-SIDE (via DocumentService::applyFilters)
        // so they work across every paginated page, not just the current one.
        $filters = [
            'category' => $request->string('category')->toString() ?: null,
            'search' => $request->string('search')->toString() ?: null,
            'department_id' => $request->integer('department_id') ?: null,
            'file_type' => $request->string('file_type')->toString() ?: null,
        ];

        return Inertia::render('Student/Documents', [
            'documents' => DocumentResource::collection($this->documents->libraryFor($user, $filters, 25)),
            'filters' => $filters,
            'categories' => Document::approved()->visibleTo($user)
                ->select('category')->distinct()->pluck('category'),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'fileTypes' => Document::approved()->visibleTo($user)
                ->whereNotNull('file_type')
                ->select('file_type')->distinct()->orderBy('file_type')->pluck('file_type'),
        ]);
    }

    /**
     * Toggle the current student's bookmark on a library document.
     *
     * Scoped to documents the student may actually see (approved + visible),
     * so route-model binding can't be used to bookmark arbitrary documents.
     */
    public function toggleBookmark(Document $document): RedirectResponse
    {
        $user = $this->user();

        abort_unless(
            Document::approved()->visibleTo($user)->whereKey($document->getKey())->exists(),
            404,
        );

        $this->documents->toggleBookmark(user: $user, document: $document);

        return back(303);
    }

    public function materials(): Response
    {
        $user = $this->user();

        return Inertia::render('Student/Materials', [
            'courses' => $this->courses->studentMaterials($user),
            'enrolledCourses' => $this->courses->studentCourses($user),
        ]);
    }

    public function attendance(): Response
    {
        $user = $this->user();
        $courses = $this->attendance->studentSummary($user);
        $totalRecords = (int) $courses->sum('total');
        $totalAttended = (int) $courses->sum('attended');

        return Inertia::render('Student/Attendance', [
            'courses' => $courses,
            'overall' => [
                'total' => $totalRecords,
                'attended' => $totalAttended,
                'absent' => $totalRecords - $totalAttended,
                'rate' => $totalRecords > 0 ? (int) round($totalAttended / $totalRecords * 100) : null,
            ],
        ]);
    }

    public function transcript(): Response
    {
        return Inertia::render('Student/Transcript', $this->transcript->build($this->user()));
    }

    public function exams(): Response
    {
        return Inertia::render('Student/Exams', $this->exams->forStudent($this->user()));
    }

    public function calendar(): Response
    {
        $user = $this->user();

        return Inertia::render('Student/Calendar', [
            ...$this->calendar->build($user),
            'courses' => $user->enrolledCourses()->wherePivotNotIn('status', ['pending'])->get(['courses.id', 'courses.code', 'courses.name']),
            'priorities' => array_map(
                fn (TaskPriority $priority) => ['value' => $priority->value, 'label' => $priority->getLabel()],
                TaskPriority::cases(),
            ),
        ]);
    }

    public function roadmap(): Response
    {
        $user = $this->user();

        return Inertia::render('Student/Roadmap', [
            'roadmap' => $this->buildRoadmap($user),
            'student' => $this->studentProfile($user),
        ]);
    }

    public function profile(): Response
    {
        return Inertia::render('Student/Profile', [
            'user' => $this->user()->load('roles', 'department'),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $this->user()->update($request->validated());

        return back()->with('success', 'Profile updated.');
    }

    public function settings(): Response
    {
        $aiSettings = app(AiSettings::class);

        return Inertia::render('Student/Settings', [
            'preferences' => $this->user()->preferences ?? $this->defaultPreferences(),
            // Languages an admin has enabled for the AI — the same source the chat uses.
            'supportedLanguages' => $aiSettings->supportedLanguages(),
        ]);
    }

    public function updateSettings(UpdateSettingsRequest $request): RedirectResponse
    {
        $this->user()->update(['preferences' => $request->validated()]);

        return back()->with('success', 'Settings saved.');
    }

    public function downloadDocument(Document $document)
    {
        Gate::authorize('download', $document);

        // Documents live on the disk owned by DocumentStorageService (the public
        // disk), not the local disk — resolve it through the service so this stays
        // correct if the storage location ever changes.
        $disk = Storage::disk($this->documentStorage->disk());
        abort_unless($document->file_path && $disk->exists($document->file_path), 404);

        $this->documents->recordDownload($document);

        return $disk->download(
            $document->file_path,
            $document->original_filename ?? $document->title,
            ['Content-Type' => DocumentStorageService::contentType($document->file_path)],
        );
    }

    public function previewDocument(Document $document)
    {
        Gate::authorize('view', $document);

        $disk = Storage::disk($this->documentStorage->disk());
        abort_unless($document->file_path && $disk->exists($document->file_path), 404);

        $this->documents->recordView($document);

        // Serve inline so PDFs/text render in the browser instead of downloading.
        return $disk->response(
            $document->file_path,
            $document->original_filename ?? $document->title,
            ['Content-Type' => DocumentStorageService::contentType($document->file_path)],
            'inline',
        );
    }

    public function downloadMaterial(CourseMaterial $material)
    {
        $user = $this->user();

        // Only students enrolled in the material's section may download it.
        abort_unless(
            $material->file_path !== null
                && $user->enrolledSectionIds()->contains($material->section_id),
            404,
        );

        $material->increment('downloads');

        return Storage::disk('local')->download(
            $material->file_path,
            $material->original_filename ?? $material->title,
            ['Content-Type' => DocumentStorageService::contentType($material->file_path)],
        );
    }

    /**
     * Mark a course material complete/incomplete for the current student.
     */
    public function toggleMaterialCompletion(Request $request, CourseMaterial $material): JsonResponse
    {
        $user = $this->user();

        abort_unless(
            $user->enrolledSectionIds()->contains($material->section_id),
            403,
        );

        $completed = $request->boolean('completed');

        if ($completed) {
            $user->completedMaterials()->syncWithoutDetaching([
                $material->id => ['completed_at' => now()],
            ]);
        } else {
            $user->completedMaterials()->detach($material->id);
        }

        return response()->json(['completed' => $completed]);
    }

    private function user(): User
    {
        return request()->user();
    }

    /**
     * @return array<string, mixed>
     */
    private function studentProfile(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'student_id' => $user->student_id,
            'department' => $user->department?->name,
            'semester' => $user->semester,
            'avatar' => $user->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=6366f1&color=fff&size=128',
            'cgpa' => $this->cgpa($user),
        ];
    }

    /**
     * The student's most recent (non-archived) chat sessions, for the
     * "Recent Conversations" card. Each entry deep-links into the chat page.
     *
     * @return array<int, array<string, mixed>>
     */
    private function recentChats(User $user): array
    {
        return $user->chatSessions()
            ->where('is_archived', false)
            ->whereNotNull('last_message_at')
            ->orderByDesc('last_message_at')
            ->limit(5)
            ->get()
            ->map(fn (ChatSession $session) => [
                'id' => $session->id,
                'question' => $session->title ?? 'Untitled conversation',
                'mode' => $session->mode->value,
                'time' => optional($session->last_message_at)->diffForHumans(),
            ])->all();
    }

    /**
     * The student's overall attendance rate across enrolled courses, or null
     * when no attendance has been recorded yet.
     */
    private function attendanceRate(User $user): ?int
    {
        $summary = $this->attendance->studentSummary($user);
        $total = (int) $summary->sum('total');
        $attended = (int) $summary->sum('attended');

        return $total > 0 ? (int) round($attended / $total * 100) : null;
    }

    /**
     * Consecutive days (ending today, or yesterday with a one-day grace) on
     * which the student has any recorded activity.
     */
    private function studyStreak(User $user): int
    {
        // A streak is consecutive days ending today, so only the last year of
        // activity can ever contribute — scoping the window keeps this bounded
        // instead of loading the user's entire (unbounded) activity history.
        $days = ActivityLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(366)->startOfDay())
            ->orderByDesc('created_at')
            ->get(['created_at'])
            ->map(fn (ActivityLog $log) => $log->created_at->toDateString())
            ->unique()
            ->flip();

        if ($days->isEmpty()) {
            return 0;
        }

        $cursor = now()->startOfDay();
        if (! $days->has($cursor->toDateString()) && $days->has($cursor->copy()->subDay()->toDateString())) {
            $cursor = $cursor->subDay();
        }

        $streak = 0;
        while ($days->has($cursor->toDateString())) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function upcomingDeadlines(User $user): array
    {
        return Assignment::whereIn('section_id', $user->enrolledSectionIds())
            ->whereNotNull('due_at')
            ->where('due_at', '>=', now())
            ->with('course')
            ->orderBy('due_at')
            ->limit(5)
            ->get()
            ->map(fn (Assignment $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'course' => $a->course?->code,
                'due_date' => $a->due_at?->toDateString(),
                'days_left' => (int) now()->diffInDays($a->due_at, false),
                'priority' => now()->diffInDays($a->due_at, false) <= 3 ? 'high' : (now()->diffInDays($a->due_at, false) <= 7 ? 'medium' : 'low'),
            ])->all();
    }

    /**
     * Build a semester-by-semester roadmap from enrolled courses.
     *
     * @return array<string, mixed>
     */
    private function buildRoadmap(User $user): array
    {
        $courses = $user->enrolledCourses()->wherePivotNotIn('status', ['pending'])->with('faculty')->get();

        // Each enrolment points to the section the student attends; show that
        // section's instructor and only that section's assignments.
        $sections = Section::with('faculty')
            ->findMany($courses->pluck('pivot.section_id')->filter()->unique())
            ->keyBy('id');

        // Assignment deadlines per course, with this student's submission state.
        $submittedAssignmentIds = AssignmentSubmission::where('user_id', $user->id)
            ->pluck('assignment_id')
            ->all();

        $assignmentsByCourse = Assignment::whereIn('section_id', $sections->keys())
            ->whereNotNull('due_at')
            ->orderBy('due_at')
            ->get()
            ->groupBy('course_id');

        $bysemester = $courses->groupBy('semester')
            ->map(function ($group, $semester) use ($assignmentsByCourse, $submittedAssignmentIds, $sections) {
                return [
                    'semester' => $semester,
                    'title' => "Semester {$semester}",
                    'credits' => $group->sum('credits'),
                    'gpa' => $this->gpaFor($group),
                    'modules' => $group->map(fn ($c) => [
                        'id' => $c->id,
                        'title' => $c->name,
                        'code' => $c->code,
                        'instructor' => $sections->get($c->pivot->section_id)?->faculty?->name ?? $c->faculty?->name,
                        'status' => $c->pivot->status === 'completed' ? 'completed' : 'in-progress',
                        'progress' => (int) $c->pivot->progress,
                        'grade' => $c->pivot->grade,
                        'credits' => $c->credits,
                        'assignments' => ($assignmentsByCourse->get($c->id) ?? collect())
                            ->map(fn (Assignment $a) => [
                                'id' => $a->id,
                                'title' => $a->title,
                                'due' => $a->due_at->toDateString(),
                                'status' => in_array($a->id, $submittedAssignmentIds, strict: true) ? 'completed' : 'pending',
                            ])->values(),
                    ])->values(),
                ];
            })->values();

        return [
            'semesters' => $bysemester,
            'overallProgress' => (int) round($courses->avg('pivot.progress') ?? 0),
            'completedCredits' => $courses->where('pivot.status', 'completed')->sum('credits'),
            'totalCredits' => max($courses->sum('credits'), 1),
            'cgpa' => $this->cgpa($user),
        ];
    }

    /**
     * Grade-letter → grade-point scale (4.0 system).
     *
     * @var array<string, float>
     */
    private const GRADE_POINTS = [
        'A' => 4.0, 'A-' => 3.7,
        'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
        'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7,
        'D+' => 1.3, 'D' => 1.0,
        'F' => 0.0,
    ];

    /**
     * Credit-weighted GPA for a set of enrolled courses, or null when ungraded.
     *
     * @param  Collection<int, Course>  $courses
     */
    private function gpaFor($courses): ?float
    {
        $gradedCredits = 0.0;
        $weightedPoints = 0.0;

        foreach ($courses as $course) {
            $points = self::GRADE_POINTS[$course->pivot->grade] ?? null;
            if ($points === null) {
                continue;
            }
            $gradedCredits += (float) $course->credits;
            $weightedPoints += $points * (float) $course->credits;
        }

        return $gradedCredits > 0 ? round($weightedPoints / $gradedCredits, 2) : null;
    }

    private function cgpa(User $user): float
    {
        $grades = $user->enrolledCourses()->wherePivotNotIn('status', ['pending'])->get()
            ->map(fn ($c) => self::GRADE_POINTS[$c->pivot->grade] ?? null)
            ->filter();

        return $grades->isEmpty() ? 0.0 : round($grades->avg(), 2);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function quickActions(): array
    {
        return [
            ['label' => 'Start AI Chat', 'description' => 'Get instant help', 'icon' => 'SparklesIcon', 'route' => 'chat', 'gradient' => 'from-purple-500 to-indigo-600'],
            ['label' => 'View Roadmap', 'description' => 'Track your progress', 'icon' => 'FireIcon', 'route' => 'roadmap', 'gradient' => 'from-orange-500 to-red-600'],
            ['label' => 'Browse Documents', 'description' => 'Access course materials', 'icon' => 'DocumentTextIcon', 'route' => 'documents', 'gradient' => 'from-blue-500 to-cyan-600'],
            ['label' => 'Saved Answers', 'description' => 'Your bookmarks', 'icon' => 'BookmarkIcon', 'route' => 'saved', 'gradient' => 'from-green-500 to-emerald-600'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultPreferences(): array
    {
        return ['theme' => 'light', 'notifications' => true, 'language' => 'en'];
    }
}
