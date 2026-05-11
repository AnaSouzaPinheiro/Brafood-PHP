<?php

use PHPUnit\Framework\TestCase;

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

        $ctl = new FavoritoCtl(null);
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

        $ctl = new FavoritoCtl(null);
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
        $model->method('isFavorite')->willReturn(true);

        $dbMock = $this->createMock(PDO::class);
        $dbMock->method('beginTransaction')->willReturn(true);
        $dbMock->method('rollBack')->willReturn(true);

        $ctl = new FavoritoCtl($dbMock);
        $ref = new ReflectionProperty(FavoritoCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->add(1, 10);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Já favoritado', $resultado['message']);
    }

    public function testAddComSucesso(): void
    {
        $model = $this->createMock(FavoritoModel::class);
        $model->method('isFavorite')->willReturn(false);
        $model->method('add')->willReturn(true);

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('rowCount')->willReturn(1);

        $dbMock = $this->createMock(PDO::class);
        $dbMock->method('beginTransaction')->willReturn(true);
        $dbMock->method('commit')->willReturn(true);
        $dbMock->method('prepare')->willReturn($stmt);

        $ctl = new FavoritoCtl($dbMock);
        $ref = new ReflectionProperty(FavoritoCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->add(1, 10);

        $this->assertTrue($resultado['success']);
    }

    public function testAddReceitaNaoEncontradaLancaException(): void
    {
        $model = $this->createMock(FavoritoModel::class);
        $model->method('isFavorite')->willReturn(false);
        $model->method('add')->willReturn(true);

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('rowCount')->willReturn(0);

        $dbMock = $this->createMock(PDO::class);
        $dbMock->method('beginTransaction')->willReturn(true);
        $dbMock->method('rollBack')->willReturn(true);
        $dbMock->method('prepare')->willReturn($stmt);

        $ctl = new FavoritoCtl($dbMock);
        $ref = new ReflectionProperty(FavoritoCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->add(1, 99);

        $this->assertFalse($resultado['success']);
    }

    // -------------------------------------------------------------------------
    // remove
    // -------------------------------------------------------------------------

    public function testRemoveComSucesso(): void
    {
        $model = $this->createMock(FavoritoModel::class);
        $model->method('remove')->willReturn(true);

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);

        $dbMock = $this->createMock(PDO::class);
        $dbMock->method('beginTransaction')->willReturn(true);
        $dbMock->method('commit')->willReturn(true);
        $dbMock->method('prepare')->willReturn($stmt);

        $ctl = new FavoritoCtl($dbMock);
        $ref = new ReflectionProperty(FavoritoCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->remove(1, 10);

        $this->assertTrue($resultado['success']);
    }

    public function testRemoveFalhaRetornaFalse(): void
    {
        $model = $this->createMock(FavoritoModel::class);
        $model->method('remove')->willThrowException(new Exception('Erro'));

        $dbMock = $this->createMock(PDO::class);
        $dbMock->method('beginTransaction')->willReturn(true);
        $dbMock->method('rollBack')->willReturn(true);
        $dbMock->method('prepare')->willReturn($this->createMock(PDOStatement::class));

        $ctl = new FavoritoCtl($dbMock);
        $ref = new ReflectionProperty(FavoritoCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

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

        $ctl = new FavoritoCtl(null);
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
        $ctl = new FavoritoCtl(null);

        $ingredientes = [
            ['titulo' => 'Massa', 'itens' => ['500g farinha', '2 ovos']],
        ];

        $link = $ctl->getLinkWhatsApp($ingredientes);

        $this->assertStringStartsWith('https://wa.me/?text=', $link);
        $this->assertStringContainsString('Massa', urldecode($link));
    }

    public function testGetLinkWhatsAppComLinkReceita(): void
    {
        $ctl = new FavoritoCtl(null);

        $link = $ctl->getLinkWhatsApp([], 'https://brafood.com/receita/1');

        $this->assertStringContainsString('brafood.com', urldecode($link));
    }

    public function testGetLinkWhatsAppSemIngredientes(): void
    {
        $ctl = new FavoritoCtl(null);

        $link = $ctl->getLinkWhatsApp([]);

        $this->assertStringStartsWith('https://wa.me/?text=', $link);
    }
}
