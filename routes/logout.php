<?php

require_once __DIR__ . '/../config/core.php';

use App\Config\Auth;
use App\Model\LoginModel;

header('Content-Type: application/json; charset=UTF-8');

// Exige o token atual só pra garantir que quem está deslogando é quem
// de fato estava logado — se o token já for inválido, não tem o que limpar.
$user = Auth::usuarioAutenticado();

if ($user) {
    $headerToken = trim(substr(getRequestHeader('Authorization'), 7));
    (new LoginModel())->logout($headerToken);
}

echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
