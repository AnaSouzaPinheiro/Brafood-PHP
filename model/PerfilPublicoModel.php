<?php

namespace App\Model;

use App\Config\Database;
use PDO;

class PerfilPublicoModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function buscarIdPorApelido(string $apelido): ?int
    {
        $stmt = $this->conn->prepare('SELECT id FROM usuarios WHERE LOWER(apelido) = LOWER(?) LIMIT 1');
        $stmt->execute([$apelido]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    public function buscarPerfil(int $id): ?array
    {
        // Dados do usuário
        $stmt = $this->conn->prepare(
            'SELECT id, nome, sobrenome, apelido, bio, foto_perfil
             FROM usuarios WHERE id = ?'
        );
        $stmt->execute([$id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$usuario) return null;

        // Total de favoritos recebidos em todas as receitas do autor
        $stmt = $this->conn->prepare(
            'SELECT COALESCE(SUM(r.qtd_fav), 0) AS total_favoritos,
                    COUNT(r.id)                  AS total_receitas
             FROM receitas r
             WHERE r.id_usuario = ?'
        );
        $stmt->execute([$id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Receitas do autor (resumo para os cards)
        $stmt = $this->conn->prepare(
            'SELECT r.id,
                    r.titulo,
                    r.resumo,
                    r.imagem1,
                    r.qtd_fav,
                    r.media_avaliacao,
                    r.qtd_avaliacoes,
                    c.nome AS categoria
             FROM receitas r
             LEFT JOIN receita_subcategoria rs ON rs.id_receita = r.id
             LEFT JOIN subcategorias s ON s.id = rs.id_subcategoria
             LEFT JOIN categorias c ON c.id = s.id_categoria
             WHERE r.id_usuario = ?
             GROUP BY r.id
             ORDER BY r.data_criacao DESC'
        );
        $stmt->execute([$id]);
        $receitas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'usuario'         => $usuario,
            'total_receitas'  => (int) $stats['total_receitas'],
            'total_favoritos' => (int) $stats['total_favoritos'],
            'receitas'        => $receitas,
        ];
    }
}
