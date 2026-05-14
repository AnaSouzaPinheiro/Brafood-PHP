<?php

/**
 * Centraliza o upload de imagens de receitas.
 *
 * Salva apenas o caminho relativo (ex: uploads/receitas/receita_abc123.jpg),
 * sem nenhuma URL absoluta. O front-end é responsável por prefixar com
 * a baseUrl correta (apiUrl do environment).
 */
class UploadService
{
    private string $uploadDir;
    private string $relativePath;

    /**
     * @param string $uploadDir    Caminho absoluto no sistema de arquivos onde os arquivos serão salvos.
     * @param string $relativePath Prefixo relativo que será armazenado no banco.
     */
    public function __construct(string $uploadDir, string $relativePath)
    {
        $this->uploadDir    = rtrim($uploadDir, '/') . '/';
        $this->relativePath = rtrim($relativePath, '/');

        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    /**
     * Processa até 3 imagens vindas de $_FILES.
     *
     * Para cada campo (imagem1, imagem2, imagem3):
     * - Se veio um arquivo novo → move e retorna o caminho relativo.
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
                $ext      = pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION);
                $novoNome = uniqid('receita_') . '.' . $ext;
                $destino  = $this->uploadDir . $novoNome;

                $imagens[$campo] = move_uploaded_file($_FILES[$campo]['tmp_name'], $destino)
                    ? $this->relativePath . '/' . $novoNome
                    : null;
            } else {
                $imagens[$campo] = $fallback[$campo] ?? null;
            }
        }

        return $imagens;
    }
}
