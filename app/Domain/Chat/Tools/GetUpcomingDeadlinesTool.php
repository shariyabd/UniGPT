<?php

declare(strict_types=1);

namespace App\Domain\Chat\Tools;

use App\Domain\Academic\Services\StudyPlannerService;
use App\Domain\Chat\Contracts\ChatToolInterface;
use App\Domain\User\Models\User;

/**
 * Read-only lookup of the student's real upcoming graded deadlines
 * (assignments, exams, class tests) across their enrolled sections.
 */
class GetUpcomingDeadlinesTool implements ChatToolInterface
{
    public function __construct(private readonly StudyPlannerService $planner) {}

    public function name(): string
    {
        return 'get_upcoming_deadlines';
    }

    public function label(): string
    {
        return 'Checking your deadlines';
    }

    public function description(): string
    {
        return 'Get the student\'s real upcoming graded deadlines (assignments, exams and class tests) '
            .'across their enrolled courses, sorted by date. Call this whenever the student asks what '
            .'is due, what is coming up, or anything about their actual schedule — never guess deadlines.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of deadlines to return (default 15).',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(User $user, array $arguments): array
    {
        $limit = max(1, min(30, (int) ($arguments['limit'] ?? 15)));
        $deadlines = array_slice($this->planner->deadlines($user), 0, $limit);

        return [
            'data' => ['deadlines' => $deadlines],
            'summary' => $deadlines === []
                ? 'No upcoming deadlines found'
                : 'Found '.count($deadlines).' upcoming deadline'.(count($deadlines) === 1 ? '' : 's'),
            'link' => route('calendar'),
            'linkLabel' => 'Open calendar',
        ];
    }
}
