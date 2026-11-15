<?php

use PHPUnit\Framework\TestCase;
use App\Service\ApelidoService;
use App\Service\RateLimiter;
use App\Controller\LoginCtl;
use App\Model\LoginModel;

require_once __DIR__ . '/../config/Logger.php';
require_once __DIR__ . '/../model/LoginModel.php';
require_once __DIR__ . '/../controller/LoginCtl.php';

class LoginCtlTest extends TestCase
{

    private string $dirRl;

    protected function setUp(): void
    {
        $this->dirRl = sys_get_temp_dir() . '/brafood_login_' . uniqid();
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dirRl . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dirRl);
        unset($_SERVER['REMOTE_ADDR']);
    }

    /** Limitador em pasta temporária (não toca o cache real da API). */
    private function limitador(): RateLimiter
    {
        return new RateLimiter($this->dirRl);
    }

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
        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock(), $this->limitador());

        $resultado = $ctl->login(['email' => '', 'senha' => 'abc123']);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('E-mail e senha são obrigatórios.', $resultado['message']);
    }

    public function testLoginSemSenha(): void
    {
        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock(), $this->limitador());

        $resultado = $ctl->login(['email' => 'user@example.com', 'senha' => '']);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('E-mail e senha são obrigatórios.', $resultado['message']);
    }

    public function testLoginSemDados(): void
    {
        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock(), $this->limitador());

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

        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock(), $this->limitador());
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

        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock(), $this->limitador());
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
        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock(), $this->limitador());

        $resultado = $ctl->cadastro(['email' => '', 'senha' => 'Senha@123']);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Dados obrigatórios ausentes.', $resultado['message']);
    }

    public function testCadastroSemSenha(): void
    {
        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock(), $this->limitador());

        $resultado = $ctl->cadastro(['email' => 'user@example.com', 'senha' => '']);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Dados obrigatórios ausentes.', $resultado['message']);
    }

    public function testCadastroComSucesso(): void
    {
        $model = $this->createMock(LoginModel::class);
        $model->method('cadastrar')->willReturn(['success' => true]);

        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock(), $this->limitador());
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

        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock(), $this->limitador());
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
        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock(), $this->limitador());

        $resultado = $ctl->resetarSenha(['token' => 'abc']);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('Dados incompletos.', $resultado['message']);
    }

    public function testResetarSenhaComSucesso(): void
    {
        $model = $this->createMock(LoginModel::class);
        $model->method('resetarSenha')->willReturn(['success' => true]);

        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock(), $this->limitador());
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
        $ctl = new LoginCtl($this->createMock(LoginModel::class), $this->apelidoMock(), $this->limitador());

        $resultado = $ctl->enviarRecuperacaoSenha([]);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('E-mail é obrigatório.', $resultado['message']);
    }

    public function testEnviarRecuperacaoRespondeIgualParaEmailInexistente(): void
    {
        $model = $this->createMock(LoginModel::class);
        $model->method('gerarTokenRecuperacao')->willReturn([
            'success'  => false,
            'mensagem' => 'Email não encontrado.',
        ]);

        $ctl = new LoginCtl($model, $this->apelidoMock(), $this->limitador());

        $resultado = $ctl->enviarRecuperacaoSenha(['email' => 'naoexiste@example.com']);

        $this->assertTrue($resultado['success']);
        $this->assertStringNotContainsString('não encontrado', $resultado['message']);
        $this->assertStringContainsString('Se o e-mail estiver cadastrado', $resultado['message']);
    }

    public function testEnviarRecuperacaoLimitaPedidosPorEmailSemRevelarNada(): void
    {
        $model = $this->createMock(LoginModel::class);
        $model->expects($this->exactly(LoginCtl::RECUPERACAO_MAX))
              ->method('gerarTokenRecuperacao')
              ->willReturn(['success' => false]);

        $ctl = new LoginCtl($model, $this->apelidoMock(), $this->limitador());

        $respostas = [];
        for ($i = 0; $i < LoginCtl::RECUPERACAO_MAX + 2; $i++) {
            $respostas[] = $ctl->enviarRecuperacaoSenha(['email' => 'alguem@example.com']);
        }

        // Mesmo bloqueado, a resposta é a mesma (e o model não é mais chamado)
        $this->assertCount(1, array_unique(array_map('json_encode', $respostas)));
    }

    // -------------------------------------------------------------------------
    // limite de tentativas de login
    // -------------------------------------------------------------------------

    public function testLoginBloqueiaAposMuitasFalhas(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.7';

        $model = $this->createMock(LoginModel::class);
        $model->expects($this->exactly(LoginCtl::LOGIN_MAX_FALHAS))
              ->method('login')
              ->willReturn(['success' => false, 'mensagem' => 'Email ou senha incorretos']);

        $ctl = new LoginCtl($model, $this->apelidoMock(), $this->limitador());

        for ($i = 0; $i < LoginCtl::LOGIN_MAX_FALHAS; $i++) {
            $r = $ctl->login(['email' => 'user@example.com', 'senha' => 'errada']);
            $this->assertArrayNotHasKey('bloqueado', $r);
        }

        $bloqueado = $ctl->login(['email' => 'user@example.com', 'senha' => 'errada']);

        $this->assertFalse($bloqueado['success']);
        $this->assertTrue($bloqueado['bloqueado']);
        $this->assertGreaterThan(0, $bloqueado['retry_after']);
    }

    public function testLoginBemSucedidoZeraAsFalhas(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.7';

        $falha = ['success' => false, 'mensagem' => 'x'];
        $model = $this->createMock(LoginModel::class);
        $model->method('login')->willReturnOnConsecutiveCalls(
            $falha,
            $falha,
            ['success' => true, 'usuario' => ['id' => 1], 'token' => 't'],
            $falha,
            $falha,
            $falha,
            $falha,
            $falha
        );

        $ctl = new LoginCtl($model, $this->apelidoMock(), $this->limitador());
        $dados = ['email' => 'user@example.com', 'senha' => 'qualquer'];

        $ctl->login($dados);
        $ctl->login($dados);
        $this->assertTrue($ctl->login($dados)['success']);

        // Depois do sucesso, o contador recomeça: 5 falhas ainda passam
        for ($i = 0; $i < LoginCtl::LOGIN_MAX_FALHAS; $i++) {
            $this->assertArrayNotHasKey('bloqueado', $ctl->login($dados));
        }
    }

    public function testBloqueioDeUmIpNaoAfetaOutro(): void
    {
        $model = $this->createMock(LoginModel::class);
        $model->method('login')->willReturn(['success' => false, 'mensagem' => 'x']);

        $ctl = new LoginCtl($model, $this->apelidoMock(), $this->limitador());
        $dados = ['email' => 'user@example.com', 'senha' => 'errada'];

        $_SERVER['REMOTE_ADDR'] = '203.0.113.7';
        for ($i = 0; $i <= LoginCtl::LOGIN_MAX_FALHAS; $i++) {
            $ctl->login($dados);
        }
        $this->assertTrue($ctl->login($dados)['bloqueado']);

        $_SERVER['REMOTE_ADDR'] = '198.51.100.9';
        $this->assertArrayNotHasKey('bloqueado', $ctl->login($dados));
    }
}
