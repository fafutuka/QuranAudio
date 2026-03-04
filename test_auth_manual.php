<?php

/**
 * Manual Verification Script for JWT Authentication
 * 
 * To run this script:
 * 1. Ensure the server is running (php -S localhost:8080 -t public)
 * 2. Run: php test_auth_manual.php
 */

$baseUrl = 'http://localhost:8080';

// Change these to test
$testEmail = 'admin@example.com';
$testPassword = 'password123';
$testName = 'Admin User';

function makeRequest($method, $url, $data = null, $token = null)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'status' => $status,
        'body' => json_decode($response, true)
    ];
}

echo "Testing JWT Authentication Flow...\n\n";

// 1. Register
echo "1. Testing Registration...\n";
$regData = [
    'name' => $testName,
    'email' => $testEmail,
    'password' => $testPassword,
    'role' => 'admin'
];
$regResponse = makeRequest('POST', "$baseUrl/auth/register", $regData);
echo "Status: " . $regResponse['status'] . "\n";
print_r($regResponse['body']);
echo "\n";

// 2. Login
echo "2. Testing Login...\n";
$loginData = [
    'email' => $testEmail,
    'password' => $testPassword
];
$loginResponse = makeRequest('POST', "$baseUrl/auth/login", $loginData);
echo "Status: " . $loginResponse['status'] . "\n";
print_r($loginResponse['body']);
echo "\n";

if ($loginResponse['status'] !== 200 || !isset($loginResponse['body']['token'])) {
    echo "Login failed. Stopping test.\n";
    exit;
}

$token = $loginResponse['body']['token'];

// 3. Test Protected Route
echo "3. Testing Protected Route...\n";
$protResponse = makeRequest('GET', "$baseUrl/auth/test-protected", null, $token);
echo "Status: " . $protResponse['status'] . "\n";
print_r($protResponse['body']);
echo "\n";

// 4. Test Admin Route
echo "4. Testing Admin Route...\n";
$adminResponse = makeRequest('GET', "$baseUrl/auth/test-admin", null, $token);
echo "Status: " . $adminResponse['status'] . "\n";
print_r($adminResponse['body']);
echo "\n";

// 5. Test Superadmin Route (Should fail for admin)
echo "5. Testing Superadmin Route (Expected 403 Forbidden for Admin)...\n";
$saResponse = makeRequest('GET', "$baseUrl/auth/test-superadmin", null, $token);
echo "Status: " . $saResponse['status'] . "\n";
print_r($saResponse['body']);
echo "\n";

echo "Verification completed.\n";
