<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../model/UserModel.php';

// Conexão
$database = new Database();
$db = $database->getConnection();

$userModel = new UserModel($db);

// Pega ID via GET (?id=)
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $user = $userModel->buscarUserPorId($id);

    if ($user) {
        echo json_encode($user, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode(["mensagem" => "Userário não encontrada."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["mensagem" => "ID inválido."]);
}
?>
