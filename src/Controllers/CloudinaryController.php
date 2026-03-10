<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\TafseerCloudinaryService;
use App\Services\CloudinaryService;
use App\Services\MauludCloudinaryService;

/**
 * CloudinaryController - Handle Cloudinary-specific operations
 */
class CloudinaryController {
    private $tafseerCloudinaryService;
    private $cloudinaryService;
    private $mauludCloudinaryService;

    public function __construct(
        TafseerCloudinaryService $tafseerCloudinaryService, 
        CloudinaryService $cloudinaryService,
        MauludCloudinaryService $mauludCloudinaryService
    ) {
        $this->tafseerCloudinaryService = $tafseerCloudinaryService;
        $this->cloudinaryService = $cloudinaryService;
        $this->mauludCloudinaryService = $mauludCloudinaryService;
    }

    /**
     * Upload tafseer audio file
     * Expects multipart/form-data with file upload or JSON with file URL
     */
    public function uploadTafseerAudio(Request $request, Response $response): Response {
        try {
            $uploadedFiles = $request->getUploadedFiles();
            $parsedBody = $request->getParsedBody();
            
            // Handle file upload or URL
            $filePath = null;
            if (!empty($uploadedFiles['audio_file'])) {
                $uploadedFile = $uploadedFiles['audio_file'];
                if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                    // Move uploaded file to temp location
                    $tempPath = sys_get_temp_dir() . '/' . uniqid() . '_' . $uploadedFile->getClientFilename();
                    $uploadedFile->moveTo($tempPath);
                    $filePath = $tempPath;
                }
            } elseif (isset($parsedBody['file_url'])) {
                $filePath = $parsedBody['file_url'];
            }

            if (!$filePath) {
                $response->getBody()->write(json_encode(['error' => 'No file provided. Use audio_file for upload or file_url for URL']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // Validate required tafseer data
            $requiredFields = ['mufasser_id', 'verse_range_from', 'verse_range_to'];
            foreach ($requiredFields as $field) {
                if (!isset($parsedBody[$field]) || empty($parsedBody[$field])) {
                    $response->getBody()->write(json_encode(['error' => "Missing required field: $field"]));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }
            }

            // Prepare tafseer data
            $tafseerData = [
                'mufasser_id' => (int)$parsedBody['mufasser_id'],
                'verse_range_from' => $parsedBody['verse_range_from'],
                'verse_range_to' => $parsedBody['verse_range_to']
            ];

            // Add optional fields
            $optionalFields = ['chapter_start', 'verse_start', 'chapter_end', 'verse_end'];
            foreach ($optionalFields as $field) {
                if (isset($parsedBody[$field])) {
                    $tafseerData[$field] = (int)$parsedBody[$field];
                }
            }

            // Prepare upload options
            $uploadOptions = [];
            if (isset($parsedBody['quality'])) {
                $uploadOptions['quality'] = $parsedBody['quality'];
            }
            if (isset($parsedBody['overwrite'])) {
                $uploadOptions['overwrite'] = filter_var($parsedBody['overwrite'], FILTER_VALIDATE_BOOLEAN);
            }

            // Upload and create
            $result = $this->tafseerCloudinaryService->uploadAndCreateTafseerAudio($filePath, $tafseerData, $uploadOptions);

            // Clean up temp file if it was uploaded
            if (isset($uploadedFiles['audio_file']) && file_exists($filePath)) {
                unlink($filePath);
            }

            if (isset($result['error'])) {
                $response->getBody()->write(json_encode(['error' => $result['error']]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($result));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Upload failed: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Get tafseer audio with quality URLs
     */
    public function getTafseerAudioWithQualities(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $queryParams = $request->getQueryParams();
        $segments = filter_var($queryParams['segments'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $result = $this->tafseerCloudinaryService->getTafseerAudioWithQualities($id, $segments);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode(['error' => $result['error']]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Update tafseer audio file
     */
    public function updateTafseerAudio(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        
        try {
            $uploadedFiles = $request->getUploadedFiles();
            $parsedBody = $request->getParsedBody();
            
            // Handle file upload or URL
            $filePath = null;
            if (!empty($uploadedFiles['audio_file'])) {
                $uploadedFile = $uploadedFiles['audio_file'];
                if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                    $tempPath = sys_get_temp_dir() . '/' . uniqid() . '_' . $uploadedFile->getClientFilename();
                    $uploadedFile->moveTo($tempPath);
                    $filePath = $tempPath;
                }
            } elseif (isset($parsedBody['file_url'])) {
                $filePath = $parsedBody['file_url'];
            }

            if (!$filePath) {
                $response->getBody()->write(json_encode(['error' => 'No file provided for update']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // Prepare update data (optional fields)
            $updateData = [];
            $optionalFields = ['chapter_start', 'verse_start', 'chapter_end', 'verse_end'];
            foreach ($optionalFields as $field) {
                if (isset($parsedBody[$field])) {
                    $updateData[$field] = (int)$parsedBody[$field];
                }
            }

            // Prepare upload options
            $uploadOptions = [];
            if (isset($parsedBody['quality'])) {
                $uploadOptions['quality'] = $parsedBody['quality'];
            }

            $result = $this->tafseerCloudinaryService->updateTafseerAudio($id, $filePath, $updateData, $uploadOptions);

            // Clean up temp file
            if (isset($uploadedFiles['audio_file']) && file_exists($filePath)) {
                unlink($filePath);
            }

            if (isset($result['error'])) {
                $response->getBody()->write(json_encode(['error' => $result['error']]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Update failed: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Delete tafseer audio (both database and Cloudinary)
     */
    public function deleteTafseerAudio(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];

        $result = $this->tafseerCloudinaryService->deleteTafseerAudio($id);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode(['error' => $result['error']]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Batch upload multiple tafseer audio files
     */
    public function batchUploadTafseerAudio(Request $request, Response $response): Response {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            if (!isset($data['uploads']) || !is_array($data['uploads'])) {
                $response->getBody()->write(json_encode(['error' => 'Missing uploads array']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $globalOptions = $data['global_options'] ?? [];
            
            $result = $this->tafseerCloudinaryService->batchUploadTafseerAudio($data['uploads'], $globalOptions);

            $statusCode = $result['success'] ? 201 : 207; // 207 Multi-Status for partial success
            
            $response->getBody()->write(json_encode($result));
            return $response->withStatus($statusCode)->withHeader('Content-Type', 'application/json');

        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Batch upload failed: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Migrate existing tafseer audio to Cloudinary
     */
    public function migrateTafseerToCloudinary(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $data = json_decode($request->getBody()->getContents(), true);
        $uploadOptions = $data['upload_options'] ?? [];

        $result = $this->tafseerCloudinaryService->migrateTafseerToCloudinary($id, $uploadOptions);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode(['error' => $result['error']]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get Cloudinary usage statistics
     */
    public function getUsageStats(Request $request, Response $response): Response {
        $result = $this->tafseerCloudinaryService->getUsageStats();

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode(['error' => $result['error']]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Test Cloudinary connection
     */
    public function testConnection(Request $request, Response $response): Response {
        $result = $this->cloudinaryService->testConnection();

        $statusCode = $result['success'] ? 200 : 500;
        
        $response->getBody()->write(json_encode($result));
        return $response->withStatus($statusCode)->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get audio file information from Cloudinary
     */
    public function getAudioInfo(Request $request, Response $response, array $args): Response {
        $publicId = $args['public_id'];

        $result = $this->cloudinaryService->getAudioInfo($publicId);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode(['error' => $result['error']]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // ===== MAULUD CLOUDINARY METHODS =====

    /**
     * Upload Maulud audio file to Cloudinary
     */
    public function uploadMauludAudio(Request $request, Response $response): Response {
        try {
            $uploadedFiles = $request->getUploadedFiles();
            $parsedBody = $request->getParsedBody();
            
            // Validate audio file upload
            if (empty($uploadedFiles['audio_file'])) {
                $response->getBody()->write(json_encode(['error' => 'Audio file is required']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $uploadedFile = $uploadedFiles['audio_file'];
            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                $response->getBody()->write(json_encode(['error' => 'File upload error']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // Validate required maulud data
            $requiredFields = ['mufasser_id', 'title'];
            foreach ($requiredFields as $field) {
                if (!isset($parsedBody[$field]) || empty($parsedBody[$field])) {
                    $response->getBody()->write(json_encode(['error' => "Missing required field: $field"]));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }
            }

            // Prepare maulud data
            $mauludData = [
                'mufasser_id' => (int)$parsedBody['mufasser_id'],
                'title' => $parsedBody['title'],
                'islamic_date' => $parsedBody['islamic_date'] ?? null,
                'gregorian_date' => $parsedBody['gregorian_date'] ?? null,
                'location' => $parsedBody['location'] ?? null
            ];

            // Prepare audio file data - save uploaded file to temp location with proper extension
            $originalFilename = $uploadedFile->getClientFilename();
            $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
            
            // Default to mp3 if no extension is found
            if (empty($extension)) {
                $extension = 'mp3';
            }
            
            $tempFile = tempnam(sys_get_temp_dir(), 'maulud_upload_') . '.' . $extension;
            $uploadedFile->moveTo($tempFile);
            
            $audioFileData = [
                'tmp_name' => $tempFile,
                'size' => $uploadedFile->getSize(),
                'type' => $uploadedFile->getClientMediaType(),
                'name' => $uploadedFile->getClientFilename()
            ];

            // Upload and create maulud
            $result = $this->mauludCloudinaryService->uploadMauludAudio($mauludData, $audioFileData);

            // Clean up temporary file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            if (isset($result['error'])) {
                $response->getBody()->write(json_encode(['error' => $result['error']]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($result));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            // Clean up temporary file in case of exception
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }
            error_log("Error uploading maulud audio: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Upload failed: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Update Maulud audio file on Cloudinary
     */
    public function updateMauludAudio(Request $request, Response $response, array $args): Response {
        $mauludId = (int)$args['id'];
        
        try {
            $uploadedFiles = $request->getUploadedFiles();
            $parsedBody = $request->getParsedBody();
            
            // Validate audio file upload
            if (empty($uploadedFiles['audio_file'])) {
                $response->getBody()->write(json_encode(['error' => 'Audio file is required for update']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $uploadedFile = $uploadedFiles['audio_file'];
            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                $response->getBody()->write(json_encode(['error' => 'File upload error']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // Prepare audio file data - save uploaded file to temp location with proper extension
            $originalFilename = $uploadedFile->getClientFilename();
            $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
            
            // Default to mp3 if no extension is found
            if (empty($extension)) {
                $extension = 'mp3';
            }
            
            $tempFile = tempnam(sys_get_temp_dir(), 'maulud_update_') . '.' . $extension;
            $uploadedFile->moveTo($tempFile);
            
            $audioFileData = [
                'tmp_name' => $tempFile,
                'size' => $uploadedFile->getSize(),
                'type' => $uploadedFile->getClientMediaType(),
                'name' => $uploadedFile->getClientFilename()
            ];

            // Prepare metadata updates (optional)
            $metadata = [];
            $optionalFields = ['title', 'islamic_date', 'gregorian_date', 'location'];
            foreach ($optionalFields as $field) {
                if (isset($parsedBody[$field])) {
                    $metadata[$field] = $parsedBody[$field];
                }
            }

            $result = $this->mauludCloudinaryService->updateMauludAudio($mauludId, $audioFileData, $metadata);

            // Clean up temporary file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            if (isset($result['error'])) {
                $status = $result['error'] === 'Maulud not found' ? 404 : 400;
                $response->getBody()->write(json_encode(['error' => $result['error']]));
                return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            // Clean up temporary file in case of exception
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }
            error_log("Error updating maulud audio: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Update failed: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Delete Maulud audio from both database and Cloudinary
     */
    public function deleteMauludAudio(Request $request, Response $response, array $args): Response {
        $mauludId = (int)$args['id'];

        try {
            $result = $this->mauludCloudinaryService->deleteMauludAudio($mauludId);

            if (isset($result['error'])) {
                $status = $result['error'] === 'Maulud not found' ? 404 : 400;
                $response->getBody()->write(json_encode(['error' => $result['error']]));
                return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($result));
            return $response->withStatus(204);

        } catch (\Exception $e) {
            error_log("Error deleting maulud audio: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Delete failed: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Get Maulud audio metadata from Cloudinary
     */
    public function getMauludAudioMetadata(Request $request, Response $response, array $args): Response {
        $publicId = $args['public_id'];

        try {
            $result = $this->mauludCloudinaryService->getMauludAudioMetadata($publicId);

            if (isset($result['error'])) {
                $response->getBody()->write(json_encode(['error' => $result['error']]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            error_log("Error getting maulud audio metadata: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Failed to get metadata']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Generate waveform for Maulud audio
     */
    public function generateMauludWaveform(Request $request, Response $response, array $args): Response {
        $publicId = $args['public_id'];

        try {
            $result = $this->mauludCloudinaryService->generateMauludWaveform($publicId);

            if (isset($result['error'])) {
                $response->getBody()->write(json_encode(['error' => $result['error']]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            error_log("Error generating maulud waveform: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Failed to generate waveform']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Get streaming URL for Maulud audio
     */
    public function getMauludStreamingUrl(Request $request, Response $response, array $args): Response {
        $publicId = $args['public_id'];
        $queryParams = $request->getQueryParams();
        
        $format = $queryParams['format'] ?? 'mp3';
        $bitrate = (int)($queryParams['bitrate'] ?? 128);

        try {
            $streamingUrl = $this->mauludCloudinaryService->getMauludStreamingUrl($publicId, $format, $bitrate);

            $response->getBody()->write(json_encode([
                'streaming_url' => $streamingUrl,
                'format' => $format,
                'bitrate' => $bitrate
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            error_log("Error getting maulud streaming URL: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Failed to get streaming URL']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * List all Maulud audio files in Cloudinary
     */
    public function listMauludAudioFiles(Request $request, Response $response): Response {
        $queryParams = $request->getQueryParams();
        $maxResults = min(500, max(1, (int)($queryParams['max_results'] ?? 100)));

        try {
            $result = $this->mauludCloudinaryService->listMauludAudioFiles($maxResults);

            if (isset($result['error'])) {
                $response->getBody()->write(json_encode(['error' => $result['error']]));
                return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            error_log("Error listing maulud audio files: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Failed to list audio files']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}