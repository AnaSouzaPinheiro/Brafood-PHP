<?php

namespace App\Service;

/**
 * Regras de rotação da home.
 *
 * Tudo que é "aleatório" na home usa uma semente derivada da data, então:
 *   - muda de um dia para o outro (ou de uma semana para a outra);
 *   - é igual para todos os visitantes durante o mesmo dia;
 *   - não muda a cada recarregamento da página.
 */
class HomeRotacaoService
{
    /** Semente do dia (ex.: 20261106) */
    public static function sementeDiaria(): int
    {
        return (int) date('Ymd');
    }

    /** Semente da semana ISO (ex.: 202645) */
    public static function sementeSemanal(): int
    {
        return (int) date('oW');
    }

    /** Identificador da semana ISO (ex.: "2026-W45") */
    public static function semanaAtual(): string
    {
        return date('o-\WW');
    }

    /** Segunda-feira 00:00 da semana atual (Y-m-d H:i:s) */
    public static function inicioDaSemana(): string
    {
        return date('Y-m-d 00:00:00', strtotime('monday this week'));
    }

    /**
     * A seção está no período ativo hoje?
     * $de/$ate no formato "MM-DD". Se algum for vazio, vale o ano todo.
     * Aceita períodos que atravessam o ano (ex.: 12-01 → 01-06).
     */
    public static function estaNoPeriodo(?string $de, ?string $ate, ?string $hoje = null): bool
    {
        if (!$de || !$ate) {
            return true;
        }

        $hoje = $hoje ?? date('m-d');

        return $de <= $ate
            ? ($hoje >= $de && $hoje <= $ate)
            : ($hoje >= $de || $hoje <= $ate);
    }

    /** A seção está fixada no topo hoje? */
    public static function estaFixada(?string $fixarAte): bool
    {
        return $fixarAte !== null && $fixarAte !== '' && $fixarAte >= date('Y-m-d');
    }

    /** Embaralha de forma determinística pela semente (mesmo resultado no mesmo dia) */
    public static function embaralhar(array $itens, int $semente): array
    {
        mt_srand($semente);
        shuffle($itens);
        mt_srand(); // devolve o gerador ao modo aleatório normal
        return $itens;
    }
}
