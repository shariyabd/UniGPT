<?php

namespace App\Domain\Analytics\Services;

use App\Domain\Academic\Services\AttendanceService;
use App\Domain\User\Models\User;
use App\Models\AssignmentSubmission;
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
        $courses = $faculty->teachingCourses()->orderBy('code')->get();
        $selected = $courseId ? $courses->firstWhere('id', $courseId) : $courses->first();

        return [
            'courses' => $courses->map(fn (Course $c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
            ])->values(),
            'overview' => $this->overview($faculty, $courses),
            'selectedCourseId' => $selected?->id,
            'report' => $selected ? $this->courseReport($selected) : null,
        ];
    }

    /**
     * Cross-course summary for the faculty member.
     *
     * @param  Collection<int, Course>  $courses
     * @return array<string, mixed>
     */
    private function overview(User $faculty, Collection $courses): array
    {
        $totalStudents = 0;
        $attendanceRates = collect();

        foreach ($courses as $course) {
            $totalStudents += $course->students()->count();
            $rate = $this->attendance->courseSummary($course)['rate'];
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
     * Detailed academic report for a single course.
     *
     * @return array<string, mixed>
     */
    private function courseReport(Course $course): array
    {
        $students = $course->students()->get();
        $gradeDistribution = $this->gradeDistribution($students);
        $submissionStats = $this->submissionStats($course);
        $attendanceSummary = $this->attendance->courseSummary($course);

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
            'atRisk' => $this->atRiskStudents($course, $students),
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
     * @return array<string, mixed>
     */
    private function submissionStats(Course $course): array
    {
        $submissions = AssignmentSubmission::whereHas('assignment', fn (Builder $q) => $q->where('course_id', $course->id))
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
     * @param  Collection<int, User>  $students
     * @return array<int, array<string, mixed>>
     */
    private function atRiskStudents(Course $course, Collection $students): array
    {
        $records = $course->attendanceRecords()->get()->groupBy('user_id');

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
            ->whereHas('assignment.course', fn (Builder $q) => $q->where('faculty_id', $faculty->id))
            ->count();
    }
}
