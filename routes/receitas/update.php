<?php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../config/Logger.php';
require_once __DIR__ . '/../../controller/UpdatingRecipeCtl.php';

$db  = (new Database())->getConnection();
$ctl = new UpdatingRecipeCtl($db);

if (!isset($_POST['dados'])) {
    echo json_encode(['success' => false, 'message' => 'Nenhum dado recebido']);
    exit;
}

$dados     = json_decode($_POST['dados'], true);
$imagens   = [];
$uploadDir = __DIR__ . '/../../uploads/receitas/';
$baseUrl   = $_ENV['API_UPLOAD_URL'] . '/receitas/';

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

foreach (['imagem1', 'imagem2', 'imagem3'] as $campo) {
    if (!empty($_FILES[$campo]['tmp_name'])) {
        $ext      = pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION);
        $novoNome = uniqid('receita_') . '.' . $ext;
        $destino  = $uploadDir . $novoNome;

        $imagens[$campo] = move_uploaded_file($_FILES[$campo]['tmp_name'], $destino)
            ? $baseUrl . $novoNome
            : null;
    } else {
        // Mantém imagem existente se não veio arquivo novo
        $imagens[$campo] = $dados[$campo] ?? null;
    }
}

try {
    $result = $ctl->editarReceita($dados, $imagens);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'success'  => false,
        'message' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
