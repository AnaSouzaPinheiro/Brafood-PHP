<?php

require_once __DIR__ . '/../../config/core.php';

use App\Config\Auth;
use App\Controller\AdminSecaoCtl;
use App\Service\UploadService;

header('Content-Type: application/json; charset=UTF-8');

// Verificar admin (token Bearer + is_admin)
Auth::requireAdmin();

// Contexto
$contexto = $_GET['contexto'] ?? 'geral';

$permitidos = [
    'categorias',
    'icone', 
    'secoes'
];

if (!in_array($contexto, $permitidos, true)) {

    http_response_code(400);

    echo json_encode([
        'mensagem' => 'Contexto inválido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Pastas
$uploadDir = __DIR__ . '/../../uploads/' . $contexto . '/';

$relativePath = 'api/uploads/' . $contexto;
$crop = false;

// Configurações por contexto
switch ($contexto) {

    case 'categorias':

        $maxLargura = 1920;
        $maxAltura  = 220;

        break;

    case 'icone':

        $maxLargura = 128;
        $maxAltura  = 128;

        $forcarPng = true;

        break;

    case 'secoes':

        $maxLargura = 1920;
        // Sem corte: mantém a proporção original. Quem enquadra é o
        // background-size: cover na tela; cortar aqui cortava duas vezes.
        $maxAltura  = 1920;

        break;

    default:

        $maxLargura = 1200;
        $maxAltura  = 1200;

        break;
}

// Instância do upload
$service = new UploadService(
    $uploadDir,
    $relativePath,
    $maxLargura,
    $maxAltura,
    $crop,
    82,
    7,
    $forcarPng ?? false
);

// Upload
$caminho = $service->processarUmaImagem(
    $_FILES['imagem'] ?? []
);

// Resposta
if ($caminho) {

    echo json_encode([
        'caminho' => $caminho
    ], JSON_UNESCAPED_UNICODE);

} else {

    http_response_code(422);

    echo json_encode([
        'mensagem' => 'Falha no upload.'
    ], JSON_UNESCAPED_UNICODE);

}