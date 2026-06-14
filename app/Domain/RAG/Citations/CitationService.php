<?php

namespace App\Domain\RAG\Citations;

use App\Enums\ConfidenceLevel;
use Illuminate\Support\Collection;

/**
 * Turns retrieved chunks into citation/source objects shaped for the Vue UI
 * and builds the grounded context block injected into the LLM prompt.
 */
class CitationService
{
    /**
     * @param  Collection<int, array{chunk: \App\Models\DocumentChunk, document: \App\Models\Document, score: float}>  $retrieved
     * @return array<int, array<string, mixed>>
     */
    public function toSources(Collection $retrieved): array
    {
        return $retrieved->map(function (array $row, int $i) {
            $document = $row['document'];
            $chunk = $row['chunk'];
            $score = (float) $row['score'];

            return [
                'id' => $chunk->id,
                'number' => $i + 1,
                'document_id' => $document->id,
                'title' => $document->title,
                'type' => $document->category,
                'page' => $chunk->page,
                'section' => $chunk->section,
                'relevantText' => $this->excerpt($chunk->content),
                'confidence' => (int) round($score * 100),
                'confidence_level' => ConfidenceLevel::fromScore($score)->value,
            ];
        })->all();
    }

    /**
     * Build the "Context:" block consumed by providers (and read back by the
     * mock provider) from the retrieved chunks.
     *
     * @param  Collection<int, array{chunk: \App\Models\DocumentChunk, document: \App\Models\Document, score: float}>  $retrieved
     */
    public function buildContext(Collection $retrieved): string
    {
        if ($retrieved->isEmpty()) {
            return '';
        }

        $maxLength = (int) config('rag.context.max_context_length', 3000);

        $parts = $retrieved->map(function (array $row, int $i) {
            $document = $row['document'];
            $chunk = $row['chunk'];
            $ref = '['.($i + 1).'] '.$document->title
                .($chunk->page ? " (p.{$chunk->page})" : '');

            return $ref.': '.trim($chunk->content);
        })->all();

        return mb_substr(implode("\n\n", $parts), 0, $maxLength);
    }

    /**
     * Overall answer confidence from the best retrieval score.
     *
     * @param  Collection<int, array{chunk: \App\Models\DocumentChunk, document: \App\Models\Document, score: float}>  $retrieved
     */
    public function overallConfidence(Collection $retrieved): float
    {
        if ($retrieved->isEmpty()) {
            return 0.0;
        }

        return (float) $retrieved->max('score');
    }

    private function excerpt(string $content, int $length = 240): string
    {
        $content = trim(preg_replace('/\s+/', ' ', $content) ?? $content);

        return mb_strlen($content) > $length
            ? mb_substr($content, 0, $length).'…'
            : $content;
    }
}
