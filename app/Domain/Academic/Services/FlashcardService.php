<?php

declare(strict_types=1);

namespace App\Domain\Academic\Services;

use App\Domain\Chat\Services\TeachingAssistantService;
use App\Domain\User\Models\User;
use App\Models\Flashcard;
use App\Models\FlashcardDeck;
use Illuminate\Support\Collection;

/**
 * Personal flashcard decks with SM-2 spaced-repetition scheduling. Decks are
 * single-owner; AI generation reuses the shared TeachingAssistantService.
 */
class FlashcardService
{
    public function __construct(private readonly TeachingAssistantService $assistant) {}

    /**
     * Decks for a student, each with total and due-now card counts.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function decksFor(User $student): Collection
    {
        return FlashcardDeck::where('user_id', $student->id)
            ->with('course:id,code')
            ->withCount([
                'cards',
                'cards as due_count' => fn ($query) => $query->due(),
            ])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (FlashcardDeck $deck) => [
                'id' => $deck->id,
                'title' => $deck->title,
                'description' => $deck->description,
                'source' => $deck->source,
                'course' => $deck->course?->code,
                'cardCount' => $deck->cards_count,
                'dueCount' => $deck->due_count,
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDeck(User $student, array $data, string $source = 'manual'): FlashcardDeck
    {
        return FlashcardDeck::create([
            'user_id' => $student->id,
            'course_id' => $data['course_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'source' => $source,
        ]);
    }

    /**
     * Create a deck and populate it with AI-generated cards for a topic.
     *
     * @param  array<string, mixed>  $data
     */
    public function generateDeck(User $student, array $data): FlashcardDeck
    {
        $cards = $this->assistant->generateFlashcards([
            'topic' => $data['topic'],
            'count' => $data['count'] ?? 10,
            'difficulty' => $data['difficulty'] ?? 'medium',
        ]);

        $deck = $this->createDeck($student, [
            'course_id' => $data['course_id'] ?? null,
            'title' => $data['title'] ?? $data['topic'],
            'description' => 'AI-generated from topic: '.$data['topic'],
        ], source: 'ai');

        $this->replaceCards($deck, $cards);

        return $deck;
    }

    /**
     * Replace a deck's cards, preserving position order.
     *
     * @param  array<int, array{front: string, back: string}>  $cards
     */
    public function replaceCards(FlashcardDeck $deck, array $cards): void
    {
        $deck->cards()->delete();

        $position = 0;
        foreach ($cards as $card) {
            $deck->cards()->create([
                'front' => $card['front'],
                'back' => $card['back'],
                'position' => $position++,
            ]);
        }
    }

    /**
     * Apply an SM-2 review to a card given a recall quality (0–5) and reschedule.
     */
    public function review(Flashcard $card, int $quality): Flashcard
    {
        $quality = max(0, min(5, $quality));

        $ease = $card->ease_factor;
        $repetitions = $card->repetitions;
        $interval = $card->interval_days;

        if ($quality < 3) {
            // Failed recall — reset the repetition streak and re-show tomorrow.
            $repetitions = 0;
            $interval = 1;
        } else {
            $repetitions++;
            $interval = match ($repetitions) {
                1 => 1,
                2 => 6,
                default => (int) round($interval * $ease),
            };
        }

        // SM-2 ease-factor adjustment, floored at 1.3.
        $ease = $ease + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02));
        $ease = max(1.3, round($ease, 2));

        $card->update([
            'ease_factor' => $ease,
            'repetitions' => $repetitions,
            'interval_days' => $interval,
            'due_at' => now()->addDays(max(1, $interval)),
            'last_reviewed_at' => now(),
        ]);

        return $card;
    }
}
