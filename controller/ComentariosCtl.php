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
use App\Config\Auth;
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
        // Quem comenta é sempre o usuário do login (nunca o id do corpo)
        $user = Auth::requireAuth();
        $data['id_usuario'] = (int) $user['id'];

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
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'COMENTARIO_RAIZ_DUPLICADO') {
                Logger::warn('comentarios', 'Tentativa de segundo comentário raiz', [
                    'usuario_id' => $data['id_usuario'],
                    'receita_id' => $data['id_receita'],
                ]);

                return [
                    'status' => 409,
                    'body'   => [
                        'error' => 'Você já comentou nesta receita. Exclua seu comentário atual para escrever outro.',
                        'code'  => 'COMENTARIO_RAIZ_DUPLICADO',
                    ],
                ];
            }

            throw $e;
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

    /**
     * Edita o texto do próprio comentário (avaliação com texto).
     *
     * @return array{status: int, body: array}
     */
    public function update(int $id, array $data): array
    {
        $user = Auth::requireAuth();

        $texto = trim((string) ($data['texto'] ?? ''));
        $tamanho = mb_strlen($texto);
        if ($tamanho < 3 || $tamanho > 1000) {
            return [
                'status' => 400,
                'body'   => ['error' => 'O texto deve ter entre 3 e 1000 caracteres.'],
            ];
        }

        $comentario = $this->model->buscarPorId($id);
        if (!$comentario) {
            return ['status' => 404, 'body' => ['error' => 'Comentário não encontrado.']];
        }

        // Só o dono edita (admin pode excluir, mas não reescrever o texto dos outros)
        if ((int) $comentario['id_usuario'] !== (int) $user['id']) {
            return ['status' => 403, 'body' => ['error' => 'Você só pode editar o seu comentário.']];
        }

        $this->model->atualizarTexto($id, $texto);
        Logger::info('comentarios', 'Comentário editado', ['comentario_id' => $id, 'usuario_id' => $user['id']]);

        return ['status' => 200, 'body' => ['success' => true]];
    }

    /**
     * Exclui um comentário (e suas respostas em cascata).
     * Só o dono do comentário ou um admin pode excluir — Auth::requireAuth()
     * e Auth::requireOwnership() encerram a execução com 401/403 caso
     * contrário, então se chegarmos até aqui é porque está autorizado.
     *
     * @param int $id  ID extraído da query string pela rota.
     * @return array{status: int, body: array}
     */
    public function destroy(int $id): array
    {
        $user = Auth::requireAuth();

        $comentario = $this->model->buscarPorId($id);

        if (!$comentario) {
            return [
                'status' => 404,
                'body'   => ['error' => 'Comentário não encontrado.'],
            ];
        }

        Auth::requireOwnership($user, (int) $comentario['id_usuario']);

        try {
            $this->model->excluir($id);

            Logger::info('comentarios', 'Comentário excluído', [
                'comentario_id' => $id,
                'usuario_id'    => $user['id'],
            ]);

            return [
                'status' => 200,
                'body'   => ['success' => true],
            ];
        } catch (Exception $e) {
            Logger::error('comentarios', 'Erro ao excluir comentário', [
                'comentario_id' => $id,
                'error'         => $e->getMessage(),
            ]);

            return [
                'status' => 500,
                'body'   => ['error' => 'Erro ao excluir comentário.'],
            ];
        }
    }
}