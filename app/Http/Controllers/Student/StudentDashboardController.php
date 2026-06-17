<?php

namespace App\Http\Controllers\Student;

use App\Domain\Academic\Services\AttendanceService;
use App\Domain\Academic\Services\CalendarService;
use App\Domain\Academic\Services\CourseService;
use App\Domain\Academic\Services\ExamService;
use App\Domain\Academic\Services\TranscriptService;
use App\Domain\Chat\Document\Services\DocumentService;
use App\Domain\User\Models\User;
use App\Enums\TaskPriority;
use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentResource;
use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\Document;
use Illuminate\Http\Request;
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
    ) {}

    public function index(): Response
    {
        $user = $this->user();
        $courses = $this->courses->studentCourses($user);
        $deadlines = $this->upcomingDeadlines($user);

        return Inertia::render('Student/Dashboard', [
            'student' => $this->studentProfile($user),
            'stats' => [
                ['label' => 'Courses', 'value' => (string) $courses->count(), 'change' => null, 'trend' => 'neutral', 'icon' => 'AcademicCapIcon', 'color' => 'blue'],
                ['label' => 'CGPA', 'value' => (string) $this->cgpa($user), 'change' => null, 'trend' => 'up', 'icon' => 'ChartBarIcon', 'color' => 'green'],
                ['label' => 'Saved Answers', 'value' => (string) $user->savedAnswers()->count(), 'change' => null, 'trend' => 'neutral', 'icon' => 'BookmarkIcon', 'color' => 'purple'],
                ['label' => 'Chat Sessions', 'value' => (string) $user->chatSessions()->count(), 'change' => null, 'trend' => 'neutral', 'icon' => 'ChatBubbleLeftRightIcon', 'color' => 'orange'],
            ],
            'recentActivities' => $this->recentActivities($user),
            'upcomingDeadlines' => $deadlines,
            'courses' => $courses,
            'quickActions' => $this->quickActions(),
        ]);
    }

    public function documents(Request $request): Response
    {
        $user = $this->user();
        $filters = $request->only(['category', 'search']);

        return Inertia::render('Student/Documents', [
            'documents' => DocumentResource::collection($this->documents->libraryFor($user, $filters)),
            'filters' => $filters,
            'categories' => Document::approved()->visibleTo($user)
                ->select('category')->distinct()->pluck('category'),
        ]);
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
            'courses' => $user->enrolledCourses()->get(['courses.id', 'courses.code', 'courses.name']),
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

    public function updateProfile(\App\Http\Requests\Student\UpdateProfileRequest $request): \Illuminate\Http\RedirectResponse
    {
        $this->user()->update($request->validated());

        return back()->with('success', 'Profile updated.');
    }

    public function settings(): Response
    {
        $aiSettings = app(\App\Domain\Chat\Support\AiSettings::class);

        return Inertia::render('Student/Settings', [
            'preferences' => $this->user()->preferences ?? $this->defaultPreferences(),
            // Languages an admin has enabled for the AI — the same source the chat uses.
            'supportedLanguages' => $aiSettings->supportedLanguages(),
        ]);
    }

    public function updateSettings(\App\Http\Requests\Student\UpdateSettingsRequest $request): \Illuminate\Http\RedirectResponse
    {
        $this->user()->update(['preferences' => $request->validated()]);

        return back()->with('success', 'Settings saved.');
    }

    public function downloadDocument(Document $document)
    {
        \Illuminate\Support\Facades\Gate::authorize('download', $document);

        $this->documents->recordDownload($document);

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_filename ?? $document->title,
        );
    }

    public function downloadMaterial(\App\Models\CourseMaterial $material)
    {
        $user = $this->user();

        // Only enrolled students may download a course's materials.
        abort_unless(
            $material->file_path !== null
                && $user->enrolledCourses()->whereKey($material->course_id)->exists(),
            404,
        );

        $material->increment('downloads');

        return Storage::disk('local')->download(
            $material->file_path,
            $material->original_filename ?? $material->title,
        );
    }

    /**
     * Mark a course material complete/incomplete for the current student.
     */
    public function toggleMaterialCompletion(Request $request, \App\Models\CourseMaterial $material): \Illuminate\Http\JsonResponse
    {
        $user = $this->user();

        abort_unless(
            $user->enrolledCourses()->whereKey($material->course_id)->exists(),
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
     * @return array<int, array<string, mixed>>
     */
    private function recentActivities(User $user): array
    {
        return ActivityLog::where('user_id', $user->id)
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'type' => explode('.', $log->action)[0],
                'title' => $log->description ?? $log->action,
                'description' => '',
                'time' => $log->created_at?->diffForHumans(),
                'icon' => 'ClockIcon',
            ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function upcomingDeadlines(User $user): array
    {
        $courseIds = $user->enrolledCourses()->pluck('courses.id');

        return Assignment::whereIn('course_id', $courseIds)
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
        $courses = $user->enrolledCourses()->with('faculty')->get();
        $bysemester = $courses->groupBy('semester')->map(function ($group, $semester) {
            return [
                'semester' => $semester,
                'title' => "Semester {$semester}",
                'credits' => $group->sum('credits'),
                'modules' => $group->map(fn ($c) => [
                    'id' => $c->id,
                    'title' => $c->name,
                    'code' => $c->code,
                    'status' => $c->pivot->status === 'completed' ? 'completed' : 'in-progress',
                    'progress' => (int) $c->pivot->progress,
                    'grade' => $c->pivot->grade,
                    'credits' => $c->credits,
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

    private function cgpa(User $user): float
    {
        $points = ['A' => 4.0, 'A-' => 3.7, 'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7, 'C+' => 2.3, 'C' => 2.0, 'D' => 1.0, 'F' => 0.0];
        $grades = $user->enrolledCourses()->get()
            ->map(fn ($c) => $points[$c->pivot->grade] ?? null)
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
        return ['theme' => 'system', 'notifications' => true, 'language' => 'en'];
    }
}
