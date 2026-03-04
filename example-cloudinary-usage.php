<?php

/**
 * Example Usage of Cloudinary Service for Tafseer Audio
 * 
 * This script demonstrates how to use the CloudinaryService and TafseerCloudinaryService
 * in your application code.
 */

require_once 'vendor/autoload.php';

use App\Services\CloudinaryService;
use App\Services\TafseerCloudinaryService;
use App\Services\AudioTafseerService;
use App\Services\DatabaseService;

// Initialize services (normally done through DI container)
$databaseService = new DatabaseService();
$audioTafseerService = new AudioTafseerService($databaseService);
$cloudinaryService = new CloudinaryService();
$tafseerCloudinaryService = new TafseerCloudinaryService($cloudinaryService, $audioTafseerService);

echo "🎵 Cloudinary Tafseer Audio Service Examples\n\n";

// Example 1: Test Cloudinary Connection
echo "1️⃣ Testing Cloudinary Connection...\n";
$connectionTest = $cloudinaryService->testConnection();
if ($connectionTest['success']) {
    echo "✅ Cloudinary connection successful!\n";
} else {
    echo "❌ Cloudinary connection failed: " . $connectionTest['error'] . "\n";
    exit(1);
}

// Example 2: Upload Audio from URL
echo "\n2️⃣ Uploading Audio from URL...\n";
$audioUrl = 'https://www.soundjay.com/misc/sounds/bell-ringing-05.wav';
$tafseerData = [
    'tafseer_id' => 1,
    'verse_range_from' => '1:1',
    'verse_range_to' => '1:7'
];

$uploadResult = $tafseerCloudinaryService->uploadAndCreateTafseerAudio($audioUrl, $tafseerData);

if (isset($uploadResult['error'])) {
    echo "❌ Upload failed: " . $uploadResult['error'] . "\n";
} else {
    echo "✅ Upload successful!\n";
    echo "   Database ID: " . $uploadResult['audio_tafseer']->id . "\n";
    echo "   Cloudinary URL: " . $uploadResult['cloudinary']['secure_url'] . "\n";
    echo "   Duration: " . $uploadResult['cloudinary']['duration'] . " seconds\n";
    echo "   File Size: " . round($uploadResult['cloudinary']['bytes'] / 1024, 2) . " KB\n";
    
    $audioId = $uploadResult['audio_tafseer']->id;
}

// Example 3: Get Audio with Quality URLs
if (isset($audioId)) {
    echo "\n3️⃣ Getting Audio with Quality URLs...\n";
    $audioWithQualities = $tafseerCloudinaryService->getTafseerAudioWithQualities($audioId);
    
    if ($audioWithQualities['success']) {
        $audio = $audioWithQualities['audio_tafseer'];
        echo "✅ Audio retrieved with quality URLs:\n";
        echo "   High Quality: " . $audio->quality_urls['high'] . "\n";
        echo "   Medium Quality: " . $audio->quality_urls['medium'] . "\n";
        echo "   Low Quality: " . $audio->quality_urls['low'] . "\n";
    }
}

// Example 4: Direct Cloudinary Service Usage
echo "\n4️⃣ Using CloudinaryService Directly...\n";

// Upload with custom options
$uploadOptions = [
    'folder' => 'custom-folder',
    'public_id' => 'my_custom_audio_' . time(),
    'quality' => 'auto:best'
];

$directUpload = $cloudinaryService->uploadAudio($audioUrl, $uploadOptions);

if (isset($directUpload['error'])) {
    echo "❌ Direct upload failed: " . $directUpload['error'] . "\n";
} else {
    echo "✅ Direct upload successful!\n";
    echo "   Public ID: " . $directUpload['public_id'] . "\n";
    echo "   URL: " . $directUpload['secure_url'] . "\n";
    
    // Get different quality URLs
    $highQualityUrl = $cloudinaryService->getAudioUrl($directUpload['public_id'], 'high_quality');
    $mediumQualityUrl = $cloudinaryService->getAudioUrl($directUpload['public_id'], 'medium_quality');
    
    echo "   High Quality URL: " . $highQualityUrl . "\n";
    echo "   Medium Quality URL: " . $mediumQualityUrl . "\n";
    
    // Get file info
    $fileInfo = $cloudinaryService->getAudioInfo($directUpload['public_id']);
    if ($fileInfo['success']) {
        echo "   File Format: " . $fileInfo['format'] . "\n";
        echo "   File Size: " . round($fileInfo['bytes'] / 1024, 2) . " KB\n";
    }
    
    // Clean up - delete the test file
    $deleteResult = $cloudinaryService->deleteAudio($directUpload['public_id']);
    if ($deleteResult['success']) {
        echo "   🗑️ Test file cleaned up\n";
    }
}

