<?php
class ReceitaModel
{
    private $conn;
    private $table = "receitas";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Retorna receitas para os cards
    public function listarTodas()
    {
        $query = "SELECT 
                    r.id,
                    r.titulo,
                    r.resumo,
                    r.dificuldade,
                    r.tempo_preparo,
                    r.tipo_tempo,
                    r.porcoes,
                    r.tipo_porcao,
                    r.qtd_fav,
                    r.data_criacao,
                    r.imagem1,
                    CONCAT(u.nome, ' ', u.sobrenome) AS autor,
                    c.nome AS categoria
                FROM receitas r
                JOIN usuarios u ON r.id_usuario = u.id
                LEFT JOIN receita_subcategoria rs ON rs.id_receita = r.id
                LEFT JOIN subcategorias s ON s.id = rs.id_subcategoria
                LEFT JOIN categorias c ON c.id = s.id_categoria
                ORDER BY r.titulo";
        /*ORDER BY r.data_criacao DESC";*/

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        // retorna um array associativo
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    // Retorna uma receita completa (com ingredientes e modo de preparo)
    public function buscarPorId($id)
    {
        // Dados básicos da receita
        $sql = "SELECT r.*, CONCAT(u.nome, ' ', u.sobrenome) AS autor, c.nome AS categoria
                FROM receitas r
                JOIN usuarios u ON r.id_usuario = u.id
                LEFT JOIN receita_subcategoria rs ON rs.id_receita = r.id
                LEFT JOIN subcategorias s ON s.id = rs.id_subcategoria
                LEFT JOIN categorias c ON c.id = s.id_categoria
                WHERE r.id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $receita = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$receita)
            return null;

        // Ingredientes
        $sqlIngr = "SELECT ii.titulo AS titulo_ingrediente, i.descricao
                    FROM itens_ingredientes ii
                    LEFT JOIN ingredientes i ON i.id_secao_ingrediente = ii.id_secao_ingrediente
                    WHERE ii.id_receita = :id
                    ORDER BY ii.id_secao_ingrediente, i.id_ingrediente";

        $stmtIngr = $this->conn->prepare($sqlIngr);
        $stmtIngr->bindParam(':id', $id);
        $stmtIngr->execute();

        $ingredientes = [];
        while ($row = $stmtIngr->fetch(PDO::FETCH_ASSOC)) {
            $titulo = $row["titulo_ingrediente"];
            if (!isset($ingredientes[$titulo])) {
                $ingredientes[$titulo] = [
                    "titulo" => $titulo,
                    "itens" => []
                ];
            }

            // Evita adicionar null (caso algum ingrediente não tenha descrição)
            if (!empty($row["descricao"])) {
                $ingredientes[$titulo]["itens"][] = $row["descricao"];
            }
        }

        // Etapas do modo de preparo
        $sqlEtapas = "SELECT e.id_secao_preparo, e.titulo, e.ordem
                  FROM etapas_preparo e
                  WHERE e.id_receita = :id
                  ORDER BY e.ordem ASC";
        $stmtEtapas = $this->conn->prepare($sqlEtapas);
        $stmtEtapas->bindParam(':id', $id);
        $stmtEtapas->execute();

        $etapas = [];
        while ($etapa = $stmtEtapas->fetch(PDO::FETCH_ASSOC)) {
            $idEtapa = $etapa["id_secao_preparo"];

            // Descrições de cada etapa
            $sqlDesc = "SELECT descricao, ordem
                    FROM descricoes_preparo
                    WHERE id_etapa = :idEtapa
                    ORDER BY ordem ASC";
            $stmtDesc = $this->conn->prepare($sqlDesc);
            $stmtDesc->bindParam(':idEtapa', $idEtapa);
            $stmtDesc->execute();
            $descricoes = $stmtDesc->fetchAll(PDO::FETCH_ASSOC);

            $etapas[] = [
                "titulo" => $etapa["titulo"],
                "ordem" => $etapa["ordem"],
                "descricoes" => $descricoes
            ];
        }

        $receita["ingredientes"] = array_values($ingredientes);
        $receita["etapas"] = $etapas;

        return $receita;
    }

    public function listarComCategoria($idCategoria)
    {
        $query = "SELECT 
                r.id,
                r.titulo,
                r.resumo,
                r.dificuldade,
                r.tempo_preparo,
                r.tipo_tempo,
                r.porcoes,
                r.tipo_porcao,
                r.qtd_fav,
                r.data_criacao,
                r.imagem1,
                CONCAT(u.nome, ' ', u.sobrenome) AS autor,
                c.nome AS categoria
            FROM receitas r
            JOIN usuarios u ON r.id_usuario = u.id
            LEFT JOIN receita_subcategoria rs ON rs.id_receita = r.id
            LEFT JOIN subcategorias s ON s.id = rs.id_subcategoria
            LEFT JOIN categorias c ON c.id = s.id_categoria
            WHERE c.id = :idCategoria
            ORDER BY r.titulo ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idCategoria', $idCategoria, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

}
