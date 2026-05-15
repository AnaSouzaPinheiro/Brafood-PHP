<?php

/**
 * Serviço responsável pelo diff de ingredientes e etapas de uma receita.
 *
 * Recebe o estado atual (vindo do banco) e o novo estado (vindo do payload)
 * e aplica apenas as diferenças: cria o que é novo, remove o que foi apagado,
 * ignora o que não mudou.
 *
 * Extraído de UpdatingRecipeCtl para separar responsabilidades e facilitar testes.
 */

namespace App\Service;

use App\Model\UpdatingRecipeModel;
use App\Model\AddingRecipeModel;
use App\Model\ReceitaModel;

class ReceitaDiffService
{
    private UpdatingRecipeModel $updating;
    private AddingRecipeModel   $adding;

    public function __construct(UpdatingRecipeModel $updating, AddingRecipeModel $adding)
    {
        $this->updating = $updating;
        $this->adding   = $adding;
    }

    // -------------------------------------------------------------------------
    // Subcategoria
    // -------------------------------------------------------------------------

    public function diffSubcategoria(int $idReceita, array $atuais, array $novas): void 
    {
        sort($atuais);
        sort($novas);

        if ($atuais === $novas) {
            return;
        }

        $this->updating->removerSubcategorias($idReceita);

        foreach ($novas as $idSubcategoria) {
            $this->updating->vincularSubcategoria($idReceita, $idSubcategoria);
        }
    }

    // -------------------------------------------------------------------------
    // Ingredientes
    // -------------------------------------------------------------------------

    /**
     * @param int   $idReceita
     * @param array $gruposAtuais  Saída de ReceitaModel::getIngredientes()
     * @param array $gruposNovos   Array de ['titulo' => string, 'itens' => string[]]
     */
    public function diffIngredientes(int $idReceita, array $gruposAtuais, array $gruposNovos): void
    {
        // Indexa grupos atuais por título
        $mapa = [];
        foreach ($gruposAtuais as $g) {
            $titulo        = ($g['titulo'] ?? '') !== '' ? $g['titulo'] : 'Sem grupo';
            $mapa[$titulo] = [
                'id_item' => $g['id_item'],
                'itens'   => isset($g['itens']) ? array_unique($g['itens']) : [],
            ];
        }

        $titulosNovos = [];

        foreach ($gruposNovos as $grupo) {
            $titulo       = ($grupo['titulo'] ?? '') !== '' ? $grupo['titulo'] : 'Sem grupo';
            $titulosNovos[] = $titulo;
            $itensNovos   = isset($grupo['itens']) && is_array($grupo['itens'])
                ? array_unique($grupo['itens'])
                : [];

            if (isset($mapa[$titulo])) {
                $idItem      = $mapa[$titulo]['id_item'];
                $itensAtuais = $mapa[$titulo]['itens'];
            } else {
                $idItem      = $this->adding->criarItemIngrediente($idReceita, $titulo);
                $itensAtuais = [];
                $mapa[$titulo] = ['id_item' => $idItem, 'itens' => []];
            }

            foreach ($itensNovos as $desc) {
                if (!in_array($desc, $itensAtuais)) {
                    $this->adding->criarIngrediente($desc, $idItem);
                    $itensAtuais[] = $desc;
                }
            }

            foreach ($itensAtuais as $descBanco) {
                if (!in_array($descBanco, $itensNovos)) {
                    $this->updating->removerIngrediente($descBanco, $idItem);
                }
            }
        }

        // Remove grupos inteiramente apagados
        foreach ($mapa as $titulo => $grupo) {
            if (!in_array($titulo, $titulosNovos)) {
                foreach ($grupo['itens'] as $desc) {
                    $this->updating->removerIngrediente($desc, $grupo['id_item']);
                }
                $this->updating->removerItemIngrediente($grupo['id_item']);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Etapas
    // -------------------------------------------------------------------------

    /**
     * @param int   $idReceita
     * @param array $etapasAtuais  Saída de ReceitaModel::getEtapas()
     * @param array $etapasNovas   Array de ['titulo', 'ordem', 'descricoes' => [...]]
     * @return int[]  IDs das etapas que foram mantidas/criadas (usado para remoção das obsoletas)
     */
    public function diffEtapas(int $idReceita, array $etapasAtuais, array $etapasNovas): array
    {
        $idsMantidasOuCriadas = [];

        foreach ($etapasNovas as $etapa) {
            $titulo     = $etapa['titulo']    ?? '';
            $ordem      = $etapa['ordem']     ?? 0;
            $descricoes = $etapa['descricoes'] ?? [];

            // Encontra etapa atual pelo título
            $etapaAtual = null;
            foreach ($etapasAtuais as $e) {
                if ($e['titulo'] === $titulo) {
                    $etapaAtual = $e;
                    break;
                }
            }

            if ($etapaAtual) {
                $idEtapa = $etapaAtual['id_secao_preparo'];

                if ($titulo !== $etapaAtual['titulo'] || $ordem != $etapaAtual['ordem']) {
                    $this->updating->editarEtapa($idEtapa, [
                        'titulo' => $titulo,
                        'ordem'  => $ordem,
                    ]);
                }

                $descricoesAtuais = array_unique(
                    array_map(fn($d) => $d['descricao'], $etapaAtual['descricoes'] ?? [])
                );

                foreach ($descricoes as $desc) {
                    $texto = $desc['descricao'] ?? '';
                    if ($texto && !in_array($texto, $descricoesAtuais)) {
                        $this->adding->criarDescricaoEtapa($idEtapa, $desc);
                        $descricoesAtuais[] = $texto;
                    }
                }

                foreach ($descricoesAtuais as $descBanco) {
                    if (!in_array($descBanco, array_column($descricoes, 'descricao'))) {
                        $this->updating->removerDescricaoEtapa($idEtapa, $descBanco);
                    }
                }
            } else {
                $idEtapa = $this->adding->criarEtapa($idReceita, $etapa);
                foreach ($descricoes as $desc) {
                    $this->adding->criarDescricaoEtapa($idEtapa, $desc);
                }
            }

            $idsMantidasOuCriadas[] = $idEtapa;
        }

        // Remove etapas que foram apagadas
        foreach ($etapasAtuais as $etapa) {
            if (!in_array($etapa['id_secao_preparo'], $idsMantidasOuCriadas)) {
                $this->updating->removerEtapa($etapa['id_secao_preparo']);
            }
        }

        return $idsMantidasOuCriadas;
    }
}
