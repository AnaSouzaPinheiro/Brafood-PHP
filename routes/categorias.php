<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

// Habilita CORS
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


include_once '../config/Database.php';
include_once '../model/CategoriaModel.php';

// Se for uma requisição OPTIONS (pré-flight), retorna apenas o cabeçalho
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$categoriaModel = new CategoriaModel($db);

// Verifica se veio parâmetro "id"
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $todas = $categoriaModel->listarCategorias();

    // Filtra a categoria correspondente
    $filtrada = array_values(array_filter($todas, function ($cat) use ($id) {
        return intval($cat['id']) === $id;
    }));

    if (empty($filtrada)) {
        http_response_code(404);
        echo json_encode(["mensagem" => "Categoria não encontrada."]);
        exit;
    }

    echo json_encode($filtrada[0], JSON_UNESCAPED_UNICODE);
    exit;
}

// Caso não tenha ID → retorna todas
$categorias = $categoriaModel->listarCategorias();
echo json_encode($categorias, JSON_UNESCAPED_UNICODE);
?>
