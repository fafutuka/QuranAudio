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
$container->set('DatabaseService', function () use ($dbConfig) {
    return new \App\Services\DatabaseService(
        $dbConfig['host'],
        $dbConfig['user'],
        $dbConfig['password'],
        $dbConfig['database']
    );
});

// Set up dependencies
$container->set('App\Services\ReciterService', function ($container) {
    return new App\Services\ReciterService($container->get('DatabaseService'));
});

$container->set('App\Services\RecitationService', function ($container) {
    return new App\Services\RecitationService($container->get('DatabaseService'));
});

$container->set('App\Services\AudioService', function ($container) {
    return new App\Services\AudioService($container->get('DatabaseService'));
});

$container->set('App\Controllers\ReciterController', function ($container) {
    return new App\Controllers\ReciterController($container->get('App\Services\ReciterService'));
});

$container->set('App\Controllers\RecitationController', function ($container) {
    return new App\Controllers\RecitationController($container->get('App\Services\RecitationService'));
});

$container->set('App\Controllers\AudioController', function ($container) {
    return new App\Controllers\AudioController(
        $container->get('App\Services\AudioService'),
        $container->get('App\Services\RecitationService')
    );
});

// Auth dependencies
$container->set('App\Services\AuthService', function ($container) {
    return new App\Services\AuthService($container->get('DatabaseService'));
});

$container->set('App\Controllers\AuthController', function ($container) {
    return new App\Controllers\AuthController($container->get('App\Services\AuthService'));
});

$container->set('App\Middleware\JwtMiddleware', function ($container) {
    return new App\Middleware\JwtMiddleware($container->get('App\Services\AuthService'));
});

$container->set('App\Middleware\CorsMiddleware', function () {
    return new App\Middleware\CorsMiddleware();
});

// Tafseer dependencies
$container->set('App\Services\MufasserService', function ($container) {
    return new App\Services\MufasserService($container->get('DatabaseService'));
});

$container->set('App\Services\TafseerService', function ($container) {
    return new App\Services\TafseerService($container->get('DatabaseService'));
});

$container->set('App\Services\AudioTafseerService', function ($container) {
    return new App\Services\AudioTafseerService($container->get('DatabaseService'));
});

$container->set('App\Controllers\MufasserController', function ($container) {
    return new App\Controllers\MufasserController(
        $container->get('App\Services\MufasserService'),
        $container->get('App\Services\CloudinaryService')
    );
});

$container->set('App\Controllers\TafseerController', function ($container) {
    return new App\Controllers\TafseerController($container->get('App\Services\TafseerService'));
});

$container->set('App\Controllers\AudioTafseerController', function ($container) {
    return new App\Controllers\AudioTafseerController($container->get('App\Services\AudioTafseerService'));
});

// Cloudinary dependencies
$container->set('App\Services\CloudinaryService', function () {
    return new App\Services\CloudinaryService();
});

$container->set('App\Services\TafseerCloudinaryService', function ($container) {
    return new App\Services\TafseerCloudinaryService(
        $container->get('App\Services\CloudinaryService'),
        $container->get('App\Services\AudioTafseerService')
    );
});

$container->set('App\Services\MauludCloudinaryService', function ($container) {
    return new App\Services\MauludCloudinaryService(
        $container->get('App\Services\CloudinaryService'),
        $container->get('App\Services\MauludService')
    );
});

$container->set('App\Controllers\CloudinaryController', function ($container) {
    return new App\Controllers\CloudinaryController(
        $container->get('App\Services\TafseerCloudinaryService'),
        $container->get('App\Services\CloudinaryService'),
        $container->get('App\Services\MauludCloudinaryService')
    );
});

// Maulud dependencies
$container->set('App\Services\MauludService', function ($container) {
    return new App\Services\MauludService($container->get('DatabaseService'));
});

$container->set('App\Controllers\MauludController', function ($container) {
    return new App\Controllers\MauludController($container->get('App\Services\MauludService'));
});

// Create App
$app = AppFactory::create();

// Set base path dynamically based on environment
$apiHost = $appConfig['api_host'];
$parsedUrl = parse_url($apiHost);
$basePath = $parsedUrl['path'] ?? '';

// Only set base path if it's not root and not empty
if (!empty($basePath) && $basePath !== '/') {
    $app->setBasePath($basePath);
}

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add routing middleware
$app->addRoutingMiddleware();

// Add body parsing middleware
$app->addBodyParsingMiddleware();

// Add CORS middleware (must be added last so it executes first)
$app->add($container->get('App\Middleware\CorsMiddleware'));

// Global OPTIONS handler for CORS preflight - catch all OPTIONS requests
$app->options('/{routes:.*}', function (Request $request, Response $response) {
    // This should not be reached if CORS middleware is working properly
    // But serves as a fallback
    return $response->withStatus(204);
});

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
            'GET /resources/ayah-recitation/{recitation_id}/{ayah_key}' => 'Get ayah recitation',
            'POST /auth/register' => 'Register new user',
            'POST /auth/login' => 'Login and get JWT token',
            'GET /auth/test-protected' => 'Test JWT protection',
            'GET /auth/test-admin' => 'Test Admin role protection'
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

// CORS test endpoint
$app->get('/cors-test', function (Request $request, Response $response) {
    $origin = $request->getHeaderLine('Origin');
    $userAgent = $request->getHeaderLine('User-Agent');

    $response->getBody()->write(json_encode([
        'message' => 'CORS test successful',
        'origin' => $origin,
        'user_agent' => $userAgent,
        'timestamp' => date('Y-m-d H:i:s'),
        'headers_received' => $request->getHeaders()
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Load route files
(require __DIR__ . '/../src/routes/reciter.php')($app);
(require __DIR__ . '/../src/routes/recitation.php')($app);
(require __DIR__ . '/../src/routes/audio.php')($app);
(require __DIR__ . '/../src/routes/auth.php')($app);
(require __DIR__ . '/../src/routes/mufasser.php')($app);
(require __DIR__ . '/../src/routes/tafseer.php')($app);
(require __DIR__ . '/../src/routes/audio_tafseer.php')($app);
(require __DIR__ . '/../src/routes/cloudinary.php')($app);
(require __DIR__ . '/../src/routes/maulud.php')($app);

$app->run();
