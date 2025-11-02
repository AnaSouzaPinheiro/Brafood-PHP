<?php
class ReceitaModel {
    private $conn;
    private $table = "receitas";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Retorna receitas para os cards
    public function listarTodas()
    {
        $query = "SELECT r.id, r.titulo, r.resumo, r.dificuldade, r.tempo_preparo, r.porcoes,
                        r.qtd_fav, r.data_criacao, r.imagem1,
                        CONCAT(u.nome, ' ', u.sobrenome) AS autor,
                        c.nome AS categoria
                FROM receitas r
                JOIN usuarios u ON r.id_usuario = u.id
                LEFT JOIN receita_subcategoria rs ON rs.id_receita = r.id
                LEFT JOIN subcategorias s ON s.id = rs.id_subcategoria
                LEFT JOIN categorias c ON c.id = s.id_categoria
                ORDER BY r.data_criacao DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        // retorna um array associativo
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }


    // Retorna uma receita completa (com ingredientes e modo de preparo)
    public function buscarPorId($id) {
        $sql = "SELECT r.*, CONCAT(u.nome, ' ', u.sobrenome) AS autor
                FROM receitas r
                JOIN usuarios u ON r.id_usuario = u.id
                WHERE r.id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $receita = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$receita) return null;

        // Seções da receita
        $sqlSecoes = "SELECT id_secao, titulo FROM secoes_receita WHERE id_receita = :id";
        $stmtSecoes = $this->conn->prepare($sqlSecoes);
        $stmtSecoes->bindParam(':id', $id);
        $stmtSecoes->execute();

        $receita["secoes"] = [];

        while ($secao = $stmtSecoes->fetch(PDO::FETCH_ASSOC)) {
            $id_secao = $secao["id_secao"];

            // Ingredientes da seção
            $sqlIng = "SELECT descricao FROM ingredientes WHERE id_secao = :id_secao";
            $stmtIng = $this->conn->prepare($sqlIng);
            $stmtIng->bindParam(':id_secao', $id_secao);
            $stmtIng->execute();
            $ingredientes = $stmtIng->fetchAll(PDO::FETCH_COLUMN);

            // Passos da seção
            $sqlPassos = "SELECT descricao, ordem FROM passos_preparo WHERE id_secao = :id_secao ORDER BY ordem ASC";
            $stmtPassos = $this->conn->prepare($sqlPassos);
            $stmtPassos->bindParam(':id_secao', $id_secao);
            $stmtPassos->execute();
            $passos = $stmtPassos->fetchAll(PDO::FETCH_ASSOC);

            $receita["secoes"][] = [
                "titulo" => $secao["titulo"],
                "ingredientes" => $ingredientes,
                "passos" => $passos
            ];
        }

        return $receita;
    }
}
?>
