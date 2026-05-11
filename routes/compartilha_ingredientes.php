<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/env.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/Database.php';
require_once '../config/Logger.php';
require_once '../controller/FavoritoCtl.php';

$database = new Database();
$db = $database->getConnection();

$controller = new FavoritoCtl($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
    if (isset($_GET['ingredientes'])) {
        $ingredientes = json_decode($_GET['ingredientes'], true);
        
        echo json_encode(
            $controller->getLinkWhatsApp($ingredientes, $_GET['link'] ?? '')
        );
    }
    break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método não suportado']);
}
