<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Services;

use App\Domain\User\Models\User;
use App\Models\ChatMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Aggregates AI-chat token usage and per-request queries for the admin AI-usage
 * monitor, so admins can spot wasteful/irrelevant usage and control API costs.
 *
 * Token usage lives on assistant messages; the matching user query is the user
 * message immediately preceding the assistant reply in the same session.
 */
class AiUsageService
{
    /**
     * Headline counters for the dashboard cards.
     *
     * @return array{total_tokens: int, total_requests: int, active_users: int, blocked_users: int}
     */
    public function summary(): array
    {
        $assistant = ChatMessage::query()->where('role', ChatMessage::ROLE_ASSISTANT);

        return [
            'total_tokens' => (int) (clone $assistant)->sum('tokens'),
            'total_requests' => (clone $assistant)->count(),
            'active_users' => User::query()->whereHas('chatMessages')->count(),
            'blocked_users' => User::query()
                ->whereNotNull('ai_chat_blocked_at')
                ->where(function (Builder $query): void {
                    $query->whereNull('ai_chat_blocked_until')
                        ->orWhere('ai_chat_blocked_until', '>', now());
                })
                ->count(),
        ];
    }

    /**
     * Per-user usage rollup (request count, token totals, last activity, block
     * status), ordered by heaviest token usage first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function perUser(): Collection
    {
        return User::query()
            ->whereHas('chatMessages')
            ->withCount(['chatMessages as request_count' => fn (Builder $query) => $query->where('role', ChatMessage::ROLE_ASSISTANT)])
            ->withSum(['chatMessages as total_tokens' => fn (Builder $query) => $query->where('role', ChatMessage::ROLE_ASSISTANT)], 'tokens')
            ->withMax('chatMessages as last_used_at', 'created_at')
            ->with('roles:id,name,slug')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
                'request_count' => (int) $user->request_count,
                'total_tokens' => (int) ($user->total_tokens ?? 0),
                'last_used_at' => $user->last_used_at,
                'is_blocked' => $user->isAiChatBlocked(),
                'block_reason' => $user->ai_chat_block_reason,
                'blocked_until' => $user->ai_chat_blocked_until?->toIso8601String(),
            ])
            ->sortByDesc('total_tokens')
            ->values();
    }

    /**
     * Recent AI requests, newest first: each assistant reply paired with the
     * student's query and its token cost.
     *
     * @return LengthAwarePaginator<ChatMessage>
     */
    public function recentRequests(int $perPage = 20): LengthAwarePaginator
    {
        $requests = ChatMessage::query()
            ->where('role', ChatMessage::ROLE_ASSISTANT)
            ->with(['session:id,user_id,title', 'session.user:id,name,email'])
            ->latest()
            ->paginate($perPage);

        $queries = $this->queriesForRequests($requests->getCollection());

        $requests->setCollection(
            $requests->getCollection()->map(fn (ChatMessage $message): array => [
                'id' => $message->id,
                'user' => $message->session?->user
                    ? ['id' => $message->session->user->id, 'name' => $message->session->user->name]
                    : null,
                'query' => $queries[$message->id] ?? '(query unavailable)',
                'tokens' => (int) $message->tokens,
                'model' => $message->model,
                'confidence' => $message->confidence,
                'created_at' => $message->created_at?->toIso8601String(),
            ])
        );

        return $requests;
    }

    /**
     * Resolve the originating user query for each assistant message: the latest
     * user message in the same session sent before that reply. Loaded in one
     * batched query (grouped by session) to avoid N+1.
     *
     * @param  Collection<int, ChatMessage>  $assistantMessages
     * @return array<int, string> Assistant message id => query text.
     */
    private function queriesForRequests(Collection $assistantMessages): array
    {
        $sessionIds = $assistantMessages->pluck('chat_session_id')->unique()->all();

        $userMessages = ChatMessage::query()
            ->where('role', ChatMessage::ROLE_USER)
            ->whereIn('chat_session_id', $sessionIds)
            ->get(['id', 'chat_session_id', 'content'])
            ->groupBy('chat_session_id');

        $resolved = [];
        foreach ($assistantMessages as $assistant) {
            $candidate = ($userMessages[$assistant->chat_session_id] ?? collect())
                ->filter(fn (ChatMessage $userMessage): bool => $userMessage->id < $assistant->id)
                ->sortByDesc('id')
                ->first();

            if ($candidate) {
                $resolved[$assistant->id] = $candidate->content;
            }
        }

        return $resolved;
    }
}
