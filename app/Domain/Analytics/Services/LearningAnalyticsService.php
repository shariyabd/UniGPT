<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Services;

use App\Domain\Academic\Services\AttendanceService;
use App\Domain\Academic\Services\TranscriptService;
use App\Domain\User\Models\User;
use App\Models\ActivityLog;
use App\Models\AssignmentSubmission;
use App\Models\ClassTestAttempt;
use Illuminate\Support\Collection;

/**
 * Aggregates a single student's own academic performance and learning
 * engagement into chart-ready series. Reuses the existing transcript and
 * attendance services so GPA/attendance definitions stay consistent with the
 * rest of the app.
 */
class LearningAnalyticsService
{
    public function __construct(
        private readonly TranscriptService $transcript,
        private readonly AttendanceService $attendance,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $student): array
    {
        $transcript = $this->transcript->build($student);
        $attendance = $this->attendance->studentSummary($student);
        $tests = $this->classTestPerformance($student);
        $assignments = $this->assignmentPerformance($student);

        $attendanceTotal = (int) $attendance->sum('total');
        $attendanceAttended = (int) $attendance->sum('attended');
        $attendanceRate = $attendanceTotal > 0
            ? (int) round($attendanceAttended / $attendanceTotal * 100)
            : null;

        return [
            'summary' => [
                'cgpa' => $transcript['summary']['cgpa'],
                'completedCredits' => $transcript['summary']['completedCredits'],
                'attendanceRate' => $attendanceRate,
                'avgTestScore' => $tests['average'],
                'avgAssignmentScore' => $assignments['average'],
                'studyStreak' => $this->studyStreak($student),
                'aiSessions' => $student->chatSessions()->count(),
            ],
            'gpaTrend' => $this->gpaTrend($transcript['semesters']),
            'attendanceByCourse' => $attendance
                ->filter(fn ($course) => $course['rate'] !== null)
                ->map(fn ($course) => ['label' => $course['code'], 'value' => $course['rate']])
                ->values()
                ->all(),
            'testTrend' => $tests['trend'],
            'assignmentByCourse' => $assignments['byCourse'],
            'activityTrend' => $this->activityTrend($student),
        ];
    }

    /**
     * Per-semester GPA line, ordered by semester, skipping ungraded semesters.
     *
     * @param  Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $semesters
     * @return array<int, array<string, mixed>>
     */
    private function gpaTrend($semesters): array
    {
        return collect($semesters)
            ->filter(fn ($semester) => $semester['gpa'] !== null)
            ->map(fn ($semester) => ['label' => $semester['title'], 'value' => $semester['gpa']])
            ->values()
            ->all();
    }

    /**
     * Submitted class-test attempts as a chronological score-percentage series.
     *
     * @return array{average: int|null, trend: array<int, array<string, mixed>>}
     */
    private function classTestPerformance(User $student): array
    {
        $attempts = ClassTestAttempt::where('user_id', $student->id)
            ->where('status', 'submitted')
            ->whereNotNull('submitted_at')
            ->where('total_marks', '>', 0)
            ->with('classTest:id,title')
            ->orderBy('submitted_at')
            ->get();

        if ($attempts->isEmpty()) {
            return ['average' => null, 'trend' => []];
        }

        $trend = $attempts->map(fn (ClassTestAttempt $attempt) => [
            'label' => $attempt->classTest?->title ?? 'Class test',
            'date' => $attempt->submitted_at->toDateString(),
            'value' => (int) round($attempt->score / $attempt->total_marks * 100),
        ])->values();

        return [
            'average' => (int) round($trend->avg('value')),
            'trend' => $trend->all(),
        ];
    }

    /**
     * Graded assignment submissions, averaged overall and grouped by course.
     *
     * @return array{average: int|null, byCourse: array<int, array<string, mixed>>}
     */
    private function assignmentPerformance(User $student): array
    {
        $submissions = AssignmentSubmission::where('user_id', $student->id)
            ->where('status', 'graded')
            ->whereNotNull('grade')
            ->with('assignment:id,course_id,total_points', 'assignment.course:id,code')
            ->get()
            ->filter(fn (AssignmentSubmission $submission) => ($submission->assignment?->total_points ?? 0) > 0);

        if ($submissions->isEmpty()) {
            return ['average' => null, 'byCourse' => []];
        }

        $percentages = $submissions->map(
            fn (AssignmentSubmission $submission) => (float) $submission->grade / (float) $submission->assignment->total_points * 100,
        );

        $byCourse = $submissions
            ->groupBy(fn (AssignmentSubmission $submission) => $submission->assignment->course?->code ?? '—')
            ->map(fn ($group, $code) => [
                'label' => (string) $code,
                'value' => (int) round($group->avg(
                    fn (AssignmentSubmission $submission) => (float) $submission->grade / (float) $submission->assignment->total_points * 100,
                )),
            ])
            ->values()
            ->all();

        return [
            'average' => (int) round($percentages->avg()),
            'byCourse' => $byCourse,
        ];
    }

    /**
     * Weekly activity counts over the last 8 weeks (engagement signal).
     *
     * @return array<int, array<string, mixed>>
     */
    private function activityTrend(User $student): array
    {
        $since = now()->subWeeks(7)->startOfWeek();

        $byDate = ActivityLog::where('user_id', $student->id)
            ->where('created_at', '>=', $since)
            ->orderBy('created_at')
            ->get(['created_at'])
            ->groupBy(fn (ActivityLog $log) => $log->created_at->copy()->startOfWeek()->toDateString());

        $weeks = [];
        for ($week = $since->copy(); $week->lessThanOrEqualTo(now()); $week->addWeek()) {
            $key = $week->toDateString();
            $weeks[] = [
                'label' => $week->format('M j'),
                'value' => $byDate->get($key)?->count() ?? 0,
            ];
        }

        return $weeks;
    }

    /**
     * Consecutive days (ending today, with a one-day grace) with any activity.
     */
    private function studyStreak(User $student): int
    {
        $days = ActivityLog::where('user_id', $student->id)
            ->where('created_at', '>=', now()->subDays(366)->startOfDay())
            ->orderByDesc('created_at')
            ->get(['created_at'])
            ->map(fn (ActivityLog $log) => $log->created_at->toDateString())
            ->unique()
            ->flip();

        if ($days->isEmpty()) {
            return 0;
        }

        $cursor = now()->startOfDay();
        if (! $days->has($cursor->toDateString()) && $days->has($cursor->copy()->subDay()->toDateString())) {
            $cursor = $cursor->subDay();
        }

        $streak = 0;
        while ($days->has($cursor->toDateString())) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }
}
