<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "=== Testing New Admin User Authentication ===\n\n";

$user = User::where('email', 'testadmin@agrisiti.com')->first();

if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

echo "✓ User found: {$user->email}\n";
echo "  - ID: {$user->id}\n";
echo "  - Role: {$user->role}\n";
echo "  - Is Active: " . ($user->is_active ? 'YES' : 'NO') . "\n\n";

// Test canAccessPanel
echo "Testing canAccessPanel('admin'):\n";
$canAccess = $user->canAccessPanel('admin');
echo "  Result: " . ($canAccess ? '✓ TRUE' : '✗ FALSE') . "\n\n";

// Test authentication
echo "Testing authentication:\n";
Auth::guard('web')->login($user);
$authenticated = Auth::guard('web')->check();
echo "  Authenticated: " . ($authenticated ? '✓ YES' : '✗ NO') . "\n";

if ($authenticated) {
    $authUser = Auth::guard('web')->user();
    echo "  Authenticated User ID: {$authUser->id}\n";
    echo "  Authenticated User Email: {$authUser->email}\n";
    echo "  Authenticated User Role: {$authUser->role}\n";
    echo "  Can Access Panel (after auth): " . ($authUser->canAccessPanel('admin') ? '✓ YES' : '✗ NO') . "\n";
}

echo "\n=== Summary ===\n";
if ($canAccess && $authenticated && $user->canAccessPanel('admin')) {
    echo "✅ All checks pass!\n";
    echo "   The new admin user should be able to login.\n";
    echo "   If you get 403, it's likely a server-specific issue.\n";
} else {
    echo "❌ Some checks failed!\n";
}

echo "\n";
echo "To test in browser:\n";
echo "  1. Go to: http://127.0.0.1:8000/admin/login\n";
echo "  2. Login with: testadmin@agrisiti.com / test123\n";
echo "  3. Check if you can access the dashboard\n";

