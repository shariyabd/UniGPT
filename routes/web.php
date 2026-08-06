<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AiUsageController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\DepartmentController as AdminDepartmentController;
use App\Http\Controllers\Admin\DiscussionModerationController;
use App\Http\Controllers\Admin\DocumentController as AdminDocumentController;
use App\Http\Controllers\Admin\EmailSettingsController;
use App\Http\Controllers\Admin\ExamController as AdminExamController;
use App\Http\Controllers\Admin\ExamSecurityController;
use App\Http\Controllers\Admin\MonitorController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SectionController as AdminSectionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TermController as AdminTermController;
use App\Http\Controllers\Admin\UserActivityController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\AuthenticationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Community\DiscussionController;
use App\Http\Controllers\DocumentSubmissionController;
use App\Http\Controllers\Faculty\AIAssistantController as FacultyAIAssistantController;
use App\Http\Controllers\Faculty\AnalyticsController as FacultyAnalyticsController;
use App\Http\Controllers\Faculty\AssignmentController as FacultyAssignmentController;
use App\Http\Controllers\Faculty\AttendanceController as FacultyAttendanceController;
use App\Http\Controllers\Faculty\ClassTestController as FacultyClassTestController;
use App\Http\Controllers\Faculty\CourseController as FacultyCourseController;
use App\Http\Controllers\Faculty\CourseFeedbackController as FacultyCourseFeedbackController;
use App\Http\Controllers\Faculty\CourseMaterialController as FacultyCourseMaterialController;
use App\Http\Controllers\Faculty\FacultyDashboardController;
use App\Http\Controllers\Faculty\GradingController as FacultyGradingController;
use App\Http\Controllers\Faculty\OfficeHoursController as FacultyOfficeHoursController;
use App\Http\Controllers\Faculty\QuestionBankController;
use App\Http\Controllers\Faculty\StudentDirectoryController as FacultyStudentDirectoryController;
use App\Http\Controllers\FeatureListController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\Messenger\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\PublicDocsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Student\AchievementController;
use App\Http\Controllers\Student\AssignmentController as StudentAssignmentController;
use App\Http\Controllers\Student\CalendarExportController;
use App\Http\Controllers\Student\ChatController;
use App\Http\Controllers\Student\ClassTestController as StudentClassTestController;
use App\Http\Controllers\Student\CourseFeedbackController as StudentCourseFeedbackController;
use App\Http\Controllers\Student\FacultyDirectoryController as StudentFacultyDirectoryController;
use App\Http\Controllers\Student\FlashcardController;
use App\Http\Controllers\Student\LeaderboardController;
use App\Http\Controllers\Student\LearningAnalyticsController;
use App\Http\Controllers\Student\NoteController;
use App\Http\Controllers\Student\OfficeHoursController as StudentOfficeHoursController;
use App\Http\Controllers\Student\PracticeQuizController;
use App\Http\Controllers\Student\RegistrationController as StudentRegistrationController;
use App\Http\Controllers\Student\SavedAnswerController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Student\StudyPlannerController;
use App\Http\Controllers\Student\StudyRoomController;
use App\Http\Controllers\Student\TaskController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Development helper: clear all caches (config, route, view, cache, compiled,
// events) from the browser. Restricted to the local environment so it can never
// be triggered in production.
Route::get('/clear-cache', function () {
    abort_unless(app()->environment('local'), 403);

    Artisan::call('optimize:clear');

    return response('<pre>'.e(Artisan::output()).'</pre>');
})->name('cache.clear');

// Signed iCalendar feed — no session auth (Google/Outlook/Apple Calendar
// fetch it server-to-server); the URL signature is the credential and is
// minted per-user on the Calendar page.
Route::get('/calendar/feed/{user}', [CalendarExportController::class, 'feed'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('calendar.feed');

// Hidden/internal maintenance switch. `?live=true` (or live=1) runs the app
// normally; `?live=false` (or live=0) drops the whole site into Maintenance Mode.
// The toggle is handled globally by HandleMaintenanceMode (any URL honours the
// `live` param); this named route is the canonical shortcut and redirects home
// once the state has been applied.
Route::get('/live', fn () => redirect()->route('home'))->name('live.toggle');

// Public marketing landing page.
Route::get('/', fn () => Inertia::render('Landing'))->name('home');

// Standalone product presentation / pitch deck. Renders full-screen with no
// app chrome — the Presentation page is self-contained (no AppLayout).
Route::get('/presentation', fn () => Inertia::render('Presentation'))->name('presentation');

// Public, interactive feature index — a mirror of docs/feature-list.md.
Route::get('/feature-list', [FeatureListController::class, 'index'])->name('feature-list');

// Public, browsable library for the shared documents under public/docs
// (reports, architecture & model, RAG question bank). Directories render a
// listing; files stream inline (or ?download=1 to force download).
Route::get('/docs/{path?}', [PublicDocsController::class, 'browse'])
    ->where('path', '.*')
    ->name('docs.browse');

// Authentication Routes
Route::middleware('guest')->group(function () {});
Route::get('/login', [AuthenticationController::class, 'create'])->name('login');
Route::post('/login', [AuthenticationController::class, 'store'])->name('login.store');
// Self-service account creation. Named `signup` (not `register`) to avoid
// colliding with the student course-registration routes below, which own the
// `/register` URI and the `register` name.
Route::post('/signup', [AuthenticationController::class, 'register'])->name('signup');
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
    // Global ⌘K search — available to every authenticated role; results are
    // scoped inside the service to what the user can access.
    Route::get('/search', SearchController::class)->name('search.global');

    // In-app notifications — available to every authenticated role.
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/poll', [NotificationController::class, 'poll'])->name('notifications.poll');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Direct messaging (student ↔ their faculty). JSON endpoints consumed by the
    // messenger pages via axios for a live feel; access is relationship-based,
    // so both roles share these routes. These also serve as the polling fallback
    // when the realtime websocket is unavailable (the DB is the source of truth).
    Route::prefix('messenger')->name('messenger.')->group(function () {
        Route::post('/conversations', [MessageController::class, 'resolve'])->name('conversations.resolve');
        Route::get('/overview', [MessageController::class, 'overview'])->name('overview');
        Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->name('messages.store');
        Route::post('/conversations/{conversation}/read', [MessageController::class, 'markRead'])->name('messages.read');
    });

    // Presence heartbeat — pinged from every authenticated page (any role/route).
    Route::post('/heartbeat', [PresenceController::class, 'ping'])->name('heartbeat');

    // Course/section discussion feed — shared by students and faculty. Access is
    // relationship-based (enrolment / teaching), enforced in the controller, so
    // both roles use one route set (like the messenger routes above).
    Route::prefix('discussions')->name('discussions.')->group(function () {
        Route::get('/', [DiscussionController::class, 'index'])->middleware('permission:view_discussions')->name('index');
        Route::post('/sections/{section}/posts', [DiscussionController::class, 'storePost'])->middleware('permission:post_discussion')->name('posts.store');
        Route::patch('/posts/{post}/pin', [DiscussionController::class, 'togglePin'])->middleware('permission:moderate_discussions')->name('posts.pin');
        Route::delete('/posts/{post}', [DiscussionController::class, 'destroyPost'])->name('posts.destroy');
        Route::post('/posts/{post}/like', [DiscussionController::class, 'toggleLike'])->name('posts.like');
        Route::post('/posts/{post}/comments', [DiscussionController::class, 'storeComment'])->middleware('permission:post_discussion')->name('comments.store');
        Route::delete('/comments/{comment}', [DiscussionController::class, 'destroyComment'])->name('comments.destroy');
        Route::post('/posts/{post}/report', [DiscussionController::class, 'reportPost'])->name('posts.report');
        Route::post('/comments/{comment}/report', [DiscussionController::class, 'reportComment'])->name('comments.report');
    });

    Route::middleware('role:student')->prefix('')->name('')->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

        // AI chat (page is Inertia; send/load are JSON for a live feel)
        Route::get('/chat', [ChatController::class, 'index'])->middleware('permission:use_ai_chat')->name('chat');
        Route::get('/chat/archived', [ChatController::class, 'archived'])->middleware('permission:view_chat_history')->name('chat.archived');
        Route::post('/chat', [ChatController::class, 'store'])->middleware(['permission:use_ai_chat', 'ai.chat.access', 'demo.ai.limit'])->name('chat.send');
        Route::post('/chat/stream', [ChatController::class, 'stream'])->middleware(['permission:use_ai_chat', 'ai.chat.access', 'demo.ai.limit'])->name('chat.stream');
        Route::get('/chat/sessions/{session}', [ChatController::class, 'show'])->middleware('permission:view_chat_history')->name('chat.session');
        Route::patch('/chat/sessions/{session}', [ChatController::class, 'rename'])->middleware('permission:use_ai_chat')->name('chat.session.rename');
        Route::patch('/chat/sessions/{session}/pin', [ChatController::class, 'togglePin'])->middleware('permission:view_chat_history')->name('chat.session.pin');
        Route::patch('/chat/sessions/{session}/archive', [ChatController::class, 'archive'])->middleware('permission:use_ai_chat')->name('chat.session.archive');
        Route::patch('/chat/sessions/{session}/unarchive', [ChatController::class, 'unarchive'])->middleware('permission:use_ai_chat')->name('chat.session.unarchive');
        Route::delete('/chat/sessions/{session}', [ChatController::class, 'destroy'])->middleware('permission:delete_chat')->name('chat.session.destroy');

        // Flashcards — personal decks with SM-2 spaced repetition. Manual CRUD is
        // student self-service; AI generation reuses the AI-chat access gate.
        Route::prefix('flashcards')->name('flashcards.')->group(function () {
            Route::get('/', [FlashcardController::class, 'index'])->name('index');
            Route::post('/', [FlashcardController::class, 'store'])->name('store');
            Route::post('/generate', [FlashcardController::class, 'generate'])->middleware(['permission:use_ai_chat', 'ai.chat.access', 'demo.ai.limit'])->name('generate');
            Route::get('/{deck}', [FlashcardController::class, 'show'])->name('show');
            Route::patch('/{deck}', [FlashcardController::class, 'update'])->name('update');
            Route::delete('/{deck}', [FlashcardController::class, 'destroy'])->name('destroy');
            Route::post('/{deck}/cards', [FlashcardController::class, 'storeCard'])->name('cards.store');
            Route::patch('/cards/{card}', [FlashcardController::class, 'updateCard'])->name('cards.update');
            Route::delete('/cards/{card}', [FlashcardController::class, 'destroyCard'])->name('cards.destroy');
            Route::post('/cards/{card}/review', [FlashcardController::class, 'review'])->name('cards.review');
        });

        // Office hours — browse the student's faculty's slots and book/cancel.
        Route::get('/office-hours', [StudentOfficeHoursController::class, 'index'])->name('office-hours');
        Route::post('/office-hours/{slot}/book', [StudentOfficeHoursController::class, 'book'])->name('office-hours.book');
        Route::post('/office-hours/{slot}/cancel', [StudentOfficeHoursController::class, 'cancel'])->name('office-hours.cancel');

        // Anonymous mid-semester course feedback (one response per section).
        Route::get('/course-feedback', [StudentCourseFeedbackController::class, 'index'])->name('course-feedback');
        Route::post('/course-feedback/{section}', [StudentCourseFeedbackController::class, 'store'])->name('course-feedback.store');

        // Group study rooms — section-scoped group chats between classmates.
        // The chat itself uses the shared messenger endpoints (membership-based
        // auth); these routes manage the rooms and their membership.
        Route::prefix('study-rooms')->name('study-rooms.')->group(function () {
            Route::get('/', [StudyRoomController::class, 'index'])->name('index');
            Route::post('/', [StudyRoomController::class, 'store'])->name('store');
            Route::post('/{room}/join', [StudyRoomController::class, 'join'])->name('join');
            Route::post('/{room}/leave', [StudyRoomController::class, 'leave'])->name('leave');
            Route::get('/{room}/members', [StudyRoomController::class, 'members'])->name('members');
        });

        // Practice quizzes — self-quizzing with AI generation and instant
        // server-side grading; generation reuses the AI-chat access gate.
        Route::prefix('practice')->name('practice.')->group(function () {
            Route::get('/', [PracticeQuizController::class, 'index'])->name('index');
            Route::post('/generate', [PracticeQuizController::class, 'generate'])->middleware(['permission:use_ai_chat', 'ai.chat.access', 'demo.ai.limit'])->name('generate');
            Route::post('/from-bank', [PracticeQuizController::class, 'fromBank'])->name('from-bank');
            Route::get('/{quiz}', [PracticeQuizController::class, 'show'])->name('show');
            Route::post('/{quiz}/submit', [PracticeQuizController::class, 'submit'])->name('submit');
            Route::post('/{quiz}/attempts/{attempt}/flashcards', [PracticeQuizController::class, 'toFlashcards'])->name('flashcards');
            Route::delete('/{quiz}', [PracticeQuizController::class, 'destroy'])->name('destroy');
        });

        // Saved answers
        Route::get('/saved', [SavedAnswerController::class, 'index'])->middleware('permission:view_chat_history')->name('saved');
        Route::post('/saved', [SavedAnswerController::class, 'store'])->middleware('permission:use_ai_chat')->name('saved.store');
        Route::patch('/saved/{savedAnswer}', [SavedAnswerController::class, 'update'])->middleware('permission:use_ai_chat')->name('saved.update');
        Route::post('/saved/{savedAnswer}/view', [SavedAnswerController::class, 'view'])->middleware('permission:view_chat_history')->name('saved.view');
        Route::delete('/saved/{savedAnswer}', [SavedAnswerController::class, 'destroy'])->middleware('permission:delete_chat')->name('saved.destroy');
        Route::get('/roadmap', [StudentDashboardController::class, 'roadmap'])->middleware('permission:view_courses')->name('roadmap');
        Route::get('/documents', [StudentDashboardController::class, 'documents'])->middleware('permission:view_documents')->name('documents');
        Route::get('/documents/{document}/download', [StudentDashboardController::class, 'downloadDocument'])->middleware('permission:download_document')->name('documents.download');
        Route::get('/documents/{document}/preview', [StudentDashboardController::class, 'previewDocument'])->middleware('permission:view_documents')->name('documents.preview');
        Route::post('/documents/{document}/bookmark', [StudentDashboardController::class, 'toggleBookmark'])->middleware('permission:view_documents')->name('documents.bookmark');

        // My Documents — student-submitted documents that flow into the admin
        // approval queue. Owner-scoped upload / edit / delete.
        Route::middleware('permission:upload_document')->group(function () {
            Route::get('/my-documents', [DocumentSubmissionController::class, 'index'])->name('my-documents');
            Route::post('/my-documents', [DocumentSubmissionController::class, 'store'])->name('my-documents.store');
            Route::post('/my-documents/{document}', [DocumentSubmissionController::class, 'update'])->name('my-documents.update');
            Route::delete('/my-documents/{document}', [DocumentSubmissionController::class, 'destroy'])->name('my-documents.destroy');
            Route::get('/my-documents/{document}/download', [DocumentSubmissionController::class, 'download'])->name('my-documents.download');
            Route::get('/my-documents/{document}/preview', [DocumentSubmissionController::class, 'preview'])->name('my-documents.preview');
        });
        Route::get('/materials', [StudentDashboardController::class, 'materials'])->middleware('permission:view_courses')->name('materials');
        Route::get('/materials/{material}/download', [StudentDashboardController::class, 'downloadMaterial'])->middleware('permission:view_courses')->name('materials.download');
        Route::patch('/materials/{material}/completion', [StudentDashboardController::class, 'toggleMaterialCompletion'])->middleware('permission:view_courses')->name('materials.completion');
        Route::get('/attendance', [StudentDashboardController::class, 'attendance'])->middleware('permission:view_attendance')->name('attendance');
        Route::get('/transcript', [StudentDashboardController::class, 'transcript'])->middleware('permission:view_courses')->name('transcript');
        Route::get('/progress', [LearningAnalyticsController::class, 'index'])->middleware('permission:view_own_analytics')->name('progress');
        Route::get('/exams', [StudentDashboardController::class, 'exams'])->middleware('permission:view_exams')->name('exams');
        Route::get('/calendar', [StudentDashboardController::class, 'calendar'])->name('calendar');
        Route::get('/calendar/export', [CalendarExportController::class, 'download'])->name('calendar.export');

        // Assignments + submissions — `{assignment}` is numeric so order is safe
        // Course self-registration (offered sections for the student's semester)
        Route::get('/register', [StudentRegistrationController::class, 'index'])->middleware('permission:enroll_course')->name('register');
        Route::post('/register', [StudentRegistrationController::class, 'store'])->middleware('permission:enroll_course')->name('register.store');
        Route::delete('/register/{section}', [StudentRegistrationController::class, 'destroy'])->middleware('permission:enroll_course')->name('register.drop');

        Route::get('/assignments', [StudentAssignmentController::class, 'index'])->middleware('permission:view_assignments')->name('assignments');
        Route::get('/assignments/{assignment}', [StudentAssignmentController::class, 'show'])->middleware('permission:view_assignments')->name('assignments.show');
        Route::post('/assignments/{assignment}/submit', [StudentAssignmentController::class, 'store'])->middleware('permission:submit_assignment')->name('assignments.submit');
        // Anonymous peer review: complete an assigned review task.
        Route::post('/assignments/{assignment}/peer-reviews/{review}', [StudentAssignmentController::class, 'storePeerReview'])->name('assignments.peer-review');

        // Class tests / quizzes (proctored, auto-graded, instant results)
        Route::middleware('permission:take_class_test')->group(function () {
            Route::get('/class-tests', [StudentClassTestController::class, 'index'])->name('class-tests');
            Route::get('/class-tests/{classTest}', [StudentClassTestController::class, 'show'])->name('class-tests.show');
            Route::post('/class-tests/{classTest}/start', [StudentClassTestController::class, 'start'])->name('class-tests.start');
            Route::get('/class-tests/{classTest}/take', [StudentClassTestController::class, 'take'])->name('class-tests.take');
            Route::post('/class-tests/{classTest}/violation', [StudentClassTestController::class, 'violation'])->name('class-tests.violation');
            Route::post('/class-tests/{classTest}/fingerprint', [StudentClassTestController::class, 'fingerprint'])->name('class-tests.fingerprint');
            Route::post('/class-tests/{classTest}/events', [StudentClassTestController::class, 'events'])->name('class-tests.events');
            Route::post('/class-tests/{classTest}/recording', [StudentClassTestController::class, 'recording'])->name('class-tests.recording');
            Route::post('/class-tests/{classTest}/snapshot', [StudentClassTestController::class, 'snapshot'])->name('class-tests.snapshot');
            Route::post('/class-tests/{classTest}/submit', [StudentClassTestController::class, 'submit'])->name('class-tests.submit');
            Route::get('/class-tests/{classTest}/result', [StudentClassTestController::class, 'result'])->name('class-tests.result');
        });

        // Personal productivity (self-service; scoped to the owner) — Notes
        Route::get('/notes', [NoteController::class, 'index'])->name('notes');
        Route::post('/notes', [NoteController::class, 'store'])->name('notes.store');
        // OCR handwritten notes — gpt-4o vision transcription (behind the AI gate)
        Route::post('/notes/ocr', [NoteController::class, 'ocr'])->middleware(['permission:use_ai_chat', 'ai.chat.access', 'demo.ai.limit'])->name('notes.ocr');
        Route::patch('/notes/{note}', [NoteController::class, 'update'])->name('notes.update');
        Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

        // AI Study Planner — builds a study schedule from upcoming deadlines and
        // saves chosen sessions as personal Tasks. Generation reuses the AI-chat
        // access gate so admin AI-blocking covers it too.
        Route::get('/study-planner', [StudyPlannerController::class, 'index'])->name('study-planner');
        Route::post('/study-planner/generate', [StudyPlannerController::class, 'generate'])->middleware(['permission:use_ai_chat', 'ai.chat.access', 'demo.ai.limit'])->name('study-planner.generate');
        Route::post('/study-planner/tasks', [StudyPlannerController::class, 'storeTasks'])->name('study-planner.tasks');

        // Personal productivity — Tasks
        Route::get('/tasks', [TaskController::class, 'index'])->name('tasks');
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::patch('/tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

        // Leaderboard — opt-in, gamified XP ranking (department / semester / section)
        Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');
        Route::patch('/leaderboard/settings', [LeaderboardController::class, 'updateSettings'])->name('leaderboard.settings');

        // Achievements — earned/locked badges with progress (awards on view).
        Route::get('/achievements', [AchievementController::class, 'index'])->name('achievements');

        // My Faculty — directory of the student's instructors, plus a dedicated
        // messenger view (real-time chat is live; see the Messenger controllers).
        Route::get('/my-faculty', [StudentFacultyDirectoryController::class, 'index'])->name('my-faculty');
        Route::get('/messages', [StudentFacultyDirectoryController::class, 'messages'])->name('messages');

        // Self-service account pages (no extra permission beyond the student role)
        Route::get('/profile', [StudentDashboardController::class, 'profile'])->name('profile');
        Route::patch('/profile', [StudentDashboardController::class, 'updateProfile'])->name('profile.update');
        Route::get('/settings', [StudentDashboardController::class, 'settings'])->name('settings');
        Route::patch('/settings', [StudentDashboardController::class, 'updateSettings'])->name('settings.update');
    });

    Route::middleware('role:faculty')->prefix('faculty')->name('faculty.')->group(function () {
        Route::get('/dashboard', [FacultyDashboardController::class, 'index'])->name('dashboard');

        // My Students — roster of students in the faculty's own courses/sections,
        // plus a dedicated messenger view (real-time chat is live; see the
        // Messenger controllers).
        Route::get('/students', [FacultyStudentDirectoryController::class, 'index'])->name('students');
        Route::get('/messages', [FacultyStudentDirectoryController::class, 'messages'])->name('messages');

        // Courses — faculty view the courses/sections they teach (catalog +
        // section management is admin-owned).
        Route::get('/courses', [FacultyCourseController::class, 'index'])->middleware('permission:view_courses')->name('courses');
        Route::get('/courses/{course}', [FacultyCourseController::class, 'show'])->middleware('permission:view_courses')->name('courses.show');

        // Course materials (faculty manage + upload)
        Route::post('/courses/{course}/materials', [FacultyCourseMaterialController::class, 'store'])->middleware('permission:manage_materials')->name('courses.materials.store');
        Route::patch('/courses/{course}/materials/{material}', [FacultyCourseMaterialController::class, 'update'])->middleware('permission:manage_materials')->name('courses.materials.update');
        Route::delete('/courses/{course}/materials/{material}', [FacultyCourseMaterialController::class, 'destroy'])->middleware('permission:manage_materials')->name('courses.materials.destroy');
        Route::get('/courses/{course}/materials/{material}/download', [FacultyCourseMaterialController::class, 'download'])->middleware('permission:view_courses')->name('courses.materials.download');

        // Office hours — publish/remove bookable slots, manage bookings.
        Route::get('/office-hours', [FacultyOfficeHoursController::class, 'index'])->name('office-hours');
        Route::post('/office-hours', [FacultyOfficeHoursController::class, 'store'])->name('office-hours.store');
        Route::delete('/office-hours/{slot}', [FacultyOfficeHoursController::class, 'destroy'])->name('office-hours.destroy');
        Route::post('/office-hours/{slot}/cancel-booking', [FacultyOfficeHoursController::class, 'cancelBooking'])->name('office-hours.cancel-booking');

        // Anonymous course feedback: per-section window + anonymized results + AI summary.
        Route::get('/course-feedback', [FacultyCourseFeedbackController::class, 'index'])->name('course-feedback');
        Route::patch('/course-feedback/{section}/toggle', [FacultyCourseFeedbackController::class, 'toggle'])->name('course-feedback.toggle');
        Route::post('/course-feedback/{section}/summarize', [FacultyCourseFeedbackController::class, 'summarize'])->middleware(['permission:use_ai_chat', 'ai.chat.access', 'demo.ai.limit'])->name('course-feedback.summarize');

        // My Documents — faculty-submitted documents that flow into the admin
        // approval queue. Owner-scoped upload / edit / delete.
        Route::middleware('permission:upload_document')->group(function () {
            Route::get('/my-documents', [DocumentSubmissionController::class, 'index'])->name('my-documents');
            Route::post('/my-documents', [DocumentSubmissionController::class, 'store'])->name('my-documents.store');
            Route::post('/my-documents/{document}', [DocumentSubmissionController::class, 'update'])->name('my-documents.update');
            Route::delete('/my-documents/{document}', [DocumentSubmissionController::class, 'destroy'])->name('my-documents.destroy');
            Route::get('/my-documents/{document}/download', [DocumentSubmissionController::class, 'download'])->name('my-documents.download');
            Route::get('/my-documents/{document}/preview', [DocumentSubmissionController::class, 'preview'])->name('my-documents.preview');
        });

        // AI teaching assistant — full ChatGPT-style chat workspace + generators
        Route::get('/ai-assistant', [FacultyAIAssistantController::class, 'index'])->middleware('permission:use_ai_chat')->name('ai-assistant');
        Route::get('/ai-assistant/archived', [FacultyAIAssistantController::class, 'archived'])->middleware('permission:view_chat_history')->name('ai-assistant.archived');
        Route::post('/ai-assistant/chat', [FacultyAIAssistantController::class, 'chat'])->middleware(['permission:use_ai_chat', 'demo.ai.limit'])->name('ai-assistant.chat');
        Route::post('/ai-assistant/chat/stream', [FacultyAIAssistantController::class, 'stream'])->middleware(['permission:use_ai_chat', 'demo.ai.limit'])->name('ai-assistant.stream');
        Route::get('/ai-assistant/sessions/{session}', [FacultyAIAssistantController::class, 'show'])->middleware('permission:view_chat_history')->name('ai-assistant.session');
        Route::patch('/ai-assistant/sessions/{session}', [FacultyAIAssistantController::class, 'rename'])->middleware('permission:use_ai_chat')->name('ai-assistant.session.rename');
        Route::patch('/ai-assistant/sessions/{session}/pin', [FacultyAIAssistantController::class, 'togglePin'])->middleware('permission:view_chat_history')->name('ai-assistant.session.pin');
        Route::patch('/ai-assistant/sessions/{session}/archive', [FacultyAIAssistantController::class, 'archive'])->middleware('permission:use_ai_chat')->name('ai-assistant.session.archive');
        Route::patch('/ai-assistant/sessions/{session}/unarchive', [FacultyAIAssistantController::class, 'unarchive'])->middleware('permission:use_ai_chat')->name('ai-assistant.session.unarchive');
        Route::delete('/ai-assistant/sessions/{session}', [FacultyAIAssistantController::class, 'destroySession'])->middleware('permission:delete_chat')->name('ai-assistant.session.destroy');
        Route::post('/ai-assistant/quiz', [FacultyAIAssistantController::class, 'generateQuiz'])->middleware(['permission:use_ai_chat', 'demo.ai.limit'])->name('ai-assistant.quiz');
        Route::post('/ai-assistant/assignment', [FacultyAIAssistantController::class, 'generateAssignment'])->middleware(['permission:create_assignment', 'demo.ai.limit'])->name('ai-assistant.assignment');
        Route::post('/ai-assistant/publish', [FacultyAIAssistantController::class, 'publish'])->middleware('permission:create_assignment')->name('ai-assistant.publish');
        Route::post('/ai-assistant/publish-class-test', [FacultyAIAssistantController::class, 'publishClassTest'])->middleware('permission:manage_class_tests')->name('ai-assistant.publish-class-test');

        // Attendance
        Route::get('/courses/{course}/attendance', [FacultyAttendanceController::class, 'index'])->middleware('permission:mark_attendance')->name('courses.attendance');
        Route::post('/courses/{course}/attendance', [FacultyAttendanceController::class, 'store'])->middleware('permission:mark_attendance')->name('courses.attendance.store');

        // Exam timetable (read-only view of the faculty's course exams)
        Route::get('/exams', [FacultyDashboardController::class, 'exams'])->middleware('permission:view_exams')->name('exams');

        // Class tests / quizzes — faculty author, publish and view results
        Route::middleware('permission:manage_class_tests')->group(function () {
            Route::get('/class-tests', [FacultyClassTestController::class, 'index'])->name('class-tests');
            Route::get('/class-tests/create', [FacultyClassTestController::class, 'create'])->name('class-tests.create');
            Route::post('/class-tests/generate', [FacultyClassTestController::class, 'generate'])->name('class-tests.generate');
            Route::post('/class-tests', [FacultyClassTestController::class, 'store'])->name('class-tests.store');
            Route::get('/class-tests/{classTest}/edit', [FacultyClassTestController::class, 'edit'])->name('class-tests.edit');
            Route::patch('/class-tests/{classTest}', [FacultyClassTestController::class, 'update'])->name('class-tests.update');
            Route::patch('/class-tests/{classTest}/status', [FacultyClassTestController::class, 'toggleStatus'])->name('class-tests.status');
            Route::delete('/class-tests/{classTest}', [FacultyClassTestController::class, 'destroy'])->name('class-tests.destroy');
            Route::get('/class-tests/{classTest}/results', [FacultyClassTestController::class, 'results'])->name('class-tests.results');
            Route::get('/class-tests/{classTest}/attempts/{attempt}', [FacultyClassTestController::class, 'attempt'])->name('class-tests.attempt');
            Route::get('/class-tests/{classTest}/attempts/{attempt}/recordings/{recording}', [FacultyClassTestController::class, 'recording'])->name('class-tests.recording');
            Route::get('/class-tests/{classTest}/attempts/{attempt}/snapshots/{snapshot}', [FacultyClassTestController::class, 'snapshot'])->name('class-tests.snapshot');

            // Question bank: per-course reusable questions, import from tests,
            // spin draft tests from a selection.
            Route::prefix('question-bank')->name('question-bank.')->middleware('permission:manage_class_tests')->group(function () {
                Route::get('/', [QuestionBankController::class, 'index'])->name('index');
                Route::post('/', [QuestionBankController::class, 'store'])->name('store');
                Route::delete('/{item}', [QuestionBankController::class, 'destroy'])->name('destroy');
                Route::post('/import/{classTest}', [QuestionBankController::class, 'import'])->name('import');
                Route::post('/create-test', [QuestionBankController::class, 'createTest'])->name('create-test');
            });
        });

        // Learning analytics & academic reporting
        Route::get('/analytics', [FacultyAnalyticsController::class, 'index'])->middleware('permission:view_department_analytics')->name('analytics');
        Route::get('/courses/{course}/analytics', [FacultyAnalyticsController::class, 'index'])->middleware('permission:view_department_analytics')->name('courses.analytics');

        // Assignment / quiz management (view/edit/status/delete published items)
        Route::patch('/assignments/{assignment}', [FacultyAssignmentController::class, 'update'])->middleware('permission:create_assignment')->name('assignments.update');
        Route::patch('/assignments/{assignment}/status', [FacultyAssignmentController::class, 'toggleStatus'])->middleware('permission:create_assignment')->name('assignments.status');
        Route::delete('/assignments/{assignment}', [FacultyAssignmentController::class, 'destroy'])->middleware('permission:create_assignment')->name('assignments.destroy');

        // Grading
        Route::get('/grading', [FacultyGradingController::class, 'index'])->middleware('permission:grade_assignment')->name('grading');
        Route::get('/courses/{course}/grading', [FacultyGradingController::class, 'index'])->middleware('permission:grade_assignment')->name('course.grading');
        Route::get('/submissions/{submission}/download', [FacultyGradingController::class, 'downloadSubmission'])->middleware('permission:grade_assignment')->name('submissions.download');
        Route::post('/submissions/{submission}/grade', [FacultyGradingController::class, 'grade'])->middleware('permission:grade_assignment')->name('submissions.grade');
        Route::post('/submissions/{submission}/feedback', [FacultyGradingController::class, 'suggestFeedback'])->middleware('permission:grade_assignment')->name('submissions.feedback');
        Route::post('/submissions/{submission}/draft-grade', [FacultyGradingController::class, 'draftGrade'])->middleware(['permission:grade_assignment', 'demo.ai.limit'])->name('submissions.draft-grade');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Document knowledge base + approval workflow
        Route::get('/documents/upload', [AdminDocumentController::class, 'upload'])->middleware('permission:upload_document')->name('documents.upload');
        Route::post('/documents', [AdminDocumentController::class, 'store'])->middleware('permission:upload_document')->name('documents.store');
        Route::get('/documents', [AdminDocumentController::class, 'library'])->middleware('permission:view_documents')->name('documents');
        Route::get('/documents/{document}/download', [AdminDocumentController::class, 'download'])->middleware('permission:download_document')->name('documents.download');
        Route::get('/documents/{document}/preview', [AdminDocumentController::class, 'preview'])->middleware('permission:view_documents')->name('documents.preview');
        Route::post('/documents/{document}/bookmark', [AdminDocumentController::class, 'toggleBookmark'])->middleware('permission:view_documents')->name('documents.bookmark');
        Route::delete('/documents/{document}', [AdminDocumentController::class, 'destroy'])->middleware('permission:delete_document')->name('documents.destroy');

        Route::get('/approvals', [AdminDocumentController::class, 'approvals'])->middleware('permission:approve_document')->name('approvals');
        Route::post('/documents/{document}/approve', [AdminDocumentController::class, 'approve'])->middleware('permission:approve_document')->name('documents.approve');
        Route::post('/documents/{document}/reject', [AdminDocumentController::class, 'reject'])->middleware('permission:approve_document')->name('documents.reject');
        Route::post('/documents/{document}/request-changes', [AdminDocumentController::class, 'requestChanges'])->middleware('permission:approve_document')->name('documents.request-changes');
        Route::post('/documents/{document}/comment', [AdminDocumentController::class, 'comment'])->middleware('permission:approve_document')->name('documents.comment');

        Route::get('/analytics', [AnalyticsController::class, 'index'])->middleware('permission:view_all_analytics')->name('analytics');

        // User activity — tracked page visits (who / from where / device / location)
        Route::get('/user-activity', [UserActivityController::class, 'index'])->middleware('permission:view_all_analytics')->name('user-activity');

        // AI usage monitor — per-user/per-request token tracking + AI-chat access control
        Route::get('/ai-usage', [AiUsageController::class, 'index'])->middleware('permission:view_all_analytics')->name('ai-usage');
        Route::post('/ai-usage/{user}/block', [AiUsageController::class, 'block'])->middleware('permission:update_user')->name('ai-usage.block');
        Route::post('/ai-usage/{user}/unblock', [AiUsageController::class, 'unblock'])->middleware('permission:update_user')->name('ai-usage.unblock');

        // User management
        Route::get('/users', [UserManagementController::class, 'index'])->middleware('permission:view_users')->name('users');
        Route::post('/users', [UserManagementController::class, 'store'])->middleware('permission:create_user')->name('users.store');
        Route::patch('/users/{user}', [UserManagementController::class, 'update'])->middleware('permission:update_user')->name('users.update');
        Route::patch('/users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->middleware('permission:update_user')->name('users.toggle-active');
        Route::patch('/users/{user}/role', [UserManagementController::class, 'assignRole'])->middleware('permission:manage_user_roles')->name('users.role');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->middleware('permission:delete_user')->name('users.destroy');

        // Role-permission matrix editor
        Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:manage_permissions')->name('roles');
        Route::patch('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->middleware('permission:manage_permissions')->name('roles.permissions');

        // Course catalog + sections (offerings). The catalog is admin-owned;
        // faculty are assigned to sections here.
        Route::get('/courses', [AdminCourseController::class, 'index'])->middleware('permission:view_courses')->name('courses');
        Route::post('/courses', [AdminCourseController::class, 'store'])->middleware('permission:create_course')->name('courses.store');
        Route::patch('/courses/{course}', [AdminCourseController::class, 'update'])->middleware('permission:update_course')->name('courses.update');
        Route::delete('/courses/{course}', [AdminCourseController::class, 'destroy'])->middleware('permission:delete_course')->name('courses.destroy');
        Route::post('/courses/{course}/sections', [AdminSectionController::class, 'store'])->middleware('permission:manage_sections')->name('sections.store');
        Route::patch('/sections/{section}', [AdminSectionController::class, 'update'])->middleware('permission:manage_sections')->name('sections.update');
        Route::delete('/sections/{section}', [AdminSectionController::class, 'destroy'])->middleware('permission:manage_sections')->name('sections.destroy');
        Route::post('/sections/{section}/enrollments', [AdminSectionController::class, 'assign'])->middleware('permission:manage_sections')->name('sections.assign');
        Route::delete('/sections/{section}/enrollments/{user}', [AdminSectionController::class, 'drop'])->middleware('permission:manage_sections')->name('sections.drop');

        // Academic terms + end-of-term rollover
        Route::get('/terms', [AdminTermController::class, 'index'])->middleware('permission:manage_terms')->name('terms');
        Route::post('/terms', [AdminTermController::class, 'store'])->middleware('permission:manage_terms')->name('terms.store');
        Route::patch('/terms/{term}/current', [AdminTermController::class, 'setCurrent'])->middleware('permission:manage_terms')->name('terms.current');
        Route::patch('/terms/{term}/registration', [AdminTermController::class, 'toggleRegistration'])->middleware('permission:manage_terms')->name('terms.registration');
        Route::post('/terms/{term}/close', [AdminTermController::class, 'close'])->middleware('permission:manage_terms')->name('terms.close');
        Route::delete('/terms/{term}', [AdminTermController::class, 'destroy'])->middleware('permission:manage_terms')->name('terms.destroy');

        // Department management
        Route::get('/departments', [AdminDepartmentController::class, 'index'])->middleware('permission:manage_departments')->name('departments');
        Route::post('/departments', [AdminDepartmentController::class, 'store'])->middleware('permission:manage_departments')->name('departments.store');
        Route::patch('/departments/{department}', [AdminDepartmentController::class, 'update'])->middleware('permission:manage_departments')->name('departments.update');
        Route::delete('/departments/{department}', [AdminDepartmentController::class, 'destroy'])->middleware('permission:manage_departments')->name('departments.destroy');

        // Discussion moderation queue — reported posts/comments across all sections
        Route::get('/discussions/reports', [DiscussionModerationController::class, 'index'])->middleware('permission:moderate_discussions')->name('discussions.reports');
        Route::post('/discussions/reports/{report}/resolve', [DiscussionModerationController::class, 'resolve'])->middleware('permission:moderate_discussions')->name('discussions.reports.resolve');
        Route::post('/discussions/reports/{report}/remove', [DiscussionModerationController::class, 'removeContent'])->middleware('permission:moderate_discussions')->name('discussions.reports.remove');

        Route::get('/monitor', [MonitorController::class, 'index'])->middleware('permission:manage_system')->name('monitor');

        // Announcements / broadcast notifications
        Route::get('/announcements', [AnnouncementController::class, 'index'])->middleware('permission:send_notifications')->name('announcements');
        Route::post('/announcements', [AnnouncementController::class, 'store'])->middleware('permission:send_notifications')->name('announcements.store');
        Route::patch('/announcements', [AnnouncementController::class, 'update'])->middleware('permission:send_notifications')->name('announcements.update');

        // Exam / timetable management
        Route::get('/exams', [AdminExamController::class, 'index'])->middleware('permission:manage_exams')->name('exams');
        Route::post('/exams', [AdminExamController::class, 'store'])->middleware('permission:manage_exams')->name('exams.store');
        Route::patch('/exams/{exam}', [AdminExamController::class, 'update'])->middleware('permission:manage_exams')->name('exams.update');
        Route::delete('/exams/{exam}', [AdminExamController::class, 'destroy'])->middleware('permission:manage_exams')->name('exams.destroy');

        // Exam security — global gate over which proctoring layers faculty may use
        Route::get('/exam-security', [ExamSecurityController::class, 'index'])->middleware('permission:manage_exams')->name('exam-security');
        Route::patch('/exam-security', [ExamSecurityController::class, 'update'])->middleware('permission:manage_exams')->name('exam-security.update');

        // AI settings
        Route::get('/settings', [SettingsController::class, 'index'])->middleware('permission:configure_ai')->name('settings');
        Route::patch('/settings', [SettingsController::class, 'update'])->middleware('permission:configure_ai')->name('settings.update');
        Route::post('/settings/test', [SettingsController::class, 'test'])->middleware('permission:configure_ai')->name('settings.test');

        // Email (SMTP) configuration
        Route::get('/email-settings', [EmailSettingsController::class, 'index'])->middleware('permission:configure_email')->name('email-settings');
        Route::patch('/email-settings', [EmailSettingsController::class, 'update'])->middleware('permission:configure_email')->name('email-settings.update');
        Route::post('/email-settings/test', [EmailSettingsController::class, 'test'])->middleware('permission:configure_email')->name('email-settings.test');
    });
});

Route::get('/terms', [LegalController::class, 'terms'])->name('terms');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('privacy');
