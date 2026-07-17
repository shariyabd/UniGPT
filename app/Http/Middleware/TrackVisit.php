<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Tracking\VisitTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs meaningful page views (landing page + every authenticated dashboard/page)
 * into the `visits` table for the Admin → User Activity report.
 *
 * The recording runs in terminate(), AFTER the response is sent to the browser, so
 * the geolocation lookup and DB write add zero latency to the page load.
 */
class TrackVisit
{
    public function __construct(private readonly VisitTracker $tracker) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $this->shouldTrack($request, $response)) {
            return;
        }

        $this->tracker->record($request);
    }

    /**
     * Only real page navigations: successful GET requests that render a full HTML
     * page or an Inertia visit. Excludes asset/api/ajax noise, partial reloads,
     * redirects, and downloads.
     */
    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $request->route() === null) {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        // Inertia partial reloads re-fetch a subset of props for a page already
        // recorded on its initial visit — not a new navigation.
        if ($request->headers->has('X-Inertia-Partial-Data')) {
            return false;
        }

        // An Inertia visit is a genuine SPA page navigation.
        if ($request->headers->get('X-Inertia') === 'true') {
            return true;
        }

        // Otherwise only count full HTML document loads (not XHR/JSON/asset hits).
        if ($request->ajax() || $request->wantsJson()) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type');

        return str_contains($contentType, 'text/html');
    }
}
