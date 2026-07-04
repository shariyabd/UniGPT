<?php

namespace App\Domain\Analytics\Services;

use App\Domain\Academic\Services\AttendanceService;
use App\Domain\User\Models\User;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Faculty-facing learning analytics and academic reporting.
 *
 * Aggregates real attendance, grading and enrolment data for the courses a
 * faculty member teaches — per-course detail plus a cross-course overview.
 */
class FacultyAnalyticsService
{
    /** Attendance rate below which a student is flagged at-risk. */
    private const AT_RISK_ATTENDANCE = 75;

    /** Grade letters that flag a student at-risk. */
    private const AT_RISK_GRADES = ['D+', 'D', 'D-', 'F'];

    public function __construct(
        private readonly AttendanceService $attendance,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $faculty, ?int $courseId = null): array
    {
        // Analytics are scoped to the sections this faculty teaches, so every
        // figure (roster, grades, attendance, submissions) reflects only their
        // own section(s) — never another instructor's section of the course.
        $sections = $faculty->teachingSections()->with('course')->get();
        $courses = $sections->pluck('course')->filter()->unique('id')->sortBy('code')->values();
        $sectionIdsByCourse = $sections->groupBy('course_id')->map(fn (Collection $g) => $g->pluck('id'));

        // A null course means "All courses" (the default), so the detail report
        // aggregates every section the faculty teaches rather than silently
        // collapsing to the first course.
        $selected = $courseId ? $courses->firstWhere('id', $courseId) : null;

        return [
            'courses' => $courses->map(fn (Course $c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
            ])->values(),
            'overview' => $this->overview($faculty, $courses, $sectionIdsByCourse),
            'selectedCourseId' => $selected?->id,
            'report' => $this->buildReport($selected, $courses, $sectionIdsByCourse),
        ];
    }

    /**
     * Build the detail report for the selected course, or an aggregate across
     * every section the faculty teaches when no course is selected ("All").
     *
     * @param  Collection<int, Course>  $courses
     * @param  Collection<int, Collection<int, int>>  $sectionIdsByCourse
     * @return array<string, mixed>|null
     */
    private function buildReport(?Course $selected, Collection $courses, Collection $sectionIdsByCourse): ?array
    {
        if ($courses->isEmpty()) {
            return null;
        }

        if ($selected) {
            $sectionIds = $sectionIdsByCourse->get($selected->id, collect());
            $students = $selected->students()->wherePivotIn('section_id', $sectionIds)->get();

            return $this->compileReport(
                course: ['id' => $selected->id, 'code' => $selected->code, 'name' => $selected->name],
                sectionIds: $sectionIds,
                students: $students,
            );
        }

        // "All courses": flatten every section the faculty teaches and merge each
        // course's roster (one row per enrolment, so grade counts stay accurate).
        $sectionIds = $sectionIdsByCourse->flatten()->values();
        $students = $courses->flatMap(
            fn (Course $course) => $course->students()
                ->wherePivotIn('section_id', $sectionIdsByCourse->get($course->id, collect()))
                ->get()
        )->values();

        return $this->compileReport(
            course: [
                'id' => null,
                'code' => 'All Courses',
                'name' => "{$courses->count()} courses · {$sectionIds->count()} sections",
            ],
            sectionIds: $sectionIds,
            students: $students,
        );
    }

    /**
     * Cross-course summary for the faculty member.
     *
     * @param  Collection<int, Course>  $courses
     * @param  Collection<int, Collection<int, int>>  $sectionIdsByCourse
     * @return array<string, mixed>
     */
    private function overview(User $faculty, Collection $courses, Collection $sectionIdsByCourse): array
    {
        $totalStudents = 0;
        $attendanceRates = collect();

        foreach ($courses as $course) {
            $sectionIds = $sectionIdsByCourse->get($course->id, collect());
            $totalStudents += $course->students()->wherePivotIn('section_id', $sectionIds)->count();
            $rate = $this->attendance->summaryForSections($sectionIds)['rate'];
            if ($rate !== null) {
                $attendanceRates->push($rate);
            }
        }

        return [
            'courses' => $courses->count(),
            'totalStudents' => $totalStudents,
            'averageAttendance' => $attendanceRates->isEmpty() ? null : (int) round($attendanceRates->avg()),
            'pendingGrading' => $this->pendingGradingCount($faculty),
        ];
    }

