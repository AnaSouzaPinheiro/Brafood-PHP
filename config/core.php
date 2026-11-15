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
        // hospedagens com redirecionamento interno) renomeiam o header pra REDIRECT_HTTP_*
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

if (!function_exists('responderEContinuar')) {
    /**
     * Envia a resposta ao cliente e encerra a conexão, mas deixa o PHP seguir
     * executando (ex.: enviar e-mail depois de responder). Assim o tempo de
     * resposta não depende do trabalho feito depois.
     * Se a hospedagem/proxy não permitir fechar a conexão antes do fim do
     * script, o efeito é só o de sempre: a resposta sai quando o script termina.
     */
    function responderEContinuar(string $corpo): void
    {
        ignore_user_abort(true);

        if (!headers_sent()) {
            header('Connection: close');
            header('Content-Length: ' . strlen($corpo));
        }

        echo $corpo;

        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        flush();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
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
