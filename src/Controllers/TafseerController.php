<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\TafseerService;

class TafseerController {
    private $tafseerService;

    public function __construct(TafseerService $tafseerService) {
        $this->tafseerService = $tafseerService;
    }

    public function getAllTafseers(Request $request, Response $response): Response {
        $queryParams = $request->getQueryParams();
        $page = (int)($queryParams['page'] ?? 1);
        $perPage = (int)($queryParams['per_page'] ?? 10);
        $language = $queryParams['language'] ?? 'en';

        $result = $this->tafseerService->getAllTafseers($page, $perPage, $language);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode(['error' => $result['error']]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getTafseerById(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $queryParams = $request->getQueryParams();
        $language = $queryParams['language'] ?? 'en';

        $tafseer = $this->tafseerService->getTafseerById($id, $language);

        if (!$tafseer) {
            $response->getBody()->write(json_encode(['error' => 'Tafseer not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['tafseer' => $tafseer]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getTafseerAudioFiles(Request $request, Response $response, array $args): Response {
        $tafseerId = (int)$args['id'];
        $queryParams = $request->getQueryParams();
        $page = (int)($queryParams['page'] ?? 1);
        $perPage = (int)($queryParams['per_page'] ?? 10);
        $segments = filter_var($queryParams['segments'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $result = $this->tafseerService->getTafseerAudioFiles($tafseerId, $page, $perPage, $segments);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode(['error' => $result['error']]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getTafseerByVerseRange(Request $request, Response $response, array $args): Response {
        $verseFrom = $args['verse_from'];
        $verseTo = $args['verse_to'] ?? $verseFrom;
        $queryParams = $request->getQueryParams();
        $tafseerIds = isset($queryParams['tafseer_ids']) ? explode(',', $queryParams['tafseer_ids']) : [];
        $segments = filter_var($queryParams['segments'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $result = $this->tafseerService->getTafseerByVerseRange($verseFrom, $verseTo, $tafseerIds, $segments);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode(['error' => $result['error']]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function createTafseer(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true);

        $result = $this->tafseerService->createTafseer($data);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode(['error' => $result['error']]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['tafseer' => $result]));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function updateTafseer(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $data = json_decode($request->getBody()->getContents(), true);

        $result = $this->tafseerService->updateTafseer($id, $data);

        if (!$result) {
            $response->getBody()->write(json_encode(['error' => 'Tafseer not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['tafseer' => $result]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function deleteTafseer(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];

        $result = $this->tafseerService->deleteTafseer($id);

        if (!$result) {
            $response->getBody()->write(json_encode(['error' => 'Tafseer not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['message' => 'Tafseer deleted successfully']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}