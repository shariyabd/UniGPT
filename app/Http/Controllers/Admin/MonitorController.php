<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
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
                'queue' => config('queue.default'),
                'cache' => config('cache.default'),
                'aiProvider' => app(\App\Domain\Chat\Contracts\AIProviderInterface::class)->name(),
            ],
            'app' => [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'environment' => app()->environment(),
                'uptimePercent' => 99.97,
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
