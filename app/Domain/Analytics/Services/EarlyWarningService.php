<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Services;

use App\Domain\User\Models\User;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\ClassTestAttempt;
use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Early-warning system: flags students showing risk signals across the data
 * the platform already records — attendance, missed assignment deadlines,
 * class-test performance and current grade. Used by faculty analytics and
 * the faculty dashboard so declining students surface before they fail.
 */
class EarlyWarningService
{
    /** Attendance is only judged once this many sessions are recorded. */
    private const MIN_ATTENDANCE_SESSIONS = 4;

    /** Attendance rate (%) below which a student is flagged. */
    private const ATTENDANCE_THRESHOLD = 75;

    /** Grade letters that flag a student. */
    private const RISK_GRADES = ['D+', 'D', 'D-', 'F'];

    /** Past-due assignments without a submission needed to flag. */
    private const MISSED_ASSIGNMENTS_THRESHOLD = 2;

    /** Class-test average (%) below which a student is flagged. */
    private const TEST_AVERAGE_THRESHOLD = 50;

    /** Signals at or above which a student is high risk (vs. watch). */
    private const HIGH_RISK_SIGNALS = 2;

    /**
     * Flag at-risk students on a roster, most signals first.
     *
     * @param  Collection<int, int>  $sectionIds  Sections in scope (a faculty's own sections).
     * @param  Collection<int, User>  $students  Roster rows with `pivot->grade` and `pivot->section_id`.
     * @return array<int, array<string, mixed>>
     */
    public function flag(Collection $sectionIds, Collection $students): array
    {
        if ($students->isEmpty() || $sectionIds->isEmpty()) {
            return [];
        }

        $attendance = $this->attendanceStats($sectionIds);
        [$dueCountBySection, $submittedCounts] = $this->assignmentStats($sectionIds, $students);
        $testAverages = $this->testAverages($sectionIds, $students);

        return $students
            ->map(function (User $student) use ($attendance, $dueCountBySection, $submittedCounts, $testAverages) {
                $reasons = [];

                $row = $attendance->get($student->id);
                $total = (int) ($row->total ?? 0);
                $rate = $total > 0 ? (int) round((int) $row->attended / $total * 100) : null;
                if ($total >= self::MIN_ATTENDANCE_SESSIONS && $rate < self::ATTENDANCE_THRESHOLD) {
                    $reasons[] = "Attendance {$rate}%";
                }

                $sectionId = $student->pivot->section_id ?? null;
                $due = (int) $dueCountBySection->get($sectionId, 0);
                $submitted = (int) ($submittedCounts[$student->id][$sectionId] ?? 0);
                $missed = max(0, $due - $submitted);
                if ($missed >= self::MISSED_ASSIGNMENTS_THRESHOLD) {
                    $reasons[] = "{$missed} missed deadlines";
                }

                $testAverage = $testAverages->get($student->id);
                if ($testAverage !== null && $testAverage < self::TEST_AVERAGE_THRESHOLD) {
                    $reasons[] = "Test average {$testAverage}%";
                }

                $grade = $student->pivot->grade ?? null;
                if ($grade !== null && in_array($grade, self::RISK_GRADES, true)) {
                    $reasons[] = "Grade {$grade}";
                }

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'studentId' => $student->student_id,
                    'attendanceRate' => $rate,
                    'missedAssignments' => $missed,
                    'testAverage' => $testAverage,
                    'grade' => $grade,
                    'reasons' => $reasons,
                    'riskLevel' => count($reasons) >= self::HIGH_RISK_SIGNALS ? 'high' : 'watch',
                ];
            })
            ->filter(fn (array $row) => $row['reasons'] !== [])
            ->sortByDesc(fn (array $row) => count($row['reasons']))
            ->values()
            ->all();
    }

    /**
     * Unique flagged students across everything a faculty member teaches —
     * the dashboard-widget number. A student enrolled in two of the faculty's
     * courses counts once.
     */
    public function countForFaculty(User $faculty): int
    {
        $sections = $faculty->teachingSections()->with('course')->get();
        $sectionIdsByCourse = $sections->groupBy('course_id')->map(fn (Collection $group) => $group->pluck('id'));

        $students = $sections->pluck('course')
            ->filter()
            ->unique('id')
            ->flatMap(fn (Course $course) => $course->students()
                ->wherePivotIn('section_id', $sectionIdsByCourse->get($course->id, collect()))
                ->get())
            ->values();

        return collect($this->flag($sections->pluck('id'), $students))
            ->unique('id')
            ->count();
    }

    /**
     * Per-student attendance aggregates, computed in SQL. "Attended" is any
     * status other than absent, mirroring AttendanceStatus semantics.
     *
     * @param  Collection<int, int>  $sectionIds
     */
    private function attendanceStats(Collection $sectionIds): Collection
    {
        return AttendanceRecord::query()
            ->whereIn('section_id', $sectionIds)
            ->selectRaw('user_id, COUNT(*) as total, SUM(CASE WHEN status <> ? THEN 1 ELSE 0 END) as attended', ['absent'])
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');
    }

    /**
     * Past-due published assignment counts per section, plus each student's
     * submission counts per section — the delta is their missed deadlines.
     *
     * @param  Collection<int, int>  $sectionIds
     * @param  Collection<int, User>  $students
     * @return array{0: Collection<int, int>, 1: array<int, array<int, int>>}
     */
    private function assignmentStats(Collection $sectionIds, Collection $students): array
    {
        $dueAssignments = Assignment::query()
            ->whereIn('section_id', $sectionIds)
            ->where('status', 'published')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->get(['id', 'section_id']);

        $dueCountBySection = $dueAssignments->groupBy('section_id')->map->count();
        $sectionByAssignment = $dueAssignments->pluck('section_id', 'id');

        $submittedCounts = [];
        AssignmentSubmission::query()
            ->whereIn('assignment_id', $dueAssignments->pluck('id'))
            ->whereIn('user_id', $students->pluck('id')->unique())
            ->get(['user_id', 'assignment_id'])
            ->each(function (AssignmentSubmission $submission) use ($sectionByAssignment, &$submittedCounts) {
                $sectionId = $sectionByAssignment->get($submission->assignment_id);
                $submittedCounts[$submission->user_id][$sectionId] = ($submittedCounts[$submission->user_id][$sectionId] ?? 0) + 1;
            });

        return [$dueCountBySection, $submittedCounts];
    }

    /**
     * Per-student class-test average (%) across finalised attempts in scope,
     * aggregated in SQL.
     *
     * @param  Collection<int, int>  $sectionIds
     * @param  Collection<int, User>  $students
     * @return Collection<int, int|null>
     */
    private function testAverages(Collection $sectionIds, Collection $students): Collection
    {
        return ClassTestAttempt::query()
            ->whereIn('user_id', $students->pluck('id')->unique())
            ->whereNotNull('score')
            ->where('status', '!=', 'in_progress')
            ->whereHas('classTest', fn (Builder $q) => $q->whereIn('section_id', $sectionIds))
            ->selectRaw('user_id, SUM(score) as scored, SUM(total_marks) as possible')
            ->groupBy('user_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->user_id => (int) $row->possible > 0
                    ? (int) round((float) $row->scored / (float) $row->possible * 100)
                    : null,
            ]);
    }
}
