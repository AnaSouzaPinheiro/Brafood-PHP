<?php

require_once __DIR__ . '/../config/core.php';

use App\Model\AvaliacaoModel;

$model  = new AvaliacaoModel();
$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

$idUsuario = isset($_GET['id_usuario']) ? (int) $_GET['id_usuario'] : ((int) ($body['id_usuario'] ?? 0));
$idReceita = isset($_GET['id_receita']) ? (int) $_GET['id_receita'] : ((int) ($body['id_receita'] ?? 0));

try {
    switch ($method) {
        // GET ?id_usuario=X&id_receita=Y → nota do usuário + média da receita
        case 'GET':
            if (!$idUsuario || !$idReceita) {
                http_response_code(400);
                echo json_encode(['mensagem' => 'id_usuario e id_receita são obrigatórios.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $nota  = $model->buscarNota($idUsuario, $idReceita);
            $media = $model->buscarMedia($idReceita);
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
    echo json_encode(['mensagem' => 'Erro interno: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
