<?php

namespace App\Model;

use App\Config\Database;
use PDO;

/**
 * Quadros de receitas salvas. Toda consulta filtra pelo id_usuario,
 * então um usuário nunca enxerga nem altera quadro de outro.
 */
class QuadroModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /** Quadros do usuário com total de receitas e até 4 capas pro mosaico. */
    public function listByUser(int $idUser): array
    {
        $stmt = $this->conn->prepare("
            SELECT q.id, q.nome, q.descricao, q.publico, q.token_publico,
                   COUNT(qr.id_receita) AS total
            FROM quadros q
            LEFT JOIN quadro_receitas qr ON qr.id_quadro = q.id
            WHERE q.id_usuario = :idUser
            GROUP BY q.id, q.nome, q.descricao, q.publico, q.token_publico
            ORDER BY q.ordem, q.criado_em DESC
        ");
        $stmt->execute([':idUser' => $idUser]);
        return $this->comCapas($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** Normaliza tipos e adiciona até 4 capas (na ordem do quadro) a cada quadro. */
    private function comCapas(array $quadros): array
    {
        $capas = $this->conn->prepare("
            SELECT r.imagem1
            FROM quadro_receitas qr
            JOIN receitas r ON r.id = qr.id_receita
            WHERE qr.id_quadro = :id AND r.imagem1 IS NOT NULL AND r.imagem1 <> ''
            ORDER BY qr.ordem, qr.adicionado_em DESC
            LIMIT 4
        ");

        foreach ($quadros as &$q) {
            $q['id'] = (int) $q['id'];
            $q['total'] = (int) $q['total'];
            if (array_key_exists('publico', $q)) $q['publico'] = (bool) $q['publico'];
            $capas->execute([':id' => $q['id']]);
            $q['capas'] = $capas->fetchAll(PDO::FETCH_COLUMN);
        }

        return $quadros;
    }

    /** Resumo do quadro fixo "Favoritos" (todas as receitas salvas). */
    public function resumoFavoritos(int $idUser): array
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM favoritos WHERE id_usuario = :idUser");
        $stmt->execute([':idUser' => $idUser]);
        $total = (int) $stmt->fetchColumn();

        $stmt = $this->conn->prepare("
            SELECT r.imagem1
            FROM favoritos f
            JOIN receitas r ON r.id = f.id_receita
            WHERE f.id_usuario = :idUser AND r.imagem1 IS NOT NULL AND r.imagem1 <> ''
            ORDER BY r.id DESC
            LIMIT 4
        ");
        $stmt->execute([':idUser' => $idUser]);

        return ['total' => $total, 'capas' => $stmt->fetchAll(PDO::FETCH_COLUMN)];
    }

    public function find(int $idUser, int $idQuadro): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT id, nome, descricao, publico, token_publico FROM quadros
            WHERE id = :id AND id_usuario = :idUser
        ");
        $stmt->execute([':id' => $idQuadro, ':idUser' => $idUser]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $row['id'] = (int) $row['id'];
        $row['publico'] = (bool) $row['publico'];
        return $row;
    }

    // ---- compartilhar ------------------------------------------------------

    /** Liga/desliga o link público. O token é criado uma vez e mantido. */
    public function setPublico(int $idUser, int $idQuadro, bool $publico): ?string
    {
        $quadro = $this->find($idUser, $idQuadro);
        if (!$quadro) return null;

        $token = $quadro['token_publico'] ?: bin2hex(random_bytes(8));
        $stmt = $this->conn->prepare("
            UPDATE quadros SET publico = :publico, token_publico = :token
            WHERE id = :id AND id_usuario = :idUser
        ");
        $stmt->execute([
            ':publico' => $publico ? 1 : 0, ':token' => $token,
            ':id' => $idQuadro, ':idUser' => $idUser,
        ]);
        return $token;
    }

    /** Quadro público pelo token, com o autor (null se não existe ou é privado). */
    public function findPublico(string $token): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT q.id, q.nome, q.descricao,
                   u.nome AS autor_nome, u.sobrenome AS autor_sobrenome,
                   u.apelido AS autor_apelido, u.foto_perfil AS autor_foto
            FROM quadros q
            JOIN usuarios u ON u.id = q.id_usuario
            WHERE q.token_publico = :token AND q.publico = 1
        ");
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $row['id'] = (int) $row['id'];
        return $row;
    }

    /** Receitas de um quadro público — sem status/nota (são pessoais). */
    public function listReceitasPublicas(int $idQuadro): array
    {
        $stmt = $this->conn->prepare("
            SELECT r.*, c.nome AS categoria
            FROM quadro_receitas qr
            JOIN receitas r ON r.id = qr.id_receita
            LEFT JOIN receita_subcategoria rs ON rs.id_receita = r.id
            LEFT JOIN subcategorias s ON s.id = rs.id_subcategoria
            LEFT JOIN categorias c ON c.id = s.id_categoria
            WHERE qr.id_quadro = :idQuadro
            GROUP BY r.id
            ORDER BY MIN(qr.ordem), MAX(qr.adicionado_em) DESC
        ");
        $stmt->execute([':idQuadro' => $idQuadro]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Quadros públicos de um usuário (para o perfil público). */
    public function listPublicosDoUsuario(int $idUser): array
    {
        $stmt = $this->conn->prepare("
            SELECT q.id, q.nome, q.descricao, q.token_publico, COUNT(qr.id_receita) AS total
            FROM quadros q
            LEFT JOIN quadro_receitas qr ON qr.id_quadro = q.id
            WHERE q.id_usuario = :idUser AND q.publico = 1
            GROUP BY q.id, q.nome, q.descricao, q.token_publico
            HAVING total > 0
            ORDER BY q.ordem, q.criado_em DESC
        ");
        $stmt->execute([':idUser' => $idUser]);
        return $this->comCapas($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // ---- ordenação ---------------------------------------------------------

    /** Grava a ordem dos quadros do usuário ($ids na ordem desejada). */
    public function reordenarQuadros(int $idUser, array $ids): void
    {
        $stmt = $this->conn->prepare("
            UPDATE quadros SET ordem = :ordem WHERE id = :id AND id_usuario = :idUser
        ");
        $this->conn->beginTransaction();
        try {
            foreach (array_values($ids) as $i => $id) {
                $stmt->execute([':ordem' => $i + 1, ':id' => $id, ':idUser' => $idUser]);
            }
            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /** Grava a ordem das receitas dentro do quadro. Receitas novas (ordem 0) entram no topo. */
    public function reordenarReceitas(int $idQuadro, array $ids): void
    {
        $stmt = $this->conn->prepare("
            UPDATE quadro_receitas SET ordem = :ordem
            WHERE id_quadro = :idQuadro AND id_receita = :idReceita
        ");
        $this->conn->beginTransaction();
        try {
            foreach (array_values($ids) as $i => $id) {
                $stmt->execute([':ordem' => $i + 1, ':idQuadro' => $idQuadro, ':idReceita' => $id]);
            }
            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    // ---- lista de compras --------------------------------------------------

    /**
     * Ingredientes das receitas escolhidas de um quadro, agrupados por receita
     * (e, dentro dela, pelos grupos da própria receita: "Massa", "Cobertura"…).
     * Só receitas que estão de fato no quadro entram.
     */
    public function ingredientes(int $idQuadro, array $idsReceitas): array
    {
        if (!$idsReceitas) return [];
        $marc = implode(',', array_fill(0, count($idsReceitas), '?'));

        $stmt = $this->conn->prepare("
            SELECT r.id, r.titulo, ii.titulo AS grupo, i.descricao
            FROM quadro_receitas qr
            JOIN receitas r ON r.id = qr.id_receita
            LEFT JOIN itens_ingredientes ii ON ii.id_receita = r.id
            LEFT JOIN ingredientes i ON i.id_item = ii.id_item
            WHERE qr.id_quadro = ? AND r.id IN ($marc)
            ORDER BY qr.ordem, qr.adicionado_em DESC, ii.id_item, i.id_ingrediente
        ");
        $stmt->execute(array_merge([$idQuadro], array_values($idsReceitas)));

        $receitas = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $id = (int) $row['id'];
            $receitas[$id] ??= ['id' => $id, 'titulo' => $row['titulo'], 'grupos' => []];
            if ($row['descricao'] === null) continue;
            $grupo = (string) ($row['grupo'] ?? '');
            $receitas[$id]['grupos'][$grupo] ??= ['titulo' => $grupo, 'itens' => []];
            $receitas[$id]['grupos'][$grupo]['itens'][] = $row['descricao'];
        }

        foreach ($receitas as &$r) $r['grupos'] = array_values($r['grupos']);
        return array_values($receitas);
    }

    public function create(int $idUser, string $nome, ?string $descricao): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO quadros (id_usuario, nome, descricao)
            VALUES (:idUser, :nome, :descricao)
        ");
        $stmt->execute([':idUser' => $idUser, ':nome' => $nome, ':descricao' => $descricao]);
        return (int) $this->conn->lastInsertId();
    }

    public function update(int $idUser, int $idQuadro, string $nome, ?string $descricao): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE quadros SET nome = :nome, descricao = :descricao
            WHERE id = :id AND id_usuario = :idUser
        ");
        return $stmt->execute([
            ':nome' => $nome, ':descricao' => $descricao,
            ':id' => $idQuadro, ':idUser' => $idUser,
        ]);
    }

    public function delete(int $idUser, int $idQuadro): bool
    {
        if (!$this->find($idUser, $idQuadro)) return false;

        $this->conn->beginTransaction();
        try {
            $this->conn->prepare("DELETE FROM quadro_receitas WHERE id_quadro = :id")
                ->execute([':id' => $idQuadro]);
            $this->conn->prepare("DELETE FROM quadros WHERE id = :id AND id_usuario = :idUser")
                ->execute([':id' => $idQuadro, ':idUser' => $idUser]);
            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /** Receitas de um quadro, no mesmo formato de FavoritoModel::listByUser. */
    public function listReceitas(int $idUser, int $idQuadro): array
    {
        $stmt = $this->conn->prepare("
            SELECT r.*, c.nome AS categoria,
                   GROUP_CONCAT(DISTINCT c.nome ORDER BY c.nome SEPARATOR ',') AS categorias,
                   f.status, f.nota
            FROM quadro_receitas qr
            JOIN quadros q ON q.id = qr.id_quadro AND q.id_usuario = :idUser
            JOIN receitas r ON r.id = qr.id_receita
            LEFT JOIN favoritos f ON f.id_receita = r.id AND f.id_usuario = :idUser2
            LEFT JOIN receita_subcategoria rs ON rs.id_receita = r.id
            LEFT JOIN subcategorias s ON s.id = rs.id_subcategoria
            LEFT JOIN categorias c ON c.id = s.id_categoria
            WHERE qr.id_quadro = :idQuadro
            GROUP BY r.id
            ORDER BY MIN(qr.ordem), MAX(qr.adicionado_em) DESC
        ");
        $stmt->execute([':idUser' => $idUser, ':idUser2' => $idUser, ':idQuadro' => $idQuadro]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addReceita(int $idQuadro, int $idReceita): bool
    {
        $stmt = $this->conn->prepare("
            INSERT IGNORE INTO quadro_receitas (id_quadro, id_receita)
            VALUES (:idQuadro, :idReceita)
        ");
        return $stmt->execute([':idQuadro' => $idQuadro, ':idReceita' => $idReceita]);
    }

    public function removeReceita(int $idUser, int $idQuadro, int $idReceita): bool
    {
        $stmt = $this->conn->prepare("
            DELETE qr FROM quadro_receitas qr
            JOIN quadros q ON q.id = qr.id_quadro
            WHERE qr.id_quadro = :idQuadro AND qr.id_receita = :idReceita
              AND q.id_usuario = :idUser
        ");
        return $stmt->execute([':idQuadro' => $idQuadro, ':idReceita' => $idReceita, ':idUser' => $idUser]);
    }

    /** Tira a receita de todos os quadros do usuário (usado ao desfavoritar). */
    public function removeReceitaDeTodos(int $idUser, int $idReceita): bool
    {
        $stmt = $this->conn->prepare("
            DELETE qr FROM quadro_receitas qr
            JOIN quadros q ON q.id = qr.id_quadro
            WHERE qr.id_receita = :idReceita AND q.id_usuario = :idUser
        ");
        return $stmt->execute([':idReceita' => $idReceita, ':idUser' => $idUser]);
    }

    /** IDs dos quadros do usuário que contêm a receita. */
    public function quadrosDaReceita(int $idUser, int $idReceita): array
    {
        $stmt = $this->conn->prepare("
            SELECT qr.id_quadro
            FROM quadro_receitas qr
            JOIN quadros q ON q.id = qr.id_quadro
            WHERE qr.id_receita = :idReceita AND q.id_usuario = :idUser
        ");
        $stmt->execute([':idReceita' => $idReceita, ':idUser' => $idUser]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
