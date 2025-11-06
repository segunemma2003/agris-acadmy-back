<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow access to login page
        if ($request->routeIs('filament.admin.auth.login') || $request->routeIs('filament.admin.auth.*')) {
            return $next($request);
        }

        // Check authentication and role for other routes
        if (!auth()->check()) {
            return redirect()->route('filament.admin.auth.login');
        }

        if (!auth()->user()->isAdmin()) {
            $userRole = auth()->user()->role ?? 'not set';
            abort(403, "Unauthorized access. Admin role required. Your current role is: '{$userRole}'. To make this user an admin, run: php artisan user:make-admin " . auth()->user()->email);
        }

        return $next($request);
    }
}

