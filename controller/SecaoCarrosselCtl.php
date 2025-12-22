<?php
require_once __DIR__ . '/../model/SecaoCarrossel.php';

class SecaoCarrosselCtl
{
    private $model;

    public function __construct()
    {
        $this->model = new SecaoCarrossel();
    }

    // Pega todas as seções com suas receitas
    public function index()
    {
        $secoes = $this->model->getAllSecoes();

        foreach ($secoes as &$secao) {
            $secao['receitas'] = $this->model->getReceitasDaSecao($secao);
        }

        return $secoes;
    }

    // Criar seção + vincular receitas
    public function create($data)
    {
        $secao_id = $this->model->createSecao(
            $data['titulo'],
            $data['url_fundo'] ?? null,
            $data['fundo_titulo'] ?? null,
            $data['ordem'] ?? 0
        );

        if (!empty($data['receitas'])) {
            foreach ($data['receitas'] as $receita_id) {
                $this->model->addReceitaToSecao($secao_id, $receita_id);
            }
        }

        return $secao_id;
    }

    // Atualiza uma seção (opcional)
    public function update($id, $data)
    {
        $this->model->updateSecao(
            $id,
            $data['titulo'] ?? null,
            $data['url_fundo'] ?? null,
            $data['fundo_titulo'] ?? null,
            $data['ordem'] ?? null
        );

        if (isset($data['receitas'])) {
            $this->model->clearReceitasFromSecao($id); // limpa vínculos antigos
            foreach ($data['receitas'] as $receita_id) {
                $this->model->addReceitaToSecao($id, $receita_id);
            }
        }
    }

    // Deletar seção
    public function delete($id)
    {
        $this->model->deleteSecao($id);
    }
}

/*$ctl = new SecaoCarrosselCtl();
header('Content-Type: application/json');
echo json_encode($ctl->index());
*/