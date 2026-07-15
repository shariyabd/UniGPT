<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hidden, URL-driven maintenance switch.
 *
 * Any request carrying `?live=true` (or `live=1`) flips the whole app back to
 * normal operation; `?live=false` (or `live=0`) drops it into Maintenance Mode.
 * The chosen state is remembered globally (cache) so it sticks across every
 * subsequent request and visitor until toggled again — an operator only needs
 * to hit one URL, from any page, to switch the site.
 *
 * While in maintenance, every request renders the Maintenance page (HTTP 503).
 * The `?live=true` shortcut is evaluated first, so an operator can always let
 * themselves back in even from a locked-down site.
 *
 * The default state (before any toggle) comes from config `app.live_default`
 * (env `APP_LIVE_DEFAULT`, default `true` = live), so the app works out of the
 * box; set it to `false` for a "public sees maintenance, insiders unlock with
 * ?live=true" gate. The test environment is never gated.
 */
class HandleMaintenanceMode
{
    private const CACHE_KEY = 'app.live_state';

    public function handle(Request $request, Closure $next): Response
    {
        // Honour an explicit toggle on this request and persist it globally.
        if ($request->has('live')) {
            Cache::forever(self::CACHE_KEY, $request->boolean('live'));
        }

        if ($this->isLive() || $this->isExempt($request)) {
            return $next($request);
        }

        return $this->maintenanceResponse($request);
    }

    private function isLive(): bool
    {
        return (bool) Cache::get(self::CACHE_KEY, config('app.live_default', true));
    }

    /**
     * Requests that must never be gated: the test suite (so features keep
     * running) and the framework health check.
     */
    private function isExempt(Request $request): bool
    {
        return app()->environment('testing')
            || $request->is('up');
    }

    private function maintenanceResponse(Request $request): Response
    {
        $payload = [
            'appName' => config('app.name', 'UniNexus'),
        ];

        // Plain API/JSON callers (non-Inertia) get a JSON 503; page/Inertia
        // requests get the rendered Maintenance page.
        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => 'The application is currently under maintenance. Please try again shortly.',
                ...$payload,
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return Inertia::render('Maintenance', $payload)
            ->toResponse($request)
            ->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
