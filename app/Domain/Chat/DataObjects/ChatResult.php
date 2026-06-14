<?php

namespace App\Domain\Chat\DataObjects;

/**
 * Immutable result of a chat completion call, provider-agnostic.
 */
class ChatResult
{
    public function __construct(
        public readonly string $content,
        public readonly string $model,
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
        public readonly array $raw = [],
    ) {}

    public function totalTokens(): int
    {
        return $this->promptTokens + $this->completionTokens;
    }
}
