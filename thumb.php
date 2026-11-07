<?php

/**
 * Miniaturas sob demanda para os cards de receita.
 *
 * O .htaccess só chega aqui quando uploads/receitas/thumbs/<arquivo>.webp
 * ainda não existe. Geramos a miniatura (600x420, WebP, corte central),
 * salvamos no disco e servimos. Das próximas vezes o Apache entrega o
 * arquivo estático direto, sem passar pelo PHP.
 *
 * Se algo falhar (arquivo inexistente, GD sem WebP...), redireciona para
 * a imagem original — o card continua funcionando.
 */

const THUMB_LARGURA  = 600;  // 2x o card (290x210) para telas Retina
const THUMB_ALTURA   = 420;
const THUMB_QUALIDADE = 72;

$arquivo = basename($_GET['f'] ?? '');

if (!preg_match('/^[A-Za-z0-9_.-]+\.(jpe?g|png|webp)$/i', $arquivo)) {
    http_response_code(404);
    exit;
}

$dirOriginal = __DIR__ . '/uploads/receitas/';
$dirThumb    = $dirOriginal . 'thumbs/';
$original    = $dirOriginal . $arquivo;
$destino     = $dirThumb . $arquivo . '.webp';

function redirecionarParaOriginal(string $arquivo): void
{
    header('Location: ../' . rawurlencode($arquivo), true, 302);
    exit;
}

if (!is_file($original)) {
    http_response_code(404);
    exit;
}

if (!function_exists('imagewebp')) {
    redirecionarParaOriginal($arquivo);
}

if (!is_file($destino)) {
    $info = @getimagesize($original);
    $src  = match ($info[2] ?? null) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($original),
        IMAGETYPE_PNG  => @imagecreatefrompng($original),
        IMAGETYPE_WEBP => @imagecreatefromwebp($original),
        default        => false,
    };

    if (!$src) {
        redirecionarParaOriginal($arquivo);
    }

    [$larg, $alt] = $info;

    // Corte central no formato do card (cover)
    $ratio  = max(THUMB_LARGURA / $larg, THUMB_ALTURA / $alt);
    $cropL  = (int) round(THUMB_LARGURA / $ratio);
    $cropA  = (int) round(THUMB_ALTURA / $ratio);
    $cropX  = (int) round(($larg - $cropL) / 2);
    $cropY  = (int) round(($alt - $cropA) / 2);

    $dst = imagecreatetruecolor(THUMB_LARGURA, THUMB_ALTURA);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $src, 0, 0, $cropX, $cropY, THUMB_LARGURA, THUMB_ALTURA, $cropL, $cropA);

    if (!is_dir($dirThumb)) {
        @mkdir($dirThumb, 0755, true);
    }

    $ok = @imagewebp($dst, $destino, THUMB_QUALIDADE);
    imagedestroy($src);
    imagedestroy($dst);

    if (!$ok) {
        @unlink($destino);
        redirecionarParaOriginal($arquivo);
    }
}

header('Content-Type: image/webp');
header('Cache-Control: public, max-age=31536000, immutable');
header('Content-Length: ' . filesize($destino));
readfile($destino);
