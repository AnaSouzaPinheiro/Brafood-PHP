<?php

namespace App\Model;

use App\Config\Database;
use PDO;

class NewsletterModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Inscreve um e-mail na newsletter.
     * Retorna 'inscrito' se novo/reativado, 'ja_inscrito' se já existia ativo.
     */
    public function inscrever(string $email): string
    {
        $stmt = $this->conn->prepare(
            'SELECT id, ativo FROM newsletter_inscritos WHERE email = :email'
        );
        $stmt->execute([':email' => $email]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if (!$existing['ativo']) {
                $upd = $this->conn->prepare(
                    'UPDATE newsletter_inscritos SET ativo = 1, inscrito_em = NOW() WHERE id = :id'
                );
                $upd->execute([':id' => $existing['id']]);
                return 'inscrito';
            }
            return 'ja_inscrito';
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO newsletter_inscritos (email, ativo, inscrito_em) VALUES (:email, 1, NOW())'
        );
        $stmt->execute([':email' => $email]);
        return 'inscrito';
    }

    public function cancelar(string $email): bool
    {
        $stmt = $this->conn->prepare(
            'UPDATE newsletter_inscritos SET ativo = 0 WHERE email = :email'
        );
        $stmt->execute([':email' => $email]);
        return $stmt->rowCount() > 0;
    }
}
