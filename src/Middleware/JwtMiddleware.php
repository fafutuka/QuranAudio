<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;
use App\Services\AuthService;

class JwtMiddleware
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function __invoke(Request $request, Handler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode(['error' => 'Unauthorized: Token missing']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $token = $matches[1];
        $decoded = $this->authService->validateToken($token);

        if (!$decoded) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode(['error' => 'Unauthorized: Invalid or expired token']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Add user info to request attributes
        $request = $request->withAttribute('user', $decoded);

        return $handler->handle($request);
    }
}
