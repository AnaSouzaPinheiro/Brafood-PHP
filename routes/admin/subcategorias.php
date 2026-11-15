<?php

require_once __DIR__ . '/../../config/core.php';

use App\Controller\AdminCategoriaCtl;

$ctl    = new AdminCategoriaCtl();
$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

$id          = isset($_GET['id'])           ? (int)$_GET['id']          : null;
$idCategoria = isset($_GET['id_categoria']) ? (int)$_GET['id_categoria'] : null;

try {
    switch ($method) {
        case 'GET':
            if (!empty($_GET['com_receitas'])) {
                $response = $ctl->listarSubcategoriasComReceitas($idCategoria);
            } else {
                $response = $ctl->listarSubcategorias($idCategoria);
            }
            break;

        case 'POST':
            $response = $ctl->criarSubcategoria($body);
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                $response = ['mensagem' => 'ID obrigatório para atualização.'];
                break;
            }
            $response = $ctl->atualizarSubcategoria($id, $body);
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                $response = ['mensagem' => 'ID obrigatório para exclusão.'];
                break;
            }
            $response = $ctl->excluirSubcategoria($id);
            break;

        default:
            http_response_code(405);
            $response = ['mensagem' => 'Método não permitido.'];
    }
} catch (\Throwable $e) {
    http_response_code(500);
    $response = ['mensagem' => \App\Config\Logger::falha('admin/subcategorias', $e)];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);