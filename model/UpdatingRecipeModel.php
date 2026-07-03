<?php

namespace App\Model;

use App\Config\Database;
use Exception;
use PDO;

class UpdatingRecipeModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function updateReceita($id, $idUser, $titulo, $resumo, $dificuldade, $tempo_hora, $tempo_min, $porcoes, $tipo_porcao, $imagem1, $ehPropria = true, $fonteAutor = null, $fonteUrl = null, $videoUrl = null)
    {
        if ($tempo_hora === null && $tempo_min === null) {
            throw new Exception("tempo_hora e tempo_min não podem ser null ao mesmo tempo.");
        }

        $ehPropria = $ehPropria ? 1 : 0;
        if (!$ehPropria && empty($fonteAutor)) {
            throw new Exception("Informe o autor original da receita.");
        }

        // Zera os campos de crédito quando a receita passa a ser marcada como própria
        $fonteAutor = $ehPropria ? null : trim((string) $fonteAutor);
        $fonteUrl   = $ehPropria ? null : $fonteUrl;
        $videoUrl   = $ehPropria ? null : $videoUrl;

        $sql = "UPDATE receitas 
                SET titulo = :titulo, resumo = :resumo, dificuldade = :dificuldade, 
                    tempo_hora = :tempo_hora, tempo_min = :tempo_min, 
                    porcoes = :porcoes, tipo_porcao = :tipo_porcao, 
                    imagem1 = :imagem1,
                    eh_propria = :eh_propria, fonte_autor = :fonte_autor,
                    fonte_url = :fonte_url, video_url = :video_url
                WHERE id_usuario = :idUser AND id = :id";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':resumo', $resumo);
        $stmt->bindParam(':dificuldade', $dificuldade, PDO::PARAM_INT);

        $stmt->bindValue(':tempo_hora', $tempo_hora, $tempo_hora === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':tempo_min', $tempo_min, $tempo_min === null ? PDO::PARAM_NULL : PDO::PARAM_INT);

        $stmt->bindParam(':porcoes', $porcoes, PDO::PARAM_INT);
        $stmt->bindParam(':tipo_porcao', $tipo_porcao);
        $stmt->bindParam(':imagem1', $imagem1);
        $stmt->bindParam(':idUser', $idUser, PDO::PARAM_INT);
        $stmt->bindParam(':eh_propria', $ehPropria, PDO::PARAM_INT);
        $stmt->bindValue(':fonte_autor', $fonteAutor, $fonteAutor === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':fonte_url', $fonteUrl, $fonteUrl === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':video_url', $videoUrl, $videoUrl === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

        $stmt->execute();
    }

    // -------------------------------------------------------------------------
    // Galeria de imagens
    // -------------------------------------------------------------------------

    public function removerImagemGaleria(int $idReceita, string $caminho): void
    {
        $sql = "DELETE FROM receita_imagens WHERE id_receita = :idReceita AND caminho = :caminho";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':idReceita', $idReceita, PDO::PARAM_INT);
        $stmt->bindParam(':caminho', $caminho);
        $stmt->execute();
    }

    /**
     * Reordena a galeria de acordo com a ordem final enviada pelo front
     * (útil quando o usuário arrasta/reordena as imagens existentes).
     *
     * @param string[] $caminhosNaOrdemFinal
     */
    public function reordenarGaleria(int $idReceita, array $caminhosNaOrdemFinal): void
    {
        $sql = "UPDATE receita_imagens SET ordem = :ordem WHERE id_receita = :idReceita AND caminho = :caminho";
        $stmt = $this->conn->prepare($sql);

        foreach (array_values($caminhosNaOrdemFinal) as $ordem => $caminho) {
            $stmt->execute([
                ':ordem'     => $ordem,
                ':idReceita' => $idReceita,
                ':caminho'   => $caminho,
            ]);
        }
    }


    public function vincularSubcategoria($id_receita, $novaSubcategoria)
    {
        $idSubcategoria = (int) $novaSubcategoria;

        if (!$idSubcategoria) {
            throw new Exception("ID de subcategoria inválido.");
        }

        $stmt = $this->conn->prepare("SELECT id 
                FROM subcategorias 
                WHERE id = ? 
                LIMIT 1");

        $stmt->execute([$idSubcategoria]);

        if (!$stmt->fetchColumn()) {
            throw new Exception(
                "Subcategoria não encontrada: id = " . $idSubcategoria
            );
        }

        $sql = "INSERT INTO receita_subcategoria 
                (id_receita, id_subcategoria)
                VALUES (:id_receita, :id_subcategoria)";

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

    public function removerIngrediente($descricao, $id)
    {
        try {
            $this->conn->beginTransaction();

            $sql = "DELETE FROM ingredientes WHERE id_item = :idItem AND descricao = :descricao";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':idItem', $id, PDO::PARAM_INT);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function removerEtapas($id)
    {
        try {
            $this->conn->beginTransaction();

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

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function removerSubcategorias($id)
    {
        try {
            $this->conn->beginTransaction();

            $sql = "DELETE FROM receita_subcategoria WHERE id_receita = :id";
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

    public function removerItemIngrediente($idItem)
    {
        try {
            $this->conn->beginTransaction();

            $sql = "DELETE FROM ingredientes WHERE id_item = :idItem";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':idItem', $idItem, PDO::PARAM_INT);
            $stmt->execute();

            $sql = "DELETE FROM itens_ingredientes WHERE id_item = :idItem";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':idItem', $idItem, PDO::PARAM_INT);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function removerDescricaoEtapa($idEtapa, $descricao)
    {
        $sql = "DELETE FROM descricoes_preparo WHERE id_etapa = :idEtapa AND descricao = :descricao";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':idEtapa', $idEtapa, PDO::PARAM_INT);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->execute();
    }

    public function removerEtapa($idEtapa)
    {
        try {
            $this->conn->beginTransaction();

            $sql = "DELETE FROM descricoes_preparo WHERE id_etapa = :idEtapa";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':idEtapa', $idEtapa, PDO::PARAM_INT);
            $stmt->execute();

            $sql = "DELETE FROM etapas_preparo WHERE id_secao_preparo = :idEtapa";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':idEtapa', $idEtapa, PDO::PARAM_INT);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}
