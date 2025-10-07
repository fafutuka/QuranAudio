<?php

// Detect environment based on hostname
$isProduction = isset($_SERVER['HTTP_HOST']) && 
                (strpos($_SERVER['HTTP_HOST'], 'quranaudio.fafutuka.com') !== false ||
                 strpos($_SERVER['HTTP_HOST'], 'fafutuka.com') !== false);

// Return appropriate database configuration based on environment
if ($isProduction) {
    // Production environment (online)
    return [
        'host' => 'localhost',
        'user' => 'fafutuka_taliya',
        'password' => '@Katonmabudi1',
        'database' => 'fafutuka_qurantafseer'
    ];
} else {
    // Local development environment
    return [
        'host' => 'localhost',
        'user' => 'root',
        'password' => '',
        'database' => 'fafutuk1_qurantafseer'
    ];
}
