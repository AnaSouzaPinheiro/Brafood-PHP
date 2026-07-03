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

    /**
     * @param array       $dados
     * @param string|null $imagem1  Caminho relativo da capa
     * @param string[]    $galeria  Caminhos relativos das imagens adicionais (opcional)
     */
    public function criarReceita($dados, $imagem1, array $galeria = [])
    {
        // validações básicas — precisa de ao menos uma imagem, seja capa ou galeria
        if (empty($imagem1) && empty($galeria)) {
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

        // Se a receita não é própria, exige ao menos o crédito do autor original
        $ehPropria = !isset($dados['eh_propria']) || $dados['eh_propria'] ? 1 : 0;
        if (!$ehPropria && empty($dados['fonte_autor'])) {
            throw new Exception("Informe o autor original da receita.");
        }

        $sql = "INSERT INTO receitas 
            (id_usuario, titulo, resumo, dificuldade, tempo_hora, tempo_min, porcoes, tipo_porcao, imagem1, qtd_fav, data_criacao, eh_propria, fonte_autor, fonte_url, video_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
            $imagem1,
            $dados['qtd_fav'] ?? 0,
            $dados['data_criacao'] ?? date('Y-m-d H:i:s'),
            $ehPropria,
            $ehPropria ? null : trim($dados['fonte_autor']),
            $ehPropria ? null : ($dados['fonte_url'] ?? null),
            $ehPropria ? null : ($dados['video_url'] ?? null),
        ]);

        $idReceita = (int) $this->conn->lastInsertId();

        if (!empty($galeria)) {
            $this->adicionarImagensGaleria($idReceita, $galeria);
        }

        // retorna o id da nova receita
        return $idReceita;
    }

    /**
     * Insere N imagens da galeria de uma vez, respeitando a ordem enviada.
     *
     * @param int      $idReceita
     * @param string[] $caminhos  Caminhos relativos, na ordem desejada
     */
    public function adicionarImagensGaleria(int $idReceita, array $caminhos): void
    {
        foreach (array_values($caminhos) as $ordem => $caminho) {
            $this->adicionarImagemGaleria($idReceita, $caminho, $ordem);
        }
    }

    public function adicionarImagemGaleria(int $idReceita, string $caminho, int $ordem = 0): void
    {
        $sql = "INSERT INTO receita_imagens (id_receita, caminho, ordem) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idReceita, $caminho, $ordem]);
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
