<?php

require_once __DIR__ . '/../config/core.php';

use App\Controller\CategoriaCtl;

$ctl = new CategoriaCtl();

if (isset($_GET['id'])) {
    $response = $ctl->buscarCategoriaPorId(intval($_GET['id']));

    if (isset($response['mensagem'])) {
        http_response_code(404);
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode($ctl->listarCategorias(), JSON_UNESCAPED_UNICODE);
