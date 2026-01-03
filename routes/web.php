<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\AuthenticationController;
use App\Http\Controllers\Faculty\FacultyDashboardController;
use App\Http\Controllers\Student\StudentDashboardController;



Route::redirect('/', '/login');

// Authentication Routes
Route::middleware('guest')->group(function () {

});
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
        Route::get('/chat', [StudentDashboardController::class, 'chat'])->name('chat');
        Route::get('/saved', [StudentDashboardController::class, 'savedAnswers'])->name('saved');
        Route::get('/roadmap', [StudentDashboardController::class, 'roadmap'])->name('roadmap');
        Route::get('/documents', [StudentDashboardController::class, 'documents'])->name('documents');
        Route::get('/materials', [StudentDashboardController::class, 'materials'])->name('materials');
        Route::get('/profile', [StudentDashboardController::class, 'profile'])->name('profile');
        Route::get('/settings', [StudentDashboardController::class, 'settings'])->name('settings');
    });



    Route::middleware('role:faculty')->prefix('faculty')->name('faculty.')->group(function () {
        Route::get('/dashboard', [FacultyDashboardController::class, 'index'])->name('dashboard');
        Route::get('/ai-assistant', [FacultyDashboardController::class, 'aiAssistant'])->name('ai-assistant');
        Route::get('/grading', [FacultyDashboardController::class, 'grading'])->name('grading');
        Route::get('/courses/{course}/grading', [FacultyDashboardController::class, 'grading'])->name('course.grading');
    });


    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', function () {
            return Inertia::render('Admin/Dashboard', [
                'user' => auth()->user()->load('roles.permissions'),
                'permissions' => auth()->user()->roles->flatMap->permissions->pluck('slug')->unique()->values(),
                'statistics' => [
                    'total_users' => 353,
                    'active_users' => 34,
                    'total_roles' => \App\Models\Role::count(),
                    'total_permissions' => \App\Models\Permission::count(),
                ]
            ]);
        })->name('admin.dashboard');

        Route::get('/admin/documents/upload', function () {
            return Inertia::render('Admin/DocumentUpload');
        })->name('admin.documents.upload');

        Route::get('/admin/analytics', function () {
            return Inertia::render('Admin/Analytics');
        })->name('admin.analytics');

        Route::get('/admin/approvals', function () {
            return Inertia::render('Admin/Approvals');
        })->name('admin.approvals');

        Route::get('/admin/users', function () {
            return Inertia::render('Admin/UserManagement', [
                'users' => \App\Models\User::with('roles')->paginate(20),
                'roles' => \App\Models\Role::all(),
            ]);
        })->name('admin.users');

        Route::get('/admin/documents', function () {
            return Inertia::render('Admin/DocumentLibrary');
        })->name('admin.documents');

        Route::get('/admin/monitor', function () {
            return Inertia::render('Admin/SystemMonitor');
        })->name('admin.monitor');

        Route::get('/admin/settings', function () {
            return Inertia::render('Admin/AISettings');
        })->name('admin.settings');
    });
});



Route::get('/terms', [LegalController::class, 'terms'])->name('terms');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('privacy');



