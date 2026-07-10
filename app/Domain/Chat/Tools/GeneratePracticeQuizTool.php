<?php

declare(strict_types=1);

namespace App\Domain\Chat\Tools;

use App\Domain\Academic\Services\PracticeQuizService;
use App\Domain\Chat\Contracts\ChatToolInterface;
use App\Domain\User\Models\User;

/**
 * Generate a practice quiz on a topic and save it to the student's practice
 * area, returning a link to take it.
 */
class GeneratePracticeQuizTool implements ChatToolInterface
{
    public function __construct(private readonly PracticeQuizService $quizzes) {}

    public function name(): string
    {
        return 'generate_practice_quiz';
    }

    public function label(): string
    {
        return 'Generating a practice quiz';
    }

    public function description(): string
    {
        return 'Create a self-practice quiz (multiple-choice / true-false, instantly auto-graded) on a '
            .'topic and save it to the student\'s Practice area. Call this when the student asks to be '
            .'quizzed or wants practice questions. Optionally attach it to one of their courses via '
            .'course_id from list_my_courses.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'topic' => [
                    'type' => 'string',
                    'description' => 'The topic to quiz the student on.',
                ],
                'question_count' => [
                    'type' => 'integer',
                    'description' => 'Number of questions (1-15, default 5).',
                ],
                'difficulty' => [
                    'type' => 'string',
                    'enum' => ['easy', 'medium', 'hard'],
                    'description' => 'Quiz difficulty (default medium).',
                ],
                'course_id' => [
                    'type' => 'integer',
                    'description' => 'Optional id of one of the student\'s enrolled courses.',
                ],
            ],
            'required' => ['topic'],
        ];
    }

    public function execute(User $user, array $arguments): array
    {
        $topic = trim((string) ($arguments['topic'] ?? ''));
        abort_if($topic === '', 422, 'A topic is required to generate a quiz.');

        $quiz = $this->quizzes->generate($user, [
            'topic' => $topic,
            'question_count' => max(1, min(15, (int) ($arguments['question_count'] ?? 5))),
            'difficulty' => in_array($arguments['difficulty'] ?? '', ['easy', 'medium', 'hard'], true)
                ? $arguments['difficulty']
                : 'medium',
            'course_id' => $this->enrolledCourseId($user, $arguments),
        ]);

        $count = count($quiz->questions ?? []);

        return [
            'data' => [
                'quiz_id' => $quiz->id,
                'title' => $quiz->title,
                'question_count' => $count,
                'difficulty' => $quiz->difficulty,
            ],
            'summary' => "Created \"{$quiz->title}\" ({$count} questions)",
            'link' => route('practice.show', $quiz),
            'linkLabel' => 'Take the quiz',
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
