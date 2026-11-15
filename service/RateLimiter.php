<?php

namespace App\Service;

/**
 * Limitador de tentativas por janela de tempo, guardado em arquivos.
 *
 * Usa a pasta de cache da API (sem tabela nova, então não exige migration em
 * produção). Cada chave (ex.: "login" + e-mail + IP) tem um arquivo JSON com
 * os instantes das tentativas recentes.
 *
 * Uso: tentativas() para consultar, registrar() a cada falha, limpar() após
 * um sucesso.
 */
class RateLimiter
{
    private string $dir;

    /** @var callable */
    private $relogio;

    public function __construct(?string $dir = null, ?callable $relogio = null)
    {
        $this->dir    = rtrim($dir ?? __DIR__ . '/../cache', '/\\');
        $this->relogio = $relogio ?? 'time';
    }

    /**
     * Segundos até poder tentar de novo; 0 se ainda não atingiu o limite.
     */
    public function segundosParaLiberar(string $balde, string $chave, int $max, int $janela): int
    {
        $agora  = ($this->relogio)();
        $tentativas = $this->ler($balde, $chave, $janela, $agora);

        if (count($tentativas) < $max) {
            return 0;
        }

        // Libera quando a tentativa mais antiga que conta sair da janela
        $maisAntiga = $tentativas[count($tentativas) - $max];
        return max(1, $maisAntiga + $janela - $agora);
    }

    public function registrar(string $balde, string $chave, int $janela): void
    {
        $arquivo = $this->arquivo($balde, $chave);
        if (!$this->garantirPasta()) {
            return;
        }

        $agora = ($this->relogio)();
        $fp = @fopen($arquivo, 'c+');
        if (!$fp) {
            return;
        }

        if (flock($fp, LOCK_EX)) {
            $atuais = json_decode((string) stream_get_contents($fp), true);
            $atuais = array_values(array_filter(
                is_array($atuais) ? $atuais : [],
                fn($t) => is_int($t) && $t > $agora - $janela
            ));
            $atuais[] = $agora;

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($atuais));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);

        // Faxina ocasional dos arquivos antigos
        if (random_int(1, 50) === 1) {
            $this->faxina($agora);
        }
    }

    public function limpar(string $balde, string $chave): void
    {
        @unlink($this->arquivo($balde, $chave));
    }

    private function ler(string $balde, string $chave, int $janela, int $agora): array
    {
        $arquivo = $this->arquivo($balde, $chave);
        if (!is_file($arquivo)) {
            return [];
        }

        $dados = json_decode((string) @file_get_contents($arquivo), true);
        if (!is_array($dados)) {
            return [];
        }

        $validas = array_values(array_filter($dados, fn($t) => is_int($t) && $t > $agora - $janela));
        sort($validas);
        return $validas;
    }

    private function arquivo(string $balde, string $chave): string
    {
        return $this->dir . '/ratelimit_' . preg_replace('/[^a-z0-9_]/i', '', $balde)
            . '_' . hash('sha256', $balde . '|' . mb_strtolower($chave)) . '.json';
    }

    private function garantirPasta(): bool
    {
        return is_dir($this->dir) || @mkdir($this->dir, 0755, true) || is_dir($this->dir);
    }

    private function faxina(int $agora): void
    {
        foreach (glob($this->dir . '/ratelimit_*.json') ?: [] as $f) {
            if (@filemtime($f) < $agora - 86400) {
                @unlink($f);
            }
        }
    }
}
