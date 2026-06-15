<?php

namespace App\Http\Controllers\Faculty;

use App\Domain\Academic\Services\CourseService;
use App\Domain\Chat\Services\RagChatService;
use App\Domain\Chat\Services\TeachingAssistantService;
use App\Enums\ChatMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Faculty\AssistantChatRequest;
use App\Http\Requests\Faculty\GenerateAssignmentRequest;
use App\Http\Requests\Faculty\GenerateQuizRequest;
use Illuminate\Http\JsonResponse;
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

    public function chat(AssistantChatRequest $request): JsonResponse
    {
        $answer = $this->rag->answer($request->validated()['message'], $request->user(), ChatMode::RESEARCH);

        return response()->json(['reply' => $answer]);
    }

    public function generateQuiz(GenerateQuizRequest $request): JsonResponse
    {
        return response()->json(['quiz' => $this->assistant->generateQuiz($request->validated())]);
    }

    public function generateAssignment(GenerateAssignmentRequest $request): JsonResponse
    {
        return response()->json(['assignment' => $this->assistant->generateAssignment($request->validated())]);
    }
}
