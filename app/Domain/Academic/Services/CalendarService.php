<?php

namespace App\Domain\Academic\Services;

use App\Domain\User\Models\User;
use App\Models\Assignment;
use App\Models\Exam;
use App\Models\OfficeHourSlot;
use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * Unified academic calendar: merges assignment deadlines, scheduled exams,
 * a student's personal tasks and their booked office-hours meetings into a
 * single date-keyed event stream.
 */
class CalendarService
{
    /**
     * @return array<string, mixed>
     */
    public function build(User $student): array
    {
        // Calendar events come from the student's enrolled sections, so deadlines
        // and exams reflect only their own section's instructor.
        $sectionIds = $student->enrolledSectionIds();

        $events = collect()
            ->merge($this->assignmentEvents($sectionIds))
            ->merge($this->examEvents($sectionIds))
            ->merge($this->taskEvents($student))
            ->merge($this->officeHourEvents($student))
            ->sortBy('date')
            ->values();

        return [
            'events' => $events->all(),
            'today' => now()->toDateString(),
        ];
    }

    /**
     * @param  Collection<int, int>  $sectionIds
     * @return Collection<int, array<string, mixed>>
     */
    private function assignmentEvents(Collection $sectionIds): Collection
    {
        if ($sectionIds->isEmpty()) {
            return collect();
        }

        return Assignment::whereIn('section_id', $sectionIds)
            ->whereNotNull('due_at')
            ->with('course:id,code')
            ->get()
            ->map(fn (Assignment $a) => [
                'id' => 'assignment-'.$a->id,
                'assignmentId' => $a->id,
                'type' => 'assignment',
                'title' => $a->title,
                'date' => $a->due_at->toDateString(),
                'time' => $a->due_at->format('H:i'),
                'course' => $a->course?->code,
            ]);
    }

    /**
     * @param  Collection<int, int>  $sectionIds
     * @return Collection<int, array<string, mixed>>
     */
    private function examEvents(Collection $sectionIds): Collection
    {
        if ($sectionIds->isEmpty()) {
            return collect();
        }

        return Exam::whereIn('section_id', $sectionIds)
            ->with('course:id,code')
            ->get()
            ->map(fn (Exam $e) => [
                'id' => 'exam-'.$e->id,
                'type' => 'exam',
                'title' => $e->title,
                'date' => $e->exam_date->toDateString(),
                'time' => $e->start_time ? substr((string) $e->start_time, 0, 5) : null,
                'course' => $e->course?->code,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function taskEvents(User $student): Collection
    {
        return Task::where('user_id', $student->id)
            ->whereNotNull('due_date')
            ->with('course:id,code')
            ->get()
            ->map(fn (Task $t) => [
                'id' => 'task-'.$t->id,
                'taskId' => $t->id,
                'type' => 'task',
                'title' => $t->title,
                'description' => $t->description,
                'date' => $t->due_date->toDateString(),
                'time' => null,
                'course' => $t->course?->code,
                'priority' => $t->priority->value,
                'completed' => $t->is_completed,
            ]);
    }

    /**
     * The student's booked office-hours meetings.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function officeHourEvents(User $student): Collection
    {
        return OfficeHourSlot::where('booked_by', $student->id)
            ->with('faculty:id,name')
            ->get()
            ->map(fn (OfficeHourSlot $slot) => [
                'id' => 'office-hours-'.$slot->id,
                'type' => 'office_hours',
                'title' => 'Office hours — '.($slot->faculty?->name ?? 'Faculty'),
                'date' => $slot->starts_at->toDateString(),
                'time' => $slot->starts_at->format('H:i'),
                'course' => null,
                'location' => $slot->location,
            ]);
    }
}
