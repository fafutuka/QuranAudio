<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\RecitationService;

class RecitationController {
    private $service;

    public function __construct(RecitationService $service) {
        $this->service = $service;
    }

    public function getAll(Request $request, Response $response): Response {
        $language = $request->getQueryParams()['language'] ?? 'en';
        $recitations = $this->service->getAll($language);
        $response->getBody()->write(json_encode(['recitations' => $recitations]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
