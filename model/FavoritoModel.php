<?php

namespace App\Model;

use App\Config\Database;
use PDO;
use Exception;

class FavoritoModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function isFavorite($idUser, $idReceita)
    {
        $sql = "SELECT 1 FROM favoritos 
                WHERE id_usuario = :idUser AND id_receita = :idReceita";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':idUser' => $idUser,
            ':idReceita' => $idReceita
        ]);
        return $stmt->rowCount() > 0;
    }

    public function add($idUser, $idReceita)
    {
        $sql = "INSERT IGNORE INTO favoritos (id_usuario, id_receita)
                VALUES (:idUser, :idReceita)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':idUser' => $idUser,
            ':idReceita' => $idReceita
        ]);
    }

    public function remove($idUser, $idReceita)
    {
        $sql = "DELETE FROM favoritos
                WHERE id_usuario = :idUser AND id_receita = :idReceita";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':idUser' => $idUser,
            ':idReceita' => $idReceita
        ]);
    }

    public function listByUser($idUser)
    {
        $sql = "SELECT r.*,
                    c.nome AS categoria
                FROM favoritos f
                LEFT JOIN receitas r ON r.id = f.id_receita
                LEFT JOIN usuarios u ON r.id_usuario = u.id
                LEFT JOIN receita_subcategoria rs ON rs.id_receita = r.id
                LEFT JOIN subcategorias s ON s.id = rs.id_subcategoria
                LEFT JOIN categorias c ON c.id = s.id_categoria
                WHERE f.id_usuario = :idUser
                GROUP BY r.id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':idUser' => $idUser]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addWithIncrement($idUser, $idReceita): array
    {
        try {
            $this->conn->beginTransaction();

            $check = $this->isFavorite($idUser, $idReceita);

            if ($check) {
                $this->conn->rollBack();
                return ['success' => false, 'mensagem' => 'Já favoritado'];
            }

            $this->add($idUser, $idReceita);

            $stmt = $this->conn->prepare("
            UPDATE receitas
            SET qtd_fav = qtd_fav + 1
            WHERE id = :id
        ");

            $stmt->execute([':id' => $idReceita]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Receita não encontrada");
            }

            $this->conn->commit();

            return ['success' => true];
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function removeWithDecrement($idUser, $idReceita): array
    {
        try {
            $this->conn->beginTransaction();

            $this->remove($idUser, $idReceita);

            $stmt = $this->conn->prepare("
            UPDATE receitas
            SET qtd_fav = GREATEST(qtd_fav - 1, 0)
            WHERE id = :id
        ");

            $stmt->execute([':id' => $idReceita]);

            $this->conn->commit();

            return ['success' => true];
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
