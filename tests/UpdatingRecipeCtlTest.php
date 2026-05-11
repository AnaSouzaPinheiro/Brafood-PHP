<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config/Logger.php';
require_once __DIR__ . '/../model/UpdatingRecipeModel.php';
require_once __DIR__ . '/../model/AddingRecipeModel.php';
require_once __DIR__ . '/../model/ReceitaModel.php';
require_once __DIR__ . '/../controller/UpdatingRecipeCtl.php';

class UpdatingRecipeCtlTest extends TestCase
{
    private function makeCtl(
        UpdatingRecipeModel $updating,
        AddingRecipeModel   $adding,
        ReceitaModel        $receita
    ): UpdatingRecipeCtl {
        $ctl = new UpdatingRecipeCtl(null);

        $r1 = new ReflectionProperty(UpdatingRecipeCtl::class, 'model');
        $r1->setAccessible(true);
        $r1->setValue($ctl, $updating);

        $r2 = new ReflectionProperty(UpdatingRecipeCtl::class, 'addRecipeModel');
        $r2->setAccessible(true);
        $r2->setValue($ctl, $adding);

        $r3 = new ReflectionProperty(UpdatingRecipeCtl::class, 'receitaModel');
        $r3->setAccessible(true);
        $r3->setValue($ctl, $receita);

        return $ctl;
    }

    private function dadosMinimos(int $id = 1): array
    {
        return [
            'id'          => $id,
            'id_usuario'  => 1,
            'titulo'      => 'Bolo editado',
            'resumo'      => 'Resumo',
            'dificuldade' => 'Fácil',
            'tempo_hora'  => 0,
            'tempo_min'   => 30,
            'porcoes'     => 8,
            'tipo_porcao' => 'fatia',
            'subcategoria'=> null,
            'ingredientes'=> [],
            'etapas'      => [],
        ];
    }

    // -------------------------------------------------------------------------
    // editarReceita — fluxo feliz
    // -------------------------------------------------------------------------

    public function testEditarReceitaComSucesso(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $updating->method('updateReceita')->willReturn(null);

        $adding  = $this->createMock(AddingRecipeModel::class);

        $receita = $this->createMock(ReceitaModel::class);
        $receita->method('getSubcategoria')->willReturn(null);
        $receita->method('getIngredientes')->willReturn([]);
        $receita->method('getEtapas')->willReturn([]);

        $ctl = $this->makeCtl($updating, $adding, $receita);

        $resultado = $ctl->editarReceita($this->dadosMinimos(), []);

        $this->assertTrue($resultado['success']);
        $this->assertEquals('Receita editada com sucesso!', $resultado['message']);
        $this->assertEquals(1, $resultado['id']);
    }

    public function testEditarReceitaLancaExcecaoQuandoModelFalha(): void
    {
        $this->expectException(Exception::class);

        $updating = $this->createMock(UpdatingRecipeModel::class);
        $updating->method('updateReceita')
                 ->willThrowException(new Exception('Erro no banco'));

        $adding  = $this->createMock(AddingRecipeModel::class);
        $receita = $this->createMock(ReceitaModel::class);

        $ctl = $this->makeCtl($updating, $adding, $receita);

        $ctl->editarReceita($this->dadosMinimos(), []);
    }

    // -------------------------------------------------------------------------
    // editarReceita — lógica de subcategoria
    // -------------------------------------------------------------------------

    public function testEditarReceitaRemoveSubcategoriaQuandoNovaEhNula(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $updating->expects($this->once())->method('removerSubcategorias');

        $adding  = $this->createMock(AddingRecipeModel::class);

        $receita = $this->createMock(ReceitaModel::class);
        $receita->method('getSubcategoria')->willReturn('Bolos');
        $receita->method('getIngredientes')->willReturn([]);
        $receita->method('getEtapas')->willReturn([]);

        $ctl = $this->makeCtl($updating, $adding, $receita);

        $dados = $this->dadosMinimos();
        $dados['subcategoria'] = null;

        $resultado = $ctl->editarReceita($dados, []);

        $this->assertTrue($resultado['success']);
    }

    public function testEditarReceitaVinculaNovaSubcategoriaQuandoNaoHavia(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $updating->expects($this->once())->method('vincularSubcategoria');

        $adding  = $this->createMock(AddingRecipeModel::class);

        $receita = $this->createMock(ReceitaModel::class);
        $receita->method('getSubcategoria')->willReturn(null);
        $receita->method('getIngredientes')->willReturn([]);
        $receita->method('getEtapas')->willReturn([]);

        $ctl = $this->makeCtl($updating, $adding, $receita);

        $dados = $this->dadosMinimos();
        $dados['subcategoria'] = 'Petiscos';

        $resultado = $ctl->editarReceita($dados, []);

        $this->assertTrue($resultado['success']);
    }

    public function testEditarReceitaTrocaSubcategoriaQuandoMudou(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $updating->expects($this->once())->method('removerSubcategorias');
        $updating->expects($this->once())->method('vincularSubcategoria');

        $adding  = $this->createMock(AddingRecipeModel::class);

        $receita = $this->createMock(ReceitaModel::class);
        $receita->method('getSubcategoria')->willReturn('Bolos');
        $receita->method('getIngredientes')->willReturn([]);
        $receita->method('getEtapas')->willReturn([]);

        $ctl = $this->makeCtl($updating, $adding, $receita);

        $dados = $this->dadosMinimos();
        $dados['subcategoria'] = 'Tortas';

        $resultado = $ctl->editarReceita($dados, []);

        $this->assertTrue($resultado['success']);
    }

    // -------------------------------------------------------------------------
    // editarReceita — lógica de ingredientes
    // -------------------------------------------------------------------------

    public function testEditarReceitaAdicionaNovoIngrediente(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $adding   = $this->createMock(AddingRecipeModel::class);
        $adding->expects($this->once())->method('criarItemIngrediente')->willReturn(99);
        $adding->expects($this->once())->method('criarIngrediente');

        $receita = $this->createMock(ReceitaModel::class);
        $receita->method('getSubcategoria')->willReturn(null);
        $receita->method('getIngredientes')->willReturn([]);
        $receita->method('getEtapas')->willReturn([]);

        $ctl = $this->makeCtl($updating, $adding, $receita);

        $dados                = $this->dadosMinimos();
        $dados['ingredientes'] = [
            ['titulo' => 'Massa', 'itens' => ['500g farinha']],
        ];

        $resultado = $ctl->editarReceita($dados, []);

        $this->assertTrue($resultado['success']);
    }

    public function testEditarReceitaRemoveIngredienteAusente(): void
    {
        $updating = $this->createMock(UpdatingRecipeModel::class);
        $updating->expects($this->once())->method('removerIngrediente');

        $adding  = $this->createMock(AddingRecipeModel::class);

        $receita = $this->createMock(ReceitaModel::class);
        $receita->method('getSubcategoria')->willReturn(null);
        $receita->method('getIngredientes')->willReturn([
            ['titulo' => 'Massa', 'id_item' => 1, 'itens' => ['500g farinha']],
        ]);
        $receita->method('getEtapas')->willReturn([]);

        $ctl = $this->makeCtl($updating, $adding, $receita);

        $dados                = $this->dadosMinimos();
        $dados['ingredientes'] = [
            ['titulo' => 'Massa', 'itens' => []],   // Remove '500g farinha'
        ];

        $resultado = $ctl->editarReceita($dados, []);

        $this->assertTrue($resultado['success']);
    }
}
