<?php
require_once __DIR__ . '/../model/UpdatingRecipeModel.php';
require_once __DIR__ . '/../model/AddingRecipeModel.php';
require_once __DIR__ . '/../model/ReceitaModel.php';

class UpdatingRecipeCtl
{
    private $model, $addRecipeModel, $receitaModel;

    public function __construct($db)
    {
        $this->model = new UpdatingRecipeModel($db);
        $this->addRecipeModel = new AddingRecipeModel($db);
        $this->receitaModel = new ReceitaModel($db);
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
            $imagens['imagem1'] ?? null,
            $imagens['imagem2'] ?? null,
            $imagens['imagem3'] ?? null
        );

        // 2. Subcategoria — criar, atualizar ou remover
        $subcategoriaAtual = $this->receitaModel->getSubcategoria($id); // retorna nome ou null
        $novaSubcategoria = $dados['subcategoria'] ?? null;
        
        if ($subcategoriaAtual && !$novaSubcategoria) {
            // tinha antes, agora não → remove
            $this->model->removerSubcategorias($id);
        } else if (!$subcategoriaAtual && $novaSubcategoria) {
            // não tinha antes, agora tem → cria
            $this->model->vincularSubcategoria($id, $novaSubcategoria);
        } else if ($subcategoriaAtual && $novaSubcategoria && $subcategoriaAtual !== $novaSubcategoria) {
            // mudou → atualiza
            $this->model->removerSubcategorias($id);
            $this->model->vincularSubcategoria($id, $novaSubcategoria);
        }

        // 3. Ingredientes — criar, atualizar ou remover
        $ingredientesAtuais = $this->receitaModel->getIngredientes($id);
        $mapaGruposAtuais = [];

        foreach ($ingredientesAtuais as $g) {
            $titulo = isset($g['titulo']) && $g['titulo'] !== '' ? $g['titulo'] : 'Sem grupo';
            $mapaGruposAtuais[$titulo] = [
                'id_item' => $g['id_item'],
                'itens' => isset($g['itens']) ? array_unique($g['itens']) : []
            ];
        }

        $titulosNovos = [];
        foreach ($dados['ingredientes'] as $grupo) {
            $tituloGrupo = isset($grupo['titulo']) && $grupo['titulo'] !== '' ? $grupo['titulo'] : 'Sem grupo';
            $titulosNovos[] = $tituloGrupo;
            $itensGrupo = isset($grupo['itens']) && is_array($grupo['itens']) ? array_unique($grupo['itens']) : [];

            if (isset($mapaGruposAtuais[$tituloGrupo])) {
                $idItem = $mapaGruposAtuais[$tituloGrupo]['id_item'];
                $itensAtuais = $mapaGruposAtuais[$tituloGrupo]['itens'];
            } else {
                $idItem = $this->addRecipeModel->criarItemIngrediente($id, $tituloGrupo);
                $itensAtuais = [];
                $mapaGruposAtuais[$tituloGrupo] = ['id_item' => $idItem, 'itens' => []];
            }

            // Criar itens novos
            foreach ($itensGrupo as $descricao) {
                if (!in_array($descricao, $itensAtuais)) {
                    $this->addRecipeModel->criarIngrediente($descricao, $idItem);
                    $itensAtuais[] = $descricao;
                }
            }

            // Remover itens apagados
            foreach ($itensAtuais as $descBanco) {
                if (!in_array($descBanco, $itensGrupo)) {
                    $this->model->removerIngrediente($descBanco, $idItem);
                }
            }
        }

        // Remover grupos apagados
        foreach ($mapaGruposAtuais as $titulo => $grupo) {
            if (!in_array($titulo, $titulosNovos)) {
                // remove todos os itens do grupo
                foreach ($grupo['itens'] as $desc) {
                    $this->model->removerIngrediente($desc, $grupo['id_item']);
                }
                // remove o grupo
                $this->model->removerItemIngrediente($grupo['id_item']);
            }
        }

        // 4. Etapas — criar, atualizar ou remover
        $etapasAtuais = $this->receitaModel->getEtapas($id); // cada etapa tem id_secao_preparo, titulo, descricoes[]
        $idsEtapasNovas = [];

        foreach ($dados['etapas'] as $etapa) {
            $tituloEtapa = $etapa['titulo'] ?? '';
            $ordemEtapa = $etapa['ordem'] ?? 0;
            $descricoes = $etapa['descricoes'] ?? [];

            // Busca se existe etapa atual com mesmo título
            $etapaAtual = null;
            foreach ($etapasAtuais as $e) {
                if ($e['titulo'] === $tituloEtapa) {
                    $etapaAtual = $e;
                    break;
                }
            }

            if ($etapaAtual) {
                $idEtapa = $etapaAtual['id_secao_preparo'];

                // Atualiza etapa só se mudou título ou ordem
                if ($tituloEtapa !== $etapaAtual['titulo'] || $ordemEtapa != $etapaAtual['ordem']) {
                    $this->model->editarEtapa($idEtapa, [
                        'titulo' => $tituloEtapa,
                        'ordem' => $ordemEtapa
                    ]);
                }

                // Normaliza descrições atuais (remove duplicadas)
                $descricoesAtuais = array_unique(array_map(fn($d) => $d['descricao'], $etapaAtual['descricoes'] ?? []));

                // Cria novas descrições se ainda não existem
                foreach ($descricoes as $desc) {
                    $descricaoText = $desc['descricao'] ?? '';
                    if ($descricaoText && !in_array($descricaoText, $descricoesAtuais)) {
                        $this->addRecipeModel->criarDescricaoEtapa($idEtapa, $desc);
                        $descricoesAtuais[] = $descricaoText; // adiciona para não duplicar novamente
                    }
                }

                // Remove descrições apagadas
                foreach ($descricoesAtuais as $descBanco) {
                    if (!in_array($descBanco, array_column($descricoes, 'descricao'))) {
                        $this->model->removerDescricaoEtapa($idEtapa, $descBanco);
                    }
                }
            } else {
                // Cria nova etapa + descrições
                $idEtapa = $this->addRecipeModel->criarEtapa($id, $etapa);
                foreach ($descricoes as $desc) {
                    $this->addRecipeModel->criarDescricaoEtapa($idEtapa, $desc);
                }
            }

            $idsEtapasNovas[] = $idEtapa;
        }

        // Remove etapas apagadas
        foreach ($etapasAtuais as $etapa) {
            if (!in_array($etapa['id_secao_preparo'], $idsEtapasNovas)) {
                $this->model->removerEtapa($etapa['id_secao_preparo']);
            }
        }

        // Remover etapas apagadas
        foreach ($etapasAtuais as $etapa) {
            if (!in_array($etapa['id_secao_preparo'], $idsEtapasNovas)) {
                $this->model->removerEtapa($etapa['id_secao_preparo']);
            }
        }

        return [
            "sucesso" => true,
            "mensagem" => "Receita editada com sucesso!",
            "id" => $id
        ];
    }
}
