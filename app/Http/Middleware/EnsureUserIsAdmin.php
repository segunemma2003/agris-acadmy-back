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

        $user = Auth::guard('web')->user();

        if (! $user || $user->role !== 'admin' || ! $user->is_active) {
            Auth::guard('web')->logout();

            return redirect()->route('filament.admin.auth.login')
                ->withErrors(['email' => 'Only active admin accounts can access this panel.']);
        }

        return $next($request);
    }
}

