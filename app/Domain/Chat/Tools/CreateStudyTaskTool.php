<?php

declare(strict_types=1);

namespace App\Domain\Chat\Tools;

use App\Domain\Chat\Contracts\ChatToolInterface;
use App\Domain\User\Models\User;
use App\Enums\TaskPriority;
use App\Models\Task;
use Illuminate\Support\Carbon;

/**
 * Add a personal task to the student's task board / study planner.
 */
class CreateStudyTaskTool implements ChatToolInterface
{
    public function name(): string
    {
        return 'create_study_task';
    }

    public function label(): string
    {
        return 'Adding a task';
    }

    public function description(): string
    {
        return 'Add a personal task to the student\'s task board (the same list the study planner saves '
            .'into). Call this when the student asks you to remind them of something, add a to-do, or '
            .'schedule a study session. Optionally attach a course via course_id from list_my_courses.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'description' => 'Short task title.',
                ],
                'due_date' => [
                    'type' => 'string',
                    'description' => 'Due date as YYYY-MM-DD.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional detail about the task.',
                ],
                'priority' => [
                    'type' => 'string',
                    'enum' => TaskPriority::values(),
                    'description' => 'Task priority (default medium).',
                ],
                'course_id' => [
                    'type' => 'integer',
                    'description' => 'Optional id of one of the student\'s enrolled courses.',
                ],
            ],
            'required' => ['title', 'due_date'],
        ];
    }

    public function execute(User $user, array $arguments): array
    {
        $title = trim((string) ($arguments['title'] ?? ''));
        abort_if($title === '', 422, 'A task title is required.');

        $dueDate = (string) ($arguments['due_date'] ?? '');
        abort_unless(Carbon::hasFormat($dueDate, 'Y-m-d'), 422, 'due_date must be a YYYY-MM-DD date.');

        $task = Task::create([
            'user_id' => $user->id,
            'course_id' => $this->enrolledCourseId($user, $arguments),
            'title' => mb_substr($title, 0, 255),
            'description' => trim((string) ($arguments['description'] ?? '')) ?: null,
            'due_date' => $dueDate,
            'priority' => TaskPriority::tryFrom((string) ($arguments['priority'] ?? '')) ?? TaskPriority::MEDIUM,
            'is_completed' => false,
        ]);

        return [
            'data' => [
                'task_id' => $task->id,
                'title' => $task->title,
                'due_date' => $task->due_date->toDateString(),
                'priority' => $task->priority->value,
            ],
            'summary' => "Added \"{$task->title}\" due {$task->due_date->format('M j')}",
            'link' => route('tasks'),
            'linkLabel' => 'Open tasks',
        ];
    }

    /**
     * Only attach a course the student is actually enrolled in.
     */
    private function enrolledCourseId(User $user, array $arguments): ?int
    {
        $courseId = (int) ($arguments['course_id'] ?? 0);
        if ($courseId <= 0) {
            return null;
        }

        return $user->enrolledCourses()
            ->wherePivotNotIn('status', ['pending'])
            ->where('courses.id', $courseId)
            ->exists() ? $courseId : null;
    }
}
