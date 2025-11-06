<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTutor
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow access to login page
        if ($request->routeIs('filament.tutor.auth.login') || $request->routeIs('filament.tutor.auth.*')) {
            return $next($request);
        }

        // Check authentication and role for other routes
        if (!auth()->check()) {
            return redirect()->route('filament.tutor.auth.login');
        }

        if (!auth()->user()->isTutor()) {
            $userRole = auth()->user()->role ?? 'not set';
            abort(403, "Unauthorized access. Tutor role required. Your current role is: '{$userRole}'. To make this user a tutor, run: php artisan user:make-tutor " . auth()->user()->email);
        }

        return $next($request);
    }
}

