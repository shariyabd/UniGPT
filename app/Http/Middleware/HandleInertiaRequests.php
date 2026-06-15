<?php

namespace App\Http\Middleware;

use App\Domain\Notification\Services\NotificationService;
use Illuminate\Http\Request;
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
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
                'success' => fn () => $request->session()->get('success'),
            ],
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

        $service = app(NotificationService::class);

        return [
            'unread' => $service->unreadCountFor($user),
            'items' => $service->recentFor($user)->all(),
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
            'department' => $user->department?->only(['id', 'name', 'slug', 'code']),
            'roles' => $user->roles->pluck('slug')->values()->all(),
            'permissions' => $permissions,
            'primary_role' => $user->getPrimaryRole()?->slug,
            'dashboard_route' => $user->getDashboardRoute(),
        ];
    }
}
