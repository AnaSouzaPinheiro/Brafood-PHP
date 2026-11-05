<?php

namespace App\Model;

use App\Config\Database;
use PDO;

class SecaoCarrossel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getAllSecoes()
    {
        $stmt = $this->conn->prepare("SELECT * FROM secoes_carrossel ORDER BY ordem ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReceitasBySecao(
        int $secao_id,
        array $idsIgnorar = [],
        int $semente = 0
    ) {
        $params = [
            ':secao_id' => $secao_id,
            ':seed'     => $semente,
        ];

        $notIn = '';

        // Evita receitas repetidas entre seções
        if (!empty($idsIgnorar)) {

            $placeholders = [];

            foreach ($idsIgnorar as $i => $id) {
                $key = ":ignore$i";

                $placeholders[] = $key;
                $params[$key] = (int) $id;
            }

            $notIn = ' AND r.id NOT IN (' . implode(',', $placeholders) . ')';
        }

        $sql = "SELECT
                    r.*,
                    c.id AS categoria_id,
                    c.nome AS categoria,
                    s.id AS subcategoria_id,
                    s.nome AS subcategoria_nome
                FROM receitas r
                INNER JOIN receitas_secoes_carrossel rs ON r.id = rs.receita_id
                LEFT JOIN receita_subcategoria rs2 ON rs2.id_receita = r.id
                LEFT JOIN subcategorias s ON s.id = rs2.id_subcategoria
                LEFT JOIN categorias c ON c.id = s.id_categoria
                WHERE rs.secao_id = :secao_id
                $notIn
                GROUP BY r.id
                ORDER BY RAND(:seed)";

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getReceitasAleatorias(int $limit = 6, ?int $idCategoria = null, ?int $idSubcategoria = null, array $idsIgnorar = [], int $semente = 0)
    {
        $params = [':limit' => $limit, ':seed' => $semente];
        $whereConditions = [];

        // Filtro por subcategoria
        if ($idSubcategoria) {
            $whereConditions[] = 'rs.id_subcategoria = :idSub';
            $params[':idSub'] = $idSubcategoria;
        }

        // Filtro por categoria
        elseif ($idCategoria) {
            $whereConditions[] = 's.id_categoria = :idCat';
            $params[':idCat'] = $idCategoria;
        }

        // Evita receitas já usadas
        if (!empty($idsIgnorar)) {
            $placeholders = [];

            foreach ($idsIgnorar as $i => $id) {
                $key = ":ignore$i";
                $placeholders[] = $key;
                $params[$key] = (int) $id;
            }

            $whereConditions[] = 'r.id NOT IN (' . implode(',', $placeholders) . ')';
        }

        $where = '';

        if (!empty($whereConditions)) {
            $where = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        $sql = "SELECT
                    r.id,
                    r.titulo,
                    r.resumo,
                    r.imagem1,
                    r.qtd_fav,
                    r.media_avaliacao,
                    r.qtd_avaliacoes,
                    c.nome AS categoria,
                    s.nome AS subcategoria
                FROM receitas r
                LEFT JOIN receita_subcategoria rs ON rs.id_receita = r.id
                LEFT JOIN subcategorias s ON s.id = rs.id_subcategoria
                LEFT JOIN categorias c ON c.id = s.id_categoria
                $where
                GROUP BY r.id
                ORDER BY RAND(:seed)
                LIMIT :limit";

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':seed') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReceitasDaSecao(array $secao, array $idsIgnorar = [], int $semente = 0)
    {
        $modo = $secao['modo'] ?? 'aleatorio';
        // Cada seção tem sua própria ordem, mas estável durante o dia
        $semente += (int) ($secao['id'] ?? 0);

        // ── Manual
        if ($modo === 'manual') {
            return $this->getReceitasBySecao(
                (int) $secao['id'],
                $idsIgnorar,
                $semente
            );
        }

        // ── Aleatório
        return $this->getReceitasAleatorias(
            6,
            isset($secao['id_categoria'])
                ? (int) $secao['id_categoria']
                : null,

            isset($secao['id_subcategoria'])
                ? (int) $secao['id_subcategoria']
                : null,

            $idsIgnorar,
            $semente
        );
    }

    /**
     * Receitas das seções automáticas.
     * $tipo: novidades | mais_favoritadas | mais_avaliadas | categoria
     */
    public function getReceitasAuto(string $tipo, int $limit, array $idsIgnorar = [], ?int $idCategoria = null, int $semente = 0): array
    {
        $params = [':limit' => $limit];
        $where  = [];

        switch ($tipo) {
            case 'novidades':
                $order = 'r.data_criacao DESC';
                break;
            case 'mais_favoritadas':
                $where[] = 'r.qtd_fav > 0';
                $order   = 'r.qtd_fav DESC, r.data_criacao DESC';
                break;
            case 'mais_avaliadas':
                $where[] = 'r.qtd_avaliacoes >= 1 AND r.media_avaliacao >= 3.5';
                $order   = 'r.media_avaliacao DESC, r.qtd_avaliacoes DESC';
                break;
            case 'categoria':
                $where[] = 's.id_categoria = :idCat';
                $params[':idCat'] = (int) $idCategoria;
                $params[':seed']  = $semente;
                $order = 'RAND(:seed)';
                break;
            default:
                return [];
        }

        foreach (array_values($idsIgnorar) as $i => $id) {
            $params[":ignore$i"] = (int) $id;
        }
        if ($idsIgnorar) {
            $where[] = 'r.id NOT IN (' . implode(',', array_map(fn($i) => ":ignore$i", array_keys(array_values($idsIgnorar)))) . ')';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT
                    r.id, r.titulo, r.resumo, r.imagem1, r.qtd_fav,
                    r.media_avaliacao, r.qtd_avaliacoes,
                    c.nome AS categoria,
                    s.nome AS subcategoria
                FROM receitas r
                LEFT JOIN receita_subcategoria rs ON rs.id_receita = r.id
                LEFT JOIN subcategorias s ON s.id = rs.id_subcategoria
                LEFT JOIN categorias c ON c.id = s.id_categoria
                $whereSql
                GROUP BY r.id
                ORDER BY $order
                LIMIT :limit";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Categorias com pelo menos $minimo receitas (para a "categoria da semana") */
    public function categoriasComReceitas(int $minimo): array
    {
        $stmt = $this->conn->prepare(
            "SELECT c.id, c.nome, COUNT(DISTINCT rs.id_receita) AS total
             FROM categorias c
             JOIN subcategorias s ON s.id_categoria = c.id
             JOIN receita_subcategoria rs ON rs.id_subcategoria = s.id
             GROUP BY c.id, c.nome
             HAVING total >= :minimo
             ORDER BY c.id"
        );
        $stmt->bindValue(':minimo', $minimo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createSecao($titulo, $url_fundo, $fundo_titulo, $ordem = 0)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO secoes_carrossel 
            (titulo, url_fundo, fundo_titulo, ordem)
            VALUES (:titulo, :url_fundo, :fundo_titulo, :ordem)
        ");
        $stmt->execute([
            ':titulo' => $titulo,
            ':url_fundo' => $url_fundo,
            ':fundo_titulo' => $fundo_titulo,
            ':ordem' => $ordem,
        ]);
        return $this->conn->lastInsertId();
    }

    public function addReceitaToSecao($secao_id, $receita_id)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO receitas_secoes_carrossel (secao_id, receita_id)
            VALUES (:secao_id, :receita_id)
        ");
        $stmt->execute([
            ':secao_id' => $secao_id,
            ':receita_id' => $receita_id,
        ]);
    }
}
