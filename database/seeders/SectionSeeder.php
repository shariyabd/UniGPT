<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\User\Models\User;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Database\Seeder;

/**
 * Creates current-term sections for catalog courses that have none, sizing the
 * number of sections to the student demand in each (department, semester) bucket
 * so EnrollmentSeeder never overflows a section. Demo courses already carry their
 * sections (e.g. CS301 A/B) and are skipped, preserving their exact section sets.
 *
 * Each section is assigned a teaching faculty from the course's department, and
 * the course's primary instructor (faculty_id) is backfilled from section A.
 */
class SectionSeeder extends Seeder
{
    public function run(): void
    {
        $term = Term::currentTerm();

        if (! $term) {
            $this->command->warn('   No current term; run TermSeeder first.');

            return;
        }

        $capacity = (int) config('seeder.section_capacity', 45);

        // Student demand per (department_id, semester) bucket.
        $demand = User::withRole(UserRole::STUDENT)
            ->get(['id', 'department_id', 'semester'])
            ->groupBy(fn (User $u) => $u->department_id.'-'.$u->semester)
            ->map->count();

        // Teaching faculty grouped by department, plus a global fallback pool.
        $facultyByDepartment = User::withRole(UserRole::FACULTY)
            ->get(['id', 'department_id'])
            ->groupBy('department_id');
        $facultyFallback = $facultyByDepartment->flatten(1)->pluck('id')->all();
        $facultyCursor = [];

        $created = 0;

        Course::doesntHave('sections')->with([])->chunkById(100, function ($courses) use (
            $term, $capacity, $demand, $facultyByDepartment, $facultyFallback, &$facultyCursor, &$created
        ) {
            foreach ($courses as $course) {
                $bucketDemand = (int) ($demand[$course->department_id.'-'.$course->semester] ?? 0);
                $sectionsNeeded = max(1, (int) ceil($bucketDemand / max(1, $capacity)));

                $deptFaculty = $facultyByDepartment->get($course->department_id)?->pluck('id')->all()
                    ?: $facultyFallback;

                for ($s = 0; $s < $sectionsNeeded; $s++) {
                    $facultyId = $this->nextFaculty($course->department_id, $deptFaculty, $facultyCursor);

                    $section = Section::create([
                        'course_id' => $course->id,
                        'term_id' => $term->id,
                        'faculty_id' => $facultyId,
                        'label' => chr(65 + $s), // A, B, C, ...
                        'schedule' => $course->schedule,
                        'max_enrollment' => $capacity,
                        'is_active' => true,
                    ]);

                    if ($s === 0 && $course->faculty_id === null) {
                        $course->update(['faculty_id' => $section->faculty_id]);
                    }

                    $created++;
                }
            }
        });

        $this->command->info("   ✓ Sections seeded ({$created} created for catalog courses)");
    }

    /**
     * Round-robin a faculty id within a department.
     *
     * @param  array<int, int>  $facultyIds
     * @param  array<int, int>  $cursor
     */
    private function nextFaculty(int $departmentId, array $facultyIds, array &$cursor): ?int
    {
        if ($facultyIds === []) {
            return null;
        }

        $index = ($cursor[$departmentId] ?? 0) % count($facultyIds);
        $cursor[$departmentId] = $index + 1;

        return $facultyIds[$index];
    }
}
