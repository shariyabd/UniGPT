<?php

declare(strict_types=1);

namespace App\Domain\Chat\Tools;

use App\Domain\Chat\Contracts\ChatToolInterface;
use App\Domain\User\Models\User;

/**
 * The student's active course enrollments with ids, so other tools that
 * accept a course_id (quiz/flashcard generation, tasks) can be targeted.
 */
class ListMyCoursesTool implements ChatToolInterface
{
    public function name(): string
    {
        return 'list_my_courses';
    }

    public function label(): string
    {
        return 'Looking up your courses';
    }

    public function description(): string
    {
        return 'List the student\'s currently enrolled courses with their numeric ids, codes and names. '
            .'Call this when you need a course_id for another tool, or when the student asks which '
            .'courses they are taking.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [],
            'required' => [],
        ];
    }

    public function execute(User $user, array $arguments): array
    {
        $courses = $user->enrolledCourses()
            ->wherePivotNotIn('status', ['pending'])
            ->get(['courses.id', 'courses.code', 'courses.name'])
            ->map(fn ($course) => [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
            ])
            ->unique('id')
            ->values()
            ->all();

        return [
            'data' => ['courses' => $courses],
            'summary' => count($courses).' enrolled course'.(count($courses) === 1 ? '' : 's'),
        ];
    }
}
