<?php

namespace App\Controller;

use App\Model\CategoriaModel;
use App\Model\ReceitaModel;
use App\Service\ImageCleanupService;

class ReceitaCtl
{
    private ReceitaModel $receitaModel;
    private CategoriaModel $categoriaModel;

    public function __construct()
    {
        $this->receitaModel = new ReceitaModel();
        $this->categoriaModel = new CategoriaModel();
    }

    public function listarPorUsuario($idUser)
    {
        if ($idUser <= 0) {
            http_response_code(400);

            return [
                "mensagem" => "ID inválido."
            ];
        }

        return $this->receitaModel->listByUser($idUser);
    }

    public function listarPorCategoria($id, $ordenar = 'nome', $subcategorias = [], $dificuldades = [], $tiposPorcao = [], $tempoMax = null)
    {
        if (!$id) {
            http_response_code(400);

            return [
                "mensagem" => "ID da categoria não informado."
            ];
        }

        $categoria = $this->categoriaModel->buscarCategoriaPorId($id);

        if (!$categoria) {
            http_response_code(404);

            return [
                "mensagem" => "Categoria não encontrada."
            ];
        }

        return $this->receitaModel->listarComCategoria(
            $id,
            $ordenar,
            $subcategorias,
            $dificuldades,
            $tiposPorcao,
            $tempoMax
        );
    }

    public function listarTodas($pesquisa = '')
    {
        return $this->receitaModel->listarTodas($pesquisa);
    }

    public function buscarPorId($id)
    {
        if ($id <= 0) {
            http_response_code(400);

            return [
                "mensagem" => "ID inválido."
            ];
        } else if (!$id) {
            http_response_code(400);

            return [
                "mensagem" => "ID da receita não informado."
            ];
        }

        $receita = $this->receitaModel->buscarPorId($id);

        if (!$receita) {
            http_response_code(404);

            return [
                "mensagem" => "Receita não encontrada."
            ];
        }

        return $receita;
    }

    public function excluirReceita($id)
    {
        if ($id <= 0) {
            http_response_code(400);

            return [
                "mensagem" => "ID inválido."
            ];
        } else if (!$id) {
            http_response_code(400);

            return [
                "mensagem" => "ID da receita não informado."
            ];
        }

        $resultado = $this->receitaModel->excluirReceita($id);

        if (!$resultado) {
            http_response_code(404);

            return [
                "mensagem" => "Receita não encontrada."
            ];
        }

        // Apaga as imagens do disco de forma silenciosa — falhas aqui não
        // devem impedir o retorno de sucesso ao cliente, pois o registro
        // já foi removido do banco.
        try {
            if (!empty($resultado['imagens'])) {
                ImageCleanupService::apagarArquivos(
                    $resultado['imagens'],
                    dirname(__DIR__, 2) . '/'
                );
            }
        } catch (\Throwable $e) {
            // Loga mas não propaga — a exclusão no banco foi bem-sucedida
            \App\Config\Logger::error('ReceitaCtl', 'Erro ao apagar imagens após exclusão', [
                'receita_id' => $id,
                'message'    => $e->getMessage(),
            ]);
        }

        http_response_code(200);

        echo json_encode([
            'success' => true,
            'mensagem' => 'Receita excluída com sucesso.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}
