<?php

namespace App\Model;

use App\Config\Database;
use PDO;

class AdminCategoriaModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    // ── Categorias ────────────────────────────────────────────

    public function listarCategorias(): array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, nome, icone, fundo, css_class, ordem, cor_primaria, gradiente_hover
             FROM categorias
             ORDER BY ordem ASC, id ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Apenas categorias que têm ao menos uma receita vinculada.
     * Usado nos selects do painel admin para evitar seções vazias.
     */
    public function listarCategoriasComReceitas(): array
    {
        $stmt = $this->conn->prepare(
            "SELECT DISTINCT c.id, c.nome, c.icone, c.fundo, c.css_class,
                    c.ordem, c.cor_primaria, c.gradiente_hover
             FROM categorias c
             INNER JOIN subcategorias s      ON s.id_categoria = c.id
             INNER JOIN receita_subcategoria rs ON rs.id_subcategoria = s.id
             ORDER BY c.ordem ASC, c.id ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarCategoriaPorId(int $id): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, nome, icone, fundo, css_class, ordem, cor_primaria, gradiente_hover
             FROM categorias WHERE id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function criarCategoria(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO categorias (nome, icone, fundo, css_class, ordem, cor_primaria, gradiente_hover)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['nome'],
            $data['icone']           ?? null,
            $data['fundo']           ?? null,
            $data['css_class']       ?? null,
            $data['ordem']           ?? 99,
            $data['cor_primaria']    ?? null,
            $data['gradiente_hover'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function atualizarCategoria(int $id, array $data): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE categorias
             SET nome = ?, icone = ?, fundo = ?, css_class = ?,
                 ordem = ?, cor_primaria = ?, gradiente_hover = ?
             WHERE id = ?"
        );
        return $stmt->execute([
            $data['nome'],
            $data['icone']           ?? null,
            $data['fundo']           ?? null,
            $data['css_class']       ?? null,
            $data['ordem']           ?? 99,
            $data['cor_primaria']    ?? null,
            $data['gradiente_hover'] ?? null,
            $id,
        ]);
    }

    public function excluirCategoria(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM categorias WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ── Subcategorias ────────────────────────────────────────

    public function listarSubcategorias(?int $idCategoria = null): array
    {
        if ($idCategoria !== null) {
            $stmt = $this->conn->prepare(
                "SELECT s.id, s.nome, s.id_categoria, c.nome AS nome_categoria
                 FROM subcategorias s
                 JOIN categorias c ON c.id = s.id_categoria
                 WHERE s.id_categoria = ?
                 ORDER BY s.nome ASC"
            );
            $stmt->execute([$idCategoria]);
        } else {
            $stmt = $this->conn->prepare(
                "SELECT s.id, s.nome, s.id_categoria, c.nome AS nome_categoria
                 FROM subcategorias s
                 JOIN categorias c ON c.id = s.id_categoria
                 ORDER BY c.ordem ASC, s.nome ASC"
            );
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Apenas subcategorias que têm ao menos uma receita vinculada.
     * Usado nos selects do painel admin para evitar seções vazias.
     */
    public function listarSubcategoriasComReceitas(?int $idCategoria = null): array
    {
        if ($idCategoria !== null) {
            $stmt = $this->conn->prepare(
                "SELECT DISTINCT s.id, s.nome, s.id_categoria, c.nome AS nome_categoria
                 FROM subcategorias s
                 JOIN categorias c ON c.id = s.id_categoria
                 INNER JOIN receita_subcategoria rs ON rs.id_subcategoria = s.id
                 WHERE s.id_categoria = ?
                 ORDER BY s.nome ASC"
            );
            $stmt->execute([$idCategoria]);
        } else {
            $stmt = $this->conn->prepare(
                "SELECT DISTINCT s.id, s.nome, s.id_categoria, c.nome AS nome_categoria
                 FROM subcategorias s
                 JOIN categorias c ON c.id = s.id_categoria
                 INNER JOIN receita_subcategoria rs ON rs.id_subcategoria = s.id
                 ORDER BY c.ordem ASC, s.nome ASC"
            );
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function criarSubcategoria(string $nome, int $idCategoria): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO subcategorias (nome, id_categoria) VALUES (?, ?)"
        );
        $stmt->execute([$nome, $idCategoria]);
        return (int) $this->conn->lastInsertId();
    }

    public function atualizarSubcategoria(int $id, string $nome, int $idCategoria): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE subcategorias SET nome = ?, id_categoria = ? WHERE id = ?"
        );
        return $stmt->execute([$nome, $idCategoria, $id]);
    }

    public function excluirSubcategoria(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM subcategorias WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ── Utilitários ──────────────────────────────────────────

    /** Retorna todas as categorias + subcategorias formatadas para o prompt da IA */
    public function listarParaPromptIA(): array
    {
        $cats = $this->listarCategorias();
        $subs = $this->listarSubcategorias();

        return [
            'categorias'   => array_map(fn($c) => ['id' => (int)$c['id'], 'nome' => $c['nome']], $cats),
            'subcategorias' => array_map(fn($s) => ['id' => (int)$s['id'], 'nome' => $s['nome']], $subs),
        ];
    }

    /** Verifica se o usuário é admin */
    public function isAdmin(int $userId): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT is_admin FROM usuarios WHERE id = ?"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && (int)$row['is_admin'] === 1;
    }
}
