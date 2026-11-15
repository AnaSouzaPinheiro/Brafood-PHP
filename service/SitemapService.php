<?php

namespace App\Service;

/**
 * Monta o sitemap.xml a partir das receitas e categorias do banco.
 * Só inclui páginas públicas (as que exigem login ficam de fora).
 */
class SitemapService
{
    private const PAGINAS_FIXAS = [
        ['/home', 'daily', '1.0'],
        ['/termos', 'yearly', '0.3'],
        ['/privacidade', 'yearly', '0.3'],
        ['/cookies', 'yearly', '0.3'],
        ['/contato', 'yearly', '0.3'],
    ];

    /**
     * @param string $urlSite   endereço público do site, sem barra final
     * @param array  $receitas  linhas com 'id', 'titulo' e, opcionalmente, 'data_criacao'
     * @param array  $categorias linhas com 'id' e 'nome'
     */
    public static function gerar(string $urlSite, array $receitas, array $categorias): string
    {
        $urlSite = rtrim($urlSite, '/');
        $itens = [];

        foreach (self::PAGINAS_FIXAS as [$caminho, $freq, $prioridade]) {
            $itens[] = self::item($urlSite . $caminho, $freq, $prioridade);
        }

        foreach ($categorias as $c) {
            $itens[] = self::item(
                $urlSite . '/categoria/' . Slug::gerar((string) $c['nome']) . '/' . (int) $c['id'],
                'weekly',
                '0.6'
            );
        }

        foreach ($receitas as $r) {
            $itens[] = self::item(
                $urlSite . '/receita/' . Slug::gerar((string) $r['titulo']) . '/' . (int) $r['id'],
                'weekly',
                '0.8',
                self::data($r['data_criacao'] ?? null)
            );
        }

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
            . implode("\n", $itens)
            . "\n</urlset>\n";
    }

    private static function item(string $loc, string $freq, string $prioridade, ?string $lastmod = null): string
    {
        $xml = "  <url>\n    <loc>" . htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
        if ($lastmod) {
            $xml .= "    <lastmod>$lastmod</lastmod>\n";
        }

        return $xml . "    <changefreq>$freq</changefreq>\n    <priority>$prioridade</priority>\n  </url>";
    }

    private static function data(?string $valor): ?string
    {
        if (!$valor || !preg_match('/^(\d{4}-\d{2}-\d{2})/', $valor, $m)) {
            return null;
        }

        return $m[1];
    }
}
