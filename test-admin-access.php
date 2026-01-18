<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

echo "=== Testing Admin Access ===\n\n";

// Get admin user
$admin = User::where('email', 'admin@agrisiti.com')->first();

if (!$admin) {
    echo "❌ Admin user not found!\n";
    exit(1);
}

echo "✓ Admin user found: {$admin->email}\n";
echo "  - Role: {$admin->role}\n";
echo "  - Is Active: " . ($admin->is_active ? 'YES' : 'NO') . "\n\n";

// Test password
$password = 'admin123';
$passwordCheck = Hash::check($password, $admin->password);
echo "Password check for 'admin123': " . ($passwordCheck ? '✓ CORRECT' : '✗ INCORRECT') . "\n\n";

// Test canAccessPanel method
$canAccess = $admin->canAccessPanel('admin');
echo "canAccessPanel('admin'): " . ($canAccess ? '✓ YES' : '✗ NO') . "\n\n";

// Test isAdmin method
$isAdmin = $admin->isAdmin();
echo "isAdmin(): " . ($isAdmin ? '✓ YES' : '✗ NO') . "\n\n";

// Simulate authentication
Auth::guard('web')->login($admin);
$authenticatedUser = Auth::guard('web')->user();

if ($authenticatedUser) {
    echo "✓ User authenticated via web guard\n";
    echo "  - Authenticated User ID: {$authenticatedUser->id}\n";
    echo "  - Authenticated User Email: {$authenticatedUser->email}\n";
    echo "  - Authenticated User Role: {$authenticatedUser->role}\n";
    echo "  - Authenticated User Is Active: " . ($authenticatedUser->is_active ? 'YES' : 'NO') . "\n";
} else {
    echo "✗ User authentication failed\n";
}

echo "\n=== Summary ===\n";
echo "Admin user exists: ✓\n";
echo "Password correct: " . ($passwordCheck ? '✓' : '✗') . "\n";
echo "Can access panel: " . ($canAccess ? '✓' : '✗') . "\n";
echo "Is admin: " . ($isAdmin ? '✓' : '✗') . "\n";
echo "Authenticated: " . ($authenticatedUser ? '✓' : '✗') . "\n";

if ($passwordCheck && $canAccess && $isAdmin && $authenticatedUser) {
    echo "\n✅ All checks passed! Admin should be able to login.\n";
    echo "\nTry logging in at: http://127.0.0.1:8000/admin/login\n";
    echo "Email: admin@agrisiti.com\n";
    echo "Password: admin123\n";
} else {
    echo "\n❌ Some checks failed. Please review the output above.\n";
}



