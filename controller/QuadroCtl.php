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

    public function estadoDaReceita(int $idUser, int $idReceita): array
    {
        return [
            'favorito' => $this->favoritoModel->isFavorite($idUser, $idReceita),
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

    public function criar(int $idUser, $nome, $descricao, ?int $idReceita = null): array
    {
        $v = $this->validar($nome, $descricao);
        if (is_string($v)) return ['success' => false, 'error' => $v];

        try {
            $id = $this->model->create($idUser, $v[0], $v[1]);
            if ($idReceita) $this->adicionarReceita($idUser, $id, $idReceita);

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

    public function removerReceita(int $idUser, int $idQuadro, int $idReceita): array
    {
        $this->model->removeReceita($idUser, $idQuadro, $idReceita);
        return ['success' => true];
    }
}
