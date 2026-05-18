<?php

namespace App\Model;

use App\Config\Database;
use PDO;

class CategoriaModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function listarCategorias()
    {
        $query = "SELECT 
                    c.id AS id_categoria,
                    c.nome AS nome_categoria,
                    c.icone,
                    c.fundo,
                    c.css_class,
                    c.ordem,
                    c.cor_primaria,
                    c.gradiente_hover,
                    s.id AS id_subcategoria,
                    s.nome AS nome_subcategoria
                FROM categorias c
                LEFT JOIN subcategorias s ON s.id_categoria = c.id
                ORDER BY c.ordem ASC, c.id ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $categorias = [];

        foreach ($rows as $row) {
            $catId = $row['id_categoria'];

            if (!isset($categorias[$catId])) {
                $categorias[$catId] = [
                    'id'              => $row['id_categoria'],
                    'nome'            => $row['nome_categoria'],
                    'icone'           => $row['icone'],
                    'fundo'           => isset($row['fundo']) ? $row['fundo'] : null,
                    'css_class'       => $row['css_class'],
                    'ordem'           => (int)$row['ordem'],
                    'cor_primaria'    => $row['cor_primaria'] ?? null,
                    'gradiente_hover' => $row['gradiente_hover'] ?? null,
                    'subcategorias'   => []
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
