<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTutor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow access to login pages
        if ($request->routeIs('filament.tutor.auth.login') || $request->routeIs('filament.tutor.auth.*')) {
            return $next($request);
        }

        $user = Auth::user();
        if (!$user || $user->role !== 'tutor') {
            abort(403, 'Unauthorized. Tutor access required.');
        }

        return $next($request);
    }
}

