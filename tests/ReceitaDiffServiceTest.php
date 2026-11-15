<?php

use PHPUnit\Framework\TestCase;
use App\Model\AddingRecipeModel;
use App\Service\ReceitaDiffService;
use App\Model\UpdatingRecipeModel;

require_once __DIR__ . '/../config/Logger.php';
require_once __DIR__ . '/../model/UpdatingRecipeModel.php';
require_once __DIR__ . '/../model/AddingRecipeModel.php';
require_once __DIR__ . '/../service/ReceitaDiffService.php';

class ReceitaDiffServiceTest extends TestCase
{
    private function makeService(
        UpdatingRecipeModel $updating,
        AddingRecipeModel   $adding
    ): ReceitaDiffService {
        return new ReceitaDiffService($updating, $adding);
    }

    // -------------------------------------------------------------------------
    // diffSubcategoria
    // -------------------------------------------------------------------------

    public function testDiffSubcategoriaRemoveQuandoNovaEhVazia(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $updating->expects($this->once())->method('removerSubcategorias')->with(1);
        $updating->expects($this->never())->method('vincularSubcategoria');

        $this->makeService($updating, $this->createMock(AddingRecipeModel::class))
             ->diffSubcategoria(1, [3], []);
    }

    public function testDiffSubcategoriaAdicionaQuandoNaoHavia(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        // Ao mudar, o service limpa os vínculos e recria só os novos
        $updating->expects($this->once())->method('removerSubcategorias')->with(1);
        $updating->expects($this->once())->method('vincularSubcategoria')->with(1, 5);

        $this->makeService($updating, $this->createMock(AddingRecipeModel::class))
             ->diffSubcategoria(1, [], [5]);
    }

    public function testDiffSubcategoriaTrocaQuandoMudou(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $updating->expects($this->once())->method('removerSubcategorias')->with(1);
        $updating->expects($this->once())->method('vincularSubcategoria')->with(1, 7);

        $this->makeService($updating, $this->createMock(AddingRecipeModel::class))
             ->diffSubcategoria(1, [3], [7]);
    }

    public function testDiffSubcategoriaVinculaTodasAsNovas(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $updating->expects($this->once())->method('removerSubcategorias')->with(1);
        $updating->expects($this->exactly(2))->method('vincularSubcategoria');

        $this->makeService($updating, $this->createMock(AddingRecipeModel::class))
             ->diffSubcategoria(1, [3], [3, 8]);
    }

    public function testDiffSubcategoriaNaoFazNadaQuandoIgualEmOutraOrdem(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $updating->expects($this->never())->method('removerSubcategorias');
        $updating->expects($this->never())->method('vincularSubcategoria');

        $this->makeService($updating, $this->createMock(AddingRecipeModel::class))
             ->diffSubcategoria(1, [3, 8], [8, 3]);
    }

    public function testDiffSubcategoriaNaoFazNadaQuandoAmbasVazias(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $updating->expects($this->never())->method('removerSubcategorias');
        $updating->expects($this->never())->method('vincularSubcategoria');

        $this->makeService($updating, $this->createMock(AddingRecipeModel::class))
             ->diffSubcategoria(1, [], []);
    }

    // -------------------------------------------------------------------------
    // diffIngredientes
    // -------------------------------------------------------------------------

    public function testDiffIngredientesCriaGrupoNovo(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $adding   = $this->createMock(AddingRecipeModel::class);
        $adding->expects($this->once())->method('criarItemIngrediente')->willReturn(10);
        $adding->expects($this->once())->method('criarIngrediente')->with('500g farinha', 10);

        $this->makeService($updating, $adding)
             ->diffIngredientes(1, [], [
                 ['titulo' => 'Massa', 'itens' => ['500g farinha']],
             ]);
    }

    public function testDiffIngredientesAdicionaItemEmGrupoExistente(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $adding   = $this->createMock(AddingRecipeModel::class);
        $adding->expects($this->never())->method('criarItemIngrediente');
        $adding->expects($this->once())->method('criarIngrediente')->with('2 ovos', 5);

        $this->makeService($updating, $adding)
             ->diffIngredientes(1,
                 [['titulo' => 'Massa', 'id_item' => 5, 'itens' => ['500g farinha']]],
                 [['titulo' => 'Massa', 'itens' => ['500g farinha', '2 ovos']]]
             );
    }

    public function testDiffIngredientesRemoveItemAusente(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $updating->expects($this->once())->method('removerIngrediente')->with('500g farinha', 5);

        $this->makeService($updating, $this->createMock(AddingRecipeModel::class))
             ->diffIngredientes(1,
                 [['titulo' => 'Massa', 'id_item' => 5, 'itens' => ['500g farinha']]],
                 [['titulo' => 'Massa', 'itens' => []]]
             );
    }

