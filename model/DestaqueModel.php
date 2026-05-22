<?php

namespace App\Model;

use App\Config\Database;
use PDO;

class DestaqueModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /** Retorna a receita em destaque ou null se não houver nenhuma */
    public function buscarDestaque(): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT
                r.id,
                r.titulo,
                r.resumo,
                r.imagem1,
                r.qtd_fav,
                r.media_avaliacao,
                r.qtd_avaliacoes,
                r.destaque_desde,
                CONCAT(u.nome, ' ', COALESCE(u.sobrenome, '')) AS autor,
                c.nome AS categoria,
                s.nome AS subcategoria
            FROM receitas r
            LEFT JOIN usuarios u ON u.id = r.id_usuario
            LEFT JOIN receita_subcategoria rs ON rs.id_receita = r.id
            LEFT JOIN subcategorias s ON s.id = rs.id_subcategoria
            LEFT JOIN categorias c ON c.id = s.id_categoria
            WHERE r.destaque = 1
            GROUP BY r.id
            LIMIT 1
        ");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Ativa o destaque de uma receita e desativa todas as outras */
    public function ativarDestaque(int $idReceita): bool
    {
        $this->conn->beginTransaction();
        try {
            // Remove destaque de todas
            $this->conn->exec("UPDATE receitas SET destaque = 0, destaque_desde = NULL");
            // Ativa só esta
            $stmt = $this->conn->prepare("
                UPDATE receitas
                SET destaque = 1, destaque_desde = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$idReceita]);
            $this->conn->commit();
            return true;
        } catch (\Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /** Remove o destaque (nenhuma receita fica destacada) */
    public function removerDestaque(): bool
    {
        $stmt = $this->conn->exec(
            "UPDATE receitas SET destaque = 0, destaque_desde = NULL WHERE destaque = 1"
        );
        return $stmt !== false;
    }

    /** Lista receitas para o seletor do admin (id + titulo + imagem) */
    public function listarParaSelector(string $busca = ''): array
    {
        if ($busca !== '') {
            $stmt = $this->conn->prepare("
                SELECT id, titulo, imagem1, destaque
                FROM receitas
                WHERE titulo LIKE ?
                ORDER BY titulo
                LIMIT 20
            ");
            $stmt->execute(['%' . $busca . '%']);
        } else {
            $stmt = $this->conn->prepare("
                SELECT id, titulo, imagem1, destaque
                FROM receitas
                ORDER BY data_criacao DESC
                LIMIT 20
            ");
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
