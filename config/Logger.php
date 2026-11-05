<?php

namespace App\Config;

/**
 * Logger estático do BraFood.
 *
 * Níveis (do menos para o mais grave):
 *   INFO  — ação normal do usuário (login, receita criada, favorito adicionado…)
 *   WARN  — algo inesperado mas não fatal (e-mail não enviado, imagem ausente…)
 *   ERROR — falha que impediu uma operação (exception, query falhou…)
 *   FATAL — falha crítica do sistema (sem conexão com o banco, arquivo ausente…)
 *
 * Organização em /logs (uma pasta por dia, um arquivo por assunto):
 *   logs/2026-11-05/receitas.log     — tudo do assunto "receitas" no dia
 *   logs/2026-11-05/auth.log         — login, cadastro…
 *   logs/2026-11-05/_erros.log       — cópia de todos os ERROR/FATAL do dia
 *   logs/2026-11-05/php.log          — erros nativos do PHP (via bootstrap)
 *
 * O assunto vem da 1ª parte do contexto: 'receitas/add' → receitas.log.
 * Pastas com mais de RETENTION_DAYS dias são apagadas automaticamente.
 *
 * Formato de cada linha:
 *   [14:32:01] [INFO] [receitas/add] Receita criada | usuario=7 id=42
 *
 * IMPORTANTE: o Logger NUNCA gera warnings ou exceptions — seu papel é
 * auxiliar, não bloquear. Todo I/O usa @ e verifica permissões antes.
 */
class Logger
{
    private const RETENTION_DAYS = 30;

    /** Contextos que não seguem o padrão "assunto/acao" */
    private const TOPIC_ALIASES = [
        'login'               => 'auth',
        'imagecleanupservice' => 'imagens',
        'receitactl'          => 'receitas',
        'receita'             => 'receitas',
        'favorito'            => 'favoritos',
        'usuario'             => 'usuarios',
        'comentario'          => 'comentarios',
    ];

    private static string $logDir = __DIR__ . '/../logs';

    public static function info(string $context, string $message, array $extra = []): void
    {
        self::write('INFO', $context, $message, $extra);
    }

    public static function warn(string $context, string $message, array $extra = []): void
    {
        self::write('WARN', $context, $message, $extra);
    }

    public static function error(string $context, string $message, array $extra = []): void
    {
        self::write('ERROR', $context, $message, $extra);
    }

    public static function fatal(string $context, string $message, array $extra = []): void
    {
        self::write('FATAL', $context, $message, $extra);
    }

    /** Pasta de logs do dia (criada se necessário). Retorna null se não for gravável. */
    public static function dayDir(): ?string
    {
        $dir = self::$logDir . '/' . date('Y-m-d');

        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                return null;
            }
            // Primeira escrita do dia: aproveita para limpar dias antigos
            self::cleanup();
        }

        return is_writable($dir) ? $dir : null;
    }

    private static function write(string $level, string $context, string $message, array $extra): void
    {
        $dir = self::dayDir();
        if ($dir === null) {
            return;
        }

        $extras = self::formatExtra($extra);
        $line   = '[' . date('H:i:s') . "] [$level] [$context] $message"
                . ($extras ? " | $extras" : '') . PHP_EOL;

        @file_put_contents("$dir/" . self::topic($context) . '.log', $line, FILE_APPEND | LOCK_EX);

        if ($level === 'ERROR' || $level === 'FATAL') {
            @file_put_contents("$dir/_erros.log", $line, FILE_APPEND | LOCK_EX);
        }
    }

    /** 'receitas/add' → 'receitas'; 'ImageCleanupService' → 'imagens' */
    private static function topic(string $context): string
    {
        $first = strtolower(explode('/', trim($context, '/'))[0] ?? '');
        $first = self::TOPIC_ALIASES[$first] ?? $first;
        $first = preg_replace('/[^a-z0-9_-]/', '', $first);

        return $first !== '' ? $first : 'geral';
    }

    /** Remove pastas de dia mais antigas que RETENTION_DAYS. */
    private static function cleanup(): void
    {
        $limit = date('Y-m-d', strtotime('-' . self::RETENTION_DAYS . ' days'));

        foreach (@glob(self::$logDir . '/[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]', GLOB_ONLYDIR) ?: [] as $dir) {
            if (basename($dir) < $limit) {
                foreach (@glob("$dir/*") ?: [] as $file) {
                    @unlink($file);
                }
                @rmdir($dir);
            }
        }
    }

    private static function formatExtra(array $extra): string
    {
        $parts = [];
        foreach ($extra as $key => $value) {
            if (in_array(strtolower((string) $key), ['senha', 'password', 'token', 'pass'])) {
                $value = '***';
            } elseif (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            $parts[] = "$key=$value";
        }
        return implode(' ', $parts);
    }
}
