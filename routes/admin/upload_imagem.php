<?php

require_once __DIR__ . '/../../config/core.php';

use App\Controller\AdminSecaoCtl;
use App\Service\UploadService;

header('Content-Type: application/json; charset=UTF-8');

// Verificar admin via AdminSecaoCtl
$ctl     = new AdminSecaoCtl();
$headers = getallheaders();
$userId  = isset($headers['X-User-Id']) ? (int)$headers['X-User-Id'] : 0;

if (!$userId) {
    http_response_code(403);
    echo json_encode(['mensagem' => 'Acesso negado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Determinar pasta de destino pelo parâmetro 'contexto'
$contexto  = $_GET['contexto'] ?? 'geral';
$permitidos = ['categorias', 'secoes'];

if (!in_array($contexto, $permitidos, true)) {
    http_response_code(400);
    echo json_encode(['mensagem' => 'Contexto inválido. Use: categorias ou secoes.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$uploadDir    = __DIR__ . '/../../uploads/' . $contexto . '/';
$relativePath = 'uploads/' . $contexto;

$service = new UploadService($uploadDir, $relativePath, 800, 82, 7);

$caminho = $service->processarUmaImagem($_FILES['imagem'] ?? []);

if ($caminho) {
    echo json_encode(['caminho' => $caminho], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(422);
    echo json_encode(['mensagem' => 'Falha no upload. Verifique se o arquivo é uma imagem válida.'], JSON_UNESCAPED_UNICODE);
}
