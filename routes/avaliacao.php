<?php

require_once __DIR__ . '/../config/core.php';

use App\Config\Auth;
use App\Model\AvaliacaoModel;

$model  = new AvaliacaoModel();
$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

$idUsuario = isset($_GET['id_usuario']) ? (int) $_GET['id_usuario'] : ((int) ($body['id_usuario'] ?? 0));
$idReceita = isset($_GET['id_receita']) ? (int) $_GET['id_receita'] : ((int) ($body['id_receita'] ?? 0));

// Salvar ou remover nota: sempre em nome de quem está logado (o id enviado
// no pedido é ignorado, senão daria para avaliar no lugar de outra pessoa)
if ($method === 'POST' || $method === 'DELETE') {
    $usuario   = Auth::requireAuth();
    $idUsuario = (int) $usuario['id'];
}

try {
    switch ($method) {

        // GET ?id_receita=Y → média pública (sempre)
        // GET ?id_usuario=X&id_receita=Y → nota do usuário + média
        case 'GET':
            if (!$idReceita) {
                http_response_code(400);
                echo json_encode(['mensagem' => 'id_receita é obrigatório.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $media = $model->buscarMedia($idReceita);

            // Só busca nota individual se houver usuário logado
            $nota = null;
            if ($idUsuario > 0) {
                $nota = $model->buscarNota($idUsuario, $idReceita);
            }

            echo json_encode([
                'nota'  => $nota,
                'media' => $media['media'],
                'total' => $media['total'],
            ], JSON_UNESCAPED_UNICODE);
            break;

        // POST { id_usuario, id_receita, nota }
        case 'POST':
            $nota = (int) ($body['nota'] ?? 0);
            if (!$idUsuario || !$idReceita || $nota < 1 || $nota > 5) {
                http_response_code(400);
                echo json_encode(['mensagem' => 'id_usuario, id_receita e nota (1-5) são obrigatórios.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $resultado = $model->avaliar($idUsuario, $idReceita, $nota);
            echo json_encode(['success' => true, ...$resultado], JSON_UNESCAPED_UNICODE);
            break;

        // DELETE ?id_usuario=X&id_receita=Y
        case 'DELETE':
            if (!$idUsuario || !$idReceita) {
                http_response_code(400);
                echo json_encode(['mensagem' => 'id_usuario e id_receita são obrigatórios.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $resultado = $model->remover($idUsuario, $idReceita);
            echo json_encode(['success' => true, ...$resultado], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(405);
            echo json_encode(['mensagem' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['mensagem' => \App\Config\Logger::falha('avaliacao', $e)], JSON_UNESCAPED_UNICODE);
}
