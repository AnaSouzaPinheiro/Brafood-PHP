<?php

/**
 * Controla a edição do perfil do usuário.
 */

require_once __DIR__ . '/../model/UserModel.php';

class UserCtl
{
    private UserModel $model;

    public function __construct($db)
    {
        $this->model = new UserModel($db);
    }

    public function editarUsuario(array $dados): array
    {
        $id = (int) $dados['id'];

        try {
            $this->model->editarUsuario(
                $id,
                $dados['nome']        ?? '',
                $dados['sobrenome']   ?? '',
                $dados['apelido']     ?? '',
                $dados['email']       ?? '',
                $dados['genero']      ?? '',
                $dados['bio']         ?? '',
                $dados['foto_perfil'] ?? null
            );

            Logger::info('usuario/update', 'Perfil atualizado', [
                'usuario_id' => $id
            ]);

            return [
                'success'  => true,
                'message' => 'Usuário editado com sucesso!',
                'id'       => $id
            ];

        } catch (Exception $e) {
            Logger::error('usuario/update', 'Erro ao editar perfil', [
                'usuario_id' => $id,
                'error'       => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function buscarUsuarioPorId(int $id): array
    {
        if ($id <= 0) {
            http_response_code(400);

            return [
                'success' => false,
                'message' => 'ID inválido.'
            ];
        }

        $usuario = $this->model->buscarUserPorId($id);

        if (!$usuario) {
            http_response_code(404);

            return [
                'success' => false,
                'message' => 'Usuário não encontrado.'
            ];
        }

        return [
            'success' => true,
            'usuario' => $usuario
        ];
    }
}
