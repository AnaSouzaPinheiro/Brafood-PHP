<?php

use PHPUnit\Framework\TestCase;
use App\Model\CategoriaModel;
use App\Controller\ReceitaCtl;
use App\Model\ReceitaModel;

require_once __DIR__ . '/../config/Logger.php';
require_once __DIR__ . '/../model/ReceitaModel.php';
require_once __DIR__ . '/../model/CategoriaModel.php';
require_once __DIR__ . '/../controller/ReceitaCtl.php';

class ReceitaCtlTest extends TestCase
{
    // -------------------------------------------------------------------------
    // listarPorUsuario
    // -------------------------------------------------------------------------

    public function testListarPorUsuarioIdInvalido(): void
    {
        $ctl = new ReceitaCtl($this->createMock(ReceitaModel::class), $this->createMock(CategoriaModel::class));

        $resultado = $ctl->listarPorUsuario(0);

        $this->assertArrayHasKey('mensagem', $resultado);
        $this->assertEquals('ID inválido.', $resultado['mensagem']);
    }

    public function testListarPorUsuarioIdNegativo(): void
    {
        $ctl = new ReceitaCtl($this->createMock(ReceitaModel::class), $this->createMock(CategoriaModel::class));

        $resultado = $ctl->listarPorUsuario(-1);

        $this->assertEquals('ID inválido.', $resultado['mensagem']);
    }

    public function testListarPorUsuarioComSucesso(): void
    {
        $receitaModel = $this->createMock(ReceitaModel::class);
        $receitaModel->method('listByUser')->willReturn([
            ['id' => 1, 'titulo' => 'Pão de queijo'],
        ]);

        $ctl = new ReceitaCtl($this->createMock(ReceitaModel::class), $this->createMock(CategoriaModel::class));
        $rR = new ReflectionProperty(ReceitaCtl::class, 'receitaModel');
        $rR->setAccessible(true);
        $rR->setValue($ctl, $receitaModel);

        $resultado = $ctl->listarPorUsuario(1);

        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
    }

    // -------------------------------------------------------------------------
    // listarPorCategoria
    // -------------------------------------------------------------------------

    public function testListarPorCategoriaIdNulo(): void
    {
        $ctl = new ReceitaCtl($this->createMock(ReceitaModel::class), $this->createMock(CategoriaModel::class));

        $resultado = $ctl->listarPorCategoria(null);

        $this->assertEquals('ID da categoria não informado.', $resultado['mensagem']);
    }

    public function testListarPorCategoriaNaoEncontrada(): void
    {
        $catModel = $this->createMock(CategoriaModel::class);
        $catModel->method('buscarCategoriaPorId')->willReturn(null);

        $ctl = new ReceitaCtl($this->createMock(ReceitaModel::class), $this->createMock(CategoriaModel::class));
        $rC = new ReflectionProperty(ReceitaCtl::class, 'categoriaModel');
        $rC->setAccessible(true);
        $rC->setValue($ctl, $catModel);

        $resultado = $ctl->listarPorCategoria(99);

        $this->assertEquals('Categoria não encontrada.', $resultado['mensagem']);
    }

    public function testListarPorCategoriaComSucesso(): void
    {
        $catModel = $this->createMock(CategoriaModel::class);
        $catModel->method('buscarCategoriaPorId')->willReturn(['id' => 1, 'nome' => 'Doces']);

        $receitaModel = $this->createMock(ReceitaModel::class);
        $receitaModel->method('listarComCategoria')->willReturn([
            ['id' => 1, 'titulo' => 'Bolo de chocolate'],
        ]);

        $ctl = new ReceitaCtl($this->createMock(ReceitaModel::class), $this->createMock(CategoriaModel::class));
        $rC = new ReflectionProperty(ReceitaCtl::class, 'categoriaModel');
        $rC->setAccessible(true);
        $rC->setValue($ctl, $catModel);

        $rR = new ReflectionProperty(ReceitaCtl::class, 'receitaModel');
        $rR->setAccessible(true);
        $rR->setValue($ctl, $receitaModel);

        $resultado = $ctl->listarPorCategoria(1);

        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
    }

    // -------------------------------------------------------------------------
    // listarTodas
    // -------------------------------------------------------------------------

