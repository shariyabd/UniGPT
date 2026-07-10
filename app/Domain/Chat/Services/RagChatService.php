<?php

namespace App\Domain\Chat\Services;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\Chat\DataObjects\ChatResult;
use App\Domain\Chat\Support\AiSettings;
use App\Domain\Chat\Tools\ChatToolRegistry;
use App\Domain\RAG\Citations\CitationService;
use App\Domain\RAG\Retrieval\RetrievalService;
use App\Domain\User\Models\User;
use App\Enums\ChatMode;
use App\Enums\ConfidenceLevel;
use App\Enums\QueryIntent;

/**
 * Orchestrates a retrieval-augmented answer: retrieve context → build a
 * grounded prompt → call the provider → attach citations, confidence and
 * follow-up suggestions.
 *
 * For students the provider is also offered agentic tools (deadlines,
 * office-hour booking, quiz/flashcard generation, tasks); requested calls are
 * executed and fed back until the model produces a final text answer.
 */
class RagChatService
{
    /**
     * Cap on provider→tools round-trips per answer; after the last round the
     * tools are withdrawn so the model must produce text.
     */
    private const MAX_TOOL_ROUNDS = 3;

    public function __construct(
        private readonly AIProviderInterface $provider,
        private readonly RetrievalService $retrieval,
        private readonly CitationService $citations,
        private readonly AiSettings $settings,
        private readonly QueryClassifier $classifier,
        private readonly ChatToolRegistry $tools,
    ) {}

    /**
     * Answer a question for a user.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     * @param  (callable(string, array<string, mixed>): void)|null  $onToolEvent  Receives tool_start/tool_result events as tools run.
     * @return array{content: string, confidence: int, confidence_level: string, sources: array, follow_ups: array, model: string, tokens: int, tool_activity: array}
     */
    public function answer(string $question, User $user, ChatMode $mode = ChatMode::ACADEMIC, array $history = [], string $language = 'en', ?callable $onToolEvent = null, bool $withTools = true): array
    {
        return $this->respond($question, $user, $mode, $history, $language, null, $onToolEvent, $withTools);
    }

    /**
     * Answer a question, streaming partial assistant text through $onDelta as
     * it is generated. Returns the same completed payload as answer().
     *
     * @param  array<int, array{role: string, content: string}>  $history
     * @param  callable(string): void  $onDelta
     * @param  (callable(string, array<string, mixed>): void)|null  $onToolEvent  Receives tool_start/tool_result events as tools run.
     * @return array{content: string, confidence: ?int, confidence_level: ?string, sources: array, follow_ups: array, model: string, tokens: int, tool_activity: array}
     */
    public function answerStream(string $question, User $user, callable $onDelta, ChatMode $mode = ChatMode::ACADEMIC, array $history = [], string $language = 'en', ?callable $onToolEvent = null, bool $withTools = true): array
    {
        return $this->respond($question, $user, $mode, $history, $language, $onDelta, $onToolEvent, $withTools);
    }

