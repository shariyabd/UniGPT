<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login');

Route::post('/login', function () {
    // Handle login logic here
    return redirect('/dashboard');
})->name('login.post');

Route::post('/register', function () {
    // Handle registration logic here
    return redirect('/dashboard');
})->name('register.post');

Route::post('/logout', function () {
    // Handle logout logic here
    return redirect('/login');
})->name('logout');

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
*/

// Student Dashboard (default)
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

/*
|--------------------------------------------------------------------------
| Student Routes
|--------------------------------------------------------------------------
*/

// Enhanced Student Chat
Route::get('/chat', function () {
    return Inertia::render('Student/Chat');
})->name('chat');

// Saved Answers & Bookmarks - FIXED
Route::get('/saved', function () {
    return Inertia::render('Student/SavedAnswers');
})->name('saved');

// Academic Roadmap - TO BE BUILT
Route::get('/roadmap', function () {
    return Inertia::render('Student/Roadmap');
})->name('roadmap');

// Course Documents
Route::get('/documents', function () {
    return Inertia::render('Student/Documents');
})->name('documents');

// Course Materials
Route::get('/student/materials', function () {
    return Inertia::render('Student/Materials');
})->name('student.materials');

/*
|--------------------------------------------------------------------------
| Faculty Routes
|--------------------------------------------------------------------------
*/
Route::get('/faculty/ai-assistant', function () {
    return Inertia::render('Faculty/AIAssistant');
})->name('faculty.ai-assistant');

Route::get('/faculty/dashboard', function () {
    return Inertia::render('Faculty/Dashboard');
})->name('faculty.dashboard');

Route::get('/faculty/grading', function () {
    return Inertia::render('Faculty/Grading');
})->name('faculty.grading');

Route::get('/faculty/courses/{course}/grading', function ($course) {
    return Inertia::render('Faculty/Grading', ['courseId' => $course]);
})->name('faculty.course.grading');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::get('/admin/dashboard', function () {
    return Inertia::render('Admin/Dashboard');
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
    return Inertia::render('Admin/UserManagement');
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

/*
|--------------------------------------------------------------------------
| Additional Pages
|--------------------------------------------------------------------------
*/

Route::get('/terms', function () {
    return Inertia::render('Legal/Terms');
})->name('terms');

Route::get('/privacy', function () {
    return Inertia::render('Legal/Privacy');
})->name('privacy');

Route::get('/forgot-password', function () {
    return Inertia::render('Auth/ForgotPassword');
})->name('password.request');

// Profile and Settings placeholders
Route::get('/profile', function () {
    return Inertia::render('Student/Profile');
})->name('profile');

Route::get('/settings', function () {
    return Inertia::render('Student/Settings');
})->name('settings');
