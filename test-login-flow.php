<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

echo "=== Testing Login Flow ===\n\n";

// Get admin user
$admin = User::where('email', 'admin@agrisiti.com')->first();

if (!$admin) {
    echo "❌ Admin user not found!\n";
    exit(1);
}

echo "✓ Admin user found: {$admin->email}\n";
echo "  - Password check: " . (Hash::check('admin123', $admin->password) ? '✓ CORRECT' : '✗ INCORRECT') . "\n\n";

// Test authentication
echo "Testing authentication:\n";
$attempt = Auth::guard('web')->attempt([
    'email' => 'admin@agrisiti.com',
    'password' => 'admin123'
]);

echo "  Auth attempt result: " . ($attempt ? '✓ SUCCESS' : '✗ FAILED') . "\n";

if ($attempt) {
    $user = Auth::guard('web')->user();
    echo "  Authenticated user: {$user->email}\n";
    echo "  User role: {$user->role}\n";
    echo "  User is_active: " . ($user->is_active ? 'YES' : 'NO') . "\n";
    echo "  canAccessPanel('admin'): " . ($user->canAccessPanel('admin') ? '✓ YES' : '✗ NO') . "\n";
} else {
    echo "  ❌ Authentication failed!\n";
    echo "  This means the credentials are wrong or the user is inactive.\n";
}

echo "\n";





