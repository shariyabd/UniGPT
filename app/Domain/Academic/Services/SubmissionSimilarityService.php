<?php

declare(strict_types=1);

namespace App\Domain\Academic\Services;

use App\Domain\Chat\Document\Chunking\ChunkingService;
use App\Domain\RAG\Embeddings\EmbeddingService;
use App\Models\AssignmentSubmission;
use App\Models\SubmissionEmbedding;
use App\Models\SubmissionSimilarity;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Similarity screening of assignment submissions: each submission's text
 * (written content + extracted file text) is chunked and embedded via the
 * existing RAG infrastructure, then compared against classmates' stored
 * vectors for the same assignment. High-similarity pairs are flagged for
 * faculty on the grading screen — a signal for human review, not a verdict.
 *
 * Assignments are section-scoped, so comparisons never leave the section's
 * own roster.
 */
class SubmissionSimilarityService
{
    public function __construct(
        private readonly SubmissionTextService $text,
        private readonly ChunkingService $chunking,
        private readonly EmbeddingService $embeddings,
    ) {}

    /**
     * (Re)screen one submission: rebuild its stored chunk embeddings, then
     * recompute its flags against every other submission of the assignment.
     * Safe to re-run — resubmissions replace all prior artifacts.
     */
    public function screen(AssignmentSubmission $submission): void
    {
        if (! config('rag.submission_screening.enabled')) {
            return;
        }

        // A resubmission changes the text, so both its embeddings and every
        // flag involving it (either direction) are stale.
        SubmissionEmbedding::where('assignment_submission_id', $submission->id)->delete();
        SubmissionSimilarity::where('submission_id', $submission->id)
            ->orWhere('matched_submission_id', $submission->id)
            ->delete();

        $model = $this->embeddings->model();
        $chunks = $this->embedChunks($submission);

        if ($chunks === []) {
            return;
        }

        foreach ($chunks as $chunk) {
            SubmissionEmbedding::create([
                'assignment_id' => $submission->assignment_id,
                'assignment_submission_id' => $submission->id,
                'chunk_index' => $chunk['index'],
                'excerpt' => $chunk['excerpt'],
                'vector' => $chunk['vector'],
                'model' => $model,
            ]);
        }

        $this->flagMatches($submission, $chunks, $model);
    }

    /**
     * Flags for a set of submissions, keyed by submission id — used by the
     * grading overview to badge suspicious submissions.
     *
     * @param  array<int, int>  $submissionIds
     * @return Collection<int, Collection<int, SubmissionSimilarity>>
     */
    public function flagsFor(array $submissionIds): Collection
    {
        if ($submissionIds === []) {
            return collect();
        }

        return SubmissionSimilarity::query()
            ->whereIn('submission_id', $submissionIds)
            ->with('matched.student:id,name')
            ->orderByDesc('score')
            ->get()
            ->groupBy('submission_id');
    }

    /**
     * Extract, chunk and embed the submission's text.
     *
     * @return array<int, array{index: int, excerpt: string, vector: array<int, float>}>
     */
    private function embedChunks(AssignmentSubmission $submission): array
    {
        $maxChunks = max(1, (int) config('rag.submission_screening.max_chunks', 40));

        $embedded = [];
        foreach (array_slice($this->chunkSubmissionText($submission), 0, $maxChunks) as $index => $chunk) {
            $vector = $this->embeddings->embedQuery($chunk['content']);
            if ($vector === []) {
                continue;
            }

            $embedded[] = [
                'index' => $index,
                'excerpt' => Str::limit(trim($chunk['content']), 220),
                'vector' => $vector,
            ];
        }

        return $embedded;
    }

    /**
     * The submission's comparable text (written answer + extracted file text,
     * via SubmissionTextService) as RAG chunks.
     *
     * @return array<int, array{content: string, page: ?int, section: ?string, token_count: int}>
     */
    private function chunkSubmissionText(AssignmentSubmission $submission): array
    {
        $text = $this->text->textFor($submission);
        $chunks = $text !== '' ? $this->chunking->chunkText($text) : [];

        // Trailing fragments carry too little signal to flag on.
        return array_values(array_filter($chunks, fn (array $chunk) => mb_strlen(trim($chunk['content'])) >= 40));
    }

