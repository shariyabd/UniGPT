<?php

namespace App\Domain\RAG\Embeddings;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\RAG\Support\CorpusVersion;
use App\Infrastructure\AI\AIProviderManager;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\Embedding;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Generates and stores vector embeddings for document chunks using the active
 * AI provider, and embeds ad-hoc query strings for retrieval.
 */
class EmbeddingService
{
    public function __construct(
        private readonly AIProviderInterface $provider,
        private readonly AIProviderManager $manager,
    ) {}

    /**
     * Embed all chunks of a document with every configured embedding backend
     * (DUAL-EMBED when an embedding fallback is enabled), replacing existing
     * embeddings per model. Storing both models' vectors is what lets query-time
     * retrieval fail over to the secondary provider without breaking. Returns the
     * total number of embeddings written across backends.
     */
    public function embedDocument(Document $document): int
    {
        $chunks = $document->chunks()->orderBy('chunk_index')->get();

        if ($chunks->isEmpty()) {
            return 0;
        }

        $written = 0;
        foreach ($this->embeddingBackends() as $backend) {
            $written += $this->embedChunksWith($document, $chunks, $backend);
        }

        // New vectors mean cached retrievals for this corpus are stale.
        if ($written > 0) {
            CorpusVersion::bump();
        }

        return $written;
    }

    /**
     * Backends to embed a document with. With an embedding fallback enabled,
     * dual-embed using the manager's concrete backend list; otherwise use the
     * injected provider, which honours container bindings (e.g. a test forcing
     * the mock provider) and keeps the single-provider path unchanged.
     *
     * @return array<int, AIProviderInterface>
     */
    private function embeddingBackends(): array
    {
        return config('ai.embedding.fallback.enabled')
            ? $this->manager->embeddingBackends()
            : [$this->provider];
    }

    /**
     * Embed a document's chunks with a single backend, tagging vectors with that
     * backend's model. A backend that errors (e.g. OpenAI quota exhausted) is
     * skipped so the other backends' vectors are still written.
     *
     * @param  Collection<int, DocumentChunk>  $chunks
     */
    private function embedChunksWith(Document $document, Collection $chunks, AIProviderInterface $backend): int
    {
        $model = $backend->embeddingModel();

        // Clear stale embeddings for THIS model so re-processing is idempotent
        // and never touches the other backend's vectors.
        Embedding::where('document_id', $document->id)
            ->where('model', $model)
            ->delete();

        $written = 0;

        foreach ($chunks->chunk(50) as $batch) {
            try {
                $vectors = $backend->embed($batch->pluck('content')->all());
            } catch (Throwable $e) {
                Log::warning("Embedding backend [{$backend->name()}] failed for document {$document->id}; skipping this model.", [
                    'reason' => $e->getMessage(),
                ]);

                return $written;
            }

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

        // Skip the vector cache when an embedding fallback is active: the model
        // that actually serves an embed() is only known after the call, so a
        // cached vector could be mis-tagged across a failover. One embed per
        // query is a negligible cost next to the safety.
        if (! (bool) config('rag.cache.enabled', true) || config('ai.embedding.fallback.enabled')) {
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
