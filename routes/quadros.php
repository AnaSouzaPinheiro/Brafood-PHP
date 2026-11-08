<?php

require_once __DIR__ . '/../config/core.php';

use App\Config\Auth;
use App\Controller\QuadroCtl;

// Quadros são sempre do usuário autenticado — o idUser nunca vem do cliente.
$usuarioLogado = Auth::requireAuth();
$idUser        = (int) $usuarioLogado['id'];

$controller = new QuadroCtl();
$method     = $_SERVER['REQUEST_METHOD'];
$data       = in_array($method, ['POST', 'PUT', 'DELETE'], true)
    ? (json_decode(file_get_contents('php://input'), true) ?? [])
    : [];

function responder(array $res, int $erro = 400): void
{
    if (!($res['success'] ?? false)) http_response_code($erro);
    echo json_encode($res);
}

switch ($method) {
    case 'GET':
        if (isset($_GET['compras'])) {
            // Lista de compras: ?compras={idQuadro}&ids=1,2,3
            $ids = array_filter(explode(',', (string) ($_GET['ids'] ?? '')));
            $res = $controller->listaDeCompras($idUser, (int) $_GET['compras'], $ids);
            if ($res === null) {
                http_response_code(404);
                echo json_encode(['error' => 'Quadro não encontrado']);
                break;
            }
            echo json_encode($res);
        } elseif (isset($_GET['idReceita'])) {
            // Em quais quadros esta receita está (menu "Salvar em…")
            echo json_encode($controller->estadoDaReceita($idUser, (int) $_GET['idReceita']));
        } elseif (isset($_GET['id'])) {
            $res = $controller->detalhe($idUser, (int) $_GET['id']);
            if (!$res) {
                http_response_code(404);
                echo json_encode(['error' => 'Quadro não encontrado']);
                break;
            }
            echo json_encode($res);
        } else {
            echo json_encode($controller->listar($idUser));
        }
        break;

    case 'POST':
        $ids = is_array($data['idsReceitas'] ?? null) ? $data['idsReceitas'] : [];
        if (isset($data['idReceita'])) $ids[] = $data['idReceita'];

        if (isset($data['idQuadro'])) {
            if (!$ids) {
                responder(['success' => false, 'error' => 'Dados incompletos']);
                break;
            }
            responder($controller->adicionarVarias($idUser, (int) $data['idQuadro'], $ids));
        } else {
            responder($controller->criar($idUser, $data['nome'] ?? '', $data['descricao'] ?? null, $ids));
        }
        break;

    case 'PUT':
        // Ordem dos quadros: { ordem: [ids] }
        if (is_array($data['ordem'] ?? null)) {
            responder($controller->reordenarQuadros($idUser, $data['ordem']));
            break;
        }
        // Ordem das receitas: { idQuadro, ordemReceitas: [ids] }
        if (isset($data['idQuadro']) && is_array($data['ordemReceitas'] ?? null)) {
            responder($controller->reordenarReceitas($idUser, (int) $data['idQuadro'], $data['ordemReceitas']), 404);
            break;
        }
        // Compartilhar: { id, publico: true|false }
        if (isset($data['id']) && array_key_exists('publico', $data)) {
            responder($controller->definirPublico($idUser, (int) $data['id'], (bool) $data['publico']), 404);
            break;
        }
        if (!isset($data['id'])) {
            responder(['success' => false, 'error' => 'Dados incompletos']);
            break;
        }
        responder($controller->editar($idUser, (int) $data['id'], $data['nome'] ?? '', $data['descricao'] ?? null));
        break;

    case 'DELETE':
        if (isset($data['idQuadro']) && is_array($data['idsReceitas'] ?? null)) {
            responder($controller->removerVarias($idUser, (int) $data['idQuadro'], $data['idsReceitas']));
        } elseif (isset($data['idQuadro'], $data['idReceita'])) {
            responder($controller->removerReceita($idUser, (int) $data['idQuadro'], (int) $data['idReceita']));
        } elseif (isset($data['id'])) {
            responder($controller->excluir($idUser, (int) $data['id']), 404);
        } else {
            responder(['success' => false, 'error' => 'Dados incompletos']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método não suportado']);
}
