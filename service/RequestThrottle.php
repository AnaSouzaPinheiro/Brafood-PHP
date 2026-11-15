<?php

namespace App\Service;

/**
 * Limite de requisições para rotas públicas (cadastro, newsletter, contato)
 * e para rotas caras (IA). Conta TODA chamada (não só as que falham) dentro
 * da janela e, ao estourar, responde 429 com Retry-After e encerra.
 *
 * Para rotas públicas a chave é o IP do visitante. Se o IP não identifica
 * ninguém (ver ClientIp::confiavel), o limite é ignorado em vez de bloquear
 * todo mundo junto.
 */
class RequestThrottle
{
    /**
     * @return int segundos de espera se estourou o limite (nesse caso NÃO registra); 0 se liberado (e registra).
     */
    public static function verificar(
        RateLimiter $limitador,
        string $balde,
        string $chave,
        int $max,
        int $janela
    ): int {
        $espera = $limitador->segundosParaLiberar($balde, $chave, $max, $janela);
        if ($espera > 0) {
            return $espera;
        }

        $limitador->registrar($balde, $chave, $janela);
        return 0;
    }

    /** Limite por IP do visitante; responde 429 e encerra se estourar. */
    public static function porIp(string $balde, int $max, int $janela, ?RateLimiter $limitador = null): void
    {
        if (!ClientIp::confiavel()) {
            return;
        }

        self::aplicar($balde, ClientIp::obter(), $max, $janela, $limitador);
    }

    /** Limite por chave qualquer (ex.: id do usuário logado); responde 429 e encerra se estourar. */
    public static function porChave(string $balde, string $chave, int $max, int $janela, ?RateLimiter $limitador = null): void
    {
        self::aplicar($balde, $chave, $max, $janela, $limitador);
    }

    private static function aplicar(string $balde, string $chave, int $max, int $janela, ?RateLimiter $limitador): void
    {
        $espera = self::verificar($limitador ?? new RateLimiter(), $balde, $chave, $max, $janela);
        if ($espera === 0) {
            return;
        }

        $minutos = (int) ceil($espera / 60);
        $msg = "Muitas solicitações. Tente novamente em $minutos minuto(s).";

        http_response_code(429);
        header('Retry-After: ' . $espera);
        echo json_encode([
            'success'  => false,
            'message'  => $msg,
            'mensagem' => $msg,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
