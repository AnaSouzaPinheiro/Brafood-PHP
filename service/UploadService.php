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
    private int $maxAltura;

    // Qualidade JPEG: 0–100 (82 = ótimo equilíbrio tamanho/qualidade)
    private int $qualidadeJpeg;

    // Compressão PNG: 0–9 (7 = boa compressão sem custo de CPU alto)
    private int $compressaoPng;

    private bool $crop;

    private bool $forcarPng;

    public function __construct(
        string $uploadDir,
        string $relativePath,
        int $maxLargura    = 1200,
        int $maxAltura     = 1200,
        bool $crop = false,
        int $qualidadeJpeg = 82,
        int $compressaoPng = 7,
        bool $forcarPng = false
    ) {
        $this->uploadDir    = rtrim($uploadDir, '/') . '/';
        $this->relativePath = rtrim($relativePath, '/');
        $this->maxLargura   = $maxLargura;
        $this->maxAltura = $maxAltura;
        $this->crop = $crop;
        $this->qualidadeJpeg = $qualidadeJpeg;
        $this->compressaoPng = $compressaoPng;
        $this->forcarPng = $forcarPng;

        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
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

    /**
     * Processa uma quantidade dinâmica (N) de imagens vindas de um input
     * múltiplo do tipo <input type="file" name="galeria[]" multiple>.
     *
     * Ao contrário de processarImagens(), não tem limite fixo de campos:
     * cada arquivo enviado em $_FILES['galeria'] vira uma imagem da galeria.
     *
     * @param  array $filesGaleria  Normalmente $_FILES['galeria'] (formato de array do PHP)
     * @return string[]             Caminhos relativos das imagens processadas com sucesso
     */
    public function processarGaleria(array $filesGaleria): array
    {
        if (empty($filesGaleria['tmp_name']) || !is_array($filesGaleria['tmp_name'])) {
            return [];
        }

        $caminhos = [];
        $total    = count($filesGaleria['tmp_name']);

        for ($i = 0; $i < $total; $i++) {
            if (empty($filesGaleria['tmp_name'][$i])) {
                continue;
            }

            $arquivoUnico = [
                'name'     => $filesGaleria['name'][$i]     ?? '',
                'type'     => $filesGaleria['type'][$i]     ?? '',
                'tmp_name' => $filesGaleria['tmp_name'][$i] ?? '',
                'error'    => $filesGaleria['error'][$i]    ?? UPLOAD_ERR_NO_FILE,
                'size'     => $filesGaleria['size'][$i]     ?? 0,
            ];

            if ($arquivoUnico['error'] !== UPLOAD_ERR_OK) {
                continue;
            }

            $caminho = $this->moverEOtimizar($arquivoUnico);
            if ($caminho !== null) {
                $caminhos[] = $caminho;
            }
        }

        return $caminhos;
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
        if ($this->forcarPng) {

            $deveSalvarComoJpeg = false;
        } else {

            $deveSalvarComoJpeg =
                ($tipo === IMAGETYPE_JPEG)
                || ($tipo === IMAGETYPE_PNG && !$this->temTransparencia($tmpPath));
        }

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

        // Carrega imagem original
        $src = match ($tipoOriginal) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($caminho),
            IMAGETYPE_PNG  => @imagecreatefrompng($caminho),
            IMAGETYPE_WEBP => @imagecreatefromwebp($caminho),
            default        => null,
        };

        if (!$src) return;

        // ─────────────────────────────────────────
        // MODO CROP (banner/cover)
        // ─────────────────────────────────────────
        if ($this->crop) {

            $ratio = max(
                $this->maxLargura / $largura,
                $this->maxAltura / $altura
            );

            $tempLargura = (int) round($largura * $ratio);
            $tempAltura  = (int) round($altura * $ratio);

            $temp = imagecreatetruecolor($tempLargura, $tempAltura);

            if (!$salvarComoJpeg) {
                imagealphablending($temp, false);
                imagesavealpha($temp, true);
            }

            imagecopyresampled(
                $temp,
                $src,
                0,
                0,
                0,
                0,
                $tempLargura,
                $tempAltura,
                $largura,
                $altura
            );

            $dst = imagecreatetruecolor(
                $this->maxLargura,
                $this->maxAltura
            );

            if (!$salvarComoJpeg) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            }

            $cropX = (int) round(($tempLargura - $this->maxLargura) / 2);
            $cropY = (int) round(($tempAltura - $this->maxAltura) / 2);

            imagecopy(
                $dst,
                $temp,
                0,
                0,
                $cropX,
                $cropY,
                $this->maxLargura,
                $this->maxAltura
            );

            imagedestroy($temp);
        } else {

            // ─────────────────────────────────────
            // MODO NORMAL
            // ─────────────────────────────────────

            if (
                $largura > $this->maxLargura ||
                $altura > $this->maxAltura
            ) {

                $ratioLargura = $this->maxLargura / $largura;
                $ratioAltura  = $this->maxAltura / $altura;

                $ratio = min($ratioLargura, $ratioAltura);

                $novaLargura = (int) round($largura * $ratio);
                $novaAltura  = (int) round($altura * $ratio);
            } else {

                $novaLargura = $largura;
                $novaAltura  = $altura;
            }

            $dst = imagecreatetruecolor(
                $novaLargura,
                $novaAltura
            );

            if ($salvarComoJpeg && $tipoOriginal === IMAGETYPE_PNG) {
                imagefill(
                    $dst,
                    0,
                    0,
                    imagecolorallocate($dst, 255, 255, 255)
                );
            }

            if (!$salvarComoJpeg) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            }

            imagecopyresampled(
                $dst,
                $src,
                0,
                0,
                0,
                0,
                $novaLargura,
                $novaAltura,
                $largura,
                $altura
            );
        }

        // ─────────────────────────────────────────
        // SALVAR
        // ─────────────────────────────────────────

        if ($salvarComoJpeg) {

            imagejpeg(
                $dst,
                $caminho,
                $this->qualidadeJpeg
            );
        } else {

            imagepng(
                $dst,
                $caminho,
                $this->compressaoPng
            );
        }

        imagedestroy($src);
        imagedestroy($dst);
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
