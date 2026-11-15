<?php

require_once __DIR__ . '/../../config/core.php';

use App\Config\Auth;
use App\Model\AdminSecaoModel;

$model  = new AdminSecaoModel();
$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method !== 'GET') {
    Auth::requireAdmin();
}

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['busca'])) {
                $response = $model->buscarReceitas(trim($_GET['busca']));
            } elseif (isset($_GET['secao_id'])) {
                $response = $model->listarReceitasDaSecao((int)$_GET['secao_id']);
            } else {
                http_response_code(400);
                $response = ['mensagem' => 'Parâmetro secao_id ou busca é obrigatório.'];
            }
            break;

        case 'POST':
            $secaoId   = (int)($body['secao_id']   ?? 0);
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
            $receitaId = (int)($_GET['receita_id'] ?? 0);

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
} catch (\Throwable $e) {
    http_response_code(500);
    $response = ['mensagem' => \App\Config\Logger::falha('admin/secoes_receitas', $e)];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);