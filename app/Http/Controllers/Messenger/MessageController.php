<?php

declare(strict_types=1);

namespace App\Http\Controllers\Messenger;

use App\Domain\User\Models\User;
use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Messenger\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Policies\ConversationPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Direct messaging between a student and a faculty member they share a section
 * with. Shared by both roles — authorization is relationship-based
 * ({@see User::canMessage()} to start, {@see ConversationPolicy}
 * to access), not role-based.
 *
 * The database is the source of truth: every message is persisted, then
 * broadcast best-effort. Clients render history from these endpoints and use
 * {@see self::index()} (with `after`) as the realtime fallback poll.
 */
class MessageController extends Controller
{
    /** Page size for history pagination and the initial conversation load. */
    private const PAGE_SIZE = 30;

    /**
     * Resolve (or create) the 1:1 conversation with a target user and return it
     * with the most recent messages. Called when the user opens a contact.
     */
    public function resolve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'with' => ['required', 'integer', 'exists:users,id'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $target = User::findOrFail($validated['with']);

        if (! $user->canMessage($target)) {
            throw new AuthorizationException('You are not allowed to message this user.');
        }

        $conversation = Conversation::betweenOrCreate($user, $target);

        $messages = $conversation->messages()
            ->latest('id')
            ->limit(self::PAGE_SIZE)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages' => MessageResource::collection($messages),
        ]);
    }

    /**
     * Conversation history. Three modes:
     *   - `after={id}`  → messages newer than {id}, ascending (realtime poll)
     *   - `before={id}` → the page of messages older than {id} (scroll-back)
     *   - neither       → the most recent page
     */
    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $query = $conversation->messages()->getQuery();

        if ($after = $request->integer('after')) {
            // Cap the catch-up batch: the client advances its cursor to the last
            // id it received and polls again for the rest, so a long gap can never
            // return an unbounded flood of messages in one response.
            $messages = $query->where('id', '>', $after)
                ->orderBy('id')
                ->limit(self::PAGE_SIZE)
                ->get();
        } else {
            $messages = $query
                ->when($before = $request->integer('before'), fn ($q) => $q->where('id', '<', $before))
                ->latest('id')
                ->limit(self::PAGE_SIZE)
                ->get()
                ->reverse()
                ->values();
        }

        return response()->json([
            'messages' => MessageResource::collection($messages),
        ]);
    }

    /**
     * Persist a message, then broadcast it best-effort. Persistence is the
     * commit point — a broadcast failure is logged and swallowed so a websocket
     * hiccup can never fail the send (the recipient still gets it on next poll).
     */
    public function store(StoreMessageRequest $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('send', $conversation);

        /** @var Message $message */
        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        $conversation->forceFill(['last_message_at' => $message->created_at])->save();

        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable $exception) {
            Log::warning('Message broadcast failed; delivered via persistence only.', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => new MessageResource($message),
        ], 201);
    }

    /**
     * Lightweight state for the contact list, polled (~10s) while the messenger
     * page is open. Returns, for the current user:
     *   - presence: which of the given contact ids are currently active
     *   - conversations: last-message preview + unread count, for list ordering
     * The open conversation itself updates instantly via Ably; this only keeps
     * the *list* (closed conversations) fresh without a per-user socket.
     */
    public function overview(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $contactIds = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values();

        // One query gives both "is active now" and "last seen" so the contact
        // list can show a Messenger-style presence/last-active label.
        $seen = User::whereIn('id', $contactIds)
            ->get(['id', 'last_seen_at'])
            ->keyBy('id');

        $activeThreshold = now()->subSeconds(User::ACTIVE_WINDOW_SECONDS);

        $presence = $contactIds->mapWithKeys(fn (int $id) => [
            $id => (bool) $seen->get($id)?->last_seen_at?->gte($activeThreshold),
        ]);

        $lastActive = $contactIds->mapWithKeys(fn (int $id) => [
            $id => $seen->get($id)?->last_seen_at?->toISOString(),
        ]);

        $conversations = $user->conversations()
            ->with(['latestMessage', 'participants:id'])
            ->get()
            ->map(function (Conversation $conversation) use ($user) {
                $other = $conversation->otherParticipant($user);
                $lastReadId = (int) $conversation->participants
                    ->firstWhere('id', $user->id)?->pivot?->last_read_message_id;

                return [
                    'user_id' => $other?->id,
                    'last_body' => $conversation->latestMessage?->body,
                    'last_at' => $conversation->latestMessage?->created_at?->toISOString(),
                    'last_sender_id' => $conversation->latestMessage?->sender_id,
                    'unread' => $conversation->messages()
                        ->where('id', '>', $lastReadId)
                        ->where('sender_id', '!=', $user->id)
                        ->count(),
                ];
            })
            ->filter(fn (array $row) => $row['user_id'] !== null)
            ->values();

        return response()->json([
            'presence' => $presence,
            'lastActive' => $lastActive,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Advance the current user's read cursor to the latest message — clears the
     * unread badge. Called when a conversation is opened/viewed.
     */
    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $latestId = $conversation->messages()->max('id');

        $conversation->participants()->updateExistingPivot(
            $request->user()->id,
            ['last_read_message_id' => $latestId],
        );

        return response()->json(['read_up_to' => $latestId]);
    }
}
