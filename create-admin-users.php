<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$adminUsers = [
    [
        'email' => 'admin@agrisiti.com',
        'name' => 'Admin User',
        'password' => 'admin123',
    ],
    [
        'email' => 'admins@agrisiti.com',
        'name' => 'Admin User 2',
        'password' => 'admin123',
    ],
];

foreach ($adminUsers as $adminData) {
    $admin = User::firstOrCreate(
        ['email' => $adminData['email']],
        [
            'name' => $adminData['name'],
            'password' => Hash::make($adminData['password']),
            'role' => 'admin',
            'is_active' => true,
        ]
    );

    if ($admin->wasRecentlyCreated) {
        echo "✓ Admin user created: {$adminData['email']}\n";
    } else {
        $admin->update([
            'password' => Hash::make($adminData['password']),
            'role' => 'admin',
            'is_active' => true,
            'name' => $adminData['name'],
        ]);
        echo "✓ Admin user updated: {$adminData['email']} (password reset to {$adminData['password']})\n";
    }
}

echo "✓ All admin users processed successfully\n";





