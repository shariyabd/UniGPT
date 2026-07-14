<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\RAG\Embeddings\CorpusReembedder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Re-embed the whole RAG corpus with the current embedding backend(s). Dispatched
 * automatically when an admin changes the embedding provider/fallback settings so
 * the corpus is re-indexed without anyone running rag:reembed by hand.
 */
class ReembedCorpusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Re-embedding is heavy; give it room and don't retry storms on a dead API.
    public int $timeout = 1800;

    public int $tries = 1;

    public function handle(CorpusReembedder $reembedder): void
    {
        $stats = $reembedder->reembedAll();

        Log::info("ReembedCorpusJob: re-embedded {$stats['documents']} documents into {$stats['vectors']} vectors.");
    }
}
