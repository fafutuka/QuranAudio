<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

return function ($app) {
    $app->group('/recitations', function (RouteCollectorProxy $group) {
        // GET all recitations
        $group->get('', 'App\Controllers\RecitationController:getAll');

        // GET single recitation by ID
        $group->get('/{id}', 'App\Controllers\RecitationController:getById');

        // POST create new recitation
        $group->post('', 'App\Controllers\RecitationController:create');

        // PUT update existing recitation
        $group->put('/{id}', 'App\Controllers\RecitationController:update');

        // DELETE recitation
        $group->delete('/{id}', 'App\Controllers\RecitationController:delete');
    });
};