    /**
     * Compare this submission's chunks against classmates' stored vectors and
     * persist flagged pairs (both directions) above the threshold.
     *
     * @param  array<int, array{index: int, excerpt: string, vector: array<int, float>}>  $chunks
     */
    private function flagMatches(AssignmentSubmission $submission, array $chunks, string $model): void
    {
        $threshold = (float) config('rag.submission_screening.flag_threshold', 0.82);
        $topPairs = max(1, (int) config('rag.submission_screening.top_pairs', 3));

        $peers = SubmissionEmbedding::query()
            ->where('assignment_id', $submission->assignment_id)
            ->where('assignment_submission_id', '!=', $submission->id)
            ->where('model', $model)
            ->get()
            ->groupBy('assignment_submission_id');

        foreach ($peers as $peerSubmissionId => $peerChunks) {
            $comparison = $this->compare($chunks, $peerChunks, $threshold);

            if ($comparison['score'] < $threshold) {
                continue;
            }

            SubmissionSimilarity::create([
                'assignment_id' => $submission->assignment_id,
                'submission_id' => $submission->id,
                'matched_submission_id' => $peerSubmissionId,
                'score' => $comparison['score'],
                'coverage' => $comparison['coverage'],
                'matched_chunks' => array_slice($comparison['pairs'], 0, $topPairs),
                'model' => $model,
            ]);

            SubmissionSimilarity::create([
                'assignment_id' => $submission->assignment_id,
                'submission_id' => $peerSubmissionId,
                'matched_submission_id' => $submission->id,
                'score' => $comparison['score'],
                'coverage' => $comparison['peerCoverage'],
                'matched_chunks' => array_slice(array_map(fn (array $pair) => [
                    'excerpt' => $pair['matchedExcerpt'],
                    'matchedExcerpt' => $pair['excerpt'],
                    'score' => $pair['score'],
                ], $comparison['pairs']), 0, $topPairs),
                'model' => $model,
            ]);
        }
    }

    /**
     * Chunk-level comparison of one submission against one peer.
     *
     * score    = best chunk-pair cosine;
     * coverage = fraction of own chunks whose best peer match ≥ threshold;
     * pairs    = flagged excerpt pairs, strongest first.
     *
     * @param  array<int, array{index: int, excerpt: string, vector: array<int, float>}>  $chunks
     * @param  Collection<int, SubmissionEmbedding>  $peerChunks
     * @return array{score: float, coverage: float, peerCoverage: float, pairs: array<int, array{excerpt: string, matchedExcerpt: string, score: float}>}
     */
    private function compare(array $chunks, Collection $peerChunks, float $threshold): array
    {
        $best = 0.0;
        $pairs = [];
        $matchedOwn = [];
        $matchedPeer = [];

        foreach ($chunks as $chunk) {
            foreach ($peerChunks as $peerChunk) {
                $score = $this->cosine($chunk['vector'], $peerChunk->vector ?? []);
                $best = max($best, $score);

                if ($score >= $threshold) {
                    $matchedOwn[$chunk['index']] = true;
                    $matchedPeer[$peerChunk->id] = true;
                    $pairs[] = [
                        'excerpt' => $chunk['excerpt'],
                        'matchedExcerpt' => $peerChunk->excerpt,
                        'score' => round($score, 4),
                    ];
                }
            }
        }

        usort($pairs, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return [
            'score' => round($best, 4),
            'coverage' => count($chunks) > 0 ? round(count($matchedOwn) / count($chunks), 4) : 0.0,
            'peerCoverage' => $peerChunks->count() > 0 ? round(count($matchedPeer) / $peerChunks->count(), 4) : 0.0,
            'pairs' => $pairs,
        ];
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
