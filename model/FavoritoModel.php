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
        $sql = "SELECT r.*
                FROM favoritos f
                JOIN receitas r ON r.id = f.id_receita
                WHERE f.id_usuario = :idUser";
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
                return ['success' => false, 'message' => 'Já favoritado'];
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
