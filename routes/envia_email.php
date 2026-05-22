<?php

require_once __DIR__ . '/../config/core.php';

use App\Controller\EmailCtl;

header('Content-Type: application/json; charset=utf-8');

$ctl  = new EmailCtl();
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "JSON inválido."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$response = $ctl->enviarContato($data);

http_response_code($response['success'] ? 200 : 400);

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;