    /**
     * Shared answer pipeline; with $onDelta the provider call streams, without
     * it the call is a single completion.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{content: string, confidence: ?int, confidence_level: ?string, sources: array, follow_ups: array, model: string, tokens: int, tool_activity: array}
     */
    private function respond(string $question, User $user, ChatMode $mode, array $history, string $language, ?callable $onDelta, ?callable $onToolEvent = null, bool $withTools = true): array
    {
        // Fast path: greetings, thanks and "what can you do" never need retrieval
        // or an LLM call. Answer them deterministically at zero API cost.
        $intent = $this->classifier->classify($question);
        if ($intent !== QueryIntent::ACADEMIC) {
            $content = $this->classifier->reply($question, $user->name) ?? '';
            if ($onDelta !== null && $content !== '') {
                $onDelta($content);
            }

            return $this->cannedAnswer($content, $mode);
        }

        $retrieved = $this->retrieval->retrieve($question, $user);
        $context = $this->citations->buildContext($retrieved);
        $sources = $this->citations->toSources($retrieved);
        $score = $this->citations->overallConfidence($retrieved);

        // Agent mode off = answer-only: tools are never even offered to the model.
        $toolDefinitions = $withTools ? $this->tools->definitionsFor($user) : [];

        $messages = $this->buildMessages($question, $mode, $context, $history, $language, $user, $toolDefinitions !== []);

        $options = $this->settings->chatOptions();
        if ($toolDefinitions !== []) {
            $options['tools'] = $toolDefinitions;
        }

        $result = $this->complete($messages, $options, $onDelta);
        $tokens = $result->totalTokens();
        $toolActivity = [];

        // Agent loop: run requested tools, feed their results back, and repeat
        // until the model answers in text (or the round cap withdraws tools).
        for ($round = 1; $result->toolCalls !== [] && $round <= self::MAX_TOOL_ROUNDS; $round++) {
            $messages[] = $this->assistantToolCallMessage($result);

            foreach ($result->toolCalls as $call) {
                if ($onToolEvent !== null) {
                    $onToolEvent('tool_start', [
                        'name' => $call['name'],
                        'label' => $this->tools->labelFor($call['name']),
                    ]);
                }

                $execution = $this->tools->execute($user, $call['name'], $call['arguments']);
                $toolActivity[] = $execution->toActivity();

                if ($onToolEvent !== null) {
                    $onToolEvent('tool_result', $execution->toActivity());
                }

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'],
                    'content' => $execution->toModelPayload(),
                ];
            }

            if ($round === self::MAX_TOOL_ROUNDS) {
                unset($options['tools']);
            }

            $result = $this->complete($messages, $options, $onDelta);
            $tokens += $result->totalTokens();
        }

