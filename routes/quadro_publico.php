<?php

/**
 * Quadros compartilhados — SEM login (só leitura).
 *   GET ?token={token}     → quadro público + autor + receitas
 *   GET ?apelido={apelido} → quadros públicos de um usuário (perfil público)
 * Status e nota pessoal nunca saem por aqui.
 */

require_once __DIR__ . '/../config/core.php';

use App\Controller\QuadroCtl;
use App\Model\PerfilPublicoModel;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não suportado']);
    exit;
}

$controller = new QuadroCtl();

try {
    if (isset($_GET['token'])) {
        $res = $controller->publico((string) $_GET['token']);
        if (!$res) {
            http_response_code(404);
            echo json_encode(['error' => 'Quadro não encontrado ou privado'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $apelido = trim((string) ($_GET['apelido'] ?? ''));
    if ($apelido !== '') {
        $idUser = (new PerfilPublicoModel())->buscarIdPorApelido($apelido);
        echo json_encode($idUser ? $controller->publicosDoUsuario($idUser) : [], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Parâmetro ausente']);
} catch (\Throwable $e) {
    http_response_code(500);
    \App\Config\Logger::error('quadro', 'Erro no quadro público', ['erro' => $e->getMessage()]);
    echo json_encode(['error' => 'Erro interno']);
}
