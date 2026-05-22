<?php

// ERROR HANDLING
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// PREVENIR DUPLA EXECUÇÃO
if (defined('__APP_BOOTSTRAPPED')) {
    return;
}
define('__APP_BOOTSTRAPPED', true);

// DEFINIR AMBIENTE
$isDev = file_exists(__DIR__ . '/../.env');

// CONFIG CENTRAL 
$config = [];

// DEV
if ($isDev) {

    require_once __DIR__ . '/env.php';

    $config = [
        'APP_ENV' => $_ENV['APP_ENV'] ?? 'development',
        'APP_URL' => $_ENV['APP_URL'] ?? 'http://localhost',

        'DB_HOST' => $_ENV['DB_HOST'] ?? 'localhost',
        'DB_NAME' => $_ENV['DB_NAME'] ?? '',
        'DB_USER' => $_ENV['DB_USER'] ?? 'root',
        'DB_PASSWORD' => $_ENV['DB_PASSWORD'] ?? '',
        'DB_PORT' => $_ENV['DB_PORT'] ?? '3306',

        'ALLOWED_ORIGIN' => $_ENV['ALLOWED_ORIGIN'] ?? '*',

        'MAIL_USER' => $_ENV['MAIL_USER'] ?? '',
        'MAIL_PASS' => $_ENV['MAIL_PASS'] ?? '',

        'OPENROUTER_API_KEY' => $_ENV['OPENROUTER_API_KEY'] ?? '',
    ];
}

// PROD
else {

    $configFile = __DIR__ . '/config.php';

    if (!file_exists($configFile)) {
        die('Config file not found.');
    }

    $config = require $configFile;
}

// DISPONIBILIZAR GLOBALMENTE
$GLOBALS['config'] = $config;