        return [
            'content' => $result->content,
            'confidence' => (int) round($score * 100),
            'confidence_level' => ConfidenceLevel::fromScore($score)->value,
            'sources' => $sources,
            'follow_ups' => $this->followUps($mode),
            'model' => $result->model,
            'tokens' => $tokens,
            'tool_activity' => $toolActivity,
        ];
    }

    /**
     * One provider call, streamed when $onDelta is given.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<string, mixed>  $options
     */
    private function complete(array $messages, array $options, ?callable $onDelta): ChatResult
    {
        return $onDelta !== null
            ? $this->provider->chatStream($messages, $onDelta, $options)
            : $this->provider->chat($messages, $options);
    }

    /**
     * Echo the model's tool-call turn back into the transcript in the
     * provider wire format, so the follow-up completion sees its own request.
     *
     * @return array<string, mixed>
     */
    private function assistantToolCallMessage(ChatResult $result): array
    {
        return [
            'role' => 'assistant',
            'content' => $result->content !== '' ? $result->content : null,
            'tool_calls' => array_map(fn (array $call) => [
                'id' => $call['id'],
                'type' => 'function',
                'function' => [
                    'name' => $call['name'],
                    'arguments' => (string) json_encode($call['arguments']),
                ],
            ], $result->toolCalls),
        ];
    }

    /**
     * Shape a deterministic (non-RAG) reply to match the RAG answer contract so
     * the rest of the pipeline persists and renders it identically.
     *
     * @return array{content: string, confidence: ?int, confidence_level: ?string, sources: array, follow_ups: array, model: string, tokens: int, tool_activity: array}
     */
    private function cannedAnswer(string $content, ChatMode $mode): array
    {
        return [
            'content' => $content,
            'confidence' => null,
            'confidence_level' => null,
            'sources' => [],
            'follow_ups' => $this->followUps($mode),
            'model' => 'rule-based',
            'tokens' => 0,
            'tool_activity' => [],
        ];
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array<int, array{role: string, content: string}>
     */
    private function buildMessages(string $question, ChatMode $mode, string $context, array $history, string $language, User $user, bool $withTools = false): array
    {
        $system = $mode->systemPrompt()
            ."\n\nYou are UniGPT, a university academic copilot for students and faculty.";

        if ($profile = $this->userContext($user)) {
            $system .= "\n\n".$profile;
        }

        $languageName = $this->settings->languageName($language);
        $system .= " Always write your entire response in {$languageName}, regardless of the "
            .'language the question is written in. Keep proper nouns, code, and standard technical '
            .'terms in their original form.';

        if ($context !== '') {
            // Documents matched — ground the answer in them, but allow sensible
            // supplementation from general knowledge.
            $system .= ' Answer using the provided context below and cite sources by their bracket '
                .'numbers (e.g. [1]). If the context only partially covers the question, you may '
                .'supplement with your general academic knowledge, but rely on the context first '
                .'and cite it wherever you use it.';
        } else {
            // No documents matched — answer common/general questions directly from
            // the model's own knowledge, while staying within the assistant's scope.
            $system .= ' No reference documents matched this question, so answer it directly using '
                .'your own general academic knowledge as a knowledgeable, helpful university '
                .'assistant. Do not invent citations or references. Keep answers relevant to '
                .'academics, courses, study skills, research and university life; if a question '
                .'falls clearly outside what a university academic assistant should help with, '
                .'politely steer the user back on topic.';
        }

        if ($withTools) {
            $system .= "\n\nYou have tools that read the student's real academic data (deadlines, courses, "
                .'office-hour slots) and take actions for them (book or cancel office-hour bookings, '
                .'generate practice quizzes or flashcard decks, add tasks to their planner). Use a tool '
                .'whenever the question concerns their actual schedule or they ask you to do one of these '
                .'things — never fabricate schedule data. Before booking or cancelling a slot, make sure '
                .'the student has clearly chosen that specific slot; list the open slots first if they '
                .'have not. Never invent slot ids or course ids — take them from tool results. After '
                .'acting, briefly confirm what you did. If a tool reports an error, explain it plainly '
                .'and do not pretend the action succeeded.';
        }

        if ($override = $this->settings->systemPromptOverride()) {
            $system .= "\n\n".$override;
        }

        if ($context !== '') {
            $system .= "\n\nContext: ".$context;
        }

        $messages = [['role' => 'system', 'content' => $system]];

        foreach ($history as $turn) {
            $role = ($turn['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
            $content = trim((string) ($turn['content'] ?? ''));
            if ($content !== '') {
                $messages[] = ['role' => $role, 'content' => $content];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $question];

        return $messages;
    }

    /**
     * Describe who is asking — role, department, semester and their courses — so
     * the model stays context-aware even when no documents match, and can resolve
     * course aliases (e.g. "DSA" → the user's "Data Structures & Algorithms").
     */
    private function userContext(User $user): string
    {
        $role = match (true) {
            $user->isFaculty() => 'faculty member',
            $user->isStudent() => 'student',
            default => 'university user',
        };

        $parts = ["The person you are assisting is {$user->name}, a {$role}"];
        if ($department = $user->department?->name) {
            $parts[] = "in the {$department} department";
        }
        if ($user->isStudent() && $user->semester) {
            $parts[] = "currently in semester {$user->semester}";
        }
        $profile = implode(' ', $parts).'.';

        $courses = $this->userCourses($user);
        if ($courses !== []) {
            $label = $user->isFaculty() ? 'They currently teach' : 'They are enrolled in';
            $profile .= " {$label}: ".implode('; ', $courses).'. '
                .'When the user refers to a subject by an abbreviation, acronym or short name, '
                .'map it to the matching course above (e.g. its code or a common acronym). '
                .'Even if no reference document matches, you can still help using this context '
                .'and your general academic knowledge.';
        }

        return $profile;
    }

    /**
     * The user's relevant courses as "CODE — Name" strings (enrolled for
     * students, taught for faculty), excluding pending enrolments.
     *
     * @return array<int, string>
     */
    private function userCourses(User $user): array
    {
        $query = $user->isFaculty()
            ? $user->teachingCourses()
            : $user->enrolledCourses()->wherePivotNotIn('status', ['pending']);

        return $query->get(['courses.code', 'courses.name'])
            ->map(fn ($course): string => trim(($course->code ? $course->code.' — ' : '').$course->name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function followUps(ChatMode $mode): array
    {
        return match ($mode) {
            ChatMode::EXAM_PREP => [
                'Quiz me on this topic',
                'Summarize the key points for revision',
                'What are common exam mistakes here?',
            ],
            ChatMode::ASSIGNMENT_HELP => [
                'Outline an approach for this assignment',
                'What sources should I reference?',
                'Check my understanding of the requirements',
            ],
            ChatMode::RESEARCH => [
                'Suggest related research directions',
                'Find supporting material on this',
                'Explain the methodology in detail',
            ],
            default => [
                'Can you explain that more simply?',
                'Give me an example',
                'What should I learn next?',
            ],
        };
    }
}
