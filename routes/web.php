<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\DocumentController as AdminDocumentController;
use App\Http\Controllers\Admin\MonitorController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\AuthenticationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Faculty\AIAssistantController as FacultyAIAssistantController;
use App\Http\Controllers\Faculty\CourseController as FacultyCourseController;
use App\Http\Controllers\Faculty\FacultyDashboardController;
use App\Http\Controllers\Faculty\GradingController as FacultyGradingController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\Student\ChatController;
use App\Http\Controllers\Student\SavedAnswerController;
use App\Http\Controllers\Student\StudentDashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// Authentication Routes
Route::middleware('guest')->group(function () {});
Route::get('/login', [AuthenticationController::class, 'create'])->name('login');
Route::post('/login', [AuthenticationController::class, 'store'])->name('login.store');
Route::post('/register', [AuthenticationController::class, 'register'])->name('register');
Route::post('/demo-login', [AuthenticationController::class, 'demoLogin'])->name('demo.login');

// Password Reset Routes
Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');

Route::post('/logout', [AuthenticationController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::middleware('role:student')->prefix('')->name('')->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

        // AI chat (page is Inertia; send/load are JSON for a live feel)
        Route::get('/chat', [ChatController::class, 'index'])->middleware('permission:use_ai_chat')->name('chat');
        Route::post('/chat', [ChatController::class, 'store'])->middleware('permission:use_ai_chat')->name('chat.send');
        Route::get('/chat/sessions/{session}', [ChatController::class, 'show'])->middleware('permission:view_chat_history')->name('chat.session');
        Route::delete('/chat/sessions/{session}', [ChatController::class, 'destroy'])->middleware('permission:delete_chat')->name('chat.session.destroy');

        // Saved answers
        Route::get('/saved', [SavedAnswerController::class, 'index'])->middleware('permission:view_chat_history')->name('saved');
        Route::post('/saved', [SavedAnswerController::class, 'store'])->middleware('permission:use_ai_chat')->name('saved.store');
        Route::patch('/saved/{savedAnswer}', [SavedAnswerController::class, 'update'])->middleware('permission:use_ai_chat')->name('saved.update');
        Route::delete('/saved/{savedAnswer}', [SavedAnswerController::class, 'destroy'])->middleware('permission:delete_chat')->name('saved.destroy');
        Route::get('/roadmap', [StudentDashboardController::class, 'roadmap'])->middleware('permission:view_courses')->name('roadmap');
        Route::get('/documents', [StudentDashboardController::class, 'documents'])->middleware('permission:view_documents')->name('documents');
        Route::get('/documents/{document}/download', [StudentDashboardController::class, 'downloadDocument'])->middleware('permission:download_document')->name('documents.download');
        Route::get('/materials', [StudentDashboardController::class, 'materials'])->middleware('permission:view_courses')->name('materials');

        // Self-service account pages (no extra permission beyond the student role)
        Route::get('/profile', [StudentDashboardController::class, 'profile'])->name('profile');
        Route::patch('/profile', [StudentDashboardController::class, 'updateProfile'])->name('profile.update');
        Route::get('/settings', [StudentDashboardController::class, 'settings'])->name('settings');
        Route::patch('/settings', [StudentDashboardController::class, 'updateSettings'])->name('settings.update');
    });

    Route::middleware('role:faculty')->prefix('faculty')->name('faculty.')->group(function () {
        Route::get('/dashboard', [FacultyDashboardController::class, 'index'])->name('dashboard');

        // Courses
        Route::get('/courses', [FacultyCourseController::class, 'index'])->middleware('permission:view_courses')->name('courses');
        Route::get('/courses/{course}', [FacultyCourseController::class, 'show'])->middleware('permission:view_courses')->name('courses.show');

        // AI teaching assistant
        Route::get('/ai-assistant', [FacultyAIAssistantController::class, 'index'])->middleware('permission:use_ai_chat')->name('ai-assistant');
        Route::post('/ai-assistant/chat', [FacultyAIAssistantController::class, 'chat'])->middleware('permission:use_ai_chat')->name('ai-assistant.chat');
        Route::post('/ai-assistant/quiz', [FacultyAIAssistantController::class, 'generateQuiz'])->middleware('permission:use_ai_chat')->name('ai-assistant.quiz');
        Route::post('/ai-assistant/assignment', [FacultyAIAssistantController::class, 'generateAssignment'])->middleware('permission:create_assignment')->name('ai-assistant.assignment');

        // Grading
        Route::get('/grading', [FacultyGradingController::class, 'index'])->middleware('permission:grade_assignment')->name('grading');
        Route::get('/courses/{course}/grading', [FacultyGradingController::class, 'index'])->middleware('permission:grade_assignment')->name('course.grading');
        Route::post('/submissions/{submission}/grade', [FacultyGradingController::class, 'grade'])->middleware('permission:grade_assignment')->name('submissions.grade');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Document knowledge base + approval workflow
        Route::get('/documents/upload', [AdminDocumentController::class, 'upload'])->middleware('permission:upload_document')->name('documents.upload');
        Route::post('/documents', [AdminDocumentController::class, 'store'])->middleware('permission:upload_document')->name('documents.store');
        Route::get('/documents', [AdminDocumentController::class, 'library'])->middleware('permission:view_documents')->name('documents');
        Route::get('/documents/{document}/download', [AdminDocumentController::class, 'download'])->middleware('permission:download_document')->name('documents.download');
        Route::delete('/documents/{document}', [AdminDocumentController::class, 'destroy'])->middleware('permission:delete_document')->name('documents.destroy');

        Route::get('/approvals', [AdminDocumentController::class, 'approvals'])->middleware('permission:approve_document')->name('approvals');
        Route::post('/documents/{document}/approve', [AdminDocumentController::class, 'approve'])->middleware('permission:approve_document')->name('documents.approve');
        Route::post('/documents/{document}/reject', [AdminDocumentController::class, 'reject'])->middleware('permission:approve_document')->name('documents.reject');
        Route::post('/documents/{document}/request-changes', [AdminDocumentController::class, 'requestChanges'])->middleware('permission:approve_document')->name('documents.request-changes');
        Route::post('/documents/{document}/comment', [AdminDocumentController::class, 'comment'])->middleware('permission:approve_document')->name('documents.comment');

        Route::get('/analytics', [AnalyticsController::class, 'index'])->middleware('permission:view_all_analytics')->name('analytics');

        // User management
        Route::get('/users', [UserManagementController::class, 'index'])->middleware('permission:view_users')->name('users');
        Route::post('/users', [UserManagementController::class, 'store'])->middleware('permission:create_user')->name('users.store');
        Route::patch('/users/{user}', [UserManagementController::class, 'update'])->middleware('permission:update_user')->name('users.update');
        Route::patch('/users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->middleware('permission:update_user')->name('users.toggle-active');
        Route::patch('/users/{user}/role', [UserManagementController::class, 'assignRole'])->middleware('permission:manage_user_roles')->name('users.role');

        // Role-permission matrix editor
        Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:manage_permissions')->name('roles');
        Route::patch('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->middleware('permission:manage_permissions')->name('roles.permissions');

        Route::get('/monitor', [MonitorController::class, 'index'])->middleware('permission:manage_system')->name('monitor');

        // AI settings
        Route::get('/settings', [SettingsController::class, 'index'])->middleware('permission:configure_ai')->name('settings');
        Route::patch('/settings', [SettingsController::class, 'update'])->middleware('permission:configure_ai')->name('settings.update');
        Route::post('/settings/test', [SettingsController::class, 'test'])->middleware('permission:configure_ai')->name('settings.test');
    });
});

Route::get('/terms', [LegalController::class, 'terms'])->name('terms');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('privacy');
