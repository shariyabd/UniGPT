<?php

declare(strict_types=1);

namespace App\Domain\RAG\Embeddings;

use App\Models\Document;

/**
 * Re-embeds the entire RAG corpus with the current embedding backend(s). Shared
 * by the rag:reembed command and the ReembedCorpusJob (auto-dispatched when the
 * admin changes embedding settings), so both use identical logic.
 */
class CorpusReembedder
{
    public function __construct(private readonly EmbeddingService $embeddings) {}

    /**
     * Re-embed every document that has chunks — library docs AND personal shadow
     * docs (notes, materials). Idempotent: embedDocument replaces vectors per
     * model. Returns totals; $onDocument (optional) reports per-document progress.
     *
     * @param  callable(Document, int): void|null  $onDocument
     * @return array{documents: int, vectors: int}
     */
    public function reembedAll(?callable $onDocument = null, int $chunkSize = 100): array
    {
        $documents = 0;
        $vectors = 0;

        Document::allSources()
            ->whereHas('chunks')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($batch) use (&$documents, &$vectors, $onDocument): void {
                foreach ($batch as $document) {
                    $written = $this->embeddings->embedDocument($document);
                    $documents++;
                    $vectors += $written;

                    if ($onDocument !== null) {
                        $onDocument($document, $written);
                    }
                }
            });

        return ['documents' => $documents, 'vectors' => $vectors];
    }
}
