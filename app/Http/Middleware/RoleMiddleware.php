<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {

        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (! $user->is_active) {
            auth()->logout();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been deactivated. Please contact support.',
            ]);
        }

        if (! $user->hasRole($roles)) {
            // Log unauthorized access attempt
            \Log::warning('Unauthorized access attempt', [
                'user_id' => $user->id,
                'email' => $user->email,
                'required_roles' => $roles,
                'user_roles' => $user->roles->pluck('slug')->toArray(),
                'route' => $request->route()->getName(),
                'ip' => $request->ip(),
            ]);

            return redirect()->route($user->getDashboardRoute())
                ->withErrors(['access' => 'You do not have permission to access this resource.']);
        }

        return $next($request);
    }
}
