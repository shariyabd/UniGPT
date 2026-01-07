<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domain\User\Services\UserManagementService;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __construct(
        private UserManagementService $userService
    ) {}

    /**
     * Display the admin dashboard
     */
    public function index(): Response
    {
        $user = auth()->user()->load('roles.permissions');

        return Inertia::render('Admin/Dashboard', [
            'user' => $user,
            'permissions' => $user->roles->flatMap->permissions->pluck('slug')->unique()->values(),
            'statistics' => $this->userService->getUserStatistics(),
            'recent_activities' => $this->getRecentActivities(),
            'system_health' => $this->getSystemHealth(),
        ]);

    }

    /**
     * Display user management
     */
    public function userManagement(): Response
    {
        return Inertia::render('Admin/UserManagement', [
            'users' => $this->userService->getPaginatedUsers(20),
            'roles' => \App\Models\Role::active()->get(),
            'user_statistics' => $this->userService->getUserStatistics(),
        ]);
    }

    // Keep other methods as they are for now...
    private function getRecentActivities(): array
    {
        return [
            [
                'type' => 'user_registration',
                'description' => 'New faculty member registered: Dr. Jane Smith',
                'timestamp' => now()->subMinutes(30),
            ],
            [
                'type' => 'document_upload',
                'description' => '15 new documents uploaded to library',
                'timestamp' => now()->subHours(2),
            ],
            [
                'type' => 'system_update',
                'description' => 'AI models updated with latest configurations',
                'timestamp' => now()->subHours(4),
            ],
        ];
    }

    private function getSystemHealth(): array
    {
        return [
            'database_status' => 'healthy',
            'ai_service_status' => 'healthy',
            'storage_usage' => 65,
            'memory_usage' => 45,
            'cpu_usage' => 32,
        ];
    }
}
