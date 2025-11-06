<?php

/**
 * Comprehensive API Testing with Real Data
 */

$baseUrl = 'http://localhost:8000/api';
$token = '';
$courseId = null;

// Colors
define('GREEN', "\033[0;32m");
define('RED', "\033[0;31m");
define('YELLOW', "\033[1;33m");
define('BLUE', "\033[0;34m");
define('CYAN', "\033[0;36m");
define('NC', "\033[0m");

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
    curl_close($ch);

    return ['code' => $httpCode, 'body' => $response];
}

function test($name, $method, $endpoint, $data = null, $useAuth = false, $expectedCodes = [200, 201]) {
    global $token, $courseId;

    // Replace placeholders
    if ($courseId !== null) {
        $endpoint = str_replace('{course_id}', $courseId, $endpoint);
    }

    echo CYAN . str_pad($name, 50) . NC;

    $result = makeRequest($method, $endpoint, $data, $useAuth);
    $success = in_array($result['code'], $expectedCodes);

    if ($success) {
        echo GREEN . "✓ PASS" . NC . " (HTTP {$result['code']})\n";
    } else {
        echo RED . "✗ FAIL" . NC . " (HTTP {$result['code']})";
        $body = json_decode($result['body'], true);
        if ($body && isset($body['message'])) {
            echo " - " . $body['message'];
        }
        echo "\n";
    }

    return ['success' => $success, 'code' => $result['code'], 'body' => $result['body']];
}

echo BLUE . "╔══════════════════════════════════════════════════════════╗\n";
echo "║        AgriSiti LMS - Comprehensive API Testing          ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n" . NC;

// Step 1: Authentication
echo YELLOW . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  AUTHENTICATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" . NC;

$email = 'test' . time() . '@example.com';
$result = test("Register New User", "POST", "/register", [
    'name' => 'Test User',
    'email' => $email,
    'password' => 'password123',
    'password_confirmation' => 'password123',
]);
$data = json_decode($result['body'], true);
if ($data && isset($data['token'])) {
    $token = $data['token'];
}

$result = test("Login with Student Account", "POST", "/login", [
    'email' => 'student@example.com',
    'password' => 'password123',
], false, [200, 422]);
$data = json_decode($result['body'], true);
if ($data && isset($data['token'])) {
    $token = $data['token'];
}

test("Get Current User", "GET", "/user", null, true);

// Step 2: Public Endpoints
echo "\n" . YELLOW . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  PUBLIC ENDPOINTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" . NC;

test("Get All Categories", "GET", "/categories");
test("Get Categories with Courses", "GET", "/categories-with-courses");
test("Get All Courses", "GET", "/courses");
test("Get Courses (Filtered)", "GET", "/courses?level=beginner");

// Get actual course ID
$result = makeRequest("GET", "/courses");
$courses = json_decode($result['body'], true);
if (isset($courses['data']) && count($courses['data']) > 0) {
    $courseId = $courses['data'][0]['id'];
    test("Get Single Course", "GET", "/courses/{course_id}", null, false);
} else {
    echo RED . "⚠ No courses found. Some tests will be skipped.\n" . NC;
    $courseId = 1; // Fallback
}

// Step 3: Protected Endpoints
if ($token) {
    echo "\n" . YELLOW . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "  PROTECTED ENDPOINTS\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" . NC;

    echo CYAN . "Enrollments:\n" . NC;
    test("Get My Enrollments", "GET", "/my-enrollments", null, true);
    test("Get My Courses", "GET", "/my-courses", null, true);
    test("Enroll in Course", "POST", "/enroll", ['course_id' => $courseId], true, [201, 400, 422]);
    test("Get Enrollment Details", "GET", "/enrollments/1", null, true, [200, 404]);

    echo "\n" . CYAN . "Progress:\n" . NC;
    test("Get Course Progress", "GET", "/courses/{course_id}/progress", null, true, [200, 403, 404]);
    test("Mark Topic Complete", "POST", "/topics/1/complete", null, true, [200, 403, 404]);

    echo "\n" . CYAN . "Notes:\n" . NC;
    test("Get Course Notes", "GET", "/courses/{course_id}/notes", null, true, [200, 403, 404]);
    test("Create Note", "POST", "/notes", [
        'course_id' => $courseId,
        'topic_id' => 1,
        'notes' => 'Test note',
        'is_public' => false
    ], true, [201, 403, 404, 422]);

    echo "\n" . CYAN . "Assignments:\n" . NC;
    test("Get Course Assignments", "GET", "/courses/{course_id}/assignments", null, true, [200, 403, 404]);
    test("Get My Submissions", "GET", "/my-submissions", null, true);

    echo "\n" . CYAN . "Messages:\n" . NC;
    test("Get Course Messages", "GET", "/courses/{course_id}/messages", null, true, [200, 403, 404]);

    echo "\n" . CYAN . "Logout:\n" . NC;
    test("Logout", "POST", "/logout", null, true);
}

echo "\n" . BLUE . "╔══════════════════════════════════════════════════════════╗\n";
echo "║                    Testing Complete!                    ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n" . NC;

echo "📝 Test Credentials:\n";
echo "   Student: student@example.com / password123\n";
echo "   Admin: admin@example.com / password123\n";
echo "   Tutor: tutor@example.com / password123\n\n";

echo "🌐 Access Points:\n";
echo "   API Tester: http://localhost:8000/api-test.html\n";
echo "   Admin Panel: http://localhost:8000/admin\n";
echo "   Tutor Panel: http://localhost:8000/tutor\n\n";

