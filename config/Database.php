<?php

namespace App\Config;

use PDO;
use PDOException;
use App\Config\Logger;

require_once __DIR__ . '/bootstrap.php';

class Database
{
    private static ?Database $instance = null;
    private ?PDO $conn = null;

    private $host;
    private $db_name;
    private $port;
    private $username;
    private $password;

    private function __construct()
    {
        $this->host     = DB_HOST;
        $this->db_name  = DB_NAME;
        $this->port     = DB_PORT;
        $this->username = DB_USER;
        $this->password = DB_PASSWORD;
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};port={$this->port};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    // Lança PDOException em qualquer erro de query ou conexão
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    // Retorna arrays associativos por padrão
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Desativa emulação de prepared statements (mais seguro)
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );

        } catch (PDOException $exception) {

            // Nos testes (tests/bootstrap.php) a falha sobe como exceção, em vez de
            // responder 500 e encerrar o processo do PHPUnit.
            if (defined('APP_ENV') && APP_ENV === 'testing') {
                throw $exception;
            }

            Logger::fatal('database', 'Falha na conexão com o banco', [
                'message' => $exception->getMessage()
            ]);

            http_response_code(500);
            echo json_encode(['error' => 'Erro interno no servidor.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        return $this->conn;
    }
}