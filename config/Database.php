<?php

require_once __DIR__ . '/Logger.php';

class Database
{
    private $host;
    private $db_name;
    private $port;
    private $username;
    private $password;
    public $conn;

    public function __construct()
    {
        $this->host     = $_ENV['DB_HOST']     ?? 'localhost';
        $this->db_name  = $_ENV['DB_NAME']     ?? 'brafood_php';
        $this->port     = $_ENV['DB_PORT']     ?? 3306;
        $this->username = $_ENV['DB_USER']     ?? 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
    }

    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};port={$this->port}",
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8");
        } catch (PDOException $exception) {
            echo $exception->getMessage();
            exit;
            /*Logger::fatal('database', 'Falha na conexão com o banco', [
                'message' => $exception->getMessage()
            ]);
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno no servidor.']);
            exit;*/
        }
        return $this->conn;
    }
}
