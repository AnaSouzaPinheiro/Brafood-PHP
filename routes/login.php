<?php

require_once __DIR__ . '/../config/core.php';

use App\Controller\LoginCtl;

header('Content-Type: application/json; charset=UTF-8');

$ctl  = new LoginCtl();
$data = json_decode(file_get_contents('php://input'), true) ?? [];

// Cadastro — detectado pela presença do campo confirmarSenha
if (isset($data['confirmarSenha'])) {
    if ($data['senha'] !== $data['confirmarSenha']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'As senhas não coincidem.']);
        exit;
    }

    $response = $ctl->cadastro($data);

    if (!($response['success'] ?? false)) {
        http_response_code(400);
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Login
$response = $ctl->login($data);

if (!($response['success'] ?? false)) {
    http_response_code(401);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
