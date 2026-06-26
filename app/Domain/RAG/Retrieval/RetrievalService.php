<?php

namespace App\Domain\RAG\Retrieval;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\Chat\Support\AiSettings;
use App\Domain\RAG\Embeddings\EmbeddingService;
use App\Domain\RAG\Support\CorpusVersion;
use App\Domain\User\Models\User;
use App\Models\DocumentChunk;
use App\Models\Embedding;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * MySQL-native vector retrieval: loads candidate embeddings (scoped to approved
 * documents the user may see), then ranks by cosine similarity in PHP.
 *
 * @phpstan-type RetrievedChunk array{chunk: \App\Models\DocumentChunk, document: \App\Models\Document, score: float}
 */
class RetrievalService
{
    public function __construct(
        private readonly AIProviderInterface $provider,
        private readonly EmbeddingService $embeddings,
        private readonly AiSettings $settings,
    ) {}

    /**
     * Retrieve the most relevant chunks for a query, visible to the given user.
     *
     * @return Collection<int, array{chunk: \App\Models\DocumentChunk, document: \App\Models\Document, score: float}>
     */
    public function retrieve(string $query, User $user, ?int $topK = null): Collection
    {
        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        $topK = $topK ?? $this->settings->topK();

        // The expensive part — embedding the query and cosine-ranking every
        // candidate vector — is cached as a compact id/score list, keyed by the
        // corpus version and the user's visibility scope. A document change bumps
        // the corpus version and transparently invalidates these entries.
        $rows = $this->cacheEnabled()
            ? Cache::remember(
                $this->cacheKey($query, $user, $topK),
                (int) config('rag.cache.ttl', 3600),
                fn (): array => $this->rankCandidates($query, $user, $topK),
            )
            : $this->rankCandidates($query, $user, $topK);

        return $this->hydrate($rows);
    }

    /**
     * Embed the query and cosine-rank visible candidate chunks, returning a
     * cache-safe array of [chunk_id, document_id, score] for the top matches.
     *
     * @return array<int, array{chunk_id: int, document_id: int, score: float}>
     */
    private function rankCandidates(string $query, User $user, int $topK): array
    {
        $queryVector = $this->embeddings->embedQuery($query);
        if (empty($queryVector)) {
            return [];
        }

        $candidates = Embedding::query()
            ->where('model', $this->provider->embeddingModel())
            ->whereHas('document', function ($q) use ($user) {
                $q->approved()->visibleTo($user);
            })
            ->with(['chunk', 'document'])
            ->get();

        if ($candidates->isEmpty()) {
            return [];
        }

        $scored = $candidates
            ->map(fn (Embedding $embedding): array => [
                'chunk_id' => $embedding->document_chunk_id,
                'document_id' => $embedding->document_id,
                'score' => $this->cosine($queryVector, $embedding->vector ?? []),
                'has_chunk' => $embedding->chunk !== null && $embedding->document !== null,
            ])
            ->filter(fn (array $row): bool => $row['has_chunk'])
            ->sortByDesc('score')
            ->values();

        $threshold = $this->threshold();
        $passing = $scored->filter(fn (array $row): bool => $row['score'] >= $threshold)->values();

        // Always surface the single best match if nothing clears the threshold
        // but a positive-similarity candidate exists.
        if ($passing->isEmpty()) {
            $best = $scored->first();
            if ($best && $best['score'] > 0) {
                $passing = collect([$best]);
            }
        }

        return $passing->take($topK)->map(fn (array $row): array => [
            'chunk_id' => (int) $row['chunk_id'],
            'document_id' => (int) $row['document_id'],
            'score' => (float) $row['score'],
        ])->all();
    }

    /**
     * Rehydrate cached id/score rows into the chunk+document shape the rest of
     * the pipeline expects. Missing chunks/documents (e.g. just deleted) are
     * dropped — though a delete also bumps the corpus version, so a hit here
     * already implies the ids are still valid.
     *
     * @param  array<int, array{chunk_id: int, document_id: int, score: float}>  $rows
     * @return Collection<int, array{chunk: \App\Models\DocumentChunk, document: \App\Models\Document, score: float}>
     */
    private function hydrate(array $rows): Collection
    {
        if ($rows === []) {
            return collect();
        }

        $chunks = DocumentChunk::with('document')
            ->whereIn('id', array_column($rows, 'chunk_id'))
            ->get()
            ->keyBy('id');

        return collect($rows)
            ->map(function (array $row) use ($chunks) {
                $chunk = $chunks->get($row['chunk_id']);
                if ($chunk === null || $chunk->document === null) {
                    return null;
                }

                return [
                    'chunk' => $chunk,
                    'document' => $chunk->document,
                    'score' => $row['score'],
                ];
            })
            ->filter()
            ->values();
    }

    private function cacheEnabled(): bool
    {
        return (bool) config('rag.cache.enabled', true);
    }

    /**
     * Cache key for a retrieval. Scoped by corpus version (invalidation), the
     * embedding model, and the user's visibility audience so that two users with
     * the same access share cached results while access boundaries are respected.
     */
    private function cacheKey(string $query, User $user, int $topK): string
    {
        return implode(':', [
            'rag:ret:v'.CorpusVersion::current(),
            $this->provider->embeddingModel(),
            $this->visibilityScope($user),
            'k'.$topK,
            't'.$this->threshold(),
            sha1($query),
        ]);
    }

    /**
     * A stable token describing which documents the user may see, mirroring
     * {@see \App\Models\Document::scopeVisibleTo()}.
     */
    private function visibilityScope(User $user): string
    {
        if ($user->isAdmin()) {
            return 'all';
        }

        $audiences = [];
        if ($user->isStudent()) {
            $audiences[] = 'students';
        }
        if ($user->isFaculty()) {
            $audiences[] = 'students';
            $audiences[] = 'faculty';
        }

        $audiences = array_values(array_unique($audiences));
        sort($audiences);

        return $audiences === [] ? 'none' : implode('+', $audiences);
    }

    /**
     * Provider-aware similarity threshold. Mock lexical similarities are lower
     * than the OpenAI-tuned default, so we relax the floor in mock mode.
     */
    private function threshold(): float
    {
        $configured = $this->settings->similarityThreshold();

        return $this->provider->name() === 'mock'
            ? min($configured, 0.08)
            : $configured;
    }

    /**
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    private function cosine(array $a, array $b): float
    {
        $len = min(count($a), count($b));
        if ($len === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        for ($i = 0; $i < $len; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        if ($normA <= 0 || $normB <= 0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
