<?php

/**
 * Controla a edição de receitas existentes.
 * Orquestra: atualização dos dados principais e delegação do diff de
 * subcategoria, ingredientes e etapas ao ReceitaDiffService.
 */

namespace App\Controller;

use App\Service\ReceitaDiffService;
use App\Model\UpdatingRecipeModel;
use App\Model\AddingRecipeModel;
use App\Model\ReceitaModel;
use App\Config\Logger;
use Exception;

class UpdatingRecipeCtl
{
    private UpdatingRecipeModel $model;
    private ReceitaModel        $receitaModel;
    private ReceitaDiffService  $diffService;

    public function __construct()
    {
        $updating           = new UpdatingRecipeModel();
        $adding             = new AddingRecipeModel();

        $this->model        = $updating;
        $this->receitaModel = new ReceitaModel();
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
                $dados['idSubcategoria'] ?? null
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
