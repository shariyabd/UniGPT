<?php
// app/Http/Controllers/Student/DashboardController.php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentDashboardController extends Controller
{
    /**
     * Display the student dashboard
     */
    public function index(): Response
    {
        $user = auth()->user()->load('roles.permissions');

        return Inertia::render('Student/Dashboard', [
            // User data
            'user' => auth()->user()->load('roles.permissions'),
            'permissions' => auth()->user()->roles->flatMap->permissions->pluck('slug')->unique()->values(),

            // Student-specific data
            'student' => [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'student_id' => auth()->user()->student_id ?? 'CS2024001',
                'department' => auth()->user()->department ?? 'Computer Science Engineering',
                'year' => auth()->user()->academic_year ?? '3rd Year',
                'semester' => auth()->user()->semester ?? '5th Semester',
                'program' => auth()->user()->program ?? 'B.Tech',
                'avatar' => auth()->user()->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=6366f1&color=fff&size=128'
            ],

            // Dashboard statistics
            'stats' => [
                [
                    'label' => 'Attendance',
                    'value' => '87%',
                    'change' => '+5%',
                    'trend' => 'up',
                    'icon' => 'AcademicCapIcon',
                    'color' => 'green'
                ],
                [
                    'label' => 'GPA',
                    'value' => '8.7',
                    'change' => '+0.3',
                    'trend' => 'up',
                    'icon' => 'ChartBarIcon',
                    'color' => 'blue'
                ],
                [
                    'label' => 'Assignments',
                    'value' => '12/15',
                    'change' => '+2',
                    'trend' => 'up',
                    'icon' => 'DocumentTextIcon',
                    'color' => 'purple'
                ],
                [
                    'label' => 'Next Exam',
                    'value' => '5 days',
                    'change' => null,
                    'trend' => 'neutral',
                    'icon' => 'ClockIcon',
                    'color' => 'orange'
                ]
            ],

            // Recent activities
            'recentActivities' => [
                [
                    'id' => 1,
                    'type' => 'chat',
                    'title' => 'Asked about Data Structures',
                    'description' => 'Binary trees implementation',
                    'time' => '2 hours ago',
                    'icon' => 'ChatBubbleLeftRightIcon'
                ],
                [
                    'id' => 2,
                    'type' => 'assignment',
                    'title' => 'Submitted ML Assignment',
                    'description' => 'Linear regression analysis',
                    'time' => '1 day ago',
                    'icon' => 'DocumentTextIcon'
                ],
                [
                    'id' => 3,
                    'type' => 'bookmark',
                    'title' => 'Saved Algorithm Notes',
                    'description' => 'Sorting algorithms comparison',
                    'time' => '3 days ago',
                    'icon' => 'BookmarkIcon'
                ]
            ],

            // Upcoming deadlines
            'upcomingDeadlines' => [
                [
                    'id' => 1,
                    'title' => 'Database Project',
                    'course' => 'DBMS Lab',
                    'due_date' => '2024-01-15',
                    'days_left' => 3,
                    'priority' => 'high'
                ],
                [
                    'id' => 2,
                    'title' => 'AI Assignment',
                    'course' => 'Artificial Intelligence',
                    'due_date' => '2024-01-18',
                    'days_left' => 6,
                    'priority' => 'medium'
                ],
                [
                    'id' => 3,
                    'title' => 'Research Paper',
                    'course' => 'Software Engineering',
                    'due_date' => '2024-01-25',
                    'days_left' => 13,
                    'priority' => 'low'
                ]
            ],

            // Quick actions
            'quickActions' => [
                [
                    'label' => 'Start AI Chat',
                    'description' => 'Get instant help',
                    'icon' => 'SparklesIcon',
                    'route' => 'chat',
                    'gradient' => 'from-purple-500 to-indigo-600'
                ],
                [
                    'label' => 'View Roadmap',
                    'description' => 'Track your progress',
                    'icon' => 'FireIcon',
                    'route' => 'roadmap',
                    'gradient' => 'from-orange-500 to-red-600'
                ],
                [
                    'label' => 'Browse Documents',
                    'description' => 'Access course materials',
                    'icon' => 'DocumentTextIcon',
                    'route' => 'documents',
                    'gradient' => 'from-blue-500 to-cyan-600'
                ],
                [
                    'label' => 'Check Deadlines',
                    'description' => 'Stay on track',
                    'icon' => 'CalendarIcon',
                    'route' => 'deadlines',
                    'gradient' => 'from-green-500 to-emerald-600'
                ]
            ]
        ]);
    }

    /**
     * Display chat interface
     */
    public function chat(): Response
    {
        return Inertia::render('Student/Chat');
    }

    /**
     * Display saved answers
     */
    public function savedAnswers(): Response
    {
        return Inertia::render('Student/SavedAnswers', [
            'saved_count' => $this->getSavedAnswersCount(),
        ]);
    }

    /**
     * Display academic roadmap
     */
    public function roadmap(): Response
    {
        return Inertia::render('Student/Roadmap', [
            'progress' => $this->getAcademicProgress(),
        ]);
    }

    /**
     * Display documents
     */
    public function documents(): Response
    {
        return Inertia::render('Student/Documents', [
            'documents' => $this->getAvailableDocuments(),
        ]);
    }

    /**
     * Display course materials
     */
    public function materials(): Response
    {
        return Inertia::render('Student/Materials', [
            'materials' => $this->getCourseMaterials(),
        ]);
    }

    /**
     * Display user profile
     */
    public function profile(): Response
    {
        return Inertia::render('Student/Profile', [
            'user' => auth()->user()->load('roles'),
        ]);
    }

    /**
     * Display settings
     */
    public function settings(): Response
    {
        return Inertia::render('Student/Settings', [
            'preferences' => $this->getUserPreferences(),
        ]);
    }

    /**
     * Get student statistics
     */
    private function getStudentStatistics(): array
    {
        // Placeholder for actual statistics logic
        return [
            'courses_enrolled' => 5,
            'assignments_completed' => 23,
            'total_chat_sessions' => 45,
            'documents_downloaded' => 67,
            'current_semester' => auth()->user()->semester ?? 'N/A',
        ];
    }

    /**
     * Get recent activities
     */
    private function getRecentActivities(): array
    {
        // Placeholder for actual activities logic
        return [
            [
                'type' => 'chat',
                'description' => 'Started AI chat session about Database Normalization',
                'timestamp' => now()->subHours(2),
            ],
            [
                'type' => 'document',
                'description' => 'Downloaded lecture notes for Machine Learning',
                'timestamp' => now()->subHours(5),
            ],
            [
                'type' => 'assignment',
                'description' => 'Submitted assignment for Software Engineering',
                'timestamp' => now()->subDay(),
            ],
        ];
    }

    /**
     * Get saved answers count
     */
    private function getSavedAnswersCount(): int
    {
        // Placeholder - implement actual logic later
        return 12;
    }

    /**
     * Get academic progress
     */
    private function getAcademicProgress(): array
    {
        // Placeholder for roadmap logic
        return [
            'current_semester' => auth()->user()->semester ?? '5th Semester',
            'completed_courses' => 18,
            'remaining_courses' => 7,
            'gpa' => 8.5,
        ];
    }

    /**
     * Get available documents
     */
    private function getAvailableDocuments(): array
    {
        // Placeholder for documents logic
        return [];
    }

    /**
     * Get course materials
     */
    private function getCourseMaterials(): array
    {
        // Placeholder for materials logic
        return [];
    }

    /**
     * Get user preferences
     */
    private function getUserPreferences(): array
    {
        // Placeholder for preferences logic
        return [
            'theme' => 'system',
            'notifications' => true,
            'language' => 'en',
        ];
    }
}
