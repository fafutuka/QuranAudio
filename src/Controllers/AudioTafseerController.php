<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\AudioTafseerService;

class AudioTafseerController {
    private $audioTafseerService;

    public function __construct(AudioTafseerService $audioTafseerService) {
        $this->audioTafseerService = $audioTafseerService;
    }

    public function getAudioTafseerById(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $queryParams = $request->getQueryParams();
        $segments = filter_var($queryParams['segments'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $audioTafseer = $this->audioTafseerService->getAudioTafseerById($id, $segments);

        if (!$audioTafseer) {
            $response->getBody()->write(json_encode(['error' => 'Audio tafseer not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['audio_tafseer' => $audioTafseer]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getAudioTafseerByVerseRange(Request $request, Response $response, array $args): Response {
        $verseFrom = $args['verse_from'];
        $verseTo = $args['verse_to'] ?? $verseFrom;
        $queryParams = $request->getQueryParams();
        $mufasserIds = isset($queryParams['mufasser_ids']) ? array_map('intval', explode(',', $queryParams['mufasser_ids'])) : [];
        $segments = filter_var($queryParams['segments'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $page = (int)($queryParams['page'] ?? 1);
        $perPage = (int)($queryParams['per_page'] ?? 10);

        $result = $this->audioTafseerService->getAudioTafseerByVerseRange($verseFrom, $verseTo, $mufasserIds, $segments, $page, $perPage);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode(['error' => $result['error']]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getAudioTafseerByChapter(Request $request, Response $response, array $args): Response {
        $chapterNumber = (int)$args['chapter_number'];
        $queryParams = $request->getQueryParams();
        $mufasserIds = isset($queryParams['mufasser_ids']) ? array_map('intval', explode(',', $queryParams['mufasser_ids'])) : [];
        $segments = filter_var($queryParams['segments'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $page = (int)($queryParams['page'] ?? 1);
        $perPage = (int)($queryParams['per_page'] ?? 10);

        // Validate chapter number
        if ($chapterNumber < 1 || $chapterNumber > 114) {
            $response->getBody()->write(json_encode(['error' => 'Invalid chapter number. Must be between 1 and 114']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $result = $this->audioTafseerService->getAudioTafseerByChapter($chapterNumber, $mufasserIds, $segments, $page, $perPage);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode(['error' => $result['error']]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }


    public function createAudioTafseer(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true);

        // Validate required fields
        $requiredFields = ['mufasser_id', 'audio_url', 'verse_range_from', 'verse_range_to'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $response->getBody()->write(json_encode(['error' => "Missing required field: $field"]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        }

        $result = $this->audioTafseerService->createAudioTafseer($data);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode(['error' => $result['error']]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['audio_tafseer' => $result]));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function updateAudioTafseer(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];
        $data = json_decode($request->getBody()->getContents(), true);

        $result = $this->audioTafseerService->updateAudioTafseer($id, $data);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode(['error' => $result['error']]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        if (!$result) {
            $response->getBody()->write(json_encode(['error' => 'Audio tafseer not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['audio_tafseer' => $result]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function deleteAudioTafseer(Request $request, Response $response, array $args): Response {
        $id = (int)$args['id'];

        $result = $this->audioTafseerService->deleteAudioTafseer($id);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode(['error' => $result['error']]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        if (!$result) {
            $response->getBody()->write(json_encode(['error' => 'Audio tafseer not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['message' => 'Audio tafseer deleted successfully']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}