<?php

namespace App\Model;

use App\Config\Database;
use PDO;

class AdminSecaoModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function isAdmin(int $userId): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM usuarios WHERE id = ? AND is_admin = 1"
        );
        $stmt->execute([$userId]);
        return (bool) $stmt->fetchColumn();
    }

    public function listarSecoes(): array
    {
        $stmt = $this->conn->prepare(
            "SELECT id,
                titulo,

                titulo_cor_tipo,
                titulo_cor_solid,
                titulo_gradiente_1,
                titulo_gradiente_2,

                url_fundo,
                cor_fundo,
                fundo_titulo,
                id_categoria,
                id_subcategoria,
                modo,
                ordem
             FROM secoes_carrossel
             ORDER BY ordem ASC, id ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarSecaoPorId(int $id): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, titulo, url_fundo, cor_fundo, fundo_titulo,
                    id_categoria, id_subcategoria, modo, ordem
             FROM secoes_carrossel WHERE id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function criarSecao(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO secoes_carrossel
            (
                titulo,

                titulo_cor_tipo,
                titulo_cor_solid,
                titulo_gradiente_1,
                titulo_gradiente_2,

                url_fundo,
                cor_fundo,
                fundo_titulo,
                id_categoria,
                id_subcategoria,
                modo,
                ordem
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['titulo'],

            $data['titulo_cor_tipo']   ?? 'gradiente',
            $data['titulo_cor_solid']  ?? null,
            $data['titulo_gradiente_1'] ?? '#8aabff',
            $data['titulo_gradiente_2'] ?? '#0049ff',

            $data['url_fundo']       ?? null,
            $data['cor_fundo']       ?? null,
            $data['fundo_titulo']    ?? null,
            $data['id_categoria']    ?? null,
            $data['id_subcategoria'] ?? null,
            $data['modo']            ?? 'aleatorio',
            $data['ordem']           ?? 99,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function atualizarSecao(int $id, array $data): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE secoes_carrossel SET

                titulo = ?,

                titulo_cor_tipo = ?,
                titulo_cor_solid = ?,
                titulo_gradiente_1 = ?,
                titulo_gradiente_2 = ?,

                url_fundo = ?,
                cor_fundo       = ?,
                fundo_titulo    = ?,
                id_categoria    = ?,
                id_subcategoria = ?,
                modo            = ?,
                ordem           = ?
             WHERE id = ?"
        );
        return $stmt->execute([
            $data['titulo'],

            $data['titulo_cor_tipo']    ?? 'gradiente',
            $data['titulo_cor_solid']   ?? null,
            $data['titulo_gradiente_1'] ?? '#8aabff',
            $data['titulo_gradiente_2'] ?? '#0049ff',

            $data['url_fundo']       ?? null,
            $data['cor_fundo']       ?? null,
            $data['fundo_titulo']    ?? null,
            $data['id_categoria']    ?? null,
            $data['id_subcategoria'] ?? null,
            $data['modo']            ?? 'aleatorio',
            $data['ordem']           ?? 99,

            $id,
        ]);
    }

    public function excluirSecao(int $id): bool
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM secoes_carrossel WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    // ── Gestão de receitas vinculadas (modo manual) ───────────────────────────

    public function listarReceitasDaSecao(int $secaoId): array
    {
        $stmt = $this->conn->prepare(
            'SELECT r.id, r.titulo, r.imagem1
             FROM receitas r
             INNER JOIN receitas_secoes_carrossel rsc ON rsc.receita_id = r.id
             WHERE rsc.secao_id = ?
             ORDER BY r.titulo'
        );
        $stmt->execute([$secaoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function adicionarReceita(int $secaoId, int $receitaId): bool
    {
        // Evita duplicata
        $chk = $this->conn->prepare(
            'SELECT COUNT(*) FROM receitas_secoes_carrossel
             WHERE secao_id = ? AND receita_id = ?'
        );
        $chk->execute([$secaoId, $receitaId]);
        if ($chk->fetchColumn() > 0) return true;

        $stmt = $this->conn->prepare(
            'INSERT INTO receitas_secoes_carrossel (secao_id, receita_id) VALUES (?, ?)'
        );
        return $stmt->execute([$secaoId, $receitaId]);
    }

    public function removerReceita(int $secaoId, int $receitaId): bool
    {
        $stmt = $this->conn->prepare(
            'DELETE FROM receitas_secoes_carrossel
             WHERE secao_id = ? AND receita_id = ?'
        );
        return $stmt->execute([$secaoId, $receitaId]);
    }

    public function buscarReceitas(string $termo): array
    {
        $stmt = $this->conn->prepare(
            'SELECT id, titulo FROM receitas
             WHERE titulo LIKE ? LIMIT 10'
        );
        $stmt->execute(['%' . $termo . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
