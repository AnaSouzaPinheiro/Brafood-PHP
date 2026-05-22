<?php

require_once __DIR__ . '/../config/core.php';

use App\Model\PerfilPublicoModel;

$model  = new PerfilPublicoModel();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['mensagem' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$id) {
    http_response_code(400);
    echo json_encode(['mensagem' => 'ID do usuário não informado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $perfil = $model->buscarPerfil($id);

    if (!$perfil) {
        http_response_code(404);
        echo json_encode(['mensagem' => 'Usuário não encontrado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode($perfil, JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['mensagem' => 'Erro interno: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
