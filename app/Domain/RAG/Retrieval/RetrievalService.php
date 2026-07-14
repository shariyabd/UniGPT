<?php

namespace App\Domain\RAG\Retrieval;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\Chat\Support\AiSettings;
use App\Domain\RAG\Embeddings\EmbeddingService;
use App\Domain\RAG\Support\CorpusVersion;
use App\Domain\User\Models\User;
use App\Models\CourseMaterial;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\Embedding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * MySQL-native vector retrieval: loads candidate embeddings (scoped to what the
 * user may retrieve from), then ranks by cosine similarity in PHP.
 *
 * A user's corpus is the union of three sources:
 *  - approved library documents whose audience includes them,
 *  - their own notes (personal shadow documents),
 *  - materials of the sections they are enrolled in (students) or teach
 *    (faculty).
 *
 * @phpstan-type RetrievedChunk array{chunk: \App\Models\DocumentChunk, document: \App\Models\Document, score: float}
 */
class RetrievalService
{
    /**
     * How many candidate embeddings to score per batch. Bounds peak memory to
     * roughly this many vectors at once, independent of total corpus size.
     */
    private const SCORING_BATCH_SIZE = 300;

    public function __construct(
        private readonly AIProviderInterface $provider,
        private readonly EmbeddingService $embeddings,
        private readonly AiSettings $settings,
    ) {}

    /**
     * Retrieve the most relevant chunks for a query, visible to the given user.
     *
     * @return Collection<int, array{chunk: DocumentChunk, document: Document, score: float}>
     */
    public function retrieve(string $query, User $user, ?int $topK = null): Collection
    {
        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        $topK = $topK ?? $this->settings->topK();

        // Resolved outside the cache closure so the cache key reflects the
        // user's *current* section access — an enrolment change reshapes the
        // key instead of serving a stale scope for the TTL.
        $materialIds = $this->accessibleMaterialIds($user);

        // The expensive part — embedding the query and cosine-ranking every
        // candidate vector — is cached as a compact id/score list, keyed by the
        // corpus version and the user's retrieval scope. A document change bumps
        // the corpus version and transparently invalidates these entries.
        $rows = $this->cacheEnabled()
            ? Cache::remember(
                $this->cacheKey($query, $user, $topK, $materialIds),
                (int) config('rag.cache.ttl', 3600),
                fn (): array => $this->rankCandidates($query, $user, $topK, $materialIds),
            )
            : $this->rankCandidates($query, $user, $topK, $materialIds);

        return $this->hydrate($rows);
    }

    /**
     * Embed the query and cosine-rank visible candidate chunks, returning a
     * cache-safe array of [chunk_id, document_id, score] for the top matches.
     *
     * @param  array<int, int>  $materialIds
     * @return array<int, array{chunk_id: int, document_id: int, score: float}>
     */
    private function rankCandidates(string $query, User $user, int $topK, array $materialIds): array
    {
        $queryVector = $this->embeddings->embedQuery($query);
        if (empty($queryVector)) {
            return [];
        }

        $threshold = $this->threshold();

        // Stream candidate embeddings in batches and keep only a running top-K
        // (plus the single best, for the fallback). A full corpus can hold tens of
        // thousands of ~1536-float vectors — loading them all at once exhausts
        // memory. We rank on the vector alone and let hydrate() resolve/validate
        // the chunk+document, so no heavy relations are loaded here.
        $passing = collect();
        $best = null;

        Embedding::query()
            ->where('model', $this->provider->embeddingModel())
            ->whereHas('document', function (Builder $q) use ($user, $materialIds) {
                $this->scopeRetrievable($q, $user, $materialIds);
            })
            ->select(['id', 'document_chunk_id', 'document_id', 'vector'])
            ->chunkById(self::SCORING_BATCH_SIZE, function (Collection $batch) use ($queryVector, $threshold, $topK, &$passing, &$best): void {
                foreach ($batch as $embedding) {
                    $row = [
                        'chunk_id' => (int) $embedding->document_chunk_id,
                        'document_id' => (int) $embedding->document_id,
                        'score' => $this->cosine($queryVector, $embedding->vector ?? []),
                    ];

                    if ($best === null || $row['score'] > $best['score']) {
                        $best = $row;
                    }

                    if ($row['score'] >= $threshold) {
                        $passing->push($row);
                    }
                }

                // Trim to the global top-K after each batch so memory never grows
                // with the corpus size.
                $passing = $passing->sortByDesc('score')->take($topK)->values();
            });

        // Always surface the single best match if nothing clears the threshold
        // but a positive-similarity candidate exists.
        if ($passing->isEmpty()) {
            if ($best !== null && $best['score'] > 0) {
                $passing = collect([$best]);
            }

            return $passing->all();
        }

        return $passing
            ->sortByDesc('score')
            ->take($topK)
            ->map(fn (array $row): array => [
                'chunk_id' => (int) $row['chunk_id'],
                'document_id' => (int) $row['document_id'],
                'score' => (float) $row['score'],
            ])
            ->values()
            ->all();
    }

