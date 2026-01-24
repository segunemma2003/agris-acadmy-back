<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$adminEmails = ['admin@agrisiti.com', 'admins@agrisiti.com'];

echo "=== Admin Users Status ===\n\n";

foreach ($adminEmails as $email) {
    $user = User::where('email', $email)->first();
    
    if (!$user) {
        echo "❌ User NOT FOUND: {$email}\n";
        continue;
    }
    
    echo "✓ User found: {$email}\n";
    echo "  - ID: {$user->id}\n";
    echo "  - Name: {$user->name}\n";
    echo "  - Role: {$user->role}\n";
    echo "  - Is Active: " . ($user->is_active ? 'YES' : 'NO') . "\n";
    echo "  - Email Verified: " . ($user->email_verified_at ? 'YES' : 'NO') . "\n";
    echo "  - Created: {$user->created_at}\n";
    echo "  - Updated: {$user->updated_at}\n";
    
    // Check if user can access admin panel
    $canAccess = $user->role === 'admin' && $user->is_active;
    echo "  - Can Access Admin Panel: " . ($canAccess ? 'YES ✓' : 'NO ✗') . "\n";
    
    if (!$canAccess) {
        echo "  ⚠️  ISSUES:\n";
        if ($user->role !== 'admin') {
            echo "     - Role is '{$user->role}' but should be 'admin'\n";
        }
        if (!$user->is_active) {
            echo "     - Account is not active (is_active = false)\n";
        }
    }
    
    echo "\n";
}

echo "=== Testing Authentication ===\n";
echo "Try logging in with:\n";
echo "  Email: admin@agrisiti.com\n";
echo "  Password: admin123\n";
echo "\n";
echo "Or:\n";
echo "  Email: admins@agrisiti.com\n";
echo "  Password: admin123\n";





