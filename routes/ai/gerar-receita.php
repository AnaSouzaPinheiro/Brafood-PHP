<?php

require_once __DIR__ . '/../../config/core.php';

use App\Config\Auth;
use App\Service\AiService;
use App\Service\RequestThrottle;

$usuario = Auth::requireAuth();

// No máximo 10 gerações por hora por usuário
RequestThrottle::porChave('ia', (string) $usuario['id'], 10, 3600);

$input = json_decode(file_get_contents("php://input"), true);

$texto = $input['texto'] ?? '';

if (!$texto) {
    echo json_encode(["success" => false]);
    exit;
}

$ai = new AiService();
$resposta = $ai->parseRecipe($texto);

echo json_encode($resposta);