<?php

require_once __DIR__ . '/../../config/core.php';

use App\Config\Auth;
use App\Controller\ReceitaCtl;
use App\Model\ReceitaModel;

// Garante que nenhum output acidental (warnings, notices) quebre o JSON
ob_start();

header('Content-Type: application/json');

// Exclusão é uma ação destrutiva — não deve viver atrás de um GET (cacheável,
// pré-carregável por proxy/navegador). Exige o verbo certo.
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'mensagem' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$usuarioLogado = Auth::requireAuth();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $dono = (new ReceitaModel())->donoDaReceita($id);

    if ($dono === null) {
        ob_end_clean();
        http_response_code(404);
        echo json_encode(['mensagem' => 'Receita não encontrada.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Só o dono da receita (ou um admin) pode excluir.
    Auth::requireOwnership($usuarioLogado, $dono);
}

$ctl = new ReceitaCtl();

$response = $ctl->excluirReceita($id);

if (isset($response['mensagem'])) {
    http_response_code($id <= 0 ? 400 : 404);
}

// Descarta qualquer output acidental antes de enviar o JSON
ob_end_clean();

echo json_encode($response, JSON_UNESCAPED_UNICODE);
