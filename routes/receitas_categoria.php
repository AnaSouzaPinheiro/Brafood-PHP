<?php

require_once __DIR__ . '/../config/core.php';

use App\Controller\ReceitaCtl;

$ctl = new ReceitaCtl();

$id      = isset($_GET['id']) ? intval($_GET['id']) : null;
$ordenar = $_GET['ordenar'] ?? 'nome';

$subcategorias = [];
if (isset($_GET['subcategorias'])) {
    $subcategorias = array_values(is_array($_GET['subcategorias'])
        ? $_GET['subcategorias']
        : explode(',', $_GET['subcategorias']));
}

$dificuldades = [];
if (isset($_GET['dificuldades'])) {
    $raw = is_array($_GET['dificuldades'])
        ? $_GET['dificuldades']
        : explode(',', $_GET['dificuldades']);
    $dificuldades = array_values(array_map('intval', array_filter($raw)));
}

$tiposPorcao = [];
if (isset($_GET['tipos_porcao'])) {
    $tiposPorcao = is_array($_GET['tipos_porcao'])
        ? $_GET['tipos_porcao']
        : explode(',', $_GET['tipos_porcao']);
    $tiposPorcao = array_values(array_filter(array_map('trim', $tiposPorcao)));
}

// tempo_max em minutos totais (ex: 30, 60, ou null = sem limite)
$tempoMax = isset($_GET['tempo_max']) && $_GET['tempo_max'] !== ''
    ? intval($_GET['tempo_max'])
    : null;

$response = $ctl->listarPorCategoria($id, $ordenar, $subcategorias, $dificuldades, $tiposPorcao, $tempoMax);

if (isset($response['mensagem'])) {
    http_response_code(!$id ? 400 : 404);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
