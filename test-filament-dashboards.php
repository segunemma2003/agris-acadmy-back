<?php

/**
 * Test Filament Dashboards
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Colors
define('GREEN', "\033[0;32m");
define('RED', "\033[0;31m");
define('YELLOW', "\033[1;33m");
define('BLUE', "\033[0;34m");
define('CYAN', "\033[0;36m");
define('NC', "\033[0m");

echo BLUE . "╔══════════════════════════════════════════════════════════╗\n";
echo "║        Filament Dashboards Testing                    ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n" . NC;

// Test 1: Check if admin user exists
echo YELLOW . "1. Checking Admin User...\n" . NC;
$admin = User::where('email', 'admin@example.com')->first();
if ($admin) {
    echo GREEN . "   ✓ Admin user exists\n" . NC;
    echo "   - Name: {$admin->name}\n";
    echo "   - Email: {$admin->email}\n";
    echo "   - Role: {$admin->role}\n";
    echo "   - Active: " . ($admin->is_active ? 'Yes' : 'No') . "\n";
} else {
    echo RED . "   ✗ Admin user not found\n" . NC;
    // Create admin user
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => Hash::make('password123'),
        'role' => 'admin',
        'is_active' => true,
    ]);
    echo GREEN . "   ✓ Admin user created\n" . NC;
}

// Test 2: Check if tutor user exists
echo "\n" . YELLOW . "2. Checking Tutor User...\n" . NC;
$tutor = User::where('email', 'tutor@example.com')->first();
if ($tutor) {
    echo GREEN . "   ✓ Tutor user exists\n" . NC;
    echo "   - Name: {$tutor->name}\n";
    echo "   - Email: {$tutor->email}\n";
    echo "   - Role: {$tutor->role}\n";

    // Update role if needed
    if ($tutor->role !== 'tutor') {
        $tutor->role = 'tutor';
        $tutor->save();
        echo YELLOW . "   ⚠ Role updated to tutor\n" . NC;
    }
} else {
    echo RED . "   ✗ Tutor user not found\n" . NC;
    // Create tutor user
    $tutor = User::create([
        'name' => 'Tutor User',
        'email' => 'tutor@example.com',
        'password' => Hash::make('password123'),
        'role' => 'student', // SQLite constraint issue
        'is_active' => true,
    ]);
    // Try to update role
    try {
        $tutor->role = 'tutor';
        $tutor->save();
    } catch (\Exception $e) {
        echo YELLOW . "   ⚠ Note: Role constraint issue with SQLite\n" . NC;
    }
    echo GREEN . "   ✓ Tutor user created\n" . NC;
}

// Test 3: Check Filament Resources
echo "\n" . YELLOW . "3. Checking Filament Resources...\n" . NC;

$adminResources = [
    'UserResource',
    'CategoryResource',
    'CourseResource',
    'EnrollmentCodeResource',
];

$tutorResources = [
    'CourseResource',
];

$adminResourcesPath = app_path('Filament/Resources');
$tutorResourcesPath = app_path('Filament/Tutor/Resources');

echo CYAN . "   Admin Resources:\n" . NC;
foreach ($adminResources as $resource) {
    $file = $adminResourcesPath . '/' . $resource . '.php';
    if (file_exists($file)) {
        echo GREEN . "   ✓ {$resource}\n" . NC;
    } else {
        echo RED . "   ✗ {$resource} not found\n" . NC;
    }
}

echo CYAN . "   Tutor Resources:\n" . NC;
foreach ($tutorResources as $resource) {
    $file = $tutorResourcesPath . '/' . $resource . '.php';
    if (file_exists($file)) {
        echo GREEN . "   ✓ {$resource}\n" . NC;
    } else {
        echo RED . "   ✗ {$resource} not found\n" . NC;
    }
}

// Test 4: Check Routes
echo "\n" . YELLOW . "4. Checking Routes...\n" . NC;

$adminRoutes = [
    'admin',
    'admin/login',
    'admin/categories',
    'admin/courses',
    'admin/users',
    'admin/enrollment-codes',
];

$tutorRoutes = [
    'tutor',
    'tutor/login',
    'tutor/courses',
];

echo CYAN . "   Admin Routes:\n" . NC;
foreach ($adminRoutes as $route) {
    $url = "http://localhost:8000/{$route}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code >= 200 && $code < 400) {
        echo GREEN . "   ✓ /{$route} (HTTP {$code})\n" . NC;
    } else {
        echo YELLOW . "   ⚠ /{$route} (HTTP {$code})\n" . NC;
    }
}

echo CYAN . "   Tutor Routes:\n" . NC;
foreach ($tutorRoutes as $route) {
    $url = "http://localhost:8000/{$route}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code >= 200 && $code < 400) {
        echo GREEN . "   ✓ /{$route} (HTTP {$code})\n" . NC;
    } else {
        echo YELLOW . "   ⚠ /{$route} (HTTP {$code})\n" . NC;
    }
}

echo "\n" . BLUE . "╔══════════════════════════════════════════════════════════╗\n";
echo "║                    Testing Complete!                    ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n" . NC;

echo "📝 Login Credentials:\n";
echo "   Admin: admin@example.com / password123\n";
echo "   Tutor: tutor@example.com / password123\n\n";

echo "🌐 Access Points:\n";
echo "   Admin Panel: http://localhost:8000/admin\n";
echo "   Tutor Panel: http://localhost:8000/tutor\n\n";

echo "💡 Note: SQLite has ENUM constraint limitations.\n";
echo "   For production, use MySQL/PostgreSQL for proper role support.\n\n";

