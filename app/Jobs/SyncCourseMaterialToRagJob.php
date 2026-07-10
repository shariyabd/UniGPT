<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\RAG\Ingestion\PersonalCorpusService;
use App\Models\CourseMaterial;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Keeps a course material's RAG shadow document in sync off the request cycle.
 * Dispatched after any material mutation; a missing material means it was
 * deleted, so its shadow is dropped.
 */
class SyncCourseMaterialToRagJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public int $materialId) {}

    public function handle(PersonalCorpusService $corpus): void
    {
        $material = CourseMaterial::find($this->materialId);

        $material !== null
            ? $corpus->syncMaterial($material)
            : $corpus->forgetMaterial($this->materialId);
    }
}
