<?php

require_once __DIR__ . '/../config/core.php';

use App\Controller\UserCtl;

$ctl = new UserCtl();
$id  = isset($_GET['id']) ? intval($_GET['id']) : 0;

$response = $ctl->buscarUsuarioPorId($id);

if (!$response['success']) {
    http_response_code($id <= 0 ? 400 : 404);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
