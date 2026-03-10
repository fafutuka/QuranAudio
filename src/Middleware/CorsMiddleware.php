<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        // Get the origin from the request
        $origin = $request->getHeaderLine('Origin');
        
        // List of allowed origins
        $allowedOrigins = [
            'https://sheiknasidi.com.ng',
            'https://www.sheiknasidi.com.ng',
            'http://localhost:3000',
            'http://localhost:5173',
            'http://localhost:8080',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:8080',
            'http://localhost:9000',
            'http://127.0.0.1:9000'
        ];
        
        // Determine which origin to allow
        $allowOrigin = '*';
        if (!empty($origin) && in_array($origin, $allowedOrigins)) {
            $allowOrigin = $origin;
        } elseif (!empty($origin)) {
            // For development, allow localhost origins
            if (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) {
                $allowOrigin = $origin;
            } else {
                // For production, be more restrictive
                $allowOrigin = 'https://sheiknasidi.com.ng';
            }
        }
        
        // Handle preflight OPTIONS request
        if ($request->getMethod() === 'OPTIONS') {
            $response = new \Slim\Psr7\Response();
            
            return $response
                ->withStatus(204) // Use 204 No Content for OPTIONS
                ->withHeader('Access-Control-Allow-Origin', $allowOrigin)
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, X-Auth-Token, Cache-Control, Pragma')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS, HEAD')
                ->withHeader('Access-Control-Allow-Credentials', 'true')
                ->withHeader('Access-Control-Max-Age', '86400')
                ->withHeader('Vary', 'Origin')
                ->withHeader('Content-Length', '0');
        }
        
        // Process the actual request
        $response = $handler->handle($request);

        // Add CORS headers to all responses
        return $response
            ->withHeader('Access-Control-Allow-Origin', $allowOrigin)
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, X-Auth-Token, Cache-Control, Pragma')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS, HEAD')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Max-Age', '86400')
            ->withHeader('Vary', 'Origin');
    }
}