<?php

require_once __DIR__ . '/../config/core.php';

use App\Config\Auth;
use App\Controller\UserCtl;

// Retorna dados privados (e-mail, gênero, is_admin): só o próprio usuário
// (ou um admin) pode consultar. Perfis de terceiros: use perfil_publico.php.
$usuarioLogado = Auth::requireAuth();

$ctl = new UserCtl();
$id  = isset($_GET['id']) ? intval($_GET['id']) : (int) $usuarioLogado['id'];

Auth::requireOwnership($usuarioLogado, $id);

$response = $ctl->buscarUsuarioPorId($id);

if (!$response['success']) {
    http_response_code($id <= 0 ? 400 : 404);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
