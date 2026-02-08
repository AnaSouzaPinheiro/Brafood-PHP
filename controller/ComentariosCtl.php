<?php

class ComentariosCtl
{

    private $model;
    private $db;

    public function __construct($db)
    {
        require_once __DIR__ . '/../model/ComentarioModel.php';
        $this->model = new ComentarioModel($db);
        $this->db = $db;
    }


    public function index()
    {
        if (!isset($_GET['receita'])) {
            http_response_code(400);
            echo json_encode(['erro' => 'Receita não informada']);
            return;
        }

        $idReceita = (int) $_GET['receita'];
        $comentarios = $this->model->buscarComentarioPorReceita($idReceita);

        echo json_encode($comentarios);
    }

    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (
            empty($data['id_receita']) ||
            empty($data['texto'])
        ) {
            http_response_code(400);
            echo json_encode(['erro' => 'Dados inválidos']);
            return;
        }

        // ⚠️ Exemplo: id do usuário vindo da sessão
        $data['id_usuario'] = $_SESSION['usuario_id'] ?? 1;

        $this->model->criar([
            'id_receita' => $data['id_receita'],
            'id_usuario' => $_SESSION['usuario_id'] ?? 1,
            'texto' => $data['texto'],
            'id_comentario_pai' => $data['id_comentario_pai'] ?? null
        ]);

        http_response_code(201);
        echo json_encode(['sucesso' => true]);
    }
}
