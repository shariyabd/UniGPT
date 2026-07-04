<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PublicDocsController extends Controller
{
    /**
     * Public, unauthenticated file browser for the shared documents under
     * public/document-library. Renders a listing for directories and streams
     * individual files (inline, or forced download with ?download=1).
     *
     * The physical folder is deliberately NOT named "docs" so it never shadows
     * the /docs URL: under `php artisan serve` a real public/docs directory
     * would be handed to the built-in server (which cannot list it) instead of
     * reaching this controller. With no physical match, every /docs/* request
     * routes here.
     */
    public function browse(Request $request, ?string $path = null): Response
    {
        $base = realpath(public_path('document-library'));

        if ($base === false) {
            abort(404, 'Document library is not available.');
        }

        $relative = trim((string) $path, '/');

        // Resolve the requested target and guard against path traversal — the
        // real path must stay inside the docs base directory.
        $target = $relative === '' ? $base : realpath($base.DIRECTORY_SEPARATOR.$relative);

        if ($target === false || ! Str::startsWith($target, $base)) {
            abort(404);
        }

        if (is_file($target)) {
            $download = $request->boolean('download');

            return response()->file($target, [
                'Content-Disposition' => ($download ? 'attachment' : 'inline')
                    .'; filename="'.basename($target).'"',
            ]);
        }

        return response()->view('docs.index', [
            'relative' => $relative,
            'breadcrumbs' => $this->breadcrumbs($relative),
            'directories' => $this->directories($target, $relative),
            'files' => $this->files($target, $relative),
        ]);
    }

    /**
     * @return array<int, array{name: string, url: string}>
     */
    private function breadcrumbs(string $relative): array
    {
        $crumbs = [['name' => 'docs', 'url' => url('docs')]];
        $accumulated = '';

        foreach (array_filter(explode('/', $relative)) as $segment) {
            $accumulated = ltrim($accumulated.'/'.$segment, '/');
            $crumbs[] = ['name' => $segment, 'url' => url('docs/'.$accumulated)];
        }

        return $crumbs;
    }

    /**
     * @return array<int, array{name: string, url: string, count: int}>
     */
    private function directories(string $target, string $relative): array
    {
        $dirs = [];

        foreach (glob($target.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR) ?: [] as $dir) {
            $name = basename($dir);
            $dirs[] = [
                'name' => $name,
                'url' => url('docs/'.ltrim($relative.'/'.$name, '/')),
                'count' => count(glob($dir.DIRECTORY_SEPARATOR.'*') ?: []),
            ];
        }

        return $dirs;
    }

    /**
     * @return array<int, array{name: string, url: string, downloadUrl: string, size: string}>
     */
    private function files(string $target, string $relative): array
    {
        $files = [];

        foreach (glob($target.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            if (! is_file($file)) {
                continue;
            }

            $name = basename($file);
            $urlPath = 'docs/'.ltrim($relative.'/'.$name, '/');

            $files[] = [
                'name' => $name,
                'url' => url($urlPath),
                'downloadUrl' => url($urlPath).'?download=1',
                'size' => $this->humanSize((int) filesize($file)),
            ];
        }

        return $files;
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), $power ? 1 : 0).' '.$units[$power];
    }
}
