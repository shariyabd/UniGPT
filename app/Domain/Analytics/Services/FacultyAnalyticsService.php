<?php

namespace App\Domain\Analytics\Services;

use App\Domain\Academic\Services\AttendanceService;
use App\Domain\User\Models\User;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
        $selected = $courseId ? $courses->firstWhere('id', $courseId) : $courses->first();

        return [
            'courses' => $courses->map(fn (Course $c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
            ])->values(),
            'overview' => $this->overview($faculty, $courses, $sectionIdsByCourse),
            'selectedCourseId' => $selected?->id,
            'report' => $selected
                ? $this->courseReport($selected, $sectionIdsByCourse->get($selected->id, collect()))
                : null,
        ];
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
     * Detailed academic report for a single course, scoped to the faculty's sections.
     *
     * @param  Collection<int, int>  $sectionIds
     * @return array<string, mixed>
     */
    private function courseReport(Course $course, Collection $sectionIds): array
    {
        $students = $course->students()->wherePivotIn('section_id', $sectionIds)->get();
        $gradeDistribution = $this->gradeDistribution($students);
        $submissionStats = $this->submissionStats($sectionIds);
        $attendanceSummary = $this->attendance->summaryForSections($sectionIds);

        return [
            'course' => [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
            ],
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
        $submissions = AssignmentSubmission::whereHas('assignment', fn (Builder $q) => $q->whereIn('section_id', $sectionIds))
            ->with('assignment:id,total_points')
            ->get();

        $graded = $submissions->whereNotNull('grade');

        $percentages = $graded
            ->map(function (AssignmentSubmission $s) {
                $total = $s->assignment?->total_points;

                return $total ? ((float) $s->grade / $total) * 100 : null;
            })
            ->filter();

        return [
            'total' => $submissions->count(),
            'graded' => $graded->count(),
            'pending' => $submissions->count() - $graded->count(),
            'completionRate' => $submissions->count() > 0
                ? (int) round($graded->count() / $submissions->count() * 100)
                : null,
            'averageScore' => $percentages->isEmpty() ? null : (int) round($percentages->avg()),
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
        $records = AttendanceRecord::whereIn('section_id', $sectionIds)->get()->groupBy('user_id');

        return $students
            ->map(function (User $student) use ($records) {
                $studentRecords = $records->get($student->id, collect());
                $total = $studentRecords->count();
                $attended = $studentRecords->filter(fn ($r) => $r->status->countsAsPresent())->count();
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
