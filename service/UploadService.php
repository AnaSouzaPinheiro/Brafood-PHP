<?php

/**
 * Centraliza o upload de imagens de receitas.
 *
 * Antes, a lógica de mover arquivos, gerar nomes únicos e montar URLs
 * estava duplicada em routes/receitas/add.php e routes/receitas/update.php.
 * Aqui fica em um único lugar e os routes só chamam este serviço.
 */
class UploadService
{
    private string $uploadDir;
    private string $baseUrl;

    public function __construct(string $uploadDir, string $baseUrl)
    {
        $this->uploadDir = rtrim($uploadDir, '/') . '/';
        $this->baseUrl   = rtrim($baseUrl, '/') . '/';

        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    /**
     * Processa até 3 imagens vindas de $_FILES.
     *
     * Para cada campo (imagem1, imagem2, imagem3):
     * - Se veio um arquivo novo → move e retorna a URL pública.
     * - Se não veio → retorna $fallback[$campo] (imagem já existente ou null).
     *
     * @param  array<string, string|null> $fallback  URLs atuais das imagens (para update).
     * @return array<string, string|null>            ['imagem1' => url|null, ...]
     */
    public function processarImagens(array $fallback = []): array
    {
        $campos  = ['imagem1', 'imagem2', 'imagem3'];
        $imagens = [];

        foreach ($campos as $campo) {
            if (!empty($_FILES[$campo]['tmp_name'])) {
                $ext      = pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION);
                $novoNome = uniqid('receita_') . '.' . $ext;
                $destino  = $this->uploadDir . $novoNome;

                $imagens[$campo] = move_uploaded_file($_FILES[$campo]['tmp_name'], $destino)
                    ? $this->baseUrl . $novoNome
                    : null;
            } else {
                $imagens[$campo] = $fallback[$campo] ?? null;
            }
        }

        return $imagens;
    }
}
