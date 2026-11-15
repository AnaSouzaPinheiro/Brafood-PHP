<?php

namespace App\Service;

/**
 * Gera o "slug" das URLs (ex.: "Frango agridoce chinês" → "frango-agridoce-chines").
 * Segue a mesma regra de toSlug() do front-end (src/app/utils/slug.ts), para os
 * links do sitemap serem idênticos aos que o app monta.
 */
class Slug
{
    private const ACENTOS = [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
    ];

    public static function gerar(string $texto): string
    {
        $texto = strtr(mb_strtolower($texto, 'UTF-8'), self::ACENTOS);
        $texto = preg_replace('/[^a-z0-9\s-]/', '', $texto);
        $texto = trim($texto);

        return preg_replace('/\s+/', '-', $texto);
    }
}
