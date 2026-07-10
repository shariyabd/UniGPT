<?php

namespace App\Http\Controllers\Student;

use App\Domain\Chat\Services\ChatService;
use App\Domain\Chat\Support\AiSettings;
use App\Domain\User\Models\User;
use App\Enums\ChatMode;
use App\Http\Concerns\StreamsServerSentEvents;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SendMessageRequest;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ChatController extends Controller
{
    use StreamsServerSentEvents;

    public function __construct(
        private readonly ChatService $chat,
        private readonly AiSettings $aiSettings,
    ) {}

    public function index(): Response
    {
        $user = request()->user();

        $courses = $user->enrolledCourses()
            ->wherePivotNotIn('status', ['pending'])
            ->get(['courses.id', 'courses.code', 'courses.name'])
            ->map(fn ($course) => [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
            ])
            ->all();

        return Inertia::render('Student/Chat', [
            'sessions' => $this->chat->sessionsFor($user)->map(fn (ChatSession $s) => $this->presentSession($s)),
            'studentContext' => [
                'name' => $user->name,
                'department' => $user->department?->name,
                'semester' => $user->semester,
                'currentCourses' => $courses,
            ],
            'modes' => collect(ChatMode::cases())->map(fn ($m) => ['value' => $m->value, 'label' => $m->label()]),
            'supportedLanguages' => $this->aiSettings->supportedLanguages(),
            // Prefer the student's saved language preference when it's still supported.
            'defaultLanguage' => $this->preferredLanguage($user),
            // AI-chat access state, so a blocked student sees the admin's note
            // instead of the composer.
            'aiAccess' => [
                'blocked' => $user->isAiChatBlocked(),
                'reason' => $user->ai_chat_block_reason,
                'blockedUntil' => $user->ai_chat_blocked_until?->toIso8601String(),
            ],
        ]);
    }

    public function store(SendMessageRequest $request): JsonResponse
    {
        [$session, $mode, $language, $message] = $this->resolveSendContext($request);

        $result = $this->chat->sendMessage(
            user: $request->user(),
            session: $session,
            content: $message,
            mode: $mode,
            language: $language,
        );

        return response()->json([
            'session' => $this->presentSession($result['session']),
            'userMessage' => $this->presentMessage($result['userMessage']),
            'assistantMessage' => $this->presentMessage($result['assistantMessage']),
        ]);
    }

    /**
     * Streaming variant of store(): emits `delta` events with assistant text
     * as it is generated, then a final `done` event carrying the same payload
     * store() returns. Errors mid-stream become an `error` event (the HTTP
     * status is already sent by then).
     */
    public function stream(SendMessageRequest $request): StreamedResponse
    {
        [$session, $mode, $language, $message] = $this->resolveSendContext($request);
        $user = $request->user();

        return $this->sseResponse(function () use ($user, $session, $mode, $language, $message) {
            try {
                $result = $this->chat->sendMessage(
                    user: $user,
                    session: $session,
                    content: $message,
                    mode: $mode,
                    language: $language,
                    onDelta: fn (string $text) => $this->sseSend('delta', ['text' => $text]),
                );

                $this->sseSend('done', [
                    'session' => $this->presentSession($result['session']),
                    'userMessage' => $this->presentMessage($result['userMessage']),
                    'assistantMessage' => $this->presentMessage($result['assistantMessage']),
                ]);
            } catch (Throwable $e) {
                Log::error('Chat stream failed: '.$e->getMessage());
                $this->sseSend('error', ['message' => 'Something went wrong while generating a response. Please try again.']);
            }
        });
    }

    /**
     * Session/mode/language/message resolution shared by the JSON and SSE
     * send endpoints.
     *
     * @return array{0: ?ChatSession, 1: ChatMode, 2: string, 3: string}
     */
    private function resolveSendContext(SendMessageRequest $request): array
    {
        $validated = $request->validated();

        $session = null;
        if (! empty($validated['session_id'])) {
            $session = ChatSession::where('user_id', $request->user()->id)->find($validated['session_id']);
        }

        $mode = ChatMode::tryFrom($validated['mode'] ?? '') ?? ChatMode::ACADEMIC;

        $language = $validated['language'] ?? '';
        if (! $this->aiSettings->isSupportedLanguage($language)) {
            $language = $this->aiSettings->defaultLanguageCode();
        }

        return [$session, $mode, $language, $validated['message']];
    }

    public function show(ChatSession $session): JsonResponse
    {
        Gate::authorize('view', $session);

        return response()->json([
            'session' => $this->presentSession($session),
            'messages' => $session->messages()->with('citations')->get()
                ->map(fn (ChatMessage $m) => $this->presentMessage($m)),
        ]);
    }

    public function destroy(ChatSession $session): JsonResponse
    {
        Gate::authorize('delete', $session);
        $this->chat->deleteSession($session);

        return response()->json(['ok' => true]);
    }

    public function togglePin(ChatSession $session): JsonResponse
    {
        Gate::authorize('view', $session);
        $this->chat->togglePin($session);

        return response()->json([
            'session' => $this->presentSession($session),
        ]);
    }

    public function rename(Request $request, ChatSession $session): JsonResponse
    {
        Gate::authorize('view', $session);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
        ]);

        $this->chat->rename($session, trim($validated['title']));

        return response()->json([
            'session' => $this->presentSession($session),
        ]);
    }

    public function archive(ChatSession $session): JsonResponse
    {
        Gate::authorize('view', $session);
        $this->chat->archive($session);

        return response()->json(['ok' => true]);
    }

    public function unarchive(ChatSession $session): JsonResponse
    {
        Gate::authorize('view', $session);
        $this->chat->unarchive($session);

        return response()->json(['ok' => true]);
    }

    public function archived(): Response
    {
        $user = request()->user();

        return Inertia::render('Student/ArchivedChats', [
            'sessions' => $this->chat->archivedSessionsFor($user)->map(fn (ChatSession $s) => $this->presentSession($s)),
        ]);
    }

    /**
     * The student's saved language preference when it is still an enabled
     * language, otherwise the admin-configured default.
     */
    private function preferredLanguage(User $user): string
    {
        $preferred = $user->preferences['language'] ?? null;

        if (is_string($preferred) && $this->aiSettings->isSupportedLanguage($preferred)) {
            return $preferred;
        }

        return $this->aiSettings->defaultLanguageCode();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentSession(ChatSession $session): array
    {
        return [
            'id' => $session->id,
            'title' => $session->title,
            'mode' => $session->mode->value,
            'pinned' => (bool) $session->is_pinned,
            'messageCount' => $session->messages_count ?? $session->messages()->count(),
            'lastMessage' => optional($session->last_message_at)->diffForHumans(),
            'timestamp' => optional($session->last_message_at ?? $session->created_at)->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentMessage(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'timestamp' => $message->created_at?->toIso8601String(),
            'confidence' => $message->confidence,
            'confidenceLevel' => $message->confidence_level,
            'sources' => $message->sources ?? [],
            'followUpSuggestions' => $message->follow_ups ?? [],
            'saved' => false,
        ];
    }
}
