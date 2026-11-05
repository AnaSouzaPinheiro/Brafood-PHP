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

// CARREGAR .env (fonte única de configuração em dev e prod)
require_once __DIR__ . '/env.php';

if (!loadEnv(__DIR__ . '/../.env')) {
    http_response_code(500);
    error_log('Arquivo .env não encontrado.');
    die(json_encode(['error' => 'Configuração ausente.']));
}

$defaults = [
    'APP_ENV'            => 'development',
    'APP_URL'            => 'http://localhost:4200',
    'DB_HOST'            => 'localhost',
    'DB_NAME'            => '',
    'DB_USER'            => 'root',
    'DB_PASSWORD'        => '',
    'DB_PORT'            => '3306',
    'ALLOWED_ORIGIN'     => '*',
    'MAIL_USER'          => '',
    'MAIL_PASS'          => '',
    'OPENROUTER_API_KEY' => '',
];

$config = [];
foreach ($defaults as $key => $default) {
    $value = $_ENV[$key] ?? $default;
    $_ENV[$key] = $value;
    $config[$key] = $value;

    // Constantes mantidas por compatibilidade (Database, AiService...)
    if (!defined($key)) {
        define($key, $value);
    }
}

$GLOBALS['config'] = $config;
