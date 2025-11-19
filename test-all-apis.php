<?php

/**
 * Comprehensive API Testing Script
 * Run: php test-all-apis.php
 */

$baseUrl = 'http://127.0.0.1:8000/api';
$token = null;
$testResults = [];

// Colors for output
define('GREEN', "\033[32m");
define('RED', "\033[31m");
define('YELLOW', "\033[33m");
define('BLUE', "\033[34m");
define('NC', "\033[0m"); // No Color

function makeRequest($method, $endpoint, $data = null, $useAuth = false) {
    global $baseUrl, $token;

    $url = $baseUrl . $endpoint;
    $ch = curl_init($url);

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];

    if ($useAuth && $token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'body' => $response,
        'data' => json_decode($response, true)
    ];
}

function test($name, $method, $endpoint, $data = null, $useAuth = false, $expectedCode = 200) {
    global $testResults;

    echo "Testing: $name... ";
    $result = makeRequest($method, $endpoint, $data, $useAuth);

    $success = $result['code'] == $expectedCode || ($expectedCode >= 200 && $expectedCode < 300 && $result['code'] >= 200 && $result['code'] < 300);

    if ($success) {
        echo GREEN . "✓ PASS" . NC . " (HTTP $result[code])\n";
    } else {
        echo RED . "✗ FAIL" . NC . " (Expected: $expectedCode, Got: $result[code])\n";
        if ($result['data'] && isset($result['data']['message'])) {
            echo "  Message: " . $result['data']['message'] . "\n";
        }
    }

    $testResults[] = [
        'name' => $name,
        'success' => $success,
        'code' => $result['code'],
        'endpoint' => $endpoint
    ];

    return $result;
}

function extractToken($result) {
    global $token;
    if (isset($result['data']['data']['token'])) {
        $token = $result['data']['data']['token'];
    } elseif (isset($result['data']['token'])) {
        $token = $result['data']['token'];
    }
}

echo BLUE . "\n========================================\n";
echo "  COMPREHENSIVE API TESTING\n";
echo "========================================\n\n" . NC;

// Generate unique test user
$testEmail = 'test_' . time() . '@example.com';
$testPassword = 'Test123456!';

echo BLUE . "1. AUTHENTICATION TESTS\n" . NC;
echo "----------------------------------------\n";

// Test 1: Register
$result = test(
    "Register Student",
    "POST",
    "/register",
    [
        'name' => 'Test User',
        'email' => $testEmail,
        'password' => $testPassword,
        'password_confirmation' => $testPassword,
        'phone' => '+1234567890'
    ]
);
extractToken($result);

// Test 2: Login
if (!$token) {
    $result = test(
        "Login Student",
        "POST",
        "/login",
        [
            'email' => $testEmail,
            'password' => $testPassword
        ]
    );
    extractToken($result);
}

// Test 3: Get Current User
if ($token) {
    test("Get Current User", "GET", "/user", null, true);
}

// Test 4: Forgot Password
test("Forgot Password", "POST", "/forgot-password", ['email' => $testEmail], false, 200);

echo "\n" . BLUE . "2. PUBLIC CATEGORY TESTS\n" . NC;
echo "----------------------------------------\n";

test("Get All Categories", "GET", "/categories");
test("Get Categories with Courses", "GET", "/categories-with-courses");
test("Get Featured Courses", "GET", "/featured-courses");

// Try to get a category (may fail if none exist)
$catResult = makeRequest("GET", "/categories");
if ($catResult['data'] && isset($catResult['data']['data']) && count($catResult['data']['data']) > 0) {
    $categoryId = $catResult['data']['data'][0]['id'];
    test("Get Category Details", "GET", "/categories/$categoryId");
}

echo "\n" . BLUE . "3. PUBLIC COURSE TESTS\n" . NC;
echo "----------------------------------------\n";

test("Get All Courses", "GET", "/courses");
test("Get All Courses (with filters)", "GET", "/courses?level=beginner&per_page=10");
test("Search Courses", "GET", "/courses?search=test");

// Try to get a course (may fail if none exist)
$courseResult = makeRequest("GET", "/courses");
$courseId = null;
if ($courseResult['data'] && isset($courseResult['data']['data']) && count($courseResult['data']['data']) > 0) {
    $courseId = $courseResult['data']['data'][0]['id'];
    test("Get Course Details", "GET", "/courses/$courseId");
}

echo "\n" . BLUE . "4. PROTECTED COURSE TESTS\n" . NC;
echo "----------------------------------------\n";

