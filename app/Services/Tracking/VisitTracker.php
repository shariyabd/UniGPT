<?php

declare(strict_types=1);

namespace App\Services\Tracking;

use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Records a single page view from an HTTP request. Called from TrackVisit::terminate,
 * so it runs AFTER the response is flushed — geolocation and the DB write never add
 * latency to the user's page load.
 */
class VisitTracker
{
    /**
     * Route names we never log — polling/ajax/utility endpoints that would flood the
     * table with noise rather than represent a real navigation.
     */
    private const IGNORED_ROUTES = [
        'notifications.poll',
        'notifications.index',
        'heartbeat',
        'search.global',
        'messenger.overview',
        'messenger.messages.index',
        'live.toggle',
    ];

    /**
     * Friendly labels for the pages an admin most cares about. Anything else falls
     * back to a humanised route name.
     */
    private const LABELS = [
        'home' => 'Home (Landing)',
        'login' => 'Login',
        'dashboard' => 'Student Dashboard',
        'faculty.dashboard' => 'Faculty Dashboard',
        'admin.dashboard' => 'Admin Dashboard',
        'presentation' => 'Presentation',
    ];

    public function __construct(
        private readonly DeviceDetector $devices,
        private readonly GeoLocationService $geo,
    ) {}

    public function record(Request $request): void
    {
        try {
            $routeName = $request->route()?->getName();

            if ($routeName !== null && in_array($routeName, self::IGNORED_ROUTES, true)) {
                return;
            }

            $device = $this->devices->parse($request->userAgent());
            $location = $this->geo->locate($request->ip());

            Visit::create([
                'user_id' => $request->user()?->id,
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'path' => Str::limit($request->path(), 1000, ''),
                'route_name' => $routeName,
                'label' => $this->label($routeName),
                'referrer' => $this->referrer($request),
                'ip_address' => $request->ip(),
                'device_type' => $device['device_type'],
                'platform' => $device['platform'],
                'browser' => $device['browser'],
                'country' => $location['country'],
                'city' => $location['city'],
                'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            ]);
        } catch (\Throwable $e) {
            // Tracking is best-effort; never let it surface to the user.
            Log::debug('Visit tracking failed', ['error' => $e->getMessage()]);
        }
    }

    private function label(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }

        return self::LABELS[$routeName] ?? Str::of($routeName)->replace(['.', '-'], ' ')->headline()->toString();
    }

    /**
     * The referrer, but only when it points somewhere OTHER than this app — an
     * internal SPA navigation isn't "where the user came from".
     */
    private function referrer(Request $request): ?string
    {
        $referrer = $request->headers->get('referer');

        if ($referrer === null || $referrer === '') {
            return null;
        }

        $referrerHost = parse_url($referrer, PHP_URL_HOST);

        if ($referrerHost !== null && $referrerHost === $request->getHost()) {
            return null;
        }

        return Str::limit($referrer, 1000, '');
    }
}
