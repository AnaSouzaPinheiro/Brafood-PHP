<?php

/**
 * Centraliza o upload de imagens de receitas.
 *
 * Salva apenas o caminho relativo (ex: uploads/receitas/receita_abc123.jpg),
 * sem nenhuma URL absoluta. O front-end é responsável por prefixar com
 * a baseUrl correta (apiUrl do environment).
 *
 * Novidades v2:
 * - Redimensiona automaticamente para MAX_LARGURA (padrão 1200px)
 * - Converte PNG fotográfico para JPEG (sem transparência = sem motivo para PNG)
 * - Comprime com qualidade 82 (imperceptível visualmente, ~70–85% menor)
 */

namespace App\Service;

class UploadService
{
    private string $uploadDir;
    private string $relativePath;

    // Largura máxima em pixels — suficiente para qualquer tela, inclusive Retina
    private int $maxLargura;

    // Qualidade JPEG: 0–100 (82 = ótimo equilíbrio tamanho/qualidade)
    private int $qualidadeJpeg;

    // Compressão PNG: 0–9 (7 = boa compressão sem custo de CPU alto)
    private int $compressaoPng;

    public function __construct(
        string $uploadDir,
        string $relativePath,
        int $maxLargura    = 1200,
        int $qualidadeJpeg = 82,
        int $compressaoPng = 7
    ) {
        $this->uploadDir    = rtrim($uploadDir, '/') . '/';
        $this->relativePath = rtrim($relativePath, '/');
        $this->maxLargura   = $maxLargura;
        $this->qualidadeJpeg = $qualidadeJpeg;
        $this->compressaoPng = $compressaoPng;

        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    /**
     * Processa até 3 imagens vindas de $_FILES.
     *
     * Para cada campo (imagem1, imagem2, imagem3):
     * - Se veio um arquivo novo → move, otimiza e retorna o caminho relativo.
     * - Se não veio → retorna $fallback[$campo] (caminho já existente ou null).
     *
     * @param  array<string, string|null> $fallback  Caminhos relativos atuais (para update).
     * @return array<string, string|null>            ['imagem1' => caminho_relativo|null, ...]
     */
    public function processarImagens(array $fallback = []): array
    {
        $campos  = ['imagem1', 'imagem2', 'imagem3'];
        $imagens = [];

        foreach ($campos as $campo) {
            if (!empty($_FILES[$campo]['tmp_name'])) {
                $resultado = $this->moverEOtimizar($_FILES[$campo]);
                $imagens[$campo] = $resultado;
            } else {
                $imagens[$campo] = $fallback[$campo] ?? null;
            }
        }

        return $imagens;
    }

    /**
     * Processa uma única imagem de $_FILES (para foto de perfil, etc).
     *
     * @param  array       $file      Entrada de $_FILES (ex: $_FILES['foto'])
     * @param  string|null $fallback  Caminho atual, retornado se não houver upload
     * @return string|null
     */
    public function processarUmaImagem(array $file, ?string $fallback = null): ?string
    {
        if (!empty($file['tmp_name'])) {
            return $this->moverEOtimizar($file);
        }
        return $fallback;
    }

    // ─── Internos ─────────────────────────────────────────────────────────────

    /**
     * Move o arquivo temporário para o diretório de uploads e otimiza.
     * Retorna o caminho relativo para salvar no banco, ou null em caso de erro.
     */
    private function moverEOtimizar(array $file): ?string
    {
        $tmpPath  = $file['tmp_name'];
        $info     = @getimagesize($tmpPath);

        if (!$info) return null; // não é imagem

        $tipo = $info[2];

        // PNG sem transparência vira JPEG para economizar espaço
        $deveSalvarComoJpeg = ($tipo === IMAGETYPE_JPEG)
            || ($tipo === IMAGETYPE_PNG && !$this->temTransparencia($tmpPath));

        $extensao = $deveSalvarComoJpeg ? 'jpg' : 'png';
        $novoNome = uniqid('receita_') . '.' . $extensao;
        $destino  = $this->uploadDir . $novoNome;

        // Move temporário para destino definitivo
        if (!move_uploaded_file($tmpPath, $destino)) {
            return null;
        }

        // Otimiza no lugar
        $this->otimizar($destino, $tipo, $deveSalvarComoJpeg);

        return $this->relativePath . '/' . $novoNome;
    }

    /**
     * Redimensiona (se necessário) e recomprime a imagem salva no disco.
     */
    private function otimizar(string $caminho, int $tipoOriginal, bool $salvarComoJpeg): void
    {
        $info = @getimagesize($caminho);
        if (!$info) return;

        [$largura, $altura] = $info;

        // Calcula dimensões alvo mantendo proporção
        if ($largura > $this->maxLargura || $altura > $this->maxLargura) {
            if ($largura >= $altura) {
                $novaLargura = $this->maxLargura;
                $novaAltura  = (int) round($altura * ($this->maxLargura / $largura));
            } else {
                $novaAltura  = $this->maxLargura;
                $novaLargura = (int) round($largura * ($this->maxLargura / $altura));
            }
            $precisaRedimensionar = true;
        } else {
            $novaLargura = $largura;
            $novaAltura  = $altura;
            $precisaRedimensionar = false;
        }

        // Carrega imagem fonte
        $src = match($tipoOriginal) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($caminho),
            IMAGETYPE_PNG  => @imagecreatefrompng($caminho),
            IMAGETYPE_WEBP => @imagecreatefromwebp($caminho),
            default        => null,
        };

        if (!$src) return;

        if ($precisaRedimensionar) {
            $dst = imagecreatetruecolor($novaLargura, $novaAltura);

            // Fundo branco para PNG convertido a JPEG (elimina transparência)
            if ($salvarComoJpeg && $tipoOriginal === IMAGETYPE_PNG) {
                imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            }

            // Preserva alpha se ainda for PNG
            if (!$salvarComoJpeg) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            }

            imagecopyresampled(
                $dst, $src,
                0, 0, 0, 0,
                $novaLargura, $novaAltura,
                $largura, $altura
            );
        } else {
            $dst = $src; // sem cópia — só recomprime
        }

        // Salva com compressão
        if ($salvarComoJpeg) {
            imagejpeg($dst, $caminho, $this->qualidadeJpeg);
        } else {
            imagepng($dst, $caminho, $this->compressaoPng);
        }

        imagedestroy($src);
        if ($dst !== $src) imagedestroy($dst);
    }

    /**
     * Verifica se um PNG tem pixels com alpha < 255 (transparência real).
     * PNGs fotográficos sem transparência podem ser convertidos para JPEG.
     */
    private function temTransparencia(string $caminho): bool
    {
        $img = @imagecreatefrompng($caminho);
        if (!$img) return false;

        $largura = imagesx($img);
        $altura  = imagesy($img);

        // Amostragem: checar todos os pixels é lento em imagens grandes.
        // Usamos um passo para checar ~10.000 pontos no máximo.
        $passo = max(1, (int) sqrt($largura * $altura / 10000));

        for ($x = 0; $x < $largura; $x += $passo) {
            for ($y = 0; $y < $altura; $y += $passo) {
                $cor   = imagecolorat($img, $x, $y);
                $alpha = ($cor >> 24) & 0x7F; // 0 = opaco, 127 = totalmente transparente
                if ($alpha > 0) {
                    imagedestroy($img);
                    return true;
                }
            }
        }

        imagedestroy($img);
        return false;
    }
}
