<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../controller/ReceitaCtl.php';

$db  = (new Database())->getConnection();
$ctl = new ReceitaCtl($db);

$id      = isset($_GET['id']) ? intval($_GET['id']) : null;
$ordenar = $_GET['ordenar'] ?? 'nome';

$subcategorias = [];
if (isset($_GET['subcategorias'])) {
    $subcategorias = is_array($_GET['subcategorias'])
        ? $_GET['subcategorias']
        : explode(',', $_GET['subcategorias']);
}

$response = $ctl->listarPorCategoria($id, $ordenar, $subcategorias);

if (isset($response['mensagem'])) {
    http_response_code(!$id ? 400 : 404);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
