<?php

/**
 * Cloudinary Configuration
 * 
 * Configure your Cloudinary credentials here.
 * For production, use environment variables.
 */

return [
    'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME') ?: 'QuranTafseer',
    'api_key' => getenv('CLOUDINARY_API_KEY') ?: '325397735469775',
    'api_secret' => getenv('CLOUDINARY_API_SECRET') ?: 'OfsS2T29M5ziu5HpOXctQ0FnuII',
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