<?php

/**
 * Comprehensive API Testing Script
 * Tests all API endpoints locally
 */

$baseUrl = 'http://127.0.0.1:8000/api';
$testResults = [];
$errors = [];

// Colors for terminal output
$green = "\033[32m";
$red = "\033[31m";
$yellow = "\033[33m";
$blue = "\033[34m";
$reset = "\033[0m";

function makeRequest($method, $url, $data = null, $token = null) {
    $ch = curl_init();
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
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

function testEndpoint($name, $method, $url, $data = null, $token = null, $expectedCode = 200) {
    global $testResults, $errors, $green, $red, $yellow, $reset;
    
    echo "Testing: {$name}... ";
    
    $result = makeRequest($method, $url, $data, $token);
    
    $success = ($result['code'] === $expectedCode || ($expectedCode === 'any' && $result['code'] < 500));
    
    if ($success) {
        echo "{$green}✓{$reset}\n";
        $testResults[] = ['name' => $name, 'status' => 'PASS', 'code' => $result['code']];
    } else {
        echo "{$red}✗{$reset} (HTTP {$result['code']})\n";
        if ($result['error']) {
            echo "  Error: {$result['error']}\n";
        }
        $testResults[] = ['name' => $name, 'status' => 'FAIL', 'code' => $result['code'], 'error' => $result['error']];
        $errors[] = $name;
    }
    
    return $result;
}

echo "{$blue}=== Comprehensive API Testing ==={$reset}\n\n";

// Step 1: Test Public Endpoints
echo "{$yellow}1. Testing Public Endpoints{$reset}\n";
echo str_repeat("-", 50) . "\n";

testEndpoint("Register User", "POST", "{$baseUrl}/register", [
    'name' => 'Test User ' . time(),
    'email' => 'test' . time() . '@example.com',
    'password' => 'TestPassword123!',
    'password_confirmation' => 'TestPassword123!',
    'phone' => '+1234567890',
], null, 201);

$testEmail = 'testuser' . time() . '@example.com';
$testPassword = 'TestPassword123!';

$registerResult = makeRequest("POST", "{$baseUrl}/register", [
    'name' => 'Test User',
    'email' => $testEmail,
    'password' => $testPassword,
    'password_confirmation' => $testPassword,
]);

$token = null;
if ($registerResult['code'] === 201) {
    $registerData = json_decode($registerResult['body'], true);
    $token = $registerData['data']['token'] ?? null;
    echo "  Registered user: {$testEmail}\n";
    echo "  Token obtained: " . ($token ? "Yes" : "No") . "\n\n";
} else {
    // Try login instead
    echo "  Registration failed, trying login...\n";
    $loginResult = makeRequest("POST", "{$baseUrl}/login", [
        'email' => 'student@example.com', // Assuming test user exists
        'password' => 'password',
    ]);
    
    if ($loginResult['code'] === 200) {
        $loginData = json_decode($loginResult['body'], true);
        $token = $loginData['data']['token'] ?? null;
        echo "  Logged in successfully\n";
        echo "  Token obtained: " . ($token ? "Yes" : "No") . "\n\n";
    } else {
        echo "  {$red}Could not authenticate. Some tests will be skipped.{$reset}\n\n";
    }
}

// Public endpoints
testEndpoint("Get Categories", "GET", "{$baseUrl}/categories");
testEndpoint("Get Categories (with search)", "GET", "{$baseUrl}/categories?search=agriculture");
testEndpoint("Get Courses", "GET", "{$baseUrl}/courses");
testEndpoint("Get Courses (with filters)", "GET", "{$baseUrl}/courses?level=beginner&per_page=5");
testEndpoint("Get Courses (with search)", "GET", "{$baseUrl}/courses?search=test&per_page=5");

// Step 2: Test Authenticated Endpoints
if ($token) {
    echo "\n{$yellow}2. Testing Authenticated Endpoints{$reset}\n";
    echo str_repeat("-", 50) . "\n";
    
    // Auth endpoints
    testEndpoint("Get Current User", "GET", "{$baseUrl}/user", null, $token);
    testEndpoint("Get User Profile with Stats", "GET", "{$baseUrl}/user", null, $token);
    testEndpoint("Get User Certificates", "GET", "{$baseUrl}/user/certificates", null, $token);
    
    // Course endpoints
    testEndpoint("Daily Recommended Courses", "GET", "{$baseUrl}/daily-recommended-courses", null, $token);
    testEndpoint("Latest Courses", "GET", "{$baseUrl}/latest-courses", null, $token);
    testEndpoint("Featured Courses", "GET", "{$baseUrl}/featured-courses", null, $token);
    testEndpoint("Recommended Courses", "GET", "{$baseUrl}/recommended-courses", null, $token);
    
    // Enrollment endpoints
    testEndpoint("My Enrollments", "GET", "{$baseUrl}/my-enrollments", null, $token);
    testEndpoint("My Courses", "GET", "{$baseUrl}/my-courses", null, $token);
    testEndpoint("My Ongoing Courses", "GET", "{$baseUrl}/my-ongoing-courses", null, $token);
    testEndpoint("Ongoing Courses", "GET", "{$baseUrl}/ongoing-courses", null, $token);
    testEndpoint("Completed Courses", "GET", "{$baseUrl}/completed-courses", null, $token);
    testEndpoint("Saved Courses List", "GET", "{$baseUrl}/saved-courses-list", null, $token);
    testEndpoint("Certified Courses", "GET", "{$baseUrl}/certified-courses", null, $token);
    
    // Get a course ID for testing (if courses exist)
    $coursesResult = makeRequest("GET", "{$baseUrl}/courses?per_page=1");
    $courseId = null;
    if ($coursesResult['code'] === 200) {
        $coursesData = json_decode($coursesResult['body'], true);
        if (!empty($coursesData['data'])) {
            $courseId = $coursesData['data'][0]['id'] ?? null;
        }
    }
    
    if ($courseId) {
        echo "\n  Testing course-specific endpoints (Course ID: {$courseId})...\n";
        testEndpoint("Get Course Details", "GET", "{$baseUrl}/courses/{$courseId}", null, $token);
        testEndpoint("Get Course Information", "GET", "{$baseUrl}/courses/{$courseId}/information", null, $token);
        testEndpoint("Get Course Modules", "GET", "{$baseUrl}/courses/{$courseId}/modules", null, $token);
        testEndpoint("Get Course Completion", "GET", "{$baseUrl}/courses/{$courseId}/completion", null, $token, 'any');
        testEndpoint("Get Course Reviews", "GET", "{$baseUrl}/courses/{$courseId}/reviews", null, $token);
        testEndpoint("Get Course Curriculum", "GET", "{$baseUrl}/courses/{$courseId}/curriculum", null, $token, 'any');
        testEndpoint("Get Course Comments", "GET", "{$baseUrl}/courses/{$courseId}/comments", null, $token);
        testEndpoint("Get Course Notes", "GET", "{$baseUrl}/courses/{$courseId}/notes", null, $token, 'any');
        
        // Category courses
        $categoriesResult = makeRequest("GET", "{$baseUrl}/categories");
        if ($categoriesResult['code'] === 200) {
            $categoriesData = json_decode($categoriesResult['body'], true);
            if (!empty($categoriesData['data'])) {
                $categoryId = $categoriesData['data'][0]['id'] ?? null;
                if ($categoryId) {
                    echo "\n  Testing category endpoints (Category ID: {$categoryId})...\n";
                    testEndpoint("Get Category Details", "GET", "{$baseUrl}/categories/{$categoryId}", null, null);
                    testEndpoint("Get Category Courses", "GET", "{$baseUrl}/categories/{$categoryId}/courses", null, $token);
                    testEndpoint("Get Category Courses (with search)", "GET", "{$baseUrl}/categories/{$categoryId}/courses?search=test", null, $token);
                }
            }
        }
    }
    
    // Saved courses
    if ($courseId) {
        testEndpoint("Save Course", "POST", "{$baseUrl}/courses/{$courseId}/save", null, $token, 'any');
        testEndpoint("Get Saved Courses", "GET", "{$baseUrl}/saved-courses", null, $token);
        testEndpoint("Unsave Course", "DELETE", "{$baseUrl}/courses/{$courseId}/unsave", null, $token, 'any');
    }
    
    // Step 3: Test Create/Update/Delete Operations
    echo "\n{$yellow}3. Testing Create/Update/Delete Operations{$reset}\n";
    echo str_repeat("-", 50) . "\n";
    
    // Update Profile
    testEndpoint("Update User Profile", "PUT", "{$baseUrl}/user/profile", [
        'name' => 'Updated Test User',
        'bio' => 'Updated bio for testing',
    ], $token);
    
    // Change Password (will fail if we don't have current password, but tests the endpoint)
    testEndpoint("Change Password (Invalid)", "PUT", "{$baseUrl}/user/password", [
        'current_password' => 'wrongpassword',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ], $token, 400);
    
    // Logout
    testEndpoint("Logout", "POST", "{$baseUrl}/logout", null, $token);
    
    // Re-login to continue testing
    $loginResult = makeRequest("POST", "{$baseUrl}/login", [
        'email' => $testEmail,
        'password' => $testPassword,
    ]);
    if ($loginResult['code'] === 200) {
        $loginData = json_decode($loginResult['body'], true);
        $token = $loginData['data']['token'] ?? $token;
        echo "  Re-authenticated for further tests\n";
    }
    
    // Get course, module, and topic IDs for testing
    $coursesResult = makeRequest("GET", "{$baseUrl}/courses?per_page=1", null, $token);
    $courseId = null;
    $moduleId = null;
    $topicId = null;
    
    if ($coursesResult['code'] === 200) {
        $coursesData = json_decode($coursesResult['body'], true);
        if (!empty($coursesData['data'])) {
            $courseId = $coursesData['data'][0]['id'] ?? null;
            
            if ($courseId) {
                // Get modules
                $modulesResult = makeRequest("GET", "{$baseUrl}/courses/{$courseId}/modules", null, $token);
                if ($modulesResult['code'] === 200) {
                    $modulesData = json_decode($modulesResult['body'], true);
                    if (!empty($modulesData['data']['modules'])) {
                        $moduleId = $modulesData['data']['modules'][0]['id'] ?? null;
                        
                        // Get topics from module
                        if ($moduleId && !empty($modulesData['data']['modules'][0]['topics'])) {
                            $topicId = $modulesData['data']['modules'][0]['topics'][0]['id'] ?? null;
                        }
                    }
                }
            }
        }
    }
    
    if ($courseId && $topicId) {
        echo "\n  Testing with Course ID: {$courseId}, Topic ID: {$topicId}...\n";
        
        // Create Note
        $createNoteResult = makeRequest("POST", "{$baseUrl}/notes", [
            'course_id' => $courseId,
            'topic_id' => $topicId,
            'notes' => 'This is a test note created during API testing',
            'timestamp_seconds' => 120,
            'is_public' => false,
        ], $token);
        
        $noteId = null;
        if ($createNoteResult['code'] === 201) {
            $noteData = json_decode($createNoteResult['body'], true);
            $noteId = $noteData['data']['id'] ?? null;
            echo "  ✓ Created note with ID: {$noteId}\n";
        }
        
        testEndpoint("Create Note", "POST", "{$baseUrl}/notes", [
            'course_id' => $courseId,
            'topic_id' => $topicId,
            'notes' => 'Test note for API testing',
        ], $token, 'any');
        
        // Update Note (if created)
        if ($noteId) {
            testEndpoint("Update Note", "PUT", "{$baseUrl}/notes/{$noteId}", [
                'notes' => 'Updated test note content',
                'is_public' => true,
            ], $token);
            
            // Delete Note
            testEndpoint("Delete Note", "DELETE", "{$baseUrl}/notes/{$noteId}", null, $token);
        }
        
        // Mark Topic Complete
        testEndpoint("Mark Topic Complete", "POST", "{$baseUrl}/topics/{$topicId}/complete", null, $token, 'any');
        
        // Add Lesson Comment
        $commentResult = makeRequest("POST", "{$baseUrl}/courses/{$courseId}/topics/{$topicId}/comments", [
            'comment' => 'This is a test comment on a lesson',
        ], $token);
        
        if ($commentResult['code'] === 201) {
            $commentData = json_decode($commentResult['body'], true);
            echo "  ✓ Created lesson comment\n";
        }
        
        testEndpoint("Add Lesson Comment", "POST", "{$baseUrl}/courses/{$courseId}/topics/{$topicId}/comments", [
            'comment' => 'Test lesson comment for API testing',
        ], $token, 'any');
        
        // Add Course Comment
        $courseCommentResult = makeRequest("POST", "{$baseUrl}/courses/{$courseId}/comments", [
            'comment' => 'This is a test comment on the course',
        ], $token);
        
        if ($courseCommentResult['code'] === 201) {
            $courseCommentData = json_decode($courseCommentResult['body'], true);
            echo "  ✓ Created course comment\n";
        }
        
        testEndpoint("Add Course Comment", "POST", "{$baseUrl}/courses/{$courseId}/comments", [
            'comment' => 'Test course comment for API testing',
        ], $token, 'any');
    } else {
        echo "\n  ⚠️  No course/topic available for create/update tests\n";
        echo "     (This is normal if no courses exist in the database)\n";
    }
    
    // Test Enrollment (if we have a course)
    if ($courseId) {
        // Try to enroll (may fail if already enrolled or no code, but tests endpoint)
        testEndpoint("Enroll in Course", "POST", "{$baseUrl}/enroll", [
            'course_id' => $courseId,
            'enrollment_code' => 'TESTCODE123',
        ], $token, 'any');
    }
    
} else {
    echo "\n{$yellow}2. Skipping Authenticated Endpoints (No token){$reset}\n";
    echo "\n{$yellow}3. Skipping Create/Update/Delete Operations (No token){$reset}\n";
}

// Step 4: Test Error Cases
echo "\n{$yellow}4. Testing Error Cases{$reset}\n";
echo str_repeat("-", 50) . "\n";

testEndpoint("Login with Invalid Credentials", "POST", "{$baseUrl}/login", [
    'email' => 'invalid@example.com',
    'password' => 'wrongpassword',
], null, 401);

testEndpoint("Get User without Auth", "GET", "{$baseUrl}/user", null, null, 401);

// Summary
echo "\n{$blue}=== Test Summary ==={$reset}\n";
echo str_repeat("-", 50) . "\n";

$passed = count(array_filter($testResults, fn($r) => $r['status'] === 'PASS'));
$failed = count(array_filter($testResults, fn($r) => $r['status'] === 'FAIL'));
$total = count($testResults);

echo "Total Tests: {$total}\n";
echo "{$green}Passed: {$passed}{$reset}\n";
echo "{$red}Failed: {$failed}{$reset}\n";

if ($failed > 0) {
    echo "\n{$red}Failed Tests:{$reset}\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

echo "\n";

if ($failed === 0) {
    echo "{$green}All tests passed!{$reset}\n";
    exit(0);
} else {
    echo "{$red}Some tests failed. Please check the errors above.{$reset}\n";
    exit(1);
}

