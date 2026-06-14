<?php

namespace App\Domain\RAG\Retrieval;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\Chat\Support\AiSettings;
use App\Domain\RAG\Embeddings\EmbeddingService;
use App\Domain\User\Models\User;
use App\Models\Embedding;
use Illuminate\Support\Collection;

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
        $queryVector = $this->embeddings->embedQuery($query);
        if (empty($queryVector)) {
            return collect();
        }

        $candidates = Embedding::query()
            ->where('model', $this->provider->embeddingModel())
            ->whereHas('document', function ($q) use ($user) {
                $q->approved()->visibleTo($user);
            })
            ->with(['chunk', 'document'])
            ->get();

        if ($candidates->isEmpty()) {
            return collect();
        }

        $scored = $candidates
            ->map(function (Embedding $embedding) use ($queryVector) {
                return [
                    'chunk' => $embedding->chunk,
                    'document' => $embedding->document,
                    'score' => $this->cosine($queryVector, $embedding->vector ?? []),
                ];
            })
            ->filter(fn ($row) => $row['chunk'] !== null && $row['document'] !== null)
            ->sortByDesc('score')
            ->values();

        $threshold = $this->threshold();
        $passing = $scored->filter(fn ($row) => $row['score'] >= $threshold)->values();

        // Always surface the single best match if nothing clears the threshold
        // but a positive-similarity candidate exists.
        if ($passing->isEmpty()) {
            $best = $scored->first();
            if ($best && $best['score'] > 0) {
                $passing = collect([$best]);
            }
        }

        return $passing->take($topK)->values();
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
