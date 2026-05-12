<?php

/**
 * Controla a edição de receitas existentes.
 * Orquestra: atualização dos dados principais e delegação do diff de
 * subcategoria, ingredientes e etapas ao ReceitaDiffService.
 */

require_once __DIR__ . '/../model/UpdatingRecipeModel.php';
require_once __DIR__ . '/../model/AddingRecipeModel.php';
require_once __DIR__ . '/../model/ReceitaModel.php';
require_once __DIR__ . '/../service/ReceitaDiffService.php';

class UpdatingRecipeCtl
{
    private UpdatingRecipeModel $model;
    private ReceitaModel        $receitaModel;
    private ReceitaDiffService  $diffService;

    public function __construct($db)
    {
        $updating           = new UpdatingRecipeModel($db);
        $adding             = new AddingRecipeModel($db);

        $this->model        = $updating;
        $this->receitaModel = new ReceitaModel($db);
        $this->diffService  = new ReceitaDiffService($updating, $adding);
    }

    public function editarReceita(array $dados, array $imagens): array
    {
        $id        = (int) $dados['id'];
        $idUsuario = $dados['id_usuario'] ?? null;

        try {
            // 1. Dados principais
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
                $imagens['imagem1'] ?? null,
                $imagens['imagem2'] ?? null,
                $imagens['imagem3'] ?? null
            );

            // 2. Subcategoria
            $this->diffService->diffSubcategoria(
                $id,
                $this->receitaModel->getSubcategoria($id),
                $dados['subcategoria'] ?? null
            );

            // 3. Ingredientes
            $this->diffService->diffIngredientes(
                $id,
                $this->receitaModel->getIngredientes($id),
                $dados['ingredientes'] ?? []
            );

            // 4. Etapas
            $this->diffService->diffEtapas(
                $id,
                $this->receitaModel->getEtapas($id),
                $dados['etapas'] ?? []
            );

            Logger::info('receitas/update', 'Receita editada com sucesso', [
                'usuario_id' => $idUsuario,
                'receita_id' => $id,
            ]);

            return ['success' => true, 'message' => 'Receita editada com sucesso!', 'id' => $id];

        } catch (Exception $e) {
            Logger::error('receitas/update', 'Erro ao editar receita', [
                'usuario_id' => $idUsuario,
                'receita_id' => $id,
                'error'       => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
