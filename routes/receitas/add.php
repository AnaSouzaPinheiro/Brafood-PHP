<?php

require_once __DIR__ . '/../../config/core.php';

use App\Config\Auth;
use App\Controller\AddingRecipeCtl;
use App\Service\UploadService;

$usuarioLogado = Auth::requireAuth();

$ctl = new AddingRecipeCtl();

if (!isset($_POST['dados'])) {
    echo json_encode(['success' => false, 'mensagem' => 'Nenhum dado recebido']);
    exit;
}

$dados = json_decode($_POST['dados'], true);

// O autor é sempre o usuário autenticado — nunca o que vier no payload,
// senão dá pra criar receita em nome de qualquer um.
$dados['autor'] = $usuarioLogado['id'];

$uploadService = new UploadService(
    __DIR__ . '/../../uploads/receitas/',
    'uploads/receitas',
    1600, // largura máx.: slide da home e página da receita (cards usam miniatura)
    1600,
    false,
    85
);

// Sem fallback: é um cadastro novo, não há imagens anteriores
$imagem1 = $uploadService->processarUmaImagem($_FILES['imagem1'] ?? []);
$galeria = $uploadService->processarGaleria($_FILES['galeria'] ?? []);

try {
    $result = $ctl->adicionarReceita($dados, $imagem1, $galeria);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensagem' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
