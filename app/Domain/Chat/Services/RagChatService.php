<?php

namespace App\Domain\Chat\Services;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\Chat\Support\AiSettings;
use App\Domain\RAG\Citations\CitationService;
use App\Domain\RAG\Retrieval\RetrievalService;
use App\Domain\User\Models\User;
use App\Enums\ChatMode;
use App\Enums\ConfidenceLevel;

/**
 * Orchestrates a retrieval-augmented answer: retrieve context → build a
 * grounded prompt → call the provider → attach citations, confidence and
 * follow-up suggestions.
 */
class RagChatService
{
    public function __construct(
        private readonly AIProviderInterface $provider,
        private readonly RetrievalService $retrieval,
        private readonly CitationService $citations,
        private readonly AiSettings $settings,
    ) {}

    /**
     * Answer a question for a user.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{content: string, confidence: int, confidence_level: string, sources: array, follow_ups: array, model: string, tokens: int}
     */
    public function answer(string $question, User $user, ChatMode $mode = ChatMode::ACADEMIC, array $history = []): array
    {
        $retrieved = $this->retrieval->retrieve($question, $user);
        $context = $this->citations->buildContext($retrieved);
        $sources = $this->citations->toSources($retrieved);
        $score = $this->citations->overallConfidence($retrieved);

        $messages = $this->buildMessages($question, $mode, $context, $history);
        $result = $this->provider->chat($messages, $this->settings->chatOptions());

        return [
            'content' => $result->content,
            'confidence' => (int) round($score * 100),
            'confidence_level' => ConfidenceLevel::fromScore($score)->value,
            'sources' => $sources,
            'follow_ups' => $this->followUps($mode),
            'model' => $result->model,
            'tokens' => $result->totalTokens(),
        ];
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array<int, array{role: string, content: string}>
     */
    private function buildMessages(string $question, ChatMode $mode, string $context, array $history): array
    {
        $system = $mode->systemPrompt()
            ."\n\nYou are UniGPT, a university academic copilot. Answer using the provided context "
            .'and cite sources by their bracket numbers (e.g. [1]). If the context does not contain '
            .'the answer, say so honestly and suggest where the student might look.';

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
