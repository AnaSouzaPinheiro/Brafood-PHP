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
        $userId = (int) getRequestHeader('X-User-Id');

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

    public function listarCategoriasComReceitas(): array
    {
        return $this->model->listarCategoriasComReceitas();
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

        $data['nome'] = trim($data['nome']);

        if ($this->model->categoriaNomeExiste($data['nome'])) {
            http_response_code(409);
            return ['mensagem' => 'Já existe uma categoria com esse nome.'];
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

        $data['nome'] = trim($data['nome']);

        if ($this->model->categoriaNomeExiste($data['nome'], $id)) {
            http_response_code(409);
            return ['mensagem' => 'Já existe uma categoria com esse nome.'];
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

    public function listarSubcategoriasComReceitas(?int $idCategoria = null): array
    {
        return $this->model->listarSubcategoriasComReceitas($idCategoria);
    }

    public function criarSubcategoria(array $data): array
    {
        $this->verificarAdmin();

        if (empty($data['nome']) || empty($data['id_categoria'])) {
            http_response_code(422);
            return ['mensagem' => 'Os campos "nome" e "id_categoria" são obrigatórios.'];
        }

        $data['nome'] = trim($data['nome']);

        if ($this->model->subcategoriaNomeExiste($data['nome'], (int) $data['id_categoria'])) {
            http_response_code(409);
            return ['mensagem' => 'Já existe uma subcategoria com esse nome nesta categoria.'];
        }

        $id = $this->model->criarSubcategoria($data['nome'], (int) $data['id_categoria']);
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

        $data['nome'] = trim($data['nome']);

        if ($this->model->subcategoriaNomeExiste($data['nome'], (int) $data['id_categoria'], $id)) {
            http_response_code(409);
            return ['mensagem' => 'Já existe uma subcategoria com esse nome nesta categoria.'];
        }

        $ok = $this->model->atualizarSubcategoria($id, $data['nome'], (int) $data['id_categoria']);
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

    /** Retorna categorias com suas subcategorias aninhadas para o prompt da IA */
    public function listarParaPromptIA(): array
    {
        $cats = $this->listarCategorias();
        $subs = $this->listarSubcategorias(); // já retorna id_categoria

        // Agrupa subcategorias por id_categoria
        $subsPorCategoria = [];
        foreach ($subs as $sub) {
            $subsPorCategoria[(int) $sub['id_categoria']][] = [
                'id' => (int) $sub['id'],
                'nome' => $sub['nome'],
            ];
        }

        // Aninha dentro de cada categoria
        return array_map(function ($cat) use ($subsPorCategoria) {
            return [
                'id' => (int) $cat['id'],
                'nome' => $cat['nome'],
                'subcategorias' => $subsPorCategoria[(int) $cat['id']] ?? [],
            ];
        }, $cats);
    }
}