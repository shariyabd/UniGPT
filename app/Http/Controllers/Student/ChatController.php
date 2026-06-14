<?php

namespace App\Http\Controllers\Student;

use App\Domain\Chat\Services\ChatService;
use App\Enums\ChatMode;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chat) {}

    public function index(): Response
    {
        $user = request()->user();

        return Inertia::render('Student/Chat', [
            'sessions' => $this->chat->sessionsFor($user)->map(fn (ChatSession $s) => $this->presentSession($s)),
            'studentContext' => [
                'name' => $user->name,
                'department' => $user->department?->name,
                'semester' => $user->semester,
                // Populated once the academic module (enrollments) is in place.
                'currentCourses' => method_exists($user, 'enrolledCourses')
                    ? $user->enrolledCourses()->pluck('code')->all()
                    : [],
            ],
            'modes' => collect(ChatMode::cases())->map(fn ($m) => ['value' => $m->value, 'label' => $m->label()]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'session_id' => ['nullable', 'integer'],
            'mode' => ['nullable', 'string'],
        ]);

        $session = null;
        if (! empty($validated['session_id'])) {
            $session = ChatSession::where('user_id', $request->user()->id)->find($validated['session_id']);
        }

        $mode = ChatMode::tryFrom($validated['mode'] ?? '') ?? ChatMode::ACADEMIC;

        $result = $this->chat->sendMessage($request->user(), $session, $validated['message'], $mode);

        return response()->json([
            'session' => $this->presentSession($result['session']),
            'userMessage' => $this->presentMessage($result['userMessage']),
            'assistantMessage' => $this->presentMessage($result['assistantMessage']),
        ]);
    }

    public function show(ChatSession $session): JsonResponse
    {
        $this->authorizeSession($session);

        return response()->json([
            'session' => $this->presentSession($session),
            'messages' => $session->messages()->with('citations')->get()
                ->map(fn (ChatMessage $m) => $this->presentMessage($m)),
        ]);
    }

    public function destroy(ChatSession $session): RedirectResponse
    {
        $this->authorizeSession($session);
        $this->chat->deleteSession($session);

        return back()->with('success', 'Conversation deleted.');
    }

    private function authorizeSession(ChatSession $session): void
    {
        abort_unless($session->user_id === request()->user()->id, 403);
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
