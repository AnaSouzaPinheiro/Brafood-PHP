<?php
require_once __DIR__ . '/../model/AddingReceitaModel.php';

class AddingReceitaCtl
{
    private $addRecipe;

    public function __construct($db)
    {
        $this->addRecipe = new AddingReceitaModel($db);
    }

    public function adicionarReceita($dados, $imagens)
    {
        if (empty($dados['titulo']) || empty($dados['categoria'])) {
            return ["sucesso" => false, "mensagem" => "Título e categoria são obrigatórios."];
        }

        $idReceita = $this->addRecipe->criarReceita($dados, $imagens);

        // Faz o vínculo com a subcategoria (receita_subcategoria)
        $nomeSubcategoria = $dados['subcategoria'] ?? '';
        if ($nomeSubcategoria) {
            $this->addRecipe->vincularSubcategoria($idReceita, $nomeSubcategoria);
        } else {
            error_log("Subcategoria não enviada para a receita ID $idReceita");
        }


        // Ingredientes
        foreach ($dados['ingredientes'] ?? [] as $grupo) {
            // Cria o item do grupo de ingredientes (itens_ingredientes) e pega o id_item
            $idItem = $this->addRecipe->criarItemIngrediente($idReceita, $grupo['titulo']);

            // Cria cada ingrediente apontando para o id_item correto
            foreach ($grupo['itens'] ?? [] as $ingrediente) {
                $this->addRecipe->criarIngrediente($ingrediente, $idItem);
            }
        }


        // Etapas
        foreach ($dados['etapas'] ?? [] as $etapa) {
            $idEtapa = $this->addRecipe->criarEtapa($idReceita, $etapa);
            foreach ($etapa['descricoes'] ?? [] as $desc) {
                $this->addRecipe->criarDescricaoEtapa($idEtapa, $desc);
            }
        }

        return ["sucesso" => true, "mensagem" => "Receita criada com sucesso!", "id" => $idReceita];
    }

}
