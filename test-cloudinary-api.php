<?php

/**
 * Cloudinary API Test Script
 * 
 * Tests all Cloudinary endpoints for the Tafseer module
 */

// Configuration
$baseUrl = 'http://localhost/QuranAudio/api';
$testToken = 'your_jwt_token_here'; // Replace with actual JWT token for protected endpoints

// Test data
$testAudioUrl = 'https://www.soundjay.com/misc/sounds/bell-ringing-05.wav'; // Sample audio URL
$testTafseerData = [
    'tafseer_id' => 1,
    'verse_range_from' => '1:1',
    'verse_range_to' => '1:7'
];

/**
 * Make HTTP request
 */
function makeRequest($url, $method = 'GET', $data = null, $token = null, $isMultipart = false) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = [];
    
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    
    if ($data) {
        if ($isMultipart) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status_code' => $httpCode,
        'body' => json_decode($response, true),
        'raw_body' => $response
    ];
}

/**
 * Print test result
 */
function printResult($testName, $response, $expectedStatus = 200) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "TEST: $testName\n";
    echo str_repeat("=", 60) . "\n";
    
    $status = $response['status_code'];
    $statusText = $status == $expectedStatus ? "✅ PASS" : "❌ FAIL";
    
    echo "Status: $status $statusText (Expected: $expectedStatus)\n";
    
    if ($response['body']) {
        echo "Response:\n";
        echo json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "Raw Response: " . $response['raw_body'] . "\n";
    }
}

echo "🚀 Starting Cloudinary API Tests\n";
echo "Base URL: $baseUrl\n";

// Test 1: Test Cloudinary Connection
$response = makeRequest("$baseUrl/cloudinary/test");
printResult("Test Cloudinary Connection", $response, 200);

// Test 2: Upload Tafseer Audio via URL (Protected)
$uploadData = array_merge($testTafseerData, [
    'file_url' => $testAudioUrl
]);
$response = makeRequest("$baseUrl/cloudinary/tafseer-audio", 'POST', $uploadData, $testToken);
printResult("Upload Tafseer Audio via URL", $response, 201);

$uploadedAudioId = null;
if ($response['status_code'] == 201 && isset($response['body']['audio_tafseer']['id'])) {
    $uploadedAudioId = $response['body']['audio_tafseer']['id'];
    echo "📝 Uploaded Audio ID: $uploadedAudioId\n";
}

// Test 3: Get Tafseer Audio with Quality URLs
if ($uploadedAudioId) {
    $response = makeRequest("$baseUrl/cloudinary/tafseer-audio/$uploadedAudioId?segments=true");
    printResult("Get Tafseer Audio with Quality URLs", $response, 200);
}

// Test 4: Get Usage Statistics (Protected)
$response = makeRequest("$baseUrl/cloudinary/stats", 'GET', null, $testToken);
printResult("Get Cloudinary Usage Statistics", $response, 200);

// Test 5: Batch Upload (Protected)
$batchData = [
    'uploads' => [
        [
            'file_path' => $testAudioUrl,
            'tafseer_data' => [
                'tafseer_id' => 2,
                'verse_range_from' => '2:1',
                'verse_range_to' => '2:5'
            ]
        ],
        [
            'file_path' => $testAudioUrl,
            'tafseer_data' => [
                'tafseer_id' => 3,
                'verse_range_from' => '3:1',
                'verse_range_to' => '3:10'
            ]
        ]
    ],
    'global_options' => [
        'quality' => 'medium_quality'
    ]
];
$response = makeRequest("$baseUrl/cloudinary/tafseer-audio/batch", 'POST', $batchData, $testToken);
printResult("Batch Upload Tafseer Audio", $response, 201);

// Test 6: Migrate Existing Audio to Cloudinary (Protected)
if ($uploadedAudioId) {
    $migrateData = [
        'upload_options' => [
            'quality' => 'high_quality'
        ]
    ];
    $response = makeRequest("$baseUrl/cloudinary/tafseer-audio/$uploadedAudioId/migrate", 'POST', $migrateData, $testToken);
    printResult("Migrate Existing Audio to Cloudinary", $response, 200);
}