    /**
     * Detailed academic report for a roster + section scope. Works for a single
     * course or the aggregated "All courses" view — the caller supplies the
     * already-resolved section ids and student roster.
     *
     * @param  array{id: int|null, code: string, name: string}  $course
     * @param  Collection<int, int>  $sectionIds
     * @param  Collection<int, User>  $students
     * @return array<string, mixed>
     */
    private function compileReport(array $course, Collection $sectionIds, Collection $students): array
    {
        $gradeDistribution = $this->gradeDistribution($students);
        $submissionStats = $this->submissionStats($sectionIds);
        $attendanceSummary = $this->attendance->summaryForSections($sectionIds);

        return [
            'course' => $course,
            'enrolled' => $students->count(),
            'attendance' => $attendanceSummary,
            'gradeDistribution' => $gradeDistribution,
            'averageScore' => $submissionStats['averageScore'],
            'submissions' => [
                'total' => $submissionStats['total'],
                'graded' => $submissionStats['graded'],
                'pending' => $submissionStats['pending'],
                'completionRate' => $submissionStats['completionRate'],
            ],
            'atRisk' => $this->atRiskStudents($sectionIds, $students),
        ];
    }

    /**
     * Letter-grade histogram across enrolled students.
     *
     * @param  Collection<int, User>  $students
     * @return array<int, array{grade: string, count: int}>
     */
    private function gradeDistribution(Collection $students): array
    {
        $order = ['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'F'];

        $counts = $students
            ->map(fn (User $s) => $s->pivot->grade)
            ->filter()
            ->countBy()
            ->all();

        return collect($order)
            ->filter(fn (string $grade) => isset($counts[$grade]))
            ->map(fn (string $grade) => ['grade' => $grade, 'count' => (int) $counts[$grade]])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, int>  $sectionIds
     * @return array<string, mixed>
     */
    private function submissionStats(Collection $sectionIds): array
    {
        // Aggregate in SQL rather than hydrating every submission — counts and the
        // mean score are computed by the database, so memory stays flat regardless
        // of how many submissions the faculty's sections hold.
        $base = AssignmentSubmission::whereHas('assignment', fn (Builder $q) => $q->whereIn('section_id', $sectionIds));

        $total = (clone $base)->count();
        $graded = (clone $base)->whereNotNull('grade')->count();

        $averageScore = AssignmentSubmission::query()
            ->join('assignments', 'assignments.id', '=', 'assignment_submissions.assignment_id')
            ->whereIn('assignments.section_id', $sectionIds)
            ->whereNotNull('assignment_submissions.grade')
            ->where('assignments.total_points', '>', 0)
            ->avg(DB::raw('assignment_submissions.grade / assignments.total_points * 100'));

        return [
            'total' => $total,
            'graded' => $graded,
            'pending' => $total - $graded,
            'completionRate' => $total > 0
                ? (int) round($graded / $total * 100)
                : null,
            'averageScore' => $averageScore === null ? null : (int) round((float) $averageScore),
        ];
    }

    /**
     * Students flagged for low attendance or a poor grade.
     *
     * @param  Collection<int, int>  $sectionIds
     * @param  Collection<int, User>  $students
     * @return array<int, array<string, mixed>>
     */
    private function atRiskStudents(Collection $sectionIds, Collection $students): array
    {
        // Aggregate attendance per student in SQL (one row per student) instead of
        // loading every attendance record into memory. "Present" is any status
        // other than absent, mirroring AttendanceStatus::countsAsPresent().
        $stats = AttendanceRecord::query()
            ->whereIn('section_id', $sectionIds)
            ->selectRaw('user_id, COUNT(*) as total, SUM(CASE WHEN status <> ? THEN 1 ELSE 0 END) as attended', ['absent'])
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        return $students
            ->map(function (User $student) use ($stats) {
                $row = $stats->get($student->id);
                $total = (int) ($row->total ?? 0);
                $attended = (int) ($row->attended ?? 0);
                $rate = $total > 0 ? (int) round($attended / $total * 100) : null;
                $grade = $student->pivot->grade;

                $reasons = [];
                if ($rate !== null && $rate < self::AT_RISK_ATTENDANCE) {
                    $reasons[] = "Attendance {$rate}%";
                }
                if ($grade !== null && in_array($grade, self::AT_RISK_GRADES, true)) {
                    $reasons[] = "Grade {$grade}";
                }

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'studentId' => $student->student_id,
                    'attendanceRate' => $rate,
                    'grade' => $grade,
                    'reasons' => $reasons,
                ];
            })
            ->filter(fn (array $row) => $row['reasons'] !== [])
            ->values()
            ->all();
    }

    private function pendingGradingCount(User $faculty): int
    {
        return AssignmentSubmission::whereNull('grade')
            ->whereHas('assignment', fn (Builder $q) => $q->whereIn('section_id', $faculty->teachingSectionIds()))
            ->count();
    }
}
