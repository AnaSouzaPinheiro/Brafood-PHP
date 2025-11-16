<?php
require_once __DIR__ . '/../model/UpdatingReceitaModel.php';

class UpdatingRecipeCtl
{
    private $model;

    public function __construct($db)
    {
        $this->model = new UpdatingRecipeModel($db);
    }

    public function editarReceita($dados, $imagens)
    {
        $id = (int) $dados['id'];

        // 1. Atualiza os dados principais da receita
        $this->model->updateReceita(
            $id,
            $dados['id_usuario'],
            $dados['titulo'],
            $dados['resumo'],
            $dados['dificuldade'],
            $dados['tempo_hora'],
            $dados['tempo_min'],
            $dados['porcoes'],
            $dados['tipo_porcao'],
            $imagens['imagem1'],
            $imagens['imagem2'],
            $imagens['imagem3']
        );

        // 2. Subcategoria
        if (!empty($dados['subcategoria'])) {
            // remove vínculos antigos
            $this->model->removerSubcategorias($id);

            // cria o novo vínculo
            $this->model->vincularSubcategoria($id, $dados['subcategoria']);
        }

        // 3. Ingredientes — apagar tudo e recriar
        $this->model->removerIngredientes($id);

        foreach ($dados['ingredientes'] as $grupo) {

            // criar grupo
            $idItem = $this->model->criarItemIngrediente($id, $grupo['titulo']);

            // criar itens do grupo
            foreach ($grupo['itens'] as $descricao) {
                $this->model->criarIngrediente($idItem, $descricao);
            }
        }

        // 4. Etapas — apagar tudo e recriar
        $this->model->removerEtapas($id);

        foreach ($dados['etapas'] as $etapa) {

            $idEtapa = $this->model->criarEtapa(
                $id,
                $etapa['titulo'],
                $etapa['ordem']
            );

            foreach ($etapa['descricoes'] as $desc) {
                $this->model->criarDescricaoEtapa(
                    $idEtapa,
                    $desc['descricao'],
                    $desc['ordem']
                );
            }
        }

        return [
            "sucesso" => true,
            "mensagem" => "Receita editada com sucesso!",
            "id" => $id
        ];
    }
}