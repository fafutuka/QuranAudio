<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\ReciterService;

class ReciterController {
    private $service;

    public function __construct(ReciterService $service) {
        $this->service = $service;
    }

    public function getAll(Request $request, Response $response): Response {
        $language = $request->getQueryParams()['language'] ?? 'en';
        $reciters = $this->service->getAll($language);
        $response->getBody()->write(json_encode(['reciters' => $reciters]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getById(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        $reciter = $this->service->getById($id);

        if (!$reciter) {
            $response->getBody()->write(json_encode(['error' => 'Reciter not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode(['reciter' => $reciter]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true);

        $result = $this->service->create($data);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $response->getBody()->write(json_encode(['reciter' => $result]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    public function update(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        $data = json_decode($request->getBody()->getContents(), true);

        $result = $this->service->update($id, $data);

        if (isset($result['error'])) {
            $statusCode = ($result['error'] === 'Reciter not found') ? 404 : 400;
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
        }

        $response->getBody()->write(json_encode(['reciter' => $result]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $id = $args['id'];

        $result = $this->service->delete($id);

        if (isset($result['error'])) {
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
