<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/test-simple', function () {
    return view('test');
});

// Root route - Welcome page
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Dashboard route - Use Student Dashboard
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');
Route::get('/admin/documents/upload', function () {
    return Inertia::render('Admin/DocumentUpload');
})->name('admin.documents.upload');
Route::get('/admin/users', function () {
    return Inertia::render('Admin/UserManagement');
})->name('admin.users');
Route::get('/admin/monitor', function () {
    return Inertia::render('Admin/SystemMonitor');
})->name('admin.monitor');
Route::get('/admin/settings', function () {
    return Inertia::render('Admin/AISettings');
})->name('admin.settings');
Route::get('/admin/documents', function () {
    return Inertia::render('Admin/DocumentLibrary');
})->name('admin.documents');
Route::get('/admin/analytics', function () {
    return Inertia::render('Admin/Analytics');
})->name('admin.analytics');
Route::get('/admin/approvals', function () {
    return Inertia::render('Admin/Approvals');
})->name('admin.approvals');
Route::get('/admin/dashboard', function () {
    return Inertia::render('Admin/Dashboard');
})->name('admin.dashboard');
// Chat route
Route::get('/chat', function () {
    return Inertia::render('Chat');
})->name('chat');

// Placeholder routes for navigation
Route::get('/roadmap', function () {
    return Inertia::render('Welcome');
})->name('roadmap');

Route::get('/saved', function () {
    return Inertia::render('Welcome');
})->name('saved');

Route::get('/documents', function () {
    return Inertia::render('Welcome');
})->name('documents');

Route::get('/deadlines', function () {
    return Inertia::render('Welcome');
})->name('deadlines');

Route::get('/profile', function () {
    return Inertia::render('Welcome');
})->name('profile');

Route::get('/settings', function () {
    return Inertia::render('Welcome');
})->name('settings');

Route::get('/login', function () {
    return Inertia::render('Welcome');
})->name('login');

Route::post('/logout', function () {
    return redirect('/');
})->name('logout');
Route::get('/faculty/dashboard', function () {
    return Inertia::render('Faculty/Dashboard');
})->name('faculty.dashboard');
Route::get('/faculty/grading', function () {
    return Inertia::render('Faculty/Grading');
})->name('faculty.grading');

// Also add course-specific grading route
Route::get('/faculty/courses/{course}/grading', function ($course) {
    return Inertia::render('Faculty/Grading', ['courseId' => $course]);
})->name('faculty.course.grading');