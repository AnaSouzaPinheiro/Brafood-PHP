<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config/Logger.php';

/**
 * Testes para SecaoCarrosselCtl.
 *
 * Como SecaoCarrossel instancia Database internamente no construtor,
 * os testes injetam um mock via ReflectionProperty após a construção
 * do controller (passando o model já mockado diretamente).
 */
class SecaoCarrosselCtlTest extends TestCase
{
    private function makeCtlWithModel(object $model): object
    {
        // Criamos o controller sem chamar __construct real,
        // pois ele instancia SecaoCarrossel (que exige DB).
        $ctl = (new ReflectionClass('SecaoCarrosselCtl'))->newInstanceWithoutConstructor();

        $ref = new ReflectionProperty('SecaoCarrosselCtl', 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        return $ctl;
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function testIndexRetornaSecoesComReceitas(): void
    {
        require_once __DIR__ . '/../model/SecaoCarrossel.php';
        require_once __DIR__ . '/../controller/SecaoCarrosselCtl.php';

        $model = $this->createMock(SecaoCarrossel::class);
        $model->method('getAllSecoes')->willReturn([
            ['id' => 1, 'titulo' => 'Destaques'],
        ]);
        $model->method('getReceitasDaSecao')->willReturn([
            ['id' => 10, 'titulo' => 'Pão de queijo'],
        ]);

        $ctl = $this->makeCtlWithModel($model);

        $resultado = $ctl->index();

        $this->assertCount(1, $resultado);
        $this->assertEquals('Destaques', $resultado[0]['titulo']);
        $this->assertArrayHasKey('receitas', $resultado[0]);
        $this->assertCount(1, $resultado[0]['receitas']);
    }

    public function testIndexRetornaArrayVazioSemSecoes(): void
    {
        require_once __DIR__ . '/../model/SecaoCarrossel.php';
        require_once __DIR__ . '/../controller/SecaoCarrosselCtl.php';

        $model = $this->createMock(SecaoCarrossel::class);
        $model->method('getAllSecoes')->willReturn([]);

        $ctl = $this->makeCtlWithModel($model);

        $resultado = $ctl->index();

        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    public function testCreateRetornaIdDaSecaoCriada(): void
    {
        require_once __DIR__ . '/../model/SecaoCarrossel.php';
        require_once __DIR__ . '/../controller/SecaoCarrosselCtl.php';

        $model = $this->createMock(SecaoCarrossel::class);
        $model->method('createSecao')->willReturn(7);
        $model->expects($this->exactly(2))
              ->method('addReceitaToSecao');

        $ctl = $this->makeCtlWithModel($model);

        $data = [
            'titulo'   => 'Novidades',
            'receitas' => [1, 2],
        ];

        $id = $ctl->create($data);

        $this->assertEquals(7, $id);
    }

    public function testCreateSemReceitasNaoAdicionaNenhuma(): void
    {
        require_once __DIR__ . '/../model/SecaoCarrossel.php';
        require_once __DIR__ . '/../controller/SecaoCarrosselCtl.php';

        $model = $this->createMock(SecaoCarrossel::class);
        $model->method('createSecao')->willReturn(3);
        $model->expects($this->never())->method('addReceitaToSecao');

        $ctl = $this->makeCtlWithModel($model);

        $id = $ctl->create(['titulo' => 'Vazia']);

        $this->assertEquals(3, $id);
    }
}