if ($token) {
    if ($courseId) {
        test("Get Course Modules", "GET", "/courses/$courseId/modules", null, true);
        test("Get Course Information", "GET", "/courses/$courseId/information", null, true);
        test("Get Course DIY Content", "GET", "/courses/$courseId/diy-content", null, true, 403); // May fail if not enrolled
        test("Get Course Resources", "GET", "/courses/$courseId/resources", null, true, 403); // May fail if not enrolled
    }

    test("Get Recommended Courses", "GET", "/recommended-courses", null, true);
}

echo "\n" . BLUE . "5. ENROLLMENT TESTS\n" . NC;
echo "----------------------------------------\n";

if ($token) {
    test("Get My Enrollments", "GET", "/my-enrollments", null, true);
    test("Get My Courses", "GET", "/my-courses", null, true);
    test("Get Ongoing Courses", "GET", "/ongoing-courses", null, true);
    test("Get Completed Courses", "GET", "/completed-courses", null, true);

    // Try to enroll (will fail without valid code)
    if ($courseId) {
        test(
            "Enroll in Course (will fail without code)",
            "POST",
            "/enroll",
            [
                'course_id' => $courseId,
                'enrollment_code' => 'INVALID_CODE'
            ],
            true,
            400
        );
    }
}

echo "\n" . BLUE . "6. PROGRESS TESTS\n" . NC;
echo "----------------------------------------\n";

if ($token && $courseId) {
    test("Get Course Progress", "GET", "/courses/$courseId/progress", null, true, 403); // May fail if not enrolled
}

echo "\n" . BLUE . "7. NOTES TESTS\n" . NC;
echo "----------------------------------------\n";

if ($token && $courseId) {
    test("Get Course Notes", "GET", "/courses/$courseId/notes", null, true, 403); // May fail if not enrolled

    // Try to get module notes (will need module ID)
    $modulesResult = makeRequest("GET", "/courses/$courseId/modules", null, true);
    if ($modulesResult['data'] && isset($modulesResult['data']['data']['modules']) && count($modulesResult['data']['data']['modules']) > 0) {
        $moduleId = $modulesResult['data']['data']['modules'][0]['id'];
        test("Get Module Notes", "GET", "/courses/$courseId/modules/$moduleId/notes", null, true, 403);
    }
}

echo "\n" . BLUE . "8. MODULE TESTS\n" . NC;
echo "----------------------------------------\n";

if ($token && $courseId) {
    $modulesResult = makeRequest("GET", "/courses/$courseId/modules", null, true);
    if ($modulesResult['data'] && isset($modulesResult['data']['data']['modules']) && count($modulesResult['data']['data']['modules']) > 0) {
        $moduleId = $modulesResult['data']['data']['modules'][0]['id'];
        test("Get Module Details", "GET", "/courses/$courseId/modules/$moduleId", null, true, 403); // May fail if not enrolled
        test("Get Module Test", "GET", "/courses/$courseId/modules/$moduleId/test", null, true, 403); // May fail if not enrolled
    }
}

echo "\n" . BLUE . "9. ASSIGNMENT TESTS\n" . NC;
echo "----------------------------------------\n";

if ($token && $courseId) {
    test("Get Course Assignments", "GET", "/courses/$courseId/assignments", null, true, 403); // May fail if not enrolled
    test("Get My Submissions", "GET", "/my-submissions", null, true);
}

echo "\n" . BLUE . "10. MESSAGE TESTS\n" . NC;
echo "----------------------------------------\n";

if ($token && $courseId) {
    test("Get Course Messages", "GET", "/courses/$courseId/messages", null, true, 403); // May fail if not enrolled
}

echo "\n" . BLUE . "11. LOGOUT TEST\n" . NC;
echo "----------------------------------------\n";

if ($token) {
    test("Logout", "POST", "/logout", null, true);
}

// Summary
echo "\n" . BLUE . "========================================\n";
echo "  TEST SUMMARY\n";
echo "========================================\n\n" . NC;

$total = count($testResults);
$passed = count(array_filter($testResults, fn($r) => $r['success']));
$failed = $total - $passed;

echo "Total Tests: $total\n";
echo GREEN . "Passed: $passed" . NC . "\n";
echo RED . "Failed: $failed" . NC . "\n\n";

if ($failed > 0) {
    echo RED . "Failed Tests:\n" . NC;
    foreach ($testResults as $result) {
        if (!$result['success']) {
            echo "  - " . $result['name'] . " (HTTP " . $result['code'] . ")\n";
            echo "    Endpoint: " . $result['endpoint'] . "\n";
        }
    }
}

echo "\n" . YELLOW . "Note: Some tests may fail if:" . NC . "\n";
echo "  - No courses/categories exist in database\n";
echo "  - User is not enrolled in courses (403 errors expected)\n";
echo "  - Invalid enrollment codes (400 errors expected)\n";
echo "  - Server is not running on localhost:8000\n\n";

echo GREEN . "Testing complete!\n" . NC;