    public function testDiffIngredientesRemoveGrupoInteiro(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $updating->expects($this->once())->method('removerIngrediente');
        $updating->expects($this->once())->method('removerItemIngrediente')->with(5);

        $this->makeService($updating, $this->createMock(AddingRecipeModel::class))
             ->diffIngredientes(1,
                 [['titulo' => 'Massa', 'id_item' => 5, 'itens' => ['500g farinha']]],
                 []  // Grupo foi removido completamente
             );
    }

    public function testDiffIngredientesNaoFazNadaQuandoIgual(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $adding   = $this->createMock(AddingRecipeModel::class);
        $adding->expects($this->never())->method('criarIngrediente');
        $updating->expects($this->never())->method('removerIngrediente');

        $this->makeService($updating, $adding)
             ->diffIngredientes(1,
                 [['titulo' => 'Massa', 'id_item' => 5, 'itens' => ['500g farinha']]],
                 [['titulo' => 'Massa', 'itens' => ['500g farinha']]]
             );
    }

    public function testDiffIngredientesIgnoraDuplicatasNoPayload(): void
    {
        $adding = $this->createMock(AddingRecipeModel::class);
        $adding->expects($this->once())->method('criarItemIngrediente')->willReturn(1);
        $adding->expects($this->once())->method('criarIngrediente');  // Só uma vez, não duas

        $this->makeService($this->createMock(UpdatingRecipeModel::class), $adding)
             ->diffIngredientes(1, [],
                 [['titulo' => 'Massa', 'itens' => ['500g farinha', '500g farinha']]]
             );
    }

    // -------------------------------------------------------------------------
    // diffEtapas
    // -------------------------------------------------------------------------

    public function testDiffEtapasCriaEtapaNova(): void
    {
        $adding = $this->createMock(AddingRecipeModel::class);
        $adding->expects($this->once())->method('criarEtapa')->willReturn(20);
        $adding->expects($this->once())->method('criarDescricaoEtapa');

        $ids = $this->makeService($this->createMock(UpdatingRecipeModel::class), $adding)
                    ->diffEtapas(1, [],
                        [['titulo' => 'Mistura', 'ordem' => 1, 'descricoes' => [['descricao' => 'Misture tudo']]]]
                    );

        $this->assertContains(20, $ids);
    }

    public function testDiffEtapasAdicionaDescricaoEmEtapaExistente(): void
    {
        $adding = $this->createMock(AddingRecipeModel::class);
        $adding->expects($this->once())->method('criarDescricaoEtapa');

        $etapasAtuais = [[
            'titulo'           => 'Mistura',
            'ordem'            => 1,
            'id_secao_preparo' => 10,
            'descricoes'       => [['descricao' => 'Misture tudo']],
        ]];

        $this->makeService($this->createMock(UpdatingRecipeModel::class), $adding)
             ->diffEtapas(1, $etapasAtuais, [[
                 'titulo'     => 'Mistura',
                 'ordem'      => 1,
                 'descricoes' => [
                     ['descricao' => 'Misture tudo'],
                     ['descricao' => 'Reserve'],
                 ],
             ]]);
    }

    public function testDiffEtapasRemoveDescricaoAusente(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $updating->expects($this->once())->method('removerDescricaoEtapa')->with(10, 'Misture tudo');

        $etapasAtuais = [[
            'titulo'           => 'Mistura',
            'ordem'            => 1,
            'id_secao_preparo' => 10,
            'descricoes'       => [['descricao' => 'Misture tudo']],
        ]];

        $this->makeService($updating, $this->createMock(AddingRecipeModel::class))
             ->diffEtapas(1, $etapasAtuais, [[
                 'titulo'     => 'Mistura',
                 'ordem'      => 1,
                 'descricoes' => [],
             ]]);
    }

    public function testDiffEtapasRemoveEtapaAusente(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $updating->expects($this->once())->method('removerEtapa')->with(10);

        $etapasAtuais = [[
            'titulo'           => 'Mistura',
            'ordem'            => 1,
            'id_secao_preparo' => 10,
            'descricoes'       => [],
        ]];

        $this->makeService($updating, $this->createMock(AddingRecipeModel::class))
             ->diffEtapas(1, $etapasAtuais, []);  // Etapa foi removida
    }

    public function testDiffEtapasRetornaIdsMantidasOuCriadas(): void
    {
        $adding = $this->createMock(AddingRecipeModel::class);
        $adding->method('criarEtapa')->willReturn(99);

        $ids = $this->makeService($this->createMock(UpdatingRecipeModel::class), $adding)
                    ->diffEtapas(1, [],
                        [['titulo' => 'Nova', 'ordem' => 1, 'descricoes' => []]]
                    );

        $this->assertContains(99, $ids);
    }
}
