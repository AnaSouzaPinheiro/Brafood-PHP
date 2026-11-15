<?php

use PHPUnit\Framework\TestCase;
use App\Model\SecaoCarrossel;
use App\Controller\SecaoCarrosselCtl;

require_once __DIR__ . '/../config/Logger.php';
require_once __DIR__ . '/../model/SecaoCarrossel.php';
require_once __DIR__ . '/../controller/SecaoCarrosselCtl.php';

class SecaoCarrosselCtlTest extends TestCase
{
    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function testIndexRetornaSecoesComReceitas(): void
    {
        $model = $this->createMock(SecaoCarrossel::class);
        $model->method('getAllSecoes')->willReturn([
            ['id' => 1, 'titulo' => 'Destaques'],
        ]);
        $model->method('getReceitasDaSecao')->willReturn([
            ['id' => 10, 'titulo' => 'Pão de queijo'],
        ]);

        $ctl = new SecaoCarrosselCtl($model);

        $resultado = $ctl->index();

        $this->assertCount(1, $resultado);
        $this->assertEquals('Destaques', $resultado[0]['titulo']);
        $this->assertArrayHasKey('receitas', $resultado[0]);
        $this->assertCount(1, $resultado[0]['receitas']);
    }

    public function testIndexRetornaArrayVazioSemSecoes(): void
    {
        $model = $this->createMock(SecaoCarrossel::class);
        $model->method('getAllSecoes')->willReturn([]);

        $ctl = new SecaoCarrosselCtl($model);

        $resultado = $ctl->index();

        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function testIndexPreencheReceitasEmCadaSecao(): void
    {
        $model = $this->createMock(SecaoCarrossel::class);
        $model->method('getAllSecoes')->willReturn([
            ['id' => 1, 'titulo' => 'Seção A'],
            ['id' => 2, 'titulo' => 'Seção B'],
        ]);
        $model->method('getReceitasDaSecao')->willReturn([
            ['id' => 1, 'titulo' => 'Receita X'],
        ]);

        $ctl = new SecaoCarrosselCtl($model);

        $resultado = $ctl->index();

        $this->assertCount(2, $resultado);
        $this->assertArrayHasKey('receitas', $resultado[0]);
        $this->assertArrayHasKey('receitas', $resultado[1]);
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    public function testCreateRetornaIdDaSecaoCriada(): void
    {
        $model = $this->createMock(SecaoCarrossel::class);
        $model->method('createSecao')->willReturn(7);
        $model->expects($this->exactly(2))->method('addReceitaToSecao');

        $ctl = new SecaoCarrosselCtl($model);

        $id = $ctl->create([
            'titulo'   => 'Novidades',
            'receitas' => [1, 2],
        ]);

        $this->assertEquals(7, $id);
    }

    public function testCreateSemReceitasNaoAdicionaNenhuma(): void
    {
        $model = $this->createMock(SecaoCarrossel::class);
        $model->method('createSecao')->willReturn(3);
        $model->expects($this->never())->method('addReceitaToSecao');

        $ctl = new SecaoCarrosselCtl($model);

        $id = $ctl->create(['titulo' => 'Vazia']);

        $this->assertEquals(3, $id);
    }

    public function testCreatePassaValoresOpcionaisCorretos(): void
    {
        $model = $this->createMock(SecaoCarrossel::class);
        $model->expects($this->once())
              ->method('createSecao')
              ->with('Título', 'img.jpg', 'Fundo', 2)
              ->willReturn(1);

        $ctl = new SecaoCarrosselCtl($model);

        $ctl->create([
            'titulo'      => 'Título',
            'url_fundo'   => 'img.jpg',
            'fundo_titulo'=> 'Fundo',
            'ordem'       => 2,
        ]);
    }
}
