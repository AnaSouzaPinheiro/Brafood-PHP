<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// --- Conexão PDO ---
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../controller/UpdatingRecipeCtl.php';

// Cria a conexão
$db = (new Database())->getConnection();

// Inicializa o Controller
$ctl = new UpdatingRecipeCtl($db);

// --- Recebe os dados ---
if (!isset($_POST['dados'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum dado recebido']);
    exit;
}

$dados = json_decode($_POST['dados'], true);

// --- Upload das imagens ---
$imagens = [];
$uploadDir = __DIR__ . '/../../uploads/receitas/';
$baseUrl = "http://localhost:8080/BraFood-Angular-PHP/BraFood-PHP/uploads/receitas/";

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// percorre imagem1, imagem2 e imagem3
foreach (['imagem1', 'imagem2', 'imagem3'] as $campo) {

    // se veio arquivo novo → substitui
    if (!empty($_FILES[$campo]['tmp_name'])) {
        $ext = pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION);
        $novoNome = uniqid("receita_") . "." . $ext;
        $destino = $uploadDir . $novoNome;

        if (move_uploaded_file($_FILES[$campo]['tmp_name'], $destino)) {
            $imagens[$campo] = $baseUrl . $novoNome;
        } else {
            $imagens[$campo] = null;
        }

    // se NÃO veio arquivo novo → mantém a imagem antiga
    } else {
        $imagens[$campo] = $dados[$campo] ?? null;
    }
}


// --- Chama o método de editar ---
try {
    $result = $ctl->editarReceita($dados, $imagens);

    error_log("Resultado da edição de receita: " . print_r($result, true));
    echo json_encode($result);

} catch (Exception $e) {

    error_log("Erro ao editar receita: " . $e->getMessage());

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
