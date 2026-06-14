<?php

namespace App\Domain\RAG\Embeddings;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Models\Document;
use App\Models\Embedding;

/**
 * Generates and stores vector embeddings for document chunks using the active
 * AI provider, and embeds ad-hoc query strings for retrieval.
 */
class EmbeddingService
{
    public function __construct(private readonly AIProviderInterface $provider) {}

    /**
     * Embed all chunks of a document (replaces any existing embeddings for the
     * current model). Returns the number of embeddings written.
     */
    public function embedDocument(Document $document): int
    {
        $model = $this->provider->embeddingModel();
        $chunks = $document->chunks()->orderBy('chunk_index')->get();

        if ($chunks->isEmpty()) {
            return 0;
        }

        // Clear stale embeddings for this model so re-processing is idempotent.
        Embedding::where('document_id', $document->id)
            ->where('model', $model)
            ->delete();

        $written = 0;

        foreach ($chunks->chunk(50) as $batch) {
            $vectors = $this->provider->embed($batch->pluck('content')->all());

            foreach ($batch->values() as $i => $chunk) {
                $vector = $vectors[$i] ?? null;
                if ($vector === null) {
                    continue;
                }

                Embedding::create([
                    'document_chunk_id' => $chunk->id,
                    'document_id' => $document->id,
                    'model' => $model,
                    'dimensions' => count($vector),
                    'vector' => $vector,
                ]);
                $written++;
            }
        }

        return $written;
    }

    /**
     * Embed a query string into a single vector.
     *
     * @return array<int, float>
     */
    public function embedQuery(string $query): array
    {
        return $this->provider->embed([$query])[0] ?? [];
    }

    public function model(): string
    {
        return $this->provider->embeddingModel();
    }
}
