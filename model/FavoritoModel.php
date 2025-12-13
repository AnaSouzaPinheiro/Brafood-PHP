<?php
class FavoritoModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function isFavorite($idUser, $idReceita)
    {
        $sql = "SELECT 1 FROM favoritos 
                WHERE id_usuario = :idUser AND id_receita = :idReceita";
        $stmt = $this->db->prepare($sql);
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
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':idUser' => $idUser,
            ':idReceita' => $idReceita
        ]);
    }

    public function remove($idUser, $idReceita)
    {
        $sql = "DELETE FROM favoritos
                WHERE id_usuario = :idUser AND id_receita = :idReceita";
        $stmt = $this->db->prepare($sql);
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
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':idUser' => $idUser]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
