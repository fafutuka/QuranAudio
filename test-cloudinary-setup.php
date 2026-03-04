<?php

/**
 * Quick test to verify Cloudinary setup
 */

require_once 'vendor/autoload.php';

echo "🧪 Testing Cloudinary Setup...\n\n";

// Test 1: Check if Cloudinary classes are available
echo "1️⃣ Checking Cloudinary SDK availability...\n";
if (class_exists('Cloudinary\Cloudinary')) {
    echo "✅ Cloudinary\Cloudinary class found\n";
} else {
    echo "❌ Cloudinary\Cloudinary class not found\n";
    exit(1);
}

if (class_exists('Cloudinary\Api\Upload\UploadApi')) {
    echo "✅ Cloudinary\Api\Upload\UploadApi class found\n";
} else {
    echo "❌ Cloudinary\Api\Upload\UploadApi class not found\n";
    exit(1);
}

// Test 2: Check if our service classes can be loaded
echo "\n2️⃣ Checking our service classes...\n";
try {
    require_once 'src/Services/CloudinaryService.php';
    echo "✅ CloudinaryService class loaded\n";
} catch (Exception $e) {
    echo "❌ CloudinaryService class failed to load: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    require_once 'src/Services/DatabaseService.php';
    require_once 'src/Services/AudioTafseerService.php';
    require_once 'src/Services/TafseerCloudinaryService.php';
    echo "✅ TafseerCloudinaryService class loaded\n";
} catch (Exception $e) {
    echo "❌ TafseerCloudinaryService class failed to load: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Try to instantiate CloudinaryService (will fail without credentials, but should not crash)
echo "\n3️⃣ Testing CloudinaryService instantiation...\n";
try {
    $cloudinaryService = new App\Services\CloudinaryService();
    echo "✅ CloudinaryService instantiated successfully\n";
    
    // Test configuration loading
    $config = $cloudinaryService->getConfig();
    echo "✅ Configuration loaded successfully\n";
    echo "   Cloud Name: " . $config['cloud_name'] . "\n";
    echo "   Audio Folder: " . $config['audio']['folder'] . "\n";
    echo "   Max File Size: " . round($config['audio']['max_file_size'] / (1024 * 1024), 2) . " MB\n";
    
} catch (Exception $e) {
    echo "❌ CloudinaryService instantiation failed: " . $e->getMessage() . "\n";
}

// Test 4: Check configuration file
echo "\n4️⃣ Checking configuration file...\n";
if (file_exists('src/config/cloudinary.php')) {
    echo "✅ Cloudinary configuration file exists\n";
    $config = require 'src/config/cloudinary.php';
    if (is_array($config)) {
        echo "✅ Configuration file returns valid array\n";
        echo "   Required keys present: " . 
             (isset($config['cloud_name'], $config['api_key'], $config['api_secret']) ? "YES" : "NO") . "\n";
    } else {
        echo "❌ Configuration file does not return array\n";
    }
} else {
    echo "❌ Cloudinary configuration file not found\n";
}

// Test 5: Check database schema files
echo "\n5️⃣ Checking database files...\n";
if (file_exists('database/tafseer_schema.sql')) {
    echo "✅ Tafseer schema file exists\n";
} else {
    echo "❌ Tafseer schema file not found\n";
}

if (file_exists('database/cloudinary_migration.sql')) {
    echo "✅ Cloudinary migration file exists\n";
} else {
    echo "❌ Cloudinary migration file not found\n";
}

// Test 6: Check route files
echo "\n6️⃣ Checking route files...\n";
if (file_exists('src/routes/cloudinary.php')) {
    echo "✅ Cloudinary routes file exists\n";
} else {
    echo "❌ Cloudinary routes file not found\n";
}

// Test 7: Check test files
echo "\n7️⃣ Checking test files...\n";
if (file_exists('test-cloudinary-api.php')) {
    echo "✅ Cloudinary API test file exists\n";
} else {
    echo "❌ Cloudinary API test file not found\n";
}

if (file_exists('example-cloudinary-usage.php')) {
    echo "✅ Cloudinary usage example file exists\n";
} else {
    echo "❌ Cloudinary usage example file not found\n";
}

echo "\n🎉 Setup verification completed!\n";

echo "\n📋 Next Steps:\n";
echo "1. Configure your Cloudinary credentials in src/config/cloudinary.php\n";
echo "2. Run database migration: mysql -u username -p database < database/cloudinary_migration.sql\n";
echo "3. Add Cloudinary routes to your main application\n";
echo "4. Test with: php test-cloudinary-api.php (after configuring credentials)\n";

echo "\n💡 Configuration Template:\n";
echo "Update src/config/cloudinary.php with your credentials:\n";
echo "  'cloud_name' => 'your_actual_cloud_name',\n";
echo "  'api_key' => 'your_actual_api_key',\n";
echo "  'api_secret' => 'your_actual_api_secret',\n";

echo "\n🔐 Environment Variables (Recommended for Production):\n";
echo "CLOUDINARY_CLOUD_NAME=your_cloud_name\n";
echo "CLOUDINARY_API_KEY=your_api_key\n";
echo "CLOUDINARY_API_SECRET=your_api_secret\n";

?>