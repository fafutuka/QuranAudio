<?php

use Slim\App;
use App\Controllers\AudioTafseerController;

return function (App $app) {
    $container = $app->getContainer();

    // Get specific audio tafseer by ID
    $app->get('/audio-tafseers/{id}', [AudioTafseerController::class, 'getAudioTafseerById']);

    // Get audio tafseer by chapter
    $app->get('/audio-tafseers/chapters/{chapter_number}', [AudioTafseerController::class, 'getAudioTafseerByChapter']);

    // Get audio tafseer by verse range
    $app->get('/audio-tafseers/verses/{verse_from}', [AudioTafseerController::class, 'getAudioTafseerByVerseRange']);
    $app->get('/audio-tafseers/verses/{verse_from}/{verse_to}', [AudioTafseerController::class, 'getAudioTafseerByVerseRange']);

    // Protected routes (require authentication)
    $app->post('/audio-tafseers', [AudioTafseerController::class, 'createAudioTafseer'])
        ->add($container->get('App\Middleware\JwtMiddleware'));

    $app->put('/audio-tafseers/{id}', [AudioTafseerController::class, 'updateAudioTafseer'])
        ->add($container->get('App\Middleware\JwtMiddleware'));

    $app->delete('/audio-tafseers/{id}', [AudioTafseerController::class, 'deleteAudioTafseer'])
        ->add($container->get('App\Middleware\JwtMiddleware'));
};