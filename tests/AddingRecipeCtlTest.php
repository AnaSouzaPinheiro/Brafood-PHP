<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config/Logger.php';
require_once __DIR__ . '/../model/AddingRecipeModel.php';
require_once __DIR__ . '/../controller/AddingRecipeCtl.php';

class AddingRecipeCtlTest extends TestCase
{
    // -------------------------------------------------------------------------
    // adicionarReceita
    // -------------------------------------------------------------------------

    public function testAdicionarReceitaSemTitulo(): void
    {
        $ctl = new AddingRecipeCtl(null);

        $resultado = $ctl->adicionarReceita(['categoria' => 'Doces'], []);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Título e categoria são obrigatórios.', $resultado['message']);
    }

    public function testAdicionarReceitaSemCategoria(): void
    {
        $ctl = new AddingRecipeCtl(null);

        $resultado = $ctl->adicionarReceita(['titulo' => 'Bolo'], []);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Título e categoria são obrigatórios.', $resultado['message']);
    }

    public function testAdicionarReceitaSemDados(): void
    {
        $ctl = new AddingRecipeCtl(null);

        $resultado = $ctl->adicionarReceita([], []);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Título e categoria são obrigatórios.', $resultado['message']);
    }

    public function testAdicionarReceitaComSucesso(): void
    {
        $model = $this->createMock(AddingRecipeModel::class);
        $model->method('criarReceita')->willReturn(42);
        $model->method('vincularSubcategoria')->willReturn(null);
        $model->method('criarItemIngrediente')->willReturn(1);
        $model->method('criarIngrediente')->willReturn(null);
        $model->method('criarEtapa')->willReturn(1);
        $model->method('criarDescricaoEtapa')->willReturn(null);

        $ctl = new AddingRecipeCtl(null);
        $ref = new ReflectionProperty(AddingRecipeCtl::class, 'addRecipe');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $dados = [
            'titulo'       => 'Pão de queijo',
            'categoria'    => 'Salgados',
            'subcategoria' => 'Petiscos',
            'ingredientes' => [
                ['titulo' => 'Massa', 'itens' => ['500g polvilho']],
            ],
            'etapas' => [
                ['titulo' => 'Mistura', 'descricoes' => [['descricao' => 'Misture tudo']]],
            ],
        ];

        $resultado = $ctl->adicionarReceita($dados, []);

        $this->assertTrue($resultado['success']);
        $this->assertEquals(42, $resultado['id']);
        $this->assertEquals('Receita criada com sucesso!', $resultado['message']);
    }

    public function testAdicionarReceitaSemSubcategoriaLogaWarn(): void
    {
        $model = $this->createMock(AddingRecipeModel::class);
        $model->method('criarReceita')->willReturn(1);

        $ctl = new AddingRecipeCtl(null);
        $ref = new ReflectionProperty(AddingRecipeCtl::class, 'addRecipe');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        // Não deve lançar exceção — subcategoria vazia apenas loga WARN
        $resultado = $ctl->adicionarReceita([
            'titulo'    => 'Bolo',
            'categoria' => 'Doces',
        ], []);

        $this->assertTrue($resultado['success']);
    }

    public function testAdicionarReceitaLancaExcecaoQuandoModelFalha(): void
    {
        $this->expectException(Exception::class);

        $model = $this->createMock(AddingRecipeModel::class);
        $model->method('criarReceita')
              ->willThrowException(new Exception('Erro no banco'));

        $ctl = new AddingRecipeCtl(null);
        $ref = new ReflectionProperty(AddingRecipeCtl::class, 'addRecipe');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $ctl->adicionarReceita(['titulo' => 'Bolo', 'categoria' => 'Doces'], []);
    }

    public function testAdicionarReceitaComIngredientesEtapasVazios(): void
    {
        $model = $this->createMock(AddingRecipeModel::class);
        $model->method('criarReceita')->willReturn(5);
        $model->expects($this->never())->method('criarItemIngrediente');
        $model->expects($this->never())->method('criarEtapa');

        $ctl = new AddingRecipeCtl(null);
        $ref = new ReflectionProperty(AddingRecipeCtl::class, 'addRecipe');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->adicionarReceita([
            'titulo'       => 'Bolo simples',
            'categoria'    => 'Doces',
            'ingredientes' => [],
            'etapas'       => [],
        ], []);

        $this->assertTrue($resultado['success']);
    }
}
