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
}
