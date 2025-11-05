<?php
require_once __DIR__ . '/../config/Database.php';

class SlideHome {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // Pega receitas aleatórias
    public function getRandomRecipes($limit = 5) {
        $stmt = $this->db->getConnection()->prepare(
            "SELECT id, titulo, resumo, imagem1, qtd_fav 
             FROM receitas 
             ORDER BY RAND() 
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function($r) {
            return [
                "img" => $r['imagem1'],
                "url" => "/receita/" . $r['id'],
                "resume" => [
                    "title" => $r['titulo'],
                    "qtdFav" => $r['qtd_fav'] ?? 0,
                    "text" => $r['resumo']
                ]
            ];
        }, $rows);
    }
}
