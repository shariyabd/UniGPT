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
     * Run a chat completion, emitting partial assistant text as it is
     * generated. Returns the same completed result as chat() once the stream
     * ends, so callers can persist/report identically on either path.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  callable(string): void  $onDelta  Receives each new fragment of assistant text.
     * @param  array<string, mixed>  $options  Optional overrides (temperature, max_tokens, model).
     */
    public function chatStream(array $messages, callable $onDelta, array $options = []): ChatResult;

    /**
     * Transcribe the text contained in an image (e.g. a photo of a handwritten
     * note) using a vision-capable model. Returns the transcribed plain text.
     */
    public function extractText(string $imagePath, string $mimeType): string;

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
