<?php

require_once 'vendor/autoload.php';

// Test the new chapter tafseer endpoint
function testChapterTafseerEndpoint() {
    $baseUrl = 'http://localhost/QuranAudio'; // Adjust based on your setup
    
    echo "Testing Chapter Tafseer Endpoint\n";
    echo "================================\n\n";
    
    // Test 1: Get all tafseer audio for Chapter 1 (Al-Fatiha)
    echo "Test 1: Get all tafseer audio for Chapter 1\n";
    $url = "$baseUrl/audio-tafseers/chapters/1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "URL: $url\n";
    echo "HTTP Code: $httpCode\n";
    echo "Response: " . json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 2: Get tafseer audio for Chapter 1 with pagination
    echo "Test 2: Get tafseer audio for Chapter 1 with pagination (per_page=2)\n";
    $url = "$baseUrl/audio-tafseers/chapters/1?per_page=2&page=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "URL: $url\n";
    echo "HTTP Code: $httpCode\n";
    echo "Response: " . json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 3: Get tafseer audio for Chapter 1 with specific tafseer IDs
    echo "Test 3: Get tafseer audio for Chapter 1 with specific tafseer IDs (1,2)\n";
    $url = "$baseUrl/audio-tafseers/chapters/1?tafseer_ids=1,2";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "URL: $url\n";
    echo "HTTP Code: $httpCode\n";
    echo "Response: " . json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 4: Test invalid chapter number
    echo "Test 4: Test invalid chapter number (115)\n";
    $url = "$baseUrl/audio-tafseers/chapters/115";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "URL: $url\n";
    echo "HTTP Code: $httpCode\n";
    echo "Response: " . json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 5: Get tafseer audio for Chapter 2 (Al-Baqarah)
    echo "Test 5: Get all tafseer audio for Chapter 2\n";
    $url = "$baseUrl/audio-tafseers/chapters/2";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "URL: $url\n";
    echo "HTTP Code: $httpCode\n";
    echo "Response: " . json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "\n\n";
}

// Run the test
testChapterTafseerEndpoint();

echo "Test completed!\n";