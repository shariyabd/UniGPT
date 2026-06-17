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

    /**
     * Languages the AI is allowed to answer in, configured by an admin. Each
     * entry is ['code' => 'en', 'name' => 'English']. Defaults to English + Bangla.
     *
     * @return array<int, array{code: string, name: string}>
     */
    public function supportedLanguages(): array
    {
        $stored = $this->overrides()['supported_languages'] ?? null;

        $languages = [];
        if (is_array($stored)) {
            foreach ($stored as $entry) {
                $code = trim((string) ($entry['code'] ?? ''));
                $name = trim((string) ($entry['name'] ?? ''));
                if ($code !== '' && $name !== '') {
                    $languages[$code] = ['code' => $code, 'name' => $name];
                }
            }
        }

        if ($languages === []) {
            $languages = [
                'en' => ['code' => 'en', 'name' => 'English'],
                'bn' => ['code' => 'bn', 'name' => 'Bangla'],
            ];
        }

        return array_values($languages);
    }

    /**
     * Resolve a language code to its configured display name, falling back to
     * the first supported language when the code is unknown.
     */
    public function languageName(string $code): string
    {
        foreach ($this->supportedLanguages() as $language) {
            if ($language['code'] === $code) {
                return $language['name'];
            }
        }

        return $this->supportedLanguages()[0]['name'];
    }

    /**
     * Whether the given language code is one an admin has enabled.
     */
    public function isSupportedLanguage(string $code): bool
    {
        foreach ($this->supportedLanguages() as $language) {
            if ($language['code'] === $code) {
                return true;
            }
        }

        return false;
    }

    public function defaultLanguageCode(): string
    {
        return $this->supportedLanguages()[0]['code'];
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
