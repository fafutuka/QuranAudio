<?php

// Detect environment based on hostname
$isProduction = isset($_SERVER['HTTP_HOST']) &&
    (strpos($_SERVER['HTTP_HOST'], 'api.sheiknasidi.com.ng') !== false ||
        strpos($_SERVER['HTTP_HOST'], 'api.sheiknasidi.com.ng') !== false);

// Return appropriate database configuration based on environment
if ($isProduction) {
    // Production environment (online)
    return [
        'host' => 'localhost',
        'user' => 'sheiknas_taliya',
        'password' => '@Katonmabudi1',
        'database' => 'sheiknas_qurantafseer'
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
