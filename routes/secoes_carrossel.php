<?php

require_once __DIR__ . '/../config/core.php';

use App\Config\Auth;
use App\Controller\SecaoCarrosselCtl;

// ── Cache em arquivo ──────────────────────────────────────────────────────────
// A home roda com semente diária, então o conteúdo é o mesmo o dia todo:
// um arquivo por dia, válido por 6h (para refletir novas receitas/favoritos).
// Alterações no painel admin apagam o cache (AdminSecaoModel::purgeCache).

define('CACHE_TTL',  6 * 3600); // segundos
define('CACHE_FILE', cacheDir() . '/brafood_secoes_cache_' . date('Ymd') . '.json');

if (isset($_GET['purge'])) {
    Auth::requireAdmin();
    foreach (glob(cacheDir() . '/brafood_secoes_cache*.json') ?: [] as $f) {
        @unlink($f);
    }
    echo json_encode(['purged' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

// Servir do cache se existir e não tiver expirado
if (file_exists(CACHE_FILE) && (time() - filemtime(CACHE_FILE)) < CACHE_TTL) {
    header('X-Cache: HIT');
    header('Content-Type: application/json; charset=utf-8');
    readfile(CACHE_FILE);
    exit;
}

// Cache expirado ou inexistente — gera o conteúdo
header('X-Cache: MISS');

$ctl    = new SecaoCarrosselCtl();
$secoes = $ctl->index();
$json   = json_encode($secoes, JSON_UNESCAPED_UNICODE);

// Salva no cache (falha silenciosa se não tiver permissão de escrita)
@file_put_contents(CACHE_FILE, $json, LOCK_EX);

echo $json;
