<?php

namespace App\Service;

use App\Config\Database;
use PDO;

/**
 * Regras do apelido, que também é o identificador público do usuário
 * (URL do perfil: /chef/{apelido}).
 *
 * Formato: 3 a 30 caracteres, apenas letras minúsculas, números, "_" e ".".
 * Não pode começar/terminar com "." nem ter ".." seguidos.
 * Único (sem diferenciar maiúsculas/minúsculas).
 */
class ApelidoService
{
    public const MIN = 3;
    public const MAX = 30;
    public const REGEX = '/^(?!\.)(?!.*\.\.)[a-z0-9_.]{3,30}(?<!\.)$/';

    /** Palavras que não podem ser usadas (rotas e termos reservados) */
    private const RESERVADOS = [
        'admin', 'administrador', 'brafood', 'suporte', 'perfil', 'chef',
        'login', 'cadastro', 'api', 'root', 'sistema', 'moderador',
    ];

    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /** "Ana Letícia!" → "ana.leticia" */
    public static function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');

        $semAcento = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        if ($semAcento !== false) {
            $texto = $semAcento;
        }

        $texto = preg_replace('/\s+/', '.', $texto);       // espaços viram ponto
        $texto = preg_replace('/[^a-z0-9_.]/', '', $texto); // remove o resto
        $texto = preg_replace('/\.{2,}/', '.', $texto);     // sem ".."
        $texto = trim($texto, '.');

        return substr($texto, 0, self::MAX);
    }

    /** Retorna a mensagem de erro de formato, ou null se o formato for válido */
    public static function erroDeFormato(string $apelido): ?string
    {
        $len = strlen($apelido);
        if ($len < self::MIN || $len > self::MAX) {
            return 'O apelido deve ter entre ' . self::MIN . ' e ' . self::MAX . ' caracteres.';
        }
        if (!preg_match(self::REGEX, $apelido)) {
            return 'Use apenas letras minúsculas, números, "_" e "." (sem ponto no início, no fim ou repetido).';
        }
        if (in_array($apelido, self::RESERVADOS, true)) {
            return 'Este apelido é reservado. Escolha outro.';
        }
        return null;
    }

    public function disponivel(string $apelido, ?int $ignorarUsuarioId = null): bool
    {
        $sql = 'SELECT 1 FROM usuarios WHERE LOWER(apelido) = LOWER(:apelido)';
        $params = [':apelido' => $apelido];

        if ($ignorarUsuarioId) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $ignorarUsuarioId;
        }

        $stmt = $this->conn->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);

        return !$stmt->fetchColumn();
    }

    /**
     * Valida formato + disponibilidade.
     * @return string|null mensagem de erro, ou null se puder ser usado
     */
    public function validar(string $apelido, ?int $ignorarUsuarioId = null): ?string
    {
        if ($erro = self::erroDeFormato($apelido)) {
            return $erro;
        }
        if (!$this->disponivel($apelido, $ignorarUsuarioId)) {
            return 'Este apelido já está em uso.';
        }
        return null;
    }

    /** Gera um apelido válido e livre a partir de um nome ("Ana Letícia" → "ana.leticia" ou "ana.leticia2") */
    public function sugerir(string $base, ?int $ignorarUsuarioId = null): string
    {
        $base = self::normalizar($base);

        if (strlen($base) < self::MIN) {
            $base = str_pad($base ?: 'chef', self::MIN, '0');
        }
        if (in_array($base, self::RESERVADOS, true)) {
            $base .= '.chef';
        }

        if ($this->disponivel($base, $ignorarUsuarioId)) {
            return $base;
        }

        for ($i = 2; $i < 1000; $i++) {
            $sufixo   = (string) $i;
            $candidato = substr($base, 0, self::MAX - strlen($sufixo)) . $sufixo;
            if ($this->disponivel($candidato, $ignorarUsuarioId)) {
                return $candidato;
            }
        }

        return substr($base, 0, self::MAX - 6) . bin2hex(random_bytes(3));
    }
}
