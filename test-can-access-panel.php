<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "=== Testing canAccessPanel ===\n\n";

$admin = User::where('email', 'admin@agrisiti.com')->first();

if (!$admin) {
    echo "❌ Admin user not found!\n";
    exit(1);
}

echo "✓ Admin user found: {$admin->email}\n";
echo "  - ID: {$admin->id}\n";
echo "  - Role: {$admin->role}\n";
echo "  - Is Active: " . ($admin->is_active ? 'YES' : 'NO') . "\n\n";

// Test canAccessPanel
$canAccess = $admin->canAccessPanel('admin');
echo "canAccessPanel('admin'): " . ($canAccess ? '✓ YES' : '✗ NO') . "\n";

if (!$canAccess) {
    echo "\n❌ PROBLEM: canAccessPanel returns FALSE!\n";
    echo "This is likely why Filament is blocking access.\n";
    echo "\nDebugging:\n";
    echo "  - role === 'admin': " . ($admin->role === 'admin' ? 'YES' : 'NO') . "\n";
    echo "  - is_active: " . ($admin->is_active ? 'YES' : 'NO') . "\n";
    echo "  - Both true: " . (($admin->role === 'admin' && $admin->is_active) ? 'YES' : 'NO') . "\n";
} else {
    echo "\n✓ canAccessPanel returns TRUE - this should work!\n";
}

echo "\n";