// Example 5: Batch Upload
echo "\n5️⃣ Batch Upload Example...\n";
$batchUploads = [
    [
        'file_path' => $audioUrl,
        'tafseer_data' => [
            'tafseer_id' => 2,
            'verse_range_from' => '2:1',
            'verse_range_to' => '2:5'
        ]
    ],
    [
        'file_path' => $audioUrl,
        'tafseer_data' => [
            'tafseer_id' => 3,
            'verse_range_from' => '3:1',
            'verse_range_to' => '3:10'
        ]
    ]
];

$batchResult = $tafseerCloudinaryService->batchUploadTafseerAudio($batchUploads);
echo "📊 Batch Upload Results:\n";
echo "   Total: " . $batchResult['summary']['total'] . "\n";
echo "   Successful: " . $batchResult['summary']['successful'] . "\n";
echo "   Failed: " . $batchResult['summary']['failed'] . "\n";

// Example 6: Usage Statistics
echo "\n6️⃣ Getting Usage Statistics...\n";
$stats = $tafseerCloudinaryService->getUsageStats();

if ($stats['success']) {
    echo "📈 Cloudinary Usage Stats:\n";
    echo "   Total Files: " . $stats['stats']['total_files'] . "\n";
    echo "   Total Size: " . $stats['stats']['total_size_mb'] . " MB\n";
    echo "   Total Duration: " . $stats['stats']['total_duration_hours'] . " hours\n";
    echo "   Average File Size: " . $stats['stats']['average_file_size_mb'] . " MB\n";
    
    if (!empty($stats['stats']['format_distribution'])) {
        echo "   Format Distribution:\n";
        foreach ($stats['stats']['format_distribution'] as $format => $count) {
            echo "     - $format: $count files\n";
        }
    }
}

// Example 7: Error Handling
echo "\n7️⃣ Error Handling Example...\n";
$invalidData = [
    'tafseer_id' => 999, // Non-existent tafseer
    'verse_range_from' => '1:1',
    'verse_range_to' => '1:7'
];

$errorResult = $tafseerCloudinaryService->uploadAndCreateTafseerAudio($audioUrl, $invalidData);
if (isset($errorResult['error'])) {
    echo "✅ Error handling working correctly: " . $errorResult['error'] . "\n";
}

// Example 8: Configuration Access
echo "\n8️⃣ Configuration Information...\n";
$config = $cloudinaryService->getConfig();
echo "📋 Current Configuration:\n";
echo "   Cloud Name: " . $config['cloud_name'] . "\n";
echo "   Audio Folder: " . $config['audio']['folder'] . "\n";
echo "   Max File Size: " . round($config['audio']['max_file_size'] / (1024 * 1024), 2) . " MB\n";
echo "   Allowed Formats: " . implode(', ', $config['audio']['allowed_formats']) . "\n";

// Cleanup uploaded test files
if (isset($audioId)) {
    echo "\n🧹 Cleaning up test files...\n";
    $cleanupResult = $tafseerCloudinaryService->deleteTafseerAudio($audioId);
    if ($cleanupResult['success']) {
        echo "✅ Test files cleaned up successfully\n";
    }
}

echo "\n🎉 All examples completed!\n";

// Usage Tips
echo "\n💡 Usage Tips:\n";
echo "1. Always check for 'error' key in results before proceeding\n";
echo "2. Use batch upload for multiple files to improve performance\n";
echo "3. Store Cloudinary public_id in database for future reference\n";
echo "4. Use quality URLs to serve different bitrates to users\n";
echo "5. Monitor usage statistics to track storage and bandwidth\n";
echo "6. Use environment variables for production credentials\n";
echo "7. Implement proper error logging in production\n";

echo "\n📚 Next Steps:\n";
echo "1. Configure your Cloudinary credentials in src/config/cloudinary.php\n";
echo "2. Run the database migration: database/cloudinary_migration.sql\n";
echo "3. Add the routes to your application\n";
echo "4. Test with the comprehensive test script: test-cloudinary-api.php\n";
echo "5. Integrate into your frontend application\n";

?>