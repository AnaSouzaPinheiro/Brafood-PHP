<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/env.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Logger.php';
require_once __DIR__ . '/../controller/LoginCtl.php';

header('Content-Type: application/json; charset=UTF-8');

$db  = (new Database())->getConnection();
$ctl = new LoginCtl($db);

$data = json_decode(file_get_contents('php://input'), true) ?? [];

// Cadastro — detectado pela presença do campo confirmarSenha
if (isset($data['confirmarSenha'])) {
    if ($data['senha'] !== $data['confirmarSenha']) {
        echo json_encode(['success' => false, 'message' => 'As senhas não coincidem.']);
        exit;
    }
    echo json_encode($ctl->cadastro($data), JSON_UNESCAPED_UNICODE);
    exit;
}

// Login
echo json_encode($ctl->login($data), JSON_UNESCAPED_UNICODE);
