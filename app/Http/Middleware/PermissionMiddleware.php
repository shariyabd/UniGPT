<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been deactivated. Please contact support.'
            ]);
        }

        if (!$user->hasAnyPermission($permissions)) {
            \Log::warning('Unauthorized permission access attempt', [
                'user_id' => $user->id,
                'email' => $user->email,
                'required_permissions' => $permissions,
                'route' => $request->route()->getName(),
                'ip' => $request->ip(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have the required permissions to access this resource.'
                ], 403);
            }

            return redirect()->route($user->getDashboardRoute())
                ->withErrors(['access' => 'You do not have the required permissions to access this resource.']);
        }

        return $next($request);
    }
}
