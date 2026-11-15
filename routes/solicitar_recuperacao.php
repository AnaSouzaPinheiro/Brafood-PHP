<?php

require_once __DIR__ . '/../config/core.php';

use App\Controller\LoginCtl;

$ctl  = new LoginCtl();
$data = json_decode(file_get_contents('php://input'), true) ?? [];

$resultado = $ctl->solicitarRecuperacao($data);
$response  = $resultado['resposta'];

if (!($response['success'] ?? false)) {
    http_response_code(400);
}

header('Content-Type: application/json; charset=UTF-8');

// Responde já (igual exista a conta ou não) e só depois envia o e-mail:
// assim o tempo de resposta não revela se o e-mail está cadastrado.
responderEContinuar(json_encode($response, JSON_UNESCAPED_UNICODE));

if ($resultado['enviar'] !== null) {
    @set_time_limit(60);
    ($resultado['enviar'])();
}
