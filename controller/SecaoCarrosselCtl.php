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
use App\Service\HomeRotacaoService;

class SecaoCarrosselCtl
{
    private $model;

    public function __construct(?SecaoCarrossel $model = null)
    {
        $this->model = $model ?? new SecaoCarrossel();
    }

    /** Mínimo de receitas para uma seção automática aparecer */
    private const MIN_RECEITAS_AUTO = 4;
    private const LIMITE_AUTO = 8;

    public function index(): array
    {
        $semente = HomeRotacaoService::sementeDiaria();

        // ── Seções cadastradas, só as que estão no período ativo ─────────────
        $secoes = array_values(array_filter(
            $this->model->getAllSecoes(),
            fn($s) => HomeRotacaoService::estaNoPeriodo($s['ativo_de'] ?? null, $s['ativo_ate'] ?? null)
        ));

        // ── Seções automáticas (entram na rotação junto com as cadastradas) ──
        $secoes = array_merge($secoes, $this->secoesAutomaticas());

        // ── Atribui prioridade numérica a cada seção ──────────────────────────
        // 1 = manual | 2 = aleatorio com filtro | 3 = automática | 4 = aleatorio sem filtro
        foreach ($secoes as &$secao) {
            $secao['_prioridade'] = $this->calcularPrioridade($secao);
        }
        unset($secao);

        // ── Ordena por prioridade (menor número = escolhe receitas primeiro) ──
        usort($secoes, function ($a, $b) {
            if ($a['_prioridade'] !== $b['_prioridade']) {
                return $a['_prioridade'] <=> $b['_prioridade'];
            }
            return ($a['ordem'] ?? 0) <=> ($b['ordem'] ?? 0);
        });

        // ── Popula receitas sem repetir entre seções ──────────────────────────
        $idsUsados = [];

        foreach ($secoes as &$secao) {
            $receitas = isset($secao['_auto'])
                ? $this->model->getReceitasAuto(
                    $secao['_auto'],
                    self::LIMITE_AUTO,
                    $idsUsados,
                    $secao['id_categoria'] ?? null,
                    $semente
                )
                : $this->model->getReceitasDaSecao($secao, $idsUsados, $semente);

            // Automática com poucas receitas não aparece
            if (isset($secao['_auto']) && count($receitas) < self::MIN_RECEITAS_AUTO) {
                $receitas = [];
            }

            foreach ($receitas as $r) {
                $idsUsados[] = (int) $r['id'];
            }

            $secao['receitas'] = $receitas;
            unset($secao['_prioridade'], $secao['_auto']);
        }
        unset($secao);

        $secoes = array_values(array_filter($secoes, fn($s) => !empty($s['receitas'])));

        // ── Ordem de exibição ─────────────────────────────────────────────────
        // Fixadas primeiro (pela ordem cadastrada); o resto embaralha por dia.
        $fixadas = array_values(array_filter($secoes, fn($s) => HomeRotacaoService::estaFixada($s['fixar_ate'] ?? null)));
        $demais  = array_values(array_filter($secoes, fn($s) => !HomeRotacaoService::estaFixada($s['fixar_ate'] ?? null)));

        usort($fixadas, fn($a, $b) => ($a['ordem'] ?? 0) <=> ($b['ordem'] ?? 0));

        return array_merge($fixadas, HomeRotacaoService::embaralhar($demais, $semente));
    }

    /**
     * Seções geradas automaticamente. IDs negativos para não colidir com
     * as cadastradas. A "categoria da semana" muda toda segunda-feira.
     */
    private function secoesAutomaticas(): array
    {
        $base = [
            'url_fundo'          => null,
            'fundo_titulo'       => null,
            'id_subcategoria'    => null,
            'modo'               => 'aleatorio',
            'ordem'              => 999,
            'fixar_ate'          => null,
            'titulo_cor_tipo'    => 'gradiente',
            'titulo_cor_solid'   => null,
            'titulo_gradiente_1' => '#8aabff',
            'titulo_gradiente_2' => '#0049ff',
            'card_exibir'        => 'categoria',
        ];

        $secoes = [
            $base + ['id' => -1, '_auto' => 'novidades',        'titulo' => 'Novidades no BraFood', 'cor_fundo' => '#EEF3FF', 'id_categoria' => null],
            $base + ['id' => -2, '_auto' => 'mais_favoritadas', 'titulo' => 'As mais favoritadas',  'cor_fundo' => '#F4F7FF', 'id_categoria' => null],
            $base + ['id' => -3, '_auto' => 'mais_avaliadas',   'titulo' => 'Mais bem avaliadas',   'cor_fundo' => '#E8EEFF', 'id_categoria' => null],
        ];

        $categorias = $this->model->categoriasComReceitas(self::MIN_RECEITAS_AUTO);
        if ($categorias) {
            $cat = $categorias[HomeRotacaoService::sementeSemanal() % count($categorias)];
            $secoes[] = $base + [
                'id'           => -4,
                '_auto'        => 'categoria',
                'titulo'       => 'Categoria da semana: ' . $cat['nome'],
                'cor_fundo'    => '#F0F4FF',
                'id_categoria' => (int) $cat['id'],
            ];
        }

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

        if (isset($secao['_auto'])) {
            return 3;
        }

        $temCategoria    = !empty($secao['id_categoria']);
        $temSubcategoria = !empty($secao['id_subcategoria']);

        if ($temCategoria || $temSubcategoria) {
            return 2;
        }

        return 4;
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