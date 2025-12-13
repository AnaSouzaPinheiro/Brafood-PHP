<?php
// ================== CORS ==================
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// Preflight (OBRIGATÓRIO para Angular)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
// ==========================================

// ===== INCLUDES =====
require_once '../config/Database.php';
require_once '../controller/FavoritoCtl.php';

// ===== DB =====
$database = new Database();
$db = $database->getConnection();

// ===== CONTROLLER =====
$controller = new FavoritoCtl($db);

// ===== METHOD =====
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // ---------- CHECK FAVORITO ----------
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

    // ---------- ADICIONAR FAVORITO ----------
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

    // ---------- REMOVER FAVORITO ----------
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

    // ---------- MÉTODO INVÁLIDO ----------
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método não suportado']);
}