// Test 7: Update Tafseer Audio (Protected)
if ($uploadedAudioId) {
    $updateData = [
        'file_url' => $testAudioUrl,
        'quality' => 'high_quality'
    ];
    $response = makeRequest("$baseUrl/cloudinary/tafseer-audio/$uploadedAudioId", 'PUT', $updateData, $testToken);
    printResult("Update Tafseer Audio", $response, 200);
}

// Test 8: Get Audio Info from Cloudinary (Protected)
if ($uploadedAudioId) {
    // First get the audio to find the public_id
    $audioResponse = makeRequest("$baseUrl/cloudinary/tafseer-audio/$uploadedAudioId");
    if ($audioResponse['status_code'] == 200 && isset($audioResponse['body']['audio_tafseer']['cloudinary_public_id'])) {
        $publicId = $audioResponse['body']['audio_tafseer']['cloudinary_public_id'];
        $encodedPublicId = urlencode($publicId);
        $response = makeRequest("$baseUrl/cloudinary/audio-info/$encodedPublicId", 'GET', null, $testToken);
        printResult("Get Audio Info from Cloudinary", $response, 200);
    }
}

// Test 9: Error Handling - Upload without required fields
$invalidData = [
    'file_url' => $testAudioUrl
    // Missing tafseer_id, verse_range_from, verse_range_to
];
$response = makeRequest("$baseUrl/cloudinary/tafseer-audio", 'POST', $invalidData, $testToken);
printResult("Error Handling - Missing Required Fields", $response, 400);

// Test 10: Error Handling - Upload without authentication
$response = makeRequest("$baseUrl/cloudinary/tafseer-audio", 'POST', $uploadData);
printResult("Error Handling - No Authentication", $response, 401);

// Test 11: Delete Tafseer Audio (Protected) - Should be last test
if ($uploadedAudioId) {
    $response = makeRequest("$baseUrl/cloudinary/tafseer-audio/$uploadedAudioId", 'DELETE', null, $testToken);
    printResult("Delete Tafseer Audio", $response, 200);
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🏁 All Cloudinary API tests completed!\n";
echo str_repeat("=", 60) . "\n";

echo "\n📋 Setup Instructions:\n";
echo "1. Install Cloudinary PHP SDK: composer install\n";
echo "2. Configure Cloudinary credentials in src/config/cloudinary.php\n";
echo "3. Run database migration: mysql -u username -p database < database/cloudinary_migration.sql\n";
echo "4. Update JWT token in this test script\n";
echo "5. Add Cloudinary routes to your main application\n";

echo "\n🔧 Environment Variables (Optional):\n";
echo "CLOUDINARY_CLOUD_NAME=your_cloud_name\n";
echo "CLOUDINARY_API_KEY=your_api_key\n";
echo "CLOUDINARY_API_SECRET=your_api_secret\n";

echo "\n📚 API Endpoints Summary:\n";
echo "GET    /cloudinary/test                           - Test connection\n";
echo "GET    /cloudinary/stats                          - Usage statistics (protected)\n";
echo "POST   /cloudinary/tafseer-audio                  - Upload audio (protected)\n";
echo "GET    /cloudinary/tafseer-audio/{id}             - Get audio with qualities\n";
echo "PUT    /cloudinary/tafseer-audio/{id}             - Update audio (protected)\n";
echo "DELETE /cloudinary/tafseer-audio/{id}             - Delete audio (protected)\n";
echo "POST   /cloudinary/tafseer-audio/batch            - Batch upload (protected)\n";
echo "POST   /cloudinary/tafseer-audio/{id}/migrate     - Migrate to Cloudinary (protected)\n";
echo "GET    /cloudinary/audio-info/{public_id}         - Get Cloudinary info (protected)\n";
?>