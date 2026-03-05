<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;
use Exception;

/**
 * CloudinaryService - Plug and Play Audio Upload Service
 * 
 * Handles audio file uploads to Cloudinary with support for:
 * - Multiple audio formats
 * - Quality transformations
 * - Folder organization
 * - Metadata management
 * - Error handling
 */
class CloudinaryService {
    private $cloudinary;
    private $config;
    private $uploadApi;
    private $adminApi;

    public function __construct() {
        $this->config = require __DIR__ . '/../config/cloudinary.php';
        
        // Initialize Cloudinary Configuration (for newer SDK versions)
        \Cloudinary\Configuration\Configuration::instance([
            'cloud' => [
                'cloud_name' => $this->config['cloud_name'],
                'api_key' => $this->config['api_key'],
                'api_secret' => $this->config['api_secret'],
                'secure' => $this->config['secure']
            ]
        ]);
        
        // Initialize Cloudinary instance
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $this->config['cloud_name'],
                'api_key' => $this->config['api_key'],
                'api_secret' => $this->config['api_secret'],
                'secure' => $this->config['secure']
            ]
        ]);
        
        $this->uploadApi = new UploadApi();
        $this->adminApi = new AdminApi();
    }

    /**
     * Upload audio file to Cloudinary
     * 
     * @param string $filePath Local file path or URL
     * @param array $options Upload options
     * @return array Upload result or error
     */
    public function uploadAudio(string $filePath, array $options = []): array {
        try {
            // Validate file
            $validation = $this->validateAudioFile($filePath);
            if (!$validation['valid']) {
                return ['error' => $validation['message']];
            }

            // Prepare upload options
            $uploadOptions = $this->prepareUploadOptions($options);
            
            // Upload to Cloudinary
            $result = $this->uploadApi->upload($filePath, $uploadOptions);
            
            // Convert ApiResponse to array
            $resultArray = $result->getArrayCopy();
            
            return [
                'success' => true,
                'public_id' => $resultArray['public_id'],
                'secure_url' => $resultArray['secure_url'],
                'url' => $resultArray['url'],
                'duration' => $resultArray['duration'] ?? null,
                'bytes' => $resultArray['bytes'],
                'format' => $resultArray['format'],
                'resource_type' => $resultArray['resource_type'],
                'created_at' => $resultArray['created_at'],
                'folder' => $resultArray['folder'] ?? null,
                'metadata' => $this->extractMetadata($resultArray)
            ];
            
        } catch (Exception $e) {
            return [
                'error' => 'Upload failed: ' . $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }

    /**
     * Upload audio for specific tafseer with organized naming
     * 
     * @param string $filePath Local file path or URL
     * @param int $tafseerId Tafseer ID
     * @param string $verseFrom Starting verse (e.g., "1:1")
     * @param string $verseTo Ending verse (e.g., "1:7")
     * @param array $options Additional options
     * @return array Upload result
     */
    public function uploadTafseerAudio(string $filePath, int $tafseerId, string $verseFrom, string $verseTo, array $options = []): array {
        // Generate organized public_id
        $publicId = $this->generateTafseerPublicId($tafseerId, $verseFrom, $verseTo, $options);
        
        $uploadOptions = array_merge($options, [
            'public_id' => $publicId,
            'folder' => $this->config['audio']['folder'] . "/tafseer_{$tafseerId}",
            'context' => [
                'tafseer_id' => $tafseerId,
                'verse_from' => $verseFrom,
                'verse_to' => $verseTo,
                'upload_date' => date('Y-m-d H:i:s')
            ]
        ]);

        return $this->uploadAudio($filePath, $uploadOptions);
    }

    /**
     * Get audio URL with transformations
     * 
     * @param string $publicId Cloudinary public ID
     * @param string $quality Quality preset (high_quality, medium_quality, low_quality)
     * @param array $customTransformations Custom transformation parameters
     * @return string Transformed URL
     */
    public function getAudioUrl(string $publicId, string $quality = 'medium_quality', array $customTransformations = []): string {
        try {
            $transformations = [];
            
            // Apply quality preset
            if (isset($this->config['transformations'][$quality])) {
                $transformations = $this->config['transformations'][$quality];
            }
            
            // Merge custom transformations
            $transformations = array_merge($transformations, $customTransformations);
            
            return $this->cloudinary->video($publicId)
                ->delivery($transformations)
                ->toUrl();
                
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Delete audio file from Cloudinary
     * 
     * @param string $publicId Cloudinary public ID
     * @return array Deletion result
     */
    public function deleteAudio(string $publicId): array {
        try {
            $result = $this->uploadApi->destroy($publicId, [
                'resource_type' => 'video'
            ]);
            
            // Convert ApiResponse to array
            $resultArray = $result->getArrayCopy();
            
            return [
                'success' => $resultArray['result'] === 'ok',
                'result' => $resultArray['result']
            ];
            
        } catch (Exception $e) {
            return [
                'error' => 'Deletion failed: ' . $e->getMessage(),
                'success' => false
            ];
        }
    }

    /**
     * Get audio file information
     * 
     * @param string $publicId Cloudinary public ID
     * @return array File information or error
     */
    public function getAudioInfo(string $publicId): array {
        try {
            $result = $this->adminApi->asset($publicId, [
                'resource_type' => 'video'
            ]);
            
            // Convert ApiResponse to array
            $resultArray = $result->getArrayCopy();
            
            return [
                'success' => true,
                'public_id' => $resultArray['public_id'],
                'format' => $resultArray['format'],
                'duration' => $resultArray['duration'] ?? null,
                'bytes' => $resultArray['bytes'],
                'url' => $resultArray['secure_url'],
                'created_at' => $resultArray['created_at'],
                'folder' => $resultArray['folder'] ?? null,
                'context' => $resultArray['context'] ?? []
            ];
            
        } catch (Exception $e) {
            return [
                'error' => 'Failed to get audio info: ' . $e->getMessage(),
                'success' => false
            ];
        }
    }

    /**
     * List audio files in a folder
     * 
     * @param string $folder Folder path
     * @param int $maxResults Maximum results to return
     * @return array List of audio files
     */
    public function listAudioFiles(string $folder = null, int $maxResults = 100): array {
        try {
            $options = [
                'resource_type' => 'video',
                'max_results' => $maxResults
            ];
            
            if ($folder) {
                $options['prefix'] = $folder;
            }
            
            $result = $this->adminApi->assets($options);
            
            // Convert ApiResponse to array
            $resultArray = $result->getArrayCopy();
            
            return [
                'success' => true,
                'files' => array_map(function($asset) {
                    return [
                        'public_id' => $asset['public_id'],
                        'format' => $asset['format'],
                        'duration' => $asset['duration'] ?? null,
                        'bytes' => $asset['bytes'],
                        'url' => $asset['secure_url'],
                        'created_at' => $asset['created_at']
                    ];
                }, $resultArray['resources']),
                'total_count' => $resultArray['total_count'] ?? count($resultArray['resources'])
            ];
            
        } catch (Exception $e) {
            return [
                'error' => 'Failed to list files: ' . $e->getMessage(),
                'success' => false
            ];
        }
    }

    /**
     * Validate audio file
     * 
     * @param string $filePath File path or URL
     * @return array Validation result
     */
    private function validateAudioFile(string $filePath): array {
        // Check if file exists (for local files)
        if (!filter_var($filePath, FILTER_VALIDATE_URL) && !file_exists($filePath)) {
            return [
                'valid' => false,
                'message' => 'File does not exist: ' . $filePath
            ];
        }

        // Check file size for local files
        if (!filter_var($filePath, FILTER_VALIDATE_URL)) {
            $fileSize = filesize($filePath);
            if ($fileSize > $this->config['audio']['max_file_size']) {
                return [
                    'valid' => false,
                    'message' => 'File size exceeds maximum allowed size'
                ];
            }

            // Check file extension
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (!in_array($extension, $this->config['audio']['allowed_formats'])) {
                return [
                    'valid' => false,
                    'message' => 'File format not supported: ' . $extension
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Prepare upload options
     * 
     * @param array $options User provided options
     * @return array Prepared options
     */
    private function prepareUploadOptions(array $options): array {
        $defaultOptions = [
            'resource_type' => $this->config['audio']['resource_type'],
            'folder' => $this->config['audio']['folder'],
            'quality' => $this->config['audio']['quality'],
            'fetch_format' => $this->config['audio']['fetch_format'],
            'overwrite' => false,
            'unique_filename' => true,
            'use_filename' => true
        ];

        return array_merge($defaultOptions, $options);
    }

    /**
     * Generate organized public ID for tafseer audio
     * 
     * @param int $tafseerId Tafseer ID
     * @param string $verseFrom Starting verse
     * @param string $verseTo Ending verse
     * @param array $options Additional options
     * @return string Generated public ID
     */
    private function generateTafseerPublicId(int $tafseerId, string $verseFrom, string $verseTo, array $options): string {
        $verseFromClean = str_replace(':', '_', $verseFrom);
        $verseToClean = str_replace(':', '_', $verseTo);
        
        $publicId = "tafseer_{$tafseerId}_verses_{$verseFromClean}_to_{$verseToClean}";
        
        // Add timestamp if unique filename is needed
        if (!isset($options['overwrite']) || !$options['overwrite']) {
            $publicId .= '_' . time();
        }
        
        return $publicId;
    }

    /**
     * Extract metadata from upload result
     * 
     * @param array $result Cloudinary upload result
     * @return array Extracted metadata
     */
    private function extractMetadata(array $result): array {
        return [
            'width' => $result['width'] ?? null,
            'height' => $result['height'] ?? null,
            'bit_rate' => $result['bit_rate'] ?? null,
            'audio' => $result['audio'] ?? null,
            'frame_rate' => $result['frame_rate'] ?? null,
            'tags' => $result['tags'] ?? []
        ];
    }

    /**
     * Get configuration
     * 
     * @return array Current configuration
     */
    public function getConfig(): array {
        return $this->config;
    }

    /**
     * Test Cloudinary connection
     * 
     * @return array Connection test result
     */
    public function testConnection(): array {
        try {
            $result = $this->adminApi->ping();
            return [
                'success' => true,
                'status' => $result['status'] ?? 'ok'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}