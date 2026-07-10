<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\RAG\Ingestion\PersonalCorpusService;
use App\Domain\User\Models\User;
use App\Models\CourseMaterial;
use App\Models\Note;
use Illuminate\Console\Command;

/**
 * Backfills the personal RAG corpus: indexes every note and file-backed course
 * material that mutation-time jobs have not covered (seeded demo data, content
 * created before this feature shipped). Safe to re-run — unchanged content is
 * skipped via its stored hash.
 */
class SyncPersonalCorpus extends Command
{
    protected $signature = 'rag:sync-personal
                            {--notes : Only sync notes}
                            {--materials : Only sync course materials}
                            {--user= : Only sync notes owned by this user (id or email)}';

    protected $description = 'Index student notes and course-material files into the RAG corpus';

    public function handle(PersonalCorpusService $corpus): int
    {
        $onlyNotes = (bool) $this->option('notes');
        $onlyMaterials = (bool) $this->option('materials');
        $both = ! $onlyNotes && ! $onlyMaterials;

        if ($both || $onlyNotes) {
            $this->syncNotes($corpus);
        }

        if ($both || $onlyMaterials) {
            $this->syncMaterials($corpus);
        }

        return self::SUCCESS;
    }

    private function syncNotes(PersonalCorpusService $corpus): void
    {
        $written = 0;
        $count = 0;

        $query = Note::query();

        if ($needle = (string) $this->option('user')) {
            $user = User::query()
                ->when(
                    is_numeric($needle),
                    fn ($q) => $q->whereKey((int) $needle),
                    fn ($q) => $q->where('email', $needle),
                )
                ->first();

            if ($user === null) {
                $this->error("No user matches [{$needle}].");

                return;
            }

            $query->where('user_id', $user->id);
        }

        $query->each(function (Note $note) use ($corpus, &$written, &$count) {
            $written += $corpus->syncNote($note);
            $count++;
        });

        $this->info("Notes: {$count} scanned, {$written} embeddings written.");
    }

    private function syncMaterials(PersonalCorpusService $corpus): void
    {
        $written = 0;
        $count = 0;

        CourseMaterial::query()
            ->whereNotNull('file_path')
            ->each(function (CourseMaterial $material) use ($corpus, &$written, &$count) {
                $written += $corpus->syncMaterial($material);
                $count++;
            });

        $this->info("Materials: {$count} scanned, {$written} embeddings written.");
    }
}
