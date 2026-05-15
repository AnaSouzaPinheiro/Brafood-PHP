<?php

require_once __DIR__ . '/../config/core.php';

use App\Controller\ReceitaCtl;

$ctl      = new ReceitaCtl();
$pesquisa = $_GET['pesquisa'] ?? '';

$response = $ctl->listarTodas($pesquisa);

echo json_encode($response, JSON_UNESCAPED_UNICODE);
