<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../controller/SecaoCarrosselCtl.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$controller = new SecaoCarrosselCtl();
$secoes = $controller->index();

echo json_encode($secoes, JSON_UNESCAPED_UNICODE);
exit;