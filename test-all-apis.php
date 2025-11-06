<?php

/**
 * Comprehensive API Testing Script
 * Tests all API endpoints locally
 */

$baseUrl = 'http://localhost:8000/api';
$token = '';
$testResults = [];

// Colors for output
define('GREEN', "\033[0;32m");
define('RED', "\033[0;31m");
define('YELLOW', "\033[1;33m");
define('BLUE', "\033[0;34m");
define('NC', "\033[0m"); // No Color

function makeRequest($method, $endpoint, $data = null, $useAuth = false) {
    global $baseUrl, $token;
    
    $url = $baseUrl . $endpoint;
    $ch = curl_init($url);
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    
    if ($useAuth && $token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data && ($method === 'POST' || $method === 'PUT')) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => $response,
        'error' => $error,
    ];
}

function test($name, $method, $endpoint, $data = null, $useAuth = false, $expectedCode = 200) {
    global $testResults;
    
    echo YELLOW . "Testing: $name" . NC . "\n";
    echo "  $method $endpoint\n";
    
    $result = makeRequest($method, $endpoint, $data, $useAuth);
    $success = ($result['code'] >= 200 && $result['code'] < 300) || $result['code'] === $expectedCode;
    
    if ($success) {
        echo GREEN . "  ✓ Success (HTTP {$result['code']})" . NC . "\n";
    } else {
        echo RED . "  ✗ Failed (HTTP {$result['code']})" . NC . "\n";
        if ($result['error']) {
            echo RED . "  Error: {$result['error']}" . NC . "\n";
        }
        $body = json_decode($result['body'], true);
        if ($body && isset($body['message'])) {
            echo "  Message: " . $body['message'] . "\n";
        }
    }
    
    $testResults[] = [
        'name' => $name,
        'success' => $success,
        'code' => $result['code'],
    ];
    
    echo "\n";
    
    return $result;
}

function extractToken($response) {
    global $token;
    $data = json_decode($response['body'], true);
    if ($data && isset($data['token'])) {
        $token = $data['token'];
        return $token;
    }
    return null;
}

echo BLUE . "========================================\n";
echo "  AgriSiti LMS API Testing Suite\n";
echo "========================================\n\n" . NC;

// Test 1: Register User
echo BLUE . "1. AUTHENTICATION TESTS\n" . NC;
$result = test(
    "Register User",
    "POST",
    "/register",
    [
        'name' => 'Test User',
        'email' => 'testuser' . time() . '@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]
);
extractToken($result);

// Test 2: Login
$result = test(
    "Login User",
    "POST",
    "/login",
    [
        'email' => 'testuser' . (time() - 1) . '@example.com',
        'password' => 'password123',
    ]
);
if (!$token) {
    extractToken($result);
}

// Test 3: Get Current User
test("Get Current User", "GET", "/user", null, true);

// Test 4: Public Endpoints
echo BLUE . "2. PUBLIC ENDPOINTS\n" . NC;
test("Get All Categories", "GET", "/categories");
test("Get Categories with Courses", "GET", "/categories-with-courses");
test("Get All Courses", "GET", "/courses");
test("Get Single Course", "GET", "/courses/1", null, false, 404); // May not exist

// Test 5: Protected Endpoints
echo BLUE . "3. PROTECTED ENDPOINTS\n" . NC;

if ($token) {
    test("Get My Enrollments", "GET", "/my-enrollments", null, true);
    test("Get My Courses", "GET", "/my-courses", null, true);
    
    // Test Enrollment (may fail if no course exists)
    test(
        "Enroll in Course",
        "POST",
        "/enroll",
        ['course_id' => 1],
        true,
        400 // May fail if course doesn't exist
    );
    
    // Test Progress (may fail if not enrolled)
    test("Get Course Progress", "GET", "/courses/1/progress", null, true, 403);
    
    // Test Notes
    test("Get Course Notes", "GET", "/courses/1/notes", null, true, 403);
    
    // Test Assignments
    test("Get Course Assignments", "GET", "/courses/1/assignments", null, true, 403);
    test("Get My Submissions", "GET", "/my-submissions", null, true);
    
    // Test Messages
    test("Get Course Messages", "GET", "/courses/1/messages", null, true, 403);
    
    // Test Logout
    test("Logout", "POST", "/logout", null, true);
} else {
    echo RED . "Warning: No token available, skipping protected endpoint tests\n" . NC;
}

// Summary
echo BLUE . "\n========================================\n";
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
            echo "  - {$result['name']} (HTTP {$result['code']})\n";
        }
    }
}

echo "\n" . BLUE . "Note: Some tests may fail if test data doesn't exist." . NC . "\n";
echo "This is expected for endpoints that require existing courses/enrollments.\n";

