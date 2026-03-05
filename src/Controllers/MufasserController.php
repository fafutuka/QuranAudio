<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\MufasserService;
use App\Services\CloudinaryService;

class MufasserController {
    private $mufasserService;
    private $cloudinaryService;

    public function __construct(MufasserService $mufasserService, CloudinaryService $cloudinaryService) {
        $this->mufasserService = $mufasserService;
        $this->cloudinaryService = $cloudinaryService;
    }

    public function getAllMufassers(Request $request, Response $response): Response {
        $queryParams = $request->getQueryParams();
        $page = (int)($queryParams['page'] ?? 1);
        $perPage = (int)($queryParams['per_page'] ?? 10);
        $language = $queryParams['language'] ?? 'en';

        $result = $this->mufasserService->getAllMufassers($page, $perPage, $language);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode(['error' => $result['error']]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getMufasserById(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $queryParams = $request->getQueryParams();
        $language = $queryParams['language'] ?? 'en';

        $mufasser = $this->mufasserService->getMufasserById($id, $language);

        if (!$mufasser) {
            $response->getBody()->write(json_encode(['error' => 'Mufasser not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['mufasser' => $mufasser]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getMufasserTafseers(Request $request, Response $response, array $args): Response {
        $mufasserId = (int)$args['id'];
        $queryParams = $request->getQueryParams();
        $language = $queryParams['language'] ?? 'en';

        $tafseers = $this->mufasserService->getMufasserTafseers($mufasserId, $language);

        if (isset($tafseers['error'])) {
            $response->getBody()->write(json_encode(['error' => $tafseers['error']]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['tafseers' => $tafseers]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function createMufasser(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true);

        $result = $this->mufasserService->createMufasser($data);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode(['error' => $result['error']]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['mufasser' => $result]));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function updateMufasser(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $data = json_decode($request->getBody()->getContents(), true);

        $result = $this->mufasserService->updateMufasser($id, $data);

        if (!$result) {
            $response->getBody()->write(json_encode(['error' => 'Mufasser not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['mufasser' => $result]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function deleteMufasser(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];

        $result = $this->mufasserService->deleteMufasser($id);

        if (!$result) {
            $response->getBody()->write(json_encode(['error' => 'Mufasser not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['message' => 'Mufasser deleted successfully']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function uploadMufasserAvatar(Request $request, Response $response, array $args): Response {
        $mufasserId = (int)$args['id'];
        
        // Check if mufasser exists
        $mufasser = $this->mufasserService->getMufasserById($mufasserId);
        if (!$mufasser) {
            $response->getBody()->write(json_encode(['error' => 'Mufasser not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $uploadedFiles = $request->getUploadedFiles();
        if (!isset($uploadedFiles['avatar'])) {
            $response->getBody()->write(json_encode(['error' => 'No avatar file provided']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $avatarFile = $uploadedFiles['avatar'];
        if ($avatarFile->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode(['error' => 'File upload error']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            // Upload to Cloudinary with mufasser-specific folder
            $folder = "mufassers/avatars";
            $publicId = "mufasser_{$mufasserId}_avatar_" . time();
            
            $uploadResult = $this->cloudinaryService->uploadImage(
                $avatarFile->getStream()->getContents(),
                $publicId,
                $folder,
                [
                    'width' => 400,
                    'height' => 400,
                    'crop' => 'fill',
                    'gravity' => 'face',
                    'quality' => 'auto',
                    'format' => 'webp'
                ]
            );

            if (isset($uploadResult['error'])) {
                $response->getBody()->write(json_encode(['error' => $uploadResult['error']]));
                return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
            }

            // Update mufasser with new avatar URLs
            $updateData = [
                'avatar_url' => $uploadResult['secure_url'],
                'avatar_cloudinary_id' => $uploadResult['public_id']
            ];

            $updatedMufasser = $this->mufasserService->updateMufasser($mufasserId, $updateData);

            $response->getBody()->write(json_encode([
                'message' => 'Avatar uploaded successfully',
                'mufasser' => $updatedMufasser,
                'upload_result' => $uploadResult
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Upload failed: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function uploadMufasserBackground(Request $request, Response $response, array $args): Response {
        $mufasserId = (int)$args['id'];
        
        // Check if mufasser exists
        $mufasser = $this->mufasserService->getMufasserById($mufasserId);
        if (!$mufasser) {
            $response->getBody()->write(json_encode(['error' => 'Mufasser not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $uploadedFiles = $request->getUploadedFiles();
        if (!isset($uploadedFiles['background'])) {
            $response->getBody()->write(json_encode(['error' => 'No background file provided']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $backgroundFile = $uploadedFiles['background'];
        if ($backgroundFile->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode(['error' => 'File upload error']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            // Upload to Cloudinary with mufasser-specific folder
            $folder = "mufassers/backgrounds";
            $publicId = "mufasser_{$mufasserId}_background_" . time();
            
            $uploadResult = $this->cloudinaryService->uploadImage(
                $backgroundFile->getStream()->getContents(),
                $publicId,
                $folder,
                [
                    'width' => 1200,
                    'height' => 600,
                    'crop' => 'fill',
                    'gravity' => 'center',
                    'quality' => 'auto',
                    'format' => 'webp'
                ]
            );

            if (isset($uploadResult['error'])) {
                $response->getBody()->write(json_encode(['error' => $uploadResult['error']]));
                return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
            }

            // Update mufasser with new background URLs
            $updateData = [
                'background_url' => $uploadResult['secure_url'],
                'background_cloudinary_id' => $uploadResult['public_id']
            ];

            $updatedMufasser = $this->mufasserService->updateMufasser($mufasserId, $updateData);

            $response->getBody()->write(json_encode([
                'message' => 'Background uploaded successfully',
                'mufasser' => $updatedMufasser,
                'upload_result' => $uploadResult
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Upload failed: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function deleteMufasserAvatar(Request $request, Response $response, array $args): Response {
        $mufasserId = (int)$args['id'];
        
        $mufasser = $this->mufasserService->getMufasserById($mufasserId);
        if (!$mufasser) {
            $response->getBody()->write(json_encode(['error' => 'Mufasser not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        if (!$mufasser->avatar_cloudinary_id) {
            $response->getBody()->write(json_encode(['error' => 'No avatar to delete']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            // Delete from Cloudinary
            $deleteResult = $this->cloudinaryService->deleteImage($mufasser->avatar_cloudinary_id);
            
            // Update mufasser to remove avatar URLs
            $updateData = [
                'avatar_url' => null,
                'avatar_cloudinary_id' => null
            ];

            $updatedMufasser = $this->mufasserService->updateMufasser($mufasserId, $updateData);

            $response->getBody()->write(json_encode([
                'message' => 'Avatar deleted successfully',
                'mufasser' => $updatedMufasser
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Delete failed: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function deleteMufasserBackground(Request $request, Response $response, array $args): Response {
        $mufasserId = (int)$args['id'];
        
        $mufasser = $this->mufasserService->getMufasserById($mufasserId);
        if (!$mufasser) {
            $response->getBody()->write(json_encode(['error' => 'Mufasser not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        if (!$mufasser->background_cloudinary_id) {
            $response->getBody()->write(json_encode(['error' => 'No background to delete']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            // Delete from Cloudinary
            $deleteResult = $this->cloudinaryService->deleteImage($mufasser->background_cloudinary_id);
            
            // Update mufasser to remove background URLs
            $updateData = [
                'background_url' => null,
                'background_cloudinary_id' => null
            ];

            $updatedMufasser = $this->mufasserService->updateMufasser($mufasserId, $updateData);

            $response->getBody()->write(json_encode([
                'message' => 'Background deleted successfully',
                'mufasser' => $updatedMufasser
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Delete failed: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}