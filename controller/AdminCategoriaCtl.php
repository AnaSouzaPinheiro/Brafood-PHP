<?php

namespace App\Controller;

use App\Model\AdminCategoriaModel;

class AdminCategoriaCtl
{
    private AdminCategoriaModel $model;

    public function __construct()
    {
        $this->model = new AdminCategoriaModel();
    }

    // ── Verificação de acesso ─────────────────────────────────

    private function verificarAdmin(): void
    {
        $headers = getallheaders();
        $userId  = isset($headers['X-User-Id']) ? (int)$headers['X-User-Id'] : 0;

        if (!$userId || !$this->model->isAdmin($userId)) {
            http_response_code(403);
            echo json_encode(['mensagem' => 'Acesso negado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // ── Categorias ────────────────────────────────────────────

    public function listarCategorias(): array
    {
        return $this->model->listarCategorias();
    }

    public function buscarCategoria(int $id): array
    {
        $cat = $this->model->buscarCategoriaPorId($id);
        if (!$cat) {
            http_response_code(404);
            return ['mensagem' => 'Categoria não encontrada.'];
        }
        return $cat;
    }

    public function criarCategoria(array $data): array
    {
        $this->verificarAdmin();

        if (empty($data['nome'])) {
            http_response_code(422);
            return ['mensagem' => 'O campo "nome" é obrigatório.'];
        }

        $id = $this->model->criarCategoria($data);
        http_response_code(201);
        return ['mensagem' => 'Categoria criada com sucesso.', 'id' => $id];
    }

    public function atualizarCategoria(int $id, array $data): array
    {
        $this->verificarAdmin();

        if (empty($data['nome'])) {
            http_response_code(422);
            return ['mensagem' => 'O campo "nome" é obrigatório.'];
        }

        $ok = $this->model->atualizarCategoria($id, $data);
        return $ok
            ? ['mensagem' => 'Categoria atualizada com sucesso.']
            : ['mensagem' => 'Erro ao atualizar categoria.'];
    }

    public function excluirCategoria(int $id): array
    {
        $this->verificarAdmin();

        $ok = $this->model->excluirCategoria($id);
        return $ok
            ? ['mensagem' => 'Categoria excluída com sucesso.']
            : ['mensagem' => 'Erro ao excluir categoria.'];
    }

    // ── Subcategorias ────────────────────────────────────────

    public function listarSubcategorias(?int $idCategoria = null): array
    {
        return $this->model->listarSubcategorias($idCategoria);
    }

    public function criarSubcategoria(array $data): array
    {
        $this->verificarAdmin();

        if (empty($data['nome']) || empty($data['id_categoria'])) {
            http_response_code(422);
            return ['mensagem' => 'Os campos "nome" e "id_categoria" são obrigatórios.'];
        }

        $id = $this->model->criarSubcategoria($data['nome'], (int)$data['id_categoria']);
        http_response_code(201);
        return ['mensagem' => 'Subcategoria criada com sucesso.', 'id' => $id];
    }

    public function atualizarSubcategoria(int $id, array $data): array
    {
        $this->verificarAdmin();

        if (empty($data['nome']) || empty($data['id_categoria'])) {
            http_response_code(422);
            return ['mensagem' => 'Os campos "nome" e "id_categoria" são obrigatórios.'];
        }

        $ok = $this->model->atualizarSubcategoria($id, $data['nome'], (int)$data['id_categoria']);
        return $ok
            ? ['mensagem' => 'Subcategoria atualizada com sucesso.']
            : ['mensagem' => 'Erro ao atualizar subcategoria.'];
    }

    public function excluirSubcategoria(int $id): array
    {
        $this->verificarAdmin();

        $ok = $this->model->excluirSubcategoria($id);
        return $ok
            ? ['mensagem' => 'Subcategoria excluída com sucesso.']
            : ['mensagem' => 'Erro ao excluir subcategoria.'];
    }

    // ── Prompt IA ────────────────────────────────────────────

    public function listarParaPromptIA(): array
    {
        return $this->model->listarParaPromptIA();
    }
}
