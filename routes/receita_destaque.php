<?php

require_once __DIR__ . '/../config/core.php';

use App\Config\Auth;
use App\Model\DestaqueModel;

$model  = new DestaqueModel();
$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// Alterar o destaque (e buscar receitas para o seletor) é só para admin
if ($method !== 'GET' || isset($_GET['busca'])) {
    $usuario = Auth::requireAuth();
    if ((int) ($usuario['is_admin'] ?? 0) !== 1) {
        http_response_code(403);
        echo json_encode(['mensagem' => 'Acesso restrito ao administrador.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

try {
    switch ($method) {

        // GET — público: retorna a receita em destaque
        case 'GET':
            if (isset($_GET['busca'])) {
                // Admin: busca receitas para o seletor
                $lista = $model->listarParaSelector($_GET['busca']);
                echo json_encode($lista, JSON_UNESCAPED_UNICODE);
            } else {
                $destaque = $model->buscarDestaque();
                if (!$destaque) {
                    http_response_code(204); // No Content
                    exit;
                }
                echo json_encode($destaque, JSON_UNESCAPED_UNICODE);
            }
            break;

        // POST { id_receita } — admin: ativa destaque
        case 'POST':
            $idReceita = (int) ($body['id_receita'] ?? 0);
            if (!$idReceita) {
                http_response_code(400);
                echo json_encode(['mensagem' => 'id_receita é obrigatório.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $model->ativarDestaque($idReceita);
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            break;

        // DELETE — admin: remove destaque
        case 'DELETE':
            $model->removerDestaque();
            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(405);
            echo json_encode(['mensagem' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    \App\Config\Logger::error('destaque', 'Erro na receita da semana', ['erro' => $e->getMessage()]);
    echo json_encode(['mensagem' => 'Erro interno.'], JSON_UNESCAPED_UNICODE);
}
