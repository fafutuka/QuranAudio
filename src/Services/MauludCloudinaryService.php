<?php

namespace App\Services;

use App\Services\CloudinaryService;
use App\Services\MauludService;

/**
 * MauludCloudinaryService
 * 
 * Handles Cloudinary integration for Maulud audio uploads.
 * Provides methods to upload, manage, and delete Maulud audio files on Cloudinary.
 */
class MauludCloudinaryService
{
    private CloudinaryService $cloudinaryService;
    private MauludService $mauludService;

    public function __construct(CloudinaryService $cloudinaryService, MauludService $mauludService)
    {
        $this->cloudinaryService = $cloudinaryService;
        $this->mauludService = $mauludService;
    }

    /**
     * Upload Maulud audio file to Cloudinary and create database record
     */
    public function uploadMauludAudio(array $data, array $audioFile): array
    {
        // Validate required fields
        $requiredFields = ['mufasser_id', 'title'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return ['error' => "Field '{$field}' is required"];
            }
        }

        // Validate audio file
        if (!isset($audioFile['tmp_name']) || !file_exists($audioFile['tmp_name'])) {
            return ['error' => 'Valid audio file is required'];
        }

        // Validate file type
        $allowedTypes = [
            'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/wave', 'audio/x-wav',
            'audio/ogg', 'audio/m4a', 'audio/mp4', 'audio/aac', 'audio/x-aac',
            'audio/flac', 'audio/x-flac'
        ];
        $fileType = $audioFile['type'] ?? '';
        if (!in_array($fileType, $allowedTypes)) {
            return ['error' => 'Invalid audio file type. Allowed types: MP3, WAV, OGG, M4A, AAC, FLAC'];
        }

        // Validate file size (max 50MB)
        $maxSize = 50 * 1024 * 1024; // 50MB
        if ($audioFile['size'] > $maxSize) {
            return ['error' => 'Audio file too large. Maximum size: 50MB'];
        }

