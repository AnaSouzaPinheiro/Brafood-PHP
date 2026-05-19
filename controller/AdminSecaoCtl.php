<?php

namespace App\Controller;

use App\Model\AdminSecaoModel;

class AdminSecaoCtl
{
    private AdminSecaoModel $model;

    public function __construct()
    {
        $this->model = new AdminSecaoModel();
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

    // ── Seções ────────────────────────────────────────────────

    public function listarSecoes(): array
    {
        return $this->model->listarSecoes();
    }

    public function buscarSecao(int $id): array
    {
        $sec = $this->model->buscarSecaoPorId($id);
        if (!$sec) {
            http_response_code(404);
            return ['mensagem' => 'Seção não encontrada.'];
        }
        return $sec;
    }

    public function criarSecao(array $data): array
    {
        $this->verificarAdmin();

        if (empty($data['titulo'])) {
            http_response_code(422);
            return ['mensagem' => 'O campo "titulo" é obrigatório.'];
        }

        $id = $this->model->criarSecao($data);
        http_response_code(201);
        return ['mensagem' => 'Seção criada com sucesso.', 'id' => $id];
    }

    public function atualizarSecao(int $id, array $data): array
    {
        $this->verificarAdmin();

        if (empty($data['titulo'])) {
            http_response_code(422);
            return ['mensagem' => 'O campo "titulo" é obrigatório.'];
        }

        $ok = $this->model->atualizarSecao($id, $data);
        return $ok
            ? ['mensagem' => 'Seção atualizada com sucesso.']
            : ['mensagem' => 'Erro ao atualizar seção.'];
    }

    public function excluirSecao(int $id): array
    {
        $this->verificarAdmin();

        $ok = $this->model->excluirSecao($id);
        return $ok
            ? ['mensagem' => 'Seção excluída com sucesso.']
            : ['mensagem' => 'Erro ao excluir seção.'];
    }
}
