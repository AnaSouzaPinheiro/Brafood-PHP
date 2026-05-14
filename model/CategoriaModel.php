<?php
class CategoriaModel
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function listarCategorias()
    {
        $query = "SELECT 
                    c.id AS id_categoria,
                    c.nome AS nome_categoria,
                    c.icone,
                    c.fundo,
                    c.css_class,
                    s.id AS id_subcategoria,
                    s.nome AS nome_subcategoria
                FROM categorias c
                LEFT JOIN subcategorias s ON s.id_categoria = c.id
                ORDER BY 
                    CASE c.id
                        WHEN 3 THEN 1
                        WHEN 4 THEN 2
                        WHEN 5 THEN 3
                        WHEN 6 THEN 4
                        WHEN 7 THEN 5
                        WHEN 1 THEN 6
                        WHEN 2 THEN 7
                        WHEN 8 THEN 8
                    END";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $categorias = [];

        foreach ($rows as $row) {
            $catId = $row['id_categoria'];

            if (!isset($categorias[$catId])) {
                $categorias[$catId] = [
                    'id' => $row['id_categoria'],
                    'nome' => $row['nome_categoria'],
                    'icone' => $row['icone'],
                    'fundo' => isset($row['fundo']) ? $row['fundo'] : null,
                    'css_class' => $row['css_class'],
                    'subcategorias' => []
                ];
            }

            // se existe subcategoria na linha (LEFT JOIN pode trazer NULL)
            if (!empty($row['id_subcategoria'])) {
                $categorias[$catId]['subcategorias'][] = [
                    'id' => $row['id_subcategoria'],
                    'nome' => $row['nome_subcategoria']
                ];
            }
        }

        // reindexa o array (remove chaves associativas pelos ids)
        $categorias = array_values($categorias);

        return $categorias;
    }

    public function buscarCategoriaPorId($id)
    {
        $categorias = $this->listarCategorias();

        foreach ($categorias as $categoria) {
            if ($categoria['id'] == $id) {
                return $categoria;
            }
        }

        return null;
    }
}
