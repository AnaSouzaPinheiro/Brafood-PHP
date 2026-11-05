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

// Perfil é buscado pelo apelido (/chef/{apelido}). O ?id= continua
// aceito para os links antigos (/chef/5), que o front redireciona.
$apelido = trim((string) ($_GET['apelido'] ?? ''));
$id      = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($apelido === '' && !$id) {
    http_response_code(400);
    echo json_encode(['mensagem' => 'Usuário não informado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($apelido !== '') {
        $id = $model->buscarIdPorApelido($apelido) ?? 0;
    }

    $perfil = $id ? $model->buscarPerfil($id) : null;

    if (!$perfil) {
        http_response_code(404);
        echo json_encode(['mensagem' => 'Usuário não encontrado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode($perfil, JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    \App\Config\Logger::error('perfil_publico', 'Erro ao buscar perfil', ['erro' => $e->getMessage()]);
    echo json_encode(['mensagem' => 'Erro interno.'], JSON_UNESCAPED_UNICODE);
}
