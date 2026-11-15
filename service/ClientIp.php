<?php

namespace App\Service;

/**
 * Descobre o IP de quem fez a requisição.
 *
 * Regra: o IP de verdade é o REMOTE_ADDR. Cabeçalhos como X-Forwarded-For
 * podem ser forjados pelo cliente, então só são lidos quando o REMOTE_ADDR é
 * um endereço interno (loopback, 10.x, 172.16-31.x, 192.168.x etc.). Nesse
 * caso a requisição passou por um proxy da própria hospedagem, e a última
 * entrada do X-Forwarded-For é a que esse proxy acrescentou (a anterior a
 * ela pode ter sido escrita pelo cliente).
 *
 * Se mesmo assim só houver endereço interno, o IP não identifica ninguém:
 * confiavel() devolve false, e os limites por IP devem ser ignorados em vez de
 * valer para todos os visitantes de uma vez.
 */
class ClientIp
{
    /** @return array{ip: string, origem: string} */
    public static function resolver(?array $server = null): array
    {
        $server ??= $_SERVER;

        $remoto = (string) ($server['REMOTE_ADDR'] ?? '');
        if (!filter_var($remoto, FILTER_VALIDATE_IP)) {
            return ['ip' => 'desconhecido', 'origem' => 'nenhuma'];
        }

        if (self::ehInterno($remoto)) {
            $xff = self::ultimoIpValido((string) ($server['HTTP_X_FORWARDED_FOR'] ?? ''));
            if ($xff !== null && !self::ehInterno($xff)) {
                return ['ip' => $xff, 'origem' => 'X-Forwarded-For'];
            }

            $real = trim((string) ($server['HTTP_X_REAL_IP'] ?? ''));
            if (filter_var($real, FILTER_VALIDATE_IP) && !self::ehInterno($real)) {
                return ['ip' => $real, 'origem' => 'X-Real-IP'];
            }
        }

        return ['ip' => $remoto, 'origem' => 'REMOTE_ADDR'];
    }

    public static function obter(?array $server = null): string
    {
        return self::resolver($server)['ip'];
    }

    /** false quando o IP é interno ou desconhecido (não identifica o visitante). */
    public static function confiavel(?array $server = null): bool
    {
        $ip = self::obter($server);
        return $ip !== 'desconhecido' && !self::ehInterno($ip);
    }

    private static function ehInterno(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    private static function ultimoIpValido(string $lista): ?string
    {
        $itens = array_reverse(array_map('trim', explode(',', $lista)));
        foreach ($itens as $item) {
            if (filter_var($item, FILTER_VALIDATE_IP)) {
                return $item;
            }
        }
        return null;
    }
}
