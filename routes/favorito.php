<?php

require_once __DIR__ . '/../config/core.php';

use App\Controller\FavoritoCtl;

$controller = new FavoritoCtl();
$method     = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['check'])) {
            echo json_encode(
                $controller->check($_GET['idUser'], $_GET['idReceita'])
            );
        } elseif (isset($_GET['idUser'])) {
            echo json_encode(
                $controller->listByUser($_GET['idUser'])
            );
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Parâmetros ausentes']);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (!isset($data['idUser'], $data['idReceita'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados incompletos']);
            exit;
        }

        $response = $controller->add($data['idUser'], $data['idReceita']);

        if (!($response['success'] ?? false)) {
            http_response_code(409); // Conflict — já favoritado, ou não encontrado
        }

        echo json_encode($response);
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (!isset($data['idUser'], $data['idReceita'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados incompletos']);
            exit;
        }

        $response = $controller->remove($data['idUser'], $data['idReceita']);

        if (!($response['success'] ?? false)) {
            http_response_code(500);
        }

        echo json_encode($response);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método não suportado']);
}
