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

    public function listByUser($idUser)
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
            WHERE u.id = :idUser
            ORDER BY r.titulo ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idUser', $idUser, PDO::PARAM_STR);
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

    public function listarComCategoria($idCategoria, $ordenar = 'nome', $subcategorias = [])
    {
        $query = "
            SELECT 
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
                c.nome AS categoria,

                -- 🔄 Compatível com MySQL 5.7/MariaDB
                IFNULL(
                    CONCAT(
                        '[',
                        GROUP_CONCAT(
                            CONCAT(
                                '{',
                                '\"id\":', s.id, ',',
                                '\"nome\":\"', REPLACE(s.nome, '\"', '\\\"'), '\"',
                                '}'
                            )
                            SEPARATOR ','
                        ),
                        ']'
                    ),
                    '[]'
                ) AS subcategorias

            FROM receitas r
            JOIN usuarios u ON r.id_usuario = u.id
            LEFT JOIN receita_subcategoria rs ON rs.id_receita = r.id
            LEFT JOIN subcategorias s ON s.id = rs.id_subcategoria
            LEFT JOIN categorias c ON c.id = s.id_categoria
            WHERE c.id = :idCategoria
        ";

        // 🔍 Filtro de subcategorias
        if (!empty($subcategorias)) {
            $placeholders = implode(',', array_fill(0, count($subcategorias), '?'));
            $query .= " AND s.id IN ($placeholders)";
        }

        $query .= " GROUP BY r.id";

        // 🔽 Ordenação
        switch ($ordenar) {
            case "mais_novos":
                $query .= " ORDER BY r.data_criacao DESC";
                break;
            case "mais_antigos":
                $query .= " ORDER BY r.data_criacao ASC";
                break;
            case "mais_favoritos":
                $query .= " ORDER BY r.qtd_fav DESC";
                break;
            case "nome":
            default:
                $query .= " ORDER BY r.titulo ASC";
                break;
        }

        $stmt = $this->conn->prepare($query);

        // Bind fixo
        $stmt->bindValue(':idCategoria', $idCategoria, PDO::PARAM_INT);

        // Bind dinâmico das subcategorias
        $i = 1;
        foreach ($subcategorias as $sub) {
            $stmt->bindValue($i, $sub, PDO::PARAM_INT);
            $i++;
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    public function getIngredientes($id)
    {
        $sqlIngr = "SELECT ii.id_item, ii.titulo AS titulo_ingrediente, i.descricao
                    FROM itens_ingredientes ii
                    LEFT JOIN ingredientes i ON i.id_item = ii.id_item
                    WHERE ii.id_receita = :id
                    ORDER BY ii.id_item, i.id_ingrediente";

        $stmtIngr = $this->conn->prepare($sqlIngr);
        $stmtIngr->bindParam(':id', $id);
        $stmtIngr->execute();

        $ingredientes = [];
        while ($row = $stmtIngr->fetch(PDO::FETCH_ASSOC)) {
            $titulo = $row["titulo_ingrediente"] ?: 'Sem grupo';
            if (!isset($ingredientes[$titulo])) {
                $ingredientes[$titulo] = [
                    "id_item" => $row["id_item"],
                    "titulo" => $titulo,
                    "itens" => []
                ];
            }

            if (!empty($row["descricao"])) {
                $ingredientes[$titulo]["itens"][] = $row["descricao"];
            }
        }

        return array_values($ingredientes); // agora cada grupo tem 'id_item', 'titulo' e 'itens'
    }

    public function getEtapas($id)
    {
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

            $sqlDesc = "SELECT descricao, ordem
                    FROM descricoes_preparo
                    WHERE id_etapa = :idEtapa
                    ORDER BY ordem ASC";
            $stmtDesc = $this->conn->prepare($sqlDesc);
            $stmtDesc->bindParam(':idEtapa', $idEtapa);
            $stmtDesc->execute();
            $descricoes = $stmtDesc->fetchAll(PDO::FETCH_ASSOC);

            $etapas[] = [
                "id_secao_preparo" => $idEtapa,
                "titulo" => $etapa["titulo"],
                "ordem" => $etapa["ordem"],
                "descricoes" => $descricoes
            ];
        }

        return $etapas;
    }

    public function getSubcategoria($idReceita)
    {
        $sql = "SELECT s.nome
                FROM receita_subcategoria rs
                JOIN subcategorias s ON s.id = rs.id_subcategoria
                WHERE rs.id_receita = :idReceita
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':idReceita', $idReceita, PDO::PARAM_INT);
        $stmt->execute();

        $subcategoria = $stmt->fetchColumn();

        return $subcategoria ?: null;
    }
}