    public function testListarTodasSemPesquisa(): void
    {
        $receitaModel = $this->createMock(ReceitaModel::class);
        $receitaModel->method('listarTodas')->with('')->willReturn([
            ['id' => 1, 'titulo' => 'Feijoada'],
        ]);

        $ctl = new ReceitaCtl($this->createMock(ReceitaModel::class), $this->createMock(CategoriaModel::class));
        $rR = new ReflectionProperty(ReceitaCtl::class, 'receitaModel');
        $rR->setAccessible(true);
        $rR->setValue($ctl, $receitaModel);

        $resultado = $ctl->listarTodas();

        $this->assertCount(1, $resultado);
    }

    public function testListarTodasComPesquisa(): void
    {
        $receitaModel = $this->createMock(ReceitaModel::class);
        $receitaModel->method('listarTodas')->with('feij')->willReturn([]);

        $ctl = new ReceitaCtl($this->createMock(ReceitaModel::class), $this->createMock(CategoriaModel::class));
        $rR = new ReflectionProperty(ReceitaCtl::class, 'receitaModel');
        $rR->setAccessible(true);
        $rR->setValue($ctl, $receitaModel);

        $resultado = $ctl->listarTodas('feij');

        $this->assertIsArray($resultado);
    }

    // -------------------------------------------------------------------------
    // buscarPorId
    // -------------------------------------------------------------------------

    public function testBuscarPorIdInvalido(): void
    {
        $ctl = new ReceitaCtl($this->createMock(ReceitaModel::class), $this->createMock(CategoriaModel::class));

        $resultado = $ctl->buscarPorId(0);

        $this->assertEquals('ID inválido.', $resultado['mensagem']);
    }

    public function testBuscarPorIdNaoEncontrada(): void
    {
        $receitaModel = $this->createMock(ReceitaModel::class);
        $receitaModel->method('buscarPorId')->willReturn(null);

        $ctl = new ReceitaCtl($this->createMock(ReceitaModel::class), $this->createMock(CategoriaModel::class));
        $rR = new ReflectionProperty(ReceitaCtl::class, 'receitaModel');
        $rR->setAccessible(true);
        $rR->setValue($ctl, $receitaModel);

        $resultado = $ctl->buscarPorId(999);

        $this->assertEquals('Receita não encontrada.', $resultado['mensagem']);
    }

    public function testBuscarPorIdComSucesso(): void
    {
        $receitaFake = ['id' => 1, 'titulo' => 'Pão de queijo'];

        $receitaModel = $this->createMock(ReceitaModel::class);
        $receitaModel->method('buscarPorId')->willReturn($receitaFake);

        $ctl = new ReceitaCtl($this->createMock(ReceitaModel::class), $this->createMock(CategoriaModel::class));
        $rR = new ReflectionProperty(ReceitaCtl::class, 'receitaModel');
        $rR->setAccessible(true);
        $rR->setValue($ctl, $receitaModel);

        $resultado = $ctl->buscarPorId(1);

        $this->assertEquals('Pão de queijo', $resultado['titulo']);
    }

    // -------------------------------------------------------------------------
    // excluirReceita
    // -------------------------------------------------------------------------

    public function testExcluirReceitaIdInvalido(): void
    {
        $ctl = new ReceitaCtl($this->createMock(ReceitaModel::class), $this->createMock(CategoriaModel::class));

        $resultado = $ctl->excluirReceita(0);

        $this->assertEquals('ID inválido.', $resultado['mensagem']);
    }

    public function testExcluirReceitaNaoEncontrada(): void
    {
        $receitaModel = $this->createMock(ReceitaModel::class);
        $receitaModel->method('excluirReceita')->willReturn(null);

        $ctl = new ReceitaCtl($this->createMock(ReceitaModel::class), $this->createMock(CategoriaModel::class));
        $rR = new ReflectionProperty(ReceitaCtl::class, 'receitaModel');
        $rR->setAccessible(true);
        $rR->setValue($ctl, $receitaModel);

        $resultado = $ctl->excluirReceita(99);

        $this->assertEquals('Receita não encontrada.', $resultado['mensagem']);
    }

    public function testExcluirReceitaComSucesso(): void
    {
        $receitaModel = $this->createMock(ReceitaModel::class);
        $receitaModel->method('excluirReceita')->willReturn(['id' => 1]);

        $ctl = new ReceitaCtl($this->createMock(ReceitaModel::class), $this->createMock(CategoriaModel::class));
        $rR = new ReflectionProperty(ReceitaCtl::class, 'receitaModel');
        $rR->setAccessible(true);
        $rR->setValue($ctl, $receitaModel);

        $resultado = $ctl->excluirReceita(1);

        $this->assertNotNull($resultado);
    }
}
