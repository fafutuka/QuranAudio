<?php

namespace App\Services;

use App\Services\CloudinaryService;
use App\Services\AudioTafseerService;
use Exception;

/**
 * TafseerCloudinaryService - Integration between Tafseer and Cloudinary
 * 
 * Provides specialized methods for uploading and managing tafseer audio files
 * with automatic database integration.
 */
class TafseerCloudinaryService {
    private $cloudinaryService;
    private $audioTafseerService;

    public function __construct(CloudinaryService $cloudinaryService, AudioTafseerService $audioTafseerService) {
        $this->cloudinaryService = $cloudinaryService;
        $this->audioTafseerService = $audioTafseerService;
    }

    /**
     * Upload tafseer audio and create database record
     * 
     * @param string $filePath Local file path or URL
     * @param array $tafseerData Tafseer data (tafseer_id, verse_range_from, verse_range_to, etc.)
     * @param array $uploadOptions Cloudinary upload options
     * @return array Result with database record and Cloudinary info
     */
    public function uploadAndCreateTafseerAudio(string $filePath, array $tafseerData, array $uploadOptions = []): array {
        try {
            // Validate required tafseer data
            $requiredFields = ['mufasser_id', 'verse_range_from', 'verse_range_to'];
            foreach ($requiredFields as $field) {
                if (!isset($tafseerData[$field]) || empty($tafseerData[$field])) {
                    return ['error' => "Missing required field: $field"];
                }
            }

            // Upload to Cloudinary
            $uploadResult = $this->cloudinaryService->uploadTafseerAudio(
                $filePath,
                $tafseerData['mufasser_id'],
                $tafseerData['verse_range_from'],
                $tafseerData['verse_range_to'],
                $uploadOptions
            );

            if (isset($uploadResult['error'])) {
                return $uploadResult;
            }

            // Prepare database data
            $dbData = array_merge($tafseerData, [
                'audio_url' => $uploadResult['secure_url'],
                'duration' => $uploadResult['duration'],
                'file_size' => $uploadResult['bytes'],
                'cloudinary_public_id' => $uploadResult['public_id'],
                'audio_format' => $uploadResult['format']
            ]);

            // Create database record
            $audioTafseer = $this->audioTafseerService->createAudioTafseer($dbData);

            if (isset($audioTafseer['error'])) {
                // Rollback: delete from Cloudinary if database creation fails
                $this->cloudinaryService->deleteAudio($uploadResult['public_id']);
                return $audioTafseer;
            }

            return [
                'success' => true,
                'audio_tafseer' => $audioTafseer,
                'cloudinary' => $uploadResult
            ];

        } catch (Exception $e) {
            return [
                'error' => 'Upload and create failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update tafseer audio file (replace with new upload)
     * 
     * @param int $audioTafseerId Database record ID
     * @param string $newFilePath New file path or URL
     * @param array $updateData Additional data to update
     * @param array $uploadOptions Cloudinary upload options
     * @return array Update result
     */
    public function updateTafseerAudio(int $audioTafseerId, string $newFilePath, array $updateData = [], array $uploadOptions = []): array {
        try {
            // Get existing record
            $existingAudio = $this->audioTafseerService->getAudioTafseerById($audioTafseerId);
            if (!$existingAudio) {
                return ['error' => 'Audio tafseer not found'];
            }

            // Upload new file
            $uploadResult = $this->cloudinaryService->uploadTafseerAudio(
                $newFilePath,
                $existingAudio->tafseer_id,
                $existingAudio->verse_range_from,
                $existingAudio->verse_range_to,
                array_merge($uploadOptions, ['overwrite' => true])
            );

            if (isset($uploadResult['error'])) {
                return $uploadResult;
            }

            // Prepare update data
            $dbUpdateData = array_merge($updateData, [
                'audio_url' => $uploadResult['secure_url'],
                'duration' => $uploadResult['duration'],
                'file_size' => $uploadResult['bytes'],
                'cloudinary_public_id' => $uploadResult['public_id'],
                'audio_format' => $uploadResult['format']
            ]);

            // Update database record
            $updatedAudio = $this->audioTafseerService->updateAudioTafseer($audioTafseerId, $dbUpdateData);

            if (isset($updatedAudio['error'])) {
                return $updatedAudio;
            }

            // Delete old Cloudinary file if it exists and is different
            if (isset($existingAudio->cloudinary_public_id) && 
                $existingAudio->cloudinary_public_id !== $uploadResult['public_id']) {
                $this->cloudinaryService->deleteAudio($existingAudio->cloudinary_public_id);
            }

            return [
                'success' => true,
                'audio_tafseer' => $updatedAudio,
                'cloudinary' => $uploadResult
            ];

        } catch (Exception $e) {
            return [
                'error' => 'Update failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete tafseer audio (both database and Cloudinary)
     * 
     * @param int $audioTafseerId Database record ID
     * @return array Deletion result
     */
    public function deleteTafseerAudio(int $audioTafseerId): array {
        try {
            // Get existing record
            $existingAudio = $this->audioTafseerService->getAudioTafseerById($audioTafseerId);
            if (!$existingAudio) {
                return ['error' => 'Audio tafseer not found'];
            }

            // Delete from database first
            $dbResult = $this->audioTafseerService->deleteAudioTafseer($audioTafseerId);
            if (isset($dbResult['error'])) {
                return $dbResult;
            }

            // Delete from Cloudinary if public_id exists
            $cloudinaryResult = ['success' => true];
            if (isset($existingAudio->cloudinary_public_id)) {
                $cloudinaryResult = $this->cloudinaryService->deleteAudio($existingAudio->cloudinary_public_id);
            }

            return [
                'success' => true,
                'database' => $dbResult,
                'cloudinary' => $cloudinaryResult
            ];

        } catch (Exception $e) {
            return [
                'error' => 'Deletion failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get tafseer audio with different quality URLs
     * 
     * @param int $audioTafseerId Database record ID
     * @param bool $includeSegments Include timestamp segments
     * @return array Audio tafseer with quality URLs
     */
    public function getTafseerAudioWithQualities(int $audioTafseerId, bool $includeSegments = false): array {
        try {
            $audioTafseer = $this->audioTafseerService->getAudioTafseerById($audioTafseerId, $includeSegments);
            
            if (!$audioTafseer) {
                return ['error' => 'Audio tafseer not found'];
            }

            // Add quality URLs if Cloudinary public_id exists
            if (isset($audioTafseer->cloudinary_public_id)) {
                $audioTafseer->quality_urls = [
                    'high' => $this->cloudinaryService->getAudioUrl($audioTafseer->cloudinary_public_id, 'high_quality'),
                    'medium' => $this->cloudinaryService->getAudioUrl($audioTafseer->cloudinary_public_id, 'medium_quality'),
                    'low' => $this->cloudinaryService->getAudioUrl($audioTafseer->cloudinary_public_id, 'low_quality')
                ];
            }

            return [
                'success' => true,
                'audio_tafseer' => $audioTafseer
            ];

        } catch (Exception $e) {
            return [
                'error' => 'Failed to get audio with qualities: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Batch upload multiple tafseer audio files
     * 
     * @param array $uploads Array of upload data [['file_path' => '', 'tafseer_data' => []], ...]
     * @param array $globalOptions Global upload options
     * @return array Batch upload results
     */
    public function batchUploadTafseerAudio(array $uploads, array $globalOptions = []): array {
        $results = [];
        $successful = 0;
        $failed = 0;

        foreach ($uploads as $index => $upload) {
            if (!isset($upload['file_path']) || !isset($upload['tafseer_data'])) {
                $results[$index] = ['error' => 'Missing file_path or tafseer_data'];
                $failed++;
                continue;
            }

            $uploadOptions = array_merge($globalOptions, $upload['upload_options'] ?? []);
            $result = $this->uploadAndCreateTafseerAudio(
                $upload['file_path'],
                $upload['tafseer_data'],
                $uploadOptions
            );

            $results[$index] = $result;
            
            if (isset($result['error'])) {
                $failed++;
            } else {
                $successful++;
            }
        }

        return [
            'success' => $failed === 0,
            'summary' => [
                'total' => count($uploads),
                'successful' => $successful,
                'failed' => $failed
            ],
            'results' => $results
        ];
    }

    /**
     * Migrate existing tafseer audio to Cloudinary
     * 
     * @param int $audioTafseerId Database record ID
     * @param array $uploadOptions Cloudinary upload options
     * @return array Migration result
     */
    public function migrateTafseerToCloudinary(int $audioTafseerId, array $uploadOptions = []): array {
        try {
            $audioTafseer = $this->audioTafseerService->getAudioTafseerById($audioTafseerId);
            
            if (!$audioTafseer) {
                return ['error' => 'Audio tafseer not found'];
            }

            if (isset($audioTafseer->cloudinary_public_id)) {
                return ['error' => 'Audio already migrated to Cloudinary'];
            }

            // Upload existing URL to Cloudinary
            $uploadResult = $this->cloudinaryService->uploadTafseerAudio(
                $audioTafseer->audio_url,
                $audioTafseer->tafseer_id,
                $audioTafseer->verse_range_from,
                $audioTafseer->verse_range_to,
                $uploadOptions
            );

            if (isset($uploadResult['error'])) {
                return $uploadResult;
            }

            // Update database with Cloudinary info
            $updateData = [
                'cloudinary_public_id' => $uploadResult['public_id'],
                'audio_url' => $uploadResult['secure_url'],
                'duration' => $uploadResult['duration'] ?? $audioTafseer->duration,
                'file_size' => $uploadResult['bytes'] ?? $audioTafseer->file_size,
                'audio_format' => $uploadResult['format']
            ];

            $updatedAudio = $this->audioTafseerService->updateAudioTafseer($audioTafseerId, $updateData);

            if (isset($updatedAudio['error'])) {
                // Rollback: delete from Cloudinary
                $this->cloudinaryService->deleteAudio($uploadResult['public_id']);
                return $updatedAudio;
            }

            return [
                'success' => true,
                'message' => 'Successfully migrated to Cloudinary',
                'audio_tafseer' => $updatedAudio,
                'cloudinary' => $uploadResult
            ];

        } catch (Exception $e) {
            return [
                'error' => 'Migration failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get Cloudinary usage statistics for tafseer audio
     * 
     * @return array Usage statistics
     */
    public function getUsageStats(): array {
        try {
            $config = $this->cloudinaryService->getConfig();
            $folder = $config['audio']['folder'];
            
            $files = $this->cloudinaryService->listAudioFiles($folder, 1000);
            
            if (isset($files['error'])) {
                return $files;
            }

            $totalSize = 0;
            $totalDuration = 0;
            $formatCounts = [];

            foreach ($files['files'] as $file) {
                $totalSize += $file['bytes'];
                $totalDuration += $file['duration'] ?? 0;
                
                $format = $file['format'];
                $formatCounts[$format] = ($formatCounts[$format] ?? 0) + 1;
            }

            return [
                'success' => true,
                'stats' => [
                    'total_files' => count($files['files']),
                    'total_size_bytes' => $totalSize,
                    'total_size_mb' => round($totalSize / (1024 * 1024), 2),
                    'total_duration_seconds' => $totalDuration,
                    'total_duration_hours' => round($totalDuration / 3600, 2),
                    'format_distribution' => $formatCounts,
                    'average_file_size_mb' => count($files['files']) > 0 ? round(($totalSize / count($files['files'])) / (1024 * 1024), 2) : 0
                ]
            ];

        } catch (Exception $e) {
            return [
                'error' => 'Failed to get usage stats: ' . $e->getMessage()
            ];
        }
    }
}