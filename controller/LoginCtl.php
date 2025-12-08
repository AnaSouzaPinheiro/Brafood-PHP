<?php
require_once '../config/Database.php';
require_once '../model/LoginModel.php';

// Configurações de CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Responde OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Conexão com o banco ---
$database = new Database();
$db = $database->getConnection();
$model = new LoginModel($db);

// Pega dados do front
$data = json_decode(file_get_contents("php://input"), true);

// --- Cadastro ---
if (isset($data['nome'], $data['email'], $data['senha'], $data['confirmarSenha'])) {
    $nome = $data['nome'];
    $email = $data['email'];
    $senha = $data['senha'];
    $confirmarSenha = $data['confirmarSenha'];

    if (!$nome || !$email || !$senha || !$confirmarSenha) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Todos os campos são obrigatórios."]);
        exit;
    }

    if ($senha !== $confirmarSenha) {
        echo json_encode(["success" => false, "errors" => ["As senhas não coincidem."]]);
        exit;
    }

    $result = $model->cadastrar($nome, $email, $senha);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Login ---
if (isset($data['email'], $data['senha'])) {
    $email = $data['email'];
    $senha = $data['senha'];

    if (!$email || !$senha) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Email e senha são obrigatórios."]);
        exit;
    }

    $result = $model->login($email, $senha);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// Se não veio nenhum dado válido
echo json_encode(['success' => false, 'message' => 'Dados inválidos ou incompletos.']);
