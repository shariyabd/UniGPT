<?php

declare(strict_types=1);

namespace App\Domain\Academic\Services;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\User\Models\User;
use App\Models\Assignment;
use App\Models\ClassTest;
use App\Models\Exam;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds an AI-generated study schedule from a student's real upcoming
 * deadlines (assignments, exams, class tests). Falls back to deterministic
 * scheduling so the feature works without any configured LLM key.
 *
 * The generated plan is ephemeral — the student reviews it and saves the
 * sessions they want as personal Tasks (see StudyPlannerController::storeTasks).
 */
class StudyPlannerService
{
    public function __construct(private readonly AIProviderInterface $provider) {}

    /**
     * Upcoming graded deadlines across the student's enrolled sections, sorted
     * by date. Drives both the on-page list and the generation prompt.
     *
     * @return array<int, array<string, mixed>>
     */
    public function deadlines(User $student): array
    {
        $sectionIds = $student->enrolledSectionIds();

        if ($sectionIds->isEmpty()) {
            return [];
        }

        $today = CarbonImmutable::now()->startOfDay();

        return collect()
            ->merge($this->assignmentDeadlines($sectionIds, $today))
            ->merge($this->examDeadlines($sectionIds, $today))
            ->merge($this->classTestDeadlines($sectionIds, $today))
            ->sortBy('date')
            ->values()
            ->all();
    }

    /**
     * Produce a day-by-day study plan spanning [startDate, endDate].
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function generatePlan(User $student, array $params): array
    {
        $start = CarbonImmutable::parse((string) $params['start_date'])->startOfDay();
        $end = CarbonImmutable::parse((string) $params['end_date'])->startOfDay();
        $hoursPerDay = max(1, min(12, (int) ($params['hours_per_day'] ?? 3)));
        $focus = trim((string) ($params['focus'] ?? ''));

        $deadlines = $this->deadlines($student);

        $sessions = $this->askLlm($deadlines, $start, $end, $hoursPerDay, $focus)
            ?? $this->synthesizePlan($deadlines, $start, $end, $hoursPerDay);

        return [
            'range' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'hoursPerDay' => $hoursPerDay,
            'sessions' => collect($sessions)
                ->filter(fn ($s) => $this->sessionInRange($s, $start, $end))
                ->sortBy('date')
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, int>  $sectionIds
     * @return Collection<int, array<string, mixed>>
     */
    private function assignmentDeadlines(Collection $sectionIds, CarbonImmutable $today): Collection
    {
        return Assignment::whereIn('section_id', $sectionIds)
            ->where('status', 'published')
            ->whereNotNull('due_at')
            ->where('due_at', '>=', $today)
            ->with('course:id,code')
            ->get()
            ->map(fn (Assignment $a) => [
                'type' => 'assignment',
                'title' => $a->title,
                'course' => $a->course?->code,
                'date' => $a->due_at->toDateString(),
                'daysRemaining' => $today->diffInDays(CarbonImmutable::parse($a->due_at->toDateString()), false),
            ]);
    }

    /**
     * @param  Collection<int, int>  $sectionIds
     * @return Collection<int, array<string, mixed>>
     */
    private function examDeadlines(Collection $sectionIds, CarbonImmutable $today): Collection
    {
        return Exam::whereIn('section_id', $sectionIds)
            ->whereDate('exam_date', '>=', $today)
            ->with('course:id,code')
            ->get()
            ->map(fn (Exam $e) => [
                'type' => 'exam',
                'title' => $e->title,
                'course' => $e->course?->code,
                'date' => CarbonImmutable::parse($e->exam_date)->toDateString(),
                'daysRemaining' => $today->diffInDays(CarbonImmutable::parse($e->exam_date), false),
            ]);
    }

    /**
     * @param  Collection<int, int>  $sectionIds
     * @return Collection<int, array<string, mixed>>
     */
    private function classTestDeadlines(Collection $sectionIds, CarbonImmutable $today): Collection
    {
        return ClassTest::whereIn('section_id', $sectionIds)
            ->where('status', 'published')
            ->whereNotNull('available_until')
            ->where('available_until', '>=', $today)
            ->with('course:id,code')
            ->get()
            ->map(fn (ClassTest $t) => [
                'type' => 'class-test',
                'title' => $t->title,
                'course' => $t->course?->code,
                'date' => CarbonImmutable::parse($t->available_until)->toDateString(),
                'daysRemaining' => $today->diffInDays(CarbonImmutable::parse($t->available_until->toDateString()), false),
            ]);
    }

