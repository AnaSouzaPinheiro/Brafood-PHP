<?php

namespace App\Model;

use App\Config\Database;
use PDO;

class AvaliacaoModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /** Nota do usuário para uma receita específica (null se não avaliou) */
    public function buscarNota(int $idUsuario, int $idReceita): ?int
    {
        $stmt = $this->conn->prepare(
            'SELECT nota FROM avaliacoes WHERE id_usuario = ? AND id_receita = ?'
        );
        $stmt->execute([$idUsuario, $idReceita]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['nota'] : null;
    }

    /** Média e total de avaliações de uma receita */
    public function buscarMedia(int $idReceita): array
    {
        $stmt = $this->conn->prepare(
            'SELECT media_avaliacao, qtd_avaliacoes FROM receitas WHERE id = ?'
        );
        $stmt->execute([$idReceita]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'media'  => $row ? (float) $row['media_avaliacao'] : null,
            'total'  => $row ? (int)   $row['qtd_avaliacoes']  : 0,
        ];
    }

    /**
     * Registra ou atualiza a avaliação do usuário e recalcula a média na tabela receitas.
     * Retorna a nova média e total.
     */
    public function avaliar(int $idUsuario, int $idReceita, int $nota): array
    {
        $this->conn->beginTransaction();

        try {
            // Upsert da avaliação
            $stmt = $this->conn->prepare(
                'INSERT INTO avaliacoes (id_usuario, id_receita, nota)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE nota = VALUES(nota)'
            );
            $stmt->execute([$idUsuario, $idReceita, $nota]);

            // Recalcula média e total e persiste em receitas (desnormalizado)
            $stmt = $this->conn->prepare(
                'UPDATE receitas r
                 JOIN (
                     SELECT id_receita,
                            ROUND(AVG(nota), 2) AS nova_media,
                            COUNT(*)            AS novo_total
                     FROM avaliacoes
                     WHERE id_receita = ?
                 ) calc ON calc.id_receita = r.id
                 SET r.media_avaliacao = calc.nova_media,
                     r.qtd_avaliacoes  = calc.novo_total
                 WHERE r.id = ?'
            );
            $stmt->execute([$idReceita, $idReceita]);

            // Busca os valores atualizados
            $stmt = $this->conn->prepare(
                'SELECT media_avaliacao, qtd_avaliacoes FROM receitas WHERE id = ?'
            );
            $stmt->execute([$idReceita]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->conn->commit();

            return [
                'media' => (float) $row['media_avaliacao'],
                'total' => (int)   $row['qtd_avaliacoes'],
            ];
        } catch (\Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /** Remove a avaliação do usuário e recalcula a média */
    public function remover(int $idUsuario, int $idReceita): array
    {
        $this->conn->beginTransaction();

        try {
            $stmt = $this->conn->prepare(
                'DELETE FROM avaliacoes WHERE id_usuario = ? AND id_receita = ?'
            );
            $stmt->execute([$idUsuario, $idReceita]);

            $stmt = $this->conn->prepare(
                'UPDATE receitas SET
                    media_avaliacao = (
                        SELECT ROUND(AVG(nota), 2) FROM avaliacoes WHERE id_receita = ?
                    ),
                    qtd_avaliacoes = (
                        SELECT COUNT(*) FROM avaliacoes WHERE id_receita = ?
                    )
                 WHERE id = ?'
            );
            $stmt->execute([$idReceita, $idReceita, $idReceita]);

            $stmt = $this->conn->prepare(
                'SELECT media_avaliacao, qtd_avaliacoes FROM receitas WHERE id = ?'
            );
            $stmt->execute([$idReceita]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->conn->commit();

            return [
                'media' => $row['media_avaliacao'] ? (float) $row['media_avaliacao'] : null,
                'total' => (int) $row['qtd_avaliacoes'],
            ];
        } catch (\Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
