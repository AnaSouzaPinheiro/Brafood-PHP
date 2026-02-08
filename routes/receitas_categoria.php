<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../model/CategoriaModel.php';
include_once '../model/ReceitaModel.php';

$database = new Database();
$db = $database->getConnection();

$categoriaModel = new CategoriaModel($db);
$receitaModel = new ReceitaModel($db);

if (!isset($_GET['id'])) {
    echo json_encode(["mensagem" => "ID da categoria não informado."]);
    http_response_code(400);
    exit;
}

$id = intval($_GET['id']);
$ordenar = $_GET['ordenar'] ?? 'nome';

$subcategorias = [];

if (isset($_GET['subcategorias'])) {
    $subcategorias = is_array($_GET['subcategorias'])
        ? $_GET['subcategorias']
        : explode(',', $_GET['subcategorias']);
}

if (!$categoriaModel->verificarCategoriaPorId($id)) {
    echo json_encode(["mensagem" => "Categoria não encontrada."]);
    http_response_code(404);
    exit;
}

$receitas = $receitaModel->listarComCategoria($id, $ordenar, $subcategorias);
echo json_encode($receitas);
