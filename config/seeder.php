<?php

/**
 * Scalable volume controls for the production-like database seeders.
 *
 * Every count is env-overridable so the dataset can grow without code changes,
 * e.g. `SEED_STUDENTS=2000 php artisan migrate:fresh --seed`.
 */
return [
    // Bulk account counts (in addition to the four canonical demo accounts).
    'students' => (int) env('SEED_STUDENTS', 500),
    'faculty' => (int) env('SEED_FACULTY', 80),
    'admins' => (int) env('SEED_ADMINS', 10),

    // Sectioning & enrollment.
    'section_capacity' => (int) env('SEED_SECTION_CAPACITY', 45),
    'max_courses_per_student' => (int) env('SEED_MAX_COURSES', 6),

    // Per-section academic content.
    'materials_per_section' => (int) env('SEED_MATERIALS', 3),
    'exams_per_section' => (int) env('SEED_EXAMS', 3),
    'attendance_sessions' => (int) env('SEED_ATTENDANCE_SESSIONS', 6),

    // Per-student productivity content.
    'notes_per_student' => (int) env('SEED_NOTES', 2),
    'tasks_per_student' => (int) env('SEED_TASKS', 3),

    // Shared password for all bulk (non-demo) accounts. Hashed once and reused.
    // Applies to every seeded student / faculty / admin (via the SeedsUsers
    // trait). The canonical demo accounts in RBACSeeder keep their own creds.
    'password' => env('SEED_PASSWORD', 'demo12345'),

    // The canonical demo accounts owned by RBACSeeder/AcademicSeeder. Bulk
    // seeders exclude these so the test/demo fixture is never duplicated.
    'demo_emails' => [
        'student@university.edu',
        'prof.smith@university.edu',
        'prof.jones@university.edu',
        'admin@university.edu',
    ],

    // Course codes owned by the demo AcademicSeeder. Bulk seeders never enroll
    // into or attach content to these, so the test suite's exact demo rosters
    // (e.g. CS301 section A/B) stay intact.
    'demo_course_codes' => [
        'CS201', 'CS210', 'CS220', 'CS301', 'CS305', 'CS310', 'CS320', 'CS330', 'CS340',
    ],
];
