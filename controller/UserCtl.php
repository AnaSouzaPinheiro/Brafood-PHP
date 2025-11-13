<?php header('Content-Type: application/json');
include_once '../model/UserModel.php';

$usuario = new UserModel($db);


$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $stmt = $usuario->buscarUserPorId($id);

    echo json_encode($usuario);
} else {
    http_response_code(400);
    echo json_encode(["mensagem" => "ID inválido."]);
}

?>