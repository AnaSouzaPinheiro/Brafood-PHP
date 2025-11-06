<?php

header("Content-Type: application/json; charset=UTF-8");

// Habilita CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


include_once '../config/Database.php';
include_once '../model/CategoriaModel.php';
include_once '../model/ReceitaModel.php';

$database = new Database();
$db = $database->getConnection();

$categoriaModel = new CategoriaModel($db);
$receitaModel = new ReceitaModel($db);

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_GET['id'])) {
    echo json_encode(["mensagem" => "ID da categoria não informado."]);
    http_response_code(400);
    exit;
}

$id = intval($_GET['id']);

if (!$categoriaModel->verificarCategoriaPorId($id)) {
    echo json_encode(["mensagem" => "Categoria não encontrada."]);
    http_response_code(404);
    exit;
}

$receitas = $receitaModel->listarComCategoria($id);
echo json_encode($receitas);
