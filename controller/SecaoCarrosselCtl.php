<?php

/**
 * Controla as seções de carrossel exibidas na home.
 * Cada seção tem um título, imagem de fundo e uma lista de receitas vinculadas.
 *
 * Ordem de prioridade para evitar repetição de receitas:
 *   1º — Seções manuais (modo = 'manual')
 *   2º — Seções aleatórias com categoria ou subcategoria definida
 *   3º — Seções totalmente aleatórias (sem filtro algum)
 *
 * Seções de maior prioridade escolhem suas receitas primeiro,
 * garantindo que as genéricas não "roubem" receitas mais relevantes.
 */

namespace App\Controller;

use App\Model\SecaoCarrossel;

class SecaoCarrosselCtl
{
    private $model;

    public function __construct()
    {
        $this->model = new SecaoCarrossel();
    }

    public function index(): array
    {
        $secoes = $this->model->getAllSecoes();

        // ── Atribui prioridade numérica a cada seção ──────────────────────────
        // 1 = manual | 2 = aleatorio com filtro | 3 = aleatorio sem filtro
        foreach ($secoes as &$secao) {
            $secao['_prioridade'] = $this->calcularPrioridade($secao);
        }
        unset($secao);

        // ── Ordena por prioridade (menor número = processa primeiro) ──────────
        // Dentro da mesma prioridade, mantém a ordem original (campo 'ordem').
        usort($secoes, function ($a, $b) {
            if ($a['_prioridade'] !== $b['_prioridade']) {
                return $a['_prioridade'] <=> $b['_prioridade'];
            }
            return ($a['ordem'] ?? 0) <=> ($b['ordem'] ?? 0);
        });

        // ── Popula receitas respeitando a prioridade ──────────────────────────
        $idsUsados = [];

        foreach ($secoes as &$secao) {
            $receitas = $this->model->getReceitasDaSecao($secao, $idsUsados);

            foreach ($receitas as $r) {
                $idsUsados[] = (int) $r['id'];
            }

            $secao['receitas'] = $receitas;
            unset($secao['_prioridade']); // Remove campo auxiliar da resposta
        }
        unset($secao);

        // ── Restaura a ordem de exibição original (campo 'ordem') ─────────────
        usort($secoes, fn($a, $b) => ($a['ordem'] ?? 0) <=> ($b['ordem'] ?? 0));

        return $secoes;
    }

    /**
     * Retorna a prioridade da seção:
     *   1 — manual
     *   2 — aleatório com categoria ou subcategoria
     *   3 — aleatório sem nenhum filtro
     */
    private function calcularPrioridade(array $secao): int
    {
        if (($secao['modo'] ?? 'aleatorio') === 'manual') {
            return 1;
        }

        $temCategoria    = !empty($secao['id_categoria']);
        $temSubcategoria = !empty($secao['id_subcategoria']);

        if ($temCategoria || $temSubcategoria) {
            return 2;
        }

        return 3;
    }

    public function create(array $data): int
    {
        $secao_id = $this->model->createSecao(
            $data['titulo'],
            $data['url_fundo']    ?? null,
            $data['fundo_titulo'] ?? null,
            $data['ordem']        ?? 0
        );

        foreach ($data['receitas'] ?? [] as $receita_id) {
            $this->model->addReceitaToSecao($secao_id, $receita_id);
        }

        return $secao_id;
    }
}