        try {
            // Generate unique public ID for Cloudinary
            $mufasserId = $data['mufasser_id'];
            $timestamp = time();
            $publicId = "maulud/mufasser_{$mufasserId}/maulud_{$timestamp}";

            // Upload to Cloudinary
            $uploadResult = $this->cloudinaryService->uploadAudio($audioFile['tmp_name'], [
                'public_id' => $publicId,
                'resource_type' => 'video', // Cloudinary uses 'video' for audio files
                'folder' => 'maulud',
                'tags' => ['maulud', 'audio', "mufasser_{$mufasserId}"],
                'context' => [
                    'mufasser_id' => $mufasserId,
                    'title' => $data['title'],
                    'type' => 'maulud_audio'
                ]
            ]);

            if (isset($uploadResult['error'])) {
                return ['error' => 'Failed to upload audio to Cloudinary: ' . $uploadResult['error']];
            }

            // Extract audio metadata
            $duration = isset($uploadResult['duration']) ? (int)($uploadResult['duration'] * 1000) : 0; // Convert to milliseconds
            $fileSize = $uploadResult['bytes'] ?? $audioFile['size'];
            $format = $uploadResult['format'] ?? 'mp3';
            $audioUrl = $uploadResult['secure_url'] ?? $uploadResult['url'];

            // Prepare maulud data
            $mauludData = [
                'mufasser_id' => $mufasserId,
                'title' => $data['title'],
                'islamic_date' => $data['islamic_date'] ?? null,
                'gregorian_date' => $data['gregorian_date'] ?? null,
                'location' => $data['location'] ?? null,
                'audio_url' => $audioUrl,
                'duration' => $duration,
                'file_size' => $fileSize,
                'format' => $format,
                'cloudinary_public_id' => $uploadResult['public_id']
            ];

            // Create maulud record in database
            $createResult = $this->mauludService->createMaulud($mauludData);

            if (isset($createResult['error'])) {
                // If database creation fails, try to delete the uploaded file from Cloudinary
                $this->cloudinaryService->deleteResource($uploadResult['public_id'], 'video');
                return ['error' => 'Failed to create maulud record: ' . $createResult['error']];
            }

            return [
                'success' => true,
                'maulud_id' => $createResult['id'],
                'cloudinary_public_id' => $uploadResult['public_id'],
                'audio_url' => $audioUrl,
                'duration' => $duration,
                'file_size' => $fileSize,
                'format' => $format
            ];

        } catch (\Exception $e) {
            error_log("Error uploading maulud audio: " . $e->getMessage());
            return ['error' => 'Failed to upload maulud audio'];
        }
    }

    /**
     * Update Maulud audio file on Cloudinary
     */
    public function updateMauludAudio(int $mauludId, array $audioFile, array $metadata = []): array
    {
        // Get existing maulud
        $existingMaulud = $this->mauludService->getMauludById($mauludId);
        if (!$existingMaulud) {
            return ['error' => 'Maulud not found'];
        }

        // Validate audio file
        if (!isset($audioFile['tmp_name']) || !file_exists($audioFile['tmp_name'])) {
            return ['error' => 'Valid audio file is required'];
        }

        try {
            // Delete old file from Cloudinary if it exists
            if ($existingMaulud->cloudinary_public_id) {
                $this->cloudinaryService->deleteResource($existingMaulud->cloudinary_public_id, 'video');
            }

            // Generate new public ID
            $timestamp = time();
            $publicId = "maulud/mufasser_{$existingMaulud->mufasser_id}/maulud_{$timestamp}";

            // Upload new file to Cloudinary
            $uploadResult = $this->cloudinaryService->uploadAudio($audioFile['tmp_name'], [
                'public_id' => $publicId,
                'resource_type' => 'video',
                'folder' => 'maulud',
                'tags' => ['maulud', 'audio', "mufasser_{$existingMaulud->mufasser_id}"],
                'context' => [
                    'mufasser_id' => $existingMaulud->mufasser_id,
                    'title' => $existingMaulud->title,
                    'type' => 'maulud_audio'
                ]
            ]);

            if (isset($uploadResult['error'])) {
                return ['error' => 'Failed to upload new audio to Cloudinary: ' . $uploadResult['error']];
            }

            // Prepare update data
            $updateData = array_merge($metadata, [
                'audio_url' => $uploadResult['secure_url'] ?? $uploadResult['url'],
                'duration' => isset($uploadResult['duration']) ? (int)($uploadResult['duration'] * 1000) : 0,
                'file_size' => $uploadResult['bytes'] ?? $audioFile['size'],
                'format' => $uploadResult['format'] ?? 'mp3',
                'cloudinary_public_id' => $uploadResult['public_id']
            ]);

            // Update maulud record
            $updateResult = $this->mauludService->updateMaulud($mauludId, $updateData);

            if (isset($updateResult['error'])) {
                // If database update fails, try to delete the new uploaded file
                $this->cloudinaryService->deleteResource($uploadResult['public_id'], 'video');
                return ['error' => 'Failed to update maulud record: ' . $updateResult['error']];
            }

            return [
                'success' => true,
                'cloudinary_public_id' => $uploadResult['public_id'],
                'audio_url' => $updateData['audio_url'],
                'duration' => $updateData['duration'],
                'file_size' => $updateData['file_size'],
                'format' => $updateData['format']
            ];

        } catch (\Exception $e) {
            error_log("Error updating maulud audio: " . $e->getMessage());
            return ['error' => 'Failed to update maulud audio'];
        }
    }

    /**
     * Delete Maulud and its audio file from Cloudinary
     */
    public function deleteMauludAudio(int $mauludId): array
    {
        // Get existing maulud
        $existingMaulud = $this->mauludService->getMauludById($mauludId);
        if (!$existingMaulud) {
            return ['error' => 'Maulud not found'];
        }

        try {
            // Delete from Cloudinary if public ID exists
            if ($existingMaulud->cloudinary_public_id) {
                $deleteResult = $this->cloudinaryService->deleteResource($existingMaulud->cloudinary_public_id, 'video');
                
                if (isset($deleteResult['error'])) {
                    error_log("Warning: Failed to delete maulud audio from Cloudinary: " . $deleteResult['error']);
                    // Continue with database deletion even if Cloudinary deletion fails
                }
            }

            // Delete from database
            $dbDeleteResult = $this->mauludService->deleteMaulud($mauludId);

            if (isset($dbDeleteResult['error'])) {
                return ['error' => 'Failed to delete maulud from database: ' . $dbDeleteResult['error']];
            }

            return ['success' => true];

        } catch (\Exception $e) {
            error_log("Error deleting maulud audio: " . $e->getMessage());
            return ['error' => 'Failed to delete maulud audio'];
        }
    }

    /**
     * Get Cloudinary URL for Maulud audio with transformations
     */
    public function getMauludAudioUrl(string $publicId, array $transformations = []): string
    {
        return $this->cloudinaryService->getAudioUrl($publicId, $transformations);
    }

    /**
     * Get Maulud audio metadata from Cloudinary
     */
    public function getMauludAudioMetadata(string $publicId): array
    {
        try {
            return $this->cloudinaryService->getResourceInfo($publicId, 'video');
        } catch (\Exception $e) {
            error_log("Error getting maulud audio metadata: " . $e->getMessage());
            return ['error' => 'Failed to get audio metadata'];
        }
    }

    /**
     * List all Maulud audio files in Cloudinary
     */
    public function listMauludAudioFiles(int $maxResults = 100): array
    {
        try {
            return $this->cloudinaryService->listResources([
                'type' => 'upload',
                'resource_type' => 'video',
                'prefix' => 'maulud/',
                'max_results' => $maxResults,
                'tags' => ['maulud', 'audio']
            ]);
        } catch (\Exception $e) {
            error_log("Error listing maulud audio files: " . $e->getMessage());
            return ['error' => 'Failed to list audio files'];
        }
    }

    /**
     * Generate audio waveform for Maulud
     */
    public function generateMauludWaveform(string $publicId): array
    {
        try {
            // Generate waveform image using Cloudinary's audio visualization
            $waveformUrl = $this->cloudinaryService->getAudioUrl($publicId, [
                'flags' => 'waveform',
                'format' => 'png',
                'width' => 800,
                'height' => 200,
                'color' => '#3498db',
                'background' => 'transparent'
            ]);

            return [
                'success' => true,
                'waveform_url' => $waveformUrl
            ];

        } catch (\Exception $e) {
            error_log("Error generating maulud waveform: " . $e->getMessage());
            return ['error' => 'Failed to generate waveform'];
        }
    }

    /**
     * Get streaming URL for Maulud audio
     */
    public function getMauludStreamingUrl(string $publicId, string $format = 'mp3', int $bitrate = 128): string
    {
        $transformations = [
            'format' => $format,
            'audio_codec' => 'mp3',
            'bit_rate' => $bitrate . 'k'
        ];

        return $this->cloudinaryService->getAudioUrl($publicId, $transformations);
    }
}