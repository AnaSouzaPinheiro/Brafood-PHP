<?php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../config/Logger.php';
require_once __DIR__ . '/../../controller/AddingRecipeCtl.php';
require_once __DIR__ . '/../../service/UploadService.php';

$db  = (new Database())->getConnection();
$ctl = new AddingRecipeCtl($db);

if (!isset($_POST['dados'])) {
    echo json_encode(['success' => false, 'message' => 'Nenhum dado recebido']);
    exit;
}

$dados = json_decode($_POST['dados'], true);

$uploadService = new UploadService(
    __DIR__ . '/../../uploads/receitas/',
    $_ENV['API_UPLOAD_URL'] . '/receitas/'
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
