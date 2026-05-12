<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config/Logger.php';
require_once __DIR__ . '/../model/SlideHomeModel.php';
require_once __DIR__ . '/../controller/SlideHomeCtl.php';

class SlideHomeCtlTest extends TestCase
{
    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function testIndexUsaLimitePadraoCinco(): void
    {
        $model = $this->createMock(SlideHome::class);
        $model->expects($this->once())
              ->method('getRandomRecipes')
              ->with(5)
              ->willReturn(array_fill(0, 5, ['img' => 'foto.jpg']));

        $_GET = [];

        $ctl      = new SlideHomeCtl($model);
        $resultado = $ctl->index();

        $this->assertCount(5, $resultado);
    }

    public function testIndexRespeiraLimiteDaQueryString(): void
    {
        $model = $this->createMock(SlideHome::class);
        $model->expects($this->once())
              ->method('getRandomRecipes')
              ->with(3)
              ->willReturn(array_fill(0, 3, ['img' => 'foto.jpg']));

        $_GET = ['limit' => '3'];

        $ctl      = new SlideHomeCtl($model);
        $resultado = $ctl->index();

        $this->assertCount(3, $resultado);

        $_GET = [];
    }

    public function testIndexRetornaArrayVazioQuandoSemReceitas(): void
    {
        $model = $this->createMock(SlideHome::class);
        $model->method('getRandomRecipes')->willReturn([]);

        $_GET = [];

        $ctl      = new SlideHomeCtl($model);
        $resultado = $ctl->index();

        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function testIndexConverteLimitParaInteiro(): void
    {
        $model = $this->createMock(SlideHome::class);
        $model->expects($this->once())
              ->method('getRandomRecipes')
              ->with(2)   // "2abc" deve virar int(2)
              ->willReturn([]);

        $_GET = ['limit' => '2abc'];

        $ctl = new SlideHomeCtl($model);
        $ctl->index();

        $_GET = [];
    }
}
