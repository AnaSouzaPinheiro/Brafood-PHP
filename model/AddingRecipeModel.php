<?php

class AddingRecipeModel
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function criarReceita($dados, $imagens)
    {
        $sql = "INSERT INTO receitas 
            (id_usuario, titulo, resumo, dificuldade, tempo_hora, tempo_min, porcoes, tipo_porcao, imagem1, imagem2, imagem3, qtd_fav, data_criacao)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);

        if (!empty($dados['autor'])) {
            $stmt->execute([
                $dados['autor'],
                $dados['titulo'],
                $dados['resumo'] ?? '',
                $dados['dificuldade'] ?? 1,
                $dados['tempo_hora'] ?? null,
                $dados['tempo_min'] ?? '',
                $dados['porcoes'] ?? 1,
                $dados['tipo_porcao'] ?? '',
                $imagens['imagem1'] ?? null,
                $imagens['imagem2'] ?? null,
                $imagens['imagem3'] ?? null,
                $dados['qtd_fav'] ?? 0,
                $dados['data_criacao'] ?? date('Y-m-d H:i:s')
            ]);
        } else {
            die("Sen ID do usuário.");
        }

        // retorna o id da nova receita
        return $this->conn->lastInsertId();
    }

    public function vincularSubcategoria($idReceita, $nomeSubcategoria)
    {
        $stmt = $this->conn->prepare("SELECT id FROM subcategorias WHERE nome = ? LIMIT 1");
        $stmt->execute([$nomeSubcategoria]);
        $idSubcategoria = $stmt->fetchColumn();

        if (!$idSubcategoria) {
            throw new Exception("Subcategoria não encontrada: " . $nomeSubcategoria);
        }

        $stmt = $this->conn->prepare("INSERT INTO receita_subcategoria (id_receita, id_subcategoria) VALUES (?, ?)");
        $stmt->execute([$idReceita, $idSubcategoria]);
    }



    public function criarItemIngrediente($receitaId, $titulo)
    {
        $sql = "INSERT INTO itens_ingredientes (id_receita, titulo) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$receitaId, $titulo]);
        return $this->conn->lastInsertId(); // retorna id_item
    }

    public function criarIngrediente($descricao, $idItem)
    {
        /*$sql = "INSERT INTO ingredientes (descricao, id_item) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$descricao, $idItem]);
        return $this->conn->lastInsertId();*/

        // Verifica se já existe
        $sql = "SELECT id_ingrediente FROM ingredientes WHERE id_item = ? AND descricao = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idItem, $descricao]);
        $existe = $stmt->fetchColumn();

        if ($existe) {
            return $existe; // já existe, retorna o id
        }

        // Cria novo
        $sql = "INSERT INTO ingredientes (id_item, descricao) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idItem, $descricao]);
        return $this->conn->lastInsertId();
    }


    public function criarEtapa($receitaId, $etapa)
    {
        $sql = "INSERT INTO etapas_preparo (id_receita, titulo, ordem) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $receitaId,
            $etapa['titulo'] ?? '',
            $etapa['ordem'] ?? 0
        ]);
        return $this->conn->lastInsertId();
    }

    public function criarDescricaoEtapa($etapaId, $descricao)
    {
        $sql = "INSERT INTO descricoes_preparo (id_etapa, descricao, ordem) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $etapaId,
            $descricao['descricao'] ?? '',
            $descricao['ordem'] ?? 0
        ]);
    }
}
