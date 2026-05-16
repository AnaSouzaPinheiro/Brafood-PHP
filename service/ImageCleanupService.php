<?php

/**
 * ImageCleanupService
 *
 * Apaga do disco imagens que não estão mais vinculadas a nenhuma receita
 * ou usuário, evitando acúmulo de arquivos órfãos após edições ou exclusões.
 *
 * USO TÍPICO:
 *   // Ao editar receita — passa os caminhos antigos que serão substituídos:
 *   ImageCleanupService::apagarImagensAntigas(
 *       $imagensAntigas,   // array com caminhos relativos anteriores
 *       $imagensNovas,     // array com caminhos relativos novos (após update)
 *       __DIR__ . '/../../'
 *   );
 *
 *   // Ao excluir receita — passa todas as imagens da receita:
 *   ImageCleanupService::apagarArquivos($imagens, __DIR__ . '/../../');
 */

namespace App\Service;

use App\Config\Logger;

class ImageCleanupService
{
    /**
     * Apaga do disco apenas as imagens que mudaram (antigas ≠ novas).
     *
     * Compara os caminhos relativos antes e depois da edição.
     * Só apaga o arquivo antigo se o caminho realmente mudou (ou foi removido)
     * e se o arquivo não está sendo usado em nenhum outro campo da mesma operação.
     *
     * @param array<string|null> $antigas   Caminhos relativos antes do update.
     * @param array<string|null> $novas     Caminhos relativos depois do update.
     * @param string             $baseDir   Raiz do projeto (onde fica a pasta uploads/).
     */
    public static function apagarImagensAntigas(array $antigas, array $novas, string $baseDir): void
    {
        $baseDir = rtrim($baseDir, '/') . '/';

        // Caminhos que ainda estão em uso depois da edição
        $emUso = array_filter($novas, fn($p) => $p !== null);

        foreach ($antigas as $caminhoAntigo) {
            if ($caminhoAntigo === null) {
                continue;
            }

            // Se o caminho antigo ainda aparece nas novas imagens, não apaga
            if (in_array($caminhoAntigo, $emUso, true)) {
                continue;
            }

            self::apagarArquivo($caminhoAntigo, $baseDir);
        }
    }

    /**
     * Apaga uma lista de imagens do disco (uso na exclusão de receita/usuário).
     *
     * @param array<string|null> $caminhos  Caminhos relativos a apagar.
     * @param string             $baseDir   Raiz do projeto.
     */
    public static function apagarArquivos(array $caminhos, string $baseDir): void
    {
        $baseDir = rtrim($baseDir, '/') . '/';

        foreach ($caminhos as $caminho) {
            if ($caminho === null) {
                continue;
            }
            self::apagarArquivo($caminho, $baseDir);
        }
    }

    // ─── Interno ──────────────────────────────────────────────────────────────

    private static function apagarArquivo(string $caminhoRelativo, string $baseDir): void
    {
        // Sanitiza para evitar path traversal
        $caminhoRelativo = ltrim($caminhoRelativo, '/');

        // Só permite apagar dentro de uploads/
        if (!str_starts_with($caminhoRelativo, 'uploads/')) {
            Logger::error('ImageCleanupService', 'Tentativa de apagar fora de uploads/', [
                'caminho' => $caminhoRelativo,
            ]);
            return;
        }

        $caminhoAbsoluto = $baseDir . $caminhoRelativo;

        if (!file_exists($caminhoAbsoluto)) {
            return; // Já não existe, sem ação necessária
        }

        if (@unlink($caminhoAbsoluto)) {
            Logger::info('ImageCleanupService', 'Imagem órfã removida', [
                'arquivo' => $caminhoRelativo,
            ]);
        } else {
            Logger::error('ImageCleanupService', 'Falha ao remover imagem', [
                'arquivo' => $caminhoRelativo,
            ]);
        }
    }
}
