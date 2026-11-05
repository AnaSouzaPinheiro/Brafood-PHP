<?php
/**
 * Lê um arquivo .env e popula $_ENV / getenv().
 * Retorna false se o arquivo não existir.
 */
function loadEnv(string $path): bool
{
    if (!is_readable($path)) return false;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\n\r\"'");

        if ($key === '') continue;

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
    return true;
}
