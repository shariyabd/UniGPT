<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public & Auth Pages
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Auth/Login');
})->name('welcome');

Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login');

Route::post('/login', function () {
    return redirect('/dashboard');
})->name('login.post');

Route::post('/register', function () {
    return redirect('/dashboard');
})->name('register.post');

Route::post('/logout', function () {
    return redirect('/');
})->name('logout');

/*
|--------------------------------------------------------------------------
| Student Routes
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

// Route::get('/chat', function () {
//     return Inertia::render('Chat');
// })->name('chat');
Route::get('/chat', function () {
    return Inertia::render('Student/Chat');
})->name('chat');

Route::get('/student/chat', function () {
    return Inertia::render('Student/Chat');
})->name('student.chat');

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

/*
|--------------------------------------------------------------------------
| Faculty Routes
|--------------------------------------------------------------------------
*/

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

Route::get('/admin/documents', function () {
    return Inertia::render('Admin/DocumentLibrary');
})->name('admin.documents');

Route::get('/admin/users', function () {
    return Inertia::render('Admin/UserManagement');
})->name('admin.users');

Route::get('/admin/monitor', function () {
    return Inertia::render('Admin/SystemMonitor');
})->name('admin.monitor');

Route::get('/admin/settings', function () {
    return Inertia::render('Admin/AISettings');
})->name('admin.settings');

Route::get('/admin/analytics', function () {
    return Inertia::render('Admin/Analytics');
})->name('admin.analytics');

Route::get('/admin/approvals', function () {
    return Inertia::render('Admin/Approvals');
})->name('admin.approvals');

/*
|--------------------------------------------------------------------------
| Testing
|--------------------------------------------------------------------------
*/

Route::get('/test-simple', function () {
    return view('test');
});
