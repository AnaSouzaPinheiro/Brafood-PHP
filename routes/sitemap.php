<?php

/**
 * sitemap.xml do site. O front o serve em /sitemap.xml por uma regra do
 * .htaccess (não há servidor Node na hospedagem para gerá-lo).
 * O resultado fica em cache de arquivo por 1 hora.
 */

require_once __DIR__ . '/../config/core.php';

use App\Config\Database;
use App\Config\Logger;
use App\Service\SitemapService;

const SITEMAP_TTL = 3600;

$arquivo = cacheDir() . '/brafood_sitemap.xml';

header('Content-Type: application/xml; charset=UTF-8');

if (is_file($arquivo) && (time() - filemtime($arquivo)) < SITEMAP_TTL) {
    header('X-Cache: HIT');
    readfile($arquivo);
    exit;
}

try {
    $conn = Database::getInstance()->getConnection();

    $receitas   = $conn->query('SELECT id, titulo, data_criacao FROM receitas ORDER BY id')->fetchAll();
    $categorias = $conn->query('SELECT id, nome FROM categorias ORDER BY ordem, id')->fetchAll();

    $xml = SitemapService::gerar($_ENV['APP_URL'] ?? 'http://localhost:4200', $receitas, $categorias);

    @file_put_contents($arquivo, $xml, LOCK_EX);

    header('X-Cache: MISS');
    echo $xml;
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['mensagem' => Logger::falha('sitemap', $e)], JSON_UNESCAPED_UNICODE);
}
