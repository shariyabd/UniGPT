<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\RAG\Embeddings\CorpusReembedder;
use App\Domain\RAG\Embeddings\EmbeddingService;
use Illuminate\Console\Command;

/**
 * Re-embed the entire RAG corpus with the CURRENT embedding provider/model.
 *
 * Run this after switching embedding providers (e.g. OpenAI → Jina): stored
 * vectors are tagged by model and retrieval only scores same-model vectors, so
 * documents must be re-embedded with the new model before RAG returns results.
 * Covers library documents AND personal shadow docs (notes, course materials).
 */
class ReembedCorpus extends Command
{
    protected $signature = 'rag:reembed {--chunk=100 : How many documents to load per batch}';

    protected $description = 'Re-embed all RAG documents with the current embedding provider/model';

    public function handle(EmbeddingService $embeddings, CorpusReembedder $reembedder): int
    {
        $model = $embeddings->model();
        $this->info("Re-embedding the corpus (current embedding model: {$model})");

        $stats = $reembedder->reembedAll(
            onDocument: fn ($document, int $written) => $this->line("  #{$document->id} {$document->title} → {$written} vectors"),
            chunkSize: (int) $this->option('chunk'),
        );

        $this->newLine();
        $this->info("Done. Re-embedded {$stats['documents']} documents into {$stats['vectors']} vectors.");

        if ($stats['vectors'] === 0) {
            $this->warn('No vectors were written — check that the embedding provider has a valid API key (php artisan tinker → app(\App\Domain\Chat\Contracts\AIProviderInterface::class)->embed([\'ping\'])).');
        }

        return self::SUCCESS;
    }
}
