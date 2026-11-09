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
        // A nota exibida junto do comentário vem sempre "ao vivo" da tabela
        // avaliacoes (LEFT JOIN por usuário+receita), nunca da coluna
        // comentarios.nota — assim ela nunca fica dessincronizada se o
        // usuário alterar a nota depois (ex: pelas estrelas, sem reescrever
        // o comentário). Só comentários raiz mostram nota; respostas não
        // carregam avaliação própria.
        $sql = "SELECT
                    c.id,
                    c.id_receita,
                    c.id_usuario,
                    c.id_comentario_pai,
                    c.texto,
                    CASE
                        WHEN c.id_comentario_pai IS NULL THEN av.nota
                        ELSE NULL
                    END AS nota,
                    c.data_criacao,
                    u.nome AS nome_usuario,
                    u.apelido AS apelido_usuario,
                    u.foto_perfil,
                    0 AS so_nota
                FROM comentarios c
                JOIN usuarios u ON u.id = c.id_usuario
                LEFT JOIN avaliacoes av
                    ON av.id_usuario = c.id_usuario AND av.id_receita = c.id_receita
                WHERE c.id_receita = ?

                UNION ALL

                -- Quem só deu estrelas (sem texto) também aparece na lista.
                -- id negativo: não é um comentário (não recebe resposta).
                SELECT
                    -av.id,
                    av.id_receita,
                    av.id_usuario,
                    NULL,
                    '',
                    av.nota,
                    av.criado_em,
                    u.nome,
                    u.apelido,
                    u.foto_perfil,
                    1
                FROM avaliacoes av
                JOIN usuarios u ON u.id = av.id_usuario
                WHERE av.id_receita = ?
                  AND NOT EXISTS (
                      SELECT 1 FROM comentarios c
                      WHERE c.id_receita = av.id_receita
                        AND c.id_usuario = av.id_usuario
                        AND c.id_comentario_pai IS NULL
                  )

                ORDER BY data_criacao ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idReceita, $idReceita]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Verifica se o usuário já tem um comentário raiz (não-resposta) nessa receita */
    public function possuiComentarioRaiz(int $idUsuario, int $idReceita): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT 1 FROM comentarios
             WHERE id_usuario = ? AND id_receita = ? AND id_comentario_pai IS NULL
             LIMIT 1"
        );
        $stmt->execute([$idUsuario, $idReceita]);

        return (bool) $stmt->fetchColumn();
    }

    public function criar(array $data): bool
    {
        $idComentarioPai = $data['id_comentario_pai'] ?? null;

        // Regra de negócio: um usuário pode ter só um comentário raiz por
        // receita (mas pode responder livremente). Isso evita ambiguidade
        // de qual comentário "carrega" a nota do usuário na listagem.
        if ($idComentarioPai === null) {
            $jaComentou = $this->possuiComentarioRaiz(
                (int) $data['id_usuario'],
                (int) $data['id_receita'],
            );

            if ($jaComentou) {
                throw new \RuntimeException('COMENTARIO_RAIZ_DUPLICADO');
            }
        }

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

    /** Troca o texto de um comentário (edição da avaliação). */
    public function atualizarTexto(int $id, string $texto): bool
    {
        $stmt = $this->conn->prepare("UPDATE comentarios SET texto = ? WHERE id = ?");
        return $stmt->execute([$texto, $id]);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, id_usuario, id_comentario_pai FROM comentarios WHERE id = ?"
        );
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Exclui um comentário e todas as suas respostas, em qualquer
     * profundidade (resposta de resposta etc.).
     */
    public function excluir(int $id): bool
    {
        $ids = $this->coletarIdsComRespostas($id);

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->conn->prepare(
            "DELETE FROM comentarios WHERE id IN ($placeholders)"
        );

        return $stmt->execute($ids);
    }

    /**
     * Coleta recursivamente o id do comentário + ids de todas as respostas
     * (e respostas de respostas), pra exclusão em cascata manual — não
     * depende de ON DELETE CASCADE existir na FK.
     */
    private function coletarIdsComRespostas(int $id): array
    {
        $ids = [$id];

        $stmt = $this->conn->prepare(
            "SELECT id FROM comentarios WHERE id_comentario_pai = ?"
        );
        $stmt->execute([$id]);
        $filhos = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($filhos as $filhoId) {
            $ids = array_merge($ids, $this->coletarIdsComRespostas((int) $filhoId));
        }

        return $ids;
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
        $stmt->execute([$idReceita, $idReceita]);
    }
}