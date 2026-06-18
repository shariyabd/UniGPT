<?php

namespace App\Domain\Academic\Services;

use App\Domain\User\Models\User;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\Section;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Attendance: faculty roster marking and student attendance monitoring.
 *
 * Faculty operations are scoped to a Section — the offering a faculty member
 * actually teaches — so one instructor never marks or sees another section's
 * roster, even within the same course.
 */
class AttendanceService
{
    /**
     * Roster for a section on a given date, each student paired with their
     * existing status that day (defaults to 'present' when unmarked).
     *
     * @return array<string, mixed>
     */
    public function rosterForDate(Section $section, string $date): array
    {
        $existing = $section->attendanceRecords()
            ->whereDate('date', $date)
            ->get()
            ->keyBy('user_id');

        $roster = $section->students()->orderBy('name')->get()->map(fn (User $student) => [
            'id' => $student->id,
            'name' => $student->name,
            'studentId' => $student->student_id,
            'avatar' => 'https://ui-avatars.com/api/?name='.urlencode($student->name).'&background=6366f1&color=fff',
            'status' => $existing->get($student->id)?->status->value ?? AttendanceStatus::PRESENT->value,
            'notes' => $existing->get($student->id)?->notes,
        ])->values();

        return [
            'date' => $date,
            'roster' => $roster,
            'statuses' => AttendanceStatus::values(),
            'summary' => $this->sectionSummary($section),
        ];
    }

    /**
     * Upsert attendance for a set of students in a section on a given date.
     *
     * @param  array<int, array{user_id: int, status: string, notes?: string|null}>  $entries
     */
    public function mark(Section $section, string $date, array $entries, User $faculty): void
    {
        $enrolledIds = $section->students()->pluck('users.id');

        foreach ($entries as $entry) {
            if (! $enrolledIds->contains($entry['user_id'])) {
                continue; // ignore students not enrolled in this section
            }

            AttendanceRecord::updateOrCreate(
                [
                    'course_id' => $section->course_id,
                    'user_id' => $entry['user_id'],
                    'date' => $date,
                ],
                [
                    'section_id' => $section->id,
                    'status' => $entry['status'],
                    'notes' => $entry['notes'] ?? null,
                    'marked_by' => $faculty->id,
                ],
            );
        }
    }

    /**
     * Per-course attendance summary for a student (attended/total + rate %).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function studentSummary(User $student): Collection
    {
        $courses = $student->enrolledCourses()->get();

        $records = AttendanceRecord::where('user_id', $student->id)
            ->whereIn('course_id', $courses->pluck('id'))
            ->get()
            ->groupBy('course_id');

        return $courses->map(function (Course $course) use ($records) {
            $courseRecords = $records->get($course->id, collect());
            $total = $courseRecords->count();
            $attended = $courseRecords->filter(fn (AttendanceRecord $r) => $r->status->countsAsPresent())->count();

            return [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
                'total' => $total,
                'attended' => $attended,
                'absent' => $total - $attended,
                'rate' => $total > 0 ? (int) round($attended / $total * 100) : null,
                'recent' => $courseRecords->sortByDesc('date')->take(5)->map(fn (AttendanceRecord $r) => [
                    'date' => $r->date->toDateString(),
                    'status' => $r->status->value,
                ])->values(),
            ];
        })->values();
    }

    /**
     * Aggregate attendance stats for a single section (faculty view).
     *
     * @return array<string, mixed>
     */
    public function sectionSummary(Section $section): array
    {
        return $this->summarize($section->attendanceRecords()->get());
    }

    /**
     * Aggregate attendance stats across a set of sections — used by analytics to
     * scope a course report to the faculty member's own section(s).
     *
     * @param  Collection<int, int>  $sectionIds
     * @return array<string, mixed>
     */
    public function summaryForSections(Collection $sectionIds): array
    {
        if ($sectionIds->isEmpty()) {
            return ['sessions' => 0, 'records' => 0, 'rate' => null];
        }

        return $this->summarize(
            AttendanceRecord::whereIn('section_id', $sectionIds)->get()
        );
    }

    /**
     * @param  Collection<int, AttendanceRecord>  $records
     * @return array<string, mixed>
     */
    private function summarize(Collection $records): array
    {
        $sessions = $records->pluck('date')->map(fn (Carbon $d) => $d->toDateString())->unique();
        $total = $records->count();
        $attended = $records->filter(fn (AttendanceRecord $r) => $r->status->countsAsPresent())->count();

        return [
            'sessions' => $sessions->count(),
            'records' => $total,
            'rate' => $total > 0 ? (int) round($attended / $total * 100) : null,
        ];
    }
}
