<?php

use Slim\App;
use App\Middleware\JwtMiddleware;
use App\Middleware\RoleMiddleware;
use App\Controllers\AuthController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app) {
    $container = $app->getContainer();

    // Authentication routes
    $app->post('/auth/register', [AuthController::class, 'register']);
    $app->post('/auth/login', [AuthController::class, 'login']);

    // Protected test routes
    $app->get('/auth/test-protected', function (Request $request, Response $response) {
        $user = $request->getAttribute('user');
        $response->getBody()->write(json_encode([
            'message' => 'Successfully accessed protected route',
            'user' => $user
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    })->add($container->get(JwtMiddleware::class));

    $app->get('/auth/test-admin', function (Request $request, Response $response) {
        $user = $request->getAttribute('user');
        $response->getBody()->write(json_encode([
            'message' => 'Successfully accessed admin route',
            'user' => $user
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    })
        ->add(new RoleMiddleware(['superadmin', 'admin']))
        ->add($container->get(JwtMiddleware::class));

    $app->get('/auth/test-superadmin', function (Request $request, Response $response) {
        $user = $request->getAttribute('user');
        $response->getBody()->write(json_encode([
            'message' => 'Successfully accessed superadmin route',
            'user' => $user
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    })
        ->add(new RoleMiddleware(['superadmin']))
        ->add($container->get(JwtMiddleware::class));
};
