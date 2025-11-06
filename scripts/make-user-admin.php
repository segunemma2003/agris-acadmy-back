<?php

/**
 * Make a user an admin
 * Usage: php scripts/make-user-admin.php user@example.com
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

// Get email from command line argument
$email = $argv[1] ?? null;

if (!$email) {
    echo "❌ Error: Please provide an email address\n";
    echo "Usage: php scripts/make-user-admin.php user@example.com\n";
    exit(1);
}

echo "🔍 Looking for user: {$email}\n";

$user = User::where('email', $email)->first();

if (!$user) {
    echo "❌ User with email '{$email}' not found.\n";
    echo "   Please create the user first using: php artisan make:filament-user\n";
    exit(1);
}

if ($user->role === 'admin') {
    echo "ℹ️  User '{$email}' is already an admin.\n";
    exit(0);
}

echo "📝 Current role: {$user->role}\n";
echo "🔄 Updating to admin...\n";

$user->role = 'admin';
$user->is_active = true;
$user->save();

echo "✅ User '{$email}' has been made an admin successfully!\n";
echo "   They can now access the admin panel at /admin\n";
echo "\n";
echo "📋 User Details:\n";
echo "   - Name: {$user->name}\n";
echo "   - Email: {$user->email}\n";
echo "   - Role: {$user->role}\n";
echo "   - Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";

