<?php

require_once __DIR__ . '/../model/CategoriaModel.php';

class CategoriaCtl
{
    private CategoriaModel $categoriaModel;

    public function __construct($db)
    {
        $this->categoriaModel = new CategoriaModel($db);
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