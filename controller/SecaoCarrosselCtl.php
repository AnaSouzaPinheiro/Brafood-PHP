<?php

/**
 * Controla as seções de carrossel exibidas na home.
 * Cada seção tem um título, imagem de fundo e uma lista de receitas vinculadas.
 */

require_once __DIR__ . '/../model/SecaoCarrossel.php';

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

        foreach ($secoes as &$secao) {
            $secao['receitas'] = $this->model->getReceitasDaSecao($secao);
        }

        return $secoes;
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
