<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\SavedAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SavedAnswerController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $answers = $user->savedAnswers()->latest()->get();

        $folders = $answers->groupBy('folder')->map(fn ($items, $name) => [
            'id' => Str::slug($name) ?: 'general',
            'name' => $name,
            'count' => $items->count(),
        ])->values();

        return Inertia::render('Student/SavedAnswers', [
            'savedAnswers' => $answers->map(fn (SavedAnswer $a) => $this->present($a)),
            'folders' => $folders,
            'categories' => $answers->pluck('category')->unique()->values(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'chat_message_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'folder' => ['nullable', 'string', 'max:100'],
        ]);

        $message = ChatMessage::with('session')->findOrFail($validated['chat_message_id']);
        abort_unless($message->session->user_id === $request->user()->id, 403);

        // Derive the question from the preceding user message.
        $question = ChatMessage::where('chat_session_id', $message->chat_session_id)
            ->where('id', '<', $message->id)
            ->where('role', ChatMessage::ROLE_USER)
            ->latest('id')
            ->value('content');

        $saved = $request->user()->savedAnswers()->updateOrCreate(
            ['chat_message_id' => $message->id],
            [
                'title' => Str::limit($question ?? 'Saved answer', 60),
                'question' => $question,
                'answer' => $message->content,
                'category' => $message->session->mode->label(),
                'sources' => $message->sources,
                'confidence' => $message->confidence,
                'notes' => $validated['notes'] ?? null,
                'folder' => $validated['folder'] ?? 'General',
            ]
        );

        if ($request->wantsJson()) {
            return response()->json(['saved' => $this->present($saved)]);
        }

        return back()->with('success', 'Answer saved.');
    }

    public function update(Request $request, SavedAnswer $savedAnswer): RedirectResponse
    {
        abort_unless($savedAnswer->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'folder' => ['sometimes', 'string', 'max:100'],
            'starred' => ['sometimes', 'boolean'],
        ]);

        $savedAnswer->update($validated);

        return back()->with('success', 'Saved answer updated.');
    }

    public function destroy(Request $request, SavedAnswer $savedAnswer): RedirectResponse
    {
        abort_unless($savedAnswer->user_id === $request->user()->id, 403);
        $savedAnswer->delete();

        return back()->with('success', 'Saved answer removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(SavedAnswer $answer): array
    {
        return [
            'id' => $answer->id,
            'title' => $answer->title,
            'question' => $answer->question,
            'answer' => $answer->answer,
            'category' => $answer->category,
            'tags' => $answer->tags ?? [],
            'sources' => $answer->sources ?? [],
            'confidence' => $answer->confidence,
            'notes' => $answer->notes,
            'folder' => $answer->folder,
            'starred' => $answer->starred,
            'viewCount' => $answer->view_count,
            'savedAt' => $answer->created_at?->toIso8601String(),
            'lastViewed' => optional($answer->last_viewed_at)->toIso8601String(),
            'chatId' => $answer->chat_message_id,
        ];
    }
}