    /**
     * Ask the LLM for a structured plan. Returns null on any parse failure so
     * the caller can fall back to deterministic scheduling.
     *
     * @param  array<int, array<string, mixed>>  $deadlines
     * @return array<int, array<string, mixed>>|null
     */
    private function askLlm(array $deadlines, CarbonImmutable $start, CarbonImmutable $end, int $hoursPerDay, string $focus): ?array
    {
        if (! $this->provider->isAvailable()) {
            return null;
        }

        $deadlineLines = $deadlines === []
            ? 'The student has no upcoming graded deadlines; build a general revision plan.'
            : collect($deadlines)
                ->map(fn ($d) => "- {$d['type']}: \"{$d['title']}\"".($d['course'] ? " ({$d['course']})" : '')." due {$d['date']}")
                ->implode("\n");

        $focusLine = $focus !== '' ? "The student wants to focus on: {$focus}. " : '';

        $prompt = "Create a study plan from {$start->toDateString()} to {$end->toDateString()} "
            ."with about {$hoursPerDay} study hours per day. {$focusLine}"
            ."Prioritise the nearest deadlines and spread revision across the days before each one.\n\n"
            ."Upcoming deadlines:\n{$deadlineLines}\n\n"
            .'Respond ONLY with JSON of this shape: '
            .'{"sessions":[{"date":"YYYY-MM-DD","title":"short session title","course":"course code or null",'
            .'"focus":"what to study","durationMinutes":60,"relatedDeadline":"deadline title or null"}]}';

        $content = $this->provider->chat([
            ['role' => 'system', 'content' => 'You are an academic study coach who builds realistic, time-boxed revision schedules.'],
            ['role' => 'user', 'content' => $prompt],
        ])->content;

        $parsed = $this->tryJson($content);
        $sessions = $parsed['sessions'] ?? null;

        if (! is_array($sessions) || $sessions === []) {
            return null;
        }

        return collect($sessions)->map(fn ($s) => [
            'date' => (string) ($s['date'] ?? $start->toDateString()),
            'title' => trim((string) ($s['title'] ?? 'Study session')),
            'course' => $s['course'] ?? null,
            'focus' => trim((string) ($s['focus'] ?? '')),
            'durationMinutes' => max(15, min(480, (int) ($s['durationMinutes'] ?? 60))),
            'relatedDeadline' => $s['relatedDeadline'] ?? null,
        ])->all();
    }

    /**
     * Deterministic fallback: schedule one revision session in the days leading
     * up to each deadline, clamped to the requested range.
     *
     * @param  array<int, array<string, mixed>>  $deadlines
     * @return array<int, array<string, mixed>>
     */
    private function synthesizePlan(array $deadlines, CarbonImmutable $start, CarbonImmutable $end, int $hoursPerDay): array
    {
        $minutes = $hoursPerDay * 60;

        if ($deadlines === []) {
            // No deadlines — lay down a light daily revision session across the range.
            $sessions = [];
            for ($day = $start; $day->lessThanOrEqualTo($end); $day = $day->addDay()) {
                $sessions[] = [
                    'date' => $day->toDateString(),
                    'title' => 'General revision',
                    'course' => null,
                    'focus' => 'Review recent lecture notes and course materials.',
                    'durationMinutes' => min(120, $minutes),
                    'relatedDeadline' => null,
                ];
            }

            return $sessions;
        }

        return collect($deadlines)->map(function (array $deadline) use ($start, $end, $minutes) {
            $due = CarbonImmutable::parse((string) $deadline['date']);
            // Aim for two days before the deadline, but never outside the range.
            $target = $due->subDays(2);
            if ($target->lessThan($start)) {
                $target = $start;
            }
            if ($target->greaterThan($end)) {
                $target = $end;
            }

            return [
                'date' => $target->toDateString(),
                'title' => 'Prepare: '.$deadline['title'],
                'course' => $deadline['course'] ?? null,
                'focus' => "Revise and prepare for the {$deadline['type']} \"{$deadline['title']}\".",
                'durationMinutes' => min(180, $minutes),
                'relatedDeadline' => $deadline['title'],
            ];
        })->all();
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function sessionInRange(array $session, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        $date = Carbon::hasFormat((string) ($session['date'] ?? ''), 'Y-m-d')
            ? CarbonImmutable::parse((string) $session['date'])
            : null;

        return $date !== null
            && $date->greaterThanOrEqualTo($start)
            && $date->lessThanOrEqualTo($end);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tryJson(string $content): ?array
    {
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
