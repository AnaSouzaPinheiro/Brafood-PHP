<?php

require_once __DIR__ . '/../../config/core.php';

use App\Controller\AddingRecipeCtl;
use App\Service\UploadService;

$ctl = new AddingRecipeCtl();

if (!isset($_POST['dados'])) {
    echo json_encode(['success' => false, 'message' => 'Nenhum dado recebido']);
    exit;
}

$dados = json_decode($_POST['dados'], true);

$uploadService = new UploadService(
    __DIR__ . '/../../uploads/receitas/',
    'uploads/receitas'
);

// Sem fallback: é um cadastro novo, não há imagens anteriores
$imagens = $uploadService->processarImagens();

try {
    $result = $ctl->adicionarReceita($dados, $imagens);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
