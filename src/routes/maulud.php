<?php

use Slim\App;
use App\Controllers\MauludController;

return function (App $app) {
    // Public routes (no authentication required)
    
    // Get all mauluds with pagination and filtering
    $app->get('/mauluds', [MauludController::class, 'getAllMauluds']);
    
    // Get maulud by ID
    $app->get('/mauluds/{id:[0-9]+}', [MauludController::class, 'getMauludById']);
    
    // Get mauluds by mufasser ID
    $app->get('/mufassers/{mufasser_id:[0-9]+}/mauluds', [MauludController::class, 'getMauludsByMufasserId']);
    
    // Get mauluds by year
    $app->get('/mauluds/year/{year:[0-9]{4}}', [MauludController::class, 'getMauludsByYear']);
    
    // Search mauluds
    $app->get('/mauluds/search', [MauludController::class, 'searchMauluds']);
    
    // Get maulud statistics
    $app->get('/mauluds/statistics', [MauludController::class, 'getMauludStatistics']);
    
    // Get recent mauluds
    $app->get('/mauluds/recent', [MauludController::class, 'getRecentMauluds']);

    // Protected routes (require authentication)
    
    // Create new maulud
    $app->post('/mauluds', [MauludController::class, 'createMaulud'])
        ->add($app->getContainer()->get('App\Middleware\JwtMiddleware'));
    
    // Update maulud
    $app->put('/mauluds/{id:[0-9]+}', [MauludController::class, 'updateMaulud'])
        ->add($app->getContainer()->get('App\Middleware\JwtMiddleware'));
    
    // Delete maulud
    $app->delete('/mauluds/{id:[0-9]+}', [MauludController::class, 'deleteMaulud'])
        ->add($app->getContainer()->get('App\Middleware\JwtMiddleware'));
};