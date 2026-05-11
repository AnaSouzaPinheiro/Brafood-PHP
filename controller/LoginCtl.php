<?php

/**
 * Controla autenticação: login e cadastro de usuários.
 * Depende de LoginModel para acesso ao banco.
 */

require_once __DIR__ . '/../model/LoginModel.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class LoginCtl
{
    private LoginModel $model;

    public function __construct($db)
    {
        $this->model = new LoginModel($db);
    }

    public function login(array $data): array
    {
        if (empty($data['email']) || empty($data['senha'])) {
            Logger::warn('login', 'Tentativa de login com dados incompletos', [
                'email' => $data['email'] ?? '(vazio)'
            ]);
            return ['success' => false, 'message' => 'E-mail e senha são obrigatórios.'];
        }

        $result = $this->model->login($data['email'], $data['senha']);

        if ($result['success']) {
            Logger::info('login', 'Login realizado com success', [
                'usuario_id' => $result['usuario']['id'] ?? '?',
                'email'      => $data['email']
            ]);
        } else {
            Logger::warn('login', 'Falha no login', [
                'email'  => $data['email'],
                'motivo' => $result['message'] ?? 'desconhecido'
            ]);
        }

        return $result;
    }

    public function cadastro(array $data): array
    {
        if (empty($data['email']) || empty($data['senha'])) {
            Logger::warn('login/cadastro', 'Tentativa de cadastro com dados incompletos');
            return ['success' => false, 'message' => 'Dados obrigatórios ausentes.'];
        }

        $result = $this->model->cadastrar(
            $data['nome']           ?? '',
            $data['email'],
            $data['senha']
        );

        if ($result['success'] ?? false) {
            Logger::info('login/cadastro', 'Novo usuário cadastrado', [
                'email' => $data['email']
            ]);
        } else {
            Logger::warn('login/cadastro', 'Falha no cadastro', [
                'email'  => $data['email'],
                'motivo' => $result['message'] ?? 'desconhecido'
            ]);
        }

        return $result;
    }

    public function resetarSenha(array $data): array
    {
        if (
            empty($data['token']) ||
            empty($data['senha']) ||
            empty($data['confirmarSenha'])
        ) {
            http_response_code(400);

            return [
                'success' => false,
                'message' => 'Dados incompletos.'
            ];
        }

        return $this->model->resetarSenha(
            $data['token'],
            $data['senha'],
            $data['confirmarSenha']
        );
    }

    public function enviarRecuperacaoSenha(array $data): array
    {
        if (empty($data["email"])) {
            http_response_code(400);

            return [
                "success" => false,
                "message" => "E-mail é obrigatório."
            ];
        }

        $result = $this->model->gerarTokenRecuperacao(
            $data["email"]
        );

        if (!($result["success"] ?? false)) {
            return $result;
        }

        $token  = $result["token"];

        $appUrl = rtrim(
            $_ENV['APP_URL'] ?? 'http://localhost:4200',
            '/'
        );

        $link = "$appUrl/resetar-senha/$token";

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = "smtp.gmail.com";
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USER'];
            $mail->Password   = $_ENV['MAIL_PASS'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet    = 'UTF-8';
            $mail->Port       = 587;

            $mail->setFrom($_ENV['MAIL_USER'], "BraFood");
            $mail->addAddress($data["email"]);

            $mail->isHTML(true);

            $mail->Subject = "Redefinição de senha — BraFood";

            $mail->Body = "
                <h2>Redefinição de senha</h2>
                <p>Clique no link abaixo para redefinir sua senha:</p>
                <a href='$link'>$link</a>
                <p>Link válido por 1 hora.</p>
            ";

            $mail->send();

            Logger::info(
                "recuperacao_senha",
                "E-mail de recuperação enviado",
                ["email" => $data["email"]]
            );

            return [
                "success" => true,
                "message" => "E-mail enviado!"
            ];

        } catch (Exception $e) {

            Logger::error(
                "recuperacao_senha",
                "Falha ao enviar e-mail de recuperação",
                [
                    "email" => $data["email"],
                    "erro"  => $e->getMessage()
                ]
            );

            return [
                "success" => false,
                "message" => "Erro ao enviar e-mail."
            ];
        }
    }
}
