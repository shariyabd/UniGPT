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

/**
 * Creates current-term sections for catalog courses in the active buckets, sized
 * by the shared academic load plan: each course in an active (department,
 * semester) bucket gets exactly `sections_per_course` sections, which
 * EnrollmentSeeder then fills evenly to the target size (40–50 students each).
 *
 * Courses outside the active buckets (higher, unstaffed semesters) are simply
 * not offered this term, so no empty sections are created. Demo courses already
 * carry their own hand-crafted sections (e.g. CS301 A/B) and are skipped.
 *
 * Each section is assigned a teaching faculty from the course's OWN department
 * (no cross-department fallback — that invariant is enforced elsewhere too), and
 * the course's primary instructor (faculty_id) is backfilled from section A.
 */
class SectionSeeder extends Seeder
{
    use PlansAcademicLoad;

    public function run(): void
    {
        $term = Term::currentTerm();

        if (! $term) {
            $this->command->warn('   No current term; run TermSeeder first.');

            return;
        }

        $capacity = max(
            (int) config('seeder.target_section_size', 45),
            (int) config('seeder.section_capacity', 50),
        );
        $demoCodes = (array) config('seeder.demo_course_codes', []);

        $plan = $this->academicLoadPlan();

        if ($plan->isEmpty()) {
            $this->command->warn('   No catalog courses; run CourseSeeder first.');

            return;
        }

        // Teaching faculty grouped by department, round-robined within each
        // department so the load is spread across its faculty.
        $facultyByDepartment = User::withRole(UserRole::FACULTY)
            ->get(['id', 'department_id'])
            ->groupBy('department_id');
        $facultyCursor = [];

        // Catalog courses in the active buckets, grouped by bucket key.
        $coursesByBucket = Course::query()
            ->whereNotIn('code', $demoCodes)
            ->whereNotNull('department_id')
            ->whereNotNull('semester')
            ->doesntHave('sections')
            ->get()
            ->groupBy(fn (Course $course) => $course->department_id.'-'.$course->semester);

        $created = 0;

        foreach ($plan as $key => $bucket) {
            $courses = $coursesByBucket->get($key);
            if (! $courses) {
                continue;
            }

            $deptFaculty = $facultyByDepartment->get($bucket['department_id'])?->pluck('id')->all() ?: [];

            foreach ($courses as $course) {
                for ($s = 0; $s < $bucket['sections_per_course']; $s++) {
                    $facultyId = $this->nextFaculty($bucket['department_id'], $deptFaculty, $facultyCursor);

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
        }

        // Every catalog course must still have at least one section (UI and tests
        // rely on it). Courses outside the active buckets — higher, not-yet-offered
        // semesters with no student cohort — get a single empty section rather than
        // the active 3–4, so they carry no misleading thin/half-full sections.
        $created += $this->seedFallbackSections($term, $capacity, $demoCodes, $facultyByDepartment, $facultyCursor);

        $this->command->info("   ✓ Sections seeded ({$created} created for catalog courses)");
    }

    /**
     * Give one section to every catalog course that the bucket pass left without
     * any (not-offered-this-term semesters), preserving the "every course has a
     * section" invariant without creating empty multi-section noise.
     *
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, User>>  $facultyByDepartment
     * @param  array<int, int>  $facultyCursor
     */
    private function seedFallbackSections(
        Term $term,
        int $capacity,
        array $demoCodes,
        $facultyByDepartment,
        array &$facultyCursor,
    ): int {
        $created = 0;

        Course::query()
            ->whereNotIn('code', $demoCodes)
            ->doesntHave('sections')
            ->chunkById(200, function ($courses) use ($term, $capacity, $facultyByDepartment, &$facultyCursor, &$created) {
                foreach ($courses as $course) {
                    $deptFaculty = $facultyByDepartment->get($course->department_id)?->pluck('id')->all() ?: [];
                    $facultyId = $this->nextFaculty((int) $course->department_id, $deptFaculty, $facultyCursor);

                    $section = Section::create([
                        'course_id' => $course->id,
                        'term_id' => $term->id,
                        'faculty_id' => $facultyId,
                        'label' => 'A',
                        'schedule' => $course->schedule,
                        'max_enrollment' => $capacity,
                        'is_active' => true,
                    ]);

                    if ($course->faculty_id === null) {
                        $course->update(['faculty_id' => $section->faculty_id]);
                    }

                    $created++;
                }
            });

        return $created;
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
