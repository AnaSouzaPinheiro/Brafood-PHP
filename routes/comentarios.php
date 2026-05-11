<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/env.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/Database.php';
require_once '../config/Logger.php';
require_once '../controller/ComentariosCtl.php';

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