<?php
require_once '../config/Database.php';
require_once '../model/LoginModel.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit;

// Conexão
$db = (new Database())->getConnection();
$model = new LoginModel($db);

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data["email"])) {
    echo json_encode(["success" => false, "message" => "E-mail é obrigatório."]);
    exit;
}

$email = $data["email"];

// Gera token
$result = $model->gerarTokenRecuperacao($email);

if (!$result["success"]) {
    echo json_encode($result);
    exit;
}

$token = $result["token"];
$link = "http://localhost:4200/resetar-senha/$token";

// Enviar email
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->Username = "anapinheirole@gmail.com";
    $mail->Password = "ynrd mrho qyrl wvcv";
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->CharSet = 'UTF-8';
    $mail->Port = 587;

    $mail->setFrom("anapinheirole@gmail.com", "BraFood");
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = "Redefinição de senha";
    $mail->Body = "
        <h2>Redefinição de senha</h2>
        <p>Clique no link abaixo para redefinir sua senha:</p>
        <a href='$link'>$link</a>
        <p>Link válido por 1 hora.</p>
    ";

    $mail->send();

    echo json_encode(["success" => true, "message" => "E-mail enviado!"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Erro ao enviar e-mail."]);
}
