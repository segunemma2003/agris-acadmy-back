<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsFacilitator
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow access to login pages
        if ($request->routeIs('filament.facilitator.auth.login') || $request->routeIs('filament.facilitator.auth.*')) {
            return $next($request);
        }

        $user = Auth::user();
        if (!$user || $user->role !== 'facilitator') {
            abort(403, 'Unauthorized. Facilitator access required.');
        }

        return $next($request);
    }
}
