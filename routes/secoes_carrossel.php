<?php
// Inclui o controller correto
require_once __DIR__ . '/../controller/SecaoCarrosselCtl.php';

// Cabeçalhos CORS
//header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

// Responde às requisições pré-flight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Chama o controller normalmente
$controller = new SecaoCarrosselCtl();
$secoes = $controller->index();

// Envia o JSON
echo json_encode($secoes, JSON_UNESCAPED_UNICODE);
exit;