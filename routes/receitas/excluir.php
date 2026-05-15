<?php

require_once __DIR__ . '/../../config/core.php';

use App\Controller\ReceitaCtl;

$ctl = new ReceitaCtl();
$id  = isset($_GET['id']) ? intval($_GET['id']) : 0;

$response = $ctl->excluirReceita($id);

if (isset($response['mensagem'])) {
    http_response_code($id <= 0 ? 400 : 404);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
