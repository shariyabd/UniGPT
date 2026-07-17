<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Visit;
use Illuminate\Console\Command;

/**
 * Delete tracked page visits older than the retention window. The activity feed
 * is high-volume (one row per navigation) and only useful for recent analysis, so
 * old rows are pruned to keep the table lean.
 */
class PruneVisits extends Command
{
    protected $signature = 'visits:prune
        {--days= : Override the retention window (config tracking.retention_days)}
        {--dry-run : Report what would be deleted without touching anything}';

    protected $description = 'Delete tracked page visits older than the retention window';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('tracking.retention_days'));

        if ($days <= 0) {
            $this->info('Retention is disabled (0 days) — nothing pruned.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);
        $query = Visit::query()->where('created_at', '<', $cutoff);

        if ($this->option('dry-run')) {
            $this->info("Would delete {$query->count()} visit(s) older than {$days} day(s).");

            return self::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("Deleted {$deleted} visit(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
