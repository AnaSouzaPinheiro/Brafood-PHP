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
