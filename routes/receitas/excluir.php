<?php

require_once __DIR__ . '/../../config/core.php';

use App\Controller\ReceitaCtl;

// Garante que nenhum output acidental (warnings, notices) quebre o JSON
ob_start();

header('Content-Type: application/json');

$ctl = new ReceitaCtl();
$id  = isset($_GET['id']) ? intval($_GET['id']) : 0;

$response = $ctl->excluirReceita($id);

if (isset($response['mensagem'])) {
    http_response_code($id <= 0 ? 400 : 404);
}

// Descarta qualquer output acidental antes de enviar o JSON
ob_end_clean();

echo json_encode($response, JSON_UNESCAPED_UNICODE);
