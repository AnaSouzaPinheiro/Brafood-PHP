<?php

use PHPUnit\Framework\TestCase;
use App\Controller\CategoriaCtl;
use App\Model\CategoriaModel;

require_once __DIR__ . '/../config/Logger.php';
require_once __DIR__ . '/../model/CategoriaModel.php';
require_once __DIR__ . '/../controller/CategoriaCtl.php';

class CategoriaCtlTest extends TestCase
{
    // -------------------------------------------------------------------------
    // listarCategorias
    // -------------------------------------------------------------------------

    public function testListarCategoriasRetornaArray(): void
    {
        $model = $this->createMock(CategoriaModel::class);
        $model->method('listarCategorias')->willReturn([
            ['id' => 1, 'nome' => 'Doces'],
            ['id' => 2, 'nome' => 'Salgados'],
        ]);

        $ctl = new CategoriaCtl($this->createMock(CategoriaModel::class));
        $ref = new ReflectionProperty(CategoriaCtl::class, 'categoriaModel');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->listarCategorias();

        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
    }

    public function testListarCategoriasRetornaVazioQuandoNaoHa(): void
    {
        $model = $this->createMock(CategoriaModel::class);
        $model->method('listarCategorias')->willReturn([]);

        $ctl = new CategoriaCtl($this->createMock(CategoriaModel::class));
        $ref = new ReflectionProperty(CategoriaCtl::class, 'categoriaModel');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->listarCategorias();

        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    // -------------------------------------------------------------------------
    // buscarCategoriaPorId
    // -------------------------------------------------------------------------

    public function testBuscarCategoriaPorIdNaoEncontrada(): void
    {
        $model = $this->createMock(CategoriaModel::class);
        $model->method('buscarCategoriaPorId')->willReturn(null);

        $ctl = new CategoriaCtl($this->createMock(CategoriaModel::class));
        $ref = new ReflectionProperty(CategoriaCtl::class, 'categoriaModel');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->buscarCategoriaPorId(99);

        $this->assertArrayHasKey('mensagem', $resultado);
        $this->assertEquals('Categoria não encontrada.', $resultado['mensagem']);
    }

    public function testBuscarCategoriaPorIdEncontrada(): void
    {
        $categoriaFake = ['id' => 1, 'nome' => 'Doces'];

        $model = $this->createMock(CategoriaModel::class);
        $model->method('buscarCategoriaPorId')->willReturn($categoriaFake);

        $ctl = new CategoriaCtl($this->createMock(CategoriaModel::class));
        $ref = new ReflectionProperty(CategoriaCtl::class, 'categoriaModel');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->buscarCategoriaPorId(1);

        $this->assertEquals('Doces', $resultado['nome']);
        $this->assertEquals(1, $resultado['id']);
    }
}
