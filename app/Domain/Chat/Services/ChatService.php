<?php

namespace App\Domain\Chat\Services;

use App\Domain\User\Models\User;
use App\Enums\ChatMode;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Services\ActivityLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Manages chat sessions and messages, delegating answer generation to the
 * RAG engine and persisting the full conversation + citations.
 */
class ChatService
{
    public function __construct(
        private readonly RagChatService $rag,
        private readonly ActivityLogger $activity,
    ) {}

    /**
     * Send a user message and generate the assistant reply.
     *
     * @return array{session: ChatSession, userMessage: ChatMessage, assistantMessage: ChatMessage}
     */
    public function sendMessage(
        User $user,
        ?ChatSession $session,
        string $content,
        ChatMode $mode = ChatMode::ACADEMIC,
        string $language = 'en',
    ): array {
        $session ??= $this->startSession($user, $mode, $language);

        // Persist the user's message.
        $userMessage = $session->messages()->create([
            'role' => ChatMessage::ROLE_USER,
            'content' => $content,
        ]);

        // Build short history (prior turns) for context.
        $history = $session->messages()
            ->where('id', '<', $userMessage->id)
            ->latest()
            ->take(6)
            ->get()
            ->reverse()
            ->map(fn (ChatMessage $m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->all();

        $answer = $this->rag->answer($content, $user, $session->mode, $history);

        $assistantMessage = $session->messages()->create([
            'role' => ChatMessage::ROLE_ASSISTANT,
            'content' => $answer['content'],
            'confidence' => $answer['confidence'],
            'confidence_level' => $answer['confidence_level'],
            'sources' => $answer['sources'],
            'follow_ups' => $answer['follow_ups'],
            'model' => $answer['model'],
            'tokens' => $answer['tokens'],
        ]);

        // Persist citations relationally for analytics.
        foreach ($answer['sources'] as $source) {
            $assistantMessage->citations()->create([
                'document_id' => $source['document_id'] ?? null,
                'document_chunk_id' => $source['id'] ?? null,
                'relevance' => $source['confidence'] ?? null,
            ]);
        }

        // Title the session from its first user message.
        if ($session->messages()->where('role', ChatMessage::ROLE_USER)->count() === 1) {
            $session->update(['title' => Str::limit($content, 50)]);
        }

        $session->update(['last_message_at' => now()]);
        $this->activity->log('chat.message', 'Asked the AI assistant', $session, ['mode' => $session->mode->value], $user);

        return [
            'session' => $session->fresh(),
            'userMessage' => $userMessage,
            'assistantMessage' => $assistantMessage,
        ];
    }

    public function startSession(User $user, ChatMode $mode = ChatMode::ACADEMIC, string $language = 'en'): ChatSession
    {
        return $user->chatSessions()->create([
            'mode' => $mode,
            'language' => $language,
        ]);
    }

    /**
     * @return Collection<int, ChatSession>
     */
    public function sessionsFor(User $user): Collection
    {
        return $user->chatSessions()
            ->withCount('messages')
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();
    }

    public function deleteSession(ChatSession $session): void
    {
        $session->delete();
    }
}
