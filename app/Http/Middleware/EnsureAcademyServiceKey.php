<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared secret for Finance → Academy service calls (Learn → Fund).
 */
class EnsureAcademyServiceKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.finance.service_key', '');

        if ($expected === '') {
            return response()->json([
                'success' => false,
                'message' => 'Academy service key is not configured.',
            ], 503);
        }

        $provided = (string) (
            $request->header('X-Academy-Service-Key')
            ?? $request->bearerToken()
            ?? ''
        );

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}
