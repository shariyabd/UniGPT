<?php

namespace App\Http\Controllers\Faculty;

use App\Domain\Academic\Services\ClassTestService;
use App\Domain\Academic\Services\CourseManagementService;
use App\Domain\Academic\Services\CourseService;
use App\Domain\Chat\Services\ChatService;
use App\Domain\Chat\Services\TeachingAssistantService;
use App\Domain\Notification\Services\NotificationService;
use App\Enums\ChatMode;
use App\Enums\NotificationType;
use App\Http\Concerns\StreamsServerSentEvents;
use App\Http\Controllers\Controller;
use App\Http\Requests\Faculty\AssistantChatRequest;
use App\Http\Requests\Faculty\GenerateAssignmentRequest;
use App\Http\Requests\Faculty\GenerateQuizRequest;
use App\Http\Requests\Faculty\PublishClassTestRequest;
use App\Http\Requests\Faculty\PublishContentRequest;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Course;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AIAssistantController extends Controller
{
    use StreamsServerSentEvents;

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

    /**
     * Streaming variant of chat(): emits `delta` events with assistant text as
     * it is generated, then a final `done` event carrying the same payload
     * chat() returns.
     */
    public function stream(AssistantChatRequest $request): StreamedResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $session = null;
        if (! empty($validated['session_id'])) {
            $session = ChatSession::where('user_id', $user->id)->find($validated['session_id']);
        }

        return $this->sseResponse(function () use ($user, $session, $validated) {
            try {
                $result = $this->chat->sendMessage(
                    user: $user,
                    session: $session,
                    content: $validated['message'],
                    mode: ChatMode::RESEARCH,
                    onDelta: fn (string $text) => $this->sseSend('delta', ['text' => $text]),
                );

                $this->sseSend('done', [
                    'session' => $this->presentSession($result['session']),
                    'userMessage' => $this->presentMessage($result['userMessage']),
                    'assistantMessage' => $this->presentMessage($result['assistantMessage']),
                ]);
            } catch (Throwable $e) {
                Log::error('Assistant stream failed: '.$e->getMessage());
                $this->sseSend('error', ['message' => 'Something went wrong while generating a response. Please try again.']);
            }
        });
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
     * Publish a generated quiz as an interactive, auto-graded class test instead of
     * a text assignment. Reuses the Class Test engine end-to-end, so students take
     * it in-browser and answers never leave the server during an attempt.
     */
    public function publishClassTest(
        PublishClassTestRequest $request,
        ClassTestService $classTests,
        ActivityLogger $activity,
    ): JsonResponse {
        $data = $request->validated();
        $course = Course::findOrFail($data['course_id']);

        Gate::authorize('manage', $course);

        // The test is bound to the section this faculty member teaches in the course.
        $section = $course->sectionFor($request->user());

        if ($section === null) {
            return response()->json([
                'message' => 'You do not teach a section in this course, so a class test cannot be created.',
            ], 422);
        }

        // ClassTestService::create() persists the questions, computes total marks and
        // notifies enrolled students when the status is "published".
        $test = $classTests->create([
            'section_id' => $section->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'duration_minutes' => $data['duration_minutes'],
            'status' => 'published',
            'shuffle_questions' => true,
            'questions' => $data['questions'],
        ], $request->user());

        $activity->log(
            'class_test.created',
            "Published class test \"{$test->title}\" to {$course->code}",
            $test,
            ['source' => 'ai-assistant'],
            $request->user(),
        );

        return response()->json([
            'ok' => true,
            'id' => $test->id,
            'message' => 'Published as a class test in '.$course->code.'.',
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
