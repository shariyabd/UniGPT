<?php

namespace App\Domain\Chat\DataObjects;

/**
 * Immutable result of a chat completion call, provider-agnostic.
 */
class ChatResult
{
    /**
     * @param  array<int, array{id: string, name: string, arguments: array<string, mixed>}>  $toolCalls
     *                                                                                                   Function calls the model requested instead of (or alongside) text;
     *                                                                                                   empty when the model answered directly.
     */
    public function __construct(
        public readonly string $content,
        public readonly string $model,
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
        public readonly array $raw = [],
        public readonly array $toolCalls = [],
    ) {}

    public function totalTokens(): int
    {
        return $this->promptTokens + $this->completionTokens;
    }
}
