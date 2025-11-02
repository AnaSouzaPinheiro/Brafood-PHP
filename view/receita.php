<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../model/ReceitaModel.php';

// Conexão
$database = new Database();
$db = $database->getConnection();

$receitaModel = new ReceitaModel($db);

// Pega ID via GET (?id=)
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $receita = $receitaModel->buscarPorId($id);

    if ($receita) {
        echo json_encode($receita, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode(["mensagem" => "Receita não encontrada."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["mensagem" => "ID inválido."]);
}
?>
