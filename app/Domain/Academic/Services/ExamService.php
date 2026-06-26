<?php

namespace App\Domain\Academic\Services;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\User\Models\User;
use App\Enums\NotificationType;
use App\Models\Course;
use App\Models\Exam;
use App\Models\Section;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Exam / timetable scheduling: admin management plus role-scoped read views.
 */
class ExamService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Every exam (admin management table), newest scheduled first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function adminList(): Collection
    {
        return Exam::with(['course.department', 'section'])
            ->orderByDesc('exam_date')
            ->get()
            ->map(fn (Exam $exam) => $this->present($exam));
    }

    /**
     * Exams for the courses a student is enrolled in, split into upcoming/past.
     *
     * @return array<string, mixed>
     */
    public function forStudent(User $student): array
    {
        return $this->splitByDate($this->examsForSections($student->enrolledSectionIds()));
    }

    /**
     * Exams for the sections a faculty member teaches.
     *
     * @return array<string, mixed>
     */
    public function forFaculty(User $faculty): array
    {
        return $this->splitByDate($this->examsForSections($faculty->teachingSectionIds()));
    }

    /**
     * Schedule an exam. With a section_id the exam is created for that one
     * section; without it the exam is scheduled for every section of the course
     * (one row each), so every section's students are reached. Each created exam
     * notifies its own section's enrolled students.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, Exam>
     */
    public function create(array $data, User $author): Collection
    {
        $course = Course::with('sections')->find($data['course_id']);
        $base = Arr::except($data, ['section_id']);

        $sections = match (true) {
            ! empty($data['section_id']) => $course?->sections->where('id', $data['section_id'])->values() ?? collect(),
            default => $course?->sections ?? collect(),
        };

        // No section configured for the course — fall back to a single section-less
        // exam so the course still gets one (legacy safety net).
        if ($sections->isEmpty()) {
            return collect([Exam::create([...$base, 'created_by' => $author->id])]);
        }

        return $sections->map(function (Section $section) use ($base, $course, $author) {
            $exam = Exam::create([
                ...$base,
                'section_id' => $section->id,
                'created_by' => $author->id,
            ]);

            $this->notifications->notifyMany(
                users: $section->students()->wherePivot('status', 'enrolled')->get(),
                type: NotificationType::EXAM,
                title: "{$exam->type->getLabel()} scheduled — {$course->code}",
                message: "\"{$exam->title}\" (Section {$section->label}) on {$exam->exam_date->toFormattedDateString()}.",
                link: route('exams'),
                data: ['exam_id' => $exam->id, 'course_id' => $course->id, 'section_id' => $section->id],
            );

            return $exam;
        })->values();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Exam $exam, array $data): Exam
    {
        // An exam row always belongs to one section; never null it out on edit
        // when the form leaves the section as "all sections".
        if (empty($data['section_id'])) {
            unset($data['section_id']);
        }

        $exam->update($data);

        return $exam->fresh();
    }

    public function delete(Exam $exam): void
    {
        $exam->delete();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $sectionIds
     * @return Collection<int, Exam>
     */
    private function examsForSections(Collection $sectionIds): Collection
    {
        if ($sectionIds->isEmpty()) {
            return collect();
        }

        return Exam::with('course')
            ->whereIn('section_id', $sectionIds)
            ->orderBy('exam_date')
            ->get();
    }

    /**
     * @param  Collection<int, Exam>  $exams
     * @return array<string, mixed>
     */
    private function splitByDate(Collection $exams): array
    {
        $today = now()->startOfDay();

        $presented = $exams->map(fn (Exam $exam) => $this->present($exam));

        return [
            'upcoming' => $presented->filter(fn (array $e) => $e['date'] >= $today->toDateString())->values(),
            'past' => $presented->filter(fn (array $e) => $e['date'] < $today->toDateString())
                ->sortByDesc('date')->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function present(Exam $exam): array
    {
        $daysUntil = (int) now()->startOfDay()->diffInDays($exam->exam_date->copy()->startOfDay(), false);

        return [
            'id' => $exam->id,
            'title' => $exam->title,
            'type' => $exam->type->value,
            'typeLabel' => $exam->type->getLabel(),
            'date' => $exam->exam_date->toDateString(),
            'dateLabel' => $exam->exam_date->toFormattedDateString(),
            'startTime' => $this->formatTime($exam->start_time),
            'durationMinutes' => $exam->duration_minutes,
            'location' => $exam->location,
            'totalMarks' => $exam->total_marks,
            'instructions' => $exam->instructions,
            'daysUntil' => $daysUntil,
            'countdown' => $this->countdownLabel($daysUntil),
            'course' => [
                'id' => $exam->course?->id,
                'code' => $exam->course?->code,
                'name' => $exam->course?->name,
                'departmentId' => $exam->course?->department_id,
                'department' => $exam->course?->department?->name,
            ],
            'sectionId' => $exam->section_id,
            'section' => $exam->section?->label,
        ];
    }

    /**
     * Normalise a stored "HH:MM:SS" time into a human label, e.g. "12:53 PM".
     */
    private function formatTime(?string $time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }

        return Carbon::parse($time)->format('g:i A');
    }

    /**
     * A friendly relative label for an upcoming exam date.
     */
    private function countdownLabel(int $daysUntil): ?string
    {
        return match (true) {
            $daysUntil < 0 => null,
            $daysUntil === 0 => 'Today',
            $daysUntil === 1 => 'Tomorrow',
            $daysUntil < 7 => "In {$daysUntil} days",
            $daysUntil < 14 => 'In 1 week',
            default => 'In '.intdiv($daysUntil, 7).' weeks',
        };
    }
}
