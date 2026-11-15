<?php

use PHPUnit\Framework\TestCase;
use App\Service\Slug;
use App\Service\SitemapService;

class SitemapTest extends TestCase
{
    public function testSlugRemoveAcentosEPontuacao(): void
    {
        $this->assertSame('frango-agridoce-chines', Slug::gerar('Frango agridoce chinês'));
        $this->assertSame('cafe-da-manha-lanches', Slug::gerar('Café da Manhã & Lanches'));
        $this->assertSame('saladas-acompanhamentos', Slug::gerar('Saladas & Acompanhamentos'));
        $this->assertSame('pao-de-queijo', Slug::gerar('  Pão   de Queijo  '));
    }

    public function testSitemapListaPaginasFixasCategoriasEReceitas(): void
    {
        $xml = SitemapService::gerar(
            'https://exemplo.test/',
            [['id' => 7, 'titulo' => 'Bolo de Fubá', 'data_criacao' => '2026-05-21 03:56:43']],
            [['id' => 3, 'nome' => 'Comemorativas']]
        );

        $this->assertStringContainsString('<loc>https://exemplo.test/home</loc>', $xml);
        $this->assertStringContainsString('<loc>https://exemplo.test/categoria/comemorativas/3</loc>', $xml);
        $this->assertStringContainsString('<loc>https://exemplo.test/receita/bolo-de-fuba/7</loc>', $xml);
        $this->assertStringContainsString('<lastmod>2026-05-21</lastmod>', $xml);
    }

    public function testSitemapEXmlValidoEEscapaCaracteres(): void
    {
        $xml = SitemapService::gerar('https://exemplo.test', [], []);
        $doc = new DOMDocument();

        $this->assertTrue($doc->loadXML($xml));
        $this->assertSame('urlset', $doc->documentElement->nodeName);
    }

    public function testSitemapNaoIncluiPaginasQueExigemLogin(): void
    {
        $xml = SitemapService::gerar('https://exemplo.test', [], []);

        $this->assertStringNotContainsString('/admin', $xml);
        $this->assertStringNotContainsString('/perfil', $xml);
        $this->assertStringNotContainsString('/adicionar-receita', $xml);
    }
}
