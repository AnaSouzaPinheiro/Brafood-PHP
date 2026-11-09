<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config/Logger.php';
require_once __DIR__ . '/../model/UserModel.php';
require_once __DIR__ . '/../controller/UserCtl.php';

class UserCtlTest extends TestCase
{
    // -------------------------------------------------------------------------
    // buscarUsuarioPorId
    // -------------------------------------------------------------------------

    public function testBuscarUsuarioComIdZero(): void
    {
        $ctl = new UserCtl(null);

        $resultado = $ctl->buscarUsuarioPorId(0);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('ID inválido.', $resultado['message']);
    }

    public function testBuscarUsuarioComIdNegativo(): void
    {
        $ctl = new UserCtl(null);

        $resultado = $ctl->buscarUsuarioPorId(-5);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('ID inválido.', $resultado['message']);
    }

    public function testBuscarUsuarioNaoEncontrado(): void
    {
        $model = $this->createMock(UserModel::class);
        $model->method('buscarUserPorId')->willReturn(null);

        $ctl = new UserCtl(null);
        $ref = new ReflectionProperty(UserCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->buscarUsuarioPorId(999);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Usuário não encontrado.', $resultado['message']);
    }

    public function testBuscarUsuarioEncontrado(): void
    {
        $usuarioFake = [
            'id'        => 1,
            'nome'      => 'João',
            'email'     => 'joao@example.com',
        ];

        $model = $this->createMock(UserModel::class);
        $model->method('buscarUserPorId')->willReturn($usuarioFake);

        $ctl = new UserCtl(null);
        $ref = new ReflectionProperty(UserCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->buscarUsuarioPorId(1);

        $this->assertTrue($resultado['success']);
        $this->assertEquals($usuarioFake, $resultado['usuario']);
    }

    // -------------------------------------------------------------------------
    // editarUsuario
    // -------------------------------------------------------------------------

    public function testEditarUsuarioComSucesso(): void
    {
        $model = $this->createMock(UserModel::class);
        $model->expects($this->once())->method('editarUsuario');

        $ctl = new UserCtl(null);
        $ref = new ReflectionProperty(UserCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $dados = [
            'id'        => 1,
            'nome'      => 'João',
            'apelido'   => 'jsilva',
            'email'     => 'joao@example.com',
            'genero'    => 'M',
            'bio'       => 'Dev',
        ];

        $resultado = $ctl->editarUsuario($dados);

        $this->assertTrue($resultado['success']);
        $this->assertEquals('Usuário editado com sucesso!', $resultado['message']);
        $this->assertEquals(1, $resultado['id']);
    }

    public function testEditarUsuarioLancaExcecaoQuandoModelFalha(): void
    {
        $this->expectException(Exception::class);

        $model = $this->createMock(UserModel::class);
        $model->method('editarUsuario')
              ->willThrowException(new Exception('Erro no banco'));

        $ctl = new UserCtl(null);
        $ref = new ReflectionProperty(UserCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $ctl->editarUsuario(['id' => 1, 'nome' => 'João']);
    }

    public function testEditarUsuarioPreencheFieldsVazios(): void
    {
        $model = $this->createMock(UserModel::class);
        $model->expects($this->once())
              ->method('editarUsuario')
              ->with(1, '', '', '', '', '', '', null);

        $ctl = new UserCtl(null);
        $ref = new ReflectionProperty(UserCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->editarUsuario(['id' => 1]);

        $this->assertTrue($resultado['success']);
    }
}
