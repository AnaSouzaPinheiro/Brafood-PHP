<?php

namespace App\Model;

use App\Config\Database;
use Exception;
use PDO;

class ReceitaModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

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
                r.media_avaliacao,
                r.qtd_avaliacoes,
                r.data_criacao,
                r.imagem1,
                u.nome AS autor,
                u.apelido AS autor_apelido,
                c.nome AS categoria
            FROM receitas r
            JOIN usuarios u ON r.id_usuario = u.id
            LEFT JOIN receita_subcategoria rs ON rs.id_receita = r.id
            LEFT JOIN subcategorias s ON s.id = rs.id_subcategoria
            LEFT JOIN categorias c ON c.id = s.id_categoria
            WHERE r.titulo LIKE :pesquisaTitulo
                OR c.nome LIKE :pesquisaCategoria
            GROUP BY r.id
            ORDER BY r.titulo ASC";

            $stmt = $this->conn->prepare($query);

            $param = "%{$pesquisa}%";

            $stmt->bindParam(':pesquisaTitulo', $param, PDO::PARAM_STR);
            $stmt->bindParam(':pesquisaCategoria', $param, PDO::PARAM_STR);
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
                r.media_avaliacao,
                r.qtd_avaliacoes,
                r.data_criacao,
                r.imagem1,
                u.nome AS autor,
                u.apelido AS autor_apelido,
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
            u.nome AS autor,
                u.apelido AS autor_apelido,
            GROUP_CONCAT(DISTINCT c.nome SEPARATOR ', ') AS categoria
        FROM receitas r
        JOIN usuarios u ON r.id_usuario = u.id
        LEFT JOIN receita_subcategoria rs ON rs.id_receita = r.id
        LEFT JOIN subcategorias s ON s.id = rs.id_subcategoria
        LEFT JOIN categorias c ON c.id = s.id_categoria
        WHERE u.id = :idUser
        GROUP BY r.id
        ORDER BY r.titulo DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idUser', $idUser, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId($id)
    {
        // Retorna IDs de categoria e subcategoria para que o Angular
        // possa pre-selecionar os dropdowns por valor, nao por nome.
        $sql = "SELECT r.*,
                       u.nome AS autor,
                u.apelido AS autor_apelido,
                       u.foto_perfil,
                       u.bio,
                       GROUP_CONCAT(DISTINCT c.nome SEPARATOR ', ') AS categoria,
                       GROUP_CONCAT(DISTINCT c.id SEPARATOR ', ') AS idCategoria,
                       GROUP_CONCAT(DISTINCT s.nome SEPARATOR ', ') AS subcategoria,
                       GROUP_CONCAT(DISTINCT s.id SEPARATOR ', ') AS idSubcategoria
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

            if (!empty($row["descricao"])) {
                $ingredientes[$titulo]["itens"][] = $row["descricao"];
            }
        }

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
                "titulo" => $etapa["titulo"],
                "ordem" => $etapa["ordem"],
                "descricoes" => $descricoes
            ];
        }

        $receita["ingredientes"] = array_values($ingredientes);
        $receita["etapas"] = $etapas;
        $receita["imagens"] = $this->getImagensGaleria($id);

        return $receita;
    }

    /**
     * Retorna os caminhos relativos da galeria de imagens de uma receita
     * (não inclui imagem1, que é a capa e continua em `receitas`).
     *
     * @return string[]
     */
    public function getImagensGaleria($idReceita): array
    {
        $sql = "SELECT caminho
                FROM receita_imagens
                WHERE id_receita = :id
                ORDER BY ordem ASC, id ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $idReceita, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function listarComCategoria($idCategoria, $ordenar = 'nome', $subcategorias = [], $dificuldades = [], $tiposPorcao = [], $tempoMax = null)
    {
        $ordenacoes = [
            'mais_novos'      => 'r.data_criacao DESC',
            'mais_antigos'   => 'r.data_criacao ASC',
            'mais_favoritos' => 'r.qtd_fav DESC',
            'nome'           => 'r.titulo ASC',
            'aleatorio'      => 'RAND()',
        ];

        $orderBy = $ordenacoes[$ordenar] ?? $ordenacoes['nome'];

        $sql = "SELECT 
                    r.id,
                    r.titulo,
                    r.resumo,
                    r.dificuldade,
                    r.tempo_hora,
                    r.tempo_min,
                    r.porcoes,
                    r.tipo_porcao,
                    r.qtd_fav,
                    r.media_avaliacao,
                    r.qtd_avaliacoes,
                    r.data_criacao,
                    r.imagem1,
                    u.nome AS autor,
                u.apelido AS autor_apelido,
                    c.nome AS categoria,
                    s.id   AS sub_id,
                    s.nome AS sub_nome
                FROM receitas r
                JOIN usuarios u ON u.id = r.id_usuario
                LEFT JOIN receita_subcategoria rs ON rs.id_receita = r.id
                LEFT JOIN subcategorias s ON s.id = rs.id_subcategoria
                LEFT JOIN categorias c ON c.id = s.id_categoria
                WHERE c.id = :categoria";

        $params = [
            ':categoria' => $idCategoria
        ];

        if (!empty($subcategorias)) {
            $placeholders = [];
            foreach ($subcategorias as $i => $sub) {
                $key = ":sub{$i}";
                $placeholders[] = $key;
                $params[$key] = (int)$sub;
            }
            $sql .= " AND EXISTS (
                    SELECT 1
                    FROM receita_subcategoria rs2
                    WHERE rs2.id_receita = r.id
                    AND rs2.id_subcategoria IN (" . implode(',', $placeholders) . ")
                )";
        }

        if (!empty($dificuldades)) {
            $placeholders = [];
            foreach ($dificuldades as $i => $dif) {
                $key = ":dif{$i}";
                $placeholders[] = $key;
                $params[$key] = (int)$dif;
            }
            $sql .= " AND r.dificuldade IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($tiposPorcao)) {
            $placeholders = [];
            foreach ($tiposPorcao as $i => $tipo) {
                $key = ":tipo{$i}";
                $placeholders[] = $key;
                $params[$key] = $tipo;
            }
            $sql .= " AND r.tipo_porcao IN (" . implode(',', $placeholders) . ")";
        }

        if ($tempoMax !== null) {
            $sql .= " AND (r.tempo_hora * 60 + r.tempo_min) <= :tempo_max";
            $params[':tempo_max'] = $tempoMax;
        }

        $sql .= " ORDER BY $orderBy";

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            if (in_array($key, [':categoria', ':tempo_max']) || str_starts_with($key, ':dif') || str_starts_with($key, ':sub')) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->organizarSubcategorias($rows);
    }

    private function organizarSubcategorias($rows)
    {
        $receitas = [];

        foreach ($rows as $row) {
            $id = $row['id'];

            if (!isset($receitas[$id])) {
                $receitas[$id] = $row;
                $receitas[$id]['subcategorias'] = [];
            }

            if ($row['sub_id']) {
                $receitas[$id]['subcategorias'][] = [
                    'id'   => $row['sub_id'],
                    'nome' => $row['sub_nome']
                ];
            }

            unset($receitas[$id]['sub_id'], $receitas[$id]['sub_nome']);
        }

        return array_values($receitas);
    }

    /**
     * Retorna só o id_usuario dono da receita (ou null se não existir).
     * Usado pelas rotas de excluir/editar pra checar propriedade antes
     * de mexer no banco, sem precisar do join pesado do buscarPorId.
     */
    public function donoDaReceita($id): ?int
    {
        $stmt = $this->conn->prepare("SELECT id_usuario FROM receitas WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (int) $row['id_usuario'] : null;
    }

    public function excluirReceita($id)
    {
        try {
            $this->conn->beginTransaction();

            // Busca imagens antes de excluir para poder apagá-las do disco depois
            $stmtImg = $this->conn->prepare("SELECT imagem1 FROM receitas WHERE id = :id");
            $stmtImg->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtImg->execute();
            $imagensReceita = $stmtImg->fetch(PDO::FETCH_ASSOC);

            // Galeria (linhas em receita_imagens são apagadas em cascata pela FK,
            // mas os arquivos no disco não — por isso buscamos os caminhos aqui).
            $imagensGaleria = $this->getImagensGaleria($id);

            $sql = "DELETE dp
                FROM descricoes_preparo dp
                JOIN etapas_preparo ep ON ep.id_secao_preparo = dp.id_etapa
                WHERE ep.id_receita = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $sql = "DELETE FROM etapas_preparo WHERE id_receita = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $sql = "DELETE i FROM ingredientes i
                JOIN itens_ingredientes ii ON ii.id_item = i.id_item
                WHERE ii.id_receita = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $sql = "DELETE FROM itens_ingredientes WHERE id_receita = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $sql = "DELETE FROM receita_subcategoria WHERE id_receita = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $sql = "DELETE FROM receitas WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $this->conn->commit();

            // Retorna os caminhos das imagens para o controller apagar do disco
            return [
                'deleted'  => true,
                'imagens'  => array_merge(
                    [$imagensReceita['imagem1'] ?? null],
                    $imagensGaleria
                ),
            ];
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

        return array_values($ingredientes);
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
        // Retorna o ID para consistencia com o fluxo de add/update (que agora trabalha com IDs)
        $sql = "SELECT s.id
                FROM receita_subcategoria rs
                JOIN subcategorias s ON s.id = rs.id_subcategoria
                WHERE rs.id_receita = :idReceita";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':idReceita', $idReceita, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
