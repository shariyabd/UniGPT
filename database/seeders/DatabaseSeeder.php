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
            ClassTestSeeder::class,      // faculty-authored, section-isolated class tests
            ClassTestAttemptSeeder::class, // submitted attempts (feeds Learning Analytics + Leaderboard XP)
            AttendanceSeeder::class,
            NoteSeeder::class,
            TaskSeeder::class,
            FlashcardSeeder::class,      // personal flashcard decks (demo student + sample cohort)
            LeaderboardSeeder::class,    // leaderboard opt-ins + aliases (XP derived from data above)
            DiscussionSeeder::class,     // section discussion feed: posts, replies, likes, reports
            AnnouncementSeeder::class, // admin broadcasts → per-recipient notifications

            // AI provider config (OpenAI key from .env) — must precede the
            // knowledge base so document embeddings use the configured provider.
            AISettingsSeeder::class,

            // RAG knowledge base (preserved).
            KnowledgeBaseSeeder::class,

            // Demo student's own document submissions (PENDING approval queue).
            StudentDocumentSeeder::class,

            // Fills the current-term demo courses' Section A with a realistic
            // cohort so demo faculty rosters look full (the bulk seeders exclude
            // demo courses to keep the test fixtures exact). Runs last so it only
            // adds enrollments and disturbs nothing earlier.
            DemoCourseRosterSeeder::class,

            // Backfills demo data for the recent feature waves (practice quizzes,
            // question bank, peer review, course feedback, office hours, proctoring
            // snapshots, prerequisites/waitlist). Runs last: it needs the demo
            // cohort and the student's class-test attempts to already exist.
            DemoFeatureShowcaseSeeder::class,

            // Achievements/badges — evaluated from all the signals above, for the
            // demo student + leaderboard opt-ins. Runs after the showcase seeder.
            AchievementSeeder::class,

            // Demo page-visit history for Admin → User Activity (only if empty).
            VisitSeeder::class,
        ]);
    }
}
