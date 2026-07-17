<?php

require_once __DIR__ . '/../config/core.php';

use App\Config\Auth;
use App\Controller\ReceitaCtl;

// Exige login. O id do usuário vem do token — não do parâmetro da query —
// pra ninguém conseguir listar as receitas de outra pessoa trocando o id na URL.
$usuarioLogado = Auth::requireAuth();

$ctl    = new ReceitaCtl();
$idUser = (int) $usuarioLogado['id'];

$response = $ctl->listarPorUsuario($idUser);

if (isset($response['mensagem'])) {
    http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
