<?php
require_once __DIR__ . '/../config/database.php';

class ComentarioModel {
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function buscarComentarioPorReceita(int $idReceita): array {
        $sql = "SELECT 
                    c.id,
                    c.id_receita,
                    c.id_usuario,
                    c.id_comentario_pai,
                    c.texto,
                    c.data_criacao,
                    u.nome AS nome_usuario,
                    u.foto_perfil
                FROM comentarios c
                JOIN usuarios u ON u.id = c.id_usuario
                WHERE c.id_receita = ?
                ORDER BY c.data_criacao ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idReceita]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function criar(array $data): bool {
        $sql = "INSERT INTO comentarios 
                (id_receita, id_usuario, id_comentario_pai, texto)
                VALUES (?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            $data['id_receita'],
            $data['id_usuario'],
            $data['id_comentario_pai'] ?? null,
            $data['texto']
        ]);
    }
}
