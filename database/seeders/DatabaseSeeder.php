<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order follows the dependency graph (see seeder-plan.md):
     * RBAC/terms first, then the demo fixture, then the production-like
     * population layered on top, then the RAG knowledge base.
     */
    public function run(): void
    {
        $this->call([
            // Foundation + demo/test fixtures (preserved verbatim).
            RBACSeeder::class,          // roles, permissions, departments, demo users
            TermSeeder::class,          // academic terms (one current)
            AcademicSeeder::class,      // demo student's hand-crafted academic data

            // Production-like population.
            CourseSeeder::class,        // CSE curriculum + department catalogs
            FacultySeeder::class,       // bulk faculty
            AdminSeeder::class,         // bulk admins
            StudentSeeder::class,       // bulk students
            SectionSeeder::class,       // sections sized to demand
            EnrollmentSeeder::class,    // students → sections (current term)
            CourseMaterialSeeder::class,
            ExamSeeder::class,
            AttendanceSeeder::class,
            NoteSeeder::class,
            TaskSeeder::class,
            AnnouncementSeeder::class, // admin broadcasts → per-recipient notifications

            // RAG knowledge base (preserved).
            KnowledgeBaseSeeder::class,
        ]);
    }
}