    /**
     * Rehydrate cached id/score rows into the chunk+document shape the rest of
     * the pipeline expects. Missing chunks/documents (e.g. just deleted) are
     * dropped — though a delete also bumps the corpus version, so a hit here
     * already implies the ids are still valid.
     *
     * @param  array<int, array{chunk_id: int, document_id: int, score: float}>  $rows
     * @return Collection<int, array{chunk: DocumentChunk, document: Document, score: float}>
     */
    private function hydrate(array $rows): Collection
    {
        if ($rows === []) {
            return collect();
        }

        // Personal shadow documents are hidden by the library global scope, so
        // eager-load the document relation without it.
        $chunks = DocumentChunk::with(['document' => function ($q) {
            $q->withoutGlobalScope(Document::LIBRARY_SCOPE);
        }])
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
     * Restrict a document query to what this user may retrieve from: approved
     * library documents in their audience, their own notes, and materials of
     * their sections. Applied inside a whereHas, where the library global scope
     * is active by default — remove it, then re-add source rules explicitly.
     *
     * @param  array<int, int>  $materialIds
     */
    private function scopeRetrievable(Builder $query, User $user, array $materialIds): void
    {
        $query->withoutGlobalScope(Document::LIBRARY_SCOPE)
            ->where(function (Builder $sources) use ($user, $materialIds) {
                $sources->where(function (Builder $library) use ($user) {
                    $library->where('documents.source_type', Document::SOURCE_LIBRARY)
                        ->approved()
                        ->visibleTo($user);
                });

                $sources->orWhere(function (Builder $notes) use ($user) {
                    $notes->where('documents.source_type', Document::SOURCE_NOTE)
                        ->where('documents.owner_id', $user->id);
                });

                if ($materialIds !== []) {
                    $sources->orWhere(function (Builder $materials) use ($materialIds) {
                        $materials->where('documents.source_type', Document::SOURCE_MATERIAL)
                            ->whereIn('documents.source_id', $materialIds);
                    });
                }
            });
    }

    /**
     * IDs of the course materials in this user's retrieval scope: published
     * materials of enrolled sections for students, all own-section materials
     * for faculty. Only file-backed materials ever get shadow documents, so
     * filter to those here to keep the id list (and cache key) tight.
     *
     * @return array<int, int>
     */
    private function accessibleMaterialIds(User $user): array
    {
        $sectionIds = match (true) {
            $user->isStudent() => $user->enrolledSectionIds(),
            $user->isFaculty() => $user->teachingSectionIds(),
            default => collect(),
        };

        if ($sectionIds->isEmpty()) {
            return [];
        }

        return CourseMaterial::query()
            ->whereIn('section_id', $sectionIds)
            ->when($user->isStudent(), fn ($q) => $q->where('is_published', true))
            ->whereNotNull('file_path')
            ->pluck('id')
            ->all();
    }

    /**
     * Cache key for a retrieval. Scoped by corpus version (invalidation), the
     * embedding model, and the user's retrieval scope. Personal corpora (own
     * notes, section materials) make the scope inherently per-user, so the key
     * carries the user id plus a fingerprint of their accessible material set.
     */
    private function cacheKey(string $query, User $user, int $topK, array $materialIds): string
    {
        return implode(':', [
            'rag:ret:v'.CorpusVersion::current(),
            $this->provider->embeddingModel(),
            'u'.$user->id,
            'm'.sha1(implode(',', $materialIds)),
            'k'.$topK,
            't'.$this->threshold(),
            sha1($query),
        ]);
    }

    /**
     * Embedding-aware similarity threshold. Mock lexical similarities are lower
     * than the OpenAI-tuned default, so we relax the floor whenever the active
     * embedding model is the mock hash embedder — regardless of the chat provider.
     */
    private function threshold(): float
    {
        $configured = $this->settings->similarityThreshold();

        return str_starts_with($this->provider->embeddingModel(), 'mock')
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
