<?php

namespace App\Domain\Academic\Services;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\User\Models\User;
use App\Enums\NotificationType;
use App\Models\Exam;
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
        return Exam::with('course')
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
        $courseIds = $student->enrolledCourses()->pluck('courses.id');

        return $this->splitByDate($this->examsForCourses($courseIds));
    }

    /**
     * Exams for the courses a faculty member teaches.
     *
     * @return array<string, mixed>
     */
    public function forFaculty(User $faculty): array
    {
        $courseIds = $faculty->teachingCourses()->pluck('id');

        return $this->splitByDate($this->examsForCourses($courseIds));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $author): Exam
    {
        $exam = Exam::create([...$data, 'created_by' => $author->id]);

        $course = $exam->course;
        if ($course) {
            $this->notifications->notifyMany(
                users: $course->students()->get(),
                type: NotificationType::EXAM,
                title: "{$exam->type->getLabel()} scheduled — {$course->code}",
                message: "\"{$exam->title}\" on {$exam->exam_date->toFormattedDateString()}.",
                link: route('exams'),
                data: ['exam_id' => $exam->id, 'course_id' => $course->id],
            );
        }

        return $exam;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Exam $exam, array $data): Exam
    {
        $exam->update($data);

        return $exam->fresh();
    }

    public function delete(Exam $exam): void
    {
        $exam->delete();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $courseIds
     * @return Collection<int, Exam>
     */
    private function examsForCourses(Collection $courseIds): Collection
    {
        if ($courseIds->isEmpty()) {
            return collect();
        }

        return Exam::with('course')
            ->whereIn('course_id', $courseIds)
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
            ],
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
