<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;

require __DIR__ . '/../vendor/autoload.php';

// Load database configuration
$dbConfig = require __DIR__ . '/../src/config/database.php';

// Load app configuration
$appConfig = require __DIR__ . '/../src/config/app.php';

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

// Set base path dynamically based on environment
$apiHost = $appConfig['api_host'];
$parsedUrl = parse_url($apiHost);
$basePath = $parsedUrl['path'] ?? '';
if (!empty($basePath)) {
    $app->setBasePath($basePath);
}

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
            'GET /health' => 'Health check endpoint',
            'GET /chapter-reciters' => 'List all chapter reciters',
            'GET /chapter-reciters/{id}' => 'Get single reciter by ID',
            'POST /chapter-reciters' => 'Create new reciter',
            'PUT /chapter-reciters/{id}' => 'Update existing reciter',
            'DELETE /chapter-reciters/{id}' => 'Delete reciter',
            'GET /recitations' => 'List all recitations',
            'GET /recitations/{id}' => 'Get single recitation by ID',
            'POST /recitations' => 'Create new recitation',
            'PUT /recitations/{id}' => 'Update existing recitation',
            'DELETE /recitations/{id}' => 'Delete recitation',
            'GET /audio-files/{id}' => 'Get single audio file by ID',
            'POST /audio-files' => 'Create new audio file',
            'PUT /audio-files/{id}' => 'Update existing audio file',
            'DELETE /audio-files/{id}' => 'Delete audio file',
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

// Health check endpoint
$app->get('/health', function (Request $request, Response $response) use ($appConfig) {
    $dbStatus = 'not_configured';

    try {
        $dbConfig = require __DIR__ . '/../src/config/database.php';
        $testConn = new \mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['password'], $dbConfig['database']);

        if ($testConn->connect_error) {
            $dbStatus = 'error: ' . $testConn->connect_error;
        } else {
            $dbStatus = $testConn->ping() ? 'connected' : 'disconnected';
            $testConn->close();
        }
    } catch (Exception $e) {
        $dbStatus = 'error: ' . $e->getMessage();
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'unknown';
    $isLocalHost = (stripos($host, 'localhost') !== false) || (stripos($host, '127.0.0.1') !== false);
    $environment = $isLocalHost ? 'local' : 'production';

    $response->getBody()->write(json_encode([
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => $environment,
        'api_host' => $appConfig['api_host'],
        'database' => $dbStatus,
        'version' => '1.0',
        'php_version' => phpversion()
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Load route files
(require __DIR__ . '/../src/routes/reciter.php')($app);
(require __DIR__ . '/../src/routes/recitation.php')($app);
(require __DIR__ . '/../src/routes/audio.php')($app);

$app->run();
