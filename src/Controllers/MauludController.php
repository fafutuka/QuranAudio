<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\MauludService;

/**
 * MauludController
 * 
 * Handles HTTP requests for Maulud (Prophet's Birthday celebration) audio recordings.
 * Provides REST API endpoints for CRUD operations and data retrieval.
 */
class MauludController
{
    private MauludService $mauludService;

    public function __construct(MauludService $mauludService)
    {
        $this->mauludService = $mauludService;
    }

    /**
     * GET /mauluds
     * Get all mauluds with optional pagination and filtering
     */
    public function getAllMauluds(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        
        // Extract and validate parameters
        $params = [
            'page' => max(1, (int)($queryParams['page'] ?? 1)),
            'per_page' => min(100, max(1, (int)($queryParams['per_page'] ?? 10))),
            'mufasser_id' => isset($queryParams['mufasser_id']) ? (int)$queryParams['mufasser_id'] : null,
            'year' => isset($queryParams['year']) ? (int)$queryParams['year'] : null,
            'search' => $queryParams['search'] ?? null
        ];

        try {
            $result = $this->mauludService->getAllMauluds($params);
            
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error in getAllMauluds: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Internal server error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * GET /mauluds/{id}
     * Get maulud by ID
     */
    public function getMauludById(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];

        if ($id <= 0) {
            $response->getBody()->write(json_encode(['error' => 'Invalid maulud ID']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $maulud = $this->mauludService->getMauludById($id);

            if (!$maulud) {
                $response->getBody()->write(json_encode(['error' => 'Maulud not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode(['maulud' => $maulud->toArray()]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error in getMauludById: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Internal server error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * GET /mufassers/{mufasser_id}/mauluds
     * Get mauluds by mufasser ID
     */
    public function getMauludsByMufasserId(Request $request, Response $response, array $args): Response
    {
        $mufasserId = (int)$args['mufasser_id'];
        $queryParams = $request->getQueryParams();

        if ($mufasserId <= 0) {
            $response->getBody()->write(json_encode(['error' => 'Invalid mufasser ID']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $params = [
            'page' => max(1, (int)($queryParams['page'] ?? 1)),
            'per_page' => min(100, max(1, (int)($queryParams['per_page'] ?? 10))),
            'year' => isset($queryParams['year']) ? (int)$queryParams['year'] : null,
            'search' => $queryParams['search'] ?? null
        ];

        try {
            $result = $this->mauludService->getMauludsByMufasserId($mufasserId, $params);
            
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error in getMauludsByMufasserId: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Internal server error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * POST /mauluds
     * Create new maulud (Protected route)
     */
    public function createMaulud(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        if (!$data) {
            $response->getBody()->write(json_encode(['error' => 'Invalid JSON data']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $result = $this->mauludService->createMaulud($data);

            if (isset($result['error'])) {
                $response->getBody()->write(json_encode($result));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($result));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error in createMaulud: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Internal server error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * PUT /mauluds/{id}
     * Update maulud (Protected route)
     */
    public function updateMaulud(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = json_decode($request->getBody()->getContents(), true);

        if ($id <= 0) {
            $response->getBody()->write(json_encode(['error' => 'Invalid maulud ID']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        if (!$data) {
            $response->getBody()->write(json_encode(['error' => 'Invalid JSON data']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $result = $this->mauludService->updateMaulud($id, $data);

            if (isset($result['error'])) {
                $status = $result['error'] === 'Maulud not found' ? 404 : 400;
                $response->getBody()->write(json_encode($result));
                return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error in updateMaulud: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Internal server error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * DELETE /mauluds/{id}
     * Delete maulud (Protected route)
     */
    public function deleteMaulud(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];

        if ($id <= 0) {
            $response->getBody()->write(json_encode(['error' => 'Invalid maulud ID']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $result = $this->mauludService->deleteMaulud($id);

            if (isset($result['error'])) {
                $status = $result['error'] === 'Maulud not found' ? 404 : 400;
                $response->getBody()->write(json_encode($result));
                return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($result));
            return $response->withStatus(204);
        } catch (\Exception $e) {
            error_log("Error in deleteMaulud: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Internal server error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * GET /mauluds/year/{year}
     * Get mauluds by year
     */
    public function getMauludsByYear(Request $request, Response $response, array $args): Response
    {
        $year = (int)$args['year'];
        $queryParams = $request->getQueryParams();

        if ($year < 1900 || $year > 2100) {
            $response->getBody()->write(json_encode(['error' => 'Invalid year']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $params = [
            'page' => max(1, (int)($queryParams['page'] ?? 1)),
            'per_page' => min(100, max(1, (int)($queryParams['per_page'] ?? 10))),
            'mufasser_id' => isset($queryParams['mufasser_id']) ? (int)$queryParams['mufasser_id'] : null,
            'search' => $queryParams['search'] ?? null
        ];

        try {
            $result = $this->mauludService->getMauludsByYear($year, $params);
            
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error in getMauludsByYear: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Internal server error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * GET /mauluds/search
     * Search mauluds by title, location, or mufasser name
     */
    public function searchMauluds(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $searchQuery = $queryParams['q'] ?? '';

        if (empty(trim($searchQuery))) {
            $response->getBody()->write(json_encode(['error' => 'Search query is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $params = [
            'page' => max(1, (int)($queryParams['page'] ?? 1)),
            'per_page' => min(100, max(1, (int)($queryParams['per_page'] ?? 10))),
            'mufasser_id' => isset($queryParams['mufasser_id']) ? (int)$queryParams['mufasser_id'] : null,
            'year' => isset($queryParams['year']) ? (int)$queryParams['year'] : null
        ];

        try {
            $result = $this->mauludService->searchMauluds($searchQuery, $params);
            
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error in searchMauluds: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Internal server error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * GET /mauluds/statistics
     * Get maulud statistics
     */
    public function getMauludStatistics(Request $request, Response $response): Response
    {
        try {
            $stats = $this->mauludService->getMauludStatistics();
            
            $response->getBody()->write(json_encode(['statistics' => $stats]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error in getMauludStatistics: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Internal server error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * GET /mauluds/recent
     * Get recent mauluds
     */
    public function getRecentMauluds(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $limit = min(50, max(1, (int)($queryParams['limit'] ?? 5)));

        try {
            $mauluds = $this->mauludService->getRecentMauluds($limit);
            
            $response->getBody()->write(json_encode(['mauluds' => $mauluds]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log("Error in getRecentMauluds: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Internal server error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}