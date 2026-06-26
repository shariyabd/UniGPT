<?php

/**
 * Scalable volume controls for the production-like database seeders.
 *
 * Every count is env-overridable so the dataset can grow without code changes,
 * e.g. `SEED_STUDENTS=2000 php artisan migrate:fresh --seed`.
 */
return [
    // Bulk account counts (in addition to the four canonical demo accounts).
    // NOTE: the student body is NOT a flat count. StudentSeeder derives the
    // head-count of every (department, semester) bucket from the academic load
    // plan (see Concerns\PlansAcademicLoad) so each offered section fills to
    // target_section_size. faculty/admins remain flat counts.
    'faculty' => (int) env('SEED_FACULTY', 80),
    'admins' => (int) env('SEED_ADMINS', 10),

    // Sectioning & enrollment.
    // Hard ceiling stored on each section (max_enrollment); must be ≥ the target
    // fill below so the 40–50 band keeps a little headroom.
    'section_capacity' => (int) env('SEED_SECTION_CAPACITY', 50),
    // The number of students each section is filled to. The plan sizes the
    // student body and the per-course section count around this so every section
    // lands in the realistic 40–50 band.
    'target_section_size' => (int) env('SEED_SECTION_TARGET', 45),
    // Sections per course for an active bucket: popular departments (weight ≥ 4)
    // use the max, the rest use the min. Capped at the A–J label set (≤ 10).
    'sections_per_course_min' => (int) env('SEED_SECTIONS_MIN', 3),
    'sections_per_course_max' => (int) env('SEED_SECTIONS_MAX', 4),
    // Each student registers for 4–5 of their (department, semester) courses.
    'min_courses_per_student' => (int) env('SEED_MIN_COURSES', 4),
    'max_courses_per_student' => (int) env('SEED_MAX_COURSES', 5),
    // How many cohort students fill each current-term demo course's Section A
    // (DemoCourseRosterSeeder). Kept below the demo sections' capacity of 60 so
    // the self-registration tests keep room to register the demo student.
    'demo_roster_size' => (int) env('SEED_DEMO_ROSTER', 40),
    // How many semesters per department actually receive students. Fewer active
    // semesters → denser buckets → fuller sections (the "concentrate" strategy).
    'active_semesters_per_department' => (int) env('SEED_ACTIVE_SEMESTERS', 2),

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
