<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ClassTestRecording;
use App\Models\ClassTestSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Delete recording chunks and snapshot frames belonging to finalised attempts
 * older than the retention window. Media evidence is the storage-heavy part of
 * proctoring — grades and the event timeline are kept forever; the footage is
 * only needed while a result can still be disputed.
 */
class PruneExamEvidence extends Command
{
    protected $signature = 'exam:prune-evidence
        {--days= : Override the retention window (config exam_security.evidence_retention_days)}
        {--dry-run : Report what would be deleted without touching anything}';

    protected $description = 'Delete exam recordings and snapshots of finalised attempts older than the retention window';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('exam_security.evidence_retention_days'));

        if ($days <= 0) {
            $this->info('Retention is disabled (0 days) — nothing pruned.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);
        $dryRun = (bool) $this->option('dry-run');

        $finalised = fn ($query) => $query->whereIn('status', ['submitted', 'disqualified', 'expired'])
            ->where('created_at', '<', $cutoff);

        $recordings = ClassTestRecording::query()
            ->whereHas('attempt', $finalised)
            ->where('created_at', '<', $cutoff);
        $snapshots = ClassTestSnapshot::query()
            ->whereHas('attempt', $finalised)
            ->where('created_at', '<', $cutoff);

        $recordingCount = 0;
        $snapshotCount = 0;
        $bytes = 0;

        foreach ([$recordings, $snapshots] as $query) {
            foreach ($query->cursor() as $row) {
                $bytes += (int) $row->size_bytes;
                $row instanceof ClassTestRecording ? $recordingCount++ : $snapshotCount++;

                if (! $dryRun) {
                    Storage::disk($row->disk)->delete($row->path);
                    $row->delete();
                }
            }
        }

        $mb = round($bytes / 1_048_576, 1);
        $verb = $dryRun ? 'Would delete' : 'Deleted';
        $this->info("{$verb} {$recordingCount} recording chunk(s) and {$snapshotCount} snapshot(s) older than {$days} day(s) — {$mb} MB.");

        return self::SUCCESS;
    }
}
