<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Section;
use App\Models\Term;
use Database\Seeders\Concerns\SeedsUsers;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fills the current-term demo courses' Section A with a realistic cohort so the
 * demo faculty's roster pages show a full class, matching the bulk catalog
 * courses. Demo courses are otherwise excluded from bulk enrollment (to keep the
 * automated demo-roster fixtures exact), so this seeder is the ONE place that
 * adds non-demo students to them — and it does so without disturbing anything the
 * test suite relies on:
 *
 *  - Only Section A is touched. CS301 Section B (used by the assignment/
 *    enrollment tests) and the A–J label space stay untouched, so no new sections
 *    are created on demo courses.
 *  - Sections are filled to demo_roster_size (≪ the demo sections' capacity of
 *    60), leaving ample headroom for the self-registration tests that register
 *    the demo student into CS330.
 *  - The demo student's own enrolled / pending / completed rows are never
 *    altered — only brand-new cohort students are inserted.
 *
 * Cohort students are CSE students in the demo courses' own semester, each taking
 * that semester's full demo-course load — exactly how a real cohort enrolls.
 */
class DemoCourseRosterSeeder extends Seeder
{
    use SeedsUsers;

    /** semester => demo course codes offered in the current term for that semester. */
    private const DEMO_COURSES_BY_SEMESTER = [
        5 => ['CS301', 'CS305', 'CS310', 'CS330', 'CS340'],
        6 => ['CS320'],
    ];

    public function run(): void
    {
        $term = Term::currentTerm();

        if (! $term) {
            $this->command->warn('   No current term; run TermSeeder first.');

            return;
        }

        if (DB::table('users')->where('email', 'demo.cohort.5.1@university.edu')->exists()) {
            $this->command->info('   Demo course rosters already seeded; skipping.');

            return;
        }

        $departmentId = Course::whereIn('code', ['CS301'])->value('department_id');

        if (! $departmentId) {
            $this->command->warn('   Demo courses missing; run AcademicSeeder first.');

            return;
        }

        $rosterSize = max(1, (int) config('seeder.demo_roster_size', 40));
        $now = Carbon::now();

        $enrollments = [];
        $globalIndex = 0;
        $cohortCount = 0;

        foreach (self::DEMO_COURSES_BY_SEMESTER as $semester => $codes) {
            // Section A of each demo course offered this term in this semester.
            $sectionByCode = Section::query()
                ->where('term_id', $term->id)
                ->where('label', 'A')
                ->whereHas('course', fn ($q) => $q->whereIn('code', $codes))
                ->with('course:id,code')
                ->get()
                ->keyBy(fn (Section $section) => $section->course->code);

            if ($sectionByCode->isEmpty()) {
                continue;
            }

            // Build this semester's cohort.
            $rows = [];
            for ($n = 1; $n <= $rosterSize; $n++) {
                $globalIndex++;

                $rows[] = [
                    'name' => fake()->name(),
                    'email' => "demo.cohort.{$semester}.{$n}@university.edu",
                    'department_id' => $departmentId,
                    'student_id' => '2024'.str_pad((string) $globalIndex, 6, '0', STR_PAD_LEFT),
                    'semester' => $semester,
                ];
            }

            $this->createUsersWithRole($rows, UserRole::STUDENT);
            $cohortCount += count($rows);

            $cohortIds = DB::table('users')
                ->whereIn('email', array_column($rows, 'email'))
                ->pluck('id');

            // Enroll every cohort member into Section A of each demo course of
            // their semester (a full, realistic course load).
            foreach ($sectionByCode as $section) {
                foreach ($cohortIds as $userId) {
                    $enrollments[] = [
                        'course_id' => $section->course_id,
                        'section_id' => $section->id,
                        'user_id' => $userId,
                        'term_id' => $term->id,
                        'role' => 'student',
                        'status' => 'enrolled',
                        'grade' => null,
                        'progress' => random_int(20, 95),
                        'enrolled_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($enrollments, 1000) as $chunk) {
            DB::table('course_user')->insert($chunk);
        }

        $this->command->info(
            "   ✓ Demo course rosters seeded ({$cohortCount} students, ".count($enrollments).' enrollments)'
        );
    }
}
