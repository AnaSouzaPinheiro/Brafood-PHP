<?php
header("Content-Type: application/json; charset=UTF-8");

// Habilita CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once '../config/Database.php';
include_once '../model/ReceitaModel.php';

// Se for uma requisição OPTIONS (pré-flight), retorna apenas o cabeçalho
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$receitaModel = new ReceitaModel($db);
$receitas = $receitaModel->listarTodas();

echo json_encode($receitas, JSON_UNESCAPED_UNICODE);
?>
