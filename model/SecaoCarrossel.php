<?php

class SecaoCarrossel
{
    private $pdo;

    /**
     * @param \PDO|null $pdo  Conexão injetada. Se null, instancia Database internamente
     *                        (compatibilidade com o código de produção existente).
     */
    public function __construct($pdo = null)
    {
        if ($pdo !== null) {
            $this->pdo = $pdo;
        } else {
            require_once __DIR__ . '/../config/Database.php';
            $db        = new Database();
            $this->pdo = $db->getConnection();
        }
    }

    public function getAllSecoes()
    {
        $stmt = $this->pdo->prepare("SELECT * FROM secoes_carrossel ORDER BY ordem ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReceitasBySecao($secao_id)
    {
        $stmt = $this->pdo->prepare("
        SELECT 
            r.*,
            c.id AS categoria_id,
            c.nome AS categoria,
            s.id AS subcategoria_id,
            s.nome AS subcategoria_nome
        FROM receitas r
        INNER JOIN receitas_secoes_carrossel rs 
            ON r.id = rs.receita_id
        LEFT JOIN receita_subcategoria rs2 
            ON rs2.id_receita = r.id
        LEFT JOIN subcategorias s 
            ON s.id = rs2.id_subcategoria
        LEFT JOIN categorias c 
            ON c.id = s.id_categoria
        WHERE rs.secao_id = :secao_id
        GROUP BY r.id
        ORDER BY rand()
    ");
        $stmt->bindParam(':secao_id', $secao_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createSecao($titulo, $url_fundo, $fundo_titulo, $ordem = 0)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO secoes_carrossel 
            (titulo, url_fundo, fundo_titulo, ordem)
            VALUES (:titulo, :url_fundo, :fundo_titulo, :ordem)
        ");
        $stmt->execute([
            ':titulo'       => $titulo,
            ':url_fundo'    => $url_fundo,
            ':fundo_titulo' => $fundo_titulo,
            ':ordem'        => $ordem,
        ]);
        return $this->pdo->lastInsertId();
    }

    public function addReceitaToSecao($secao_id, $receita_id)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO receitas_secoes_carrossel (secao_id, receita_id)
            VALUES (:secao_id, :receita_id)
        ");
        $stmt->execute([
            ':secao_id'   => $secao_id,
            ':receita_id' => $receita_id,
        ]);
    }

    private function getReceitasAleatorias($limit = 6)
    {
        $stmt = $this->pdo->prepare("
        SELECT 
            r.id,
            r.titulo,
            r.resumo,
            r.imagem1,
            r.qtd_fav,
            c.nome AS categoria
        FROM receitas r
        LEFT JOIN receita_subcategoria rs 
            ON rs.id_receita = r.id
        LEFT JOIN subcategorias s 
            ON s.id = rs.id_subcategoria
        LEFT JOIN categorias c 
            ON c.id = s.id_categoria
        ORDER BY RAND()
        LIMIT :limit
    ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReceitasDaSecao($secao)
    {
        if (empty($secao['id_categoria']) && empty($secao['id_subcategoria'])) {
            return $this->getReceitasAleatorias(6);
        }

        return $this->getReceitasBySecao($secao['id']);
    }
}
