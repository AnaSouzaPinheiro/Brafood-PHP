<?php

namespace App\Config;

use PDO;
use PDOException;
use App\Config\Logger;

require_once __DIR__ . '/config.php';

class Database
{
    private static ?Database $instance = null;
    private ?PDO $conn = null;

    private $host;
    private $db_name;
    private $port;
    private $username;
    private $password;

    // 🔒 1. construtor privado (REGRA do Singleton)
    private function __construct()
    {
        $this->host     = DB_HOST;
        $this->db_name  = DB_NAME;
        $this->port     = DB_PORT;
        $this->username = DB_USER;
        $this->password = DB_PASSWORD;
    }

    // 🚪 2. ponto único de acesso
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }

        return self::$instance;
    }

    // 🔌 conexão única reutilizável
    public function getConnection(): PDO
    {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};port={$this->port}",
                $this->username,
                $this->password
            );

            $this->conn->exec("set names utf8");

        } catch (PDOException $exception) {

            Logger::fatal('database', 'Falha na conexão com o banco', [
                'message' => $exception->getMessage()
            ]);

            http_response_code(500);
            echo json_encode(['error' => 'Erro interno no servidor.']);
            exit;
        }

        return $this->conn;
    }
}