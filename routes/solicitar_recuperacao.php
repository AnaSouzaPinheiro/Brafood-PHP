<?php

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../controller/LoginCtl.php';
require_once __DIR__ . '/../vendor/autoload.php';

$db   = (new Database())->getConnection();
$ctl  = new LoginCtl($db);
$data = json_decode(file_get_contents('php://input'), true) ?? [];

$response = $ctl->enviarRecuperacaoSenha($data);

if (!($response['success'] ?? false)) {
    http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
