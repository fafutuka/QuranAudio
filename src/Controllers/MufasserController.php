<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\MufasserService;

class MufasserController {
    private $mufasserService;

    public function __construct(MufasserService $mufasserService) {
        $this->mufasserService = $mufasserService;
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
}