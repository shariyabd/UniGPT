<?php

namespace App\Http\Controllers\Faculty;

use App\Domain\Academic\Services\CourseService;
use App\Domain\Chat\Services\RagChatService;
use App\Domain\Chat\Services\TeachingAssistantService;
use App\Enums\ChatMode;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AIAssistantController extends Controller
{
    public function __construct(
        private readonly RagChatService $rag,
        private readonly TeachingAssistantService $assistant,
        private readonly CourseService $courses,
    ) {}

    public function index(): Response
    {
        $user = request()->user();

        return Inertia::render('Faculty/AIAssistant', [
            'facultyContext' => [
                'name' => $user->name,
                'department' => $user->department?->name,
                'courses' => $this->courses->facultyCourses($user)->map(fn ($c) => ['id' => $c['id'], 'code' => $c['code'], 'name' => $c['name']]),
            ],
        ]);
    }

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $answer = $this->rag->answer($validated['message'], $request->user(), ChatMode::RESEARCH);

        return response()->json(['reply' => $answer]);
    }

    public function generateQuiz(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'course' => ['nullable', 'string'],
            'difficulty' => ['nullable', 'in:easy,medium,hard'],
            'questionCount' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        return response()->json(['quiz' => $this->assistant->generateQuiz($validated)]);
    }

    public function generateAssignment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'topics' => ['nullable', 'array'],
            'topics.*' => ['string', 'max:100'],
            'points' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        return response()->json(['assignment' => $this->assistant->generateAssignment($validated)]);
    }
}
