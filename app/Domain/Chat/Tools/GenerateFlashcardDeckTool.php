<?php

declare(strict_types=1);

namespace App\Domain\Chat\Tools;

use App\Domain\Academic\Services\FlashcardService;
use App\Domain\Chat\Contracts\ChatToolInterface;
use App\Domain\User\Models\User;

/**
 * Generate a spaced-repetition flashcard deck on a topic and save it to the
 * student's Flashcards area.
 */
class GenerateFlashcardDeckTool implements ChatToolInterface
{
    public function __construct(private readonly FlashcardService $flashcards) {}

    public function name(): string
    {
        return 'generate_flashcard_deck';
    }

    public function label(): string
    {
        return 'Generating flashcards';
    }

    public function description(): string
    {
        return 'Create a flashcard deck (front/back cards, SM-2 spaced repetition) on a topic and save '
            .'it to the student\'s Flashcards area. Call this when the student asks for flashcards or '
            .'revision cards. Optionally attach it to one of their courses via course_id.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'topic' => [
                    'type' => 'string',
                    'description' => 'The topic to make flashcards for.',
                ],
                'count' => [
                    'type' => 'integer',
                    'description' => 'Number of cards (1-30, default 10).',
                ],
                'difficulty' => [
                    'type' => 'string',
                    'enum' => ['easy', 'medium', 'hard'],
                    'description' => 'Card difficulty (default medium).',
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
        abort_if($topic === '', 422, 'A topic is required to generate flashcards.');

        $deck = $this->flashcards->generateDeck($user, [
            'topic' => $topic,
            'count' => max(1, min(30, (int) ($arguments['count'] ?? 10))),
            'difficulty' => in_array($arguments['difficulty'] ?? '', ['easy', 'medium', 'hard'], true)
                ? $arguments['difficulty']
                : 'medium',
            'course_id' => $this->enrolledCourseId($user, $arguments),
        ]);

        $cardCount = $deck->cards()->count();

        return [
            'data' => [
                'deck_id' => $deck->id,
                'title' => $deck->title,
                'card_count' => $cardCount,
            ],
            'summary' => "Created deck \"{$deck->title}\" ({$cardCount} cards)",
            'link' => route('flashcards.show', $deck),
            'linkLabel' => 'Study the deck',
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
