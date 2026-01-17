<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CustomFilamentAuthenticate extends FilamentAuthenticate
{
    protected function authenticate($request, array $guards): void
    {
        Log::info('CustomFilamentAuthenticate: Starting authentication', [
            'path' => $request->path(),
            'guards' => $guards,
        ]);

        try {
            parent::authenticate($request, $guards);
            
            $user = Auth::guard('web')->user();
            Log::info('CustomFilamentAuthenticate: Authentication successful', [
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'user_role' => $user?->role,
            ]);
        } catch (\Exception $e) {
            Log::error('CustomFilamentAuthenticate: Authentication failed', [
                'error' => $e->getMessage(),
                'path' => $request->path(),
            ]);
            throw $e;
        }
    }
}

