<?php

declare(strict_types=1);

namespace App\Domain\Chat\Support;

use App\Models\Setting;

/**
 * Resolves the effective AI/RAG runtime parameters: admin overrides persisted
 * via the AI Settings screen (the `ai` row of the settings table) layered on
 * top of the config defaults. This is what makes the admin AI Settings screen
 * actually control the running system.
 */
class AiSettings
{
    /** @var array<string, mixed>|null */
    private ?array $overrides = null;

    /**
     * Chat completion overrides passed to the provider.
     *
     * @return array<string, mixed>
     */
    public function chatOptions(): array
    {
        return [
            'temperature' => (float) $this->value('temperature', config('ai.providers.openai.temperature', 0.3)),
            'max_tokens' => (int) $this->value('max_tokens', config('ai.providers.openai.max_tokens', 4096)),
        ];
    }

    public function topK(): int
    {
        return max(1, (int) $this->value('rag_top_k', config('rag.retrieval.top_k', 5)));
    }

    public function similarityThreshold(): float
    {
        return (float) $this->value('rag_similarity_threshold', config('rag.retrieval.similarity_threshold', 0.7));
    }

    /**
     * Extra system-prompt instructions configured by an admin, or null when unset.
     */
    public function systemPromptOverride(): ?string
    {
        $value = trim((string) $this->value('system_prompt', ''));

        return $value === '' ? null : $value;
    }

    private function value(string $key, mixed $default): mixed
    {
        $value = $this->overrides()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function overrides(): array
    {
        if ($this->overrides === null) {
            $saved = Setting::get('ai', []);
            $this->overrides = is_array($saved) ? $saved : [];
        }

        return $this->overrides;
    }
}
