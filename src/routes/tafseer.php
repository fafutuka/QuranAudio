<?php

use Slim\App;
use App\Controllers\TafseerController;

return function (App $app) {
    $container = $app->getContainer();

    // Get all tafseers
    $app->get('/tafseers', [TafseerController::class, 'getAllTafseers']);

    // Get specific tafseer by ID
    $app->get('/tafseers/{id}', [TafseerController::class, 'getTafseerById']);

    // Get audio files for a specific tafseer
    $app->get('/tafseers/{id}/audio-files', [TafseerController::class, 'getTafseerAudioFiles']);

    // Get tafseer by verse range
    $app->get('/tafseers/verses/{verse_from}', [TafseerController::class, 'getTafseerByVerseRange']);
    $app->get('/tafseers/verses/{verse_from}/{verse_to}', [TafseerController::class, 'getTafseerByVerseRange']);

    // Protected routes (require authentication)
    $app->post('/tafseers', [TafseerController::class, 'createTafseer'])
        ->add($container->get('App\Middleware\JwtMiddleware'));

    $app->put('/tafseers/{id}', [TafseerController::class, 'updateTafseer'])
        ->add($container->get('App\Middleware\JwtMiddleware'));

    $app->delete('/tafseers/{id}', [TafseerController::class, 'deleteTafseer'])
        ->add($container->get('App\Middleware\JwtMiddleware'));
};