<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Domain\Academic\Services\FlashcardService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\FlashcardDeckRequest;
use App\Http\Requests\Student\FlashcardGenerateRequest;
use App\Models\Flashcard;
use App\Models\FlashcardDeck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Personal flashcards — deck management, AI generation, and SM-2 review.
 */
class FlashcardController extends Controller
{
    public function __construct(private readonly FlashcardService $flashcards) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Student/Flashcards/Index', [
            'decks' => $this->flashcards->decksFor($user),
            'courses' => $user->enrolledCourses()
                ->wherePivotNotIn('status', ['pending'])
                ->get(['courses.id', 'courses.code', 'courses.name']),
        ]);
    }

    public function show(Request $request, FlashcardDeck $deck): Response
    {
        $this->authorizeOwner($request, $deck);

        $deck->load('cards', 'course:id,code');

        return Inertia::render('Student/Flashcards/Study', [
            'deck' => [
                'id' => $deck->id,
                'title' => $deck->title,
                'description' => $deck->description,
                'course' => $deck->course?->code,
                'cards' => $deck->cards->map(fn (Flashcard $card) => [
                    'id' => $card->id,
                    'front' => $card->front,
                    'back' => $card->back,
                    'isDue' => $card->due_at === null || $card->due_at->isPast(),
                    'dueAt' => $card->due_at?->toDateString(),
                ]),
            ],
        ]);
    }

    public function store(FlashcardDeckRequest $request): RedirectResponse
    {
        $this->flashcards->createDeck($request->user(), $request->validated());

        return back()->with('success', 'Deck created.');
    }

    public function generate(FlashcardGenerateRequest $request): RedirectResponse
    {
        $deck = $this->flashcards->generateDeck($request->user(), $request->validated());

        return redirect()
            ->route('flashcards.show', $deck)
            ->with('success', 'AI flashcards generated.');
    }

    public function update(FlashcardDeckRequest $request, FlashcardDeck $deck): RedirectResponse
    {
        $this->authorizeOwner($request, $deck);

        $deck->update($request->validated());

        return back()->with('success', 'Deck updated.');
    }

    public function destroy(Request $request, FlashcardDeck $deck): RedirectResponse
    {
        $this->authorizeOwner($request, $deck);

        $deck->delete();

        return redirect()->route('flashcards.index')->with('success', 'Deck deleted.');
    }

    public function storeCard(Request $request, FlashcardDeck $deck): RedirectResponse
    {
        $this->authorizeOwner($request, $deck);

        $data = $request->validate([
            'front' => ['required', 'string', 'max:2000'],
            'back' => ['required', 'string', 'max:2000'],
        ]);

        $deck->cards()->create([
            ...$data,
            'position' => (int) $deck->cards()->max('position') + 1,
        ]);

        return back()->with('success', 'Card added.');
    }

    public function updateCard(Request $request, Flashcard $card): RedirectResponse
    {
        $this->authorizeCardOwner($request, $card);

        $data = $request->validate([
            'front' => ['required', 'string', 'max:2000'],
            'back' => ['required', 'string', 'max:2000'],
        ]);

        $card->update($data);

        return back()->with('success', 'Card updated.');
    }

    public function destroyCard(Request $request, Flashcard $card): RedirectResponse
    {
        $this->authorizeCardOwner($request, $card);

        $card->delete();

        return back()->with('success', 'Card deleted.');
    }

    public function review(Request $request, Flashcard $card): RedirectResponse
    {
        $this->authorizeCardOwner($request, $card);

        $data = $request->validate([
            'quality' => ['required', 'integer', 'min:0', 'max:5'],
        ]);

        $this->flashcards->review($card, $data['quality']);

        return back();
    }

    private function authorizeOwner(Request $request, FlashcardDeck $deck): void
    {
        abort_unless($deck->user_id === $request->user()->id, 403);
    }

    private function authorizeCardOwner(Request $request, Flashcard $card): void
    {
        abort_unless($card->deck->user_id === $request->user()->id, 403);
    }
}
