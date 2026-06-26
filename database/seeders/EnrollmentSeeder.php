<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\User\Models\User;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Registers every bulk student for the full course load of their department and
 * semester in the current term. Each student takes one section per course, filled
 * section-by-section so no section exceeds its capacity (SectionSeeder sized the
 * sections to this exact demand). Every row carries section_id + term_id.
 *
 * Demo courses (config seeder.demo_course_codes) are excluded so the demo
 * student's hand-crafted rosters remain authoritative.
 */
class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $term = Term::currentTerm();

        if (! $term) {
            $this->command->warn('   No current term; run TermSeeder first.');

            return;
        }

        $demoCodes = (array) config('seeder.demo_course_codes', []);
        $demoEmails = (array) config('seeder.demo_emails', []);
        $now = Carbon::now();

        $students = User::withRole(UserRole::STUDENT)
            ->whereNotIn('email', $demoEmails)
            ->get(['id', 'department_id', 'semester'])
            ->groupBy(fn (User $u) => $u->department_id.'-'.$u->semester);

        if ($students->isEmpty()) {
            $this->command->warn('   No bulk students; run StudentSeeder first.');

            return;
        }

        // Catalog sections for the current term, grouped by course, ordered by label.
        $sectionsByCourse = Section::query()
            ->where('term_id', $term->id)
            ->whereHas('course', fn ($q) => $q->whereNotIn('code', $demoCodes))
            ->orderBy('label')
            ->get(['id', 'course_id', 'max_enrollment'])
            ->groupBy('course_id');

        $coursesByBucket = Course::query()
            ->whereNotIn('code', $demoCodes)
            ->whereNotNull('department_id')
            ->whereNotNull('semester')
            ->get(['id', 'department_id', 'semester'])
            ->groupBy(fn (Course $c) => $c->department_id.'-'.$c->semester);

        $minCourses = max(1, (int) config('seeder.min_courses_per_student', 4));
        $maxCourses = max($minCourses, (int) config('seeder.max_courses_per_student', 5));

        $rows = [];
        $enrolled = 0;
        // Running enrollment count per section, so we never exceed a section's
        // capacity and sections fill (A, then B, …) toward their target size.
        $sectionFill = [];

        foreach ($students as $bucket => $bucketStudents) {
            $courses = $coursesByBucket->get($bucket);
            if (! $courses) {
                continue;
            }

            foreach ($bucketStudents as $student) {
                // Each student registers for 4–5 of their bucket's courses (or all
                // of them, when the bucket offers fewer).
                $take = min($courses->count(), random_int($minCourses, $maxCourses));
                $chosen = $courses->shuffle()->take($take);

                foreach ($chosen as $course) {
                    $sections = $sectionsByCourse->get($course->id);
                    if (! $sections) {
                        continue;
                    }

                    // First section of this course with remaining capacity.
                    $target = $sections->first(
                        fn (Section $section) => ($sectionFill[$section->id] ?? 0) < $section->max_enrollment
                    );

                    if (! $target) {
                        continue; // every section full — the student takes fewer courses
                    }

                    $sectionFill[$target->id] = ($sectionFill[$target->id] ?? 0) + 1;

                    $rows[] = [
                        'course_id' => $course->id,
                        'section_id' => $target->id,
                        'user_id' => $student->id,
                        'term_id' => $term->id,
                        'role' => 'student',
                        'status' => 'enrolled',
                        'grade' => null,
                        'progress' => random_int(20, 95),
                        'enrolled_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $enrolled++;
                }
            }
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('course_user')->insert($chunk);
        }

        $this->command->info("   ✓ Enrollments seeded ({$enrolled} registrations)");
    }
}
