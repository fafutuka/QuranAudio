<?php
// Global application configuration
// This file can be environment-aware. Configure APP_ENV=local to use localhost frontend by default.
// You can also override using FRONTEND_BASE_URL, FRONTEND_BASE_URL_LOCAL, or FRONTEND_BASE_URL_PROD env vars.

$env = getenv('APP_ENV') ?: 'production';
$localBase = getenv('FRONTEND_BASE_URL_LOCAL') ?: 'http://localhost:9000/#/';
$prodBase = getenv('FRONTEND_BASE_URL_PROD') ?: 'https://quran.fafutuka.com/#/';
$base = getenv('FRONTEND_BASE_URL') ?: ($env === 'local' ? $localBase : $prodBase);

// API Host configuration for QuranAudio
$localApiHost = getenv('API_HOST_LOCAL') ?: 'http://localhost/QuranAudio';
$prodApiHost = getenv('API_HOST_PROD') ?: 'https://quranapi.fafutuka.com';
$apiHost = getenv('API_HOST') ?: ($env === 'local' ? $localApiHost : $prodApiHost);

/**
 * Get the frontend URL for redirects
 * 
 * @param int $eventId Event ID for the registration page (optional)
 * @return string Frontend URL
 */
if (!function_exists('getFrontendUrl')) {
    function getFrontendUrl($eventId = null): string
    {
        // Prefer environment overrides, then force localhost base when served from localhost, else fall back to config/production
        try {
            $configPath = __DIR__ . '/app.php';
            $appConfig = file_exists($configPath) ? require $configPath : [];
        } catch (\Throwable $t) {
            $appConfig = [];
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        $isLocalHost = (stripos($host, 'localhost') !== false) || (stripos($host, '127.0.0.1') !== false);

        // Highest priority: explicit FRONTEND_BASE_URL env var
        $envBase = getenv('FRONTEND_BASE_URL') ?: '';

        if ($isLocalHost) {
            // If serving callback locally, default to localhost base unless explicitly overridden
            $base = $envBase ?: (getenv('FRONTEND_BASE_URL_LOCAL') ?: 'http://localhost:9000');
        } else {
            // Remote/production-like callback host: prefer config, then env, then hard-coded prod
            if (isset($appConfig['frontend_base_url']) && is_string($appConfig['frontend_base_url']) && $appConfig['frontend_base_url'] !== '') {
                $base = $appConfig['frontend_base_url'];
            } else {
                $base = $envBase ?: (getenv('FRONTEND_BASE_URL_PROD') ?: 'https://quran.fafutuka.com/#/');
            }
        }

        // Normalize base: strip any URL fragment (everything from '#') and trailing slash
        $hashPos = strpos($base, '#');
        if ($hashPos !== false) {
            $base = substr($base, 0, $hashPos);
        }
        $base = rtrim($base, '/');

        // Return base URL or with event route if eventId provided
        if ($eventId !== null) {
            return $base . '/#/events/' . $eventId . '/register';
        }

        return $base;
    }
}

/**
 * Get the API host URL based on environment detection
 * Uses same logic as getFrontendUrl for consistency
 * 
 * @return string API host URL (without trailing slash)
 */
if (!function_exists('getApiHost')) {
    function getApiHost(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $isLocalHost = (stripos($host, 'localhost') !== false) || (stripos($host, '127.0.0.1') !== false);
        
        // Environment configuration for QuranAudio
        $localApiHost = getenv('API_HOST_LOCAL') ?: 'http://localhost/QuranAudio';
        $prodApiHost = getenv('API_HOST_PROD') ?: 'https://quranapi.fafutuka.com';
        
        // Highest priority: explicit API_HOST env var
        $envApiHost = getenv('API_HOST') ?: '';
        
        if ($isLocalHost) {
            // If serving from localhost, default to local API host unless explicitly overridden
            $apiHost = $envApiHost ?: $localApiHost;
        } else {
            // Remote/production-like host: use environment detection
            $env = getenv('APP_ENV') ?: 'production';
            $apiHost = $envApiHost ?: ($env === 'local' ? $localApiHost : $prodApiHost);
        }
        
        return rtrim($apiHost, '/');
    }
}

return [
    // Base URL of the frontend SPA used for redirects after payments
    // Can include '#/' suffix; backend will sanitize it for redirects.
    'frontend_base_url' => $base,
    
    // API Host for generating full URLs (without trailing slash)
    'api_host' => rtrim($apiHost, '/'),
];
