<?php
class UserModel
{
    private $conn;
    private $table = "receitas";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function buscarUserPorId($id)
    {
        // Dados básicos do usuário
        $sql = "SELECT id, nome, sobrenome, apelido, email, genero, bio, foto_perfil
                FROM usuarios
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        return $usuario;
    }

}
