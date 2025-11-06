<?php

/**
 * Create Test Data for API Testing
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Category;
use App\Models\Course;
use App\Models\Module;
use App\Models\Topic;
use Illuminate\Support\Facades\Hash;

echo "Creating test data...\n\n";

// Create Admin User
$admin = User::firstOrCreate(
    ['email' => 'admin@example.com'],
    [
        'name' => 'Admin User',
        'password' => Hash::make('password123'),
        'role' => 'admin',
        'is_active' => true,
    ]
);
echo "✓ Admin user created: admin@example.com / password123\n";

// Create Tutor User
try {
    $tutor = User::firstOrCreate(
        ['email' => 'tutor@example.com'],
        [
            'name' => 'Tutor User',
            'password' => Hash::make('password123'),
            'role' => 'tutor',
            'is_active' => true,
        ]
    );
    echo "✓ Tutor user created: tutor@example.com / password123\n";
} catch (\Exception $e) {
    // Try to get existing user
    $tutor = User::where('email', 'tutor@example.com')->first();
    if (!$tutor) {
        $tutor = User::create([
            'name' => 'Tutor User',
            'email' => 'tutor@example.com',
            'password' => Hash::make('password123'),
            'role' => 'tutor',
            'is_active' => true,
        ]);
    }
    echo "✓ Tutor user: tutor@example.com / password123\n";
}

// Create Student User
try {
    $student = User::firstOrCreate(
        ['email' => 'student@example.com'],
        [
            'name' => 'Student User',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'is_active' => true,
        ]
    );
    echo "✓ Student user created: student@example.com / password123\n";
} catch (\Exception $e) {
    $student = User::where('email', 'student@example.com')->first();
    if (!$student) {
        $student = User::create([
            'name' => 'Student User',
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'is_active' => true,
        ]);
    }
    echo "✓ Student user: student@example.com / password123\n";
}

// Create Category
$category = Category::firstOrCreate(
    ['slug' => 'agriculture'],
    [
        'name' => 'Agriculture',
        'description' => 'Agricultural courses and farming techniques',
        'is_active' => true,
        'sort_order' => 1,
    ]
);
echo "✓ Category created: Agriculture\n";

// Create Course
$course = Course::firstOrCreate(
    ['slug' => 'introduction-to-farming'],
    [
        'category_id' => $category->id,
        'tutor_id' => $tutor->id,
        'title' => 'Introduction to Farming',
        'short_description' => 'Learn the basics of modern farming techniques',
        'description' => 'This comprehensive course covers all aspects of modern farming, from soil preparation to harvest. You will learn practical techniques that can be applied immediately.',
        'what_you_will_learn' => json_encode([
            'Soil preparation and management',
            'Crop selection and planting',
            'Irrigation techniques',
            'Pest and disease control',
            'Harvesting and storage',
        ]),
        'what_you_will_get' => json_encode([
            '5 downloadable resources',
            'Video lessons',
            'Certificate of completion',
            'Lifetime access',
        ]),
        'level' => 'beginner',
        'language' => 'English',
        'duration_minutes' => 120,
        'materials_count' => 5,
        'is_published' => true,
        'is_featured' => true,
        'price' => 99.99,
        'is_free' => false,
    ]
);
echo "✓ Course created: Introduction to Farming\n";

// Create Module
$module = Module::firstOrCreate(
    [
        'course_id' => $course->id,
        'title' => 'Module 1: Getting Started',
    ],
    [
        'description' => 'Introduction to farming basics',
        'sort_order' => 1,
        'is_active' => true,
    ]
);
echo "✓ Module created: Module 1: Getting Started\n";

// Create Topic
$topic = Topic::firstOrCreate(
    [
        'module_id' => $module->id,
        'title' => 'Topic 1: Understanding Soil',
    ],
    [
        'description' => 'Learn about different soil types and their properties',
        'video_url' => 'https://example.com/video1.mp4',
        'transcript' => 'This is a transcript of the video lesson about soil types...',
        'duration_minutes' => 15,
        'content_type' => 'video',
        'sort_order' => 1,
        'is_active' => true,
    ]
);
echo "✓ Topic created: Topic 1: Understanding Soil\n";

// Update module total topics
$module->updateTotalTopics();

echo "\n✅ Test data created successfully!\n\n";
echo "You can now test the API with:\n";
echo "  - Admin: admin@example.com / password123\n";
echo "  - Tutor: tutor@example.com / password123\n";
echo "  - Student: student@example.com / password123\n";
echo "  - Course ID: {$course->id}\n";
echo "  - Category ID: {$category->id}\n";

