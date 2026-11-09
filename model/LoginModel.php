<?php

namespace App\Model;

use App\Config\Database;
use PDO;

class LoginModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function cadastrar($nome, $email, $senha, $apelido)
    {
        $erros = $this->validarSenha($senha);
        $errosFiltrados = array_filter($erros);

        if (!empty($errosFiltrados)) {
            return ['success' => false, 'errors' => $errosFiltrados];
        }

        $stmt = $this->conn->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        if ($stmt->fetch()) return ['success' => false, 'errors' => ['Este email já está cadastrado.']];

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare("INSERT INTO usuarios (nome, apelido, email, senha) VALUES (:nome, :apelido, :email, :senha)");
        $stmt->bindParam(':apelido', $apelido);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senhaHash);
        $stmt->execute();

        return ['success' => true];
    }

    public function login($email, $senha)
    {
        $stmt = $this->conn->prepare("SELECT id, nome, apelido, email, genero, bio, foto_perfil, is_admin, senha FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($senha, $user['senha'])) {
            $token = bin2hex(random_bytes(32));

            $stmtToken = $this->conn->prepare("UPDATE usuarios SET token = :token WHERE id = :id");
            $stmtToken->bindParam(':token', $token);
            $stmtToken->bindParam(':id', $user['id']);
            $stmtToken->execute();

            return [
                'success' => true,
                'token'   => $token,
                'usuario' => [
                    'id'          => $user['id'],
                    'nome'        => $user['nome'],
                    'apelido'     => $user['apelido'],
                    'email'       => $user['email'],
                    'genero'      => $user['genero'],
                    'bio'         => $user['bio'],
                    'foto_perfil' => $user['foto_perfil'],
                    'is_admin'    => (int)$user['is_admin'],
                ],
            ];
        }
        return ['success' => false, 'mensagem' => 'Email ou senha incorretos'];
    }

    public function logout($token)
    {
        $stmt = $this->conn->prepare("UPDATE usuarios SET token = NULL WHERE token = :token");
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        return ['success' => true];
    }

    private function validarSenha($senha)
    {
        $erros = [null, null, null, null, null];

        if (strlen($senha) < 8 || empty($senha)) $erros[0] = "A senha deve ter no mínimo 8 caracteres.";
        if (!preg_match('/[0-9]/', $senha)) $erros[1] = "A senha deve conter pelo menos 1 dígito.";
        if (!preg_match('/[a-z]/', $senha)) $erros[2] = "A senha deve conter pelo menos 1 letra minúscula.";
        if (!preg_match('/[A-Z]/', $senha)) $erros[3] = "A senha deve conter pelo menos 1 letra maiúscula.";
        if (!preg_match('/[!@#$.?&()+-]/', $senha)) $erros[4] = "A senha deve conter pelo menos 1 carácter especial (! @ # $).";

        return $erros;
    }

    public function gerarTokenRecuperacao($email)
    {
        $stmt = $this->conn->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ["success" => false, "mensagem" => "Email não encontrado."];
        }

        $token = bin2hex(random_bytes(20));

        $stmt = $this->conn->prepare("
        UPDATE usuarios
        SET token_recuperacao = :token, token_expira = DATE_ADD(NOW(), INTERVAL 1 HOUR)
        WHERE email = :email
    ");
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return [
            "success" => true,
            "mensagem" => "Token gerado.",
            "token" => $token
        ];
    }

    public function resetarSenha($token, $senha, $confirmarSenha)
    {
        if ($senha !== $confirmarSenha) {
            return ["success" => false, "mensagem" => "As senhas não coincidem."];
        }

        $erros = $this->validarSenha($senha);
        $errosFiltrados = array_filter($erros);

        if (!empty($errosFiltrados)) {
            return ['success' => false, 'errors' => $errosFiltrados];
        }

        $stmt = $this->conn->prepare("
                SELECT id FROM usuarios
                WHERE token_recuperacao = :token
                AND token_expira > NOW()
            ");
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ["success" => false, "mensagem" => "Token inválido ou expirado."];
        }

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare("
            UPDATE usuarios
            SET senha = :senha, token_recuperacao = NULL, token_expira = NULL
            WHERE id = :id
        ");
        $stmt->bindParam(':senha', $senhaHash);
        $stmt->bindParam(':id', $user['id']);
        $stmt->execute();

        return ["success" => true, "mensagem" => "Senha redefinida com sucesso!"];
    }
}
