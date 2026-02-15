<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../model/UserModel.php';
require_once __DIR__ . '/../config/Database.php';

$db = (new Database())->getConnection();
$usuario = new UserModel($db);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $stmt = $usuario->buscarUserPorId($id);

    echo json_encode($usuario);
} else {
    http_response_code(400);
    echo json_encode(["mensagem" => "ID inválido."]);
}


class UserCtl
{
    private $model;

    public function __construct($db)
    {
        $this->model = new UserModel($db);
    }
    public function editarUsuario($dados)
    {

        $id = (int) $dados['id'];

        $this->model->editarUsuario(
            $id,
            $dados['nome'],
            $dados['sobrenome'],
            $dados['apelido'],
            $dados['email'],
            $dados['genero'],
            $dados['bio'],
            $dados['foto_perfil'] ?? null
        );

        return [
            "sucesso" => true,
            "mensagem" => "Usuário editado com sucesso!",
            "id" => $id
        ];
    }
}
