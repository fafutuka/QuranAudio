# Cloudinary Integration Guide for Quran Audio API

## Overview

This guide provides a complete plug-and-play Cloudinary integration for the Tafseer module of your Quran Audio API. The integration allows you to upload, manage, and serve audio files through Cloudinary's powerful media management platform.

## Features

- **Seamless Upload**: Upload audio files directly to Cloudinary with automatic database integration
- **Multiple Quality URLs**: Generate different quality versions of audio files automatically
- **Organized Storage**: Automatic folder organization by tafseer and verse ranges
- **Batch Operations**: Upload multiple files in a single request
- **Migration Support**: Migrate existing audio files to Cloudinary
- **Error Handling**: Comprehensive error handling with rollback capabilities
- **Usage Statistics**: Monitor your Cloudinary usage and storage

## Installation

### 1. Install Cloudinary PHP SDK

```bash
composer install
```

The `composer.json` has been updated to include `cloudinary/cloudinary_php: ^2.0`.

### 2. Configure Cloudinary Credentials

Update `src/config/cloudinary.php` with your Cloudinary credentials:

```php
return [
    'cloud_name' => 'your_cloud_name',
    'api_key' => 'your_api_key', 
    'api_secret' => 'your_api_secret',
    // ... other settings
];
```

**Environment Variables (Recommended for Production):**
```bash
CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret
```

### 3. Database Migration

Run the migration to add Cloudinary fields to your existing database:

```bash
mysql -u username -p database_name < database/cloudinary_migration.sql
```

### 4. Register Services in DI Container

Add these services to your dependency injection container:

```php
// In your DI container configuration
$container->set('App\Services\CloudinaryService', function() {
    return new App\Services\CloudinaryService();
});

$container->set('App\Services\TafseerCloudinaryService', function() use ($container) {
    return new App\Services\TafseerCloudinaryService(
        $container->get('App\Services\CloudinaryService'),
        $container->get('App\Services\AudioTafseerService')
    );
});

$container->set('App\Controllers\CloudinaryController', function() use ($container) {
    return new App\Controllers\CloudinaryController(
        $container->get('App\Services\TafseerCloudinaryService'),
        $container->get('App\Services\CloudinaryService')
    );
});
```

### 5. Add Routes

Include the Cloudinary routes in your main application:

```php
// In your main routes file or bootstrap
$cloudinaryRoutes = require __DIR__ . '/routes/cloudinary.php';
$cloudinaryRoutes($app);
```

## API Endpoints

### Public Endpoints

#### Test Connection
```http
GET /cloudinary/test
```
Tests the Cloudinary connection and returns status.

#### Get Audio with Quality URLs
```http
GET /cloudinary/tafseer-audio/{id}?segments=true
```
Returns tafseer audio with multiple quality URLs and optional segments.

### Protected Endpoints (Require JWT Authentication)

#### Upload Audio File
```http
POST /cloudinary/tafseer-audio
Content-Type: multipart/form-data OR application/json
```

**Multipart Upload:**
```bash
curl -X POST "http://localhost/QuranAudio/api/cloudinary/tafseer-audio" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "audio_file=@/path/to/audio.mp3" \
  -F "tafseer_id=1" \
  -F "verse_range_from=1:1" \
  -F "verse_range_to=1:7"
```

**URL Upload:**
```bash
curl -X POST "http://localhost/QuranAudio/api/cloudinary/tafseer-audio" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "file_url": "https://example.com/audio.mp3",
    "tafseer_id": 1,
    "verse_range_from": "1:1",
    "verse_range_to": "1:7",
    "quality": "high_quality"
  }'
```

#### Batch Upload
```http
POST /cloudinary/tafseer-audio/batch
```

```json
{
  "uploads": [
    {
      "file_path": "https://example.com/audio1.mp3",
      "tafseer_data": {
        "tafseer_id": 1,
        "verse_range_from": "1:1",
        "verse_range_to": "1:7"
      }
    },
    {
      "file_path": "https://example.com/audio2.mp3", 
      "tafseer_data": {
        "tafseer_id": 2,
        "verse_range_from": "2:1",
        "verse_range_to": "2:5"
      }
    }
  ],
  "global_options": {
    "quality": "medium_quality"
  }
}
```

#### Update Audio File
```http
PUT /cloudinary/tafseer-audio/{id}
```

#### Delete Audio File
```http
DELETE /cloudinary/tafseer-audio/{id}
```

#### Migrate Existing Audio
```http
POST /cloudinary/tafseer-audio/{id}/migrate
```

#### Usage Statistics
```http
GET /cloudinary/stats
```

## Usage Examples

### 1. Basic Upload with PHP

```php
use App\Services\TafseerCloudinaryService;

$service = $container->get('App\Services\TafseerCloudinaryService');

$result = $service->uploadAndCreateTafseerAudio(
    '/path/to/audio.mp3',
    [
        'tafseer_id' => 1,
        'verse_range_from' => '1:1',
        'verse_range_to' => '1:7'
    ]
);

if ($result['success']) {
    echo "Upload successful! Audio ID: " . $result['audio_tafseer']['id'];
    echo "Cloudinary URL: " . $result['cloudinary']['secure_url'];
} else {
    echo "Upload failed: " . $result['error'];
}
```

