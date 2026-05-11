<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Logger.php';
require_once __DIR__ . '/../controller/UserCtl.php';

$db  = (new Database())->getConnection();
$ctl = new UserCtl($db);

if (!isset($_POST['dados'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nenhum dado recebido']);
    exit;
}

$dados = json_decode($_POST['dados'], true);
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$uploadUrl = rtrim($_ENV['API_UPLOAD_URL'] ?? "$protocol://{$_SERVER['HTTP_HOST']}/uploads", '/');
$uploadDir = dirname(__DIR__) . '/uploads/users/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$imagens = [];

foreach (['foto_perfil'] as $campo) {
    if (!empty($_FILES[$campo]['tmp_name'])) {
        $ext      = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
        $novoNome = uniqid("usuario_") . "." . $ext;
        $destino  = $uploadDir . $novoNome;

        if (move_uploaded_file($_FILES[$campo]['tmp_name'], $destino)) {
            $imagens[$campo] = "$uploadUrl/users/$novoNome";
        } else {
            $imagens[$campo] = null;
        }
    } else {
        $imagens[$campo] = $dados[$campo] ?? null;
    }
}

try {
    $dados['foto_perfil'] = $imagens['foto_perfil'] ?? $dados['foto_perfil'] ?? null;

    $result = $ctl->editarUsuario($dados);

    ob_clean();
    echo json_encode($result);
    exit;
} catch (Exception $e) {
    Logger::error("usuario/update", "Erro inesperado ao editar usuário", ["erro" => $e->getMessage()]);
    ob_clean();
    echo json_encode([
        'success'  => false,
        'message' => 'Erro no servidor.'
    ]);
    exit;
}
