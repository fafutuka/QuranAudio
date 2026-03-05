<?php

/**
 * Cloudinary Configuration
 * 
 * Configure your Cloudinary credentials here.
 * For production, use environment variables.
 */

// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

return [
    'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME') ?: 'your_cloud_name_here',
    'api_key' => getenv('CLOUDINARY_API_KEY') ?: 'your_api_key_here',
    'api_secret' => getenv('CLOUDINARY_API_SECRET') ?: 'your_api_secret_here',
    'secure' => true,
    
    // Audio upload settings
    'audio' => [
        'folder' => 'quran-audio/tafseer',
        'resource_type' => 'video', // Cloudinary uses 'video' for audio files
        'allowed_formats' => ['mp3', 'wav', 'ogg', 'aac', 'm4a', 'flac'],
        'max_file_size' => 100 * 1024 * 1024, // 100MB in bytes
        'quality' => 'auto',
        'fetch_format' => 'auto',
    ],
    
    // Transformation presets for different audio qualities
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
];