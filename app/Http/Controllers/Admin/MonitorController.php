<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MonitorController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/SystemMonitor', [
            'metrics' => $this->metrics(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function metrics(): array
    {
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
        $diskFree = @disk_free_space(base_path()) ?: 0;
        $diskTotal = @disk_total_space(base_path()) ?: 1;
        $diskUsedPct = round((($diskTotal - $diskFree) / $diskTotal) * 100, 1);

        return [
            'cpu' => [
                'load1' => round($load[0], 2),
                'load5' => round($load[1], 2),
                'load15' => round($load[2], 2),
                'cores' => (int) (shell_exec('nproc') ?: 1),
            ],
            'memory' => [
                'usage' => $this->memoryUsageMb(),
                'peak' => round(memory_get_peak_usage(true) / 1048576, 1),
            ],
            'storage' => [
                'usedPercent' => $diskUsedPct,
                'free' => $this->humanBytes($diskFree),
                'total' => $this->humanBytes($diskTotal),
            ],
            'services' => [
                'database' => $this->databaseUp() ? 'operational' : 'down',
                'queue' => [
                    'driver' => config('queue.default'),
                    'status' => $this->queueUp() ? 'operational' : 'down',
                ],
                'cache' => [
                    'driver' => config('cache.default'),
                    'status' => $this->cacheUp() ? 'operational' : 'down',
                ],
                'aiProvider' => $this->aiProviderStatus(),
            ],
            'app' => [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'environment' => app()->environment(),
                'uptime' => $this->systemUptime(),
            ],
        ];
    }

    private function memoryUsageMb(): float
    {
        return round(memory_get_usage(true) / 1048576, 1);
    }

    private function databaseUp(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Probe the queue backend. The `sync` driver runs jobs inline so it is
     * always up; other drivers (database/redis) are exercised via size(), which
     * surfaces a connectivity failure as `down` instead of a fake "operational".
     */
    private function queueUp(): bool
    {
        try {
            if (config('queue.default') === 'sync') {
                return true;
            }

            Queue::connection()->size();

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Probe the cache store with a short-lived round-trip write/read.
     */
    private function cacheUp(): bool
    {
        try {
            Cache::put('__monitor_probe__', '1', 5);

            return Cache::get('__monitor_probe__') === '1';
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * AI provider name plus whether it is actually usable (API key present /
     * reachable) — reported without making a billable live request.
     *
     * @return array{name: string, status: string}
     */
    private function aiProviderStatus(): array
    {
        $provider = app(AIProviderInterface::class);

        try {
            $available = $provider->isAvailable();
        } catch (Throwable $e) {
            $available = false;
        }

        return [
            'name' => $provider->name(),
            'status' => $available ? 'operational' : 'unconfigured',
        ];
    }

    /**
     * Real system uptime as a human string (e.g. "5d 3h 12m"). Linux exposes it
     * via /proc/uptime; non-Linux hosts return "n/a" rather than a fabricated
     * percentage.
     */
    private function systemUptime(): string
    {
        $seconds = $this->uptimeSeconds();

        if ($seconds <= 0) {
            return 'n/a';
        }

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = "{$days}d";
        }
        if ($hours > 0) {
            $parts[] = "{$hours}h";
        }
        $parts[] = "{$minutes}m";

        return implode(' ', $parts);
    }

    private function uptimeSeconds(): int
    {
        if (! is_readable('/proc/uptime')) {
            return 0;
        }

        $contents = @file_get_contents('/proc/uptime');
        if ($contents === false) {
            return 0;
        }

        return (int) (float) explode(' ', trim($contents))[0];
    }

    private function humanBytes(float $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), 1).' '.$units[$power];
    }
}
