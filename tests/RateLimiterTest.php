<?php

use PHPUnit\Framework\TestCase;
use App\Service\RateLimiter;

class RateLimiterTest extends TestCase
{
    private string $dir;
    private int $agora = 1000000;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/brafood_rl_' . uniqid();
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dir);
    }

    private function limitador(): RateLimiter
    {
        return new RateLimiter($this->dir, fn() => $this->agora);
    }

    public function testLiberadoAteOLimite(): void
    {
        $rl = $this->limitador();

        for ($i = 0; $i < 4; $i++) {
            $rl->registrar('login', 'a@example.com', 900);
        }

        $this->assertSame(0, $rl->segundosParaLiberar('login', 'a@example.com', 5, 900));
    }

    public function testBloqueiaAoAtingirOLimiteEInformaEspera(): void
    {
        $rl = $this->limitador();

        for ($i = 0; $i < 5; $i++) {
            $rl->registrar('login', 'a@example.com', 900);
        }
        $this->agora += 100;

        $this->assertSame(800, $rl->segundosParaLiberar('login', 'a@example.com', 5, 900));
    }

    public function testLiberaDepoisDaJanela(): void
    {
        $rl = $this->limitador();

        for ($i = 0; $i < 5; $i++) {
            $rl->registrar('login', 'a@example.com', 900);
        }
        $this->agora += 901;

        $this->assertSame(0, $rl->segundosParaLiberar('login', 'a@example.com', 5, 900));
    }

    public function testLimparZeraOContador(): void
    {
        $rl = $this->limitador();

        for ($i = 0; $i < 5; $i++) {
            $rl->registrar('login', 'a@example.com', 900);
        }
        $rl->limpar('login', 'a@example.com');

        $this->assertSame(0, $rl->segundosParaLiberar('login', 'a@example.com', 5, 900));
    }

    public function testChavesEBaldesSaoIndependentes(): void
    {
        $rl = $this->limitador();

        for ($i = 0; $i < 5; $i++) {
            $rl->registrar('login', 'a@example.com', 900);
        }

        $this->assertSame(0, $rl->segundosParaLiberar('login', 'b@example.com', 5, 900));
        $this->assertSame(0, $rl->segundosParaLiberar('recuperacao', 'a@example.com', 5, 900));
    }

    public function testChaveNaoDistingueMaiusculas(): void
    {
        $rl = $this->limitador();

        for ($i = 0; $i < 5; $i++) {
            $rl->registrar('login', 'A@Example.com', 900);
        }

        $this->assertGreaterThan(0, $rl->segundosParaLiberar('login', 'a@example.com', 5, 900));
    }
}
