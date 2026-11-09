<?php

namespace App\Config;

use PDO;

/**
 * Autenticação por token (Bearer). O token é gerado no login e guardado
 * na coluna `usuarios.token`; o cliente manda de volta em todo request
 * autenticado via header `Authorization: Bearer <token>`.
 */
class Auth
{
    /**
     * Retorna o usuário autenticado (sem a senha) ou null se o token
     * não veio, é inválido, ou não bate com nenhum usuário.
     */
    public static function usuarioAutenticado(): ?array
    {
        $header = getRequestHeader('Authorization');

        if (!$header || stripos($header, 'Bearer ') !== 0) {
            return null;
        }

        $token = trim(substr($header, 7));

        if ($token === '') {
            return null;
        }

        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare(
            'SELECT id, nome, apelido, email, genero, bio, foto_perfil, is_admin
             FROM usuarios
             WHERE token = :token',
        );
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Exige autenticação. Se não houver usuário válido, responde 401 e
     * encerra a execução — chame no topo de qualquer rota privada.
     */
    public static function requireAuth(): array
    {
        $user = self::usuarioAutenticado();

        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'success'  => false,
                'mensagem' => 'Não autenticado.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        return $user;
    }

    /**
     * Exige que o usuário autenticado seja o dono do recurso (ou admin).
     * Responde 403 e encerra a execução caso contrário.
     */
    public static function requireOwnership(array $user, int $donoId): void
    {
        $ehAdmin = (int) ($user['is_admin'] ?? 0) === 1;

        if (!$ehAdmin && (int) $user['id'] !== $donoId) {
            http_response_code(403);
            echo json_encode([
                'success'  => false,
                'mensagem' => 'Você não tem permissão para acessar este recurso.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
