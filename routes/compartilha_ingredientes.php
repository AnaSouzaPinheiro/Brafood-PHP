<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

// ================== CORS ==================
//header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Origin: *");
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
    if (isset($_GET['ingredientes'])) {
        $ingredientes = json_decode($_GET['ingredientes'], true);
        
        echo json_encode(
            $controller->getLinkWhatsApp($ingredientes, $_GET['link'] ?? '')
        );
    }
    break;

    // ---------- MÉTODO INVÁLIDO ----------
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método não suportado']);
}
