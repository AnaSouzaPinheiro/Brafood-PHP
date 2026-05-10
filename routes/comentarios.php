<?php
session_start();

// ================== CORS ==================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

//header("Access-Control-Allow-Origin: http://localhost:4200");
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// necessário para requisição OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


// ===== INCLUDES =====
require_once '../config/Database.php';
require_once '../controller/ComentariosCtl.php';

// ===== DB =====
$database = new Database();
$db = $database->getConnection();

$controller = new ComentariosCtl($db);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $controller->index();
        break;

    case 'POST':
        $controller->store();
        break;

    default:
        http_response_code(405);
}