### 2. Get Audio with Different Qualities

```php
$result = $service->getTafseerAudioWithQualities(1, true); // Include segments

if ($result['success']) {
    $audio = $result['audio_tafseer'];
    echo "High Quality: " . $audio->quality_urls['high'];
    echo "Medium Quality: " . $audio->quality_urls['medium'];
    echo "Low Quality: " . $audio->quality_urls['low'];
}
```

### 3. Batch Upload

```php
$uploads = [
    [
        'file_path' => 'https://example.com/audio1.mp3',
        'tafseer_data' => [
            'tafseer_id' => 1,
            'verse_range_from' => '1:1',
            'verse_range_to' => '1:7'
        ]
    ],
    [
        'file_path' => '/local/path/audio2.mp3',
        'tafseer_data' => [
            'tafseer_id' => 2,
            'verse_range_from' => '2:1',
            'verse_range_to' => '2:5'
        ]
    ]
];

$result = $service->batchUploadTafseerAudio($uploads);
echo "Successful: {$result['summary']['successful']}";
echo "Failed: {$result['summary']['failed']}";
```

## Configuration Options

### Audio Settings

```php
'audio' => [
    'folder' => 'quran-audio/tafseer',           // Cloudinary folder
    'resource_type' => 'video',                   // Cloudinary uses 'video' for audio
    'allowed_formats' => ['mp3', 'wav', 'ogg', 'aac', 'm4a', 'flac'],
    'max_file_size' => 100 * 1024 * 1024,       // 100MB
    'quality' => 'auto',
    'fetch_format' => 'auto',
]
```

### Quality Presets

```php
'transformations' => [
    'high_quality' => [
        'quality' => 'auto:best',
        'audio_codec' => 'mp3',
        'bit_rate' => '320k'
    ],
    'medium_quality' => [
        'quality' => 'auto:good', 
        'audio_codec' => 'mp3',
        'bit_rate' => '192k'
    ],
    'low_quality' => [
        'quality' => 'auto:low',
        'audio_codec' => 'mp3',
        'bit_rate' => '128k'
    ]
]
```

## File Organization

Cloudinary files are automatically organized as:

```
quran-audio/tafseer/
├── tafseer_1/
│   ├── tafseer_1_verses_1_1_to_1_7_1234567890.mp3
│   └── tafseer_1_verses_2_1_to_2_5_1234567891.mp3
├── tafseer_2/
│   └── tafseer_2_verses_1_1_to_1_7_1234567892.mp3
└── ...
```

## Error Handling

The service includes comprehensive error handling:

- **Validation Errors**: File format, size, required fields
- **Upload Failures**: Network issues, Cloudinary errors
- **Database Errors**: Automatic rollback of Cloudinary uploads
- **Authentication**: JWT token validation
- **Not Found**: Graceful handling of missing resources

## Testing

Run the comprehensive test suite:

```bash
php test-cloudinary-api.php
```

Make sure to update the JWT token in the test script before running.

## Migration from Existing URLs

To migrate existing audio files to Cloudinary:

```php
// Migrate single file
$result = $service->migrateTafseerToCloudinary(1);

// Or use the API endpoint
curl -X POST "http://localhost/QuranAudio/api/cloudinary/tafseer-audio/1/migrate" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"upload_options": {"quality": "high_quality"}}'
```

## Monitoring and Analytics

### Usage Statistics

```php
$stats = $service->getUsageStats();
echo "Total Files: " . $stats['stats']['total_files'];
echo "Total Size: " . $stats['stats']['total_size_mb'] . " MB";
echo "Total Duration: " . $stats['stats']['total_duration_hours'] . " hours";
```

### Cloudinary Dashboard

Monitor your usage, transformations, and costs through the Cloudinary dashboard at https://cloudinary.com/console

## Security Best Practices

1. **Environment Variables**: Store credentials in environment variables, not in code
2. **JWT Authentication**: All upload/modify operations require valid JWT tokens
3. **File Validation**: Automatic validation of file types and sizes
4. **Input Sanitization**: All inputs are validated and sanitized
5. **Error Messages**: Generic error messages to prevent information disclosure

## Troubleshooting

### Common Issues

1. **Upload Fails**: Check Cloudinary credentials and network connectivity
2. **Large Files**: Increase `max_file_size` in configuration or PHP limits
3. **Authentication Errors**: Verify JWT token is valid and not expired
4. **Database Errors**: Check foreign key constraints and required fields

### Debug Mode

Enable debug logging by setting environment variable:
```bash
FASTMCP_LOG_LEVEL=DEBUG
```

## Support

For issues specific to this integration, check:
1. Test script output for detailed error messages
2. Cloudinary dashboard for upload status
3. Database logs for constraint violations
4. PHP error logs for service-level issues

For Cloudinary-specific issues, refer to the [Cloudinary PHP Documentation](https://cloudinary.com/documentation/php_integration).