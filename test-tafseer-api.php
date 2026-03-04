<?php

// Test script for Tafseer API endpoints
// Run this file to test the Tafseer module functionality

require_once 'vendor/autoload.php';

// Configuration
$baseUrl = 'http://localhost/QuranAudio'; // Adjust this to your API base URL
$apiUrl = $baseUrl . '/api';

function makeRequest($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data && ($method === 'POST' || $method === 'PUT')) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ]);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status_code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

echo "=== Tafseer API Test Script ===\n\n";

// Test 1: Get all mufassers
echo "1. Testing GET /mufassers\n";
$response = makeRequest("$apiUrl/mufassers");
echo "Status: " . $response['status_code'] . "\n";
echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Get specific mufasser
echo "2. Testing GET /mufassers/1\n";
$response = makeRequest("$apiUrl/mufassers/1");
echo "Status: " . $response['status_code'] . "\n";
echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n\n";

// Test 3: Get mufasser's tafseers
echo "3. Testing GET /mufassers/1/tafseers\n";
$response = makeRequest("$apiUrl/mufassers/1/tafseers");
echo "Status: " . $response['status_code'] . "\n";
echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n\n";

// Test 4: Get all tafseers
echo "4. Testing GET /tafseers\n";
$response = makeRequest("$apiUrl/tafseers");
echo "Status: " . $response['status_code'] . "\n";
echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n\n";

// Test 5: Get specific tafseer
echo "5. Testing GET /tafseers/1\n";
$response = makeRequest("$apiUrl/tafseers/1");
echo "Status: " . $response['status_code'] . "\n";
echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n\n";

// Test 6: Get tafseer audio files
echo "6. Testing GET /tafseers/1/audio-files\n";
$response = makeRequest("$apiUrl/tafseers/1/audio-files");
echo "Status: " . $response['status_code'] . "\n";
echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n\n";

// Test 7: Get audio tafseer by ID
echo "7. Testing GET /audio-tafseers/1\n";
$response = makeRequest("$apiUrl/audio-tafseers/1");
echo "Status: " . $response['status_code'] . "\n";
echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n\n";

// Test 8: Get audio tafseer by verse range
echo "8. Testing GET /audio-tafseers/verses/1:1/1:7\n";
$response = makeRequest("$apiUrl/audio-tafseers/verses/1:1/1:7");
echo "Status: " . $response['status_code'] . "\n";
echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n\n";

// Test 9: Get tafseer by verse range with segments
echo "9. Testing GET /tafseers/verses/1:1/1:7?segments=true\n";
$response = makeRequest("$apiUrl/tafseers/verses/1:1/1:7?segments=true");
echo "Status: " . $response['status_code'] . "\n";
echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n\n";

// Test 10: Get audio tafseer with specific tafseer IDs
echo "10. Testing GET /audio-tafseers/verses/1:1/1:7?tafseer_ids=1,2&segments=true\n";
$response = makeRequest("$apiUrl/audio-tafseers/verses/1:1/1:7?tafseer_ids=1,2&segments=true");
echo "Status: " . $response['status_code'] . "\n";
echo "Response: " . json_encode($response['body'], JSON_PRETTY_PRINT) . "\n\n";

echo "=== Test completed ===\n";

?>