<?php

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../controller/LoginCtl.php';

require_once __DIR__ . '/../vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

$db = (new Database())->getConnection();
$ctl = new LoginCtl($db);

$data = json_decode(
    file_get_contents("php://input"),
    true
);

$response = $ctl->enviarRecuperacaoSenha($data);
echo json_encode($response, JSON_UNESCAPED_UNICODE);