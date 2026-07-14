<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\Chat\Document\Services\DocumentService;
use App\Domain\User\Models\User;
use App\Domain\User\Services\UserManagementService;
use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentResource;
use App\Models\ActivityLog;
use App\Models\AttendanceRecord;
use App\Models\Document;
use App\Models\Term;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userService,
        private readonly DocumentService $documents,
    ) {}

    public function index(): Response
    {
        $stats = $this->userService->getUserStatistics();
        $docStats = $this->documents->statistics();

        return Inertia::render('Admin/Dashboard', [
            'systemStats' => [
                ['label' => 'Total Users', 'value' => $stats['total_users'], 'icon' => 'UsersIcon', 'gradient' => 'from-blue-500 to-indigo-600', 'description' => "{$stats['active_users']} active"],
                ['label' => 'Online Now', 'value' => $stats['online_users'], 'icon' => 'SignalIcon', 'gradient' => 'from-green-500 to-emerald-600', 'description' => 'Last 15 minutes'],
                ['label' => 'Documents', 'value' => $docStats['approved'], 'icon' => 'DocumentTextIcon', 'gradient' => 'from-purple-500 to-pink-600', 'description' => "{$docStats['pending']} pending"],
                ['label' => 'New This Week', 'value' => $stats['new_registrations_this_week'], 'icon' => 'UserPlusIcon', 'gradient' => 'from-orange-500 to-red-600', 'description' => 'Registrations'],
            ],
            'statistics' => $stats,
            'pendingDocuments' => DocumentResource::collection(
                Document::pending()->with(['uploader', 'departments'])->latest()->limit(5)->get()
            ),
            'recentActivities' => $this->recentActivities(),
            'systemHealth' => $this->systemHealth($docStats),
            'studentInsights' => $this->studentInsights($stats),
        ]);
    }

    /**
     * Real, queryable student-body metrics for the dashboard insights card.
     *
     * @param  array<string, int>  $stats
     * @return array<string, mixed>
     */
    private function studentInsights(array $stats): array
    {
        $currentTerm = Term::currentTerm();

        $enrolledThisTerm = $currentTerm
            ? User::whereHas(
                'enrolledCourses',
                fn ($query) => $query->where('course_user.term_id', $currentTerm->id)
            )->count()
            : 0;

        $totalAttendance = AttendanceRecord::count();
        $attended = $totalAttendance > 0
            ? AttendanceRecord::where('status', '!=', AttendanceStatus::ABSENT->value)->count()
            : 0;
        $attendanceRate = $totalAttendance > 0
            ? round($attended / $totalAttendance * 100, 1)
            : 0.0;

        return [
            'totalStudents' => $stats['total_students'],
            'enrolledThisTerm' => $enrolledThisTerm,
            'attendanceRate' => $attendanceRate,
            'totalFaculty' => $stats['total_faculty'],
            'currentTerm' => $currentTerm?->name,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentActivities(): array
    {
        return ActivityLog::with('user')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'type' => explode('.', $log->action)[0],
                'description' => $log->description ?? $log->action,
                'user' => $log->user?->name,
                'timestamp' => $log->created_at?->diffForHumans(),
            ])->all();
    }

    /**
     * @param  array<string, int>  $docStats
     * @return array<string, mixed>
     */
    private function systemHealth(array $docStats): array
    {
        $databaseUp = $this->databaseUp();

        // Mirror the System Monitor's disk threshold (>= 85% = degraded) so the
        // dashboard banner and the monitor page never disagree.
        $diskFree = @disk_free_space(base_path()) ?: 0;
        $diskTotal = @disk_total_space(base_path()) ?: 1;
        $diskUsedPercent = round((($diskTotal - $diskFree) / $diskTotal) * 100, 1);

        $status = match (true) {
            ! $databaseUp => 'down',
            $diskUsedPercent >= 85 => 'degraded',
            default => 'healthy',
        };

        return [
            'status' => $status,
            'database_status' => $databaseUp ? 'healthy' : 'down',
            'disk_used_percent' => $diskUsedPercent,
            'ai_provider' => app(AIProviderInterface::class)->name(),
            'documents_indexed' => $docStats['approved'],
            'documents_pending' => $docStats['pending'],
        ];
    }

    private function databaseUp(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
