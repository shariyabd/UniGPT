<?php

namespace App\Domain\Academic\Services;

use App\Domain\User\Models\User;
use App\Models\Course;

/**
 * Builds an academic transcript (term-grouped grades, per-term GPA and CGPA)
 * from a student's course enrolments.
 */
class TranscriptService
{
    /**
     * Grade-letter → grade-point scale (4.0 system).
     *
     * @var array<string, float>
     */
    private const GRADE_POINTS = [
        'A' => 4.0, 'A-' => 3.7,
        'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
        'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7,
        'D+' => 1.3, 'D' => 1.0,
        'F' => 0.0,
    ];

    /**
     * @return array<string, mixed>
     */
    public function build(User $student): array
    {
        $courses = $student->enrolledCourses()->with('department')->get();

        $semesters = $courses
            ->sortBy('semester')
            ->groupBy(fn (Course $course) => $course->semester ?? 'Unscheduled')
            ->map(fn ($group, $semester) => $this->buildSemester((string) $semester, $group))
            ->values();

        $gradedCredits = 0.0;
        $weightedPoints = 0.0;

        foreach ($courses as $course) {
            $points = $this->pointsFor($course->pivot->grade);
            if ($points === null) {
                continue;
            }
            $credits = (float) $course->credits;
            $gradedCredits += $credits;
            $weightedPoints += $points * $credits;
        }

        $completedCredits = (int) $courses
            ->filter(fn (Course $c) => $c->pivot->status === 'completed')
            ->sum('credits');

        return [
            'student' => [
                'name' => $student->name,
                'studentId' => $student->student_id,
                'department' => $student->department?->name,
                'semester' => $student->semester,
                'email' => $student->email,
            ],
            'semesters' => $semesters,
            'summary' => [
                'cgpa' => $gradedCredits > 0 ? round($weightedPoints / $gradedCredits, 2) : null,
                'totalCredits' => (int) $courses->sum('credits'),
                'completedCredits' => $completedCredits,
                'gradedCredits' => (int) $gradedCredits,
                'coursesCount' => $courses->count(),
            ],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Course>  $courses
     * @return array<string, mixed>
     */
    private function buildSemester(string $semester, $courses): array
    {
        $rows = $courses->map(fn (Course $course) => [
            'code' => $course->code,
            'name' => $course->name,
            'credits' => (int) $course->credits,
            'grade' => $course->pivot->grade,
            'points' => $this->pointsFor($course->pivot->grade),
            'status' => $course->pivot->status,
        ])->values();

        $gradedCredits = 0.0;
        $weightedPoints = 0.0;
        foreach ($rows as $row) {
            if ($row['points'] === null) {
                continue;
            }
            $gradedCredits += $row['credits'];
            $weightedPoints += $row['points'] * $row['credits'];
        }

        return [
            'semester' => $semester,
            'title' => is_numeric($semester) ? "Semester {$semester}" : $semester,
            'courses' => $rows,
            'credits' => (int) $courses->sum('credits'),
            'gpa' => $gradedCredits > 0 ? round($weightedPoints / $gradedCredits, 2) : null,
        ];
    }

    private function pointsFor(?string $grade): ?float
    {
        if ($grade === null) {
            return null;
        }

        return self::GRADE_POINTS[$grade] ?? null;
    }
}
