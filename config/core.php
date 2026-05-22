<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/cors.php';

/**
 * Retorna o valor de um header HTTP de forma compatível com CGI/FastCGI.
 * getallheaders() não existe em todos os ambientes PHP.
 */
if (!function_exists('getRequestHeader')) {
    function getRequestHeader(string $name): string
    {
        // Tenta $_SERVER primeiro (funciona em CGI, FPM, Apache mod_php)
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($_SERVER[$serverKey])) {
            return $_SERVER[$serverKey];
        }
        // Fallback para getallheaders() se disponível
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            return $headers[$name] ?? '';
        }
        return '';
    }
}
