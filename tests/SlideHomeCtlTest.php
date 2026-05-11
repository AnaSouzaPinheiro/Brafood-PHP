<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config/Logger.php';

/**
 * Testes para SlideHomeCtl.
 * SlideHome também instancia Database internamente, então usamos
 * newInstanceWithoutConstructor + ReflectionProperty para injetar o mock.
 */
class SlideHomeCtlTest extends TestCase
{
    private function makeCtlWithModel(object $model): object
    {
        $ctl = (new ReflectionClass('SlideHomeCtl'))->newInstanceWithoutConstructor();

        $ref = new ReflectionProperty('SlideHomeCtl', 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        return $ctl;
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function testIndexRetornaReceitasComLimitePadrao(): void
    {
        require_once __DIR__ . '/../model/SlideHomeModel.php';
        require_once __DIR__ . '/../controller/SlideHomeCtl.php';

        $model = $this->createMock(SlideHome::class);
        $model->expects($this->once())
              ->method('getRandomRecipes')
              ->with(5)
              ->willReturn(array_fill(0, 5, ['img' => 'foto.jpg']));

        $_GET = [];

        $ctl = $this->makeCtlWithModel($model);
        $resultado = $ctl->index();

        $this->assertCount(5, $resultado);
    }

    public function testIndexRespeiraLimiteDaQueryString(): void
    {
        require_once __DIR__ . '/../model/SlideHomeModel.php';
        require_once __DIR__ . '/../controller/SlideHomeCtl.php';

        $model = $this->createMock(SlideHome::class);
        $model->expects($this->once())
              ->method('getRandomRecipes')
              ->with(3)
              ->willReturn(array_fill(0, 3, ['img' => 'foto.jpg']));

        $_GET = ['limit' => '3'];

        $ctl = $this->makeCtlWithModel($model);
        $resultado = $ctl->index();

        $this->assertCount(3, $resultado);

        // Limpa superglobal
        $_GET = [];
    }

    public function testIndexRetornaArrayVazioQuandoSemReceitas(): void
    {
        require_once __DIR__ . '/../model/SlideHomeModel.php';
        require_once __DIR__ . '/../controller/SlideHomeCtl.php';

        $model = $this->createMock(SlideHome::class);
        $model->method('getRandomRecipes')->willReturn([]);

        $_GET = [];

        $ctl = $this->makeCtlWithModel($model);
        $resultado = $ctl->index();

        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }
}
