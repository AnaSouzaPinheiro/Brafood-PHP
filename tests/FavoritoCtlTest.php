<?php

use PHPUnit\Framework\TestCase;
use App\Controller\FavoritoCtl;
use App\Model\FavoritoModel;

require_once __DIR__ . '/../config/Logger.php';
require_once __DIR__ . '/../model/FavoritoModel.php';
require_once __DIR__ . '/../controller/FavoritoCtl.php';

class FavoritoCtlTest extends TestCase
{
    // -------------------------------------------------------------------------
    // check
    // -------------------------------------------------------------------------

    public function testCheckRetornaTrueQuandoFavoritado(): void
    {
        $model = $this->createMock(FavoritoModel::class);
        $model->method('isFavorite')->willReturn(true);

        $ctl = new FavoritoCtl($this->createMock(FavoritoModel::class));
        $ref = new ReflectionProperty(FavoritoCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->check(1, 10);

        $this->assertTrue($resultado['favorite']);
    }

    public function testCheckRetornaFalseQuandoNaoFavoritado(): void
    {
        $model = $this->createMock(FavoritoModel::class);
        $model->method('isFavorite')->willReturn(false);

        $ctl = new FavoritoCtl($this->createMock(FavoritoModel::class));
        $ref = new ReflectionProperty(FavoritoCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->check(1, 10);

        $this->assertFalse($resultado['favorite']);
    }

    // -------------------------------------------------------------------------
    // add
    // -------------------------------------------------------------------------

    public function testAddJaFavoritadoRetornaFalse(): void
    {
        $model = $this->createMock(FavoritoModel::class);
        $model->method('addWithIncrement')
              ->willReturn(['success' => false, 'mensagem' => 'Já favoritado']);

        $ctl = new FavoritoCtl($model);

        $resultado = $ctl->add(1, 10);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Já favoritado', $resultado['mensagem']);
    }

    public function testAddComSucesso(): void
    {
        $model = $this->createMock(FavoritoModel::class);
        $model->expects($this->once())
              ->method('addWithIncrement')
              ->with(1, 10)
              ->willReturn(['success' => true]);

        $ctl = new FavoritoCtl($model);

        $resultado = $ctl->add(1, 10);

        $this->assertTrue($resultado['success']);
    }

    public function testAddReceitaNaoEncontradaRetornaErro(): void
    {
        $model = $this->createMock(FavoritoModel::class);
        $model->method('addWithIncrement')
              ->willThrowException(new Exception('Receita não encontrada'));

        $ctl = new FavoritoCtl($model);

        $resultado = $ctl->add(1, 99);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Receita não encontrada', $resultado['error']);
    }

    // -------------------------------------------------------------------------
    // remove
    // -------------------------------------------------------------------------

    public function testRemoveComSucesso(): void
    {
        $model = $this->createMock(FavoritoModel::class);
        $model->expects($this->once())
              ->method('removeWithDecrement')
              ->with(1, 10)
              ->willReturn(['success' => true]);

        $ctl = new FavoritoCtl($model);

        $resultado = $ctl->remove(1, 10);

        $this->assertTrue($resultado['success']);
    }

    public function testRemoveFalhaRetornaFalse(): void
    {
        $model = $this->createMock(FavoritoModel::class);
        $model->method('removeWithDecrement')
              ->willThrowException(new Exception('Erro'));

        $ctl = new FavoritoCtl($model);

        $resultado = $ctl->remove(1, 10);

        $this->assertFalse($resultado['success']);
    }

    // -------------------------------------------------------------------------
    // listByUser
    // -------------------------------------------------------------------------

    public function testListByUserRetornaArray(): void
    {
        $model = $this->createMock(FavoritoModel::class);
        $model->method('listByUser')->willReturn([
            ['id' => 1, 'titulo' => 'Pão de queijo'],
        ]);

        $ctl = new FavoritoCtl($this->createMock(FavoritoModel::class));
        $ref = new ReflectionProperty(FavoritoCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->listByUser(1);

        $this->assertCount(1, $resultado);
    }

    // -------------------------------------------------------------------------
    // getLinkWhatsApp
    // -------------------------------------------------------------------------

    public function testGetLinkWhatsAppFormatoBasico(): void
    {
        $ctl = new FavoritoCtl($this->createMock(FavoritoModel::class));

        $ingredientes = [
            ['titulo' => 'Massa', 'itens' => ['500g farinha', '2 ovos']],
        ];

        $link = $ctl->getLinkWhatsApp($ingredientes);

        $this->assertStringStartsWith('https://wa.me/?text=', $link);
        $this->assertStringContainsString('Massa', urldecode($link));
    }

    public function testGetLinkWhatsAppComLinkReceita(): void
    {
        $ctl = new FavoritoCtl($this->createMock(FavoritoModel::class));

        $link = $ctl->getLinkWhatsApp([], 'https://brafood.com/receita/1');

        $this->assertStringContainsString('brafood.com', urldecode($link));
    }

    public function testGetLinkWhatsAppSemIngredientes(): void
    {
        $ctl = new FavoritoCtl($this->createMock(FavoritoModel::class));

        $link = $ctl->getLinkWhatsApp([]);

        $this->assertStringStartsWith('https://wa.me/?text=', $link);
    }
}
