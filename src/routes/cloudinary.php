<?php

use Slim\App;
use App\Controllers\CloudinaryController;

return function (App $app) {
    $container = $app->getContainer();

    // Cloudinary Audio Upload Routes
    $app->group('/cloudinary', function ($group) use ($container) {
        
        // Test connection
        $group->get('/test', [CloudinaryController::class, 'testConnection']);
        
        // Usage statistics
        $group->get('/stats', [CloudinaryController::class, 'getUsageStats'])
            ->add($container->get('App\Middleware\JwtMiddleware'));
        
        // Tafseer audio upload (file upload or URL)
        $group->post('/tafseer-audio', [CloudinaryController::class, 'uploadTafseerAudio'])
            ->add($container->get('App\Middleware\JwtMiddleware'));
        
        // Get tafseer audio with quality URLs
        $group->get('/tafseer-audio/{id:[0-9]+}', [CloudinaryController::class, 'getTafseerAudioWithQualities']);
        
        // Update tafseer audio file
        $group->put('/tafseer-audio/{id:[0-9]+}', [CloudinaryController::class, 'updateTafseerAudio'])
            ->add($container->get('App\Middleware\JwtMiddleware'));
        
        // Delete tafseer audio (both database and Cloudinary)
        $group->delete('/tafseer-audio/{id:[0-9]+}', [CloudinaryController::class, 'deleteTafseerAudio'])
            ->add($container->get('App\Middleware\JwtMiddleware'));
        
        // Batch upload multiple tafseer audio files
        $group->post('/tafseer-audio/batch', [CloudinaryController::class, 'batchUploadTafseerAudio'])
            ->add($container->get('App\Middleware\JwtMiddleware'));
        
        // Migrate existing tafseer audio to Cloudinary
        $group->post('/tafseer-audio/{id:[0-9]+}/migrate', [CloudinaryController::class, 'migrateTafseerToCloudinary'])
            ->add($container->get('App\Middleware\JwtMiddleware'));
        
        // Get audio file information from Cloudinary
        $group->get('/audio-info/{public_id:.+}', [CloudinaryController::class, 'getAudioInfo'])
            ->add($container->get('App\Middleware\JwtMiddleware'));
    });
};