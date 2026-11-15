<?php

use PHPUnit\Framework\TestCase;
use App\Service\ApelidoService;
use App\Controller\LoginCtl;
use App\Model\LoginModel;

require_once __DIR__ . '/../config/Logger.php';
require_once __DIR__ . '/../model/LoginModel.php';
require_once __DIR__ . '/../controller/LoginCtl.php';

class LoginCtlTest extends TestCase
{
    /** ApelidoService sem banco: apelido sempre válido e livre. */
    private function apelidoMock(): ApelidoService
    {
        $m = $this->createMock(ApelidoService::class);
        $m->method('validar')->willReturn(null);
        $m->method('sugerir')->willReturn('joao');
        return $m;
    }

    // -------------------------------------------------------------------------
    // login
    // -------------------------------------------------------------------------

    public function testLoginSemEmail(): void
    {
        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock());

        $resultado = $ctl->login(['email' => '', 'senha' => 'abc123']);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('E-mail e senha são obrigatórios.', $resultado['message']);
    }

    public function testLoginSemSenha(): void
    {
        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock());

        $resultado = $ctl->login(['email' => 'user@example.com', 'senha' => '']);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('E-mail e senha são obrigatórios.', $resultado['message']);
    }

    public function testLoginSemDados(): void
    {
        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock());

        $resultado = $ctl->login([]);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('E-mail e senha são obrigatórios.', $resultado['message']);
    }

    public function testLoginComCredenciaisValidas(): void
    {
        $model = $this->createMock(LoginModel::class);
        $model->method('login')->willReturn([
            'success' => true,
            'usuario' => ['id' => 1, 'email' => 'user@example.com'],
        ]);

        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock());
        $ref = new ReflectionProperty(LoginCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->login(['email' => 'user@example.com', 'senha' => 'Senha@123']);

        $this->assertTrue($resultado['success']);
    }

    public function testLoginComCredenciaisInvalidas(): void
    {
        $model = $this->createMock(LoginModel::class);
        $model->method('login')->willReturn([
            'success' => false,
            'message' => 'Email ou senha incorretos',
        ]);

        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock());
        $ref = new ReflectionProperty(LoginCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->login(['email' => 'user@example.com', 'senha' => 'errada']);

        $this->assertFalse($resultado['success']);
    }

    // -------------------------------------------------------------------------
    // cadastro
    // -------------------------------------------------------------------------

    public function testCadastroSemEmail(): void
    {
        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock());

        $resultado = $ctl->cadastro(['email' => '', 'senha' => 'Senha@123']);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Dados obrigatórios ausentes.', $resultado['message']);
    }

    public function testCadastroSemSenha(): void
    {
        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock());

        $resultado = $ctl->cadastro(['email' => 'user@example.com', 'senha' => '']);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Dados obrigatórios ausentes.', $resultado['message']);
    }

    public function testCadastroComSucesso(): void
    {
        $model = $this->createMock(LoginModel::class);
        $model->method('cadastrar')->willReturn(['success' => true]);

        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock());
        $ref = new ReflectionProperty(LoginCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->cadastro([
            'nome'  => 'João',
            'email' => 'joao@example.com',
            'senha' => 'Senha@123',
        ]);

        $this->assertTrue($resultado['success']);
    }

    public function testCadastroEmailDuplicado(): void
    {
        $model = $this->createMock(LoginModel::class);
        $model->method('cadastrar')->willReturn([
            'success' => false,
            'errors'  => ['Este email já está cadastrado.'],
        ]);

        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock());
        $ref = new ReflectionProperty(LoginCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->cadastro([
            'nome'  => 'João',
            'email' => 'joao@example.com',
            'senha' => 'Senha@123',
        ]);

        $this->assertFalse($resultado['success']);
    }

    // -------------------------------------------------------------------------
    // resetarSenha
    // -------------------------------------------------------------------------

    public function testResetarSenhaDadosIncompletos(): void
    {
        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock());

        $resultado = $ctl->resetarSenha(['token' => 'abc']);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Dados incompletos.', $resultado['message']);
    }

    public function testResetarSenhaComSucesso(): void
    {
        $model = $this->createMock(LoginModel::class);
        $model->method('resetarSenha')->willReturn(['success' => true]);

        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock());
        $ref = new ReflectionProperty(LoginCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->resetarSenha([
            'token'          => 'token123',
            'senha'          => 'Nova@Senha1',
            'confirmarSenha' => 'Nova@Senha1',
        ]);

        $this->assertTrue($resultado['success']);
    }

    // -------------------------------------------------------------------------
    // enviarRecuperacaoSenha
    // -------------------------------------------------------------------------

    public function testEnviarRecuperacaoSemEmail(): void
    {
        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock());

        $resultado = $ctl->enviarRecuperacaoSenha([]);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('E-mail é obrigatório.', $resultado['message']);
    }

    public function testEnviarRecuperacaoEmailNaoEncontrado(): void
    {
        $model = $this->createMock(LoginModel::class);
        $model->method('gerarTokenRecuperacao')->willReturn([
            'success' => false,
            'message' => 'Email não encontrado.',
        ]);

        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock());
        $ref = new ReflectionProperty(LoginCtl::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($ctl, $model);

        $resultado = $ctl->enviarRecuperacaoSenha(['email' => 'naoexiste@example.com']);

        $this->assertFalse($resultado['success']);
    }
}
