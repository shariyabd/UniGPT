<?php

namespace App\Http\Middleware;

use App\Domain\Notification\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $this->resolveAuthUser($request),
            ],
            'flash' => fn () => $this->resolveFlash($request),
            'notifications' => fn () => $this->resolveNotifications($request),
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }

    /**
     * Build the shared authenticated-user payload consumed by every Vue page.
     *
     * @return array<string, mixed>|null
     */
    /**
     * Normalised one-shot flash payload consumed by the centralized toast pipeline.
     *
     * This is the SINGLE source of action feedback (add/update/delete/etc.) for the
     * whole app, so it must never surface twice. Two guarantees enforce that:
     *
     *  1. `pull()` (not `get()`) reads AND removes each key, so the flash is consumed
     *     the instant it is read. It can never be re-read on a later navigation,
     *     partial reload, or concurrent request (e.g. the notification poll) — which
     *     is the classic reason a "saved"/"deleted" toast reappears on the next page.
     *  2. A unique `id` is stamped on every flash response, and the client shows each
     *     id exactly once. So even if Inertia re-emits the same props (a `success`
     *     event plus the initial-page read, an HMR-stacked listener, or a re-render),
     *     the toast still fires a single time.
     *
     * @return array<string, string>
     */
    protected function resolveFlash(Request $request): array
    {
        $session = $request->session();

        // Pull every key unconditionally (no `??` short-circuit) so no alias is left
        // behind in the session to resurface later. Legacy `status` (logout / password
        // reset) maps to success; legacy `message` maps to info.
        $success = $session->pull('success');
        $status = $session->pull('status');
        $error = $session->pull('error');
        $warning = $session->pull('warning');
        $info = $session->pull('info');
        $message = $session->pull('message');

        $messages = array_filter([
            'success' => $success ?? $status,
            'error' => $error,
            'warning' => $warning,
            'info' => $info ?? $message,
        ]);

        if ($messages === []) {
            return [];
        }

        return ['id' => (string) Str::uuid(), ...$messages];
    }

    /**
     * Shared notification payload (unread badge + recent dropdown) for the navbar bell.
     *
     * @return array<string, mixed>
     */
    protected function resolveNotifications(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return ['unread' => 0, 'items' => []];
        }

        // Respect the user's notification preference — when muted, the bell shows
        // nothing rather than a stale badge.
        if (($user->preferences['notifications'] ?? true) === false) {
            return ['unread' => 0, 'items' => [], 'muted' => true];
        }

        $service = app(NotificationService::class);

        return [
            'unread' => $service->unreadCountFor($user),
            'items' => $service->recentFor($user)->all(),
            'muted' => false,
        ];
    }

    protected function resolveAuthUser(Request $request): ?array
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $user->loadMissing('roles.permissions', 'department');

        $permissions = $user->roles
            ->flatMap->permissions
            ->pluck('slug')
            ->unique()
            ->values()
            ->all();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'display_name' => $user->display_name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'bio' => $user->bio,
            'semester' => $user->semester,
            'identifier' => $user->identifier,
            'student_id' => $user->student_id,
            'employee_id' => $user->employee_id,
            'is_active' => $user->is_active,
            'preferences' => $user->preferences,
            'department' => $user->department?->only(['id', 'name', 'slug', 'code']),
            'roles' => $user->roles->pluck('slug')->values()->all(),
            'permissions' => $permissions,
            'primary_role' => $user->getPrimaryRole()?->slug,
            'dashboard_route' => $user->getDashboardRoute(),
        ];
    }
}
