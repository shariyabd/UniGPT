<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * High-level intent of a chat message, decided cheaply before any retrieval or
 * LLM call so that greetings and meta questions skip the expensive RAG path.
 */
enum QueryIntent: string
{
    /** Greetings, thanks, farewells, bare acknowledgements — no knowledge needed. */
    case SMALLTALK = 'smalltalk';

    /** "Who are you / what can you do" — answerable from a fixed capability blurb. */
    case META = 'meta';

    /** A genuine academic/knowledge question — run the full RAG pipeline. */
    case ACADEMIC = 'academic';

    public function isAcademic(): bool
    {
        return $this === self::ACADEMIC;
    }
}
