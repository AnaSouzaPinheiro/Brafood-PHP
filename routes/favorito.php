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
    if (isset($_GET['check'])) {
        echo json_encode(
            $controller->check($_GET['idUser'], $_GET['idReceita'])
        );
    } else if (isset($_GET['idUser'])) {
        echo json_encode(
            $controller->listByUser($_GET['idUser'])
        );
    }
    break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['idUser'], $data['idReceita'])) {
            echo json_encode(['error' => 'Dados incompletos']);
            exit;
        }

        echo json_encode(
            $controller->add(
                $data['idUser'],
                $data['idReceita']
            )
        );
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['idUser'], $data['idReceita'])) {
            echo json_encode(['error' => 'Dados incompletos']);
            exit;
        }

        echo json_encode(
            $controller->remove(
                $data['idUser'],
                $data['idReceita']
            )
        );
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método não suportado']);
}
