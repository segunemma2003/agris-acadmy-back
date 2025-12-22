<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTagDev
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow access to login pages
        if ($request->routeIs('filament.tagdev.auth.login') || $request->routeIs('filament.tagdev.auth.*')) {
            return $next($request);
        }

        $user = Auth::user();
        if (!$user || $user->role !== 'tagdev') {
            abort(403, 'Unauthorized. TagDev access required.');
        }

        return $next($request);
    }
}
