<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/env.php';
session_start();

require_once '../config/Database.php';
require_once '../config/Logger.php';
require_once '../controller/ComentariosCtl.php';

$database   = new Database();
$db         = $database->getConnection();
$controller = new ComentariosCtl($db);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $idReceita = isset($_GET['receita']) ? (int) $_GET['receita'] : null;
        $result    = $controller->index($idReceita);
        break;

    case 'POST':
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $controller->store($data);
        break;

    default:
        http_response_code(405);
        exit;
}

http_response_code($result['status']);
echo json_encode($result['body'], JSON_UNESCAPED_UNICODE);
