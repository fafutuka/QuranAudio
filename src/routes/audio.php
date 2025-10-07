<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

return function ($app) {
    // Audio Files CRUD endpoints
    $app->group('/audio-files', function (RouteCollectorProxy $group) {
        // GET single audio file by ID
        $group->get('/{id}', 'App\Controllers\AudioController:getById');

        // POST create new audio file
        $group->post('', 'App\Controllers\AudioController:create');

        // PUT update existing audio file
        $group->put('/{id}', 'App\Controllers\AudioController:update');

        // DELETE audio file
        $group->delete('/{id}', 'App\Controllers\AudioController:delete');
    });

    // Reciter audio endpoints
    $app->group('/reciters/{id}', function (RouteCollectorProxy $group) {
        // GET chapter audio file
        $group->get('/chapters/{chapter_number}', 'App\Controllers\AudioController:getChapterAudio');

        // GET all audio files for a reciter
        $group->get('/audio-files', 'App\Controllers\AudioController:getReciterAudioFiles');
    });

    // Recitation audio files endpoint
    $app->get('/recitation-audio-files/{recitation_id}', 'App\Controllers\AudioController:getRecitationAudioFiles');

    // Resource recitations endpoints
    $app->group('/resources/recitations/{recitation_id}', function (RouteCollectorProxy $group) {
        // GET ayah recitations for surah
        $group->get('/{chapter_number}', 'App\Controllers\AudioController:getSurahAyahRecitations');

        // GET ayah recitations for juz
        $group->get('/juz/{juz_number}', 'App\Controllers\AudioController:getJuzAyahRecitations');

        // GET ayah recitations for page
        $group->get('/pages/{page_number}', 'App\Controllers\AudioController:getPageAyahRecitations');

        // GET ayah recitations for rub el hizb
        $group->get('/rub-el-hizb/{rub_el_hizb_number}', 'App\Controllers\AudioController:getRubElHizbAyahRecitations');

        // GET ayah recitations for hizb
        $group->get('/hizb/{hizb_number}', 'App\Controllers\AudioController:getHizbAyahRecitations');
    });

    // GET ayah recitation endpoint
    $app->get('/resources/ayah-recitation/{recitation_id}/{ayah_key}', 'App\Controllers\AudioController:getAyahRecitations');
};
