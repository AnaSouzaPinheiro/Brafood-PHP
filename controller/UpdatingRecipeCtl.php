<?php

/**
 * Controla a edição de receitas existentes.
 * Faz diff entre estado atual do banco e dados enviados para
 * criar, atualizar ou remover: dados principais, subcategoria,
 * grupos de ingredientes e etapas de preparo.
 */

require_once __DIR__ . '/../model/UpdatingRecipeModel.php';
require_once __DIR__ . '/../model/AddingRecipeModel.php';
require_once __DIR__ . '/../model/ReceitaModel.php';

class UpdatingRecipeCtl
{
    private UpdatingRecipeModel $model;
    private AddingRecipeModel   $addRecipeModel;
    private ReceitaModel        $receitaModel;

    public function __construct($db)
    {
        $this->model          = new UpdatingRecipeModel($db);
        $this->addRecipeModel = new AddingRecipeModel($db);
        $this->receitaModel   = new ReceitaModel($db);
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

            // 2. Subcategoria — cria, atualiza ou remove conforme mudança
            $subcategoriaAtual = $this->receitaModel->getSubcategoria($id);
            $novaSubcategoria  = $dados['subcategoria'] ?? null;

            if ($subcategoriaAtual && !$novaSubcategoria) {
                $this->model->removerSubcategorias($id);
            } elseif (!$subcategoriaAtual && $novaSubcategoria) {
                $this->model->vincularSubcategoria($id, $novaSubcategoria);
            } elseif ($subcategoriaAtual && $novaSubcategoria && $subcategoriaAtual !== $novaSubcategoria) {
                $this->model->removerSubcategorias($id);
                $this->model->vincularSubcategoria($id, $novaSubcategoria);
            }

            // 3. Ingredientes — diff por título de grupo
            $ingredientesAtuais = $this->receitaModel->getIngredientes($id);
            $mapaGruposAtuais   = [];

            foreach ($ingredientesAtuais as $g) {
                $titulo = isset($g['titulo']) && $g['titulo'] !== '' ? $g['titulo'] : 'Sem grupo';
                $mapaGruposAtuais[$titulo] = [
                    'id_item' => $g['id_item'],
                    'itens'   => isset($g['itens']) ? array_unique($g['itens']) : []
                ];
            }

            $titulosNovos = [];
            foreach ($dados['ingredientes'] as $grupo) {
                $tituloGrupo  = isset($grupo['titulo']) && $grupo['titulo'] !== '' ? $grupo['titulo'] : 'Sem grupo';
                $titulosNovos[] = $tituloGrupo;
                $itensGrupo   = isset($grupo['itens']) && is_array($grupo['itens']) ? array_unique($grupo['itens']) : [];

                if (isset($mapaGruposAtuais[$tituloGrupo])) {
                    $idItem      = $mapaGruposAtuais[$tituloGrupo]['id_item'];
                    $itensAtuais = $mapaGruposAtuais[$tituloGrupo]['itens'];
                } else {
                    $idItem      = $this->addRecipeModel->criarItemIngrediente($id, $tituloGrupo);
                    $itensAtuais = [];
                    $mapaGruposAtuais[$tituloGrupo] = ['id_item' => $idItem, 'itens' => []];
                }

                foreach ($itensGrupo as $descricao) {
                    if (!in_array($descricao, $itensAtuais)) {
                        $this->addRecipeModel->criarIngrediente($descricao, $idItem);
                        $itensAtuais[] = $descricao;
                    }
                }

                foreach ($itensAtuais as $descBanco) {
                    if (!in_array($descBanco, $itensGrupo)) {
                        $this->model->removerIngrediente($descBanco, $idItem);
                    }
                }
            }

            // Remove grupos que foram apagados
            foreach ($mapaGruposAtuais as $titulo => $grupo) {
                if (!in_array($titulo, $titulosNovos)) {
                    foreach ($grupo['itens'] as $desc) {
                        $this->model->removerIngrediente($desc, $grupo['id_item']);
                    }
                    $this->model->removerItemIngrediente($grupo['id_item']);
                }
            }

            // 4. Etapas — diff por título de etapa
            $etapasAtuais   = $this->receitaModel->getEtapas($id);
            $idsEtapasNovas = [];

            foreach ($dados['etapas'] as $etapa) {
                $tituloEtapa = $etapa['titulo'] ?? '';
                $ordemEtapa  = $etapa['ordem']  ?? 0;
                $descricoes  = $etapa['descricoes'] ?? [];

                $etapaAtual = null;
                foreach ($etapasAtuais as $e) {
                    if ($e['titulo'] === $tituloEtapa) {
                        $etapaAtual = $e;
                        break;
                    }
                }

                if ($etapaAtual) {
                    $idEtapa = $etapaAtual['id_secao_preparo'];

                    if ($tituloEtapa !== $etapaAtual['titulo'] || $ordemEtapa != $etapaAtual['ordem']) {
                        $this->model->editarEtapa($idEtapa, [
                            'titulo' => $tituloEtapa,
                            'ordem'  => $ordemEtapa
                        ]);
                    }

                    $descricoesAtuais = array_unique(
                        array_map(fn($d) => $d['descricao'], $etapaAtual['descricoes'] ?? [])
                    );

                    foreach ($descricoes as $desc) {
                        $descricaoText = $desc['descricao'] ?? '';
                        if ($descricaoText && !in_array($descricaoText, $descricoesAtuais)) {
                            $this->addRecipeModel->criarDescricaoEtapa($idEtapa, $desc);
                            $descricoesAtuais[] = $descricaoText;
                        }
                    }

                    foreach ($descricoesAtuais as $descBanco) {
                        if (!in_array($descBanco, array_column($descricoes, 'descricao'))) {
                            $this->model->removerDescricaoEtapa($idEtapa, $descBanco);
                        }
                    }
                } else {
                    $idEtapa = $this->addRecipeModel->criarEtapa($id, $etapa);
                    foreach ($descricoes as $desc) {
                        $this->addRecipeModel->criarDescricaoEtapa($idEtapa, $desc);
                    }
                }

                $idsEtapasNovas[] = $idEtapa;
            }

            // Remove etapas que foram apagadas
            foreach ($etapasAtuais as $etapa) {
                if (!in_array($etapa['id_secao_preparo'], $idsEtapasNovas)) {
                    $this->model->removerEtapa($etapa['id_secao_preparo']);
                }
            }

            Logger::info('receitas/update', 'Receita editada com sucesso', [
                'usuario_id' => $idUsuario,
                'receita_id' => $id
            ]);

            return ['success' => true, 'message' => 'Receita editada com sucesso!', 'id' => $id];

        } catch (Exception $e) {
            Logger::error('receitas/update', 'Erro ao editar receita', [
                'usuario_id' => $idUsuario,
                'receita_id' => $id,
                'error'       => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
