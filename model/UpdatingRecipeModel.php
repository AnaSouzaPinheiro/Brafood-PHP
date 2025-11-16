<?php

class UpdatingRecipeModel
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function updateReceita($id, $idUser, $titulo, $resumo, $dificuldade, $tempo_hora, $tempo_min, $porcoes, $tipo_porcao, $imagem1, $imagem2, $imagem3)
    {
        // Não deixa os dois null ao mesmo tempo
        if ($tempo_hora === null && $tempo_min === null) {
            throw new Exception("tempo_hora e tempo_min não podem ser null ao mesmo tempo.");
        }

        $sql = "UPDATE receitas 
            SET titulo = :titulo, resumo = :resumo, dificuldade = :dificuldade, 
                tempo_hora = :tempo_hora, tempo_min = :tempo_min, 
                porcoes = :porcoes, tipo_porcao = :tipo_porcao, 
                imagem1 = :imagem1, imagem2 = :imagem2, imagem3 = :imagem3
            WHERE id_usuario = :idUser AND id = :id";

        $stmt = $this->conn->prepare($sql);

        // binds normais
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':resumo', $resumo);
        $stmt->bindParam(':dificuldade', $dificuldade, PDO::PARAM_INT);

        // binds com null seguro
        $stmt->bindValue(':tempo_hora', $tempo_hora, $tempo_hora === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':tempo_min', $tempo_min, $tempo_min === null ? PDO::PARAM_NULL : PDO::PARAM_INT);

        $stmt->bindParam(':porcoes', $porcoes, PDO::PARAM_INT);
        $stmt->bindParam(':tipo_porcao', $tipo_porcao);
        $stmt->bindParam(':imagem1', $imagem1);
        $stmt->bindParam(':imagem2', $imagem2);
        $stmt->bindParam(':imagem3', $imagem3);
        $stmt->bindParam(':idUser', $idUser, PDO::PARAM_INT);

        $stmt->execute();
    }


    public function vincularSubcategoria($id_receita, $nomeSubcategoria)
    {
        // 1. Buscar o ID da subcategoria pelo nome
        $stmt = $this->conn->prepare("SELECT id FROM subcategorias WHERE nome = ? LIMIT 1");
        $stmt->execute([$nomeSubcategoria]);
        $idSubcategoria = $stmt->fetchColumn();

        if (!$idSubcategoria) {
            throw new Exception("Subcategoria não encontrada: " . $nomeSubcategoria);
        }

        // 2. Atualizar a subcategoria vinculada à receita
        $sql = "UPDATE receita_subcategoria 
        SET id_subcategoria = :id_subcategoria 
        WHERE id_receita = :id_receita
        ";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':id_receita', $id_receita, PDO::PARAM_INT);
        $stmt->bindParam(':id_subcategoria', $idSubcategoria, PDO::PARAM_INT);

        $stmt->execute();
    }

    public function editarItemIngrediente($idItem, $titulo)
    {
        $sql = "UPDATE itens_ingredientes SET titulo = :titulo WHERE id_item = :idItem";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':idItem', $idItem, PDO::PARAM_INT);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->execute();
    }

    public function editarIngrediente($descricao, $idItem)
    {
        $sql = "UPDATE ingredientes SET descricao = :descricao WHERE id_item = :idItem";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':idItem', $idItem, PDO::PARAM_INT);
        $stmt->execute();
    }


    public function editarEtapa($idItem, $etapa)
    {
        $sql = "UPDATE etapas_preparo SET titulo = :titulo, ordem = :ordem WHERE id_secao_preparo = :idItem";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':idItem', $idItem);
        $titulo = $etapa['titulo'];
        $ordem = $etapa['ordem'];
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':ordem', $ordem, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function editarDescricaoEtapa($etapaId, $descricao)
    {
        $sql = "UPDATE descricoes_preparo SET descricao = :descricao, ordem = :ordem WHERE id_etapa = :etapaId";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':etapaId', $etapaId);
        $descricao = $descricao['descricao'];
        $ordem = $descricao['ordem'];
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':ordem', $ordem, PDO::PARAM_INT);
        $stmt->execute();
    }
}
