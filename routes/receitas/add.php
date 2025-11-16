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
require_once __DIR__ . '/../../controller/AddingReceitaCtl.php';

// Cria a conexão
$db = (new Database())->getConnection();

// Inicializa o Controller
$ctl = new AddingReceitaCtl($db);

// --- Recebe os dados ---
if (!isset($_POST['dados'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum dado recebido']);
    exit;
}

$dados = json_decode($_POST['dados'], true);

// --- Upload das imagens ---
$imagens = [];
// caminho físico correto (raiz do projeto + pasta uploads)
$uploadDir = __DIR__ . '/../../uploads/receitas/';
// URL pública correspondente
$baseUrl = "http://localhost/BraFood-Angular-PHP/BraFood-PHP/uploads/receitas/";

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

foreach (['imagem1', 'imagem2', 'imagem3'] as $campo) {
    if (!empty($_FILES[$campo]['tmp_name'])) {
        $ext = pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION);
        $novoNome = uniqid("receita_") . "." . $ext;
        $destino = $uploadDir . $novoNome;

        if (move_uploaded_file($_FILES[$campo]['tmp_name'], $destino)) {
            // caminho público acessível pelo Angular
            $imagens[$campo] = $baseUrl . $novoNome;
        } else {
            $imagens[$campo] = null;
        }
    } else {
        $imagens[$campo] = null;
    }
}


// --- Salva usando o Controller ---
try {
    $result = $ctl->adicionarReceita($dados, $imagens);

    // Debug seguro: escreve no log do PHP sem quebrar o JSON
    error_log("Resultado da criação de receita: " . print_r($result, true));

    echo json_encode($result); // JSON puro para o Angular
} catch (Exception $e) {
    // Debug seguro da exceção
    error_log("Erro ao criar receita: " . $e->getMessage());

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
