<?php

namespace App\Model;

use App\Config\Database;
use PDO;

class UserModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
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

    public function editarUsuario ($id, $nome, $sobrenome, $apelido, $email, $genero, $bio, $foto_perfil) 
    {
        $sql = 'UPDATE usuarios
                SET nome = :nome,
                    sobrenome = :sobrenome,
                    apelido = :apelido,
                    email = :email,
                    genero = :genero,
                    bio = :bio,
                    foto_perfil = :foto_perfil
                WHERE id = :id';

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':sobrenome', $sobrenome);
        $stmt->bindParam(':apelido', $apelido);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':genero', $genero);
        $stmt->bindParam(':bio', $bio);
        $stmt->bindParam(':foto_perfil', $foto_perfil);

        $stmt->execute();
    }
}
