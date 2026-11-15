<?php

use PHPUnit\Framework\TestCase;
use App\Service\ClientIp;

class ClientIpTest extends TestCase
{
    public function testUsaRemoteAddrQuandoEPublico(): void
    {
        $ip = ClientIp::obter(['REMOTE_ADDR' => '203.0.113.7']);

        $this->assertSame('203.0.113.7', $ip);
    }

    public function testIgnoraXForwardedForQuandoRemoteAddrEPublico(): void
    {
        // Cliente tentando forjar o IP: o cabeçalho não pode valer
        $ip = ClientIp::obter([
            'REMOTE_ADDR'          => '203.0.113.7',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.99',
        ]);

        $this->assertSame('203.0.113.7', $ip);
    }

    public function testLeXForwardedForQuandoRemoteAddrEInterno(): void
    {
        $r = ClientIp::resolver([
            'REMOTE_ADDR'          => '10.0.0.5',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.99',
        ]);

        $this->assertSame('198.51.100.99', $r['ip']);
        $this->assertSame('X-Forwarded-For', $r['origem']);
    }

    public function testUsaAUltimaEntradaDoXForwardedFor(): void
    {
        // A 1ª entrada pode ter sido escrita pelo cliente; a última, pelo proxy
        $ip = ClientIp::obter([
            'REMOTE_ADDR'          => '172.16.0.2',
            'HTTP_X_FORWARDED_FOR' => '1.2.3.4, 198.51.100.99',
        ]);

        $this->assertSame('198.51.100.99', $ip);
    }

    public function testUsaXRealIpQuandoNaoHaXForwardedFor(): void
    {
        $r = ClientIp::resolver([
            'REMOTE_ADDR'    => '192.168.1.10',
            'HTTP_X_REAL_IP' => '198.51.100.50',
        ]);

        $this->assertSame('198.51.100.50', $r['ip']);
        $this->assertSame('X-Real-IP', $r['origem']);
    }

    public function testIgnoraCabecalhoComIpInvalidoOuInterno(): void
    {
        $r = ClientIp::resolver([
            'REMOTE_ADDR'          => '10.0.0.5',
            'HTTP_X_FORWARDED_FOR' => 'lixo, 10.1.1.1',
        ]);

        $this->assertSame('10.0.0.5', $r['ip']);
        $this->assertSame('REMOTE_ADDR', $r['origem']);
    }

    public function testNaoEConfiavelQuandoSoHaIpInterno(): void
    {
        $this->assertFalse(ClientIp::confiavel(['REMOTE_ADDR' => '10.0.0.5']));
        $this->assertFalse(ClientIp::confiavel(['REMOTE_ADDR' => '127.0.0.1']));
        $this->assertFalse(ClientIp::confiavel([]));
    }

    public function testEConfiavelComIpPublico(): void
    {
        $this->assertTrue(ClientIp::confiavel(['REMOTE_ADDR' => '203.0.113.7']));
        $this->assertTrue(ClientIp::confiavel([
            'REMOTE_ADDR'          => '10.0.0.5',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.99',
        ]));
    }
}
