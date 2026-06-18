<?php

namespace App\Http\Controllers\Faculty;

use App\Domain\Academic\Services\CourseService;
use App\Domain\Academic\Services\ExamService;
use App\Domain\User\Models\User;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AssignmentSubmission;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class FacultyDashboardController extends Controller
{
    public function __construct(
        private readonly CourseService $courses,
        private readonly ExamService $exams,
    ) {}

    public function exams(): Response
    {
        return Inertia::render('Faculty/Exams', $this->exams->forFaculty(request()->user()));
    }

    public function index(): Response
    {
        $user = request()->user();
        $courses = $this->courses->facultyCourses($user);

        $totalStudents = $courses->sum('students');
        $pendingGrading = $this->pendingGradingCount($user);

        return Inertia::render('Faculty/Dashboard', [
            'faculty' => [
                'name' => $user->name,
                'email' => $user->email,
                'employeeId' => $user->employee_id,
                'department' => $user->department?->name,
                'avatar' => 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=10b981&color=fff&size=128',
            ],
            'courseStats' => [
                ['label' => 'Active Courses', 'value' => $courses->count(), 'icon' => 'BookOpenIcon', 'gradient' => 'from-blue-500 to-indigo-600'],
                ['label' => 'Total Students', 'value' => $totalStudents, 'icon' => 'UsersIcon', 'gradient' => 'from-green-500 to-emerald-600'],
                ['label' => 'Pending Grading', 'value' => $pendingGrading, 'icon' => 'DocumentTextIcon', 'gradient' => 'from-orange-500 to-red-600'],
                ['label' => 'AI Queries', 'value' => $user->chatSessions()->count(), 'icon' => 'SparklesIcon', 'gradient' => 'from-purple-500 to-pink-600'],
            ],
            'activeCourses' => $courses,
            'recentActivities' => $this->recentActivities($user),
        ]);
    }

    private function pendingGradingCount(User $faculty): int
    {
        return AssignmentSubmission::whereNull('grade')
            ->whereHas('assignment', fn (Builder $q) => $q->whereIn('section_id', $faculty->teachingSectionIds()))
            ->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentActivities(User $faculty): array
    {
        return ActivityLog::where('user_id', $faculty->id)
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'type' => explode('.', $log->action)[0],
                'description' => $log->description ?? $log->action,
                'time' => $log->created_at?->diffForHumans(),
            ])->all();
    }
}
