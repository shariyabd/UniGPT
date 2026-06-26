<?php

declare(strict_types=1);

namespace App\Domain\RAG\Support;

use Illuminate\Support\Facades\Cache;

/**
 * A monotonically increasing version number for the document corpus. It is
 * embedded in every retrieval cache key, so bumping it (on any document
 * create/update/delete or re-embedding) transparently invalidates all cached
 * retrievals without having to enumerate or forget individual keys.
 */
class CorpusVersion
{
    private const KEY = 'rag:corpus:version';

    public static function current(): int
    {
        return (int) Cache::rememberForever(self::KEY, fn (): int => 1);
    }

    public static function bump(): void
    {
        // increment() is atomic but only works once the key exists; seed it first.
        if (Cache::add(self::KEY, 2)) {
            return;
        }

        Cache::increment(self::KEY);
    }
}
