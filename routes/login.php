<?php

require_once __DIR__ . '/../config/core.php';

use App\Controller\LoginCtl;
use App\Service\RequestThrottle;

header('Content-Type: application/json; charset=UTF-8');

$ctl  = new LoginCtl();
$data = json_decode(file_get_contents('php://input'), true) ?? [];

// Cadastro — detectado pela presença do campo confirmarSenha
if (isset($data['confirmarSenha'])) {
    // No máximo 10 cadastros por hora por IP
    RequestThrottle::porIp('cadastro', 10, 3600);

    if ($data['senha'] !== $data['confirmarSenha']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'mensagem' => 'As senhas não coincidem.']);
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
    if ($response['bloqueado'] ?? false) {
        http_response_code(429);
        header('Retry-After: ' . (int) ($response['retry_after'] ?? 900));
    } else {
        http_response_code(401);
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Retorna dados completos do usuário (incluindo is_admin) + token de sessão
echo json_encode([
    'success' => true,
    'mensagem' => 'Login realizado com sucesso.',
    'user'    => $response['usuario'],
    'token'   => $response['token'],
], JSON_UNESCAPED_UNICODE);
