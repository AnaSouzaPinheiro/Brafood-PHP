<?php

namespace App\Model;

use App\Config\Database;
use PDO;

class ComentarioModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function buscarComentarioPorReceita(int $idReceita): array
    {
        $sql = "SELECT
                    c.id,
                    c.id_receita,
                    c.id_usuario,
                    c.id_comentario_pai,
                    c.texto,
                    c.nota,
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

    public function criar(array $data): bool
    {
        $nota = isset($data['nota']) && $data['nota'] >= 1 && $data['nota'] <= 5
            ? (int) $data['nota']
            : null;

        $sql = "INSERT INTO comentarios
                (id_receita, id_usuario, id_comentario_pai, texto, nota)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);

        $ok = $stmt->execute([
            $data['id_receita'],
            $data['id_usuario'],
            $data['id_comentario_pai'] ?? null,
            $data['texto'],
            $nota,
        ]);

        // Se veio nota, sincroniza com a tabela avaliacoes e recalcula média
        if ($ok && $nota !== null) {
            $this->sincronizarNota((int) $data['id_usuario'], (int) $data['id_receita'], $nota);
        }

        return $ok;
    }

    private function sincronizarNota(int $idUsuario, int $idReceita, int $nota): void
    {
        // Upsert na tabela de avaliações para manter consistência
        $stmt = $this->conn->prepare(
            "INSERT INTO avaliacoes (id_usuario, id_receita, nota)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE nota = VALUES(nota)"
        );
        $stmt->execute([$idUsuario, $idReceita, $nota]);

        // Recalcula média na tabela receitas
        $stmt = $this->conn->prepare(
            "UPDATE receitas r
             JOIN (
                 SELECT id_receita,
                        ROUND(AVG(nota), 2) AS nova_media,
                        COUNT(*)            AS novo_total
                 FROM avaliacoes
                 WHERE id_receita = ?
             ) calc ON calc.id_receita = r.id
             SET r.media_avaliacao = calc.nova_media,
                 r.qtd_avaliacoes  = calc.novo_total
             WHERE r.id = ?"
        );
        var_dump($stmt->execute([$idReceita, $idReceita]));
        $stmt->execute([$idReceita, $idReceita]);
    }
}
