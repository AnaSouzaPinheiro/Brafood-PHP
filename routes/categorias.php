<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/env.php';

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../controller/CategoriaCtl.php';

$db  = (new Database())->getConnection();
$ctl = new CategoriaCtl($db);

if (isset($_GET['id'])) {
    echo json_encode(
        $ctl->buscarCategoriaPorId(intval($_GET['id'])),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

echo json_encode(
    $ctl->listarCategorias(),
    JSON_UNESCAPED_UNICODE
);