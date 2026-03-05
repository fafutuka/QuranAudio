<?php

use Slim\App;
use App\Controllers\MufasserController;

return function (App $app) {
    $container = $app->getContainer();

    // Get all mufassers
    $app->get('/mufassers', [MufasserController::class, 'getAllMufassers']);

    // Get specific mufasser by ID
    $app->get('/mufassers/{id}', [MufasserController::class, 'getMufasserById']);

    // Get tafseers by mufasser
    $app->get('/mufassers/{id}/tafseers', [MufasserController::class, 'getMufasserTafseers']);

    // Protected routes (require authentication)
    $app->post('/mufassers', [MufasserController::class, 'createMufasser'])
        ->add($container->get('App\Middleware\JwtMiddleware'));

    $app->put('/mufassers/{id}', [MufasserController::class, 'updateMufasser'])
        ->add($container->get('App\Middleware\JwtMiddleware'));

    $app->delete('/mufassers/{id}', [MufasserController::class, 'deleteMufasser'])
        ->add($container->get('App\Middleware\JwtMiddleware'));

    // Image upload routes (require authentication)
    $app->post('/mufassers/{id}/avatar', [MufasserController::class, 'uploadMufasserAvatar'])
        ->add($container->get('App\Middleware\JwtMiddleware'));

    $app->post('/mufassers/{id}/background', [MufasserController::class, 'uploadMufasserBackground'])
        ->add($container->get('App\Middleware\JwtMiddleware'));

    $app->delete('/mufassers/{id}/avatar', [MufasserController::class, 'deleteMufasserAvatar'])
        ->add($container->get('App\Middleware\JwtMiddleware'));

    $app->delete('/mufassers/{id}/background', [MufasserController::class, 'deleteMufasserBackground'])
        ->add($container->get('App\Middleware\JwtMiddleware'));
};