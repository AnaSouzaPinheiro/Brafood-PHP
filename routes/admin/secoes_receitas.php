<?php

require_once __DIR__ . '/../../config/core.php';

use App\Model\AdminSecaoModel;

header('Content-Type: application/json; charset=UTF-8');

$model  = new AdminSecaoModel();
$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// Verificar admin
$headers = getallheaders();
$userId  = isset($headers['X-User-Id']) ? (int)$headers['X-User-Id'] : 0;

if ($method !== 'GET' && (!$userId || !$model->isAdmin($userId))) {
    http_response_code(403);
    echo json_encode(['mensagem' => 'Acesso negado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($method) {
    case 'GET':
        if (isset($_GET['busca'])) {
            // Autocomplete de receitas por título
            $termo    = trim($_GET['busca']);
            $response = $model->buscarReceitas($termo);
        } elseif (isset($_GET['secao_id'])) {
            // Listar receitas vinculadas à seção
            $secaoId  = (int)$_GET['secao_id'];
            $response = $model->listarReceitasDaSecao($secaoId);
        } else {
            http_response_code(400);
            $response = ['mensagem' => 'Parâmetro secao_id ou busca é obrigatório.'];
        }
        break;

    case 'POST':
        $secaoId  = (int)($body['secao_id']  ?? 0);
        $receitaId = (int)($body['receita_id'] ?? 0);

        if (!$secaoId || !$receitaId) {
            http_response_code(400);
            $response = ['mensagem' => 'secao_id e receita_id são obrigatórios.'];
            break;
        }

        $ok = $model->adicionarReceita($secaoId, $receitaId);
        $response = $ok
            ? ['mensagem' => 'Receita vinculada com sucesso.']
            : ['mensagem' => 'Erro ao vincular receita.'];
        break;

    case 'DELETE':
        $secaoId   = (int)($_GET['secao_id']   ?? 0);
        $receitaId = (int)($_GET['receita_id']  ?? 0);

        if (!$secaoId || !$receitaId) {
            http_response_code(400);
            $response = ['mensagem' => 'secao_id e receita_id são obrigatórios.'];
            break;
        }

        $ok = $model->removerReceita($secaoId, $receitaId);
        $response = $ok
            ? ['mensagem' => 'Receita removida da seção.']
            : ['mensagem' => 'Erro ao remover receita.'];
        break;

    default:
        http_response_code(405);
        $response = ['mensagem' => 'Método não permitido.'];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
