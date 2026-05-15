<?php

namespace App\Model;

use App\Config\Database;
use Exception;
use PDO;

class AddingRecipeModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function criarReceita($dados, $imagens)
    {
        // validações básicas
        if (
            empty($imagens['imagem1']) &&
            empty($imagens['imagem2']) &&
            empty($imagens['imagem3'])
        ) {
            throw new Exception("É necessário anexar ao menos uma imagem.");
        }

        if (empty($dados['autor'])) {
            throw new Exception("ID do usuário não informado.");
        }

        // campos obrigatórios
        $camposObrigatorios = ['titulo', 'resumo', 'dificuldade', 'porcoes', 'tipo_porcao'];

        foreach ($camposObrigatorios as $campo) {
            if (!isset($dados[$campo]) || trim($dados[$campo]) === '') {
                throw new Exception("O campo {$campo} é obrigatório.");
            }
        }

        $sql = "INSERT INTO receitas 
            (id_usuario, titulo, resumo, dificuldade, tempo_hora, tempo_min, porcoes, tipo_porcao, imagem1, imagem2, imagem3, qtd_fav, data_criacao)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);

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

        // retorna o id da nova receita
        return $this->conn->lastInsertId();
    }

    public function vincularSubcategoria($idReceita, $idSubcategoria)
    {
        // Recebe o ID diretamente — sem lookup por nome
        $idSubcategoria = (int) $idSubcategoria;

        if (!$idSubcategoria) {
            throw new Exception("ID de subcategoria inválido.");
        }

        // Confirma que o ID existe no banco antes de vincular
        $stmt = $this->conn->prepare("SELECT id FROM subcategorias WHERE id = ? LIMIT 1");
        $stmt->execute([$idSubcategoria]);
        if (!$stmt->fetchColumn()) {
            throw new Exception("Subcategoria não encontrada: id = " . $idSubcategoria);
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
