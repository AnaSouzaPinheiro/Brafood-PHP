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
    public function listarTodas($pesquisa = '')
    {
        if (!empty($pesquisa)) {

            $query = "SELECT 
                r.id,
                r.titulo,
                r.resumo,
                r.dificuldade,
                r.tempo_hora,
                r.tempo_min,
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
            WHERE r.titulo LIKE :pesquisa
               OR c.nome LIKE :pesquisa
            ORDER BY r.titulo ASC";

            $stmt = $this->conn->prepare($query);
            $param = "%{$pesquisa}%";
            $stmt->bindParam(':pesquisa', $param, PDO::PARAM_STR);

        } else {

            $query = "SELECT 
                r.id,
                r.titulo,
                r.resumo,
                r.dificuldade,
                r.tempo_hora,
                r.tempo_min,
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
            ORDER BY r.titulo ASC";

            $stmt = $this->conn->prepare($query);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    LEFT JOIN ingredientes i ON i.id_item = ii.id_item
                    WHERE ii.id_receita = :id
                    ORDER BY ii.id_item, i.id_ingrediente";

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
                r.tempo_hora,
                r.tempo_min,
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

    public function excluirReceita($id)
    {
        try {
            $this->conn->beginTransaction();

            // 1. Deletar descrições de preparo
            $sql = "DELETE dp
                FROM descricoes_preparo dp
                JOIN etapas_preparo ep ON ep.id_secao_preparo = dp.id_etapa
                WHERE ep.id_receita = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // 2. Deletar etapas de preparo
            $sql = "DELETE FROM etapas_preparo WHERE id_receita = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // 3. Deletar ingredientes
            $sql = "DELETE i FROM ingredientes i
                JOIN itens_ingredientes ii ON ii.id_item = i.id_item
                WHERE ii.id_receita = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // 4. Deletar itens_ingredientes
            $sql = "DELETE FROM itens_ingredientes WHERE id_receita = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // 5. Deletar relação com subcategorias
            $sql = "DELETE FROM receita_subcategoria WHERE id_receita = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // 6. Deletar a receita
            $sql = "DELETE FROM receitas WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

}
