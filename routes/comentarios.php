<?php

require_once __DIR__ . '/../config/core.php';

use App\Controller\ComentariosCtl;
session_start();

$controller = new ComentariosCtl();

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
