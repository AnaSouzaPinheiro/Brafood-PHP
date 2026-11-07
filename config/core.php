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
        // Alguns hosts compartilhados (CGI com redirecionamento interno,
        // caso do InfinityFree) renomeiam o header pra REDIRECT_HTTP_*
        $redirectKey = 'REDIRECT_' . $serverKey;
        if (isset($_SERVER[$redirectKey])) {
            return $_SERVER[$redirectKey];
        }
        // Fallback para getallheaders() se disponível
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            return $headers[$name] ?? '';
        }
        return '';
    }
}

if (!function_exists('cacheDir')) {
    /**
     * Pasta de cache dentro da própria API. Não usamos sys_get_temp_dir():
     * em hospedagem compartilhada ela pode variar entre requisições, e aí
     * a limpeza feita pelo painel admin não encontrava o arquivo.
     */
    function cacheDir(): string
    {
        $dir = __DIR__ . '/../cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }
}
