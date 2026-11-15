<?php

namespace App\Config;

use PDO;

/**
 * Autenticação por token (Bearer). O token é gerado no login e guardado
 * na tabela `sessoes` (um token por aparelho; antes era um só, na coluna
 * `usuarios.token`, e entrar num aparelho derrubava o outro). O cliente manda de volta em todo request
 * autenticado via header `Authorization: Bearer <token>`.
 */
class Auth
{
    /** Dias sem uso após os quais uma sessão deixa de ser aceita */
    public const SESSAO_DIAS_VALIDADE = 180;

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
        $campos = 'u.id, u.nome, u.apelido, u.email, u.genero, u.bio, u.foto_perfil, u.is_admin';

        // 1) Sessões por aparelho (tabela sessoes)
        $usaSessoes = true;
        try {
            $stmt = $conn->prepare(
                "SELECT $campos, s.id AS sessao_id
                 FROM sessoes s
                 JOIN usuarios u ON u.id = s.id_usuario
                 WHERE s.token = :token
                   AND s.ultimo_uso > NOW() - INTERVAL " . self::SESSAO_DIAS_VALIDADE . " DAY"
            );
            $stmt->execute([':token' => $token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Marca o uso (no máximo 1x por dia, para não escrever a cada request)
                $conn->prepare(
                    'UPDATE sessoes SET ultimo_uso = NOW()
                     WHERE id = ? AND ultimo_uso < NOW() - INTERVAL 1 DAY'
                )->execute([$user['sessao_id']]);
                unset($user['sessao_id']);
                return $user;
            }
        } catch (\PDOException $e) {
            // Tabela ainda não criada (migration não rodou): segue para o modo antigo
            $usaSessoes = false;
        }

        // Com a tabela de sessões, token desconhecido ou expirado não vale:
        // o modo antigo existe só para bancos que ainda não rodaram a migration.
        if ($usaSessoes) {
            return null;
        }

        // 2) Modo antigo: token único em usuarios.token (sessões de antes da migration)
        $stmt = $conn->prepare(
            "SELECT $campos FROM usuarios u WHERE u.token = :token"
        );
        $stmt->execute([':token' => $token]);
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
     * Exige autenticação (token Bearer válido) E perfil de administrador.
     * Responde 401 sem token válido e 403 se o usuário não for admin.
     */
    public static function requireAdmin(): array
    {
        $user = self::requireAuth();

        if ((int) ($user['is_admin'] ?? 0) !== 1) {
            http_response_code(403);
            echo json_encode([
                'success'  => false,
                'mensagem' => 'Acesso negado.',
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
