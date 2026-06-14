<?php

namespace App\Domain\Chat\Document\Services;

use App\Domain\Chat\Document\Chunking\ChunkingService;
use App\Domain\RAG\Embeddings\EmbeddingService;
use App\Enums\DocumentStatus;
use App\Infrastructure\FileStorage\DocumentTextExtractor;
use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Ingests an approved document into the RAG index: extract text → chunk →
 * embed → store. Idempotent (re-running replaces existing chunks/embeddings).
 */
class DocumentProcessingService
{
    public function __construct(
        private readonly DocumentTextExtractor $extractor,
        private readonly ChunkingService $chunker,
        private readonly EmbeddingService $embeddings,
    ) {}

    /**
     * Process a document end-to-end. Returns the number of embeddings written.
     */
    public function process(Document $document): int
    {
        try {
            $document->update(['status' => DocumentStatus::PROCESSING]);

            $pages = $this->extractor->extract($document->file_path, (string) $document->file_type);

            if (empty($pages)) {
                $document->update(['status' => DocumentStatus::FAILED]);
                Log::warning("Document {$document->id} produced no extractable text.");

                return 0;
            }

            $document->chunks()->delete();

            $chunks = $this->chunker->chunkPages($pages);
            foreach ($chunks as $chunk) {
                $document->chunks()->create([
                    'chunk_index' => $chunk['chunk_index'] ?? 0,
                    'content' => $chunk['content'],
                    'page' => $chunk['page'] ?? null,
                    'section' => $chunk['section'] ?? null,
                    'token_count' => $chunk['token_count'] ?? 0,
                ]);
            }

            $written = $this->embeddings->embedDocument($document->fresh());

            $document->update([
                'status' => DocumentStatus::APPROVED,
                'pages' => count($pages),
                'processed_at' => now(),
            ]);

            return $written;
        } catch (Throwable $e) {
            $document->update(['status' => DocumentStatus::FAILED]);
            Log::error("Document {$document->id} processing failed: {$e->getMessage()}");

            return 0;
        }
    }
}
