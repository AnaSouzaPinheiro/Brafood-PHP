<?php

require_once __DIR__ . '/../config/core.php';

use App\Controller\LoginCtl;

$ctl  = new LoginCtl();
$data = json_decode(file_get_contents('php://input'), true) ?? [];

$response = $ctl->enviarRecuperacaoSenha($data);

if (!($response['success'] ?? false)) {
    http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
