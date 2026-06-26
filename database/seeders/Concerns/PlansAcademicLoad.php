<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Support\Collection;

/**
 * The single source of truth for how dense the bulk academic dataset is.
 *
 * Student / Section / Enrollment seeders all derive their volumes from the SAME
 * per-bucket plan so the three stay consistent: every offered
 * (department → semester → course → section) ends up with ~target_section_size
 * (40–50) students.
 *
 * A "bucket" is one active (department, semester) pair. For each bucket the plan
 * fixes the number of sections per course (Sec) and the student head-count (B)
 * needed to fill every one of those sections to the target size:
 *
 *     seats per bucket      = courses(C) × Sec × targetSize
 *     enrollments per student ≈ avgLoad   (mean of min/max courses per student)
 *     students  B           = seats / avgLoad = round(Sec × targetSize × C / avgLoad)
 *
 * EnrollmentSeeder then spreads each course's enrollers evenly across its Sec
 * sections, so each section lands on ~targetSize. Demo courses (owned by
 * AcademicSeeder) are excluded everywhere so the hand-crafted demo rosters stay
 * authoritative.
 */
trait PlansAcademicLoad
{
    /**
     * Relative department popularity (slug => weight). Popular departments
     * (weight ≥ 4) run the upper section count per course; the rest run the
     * lower one. Unlisted departments default to weight 1.
     */
    private const DEPARTMENT_WEIGHTS = [
        'computer-science-engineering' => 5,
        'business-administration' => 4,
        'electrical-engineering' => 3,
        'mechanical-engineering' => 3,
        'civil-engineering' => 2,
        'psychology' => 2,
        'mathematics' => 2,
    ];

    /**
     * Build the per-bucket plan, keyed by "departmentId-semester".
     *
     * @return Collection<string, array{
     *     department_id:int, semester:int, course_count:int,
     *     sections_per_course:int, target_section_size:int, student_count:int
     * }>
     */
    protected function academicLoadPlan(): Collection
    {
        $demoCodes = (array) config('seeder.demo_course_codes', []);
        $targetSize = max(1, (int) config('seeder.target_section_size', 45));
        $minSections = max(1, (int) config('seeder.sections_per_course_min', 3));
        $maxSections = max($minSections, (int) config('seeder.sections_per_course_max', 4));
        $minCourses = max(1, (int) config('seeder.min_courses_per_student', 4));
        $maxCourses = max($minCourses, (int) config('seeder.max_courses_per_student', 5));
        $activeSemesters = max(1, (int) config('seeder.active_semesters_per_department', 2));
        $avgLoad = ($minCourses + $maxCourses) / 2;

        // Catalog courses only (demo courses keep their own hand-crafted sections).
        $coursesByDepartment = Course::query()
            ->whereNotIn('code', $demoCodes)
            ->whereNotNull('department_id')
            ->whereNotNull('semester')
            ->get(['id', 'department_id', 'semester'])
            ->groupBy('department_id');

        $weightBySlug = self::DEPARTMENT_WEIGHTS;
        $slugById = Department::pluck('slug', 'id');

        $plan = collect();

        foreach ($coursesByDepartment as $departmentId => $courses) {
            // Concentrate students into the lowest N offered semesters so each
            // active bucket is dense enough to fill full sections.
            $semesters = $courses->pluck('semester')->unique()->sort()->values()->all();
            $active = array_slice($semesters, 0, $activeSemesters);

            $weight = $weightBySlug[$slugById[$departmentId] ?? ''] ?? 1;
            $sectionsPerCourse = $weight >= 4 ? $maxSections : $minSections;

            foreach ($active as $semester) {
                $courseCount = $courses->where('semester', $semester)->count();
                if ($courseCount === 0) {
                    continue;
                }

                $studentCount = (int) round(
                    $sectionsPerCourse * $targetSize * $courseCount / $avgLoad
                );

                $plan->put((int) $departmentId.'-'.(int) $semester, [
                    'department_id' => (int) $departmentId,
                    'semester' => (int) $semester,
                    'course_count' => $courseCount,
                    'sections_per_course' => $sectionsPerCourse,
                    'target_section_size' => $targetSize,
                    'student_count' => $studentCount,
                ]);
            }
        }

        return $plan;
    }
}
