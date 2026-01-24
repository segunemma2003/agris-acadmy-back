<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "=== 403 Error Diagnosis ===\n\n";

// Check admin user
$admin = User::where('email', 'admin@agrisiti.com')->first();

if (!$admin) {
    echo "❌ Admin user not found!\n";
    exit(1);
}

echo "✓ Admin user found:\n";
echo "  - ID: {$admin->id}\n";
echo "  - Email: {$admin->email}\n";
echo "  - Role: {$admin->role}\n";
echo "  - Is Active: " . ($admin->is_active ? 'YES' : 'NO') . "\n\n";

// Test canAccessPanel
echo "Testing canAccessPanel('admin'):\n";
$canAccess = $admin->canAccessPanel('admin');
echo "  Result: " . ($canAccess ? '✓ TRUE' : '✗ FALSE') . "\n\n";

// Test isAdmin
echo "Testing isAdmin():\n";
$isAdmin = $admin->isAdmin();
echo "  Result: " . ($isAdmin ? '✓ TRUE' : '✗ FALSE') . "\n\n";

// Simulate authentication
echo "Simulating authentication:\n";
Auth::guard('web')->login($admin);
$authenticated = Auth::guard('web')->check();
echo "  Authenticated: " . ($authenticated ? '✓ YES' : '✗ NO') . "\n";

if ($authenticated) {
    $user = Auth::guard('web')->user();
    echo "  User ID: {$user->id}\n";
    echo "  User Email: {$user->email}\n";
    echo "  User Role: {$user->role}\n";
    echo "  Can Access Panel: " . ($user->canAccessPanel('admin') ? '✓ YES' : '✗ NO') . "\n";
}

echo "\n=== Summary ===\n";
echo "canAccessPanel returns: " . ($canAccess ? 'TRUE' : 'FALSE') . "\n";
echo "isAdmin returns: " . ($isAdmin ? 'TRUE' : 'FALSE') . "\n";
echo "Authentication works: " . ($authenticated ? 'YES' : 'NO') . "\n";

if ($canAccess && $isAdmin && $authenticated) {
    echo "\n✅ All checks pass - the issue might be:\n";
    echo "   1. Filament's Authenticate middleware blocking\n";
    echo "   2. Web server (Nginx) blocking\n";
    echo "   3. Session/cookie issues\n";
    echo "   4. Filament version compatibility\n";
} else {
    echo "\n❌ Some checks failed - this is likely the issue!\n";
}

echo "\n";





