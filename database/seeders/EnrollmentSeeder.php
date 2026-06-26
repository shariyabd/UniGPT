<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\User\Models\User;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Section;
use App\Models\Term;
use Database\Seeders\Concerns\PlansAcademicLoad;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Registers every bulk student for a 4–5 course load in their (department,
 * semester) bucket and spreads each course's enrollers EVENLY across that
 * course's sections, so every section fills to ~target_section_size (40–50).
 *
 * Two deliberate choices make the fill uniform (the old fill-section-A-first
 * approach left B/C sections with 1–2 students):
 *
 *  1. Course load is a cyclic block — student i takes courses
 *     [i, i+1, … i+load-1] (mod course-count) — so every course in the bucket
 *     receives a near-identical number of enrollers.
 *  2. Each enroller is placed into the course's least-filled section, so the
 *     Sec sections of a course grow in lock-step toward the target size.
 *
 * Demo courses / students (config seeder.demo_*) are excluded so the demo
 * student's hand-crafted rosters remain authoritative. Every row carries
 * section_id + term_id.
 */
class EnrollmentSeeder extends Seeder
{
    use PlansAcademicLoad;

    public function run(): void
    {
        $term = Term::currentTerm();

        if (! $term) {
            $this->command->warn('   No current term; run TermSeeder first.');

            return;
        }

        $demoCodes = (array) config('seeder.demo_course_codes', []);
        $demoEmails = (array) config('seeder.demo_emails', []);
        $minCourses = max(1, (int) config('seeder.min_courses_per_student', 4));
        $maxCourses = max($minCourses, (int) config('seeder.max_courses_per_student', 5));
        $now = Carbon::now();

        $students = User::withRole(UserRole::STUDENT)
            ->whereNotIn('email', $demoEmails)
            ->get(['id', 'department_id', 'semester'])
            ->groupBy(fn (User $u) => $u->department_id.'-'.$u->semester);

        if ($students->isEmpty()) {
            $this->command->warn('   No bulk students; run StudentSeeder first.');

            return;
        }

        // Catalog sections for the current term, grouped by course, ordered by
        // label so section A is index 0, B is 1, ….
        $sectionsByCourse = Section::query()
            ->where('term_id', $term->id)
            ->whereHas('course', fn ($q) => $q->whereNotIn('code', $demoCodes))
            ->orderBy('label')
            ->get(['id', 'course_id', 'max_enrollment'])
            ->groupBy('course_id');

        // Catalog courses per bucket, ordered by id for a stable cyclic block.
        $coursesByBucket = Course::query()
            ->whereNotIn('code', $demoCodes)
            ->whereNotNull('department_id')
            ->whereNotNull('semester')
            ->orderBy('id')
            ->get(['id', 'department_id', 'semester'])
            ->groupBy(fn (Course $c) => $c->department_id.'-'.$c->semester);

        $rows = [];
        $enrolled = 0;
        // Running enrollment count per section id, so each course's sections fill
        // evenly and never exceed their capacity.
        $sectionFill = [];

        foreach ($students as $bucket => $bucketStudents) {
            $courses = $coursesByBucket->get($bucket);
            if (! $courses || $courses->isEmpty()) {
                continue;
            }

            $courseList = $courses->values();
            $courseCount = $courseList->count();

            foreach ($bucketStudents->values() as $i => $student) {
                // Cyclic block of 4–5 distinct courses (alternating, averaging
                // 4.5) starting at a rotating offset, so every course in the
                // bucket gets a near-uniform share of enrollers.
                $load = min($courseCount, $i % 2 === 0 ? $maxCourses : $minCourses);

                for ($d = 0; $d < $load; $d++) {
                    $course = $courseList[($i + $d) % $courseCount];
                    $sections = $sectionsByCourse->get($course->id);
                    if (! $sections || $sections->isEmpty()) {
                        continue;
                    }

                    // Least-filled section that still has capacity.
                    $target = $sections
                        ->filter(fn (Section $s) => ($sectionFill[$s->id] ?? 0) < $s->max_enrollment)
                        ->sortBy(fn (Section $s) => $sectionFill[$s->id] ?? 0)
                        ->first();

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
