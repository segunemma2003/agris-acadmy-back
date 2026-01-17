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
        // This middleware runs in authMiddleware, so it only runs after authentication
        // But we still need to allow login/auth routes to pass through
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
        
        if (!$user) {
            // This shouldn't happen if Authenticate middleware worked, but just in case
            return redirect()->route('filament.admin.auth.login');
        }

        // Refresh user from database to ensure we have latest data
        $user->refresh();

        // Check if user has admin role
        if ($user->role !== 'admin') {
            \Log::warning('Admin panel access denied - wrong role', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'path' => $path,
                'route' => $routeName,
            ]);
            abort(403, 'Unauthorized. Admin access required. Your role: ' . $user->role . '. Email: ' . $user->email);
        }

        // Check if user is active
        if (!$user->is_active) {
            \Log::warning('Admin panel access denied - inactive account', [
                'user_id' => $user->id,
                'email' => $user->email,
                'path' => $path,
            ]);
            abort(403, 'Unauthorized. Your account is inactive. Please contact support. Email: ' . $user->email);
        }

        return $next($request);
    }
}

