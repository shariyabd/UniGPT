<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public, interactive showcase of the UniNexus feature index.
 *
 * The feature list is parsed straight from docs/feature-list.md so this page
 * stays a faithful mirror of that document — the single source of truth. Each
 * "## Heading" becomes a group and each numbered list item a feature.
 */
class FeatureListController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('FeatureList', [
            'groups' => $this->groups(),
        ]);
    }

    /**
     * @return array<int, array{name: string, features: list<string>}>
     */
    private function groups(): array
    {
        $file = base_path('docs/feature-list.md');

        if (! is_file($file)) {
            return [];
        }

        $key = 'feature-list:'.filemtime($file);

        return Cache::remember($key, now()->addHour(), fn () => $this->parse((string) file_get_contents($file)));
    }

    /**
     * Turn the markdown into ordered groups. A "## Heading" opens a group;
     * subsequent "1. Feature" list items fill it. The "# Title" and blockquote
     * intro lines are ignored.
     *
     * @return array<int, array{name: string, features: list<string>}>
     */
    private function parse(string $markdown): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $markdown) ?: [];
        $groups = [];
        $current = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (preg_match('/^##\s+(.+)$/', $trimmed, $m)) {
                if ($current !== null && $current['features'] !== []) {
                    $groups[] = $current;
                }
                $current = ['name' => trim($m[1]), 'features' => []];

                continue;
            }

            if ($current !== null && preg_match('/^\d+\.\s+(.+)$/', $trimmed, $m)) {
                $current['features'][] = trim($m[1]);
            }
        }

        if ($current !== null && $current['features'] !== []) {
            $groups[] = $current;
        }

        return $groups;
    }
}
