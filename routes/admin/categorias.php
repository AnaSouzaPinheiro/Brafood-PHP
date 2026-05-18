<?php

require_once __DIR__ . '/../../config/core.php';

use App\Controller\AdminCategoriaCtl;

$ctl    = new AdminCategoriaCtl();
$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// /routes/admin/categorias.php[?id=X]
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {
    case 'GET':
        if ($id) {
            $response = $ctl->buscarCategoria($id);
        } elseif (!empty($_GET['com_receitas'])) {
            // Retorna apenas categorias/subcategorias que têm receitas cadastradas
            $response = $ctl->listarCategoriasComReceitas();
        } else {
            $response = $ctl->listarCategorias();
        }
        break;

    case 'POST':
        $response = $ctl->criarCategoria($body);
        break;

    case 'PUT':
        if (!$id) {
            http_response_code(400);
            $response = ['mensagem' => 'ID obrigatório para atualização.'];
            break;
        }
        $response = $ctl->atualizarCategoria($id, $body);
        break;

    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            $response = ['mensagem' => 'ID obrigatório para exclusão.'];
            break;
        }
        $response = $ctl->excluirCategoria($id);
        break;

    default:
        http_response_code(405);
        $response = ['mensagem' => 'Método não permitido.'];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
