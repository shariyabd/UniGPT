<?php

declare(strict_types=1);

namespace App\Observers;

use App\Domain\RAG\Support\CorpusVersion;
use App\Models\Document;

/**
 * Invalidates cached retrievals whenever the document corpus changes. Any
 * create, update (e.g. approval, edit), delete or restore bumps the corpus
 * version so stale answers can never be served after an admin changes a
 * document — satisfying the "cache must not return outdated info" requirement.
 */
class DocumentObserver
{
    public function saved(Document $document): void
    {
        CorpusVersion::bump();
    }

    public function deleted(Document $document): void
    {
        CorpusVersion::bump();
    }

    public function restored(Document $document): void
    {
        CorpusVersion::bump();
    }
}
