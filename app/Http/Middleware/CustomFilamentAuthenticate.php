<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CustomFilamentAuthenticate extends FilamentAuthenticate
{
    protected function authenticate($request, array $guards): void
    {
        // Force use of 'web' guard if guards array is empty
        if (empty($guards)) {
            $guards = ['web'];
        }
        
        Log::info('CustomFilamentAuthenticate: Starting authentication', [
            'path' => $request->path(),
            'guards' => $guards,
            'session_id' => $request->session()->getId(),
        ]);

        try {
            // Call parent with corrected guards
            parent::authenticate($request, $guards);
            
            $user = Auth::guard('web')->user();
            Log::info('CustomFilamentAuthenticate: Authentication successful', [
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'user_role' => $user?->role,
                'is_active' => $user?->is_active,
            ]);
        } catch (\Exception $e) {
            Log::error('CustomFilamentAuthenticate: Authentication failed', [
                'error' => $e->getMessage(),
                'path' => $request->path(),
                'session_id' => $request->session()->getId(),
            ]);
            throw $e;
        }
    }
}

