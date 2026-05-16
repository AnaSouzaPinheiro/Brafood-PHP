<?php

require_once __DIR__ . '/../../config/core.php';

use App\Controller\ReceitaCtl;

header('Content-Type: application/json');

$ctl = new ReceitaCtl();
$id  = isset($_GET['id']) ? intval($_GET['id']) : 0;

$response = $ctl->excluirReceita($id);

if (!$response['success']) {
    http_response_code($id <= 0 ? 400 : 404);
} else {
    http_response_code(200);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;