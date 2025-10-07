<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

return function ($app) {
    $app->group('/chapter-reciters', function (RouteCollectorProxy $group) {
        // GET all reciters
        $group->get('', 'App\Controllers\ReciterController:getAll');

        // GET single reciter by ID
        $group->get('/{id}', 'App\Controllers\ReciterController:getById');

        // POST create new reciter
        $group->post('', 'App\Controllers\ReciterController:create');

        // PUT update existing reciter
        $group->put('/{id}', 'App\Controllers\ReciterController:update');

        // DELETE reciter
        $group->delete('/{id}', 'App\Controllers\ReciterController:delete');
    });
};
