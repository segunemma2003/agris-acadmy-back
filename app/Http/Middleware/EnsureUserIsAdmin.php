<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        // Allow access to login pages and authentication routes
        if ($request->routeIs('filament.admin.auth.login') || 
            $request->routeIs('filament.admin.auth.*') ||
            $request->routeIs('filament.admin.*.login') ||
            str_contains($request->path(), 'admin/login')) {
            return $next($request);
        }

        $user = Auth::user();
        
        // Check if user is authenticated
        if (!$user) {
            abort(403, 'Unauthorized. Please log in.');
        }

        // Check if user has admin role
        if ($user->role !== 'admin') {
            abort(403, 'Unauthorized. Admin access required. Your role: ' . ($user->role ?? 'none'));
        }

        // Check if user is active
        if (!$user->is_active) {
            abort(403, 'Unauthorized. Your account is inactive. Please contact support.');
        }

        return $next($request);
    }
}

