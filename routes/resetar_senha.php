<?php
require_once '../config/Database.php';
require_once '../model/LoginModel.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit;

$database = new Database();
$db = $database->getConnection();
$model = new LoginModel($db);

$data = json_decode(file_get_contents("php://input"), true);

if (
    !isset($data['token']) ||
    !isset($data['senha']) ||
    !isset($data['confirmarSenha'])
) {
    echo json_encode(["success" => false, "message" => "Dados incompletos."]);
    exit;
}

$result = $model->resetarSenha(
    $data['token'],
    $data['senha'],
    $data['confirmarSenha']
);

echo json_encode($result, JSON_UNESCAPED_UNICODE);
