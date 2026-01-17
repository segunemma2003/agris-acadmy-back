<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log that middleware is being called
        Log::info('EnsureUserIsAdmin middleware START', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);

        // Allow access to login pages and authentication routes
        // We need to allow login/auth routes to pass through before checking authentication
        $path = $request->path();
        $routeName = $request->route()?->getName() ?? '';

        // Allow all authentication-related routes (login, logout, etc.)
        if ($request->routeIs('filament.admin.auth.login') ||
            $request->routeIs('filament.admin.auth.*') ||
            $request->routeIs('filament.admin.*.login') ||
            str_contains($path, 'admin/login') ||
            str_contains($path, 'admin/auth') ||
            str_contains($routeName, 'filament.admin.auth')) {
            return $next($request);
        }

        // At this point, user should be authenticated (Authenticate middleware runs first)
        $user = Auth::guard('web')->user();

        // Debug logging only - no restrictions
        Log::info('EnsureUserIsAdmin middleware running', [
            'path' => $path,
            'route' => $routeName,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_role' => $user?->role,
            'user_is_active' => $user?->is_active,
        ]);

        // Just log and allow through - no blocking
        if ($user) {
            Log::info('EnsureUserIsAdmin: Allowing access', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'path' => $path,
            ]);
        }

        return $next($request);
    }
}

