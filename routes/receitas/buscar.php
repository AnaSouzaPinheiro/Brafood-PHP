<?php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../controller/ReceitaCtl.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$db  = (new Database())->getConnection();
$ctl = new ReceitaCtl($db);
$id  = isset($_GET['id']) ? intval($_GET['id']) : 0;

$response = $ctl->buscarPorId($id);

if (isset($response['mensagem'])) {
    http_response_code($id <= 0 ? 400 : 404);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
