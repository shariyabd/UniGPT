<?php

namespace App\Domain\Chat\Contracts;

use App\Domain\Chat\DataObjects\ChatResult;

/**
 * Provider-agnostic contract for LLM chat completion + text embeddings.
 *
 * Implementations: OpenAiProvider (real, HTTP) and MockProvider (keyless, deterministic).
 */
interface AIProviderInterface
{
    /**
     * Run a chat completion.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options  Optional overrides (temperature, max_tokens, model).
     */
    public function chat(array $messages, array $options = []): ChatResult;

    /**
     * Embed one or more texts into vectors.
     *
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>> One vector per input text.
     */
    public function embed(array $texts): array;

    /**
     * Dimensionality of vectors produced by embed().
     */
    public function embeddingDimensions(): int;

    /**
     * Embedding model identifier (stored alongside vectors so retrieval only
     * compares vectors produced by the same model).
     */
    public function embeddingModel(): string;

    /**
     * Short provider key, e.g. "openai" or "mock".
     */
    public function name(): string;

    /**
     * Whether this provider can actually reach its backend (key present, etc.).
     */
    public function isAvailable(): bool;
}
