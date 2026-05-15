<?php

require_once __DIR__ . '/../config/core.php';

use App\Service\AiService;

$input = json_decode(file_get_contents("php://input"), true);

$texto = $input['texto'] ?? '';

if (!$texto) {
    echo json_encode(["success" => false]);
    exit;
}

$ai = new AiService();
$resposta = $ai->parseRecipe($texto);

echo json_encode($resposta);