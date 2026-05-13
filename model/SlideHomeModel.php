<?php

class SlideHome
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

    public function getRandomRecipes($limit = 5)
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, titulo, resumo, imagem1, qtd_fav 
             FROM receitas 
             ORDER BY RAND() 
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($r) {
            return [
                'img' => $r['imagem1'],
                'url' => '/receita/' . $this->slugify($r['titulo']) . '/' . $r['id'],
                'resume' => [
                    'title'  => $r['titulo'],
                    'qtdFav' => $r['qtd_fav'] ?? 0,
                    'text'   => $r['resumo'],
                ],
            ];
        }, $rows);
    }

    public function slugify($text)
    {
        $text = mb_strtolower($text, 'UTF-8');

        $text = preg_replace('/[áàãâä]/u', 'a', $text);
        $text = preg_replace('/[éèêë]/u', 'e', $text);
        $text = preg_replace('/[íìîï]/u', 'i', $text);
        $text = preg_replace('/[óòõôö]/u', 'o', $text);
        $text = preg_replace('/[úùûü]/u', 'u', $text);
        $text = preg_replace('/[ç]/u', 'c', $text);

        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');

        return $text;
    }
}
