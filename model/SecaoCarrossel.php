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
        array $idsIgnorar = []
    ) {
        $params = [
            ':secao_id' => $secao_id
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
                ORDER BY RAND()";

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getReceitasAleatorias(int $limit = 6, ?int $idCategoria = null, ?int $idSubcategoria = null, array $idsIgnorar = [])
    {
        $params = [':limit' => $limit];
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
                    c.nome AS categoria
                FROM receitas r
                LEFT JOIN receita_subcategoria rs ON rs.id_receita = r.id
                LEFT JOIN subcategorias s ON s.id = rs.id_subcategoria
                LEFT JOIN categorias c ON c.id = s.id_categoria
                $where
                GROUP BY r.id
                ORDER BY RAND()
                LIMIT :limit";

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            if ($key === ':limit') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReceitasDaSecao(array $secao, array $idsIgnorar = [])
    {
        $modo = $secao['modo'] ?? 'aleatorio';

        // ── Manual
        if ($modo === 'manual') {
            return $this->getReceitasBySecao(
                (int) $secao['id'],
                $idsIgnorar
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

            $idsIgnorar
        );
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
