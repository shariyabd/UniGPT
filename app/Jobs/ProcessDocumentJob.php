<?php

namespace App\Jobs;

use App\Domain\Chat\Document\Services\DocumentProcessingService;
use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Ingests an approved document into the RAG index off the request cycle.
 */
class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public int $documentId) {}

    public function handle(DocumentProcessingService $processor): void
    {
        $document = Document::find($this->documentId);

        if ($document) {
            $processor->process($document);
        }
    }
}
