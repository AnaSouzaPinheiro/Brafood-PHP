<?php

require_once __DIR__ . '/../../config/core.php';

use App\Controller\AdminSecaoCtl;

$ctl    = new AdminSecaoCtl();
$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// /routes/admin/secoes.php[?id=X]
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {
    case 'GET':
        $response = $id
            ? $ctl->buscarSecao($id)
            : $ctl->listarSecoes();
        break;

    case 'POST':
        $response = $ctl->criarSecao($body);
        break;

    case 'PUT':
        if (!$id) {
            http_response_code(400);
            $response = ['mensagem' => 'ID obrigatório para atualização.'];
            break;
        }
        $response = $ctl->atualizarSecao($id, $body);
        break;

    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            $response = ['mensagem' => 'ID obrigatório para exclusão.'];
            break;
        }
        $response = $ctl->excluirSecao($id);
        break;

    default:
        http_response_code(405);
        $response = ['mensagem' => 'Método não permitido.'];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
