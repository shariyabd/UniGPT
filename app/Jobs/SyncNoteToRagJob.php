<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\RAG\Ingestion\PersonalCorpusService;
use App\Models\Note;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Keeps a note's RAG shadow document in sync off the request cycle. Dispatched
 * after any note mutation; a missing note means it was deleted, so its shadow
 * is dropped.
 */
class SyncNoteToRagJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $noteId) {}

    public function handle(PersonalCorpusService $corpus): void
    {
        $note = Note::find($this->noteId);

        $note !== null
            ? $corpus->syncNote($note)
            : $corpus->forgetNote($this->noteId);
    }
}
