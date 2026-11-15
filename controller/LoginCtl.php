<?php

/**
 * Controla autenticação: login e cadastro de usuários.
 * Depende de LoginModel para acesso ao banco.
 */

namespace App\Controller;

use App\Model\LoginModel;
use App\Config\Logger;
use App\Service\ApelidoService;
use App\Service\RateLimiter;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class LoginCtl
{
    private LoginModel $model;

    private ?ApelidoService $apelidoService;

    private RateLimiter $limitador;

    /** Falhas de login permitidas por e-mail + IP dentro da janela */
    public const LOGIN_MAX_FALHAS = 5;
    public const LOGIN_JANELA = 900; // 15 min

    /** Pedidos de recuperação de senha permitidos por e-mail dentro da janela */
    public const RECUPERACAO_MAX = 3;
    public const RECUPERACAO_JANELA = 900;

    public function __construct(
        ?LoginModel $model = null,
        ?ApelidoService $apelidoService = null,
        ?RateLimiter $limitador = null
    ) {
        $this->model = $model ?? new LoginModel();
        $this->apelidoService = $apelidoService;
        $this->limitador = $limitador ?? new RateLimiter();
    }

    /**
     * IP de quem chamou. Só confia em REMOTE_ADDR: headers como
     * X-Forwarded-For podem ser forjados pelo cliente.
     */
    private function ipCliente(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'desconhecido');
    }

    public function login(array $data): array
    {
        if (empty($data['email']) || empty($data['senha'])) {
            Logger::warn('login', 'Tentativa de login com dados incompletos', [
                'email' => $data['email'] ?? '(vazio)'
            ]);
            return ['success' => false, 'message' => 'E-mail e senha são obrigatórios.'];
        }

        // Limite de tentativas por e-mail + IP (protege contra força bruta)
        $chave = $data['email'] . '|' . $this->ipCliente();
        $espera = $this->limitador->segundosParaLiberar(
            'login', $chave, self::LOGIN_MAX_FALHAS, self::LOGIN_JANELA
        );

        if ($espera > 0) {
            Logger::warn('login', 'Login bloqueado por excesso de tentativas', [
                'email' => $data['email'],
            ]);
            $minutos = (int) ceil($espera / 60);
            $msg = "Muitas tentativas de login. Tente novamente em $minutos minuto(s).";
            return [
                'success'     => false,
                'bloqueado'   => true,
                'retry_after' => $espera,
                'message'     => $msg,
                'mensagem'    => $msg,
            ];
        }

        $result = $this->model->login($data['email'], $data['senha']);

        if ($result['success']) {
            $this->limitador->limpar('login', $chave);
            Logger::info('login', 'Login realizado com success', [
                'usuario_id' => $result['usuario']['id'] ?? '?',
                'email'      => $data['email']
            ]);
        } else {
            $this->limitador->registrar('login', $chave, self::LOGIN_JANELA);
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

        // Apelido = identificador público (/chef/{apelido}). Se não vier,
        // gera um a partir do nome; se vier, valida formato e unicidade.
        $apelidoService = $this->apelidoService ?? new ApelidoService();
        $apelido = trim((string) ($data['apelido'] ?? ''));

        if ($apelido === '') {
            $apelido = $apelidoService->sugerir($data['nome'] ?? '');
        } elseif ($erro = $apelidoService->validar($apelido)) {
            return ['success' => false, 'errors' => ['apelido' => $erro]];
        }

        $result = $this->model->cadastrar(
            $data['nome']           ?? '',
            $data['email'],
            $data['senha'],
            $apelido
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

        // Resposta idêntica exista o e-mail ou não (não revela quem tem conta)
        $generica = [
            "success" => true,
            "message" => "Se o e-mail estiver cadastrado, enviaremos um link para redefinir a senha."
        ];

        $email = trim((string) $data["email"]);

        // Limite de pedidos por e-mail (evita usar o envio de e-mails como spam)
        if ($this->limitador->segundosParaLiberar(
            'recuperacao', $email, self::RECUPERACAO_MAX, self::RECUPERACAO_JANELA
        ) > 0) {
            Logger::warn("recuperacao_senha", "Pedido bloqueado por excesso de tentativas", [
                "email" => $email
            ]);
            return $generica;
        }
        $this->limitador->registrar('recuperacao', $email, self::RECUPERACAO_JANELA);

        $result = $this->model->gerarTokenRecuperacao($email);

        if (!($result["success"] ?? false)) {
            return $generica;
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

            return $generica;

        } catch (Exception $e) {

            Logger::error(
                "recuperacao_senha",
                "Falha ao enviar e-mail de recuperação",
                [
                    "email" => $data["email"],
                    "erro"  => $e->getMessage()
                ]
            );

            // Não informa a falha ao cliente: a resposta seria diferente para
            // e-mails cadastrados e revelaria quem tem conta. Fica no log.
            return $generica;
        }
    }
}
