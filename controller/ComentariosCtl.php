<?php

/**
 * Controla comentários de receitas.
 * Suporta listagem (GET) e criação (POST), incluindo respostas aninhadas via id_comentario_pai.
 */

class ComentariosCtl
{
    private $model;

    public function __construct($db)
    {
        require_once __DIR__ . '/../model/ComentarioModel.php';
        $this->model = new ComentarioModel($db);
    }

    public function index(): void
    {
        if (!isset($_GET['receita'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Receita não informada']);
            return;
        }

        $idReceita   = (int) $_GET['receita'];
        $comentarios = $this->model->buscarComentarioPorReceita($idReceita);

        echo json_encode($comentarios);
    }

    public function store(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id_receita']) || empty($data['texto']) || empty($data['id_usuario'])) {
            Logger::warn('comentarios', 'Tentativa de comentário com dados inválidos', [
                'usuario_id' => $data['id_usuario'] ?? '(ausente)',
                'receita_id' => $data['id_receita'] ?? '(ausente)'
            ]);
            http_response_code(400);
            echo json_encode(['error' => 'Dados inválidos: id_receita, id_usuario e texto são obrigatórios.']);
            return;
        }

        try {
            $this->model->criar([
                'id_receita'        => (int) $data['id_receita'],
                'id_usuario'        => (int) $data['id_usuario'],
                'texto'             => $data['texto'],
                'id_comentario_pai' => isset($data['id_comentario_pai']) ? (int) $data['id_comentario_pai'] : null,
            ]);

            Logger::info('comentarios', 'Comentário postado', [
                'usuario_id' => $data['id_usuario'],
                'receita_id' => $data['id_receita'],
                'resposta'   => isset($data['id_comentario_pai']) ? 'sim' : 'nao'
            ]);

            http_response_code(201);
            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            Logger::error('comentarios', 'Erro ao salvar comentário', [
                'usuario_id' => $data['id_usuario'],
                'receita_id' => $data['id_receita'],
                'error'       => $e->getMessage()
            ]);
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao salvar comentário.']);
        }
    }
}
