<?php

/**
 * Logger estático do BraFood.
 *
 * Níveis disponíveis (do menos para o mais grave):
 *   INFO  — ação normal do usuário (login, receita criada, favorito adicionado…)
 *   WARN  — algo inesperado mas não fatal (e-mail não enviado, imagem ausente…)
 *   ERROR — falha que impediu uma operação (exception, query falhou…)
 *   FATAL — falha crítica do sistema (sem conexão com o banco, arquivo ausente…)
 *
 * Arquivos gerados em /logs:
 *   app.log    — INFO + WARN
 *   error.log  — ERROR + FATAL
 *
 * Formato de cada linha:
 *   [2025-01-15 14:32:01] [INFO] [receitas/add] Receita criada | usuario=7 id=42
 */
class Logger
{
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

    private static function write(string $level, string $context, string $message, array $extra): void
    {
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $extras    = self::formatExtra($extra);
        $line      = "[$timestamp] [$level] [$context] $message" . ($extras ? " | $extras" : '') . PHP_EOL;

        $file = in_array($level, ['ERROR', 'FATAL'])
            ? self::$logDir . '/error.log'
            : self::$logDir . '/app.log';

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    private static function formatExtra(array $extra): string
    {
        $parts = [];
        foreach ($extra as $key => $value) {
            // Nunca loga senhas ou tokens
            if (in_array(strtolower($key), ['senha', 'password', 'token', 'pass'])) {
                $value = '***';
            }
            $parts[] = "$key=$value";
        }
        return implode(' ', $parts);
    }
}
