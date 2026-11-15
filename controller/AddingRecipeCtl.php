<?php

/**
 * Controla a criação de novas receitas.
 * Orquestra: dados principais, subcategoria, grupos de ingredientes e etapas de preparo.
 */

namespace App\Controller;

use App\Model\AddingRecipeModel;
use App\Config\Logger;
use Exception;

class AddingRecipeCtl
{
    private AddingRecipeModel $addRecipe;

    public function __construct(?AddingRecipeModel $addRecipe = null)
    {
        $this->addRecipe = $addRecipe ?? new AddingRecipeModel();
    }

    public function adicionarReceita(array $dados, ?string $imagem1, array $galeria = []): array
    {
        if (empty($dados['titulo']) || empty($dados['categoria'])) {
            return ['success' => false, 'message' => 'Título e categoria são obrigatórios.'];
        }

        $idUsuario = $dados['autor'] ?? null;

        try {
            $idReceita = $this->addRecipe->criarReceita($dados, $imagem1, $galeria);

            // Subcategoria
            $nomeSubcategoria = $dados['subcategoria'] ?? '';
            if ($nomeSubcategoria) {
                $this->addRecipe->vincularSubcategoria($idReceita, $nomeSubcategoria);
            } else {
                Logger::warn('receitas/add', 'Receita criada sem subcategoria', [
                    'receita_id' => $idReceita
                ]);
            }

            // Grupos de ingredientes
            foreach ($dados['ingredientes'] ?? [] as $grupo) {
                $idItem = $this->addRecipe->criarItemIngrediente($idReceita, $grupo['titulo']);
                foreach ($grupo['itens'] ?? [] as $ingrediente) {
                    $this->addRecipe->criarIngrediente($ingrediente, $idItem);
                }
            }

            // Etapas de preparo
            foreach ($dados['etapas'] ?? [] as $etapa) {
                $idEtapa = $this->addRecipe->criarEtapa($idReceita, $etapa);
                foreach ($etapa['descricoes'] ?? [] as $desc) {
                    $this->addRecipe->criarDescricaoEtapa($idEtapa, $desc);
                }
            }

            Logger::info('receitas/add', 'Receita criada com sucesso', [
                'usuario_id' => $idUsuario,
                'receita_id' => $idReceita,
                'titulo'     => $dados['titulo'] ?? '?'
            ]);

            return ['success' => true, 'message' => 'Receita criada com sucesso!', 'id' => $idReceita];

        } catch (Exception $e) {
            Logger::error('receitas/add', 'Erro ao criar receita', [
                'usuario_id' => $idUsuario,
                'error'       => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
