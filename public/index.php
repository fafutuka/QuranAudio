<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;

require __DIR__ . '/../vendor/autoload.php';

// Load database configuration
$dbConfig = require __DIR__ . '/../src/config/database.php';

// Create Container
$container = new Container();
AppFactory::setContainer($container);

// Set up DatabaseService
$container->set('DatabaseService', function() use ($dbConfig) {
    return new DatabaseService(
        $dbConfig['host'],
        $dbConfig['user'],
        $dbConfig['password'],
        $dbConfig['database']
    );
});

// Set up dependencies
$container->set('App\Services\ReciterService', function($container) {
    return new App\Services\ReciterService($container->get('DatabaseService'));
});

$container->set('App\Services\RecitationService', function($container) {
    return new App\Services\RecitationService($container->get('DatabaseService'));
});

$container->set('App\Services\AudioService', function($container) {
    return new App\Services\AudioService($container->get('DatabaseService'));
});

$container->set('App\Controllers\ReciterController', function($container) {
    return new App\Controllers\ReciterController($container->get('App\Services\ReciterService'));
});

$container->set('App\Controllers\RecitationController', function($container) {
    return new App\Controllers\RecitationController($container->get('App\Services\RecitationService'));
});

$container->set('App\Controllers\AudioController', function($container) {
    return new App\Controllers\AudioController(
        $container->get('App\Services\AudioService'),
        $container->get('App\Services\RecitationService')
    );
});

// Create App
$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add routing middleware
$app->addRoutingMiddleware();

// Define routes
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode([
        'message' => 'Quran Audio API',
        'version' => '1.0',
        'endpoints' => [
            'GET /chapter-reciters' => 'List all chapter reciters',
            'GET /recitations' => 'List all recitations',
            'GET /reciters/{id}/chapters/{chapter_number}' => 'Get chapter audio file',
            'GET /reciters/{id}/audio-files' => 'Get all audio files for a reciter',
            'GET /recitation-audio-files/{recitation_id}' => 'Get audio files for a recitation',
            'GET /resources/recitations/{recitation_id}/{chapter_number}' => 'Get ayah recitations for surah',
            'GET /resources/recitations/{recitation_id}/juz/{juz_number}' => 'Get ayah recitations for juz',
            'GET /resources/recitations/{recitation_id}/pages/{page_number}' => 'Get ayah recitations for page',
            'GET /resources/recitations/{recitation_id}/rub-el-hizb/{rub_el_hizb_number}' => 'Get ayah recitations for rub el hizb',
            'GET /resources/recitations/{recitation_id}/hizb/{hizb_number}' => 'Get ayah recitations for hizb',
            'GET /resources/ayah-recitation/{recitation_id}/{ayah_key}' => 'Get ayah recitation'
        ]
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Chapter Reciters endpoint
$app->get('/chapter-reciters', 'App\Controllers\ReciterController:getAll');

// Recitations endpoint
$app->get('/recitations', 'App\Controllers\RecitationController:getAll');

// Chapter audio file endpoint
$app->get('/reciters/{id}/chapters/{chapter_number}', 'App\Controllers\AudioController:getChapterAudio');

// Reciter audio files endpoint
$app->get('/reciters/{id}/audio-files', 'App\Controllers\AudioController:getReciterAudioFiles');

// Recitation audio files endpoint
$app->get('/recitation-audio-files/{recitation_id}', 'App\Controllers\AudioController:getRecitationAudioFiles');

// Surah ayah recitations endpoint
$app->get('/resources/recitations/{recitation_id}/{chapter_number}', 'App\Controllers\AudioController:getSurahAyahRecitations');

// Juz ayah recitations endpoint
$app->get('/resources/recitations/{recitation_id}/juz/{juz_number}', 'App\Controllers\AudioController:getJuzAyahRecitations');

// Page ayah recitations endpoint
$app->get('/resources/recitations/{recitation_id}/pages/{page_number}', 'App\Controllers\AudioController:getPageAyahRecitations');

// Rub el Hizb ayah recitations endpoint
$app->get('/resources/recitations/{recitation_id}/rub-el-hizb/{rub_el_hizb_number}', 'App\Controllers\AudioController:getRubElHizbAyahRecitations');

// Hizb ayah recitations endpoint
$app->get('/resources/recitations/{recitation_id}/hizb/{hizb_number}', 'App\Controllers\AudioController:getHizbAyahRecitations');

// Ayah recitation endpoint
$app->get('/resources/ayah-recitation/{recitation_id}/{ayah_key}', 'App\Controllers\AudioController:getAyahRecitations');

$app->run();
