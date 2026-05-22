<?php

namespace App\Controller;

use App\Config\Logger;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailCtl
{

    public function __construct()
    {
    }
    public function enviarContato(array $data): array
    {
        if (empty($data["email"])) {
            return [
                "success" => false,
                "message" => "E-mail é obrigatório."
            ];
        }

        $mensagem = htmlspecialchars($data['mensagem']);
        $nome = htmlspecialchars($data['nome']);
        $assunto = htmlspecialchars($data['assunto']);
        $email = htmlspecialchars($data['email']);

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = "smtp.gmail.com";
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USER'];
            $mail->Password = $_ENV['MAIL_PASS'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet = 'UTF-8';
            $mail->Port = 587;

            $mail->setFrom($_ENV['MAIL_USER'], "BraFood");
            $mail->addAddress($_ENV['MAIL_USER']);
            $mail->addReplyTo($email, $nome);

            $mail->isHTML(true);

            $mail->Subject = "Contato enviado pelo site — BraFood";

            $mail->Body = "
                <h2>Novo contato via site</h2>
                <p><strong>Nome:</strong> {$nome}</p>
                <p><strong>E-mail:</strong> {$email}</p>
                <p><strong>Assunto:</strong> {$assunto}</p>
                <p><strong>Mensagem:</strong><br>{$mensagem}</p>
            ";

            $mail->send();

            Logger::info(
                "email_contato",
                "E-mail enviado!",
                ["email" => $data["email"]]
            );

            return [
                "success" => true,
                "message" => "E-mail enviado!"
            ];
        } catch (Exception $e) {

            Logger::error(
                "email_contato",
                "Falha ao enviar e-mail.",
                [
                    "email" => $data["email"],
                    "erro" => $e->getMessage()
                ]
            );

            return [
                "success" => false,
                "message" => "Erro ao enviar e-mail."
            ];
        }
    }
}
