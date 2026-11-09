<?php

/**
 * Controla os quadros de receitas salvas do usuário.
 * O quadro "Favoritos" é virtual: é a própria tabela `favoritos`.
 * Salvar num quadro também favorita a receita (mantém qtd_fav correto).
 */

namespace App\Controller;

use App\Model\QuadroModel;
use App\Model\FavoritoModel;
use App\Config\Logger;
use Exception;

class QuadroCtl
{
    private $model;
    private $favoritoModel;

    public const NOME_MAX = 60;
    public const DESCRICAO_MAX = 255;

    public function __construct()
    {
        $this->model = new QuadroModel();
        $this->favoritoModel = new FavoritoModel();
    }

    public function listar(int $idUser): array
    {
        return [
            'favoritos' => $this->model->resumoFavoritos($idUser),
            'quadros'   => $this->model->listByUser($idUser),
        ];
    }

    public function detalhe(int $idUser, int $idQuadro): ?array
    {
        $quadro = $this->model->find($idUser, $idQuadro);
        if (!$quadro) return null;
        return ['quadro' => $quadro, 'receitas' => $this->model->listReceitas($idUser, $idQuadro)];
    }

    // ---- Fase 3: compartilhar, ordenar, lista de compras -------------------

    public function definirPublico(int $idUser, int $idQuadro, bool $publico): array
    {
        $token = $this->model->setPublico($idUser, $idQuadro, $publico);
        if ($token === null) return ['success' => false, 'error' => 'Quadro não encontrado'];

        Logger::info('quadro', $publico ? 'Quadro publicado' : 'Quadro tornado privado', [
            'usuario_id' => $idUser, 'quadro_id' => $idQuadro,
        ]);
        return ['success' => true, 'publico' => $publico, 'token' => $token];
    }

    /** Visão pública (sem login) de um quadro compartilhado. */
    public function publico(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{16}$/', $token)) return null;
        $q = $this->model->findPublico($token);
        if (!$q) return null;

        return [
            'quadro' => ['nome' => $q['nome'], 'descricao' => $q['descricao']],
            'autor'  => [
                'nome'      => $q['autor_nome'],
                'apelido'   => $q['autor_apelido'],
                'foto'      => $q['autor_foto'],
            ],
            'receitas' => $this->model->listReceitasPublicas($q['id']),
        ];
    }

    public function publicosDoUsuario(int $idUser): array
    {
        return $this->model->listPublicosDoUsuario($idUser);
    }

    public function reordenarQuadros(int $idUser, array $ids): array
    {
        $this->model->reordenarQuadros($idUser, $this->normalizarIds($ids));
        return ['success' => true];
    }

    public function reordenarReceitas(int $idUser, int $idQuadro, array $ids): array
    {
        if (!$this->model->find($idUser, $idQuadro)) return ['success' => false, 'error' => 'Quadro não encontrado'];
        $this->model->reordenarReceitas($idQuadro, $this->normalizarIds($ids));
        return ['success' => true];
    }

    public function listaDeCompras(int $idUser, int $idQuadro, array $idsReceitas): ?array
    {
        if (!$this->model->find($idUser, $idQuadro)) return null;
        return $this->model->ingredientes($idQuadro, $this->normalizarIds($idsReceitas));
    }

    public function estadoDaReceita(int $idUser, int $idReceita): array
    {
        $extras = $this->favoritoModel->getExtras($idUser, $idReceita);
        return [
            'favorito' => $extras !== null,
            'status'   => $extras['status'] ?? null,
            'nota'     => $extras['nota'] ?? null,
            'quadros'  => $this->model->quadrosDaReceita($idUser, $idReceita),
        ];
    }

    /** Valida nome/descrição; devolve [nome, descricao] ou string de erro. */
    private function validar($nome, $descricao)
    {
        $nome = trim((string) $nome);
        $descricao = trim((string) ($descricao ?? ''));

        if ($nome === '') return 'Dê um nome ao quadro';
        if (mb_strlen($nome) > self::NOME_MAX) return 'Nome muito longo (máx. ' . self::NOME_MAX . ' caracteres)';
        if (mb_strlen($descricao) > self::DESCRICAO_MAX) return 'Descrição muito longa';

        return [$nome, $descricao === '' ? null : $descricao];
    }

