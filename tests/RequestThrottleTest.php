<?php

use PHPUnit\Framework\TestCase;
use App\Service\RateLimiter;
use App\Service\RequestThrottle;

class RequestThrottleTest extends TestCase
{
    private string $dir;
    private int $agora = 2000000;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/brafood_rt_' . uniqid();
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

    public function testLiberaAteOMaximoEBloqueiaDepois(): void
    {
        $rl = $this->limitador();

        for ($i = 0; $i < 3; $i++) {
            $this->assertSame(0, RequestThrottle::verificar($rl, 'contato', '203.0.113.7', 3, 3600));
        }

        $this->assertGreaterThan(0, RequestThrottle::verificar($rl, 'contato', '203.0.113.7', 3, 3600));
    }

    public function testChamadaBloqueadaNaoRegistraNovaTentativa(): void
    {
        $rl = $this->limitador();

        for ($i = 0; $i < 3; $i++) {
            RequestThrottle::verificar($rl, 'contato', 'ip', 3, 3600);
        }
        $esperaAntes = RequestThrottle::verificar($rl, 'contato', 'ip', 3, 3600);
        $this->agora += 60;
        $esperaDepois = RequestThrottle::verificar($rl, 'contato', 'ip', 3, 3600);

        // A espera só diminui com o tempo: insistir não prolonga o bloqueio
        $this->assertSame($esperaAntes - 60, $esperaDepois);
    }

    public function testLiberaDepoisDaJanela(): void
    {
        $rl = $this->limitador();

        for ($i = 0; $i < 3; $i++) {
            RequestThrottle::verificar($rl, 'newsletter', 'ip', 3, 3600);
        }
        $this->agora += 3601;

        $this->assertSame(0, RequestThrottle::verificar($rl, 'newsletter', 'ip', 3, 3600));
    }

    public function testChavesDiferentesNaoSeAfetam(): void
    {
        $rl = $this->limitador();

        for ($i = 0; $i < 3; $i++) {
            RequestThrottle::verificar($rl, 'ia', '1', 3, 3600);
        }

        $this->assertGreaterThan(0, RequestThrottle::verificar($rl, 'ia', '1', 3, 3600));
        $this->assertSame(0, RequestThrottle::verificar($rl, 'ia', '2', 3, 3600));
    }
}
