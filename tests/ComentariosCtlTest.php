<?php

use PHPUnit\Framework\TestCase;
use App\Model\ComentarioModel;
use App\Controller\ComentariosCtl;

require_once __DIR__ . '/../config/Logger.php';
require_once __DIR__ . '/../model/ComentarioModel.php';
require_once __DIR__ . '/../controller/ComentariosCtl.php';

class ComentariosCtlTest extends TestCase
{
    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function testIndexSemIdReceitaRetorna400(): void
    {
        $ctl = new ComentariosCtl($this->createMock(ComentarioModel::class), fn() => ['id' => 2]);

        $resultado = $ctl->index(null);

        $this->assertEquals(400, $resultado['status']);
        $this->assertEquals('Receita não informada', $resultado['body']['error']);
    }

    public function testIndexComIdZeroRetorna400(): void
    {
        $ctl = new ComentariosCtl($this->createMock(ComentarioModel::class), fn() => ['id' => 2]);

        $resultado = $ctl->index(0);

        $this->assertEquals(400, $resultado['status']);
    }

    public function testIndexRetornaComentarios(): void
    {
        $comentariosFake = [
            ['id' => 1, 'texto' => 'Ótima receita!', 'id_usuario' => 5],
            ['id' => 2, 'texto' => 'Adorei!',        'id_usuario' => 7],
        ];

        $model = $this->createMock(ComentarioModel::class);
        $model->method('buscarComentarioPorReceita')->willReturn($comentariosFake);

        $ctl = new ComentariosCtl($this->createMock(ComentarioModel::class), fn() => ['id' => 2]);
        $ref = new ReflectionProperty(ComentariosCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->index(1);

        $this->assertEquals(200, $resultado['status']);
        $this->assertCount(2, $resultado['body']);
    }

    public function testIndexRetornaArrayVazioQuandoSemComentarios(): void
    {
        $model = $this->createMock(ComentarioModel::class);
        $model->method('buscarComentarioPorReceita')->willReturn([]);

        $ctl = new ComentariosCtl($this->createMock(ComentarioModel::class), fn() => ['id' => 2]);
        $ref = new ReflectionProperty(ComentariosCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->index(1);

        $this->assertEquals(200, $resultado['status']);
        $this->assertEmpty($resultado['body']);
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function testStoreSemIdReceitaRetorna400(): void
    {
        $ctl = new ComentariosCtl($this->createMock(ComentarioModel::class), fn() => ['id' => 2]);

        $resultado = $ctl->store(['texto' => 'Oi', 'id_usuario' => 1]);

        $this->assertEquals(400, $resultado['status']);
        $this->assertStringContainsString('obrigatórios', $resultado['body']['error']);
    }

    public function testStoreSemTextoRetorna400(): void
    {
        $ctl = new ComentariosCtl($this->createMock(ComentarioModel::class), fn() => ['id' => 2]);

        $resultado = $ctl->store(['id_receita' => 1, 'id_usuario' => 1]);

        $this->assertEquals(400, $resultado['status']);
    }

    public function testStoreUsaOUsuarioDoLoginEIgnoraIdDoCorpo(): void
    {
        $model = $this->createMock(ComentarioModel::class);
        $model->expects($this->once())
              ->method('criar')
              ->with($this->callback(fn ($dados) => $dados['id_usuario'] === 2));

        $ctl = new ComentariosCtl($model, fn() => ['id' => 2]);

        $resultado = $ctl->store(['id_receita' => 1, 'texto' => 'Oi', 'id_usuario' => 99]);

        $this->assertEquals(201, $resultado['status']);
    }

    public function testStoreDadosVaziosRetorna400(): void
    {
        $ctl = new ComentariosCtl($this->createMock(ComentarioModel::class), fn() => ['id' => 2]);

        $resultado = $ctl->store([]);

        $this->assertEquals(400, $resultado['status']);
    }

    public function testStoreComSucessoRetorna201(): void
    {
        $model = $this->createMock(ComentarioModel::class);
        $model->expects($this->once())->method('criar');

        $ctl = new ComentariosCtl($this->createMock(ComentarioModel::class), fn() => ['id' => 2]);
        $ref = new ReflectionProperty(ComentariosCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->store([
            'id_receita' => 1,
            'id_usuario' => 2,
            'texto'      => 'Delicioso!',
        ]);

        $this->assertEquals(201, $resultado['status']);
        $this->assertTrue($resultado['body']['success']);
    }

    public function testStoreComRespostaAninhada(): void
    {
        $model = $this->createMock(ComentarioModel::class);
        $model->expects($this->once())
              ->method('criar')
              ->with($this->callback(function ($dados) {
                  return $dados['id_comentario_pai'] === 5;
              }));

        $ctl = new ComentariosCtl($this->createMock(ComentarioModel::class), fn() => ['id' => 2]);
        $ref = new ReflectionProperty(ComentariosCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->store([
            'id_receita'        => 1,
            'id_usuario'        => 2,
            'texto'             => 'Concordo!',
            'id_comentario_pai' => 5,
        ]);

        $this->assertEquals(201, $resultado['status']);
    }

    public function testStoreModelFalhaRetorna500(): void
    {
        $model = $this->createMock(ComentarioModel::class);
        $model->method('criar')->willThrowException(new Exception('Erro no banco'));

        $ctl = new ComentariosCtl($this->createMock(ComentarioModel::class), fn() => ['id' => 2]);
        $ref = new ReflectionProperty(ComentariosCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->store([
            'id_receita' => 1,
            'id_usuario' => 2,
            'texto'      => 'Falha',
        ]);

        $this->assertEquals(500, $resultado['status']);
        $this->assertArrayHasKey('error', $resultado['body']);
    }
}