    /**
     * Cria o quadro. $idsReceitas permite já começar com receitas
     * (ex.: as salvas de uma categoria, escolhidas no seletor).
     */
    public function criar(int $idUser, $nome, $descricao, array $idsReceitas = []): array
    {
        $v = $this->validar($nome, $descricao);
        if (is_string($v)) return ['success' => false, 'error' => $v];

        try {
            $id = $this->model->create($idUser, $v[0], $v[1]);
            foreach ($this->normalizarIds($idsReceitas) as $idReceita) {
                $this->adicionarReceita($idUser, $id, $idReceita);
            }

            Logger::info('quadro', 'Quadro criado', ['usuario_id' => $idUser, 'quadro_id' => $id]);
            return ['success' => true, 'id' => $id];
        } catch (Exception $e) {
            Logger::error('quadro', 'Erro ao criar quadro', ['usuario_id' => $idUser, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Erro ao criar quadro'];
        }
    }

    public function editar(int $idUser, int $idQuadro, $nome, $descricao): array
    {
        $v = $this->validar($nome, $descricao);
        if (is_string($v)) return ['success' => false, 'error' => $v];
        if (!$this->model->find($idUser, $idQuadro)) return ['success' => false, 'error' => 'Quadro não encontrado'];

        $this->model->update($idUser, $idQuadro, $v[0], $v[1]);
        return ['success' => true];
    }

    public function excluir(int $idUser, int $idQuadro): array
    {
        try {
            $ok = $this->model->delete($idUser, $idQuadro);
            return $ok ? ['success' => true] : ['success' => false, 'error' => 'Quadro não encontrado'];
        } catch (Exception $e) {
            Logger::error('quadro', 'Erro ao excluir quadro', ['usuario_id' => $idUser, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Erro ao excluir quadro'];
        }
    }

    public function adicionarReceita(int $idUser, int $idQuadro, int $idReceita): array
    {
        if (!$this->model->find($idUser, $idQuadro)) return ['success' => false, 'error' => 'Quadro não encontrado'];

        try {
            // Salvar num quadro também favorita (incrementa qtd_fav uma vez só)
            if (!$this->favoritoModel->isFavorite($idUser, $idReceita)) {
                $this->favoritoModel->addWithIncrement($idUser, $idReceita);
            }
            $this->model->addReceita($idQuadro, $idReceita);
            return ['success' => true];
        } catch (Exception $e) {
            Logger::error('quadro', 'Erro ao adicionar receita ao quadro', [
                'usuario_id' => $idUser, 'quadro_id' => $idQuadro, 'receita_id' => $idReceita, 'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => 'Erro ao salvar no quadro'];
        }
    }

    /** Adiciona várias receitas de uma vez (seletor de receitas salvas). */
    public function adicionarVarias(int $idUser, int $idQuadro, array $idsReceitas): array
    {
        if (!$this->model->find($idUser, $idQuadro)) return ['success' => false, 'error' => 'Quadro não encontrado'];

        foreach ($this->normalizarIds($idsReceitas) as $idReceita) {
            $res = $this->adicionarReceita($idUser, $idQuadro, $idReceita);
            if (!$res['success']) return $res;
        }
        return ['success' => true];
    }

    /** Inteiros positivos, sem repetição, no máximo 200 por vez. */
    private function normalizarIds(array $ids): array
    {
        $ids = array_filter(array_map('intval', $ids), fn($i) => $i > 0);
        return array_slice(array_values(array_unique($ids)), 0, 200);
    }

    public function removerReceita(int $idUser, int $idQuadro, int $idReceita): array
    {
        $this->model->removeReceita($idUser, $idQuadro, $idReceita);
        return ['success' => true];
    }

    /** Remove várias receitas do quadro (seleção em massa / mover). */
    public function removerVarias(int $idUser, int $idQuadro, array $idsReceitas): array
    {
        foreach ($this->normalizarIds($idsReceitas) as $idReceita) {
            $this->model->removeReceita($idUser, $idQuadro, $idReceita);
        }
        return ['success' => true];
    }
}
