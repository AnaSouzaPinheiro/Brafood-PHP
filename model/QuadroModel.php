<?php

namespace App\Model;

use App\Config\Database;
use PDO;

/**
 * Quadros de receitas salvas. Toda consulta filtra pelo id_usuario,
 * então um usuário nunca enxerga nem altera quadro de outro.
 */
class QuadroModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /** Quadros do usuário com total de receitas e até 4 capas pro mosaico. */
    public function listByUser(int $idUser): array
    {
        $stmt = $this->conn->prepare("
            SELECT q.id, q.nome, q.descricao, COUNT(qr.id_receita) AS total
            FROM quadros q
            LEFT JOIN quadro_receitas qr ON qr.id_quadro = q.id
            WHERE q.id_usuario = :idUser
            GROUP BY q.id, q.nome, q.descricao
            ORDER BY q.ordem, q.criado_em DESC
        ");
        $stmt->execute([':idUser' => $idUser]);
        $quadros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $capas = $this->conn->prepare("
            SELECT r.imagem1
            FROM quadro_receitas qr
            JOIN receitas r ON r.id = qr.id_receita
            WHERE qr.id_quadro = :id AND r.imagem1 IS NOT NULL AND r.imagem1 <> ''
            ORDER BY qr.adicionado_em DESC
            LIMIT 4
        ");

        foreach ($quadros as &$q) {
            $q['id'] = (int) $q['id'];
            $q['total'] = (int) $q['total'];
            $capas->execute([':id' => $q['id']]);
            $q['capas'] = $capas->fetchAll(PDO::FETCH_COLUMN);
        }

        return $quadros;
    }

    /** Resumo do quadro fixo "Favoritos" (todas as receitas salvas). */
    public function resumoFavoritos(int $idUser): array
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM favoritos WHERE id_usuario = :idUser");
        $stmt->execute([':idUser' => $idUser]);
        $total = (int) $stmt->fetchColumn();

        $stmt = $this->conn->prepare("
            SELECT r.imagem1
            FROM favoritos f
            JOIN receitas r ON r.id = f.id_receita
            WHERE f.id_usuario = :idUser AND r.imagem1 IS NOT NULL AND r.imagem1 <> ''
            ORDER BY r.id DESC
            LIMIT 4
        ");
        $stmt->execute([':idUser' => $idUser]);

        return ['total' => $total, 'capas' => $stmt->fetchAll(PDO::FETCH_COLUMN)];
    }

    public function find(int $idUser, int $idQuadro): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT id, nome, descricao FROM quadros
            WHERE id = :id AND id_usuario = :idUser
        ");
        $stmt->execute([':id' => $idQuadro, ':idUser' => $idUser]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $row['id'] = (int) $row['id'];
        return $row;
    }

    public function create(int $idUser, string $nome, ?string $descricao): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO quadros (id_usuario, nome, descricao)
            VALUES (:idUser, :nome, :descricao)
        ");
        $stmt->execute([':idUser' => $idUser, ':nome' => $nome, ':descricao' => $descricao]);
        return (int) $this->conn->lastInsertId();
    }

    public function update(int $idUser, int $idQuadro, string $nome, ?string $descricao): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE quadros SET nome = :nome, descricao = :descricao
            WHERE id = :id AND id_usuario = :idUser
        ");
        return $stmt->execute([
            ':nome' => $nome, ':descricao' => $descricao,
            ':id' => $idQuadro, ':idUser' => $idUser,
        ]);
    }

    public function delete(int $idUser, int $idQuadro): bool
    {
        if (!$this->find($idUser, $idQuadro)) return false;

        $this->conn->beginTransaction();
        try {
            $this->conn->prepare("DELETE FROM quadro_receitas WHERE id_quadro = :id")
                ->execute([':id' => $idQuadro]);
            $this->conn->prepare("DELETE FROM quadros WHERE id = :id AND id_usuario = :idUser")
                ->execute([':id' => $idQuadro, ':idUser' => $idUser]);
            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /** Receitas de um quadro, no mesmo formato de FavoritoModel::listByUser. */
    public function listReceitas(int $idUser, int $idQuadro): array
    {
        $stmt = $this->conn->prepare("
            SELECT r.*, c.nome AS categoria
            FROM quadro_receitas qr
            JOIN quadros q ON q.id = qr.id_quadro AND q.id_usuario = :idUser
            JOIN receitas r ON r.id = qr.id_receita
            LEFT JOIN receita_subcategoria rs ON rs.id_receita = r.id
            LEFT JOIN subcategorias s ON s.id = rs.id_subcategoria
            LEFT JOIN categorias c ON c.id = s.id_categoria
            WHERE qr.id_quadro = :idQuadro
            GROUP BY r.id
            ORDER BY MAX(qr.adicionado_em) DESC
        ");
        $stmt->execute([':idUser' => $idUser, ':idQuadro' => $idQuadro]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addReceita(int $idQuadro, int $idReceita): bool
    {
        $stmt = $this->conn->prepare("
            INSERT IGNORE INTO quadro_receitas (id_quadro, id_receita)
            VALUES (:idQuadro, :idReceita)
        ");
        return $stmt->execute([':idQuadro' => $idQuadro, ':idReceita' => $idReceita]);
    }

    public function removeReceita(int $idUser, int $idQuadro, int $idReceita): bool
    {
        $stmt = $this->conn->prepare("
            DELETE qr FROM quadro_receitas qr
            JOIN quadros q ON q.id = qr.id_quadro
            WHERE qr.id_quadro = :idQuadro AND qr.id_receita = :idReceita
              AND q.id_usuario = :idUser
        ");
        return $stmt->execute([':idQuadro' => $idQuadro, ':idReceita' => $idReceita, ':idUser' => $idUser]);
    }

    /** Tira a receita de todos os quadros do usuário (usado ao desfavoritar). */
    public function removeReceitaDeTodos(int $idUser, int $idReceita): bool
    {
        $stmt = $this->conn->prepare("
            DELETE qr FROM quadro_receitas qr
            JOIN quadros q ON q.id = qr.id_quadro
            WHERE qr.id_receita = :idReceita AND q.id_usuario = :idUser
        ");
        return $stmt->execute([':idReceita' => $idReceita, ':idUser' => $idUser]);
    }

    /** IDs dos quadros do usuário que contêm a receita. */
    public function quadrosDaReceita(int $idUser, int $idReceita): array
    {
        $stmt = $this->conn->prepare("
            SELECT qr.id_quadro
            FROM quadro_receitas qr
            JOIN quadros q ON q.id = qr.id_quadro
            WHERE qr.id_receita = :idReceita AND q.id_usuario = :idUser
        ");
        $stmt->execute([':idReceita' => $idReceita, ':idUser' => $idUser]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
