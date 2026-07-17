<?php

require_once __DIR__ . '/../../config/core.php';

use App\Config\Auth;
use App\Controller\UpdatingRecipeCtl;
use App\Model\ReceitaModel;
use App\Service\UploadService;
use App\Service\ImageCleanupService;

$usuarioLogado = Auth::requireAuth();

$ctl = new UpdatingRecipeCtl();

if (!isset($_POST['dados'])) {
    echo json_encode(['success' => false, 'mensagem' => 'Nenhum dado recebido']);
    exit;
}

$dados = json_decode($_POST['dados'], true);

// ── Busca imagens atuais antes de qualquer alteração ────────────────────────
$receitaModel = new ReceitaModel();
$receitaAtual = $receitaModel->buscarPorId((int) $dados['id']);

if (!$receitaAtual) {
    http_response_code(404);
    echo json_encode(['success' => false, 'mensagem' => 'Receita não encontrada.']);
    exit;
}

// Só o dono da receita (ou um admin) pode editar. E o id_usuario nunca vem
// do payload — sempre do usuário autenticado, senão dá pra "roubar" a
// receita de outra pessoa mandando um id_usuario diferente no corpo.
Auth::requireOwnership($usuarioLogado, (int) $receitaAtual['id_usuario']);
$dados['id_usuario'] = $receitaAtual['id_usuario'];

$imagensAntigas = array_merge(
    [$receitaAtual['imagem1'] ?? null],
    $receitaAtual['imagens'] ?? []
);

// ── Upload das imagens novas ─────────────────────────────────────────────────
$uploadService = new UploadService(
    __DIR__ . '/../../uploads/receitas/',
    'uploads/receitas'
);

// Capa: se não veio arquivo novo, mantém a capa atual do banco
$imagem1 = $uploadService->processarUmaImagem(
    $_FILES['imagem1'] ?? [],
    $receitaAtual['imagem1'] ?? null
);

// Galeria: o front manda em `dados.galeriaAtual` os caminhos das imagens
// existentes que o usuário optou por manter (as que ele removeu na tela
// simplesmente não aparecem aqui). Os arquivos novos vêm em $_FILES['galeria'][].
$galeriaMantida = $dados['galeriaAtual'] ?? [];
$galeriaNova    = $uploadService->processarGaleria($_FILES['galeria'] ?? []);
$galeriaFinal   = array_merge($galeriaMantida, $galeriaNova);

try {
    $result = $ctl->editarReceita($dados, $imagem1, $galeriaFinal);

    // ── Apaga do disco apenas as imagens que foram substituídas/removidas ────
    if ($result['success'] ?? false) {
        $imagensNovas = array_merge([$imagem1], $galeriaFinal);
        ImageCleanupService::apagarImagensAntigas(
            $imagensAntigas,
            $imagensNovas,
            __DIR__ . '/../../'
        );
    }

    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensagem' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
