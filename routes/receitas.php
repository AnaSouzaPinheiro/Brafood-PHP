<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/env.php';

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../controller/ReceitaCtl.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$db       = (new Database())->getConnection();
$ctl      = new ReceitaCtl($db);
$pesquisa = $_GET['pesquisa'] ?? '';

$response = $ctl->listarTodas($pesquisa);

echo json_encode($response, JSON_UNESCAPED_UNICODE);
