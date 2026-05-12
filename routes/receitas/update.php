<?php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../config/Logger.php';
require_once __DIR__ . '/../../controller/UpdatingRecipeCtl.php';
require_once __DIR__ . '/../../service/UploadService.php';

$db  = (new Database())->getConnection();
$ctl = new UpdatingRecipeCtl($db);

if (!isset($_POST['dados'])) {
    echo json_encode(['success' => false, 'message' => 'Nenhum dado recebido']);
    exit;
}

$dados = json_decode($_POST['dados'], true);

$uploadService = new UploadService(
    __DIR__ . '/../../uploads/receitas/',
    $_ENV['API_UPLOAD_URL'] . '/receitas/'
);

// Fallback: mantém as imagens existentes caso não venha arquivo novo
$fallback = [
    'imagem1' => $dados['imagem1'] ?? null,
    'imagem2' => $dados['imagem2'] ?? null,
    'imagem3' => $dados['imagem3'] ?? null,
];

$imagens = $uploadService->processarImagens($fallback);

try {
    $result = $ctl->editarReceita($dados, $imagens);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
