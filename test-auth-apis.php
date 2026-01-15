<?php

/**
 * Test script for Registration and Login APIs
 * Run: php test-auth-apis.php
 */

$baseUrl = 'http://localhost:8000/api';

// Generate unique email for testing
$timestamp = time();
$testEmail = "testuser{$timestamp}@example.com";
$testPassword = "TestPassword123!";
$testName = "Test User {$timestamp}";

echo "========================================\n";
echo "Testing Registration and Login APIs\n";
echo "========================================\n\n";

// Test 1: Registration
echo "1. Testing Registration API\n";
echo "   POST {$baseUrl}/register\n";
echo "   Email: {$testEmail}\n";
echo "   Name: {$testName}\n\n";

$registerData = [
    'name' => $testName,
    'email' => $testEmail,
    'password' => $testPassword,
    'password_confirmation' => $testPassword,
    'phone' => '+1234567890'
];

$ch = curl_init("{$baseUrl}/register");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($registerData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$registerResponse = curl_exec($ch);
$registerHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Response Code: {$registerHttpCode}\n";
$registerResult = json_decode($registerResponse, true);

if ($registerHttpCode === 201 && isset($registerResult['data']['token'])) {
    echo "   ✅ Registration SUCCESSFUL!\n";
    echo "   User ID: {$registerResult['data']['user']['id']}\n";
    echo "   Token: " . substr($registerResult['data']['token'], 0, 20) . "...\n\n";
    
    $token = $registerResult['data']['token'];
} else {
    echo "   ❌ Registration FAILED!\n";
    echo "   Response: " . json_encode($registerResult, JSON_PRETTY_PRINT) . "\n\n";
    exit(1);
}

// Test 2: Login with registered user
echo "2. Testing Login API\n";
echo "   POST {$baseUrl}/login\n";
echo "   Email: {$testEmail}\n\n";

$loginData = [
    'email' => $testEmail,
    'password' => $testPassword
];

$ch = curl_init("{$baseUrl}/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Response Code: {$loginHttpCode}\n";
$loginResult = json_decode($loginResponse, true);

if ($loginHttpCode === 200 && isset($loginResult['data']['token'])) {
    echo "   ✅ Login SUCCESSFUL!\n";
    echo "   User ID: {$loginResult['data']['user']['id']}\n";
    echo "   User Name: {$loginResult['data']['user']['name']}\n";
    echo "   User Email: {$loginResult['data']['user']['email']}\n";
    echo "   User Role: {$loginResult['data']['user']['role']}\n";
    echo "   Token: " . substr($loginResult['data']['token'], 0, 20) . "...\n\n";
    
    $loginToken = $loginResult['data']['token'];
} else {
    echo "   ❌ Login FAILED!\n";
    echo "   Response: " . json_encode($loginResult, JSON_PRETTY_PRINT) . "\n\n";
    exit(1);
}

// Test 3: Test invalid credentials
echo "3. Testing Login with Invalid Credentials\n";
echo "   POST {$baseUrl}/login\n";
echo "   Email: {$testEmail}\n";
echo "   Password: wrongpassword\n\n";

$invalidLoginData = [
    'email' => $testEmail,
    'password' => 'wrongpassword'
];

$ch = curl_init("{$baseUrl}/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($invalidLoginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$invalidLoginResponse = curl_exec($ch);
$invalidLoginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Response Code: {$invalidLoginHttpCode}\n";
$invalidLoginResult = json_decode($invalidLoginResponse, true);

if ($invalidLoginHttpCode === 401 && isset($invalidLoginResult['success']) && $invalidLoginResult['success'] === false) {
    echo "   ✅ Invalid credentials correctly rejected!\n";
    echo "   Message: {$invalidLoginResult['message']}\n\n";
} else {
    echo "   ⚠️ Unexpected response for invalid credentials\n";
    echo "   Response: " . json_encode($invalidLoginResult, JSON_PRETTY_PRINT) . "\n\n";
}

// Test 4: Test duplicate registration
echo "4. Testing Duplicate Registration (should fail)\n";
echo "   POST {$baseUrl}/register\n";
echo "   Email: {$testEmail} (already registered)\n\n";

$ch = curl_init("{$baseUrl}/register");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($registerData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$duplicateResponse = curl_exec($ch);
$duplicateHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Response Code: {$duplicateHttpCode}\n";
$duplicateResult = json_decode($duplicateResponse, true);

if ($duplicateHttpCode === 422 && isset($duplicateResult['errors']['email'])) {
    echo "   ✅ Duplicate registration correctly rejected!\n";
    echo "   Error: " . $duplicateResult['errors']['email'][0] . "\n\n";
} else {
    echo "   ⚠️ Unexpected response for duplicate registration\n";
    echo "   Response: " . json_encode($duplicateResult, JSON_PRETTY_PRINT) . "\n\n";
}

// Test 5: Test Get Current User (with token)
echo "5. Testing Get Current User (Protected Endpoint)\n";
echo "   GET {$baseUrl}/user\n";
echo "   Authorization: Bearer {$loginToken}\n\n";

$ch = curl_init("{$baseUrl}/user");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $loginToken
]);

$userResponse = curl_exec($ch);
$userHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Response Code: {$userHttpCode}\n";
$userResult = json_decode($userResponse, true);

if ($userHttpCode === 200 && isset($userResult['id'])) {
    echo "   ✅ Get Current User SUCCESSFUL!\n";
    echo "   User ID: {$userResult['id']}\n";
    echo "   User Name: {$userResult['name']}\n";
    echo "   User Email: {$userResult['email']}\n";
    echo "   User Role: {$userResult['role']}\n\n";
} else {
    echo "   ❌ Get Current User FAILED!\n";
    echo "   Response: " . json_encode($userResult, JSON_PRETTY_PRINT) . "\n\n";
}

echo "========================================\n";
echo "All Tests Completed!\n";
echo "========================================\n";

