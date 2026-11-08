<?php

/**
 * Controla favoritos de receitas.
 * Gerencia adição e remoção com atualização do contador qtd_fav,
 * listagem dos favoritos do usuário e geração de link de compartilhamento via WhatsApp.
 */

namespace App\Controller;

use App\Model\FavoritoModel;
use App\Config\Logger;
use Exception;

class FavoritoCtl
{
    private $model;

    public function __construct()
    {
        $this->model = new FavoritoModel();
    }

    public function check(int $idUser, int $idReceita): array
    {
        return ['favorite' => $this->model->isFavorite($idUser, $idReceita)];
    }

    public function add(int $idUser, int $idReceita): array
    {
        try {
            $result = $this->model->addWithIncrement($idUser, $idReceita);

            Logger::info('favorito', 'Receita favoritada', [
                'usuario_id' => $idUser,
                'receita_id' => $idReceita
            ]);

            return $result;
        } catch (Exception $e) {

            Logger::error('favorito', 'Erro ao favoritar receita', [
                'usuario_id' => $idUser,
                'receita_id' => $idReceita,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function remove(int $idUser, int $idReceita): array
    {
        try {
            $result = $this->model->removeWithDecrement($idUser, $idReceita);

            Logger::info('favorito', 'Receita desfavoritada', [
                'usuario_id' => $idUser,
                'receita_id' => $idReceita
            ]);

            return $result;
        } catch (Exception $e) {

            Logger::error('favorito', 'Erro ao desfavoritar receita', [
                'usuario_id' => $idUser,
                'receita_id' => $idReceita,
                'error' => $e->getMessage()
            ]);

            return ['success' => false];
        }
    }

    public const STATUS_VALIDOS = ['quero_fazer', 'ja_fiz'];
    public const NOTA_MAX = 1000;

    /**
     * Atualiza status e/ou nota pessoal. Só os campos presentes em $campos
     * mudam (marcar status não apaga a nota e vice-versa). Se a receita
     * ainda não estiver salva, ela é favoritada antes (status/nota vivem no favorito).
     */
    public function atualizarExtras(int $idUser, int $idReceita, array $campos): array
    {
        $dados = [];

        if (array_key_exists('status', $campos)) {
            $status = ($campos['status'] === '' || $campos['status'] === null) ? null : (string) $campos['status'];
            if ($status !== null && !in_array($status, self::STATUS_VALIDOS, true)) {
                return ['success' => false, 'error' => 'Status inválido'];
            }
            $dados['status'] = $status;
        }

        if (array_key_exists('nota', $campos)) {
            $nota = trim((string) ($campos['nota'] ?? ''));
            if (mb_strlen($nota) > self::NOTA_MAX) {
                return ['success' => false, 'error' => 'Nota muito longa (máx. ' . self::NOTA_MAX . ' caracteres)'];
            }
            $dados['nota'] = $nota === '' ? null : $nota;
        }

        if (!$dados) return ['success' => false, 'error' => 'Nada para atualizar'];

        try {
            $favoritou = false;
            if (!$this->model->isFavorite($idUser, $idReceita)) {
                $this->model->addWithIncrement($idUser, $idReceita);
                $favoritou = true;
            }
            $this->model->updateExtras($idUser, $idReceita, $dados);
            return ['success' => true, 'favoritou' => $favoritou];
        } catch (Exception $e) {
            Logger::error('favorito', 'Erro ao salvar status/nota', [
                'usuario_id' => $idUser, 'receita_id' => $idReceita, 'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => 'Erro ao salvar'];
        }
    }

    public function listByUser(int $idUser): array
    {
        return $this->model->listByUser($idUser);
    }

    public function getLinkWhatsApp(array $ingredientes, string $link = ''): string
    {
        $texto = "Olá! Gostaria de compartilhar os ingredientes dessa receita com você:\n\n";

        foreach ($ingredientes as $grupo) {
            $texto .= $grupo['titulo'] . ":\n";
            foreach ($grupo['itens'] as $item) {
                $texto .= "- " . $item . "\n";
            }
            $texto .= "\n";
        }

        if (!empty($link)) {
            $texto .= "Veja a receita completa no BraFood:\n" . $link . "\n\n";
        }

        return "https://wa.me/?text=" . urlencode($texto);
    }
}
