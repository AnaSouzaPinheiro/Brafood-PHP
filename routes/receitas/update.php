<?php

require_once __DIR__ . '/../../config/core.php';

use App\Controller\UpdatingRecipeCtl;
use App\Model\ReceitaModel;
use App\Service\UploadService;
use App\Service\ImageCleanupService;

$ctl = new UpdatingRecipeCtl();

if (!isset($_POST['dados'])) {
    echo json_encode(['success' => false, 'message' => 'Nenhum dado recebido']);
    exit;
}

$dados = json_decode($_POST['dados'], true);

// ── Busca imagens atuais antes de qualquer alteração ────────────────────────
$receitaModel = new ReceitaModel();
$receitaAtual = $receitaModel->buscarPorId((int) $dados['id']);

$imagensAntigas = [
    $receitaAtual['imagem1'] ?? null,
    $receitaAtual['imagem2'] ?? null,
    $receitaAtual['imagem3'] ?? null,
];

// ── Upload das imagens novas (ou mantém as existentes como fallback) ─────────
$uploadService = new UploadService(
    __DIR__ . '/../../uploads/receitas/',
    'uploads/receitas'
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

    // ── Apaga do disco apenas as imagens que foram substituídas ──────────────
    if ($result['success'] ?? false) {
        ImageCleanupService::apagarImagensAntigas(
            $imagensAntigas,
            array_values($imagens),
            __DIR__ . '/../../'
        );
    }

    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
