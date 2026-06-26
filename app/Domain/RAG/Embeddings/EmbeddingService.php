<?php

namespace App\Domain\RAG\Embeddings;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\RAG\Support\CorpusVersion;
use App\Models\Document;
use App\Models\Embedding;
use Illuminate\Support\Facades\Cache;

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

        // New vectors mean cached retrievals for this corpus are stale.
        if ($written > 0) {
            CorpusVersion::bump();
        }

        return $written;
    }

    /**
     * Embed a query string into a single vector.
     *
     * The (model, text) → vector mapping is deterministic, so the result is
     * cached: repeated/identical queries reuse the vector instead of paying for
     * another embedding API call. This cache never goes stale (a model always
     * embeds the same text the same way), so it is keyed only by model + text.
     *
     * @return array<int, float>
     */
    public function embedQuery(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $compute = fn (): array => $this->provider->embed([$query])[0] ?? [];

        if (! (bool) config('rag.cache.enabled', true)) {
            return $compute();
        }

        $key = 'rag:emb:'.$this->provider->embeddingModel().':'.sha1($query);

        return Cache::remember($key, (int) config('rag.cache.ttl', 3600), $compute);
    }

    public function model(): string
    {
        return $this->provider->embeddingModel();
    }
}
