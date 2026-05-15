<?php

require_once __DIR__ . '/../config/core.php';

use App\Controller\ReceitaCtl;

$ctl    = new ReceitaCtl();
$idUser = isset($_GET['id']) ? intval($_GET['id']) : 0;

$response = $ctl->listarPorUsuario($idUser);

if (isset($response['mensagem'])) {
    http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
