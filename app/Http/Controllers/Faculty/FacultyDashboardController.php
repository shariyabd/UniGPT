<?php
// app/Http/Controllers/Faculty/DashboardController.php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FacultyDashboardController extends Controller
{
    /**
     * Display the faculty dashboard
     */
    public function index(): Response
    {
        $user = auth()->user()->load('roles.permissions');

        return Inertia::render('Faculty/Dashboard', [
            'user' => $user,
            'permissions' => $user->roles->flatMap->permissions->pluck('slug')->unique()->values(),
            'statistics' => $this->getFacultyStatistics(),
            'recent_activities' => $this->getRecentActivities(),
            'upcoming_tasks' => $this->getUpcomingTasks(),
        ]);
    }

    /**
     * Display AI assistant
     */
    public function aiAssistant(): Response
    {
        return Inertia::render('Faculty/AIAssistant');
    }

    /**
     * Display grading interface
     */
    public function grading(?string $courseId = null): Response
    {
        return Inertia::render('Faculty/Grading', [
            'courseId' => $courseId,
            'pending_assignments' => $this->getPendingAssignments($courseId),
            'courses' => $this->getFacultyCourses(),
        ]);
    }

    /**
     * Get faculty statistics
     */
    private function getFacultyStatistics(): array
    {
        return [
            'total_courses' => 3,
            'total_students' => 145,
            'pending_assignments' => 28,
            'completed_gradings' => 67,
            'ai_assistant_usage' => 34,
        ];
    }

    /**
     * Get recent activities
     */
    private function getRecentActivities(): array
    {
        return [
            [
                'type' => 'grading',
                'description' => 'Graded 5 assignments for Database Systems',
                'timestamp' => now()->subHours(1),
            ],
            [
                'type' => 'ai_assistant',
                'description' => 'Generated quiz for Machine Learning course',
                'timestamp' => now()->subHours(3),
            ],
            [
                'type' => 'course',
                'description' => 'Updated course materials for Software Engineering',
                'timestamp' => now()->subHours(6),
            ],
        ];
    }

    /**
     * Get upcoming tasks
     */
    private function getUpcomingTasks(): array
    {
        return [
            [
                'title' => 'Grade Database Systems Assignment 3',
                'due_date' => now()->addDays(2),
                'priority' => 'high',
            ],
            [
                'title' => 'Prepare lecture for ML Neural Networks',
                'due_date' => now()->addDays(3),
                'priority' => 'medium',
            ],
        ];
    }

    /**
     * Get pending assignments
     */
    private function getPendingAssignments(?string $courseId = null): array
    {
        // Placeholder for pending assignments logic
        return [];
    }

    /**
     * Get faculty courses
     */
    private function getFacultyCourses(): array
    {
        return [
            ['id' => 1, 'name' => 'Database Systems', 'code' => 'CS301'],
            ['id' => 2, 'name' => 'Machine Learning', 'code' => 'CS401'],
            ['id' => 3, 'name' => 'Software Engineering', 'code' => 'CS302'],
        ];
    }
}
