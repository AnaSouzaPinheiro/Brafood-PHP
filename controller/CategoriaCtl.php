<?php

namespace App\Controller;

use App\Model\CategoriaModel;

class CategoriaCtl
{
    private CategoriaModel $categoriaModel;

    public function __construct(?CategoriaModel $categoriaModel = null)
    {
        $this->categoriaModel = $categoriaModel ?? new CategoriaModel();
    }

    public function listarCategorias()
    {
        return $this->categoriaModel->listarCategorias();
    }

    public function buscarCategoriaPorId($id)
    {
        $categoria = $this->categoriaModel->buscarCategoriaPorId($id);

        if (!$categoria) {
            http_response_code(404);

            return [
                "mensagem" => "Categoria não encontrada."
            ];
        }

        return $categoria;
    }
}