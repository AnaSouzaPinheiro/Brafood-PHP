<?php

require_once __DIR__ . '/../config/core.php';

use App\Controller\SecaoCarrosselCtl;

// ── Cache em arquivo ──────────────────────────────────────────────────────────
// TTL de 10 minutos. O cache é invalidado automaticamente ao expirar.
// Para forçar rebuild (ex: após alterar seções no admin), DELETE o arquivo
// ou chame este endpoint com ?purge=1 a partir do painel admin.

define('CACHE_TTL',  600); // segundos
define('CACHE_FILE', sys_get_temp_dir() . '/brafood_secoes_cache.json');

// Purge manual pelo admin (apenas requisições locais ou autenticadas)
if (isset($_GET['purge'])) {
    if (file_exists(CACHE_FILE)) {
        unlink(CACHE_FILE);
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
