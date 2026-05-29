<?php

/**
 * Controla comentários de receitas.
 * Suporta listagem (GET) e criação (POST), incluindo respostas aninhadas via id_comentario_pai.
 *
 * Os métodos retornam arrays — a camada de rota é responsável por
 * chamar http_response_code() e echo json_encode(), o que torna
 * este controller totalmente testável sem output de buffer.
 */

namespace App\Controller;

use App\Model\ComentarioModel;
use App\Config\Logger;
use Exception;

class ComentariosCtl
{
    private $model;

    public function __construct()
    {
        $this->model = new ComentarioModel();
    }

    /**
     * Lista comentários de uma receita.
     *
     * @param int|null $idReceita  ID extraído da query string pela rota.
     * @return array{status: int, body: array}
     */
    public function index(?int $idReceita): array
    {
        if (!$idReceita) {
            return [
                'status' => 400,
                'body'   => ['error' => 'Receita não informada'],
            ];
        }

        $comentarios = $this->model->buscarComentarioPorReceita($idReceita);

        return [
            'status' => 200,
            'body'   => $comentarios,
        ];
    }

    /**
     * Cria um novo comentário (ou resposta aninhada).
     *
     * @param array $data  Payload decodificado do body JSON.
     * @return array{status: int, body: array}
     */
    public function store(array $data): array
    {
        if (empty($data['id_receita']) || empty($data['texto']) || empty($data['id_usuario'])) {
            Logger::warn('comentarios', 'Tentativa de comentário com dados inválidos', [
                'usuario_id' => $data['id_usuario'] ?? '(ausente)',
                'receita_id' => $data['id_receita'] ?? '(ausente)',
            ]);

            return [
                'status' => 400,
                'body'   => ['error' => 'Dados inválidos: id_receita, id_usuario e texto são obrigatórios.'],
            ];
        }

        try {
            $this->model->criar([
                'id_receita'        => (int) $data['id_receita'],
                'id_usuario'        => (int) $data['id_usuario'],
                'texto'             => $data['texto'],
                'id_comentario_pai' => isset($data['id_comentario_pai']) ? (int) $data['id_comentario_pai'] : null,
                'nota'              => isset($data['nota']) ? (int) $data['nota'] : null,
            ]);

            Logger::info('comentarios', 'Comentário postado', [
                'usuario_id' => $data['id_usuario'],
                'receita_id' => $data['id_receita'],
                'resposta'   => isset($data['id_comentario_pai']) ? 'sim' : 'nao',
            ]);

            return [
                'status' => 201,
                'body'   => ['success' => true],
            ];
        } catch (Exception $e) {
            Logger::error('comentarios', 'Erro ao salvar comentário', [
                'usuario_id' => $data['id_usuario'],
                'receita_id' => $data['id_receita'],
                'error'       => $e->getMessage(),
            ]);

            return [
                'status' => 500,
                'body'   => ['error' => 'Erro ao salvar comentário.'],
            ];
        }
    }
}
