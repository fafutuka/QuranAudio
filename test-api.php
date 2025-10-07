<?php

/**
 * Simple API Test Script
 * Run this to test the Quran Audio API endpoints
 */

$baseUrl = 'http://localhost:8080';

echo "=== Quran Audio API Test Suite ===\n\n";

// Test 1: Root endpoint
echo "Test 1: Root Endpoint\n";
echo "URL: $baseUrl/\n";
$response = @file_get_contents($baseUrl . '/');
if ($response) {
    $data = json_decode($response, true);
    echo "✓ Success: " . $data['message'] . "\n";
    echo "  Version: " . $data['version'] . "\n\n";
} else {
    echo "✗ Failed: Could not connect to API\n";
    echo "  Make sure the server is running: php -S localhost:8080 -t public\n\n";
    exit(1);
}

// Test 2: List Chapter Reciters
echo "Test 2: List Chapter Reciters\n";
echo "URL: $baseUrl/chapter-reciters\n";
$response = @file_get_contents($baseUrl . '/chapter-reciters');
if ($response) {
    $data = json_decode($response, true);
    echo "✓ Success: Found " . count($data['reciters']) . " reciters\n";
    if (!empty($data['reciters'])) {
        echo "  First reciter: " . $data['reciters'][0]['name'] . "\n\n";
    }
} else {
    echo "✗ Failed\n\n";
}

// Test 3: List Recitations
echo "Test 3: List Recitations\n";
echo "URL: $baseUrl/recitations\n";
$response = @file_get_contents($baseUrl . '/recitations');
if ($response) {
    $data = json_decode($response, true);
    echo "✓ Success: Found " . count($data['recitations']) . " recitations\n";
    if (!empty($data['recitations'])) {
        echo "  First recitation: " . $data['recitations'][0]['reciter_name'] . " (" . $data['recitations'][0]['style'] . ")\n\n";
    }
} else {
    echo "✗ Failed\n\n";
}

// Test 4: Get Chapter Audio
echo "Test 4: Get Chapter Audio (Reciter 1, Chapter 1)\n";
echo "URL: $baseUrl/reciters/1/chapters/1\n";
$response = @file_get_contents($baseUrl . '/reciters/1/chapters/1');
if ($response) {
    $data = json_decode($response, true);
    echo "✓ Success: Audio file retrieved\n";
    if (isset($data['audio_file'])) {
        echo "  Chapter ID: " . $data['audio_file']['chapter_id'] . "\n";
        echo "  Duration: " . $data['audio_file']['duration'] . "ms\n";
        echo "  Timestamps: " . count($data['audio_file']['timestamps']) . " verses\n\n";
    }
} else {
    echo "✗ Failed\n\n";
}

// Test 5: Get Chapter Audio with Segments
echo "Test 5: Get Chapter Audio with Segments\n";
echo "URL: $baseUrl/reciters/1/chapters/1?segments=true\n";
$response = @file_get_contents($baseUrl . '/reciters/1/chapters/1?segments=true');
if ($response) {
    $data = json_decode($response, true);
    echo "✓ Success: Audio file with segments retrieved\n";
    if (isset($data['audio_file']['timestamps'][0]['segments'])) {
        echo "  First verse has " . count($data['audio_file']['timestamps'][0]['segments']) . " word segments\n\n";
    }
} else {
    echo "✗ Failed\n\n";
}

// Test 6: Get Reciter Audio Files
echo "Test 6: Get All Audio Files for Reciter 1\n";
echo "URL: $baseUrl/reciters/1/audio-files\n";
$response = @file_get_contents($baseUrl . '/reciters/1/audio-files');
if ($response) {
    $data = json_decode($response, true);
    echo "✓ Success: Found " . count($data['audio_files']) . " audio files\n\n";
} else {
    echo "✗ Failed\n\n";
}

// Test 7: Get Surah Ayah Recitations
echo "Test 7: Get Surah Ayah Recitations (Recitation 1, Chapter 1)\n";
echo "URL: $baseUrl/resources/recitations/1/1?page=1&per_page=5\n";
$response = @file_get_contents($baseUrl . '/resources/recitations/1/1?page=1&per_page=5');
if ($response) {
    $data = json_decode($response, true);
    echo "✓ Success: Retrieved ayah recitations\n";
    if (isset($data['pagination'])) {
        echo "  Total records: " . $data['pagination']['total_records'] . "\n";
        echo "  Current page: " . $data['pagination']['current_page'] . "\n";
        echo "  Per page: " . $data['pagination']['per_page'] . "\n\n";
    }
} else {
    echo "✗ Failed\n\n";
}

// Test 8: Get Juz Ayah Recitations
echo "Test 8: Get Juz Ayah Recitations (Recitation 1, Juz 1)\n";
echo "URL: $baseUrl/resources/recitations/1/juz/1?page=1&per_page=10\n";
$response = @file_get_contents($baseUrl . '/resources/recitations/1/juz/1?page=1&per_page=10');
if ($response) {
    $data = json_decode($response, true);
    echo "✓ Success: Retrieved juz recitations\n";
    if (isset($data['pagination'])) {
        echo "  Total records: " . $data['pagination']['total_records'] . "\n\n";
    }
} else {
    echo "✗ Failed\n\n";
}

// Test 9: Get Ayah Recitation
echo "Test 9: Get Specific Ayah Recitation (1:1)\n";
echo "URL: $baseUrl/resources/ayah-recitation/1/1:1\n";
$response = @file_get_contents($baseUrl . '/resources/ayah-recitation/1/1:1');
if ($response) {
    $data = json_decode($response, true);
    echo "✓ Success: Retrieved ayah recitation\n";
    if (!empty($data['audio_files'])) {
        echo "  Verse key: " . $data['audio_files'][0]['verse_key'] . "\n\n";
    }
} else {
    echo "✗ Failed\n\n";
}

// Test 10: Get Recitation Audio Files with Filter
echo "Test 10: Get Recitation Audio Files (Filtered by Chapter 1)\n";
echo "URL: $baseUrl/recitation-audio-files/1?chapter_number=1\n";
$response = @file_get_contents($baseUrl . '/recitation-audio-files/1?chapter_number=1');
if ($response) {
    $data = json_decode($response, true);
    echo "✓ Success: Retrieved filtered audio files\n";
    echo "  Files found: " . count($data['audio_files']) . "\n\n";
} else {
    echo "✗ Failed\n\n";
}

echo "=== Test Suite Complete ===\n";
echo "\nTo start the server, run:\n";
echo "  php -S localhost:8080 -t public\n\n";
echo "Or use Composer:\n";
echo "  composer start\n\n";
