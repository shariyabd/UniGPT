<?php

namespace App\Http\Controllers\Faculty;

use App\Domain\Academic\Services\CourseManagementService;
use App\Domain\Academic\Services\CourseService;
use App\Domain\Chat\Services\ChatService;
use App\Domain\Chat\Services\TeachingAssistantService;
use App\Domain\Notification\Services\NotificationService;
use App\Enums\ChatMode;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Faculty\AssistantChatRequest;
use App\Http\Requests\Faculty\GenerateAssignmentRequest;
use App\Http\Requests\Faculty\GenerateQuizRequest;
use App\Http\Requests\Faculty\PublishContentRequest;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Course;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AIAssistantController extends Controller
{
    public function __construct(
        private readonly ChatService $chat,
        private readonly TeachingAssistantService $assistant,
        private readonly CourseService $courses,
    ) {}

    public function index(): Response
    {
        $user = request()->user();

        return Inertia::render('Faculty/AIAssistant', [
            'sessions' => $this->chat->sessionsFor($user)->map(fn (ChatSession $s) => $this->presentSession($s)),
            'facultyContext' => [
                'name' => $user->name,
                'department' => $user->department?->name,
                'courses' => $this->courses->facultyCourses($user)->map(fn ($c) => [
                    'id' => $c['id'],
                    'code' => $c['code'],
                    'name' => $c['name'],
                ]),
            ],
        ]);
    }

    /**
     * Send a chat message — persisted as a session/message pair so the faculty
     * member has a full ChatGPT-style history.
     */
    public function chat(AssistantChatRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $session = null;
        if (! empty($validated['session_id'])) {
            $session = ChatSession::where('user_id', $request->user()->id)->find($validated['session_id']);
        }

        $result = $this->chat->sendMessage(
            user: $request->user(),
            session: $session,
            content: $validated['message'],
            mode: ChatMode::RESEARCH,
        );

        return response()->json([
            'session' => $this->presentSession($result['session']),
            'userMessage' => $this->presentMessage($result['userMessage']),
            'assistantMessage' => $this->presentMessage($result['assistantMessage']),
        ]);
    }

    public function show(ChatSession $session): JsonResponse
    {
        Gate::authorize('view', $session);

        return response()->json([
            'session' => $this->presentSession($session),
            'messages' => $session->messages()->get()->map(fn (ChatMessage $m) => $this->presentMessage($m)),
        ]);
    }

    public function rename(Request $request, ChatSession $session): JsonResponse
    {
        Gate::authorize('view', $session);

        $validated = $request->validate(['title' => ['required', 'string', 'max:120']]);
        $this->chat->rename($session, trim($validated['title']));

        return response()->json(['session' => $this->presentSession($session)]);
    }

    public function togglePin(ChatSession $session): JsonResponse
    {
        Gate::authorize('view', $session);
        $this->chat->togglePin($session);

        return response()->json(['session' => $this->presentSession($session)]);
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

    public function destroySession(ChatSession $session): JsonResponse
    {
        Gate::authorize('delete', $session);
        $this->chat->deleteSession($session);

        return response()->json(['ok' => true]);
    }

    public function archived(): Response
    {
        $user = request()->user();

        return Inertia::render('Faculty/ArchivedChats', [
            'sessions' => $this->chat->archivedSessionsFor($user)->map(fn (ChatSession $s) => $this->presentSession($s)),
        ]);
    }

    public function generateQuiz(GenerateQuizRequest $request): JsonResponse
    {
        return response()->json(['quiz' => $this->assistant->generateQuiz($request->validated())]);
    }

    public function generateAssignment(GenerateAssignmentRequest $request): JsonResponse
    {
        return response()->json(['assignment' => $this->assistant->generateAssignment($request->validated())]);
    }

    /**
     * Persist a generated quiz/assignment as a published assignment on one of
     * the faculty's courses, then notify the enrolled students.
     */
    public function publish(
        PublishContentRequest $request,
        CourseManagementService $management,
        ActivityLogger $activity,
        NotificationService $notifications,
    ): JsonResponse {
        $data = $request->validated();
        $course = Course::findOrFail($data['course_id']);

        Gate::authorize('manage', $course);

        $assignment = $management->publishAssignment($course, $request->user(), $data);

        $activity->log(
            'assignment.published',
            "Published \"{$assignment->title}\" to {$course->code}",
            $assignment,
            ['type' => $assignment->type],
            $request->user(),
        );

        // Notify the students in the assignment's section — the faculty's own
        // section — rather than every section of the course.
        $recipients = $assignment->section
            ? $assignment->section->students()->wherePivot('status', 'enrolled')->get()
            : collect();

        $notifications->notifyMany(
            users: $recipients,
            type: NotificationType::ASSIGNMENT,
            title: "New {$assignment->type} in {$course->code}",
            message: "\"{$assignment->title}\" has been published.",
            link: route('transcript'),
            data: ['course_id' => $course->id, 'section_id' => $assignment->section_id, 'assignment_id' => $assignment->id],
        );

        return response()->json([
            'ok' => true,
            'id' => $assignment->id,
            'message' => 'Published to '.$course->code.'.',
        ]);
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
        ];
    }
}
