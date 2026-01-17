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
            'auth_check_before' => Auth::guard('web')->check(),
            'user_before' => Auth::guard('web')->user()?->email ?? 'null',
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
            // Check what's in the session
            $sessionData = $request->session()->all();
            Log::error('CustomFilamentAuthenticate: Authentication failed', [
                'error' => $e->getMessage(),
                'path' => $request->path(),
                'session_id' => $request->session()->getId(),
                'auth_check' => Auth::guard('web')->check(),
                'session_keys' => array_keys($sessionData),
                'has_login_web' => isset($sessionData['login_web_59ba36addc2b2f9401580f014c67f316ea4']),
            ]);
            throw $e;
        }
    }
}

