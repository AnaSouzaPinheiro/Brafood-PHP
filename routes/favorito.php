<?php

require_once __DIR__ . '/../config/core.php';

use App\Config\Auth;
use App\Controller\FavoritoCtl;

// Favoritos são sempre do usuário autenticado — o idUser nunca vem do
// cliente, senão dá pra ver/mexer na lista de favoritos de qualquer um.
$usuarioLogado = Auth::requireAuth();
$idUser        = $usuarioLogado['id'];

$controller = new FavoritoCtl();
$method     = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['check'])) {
            echo json_encode(
                $controller->check($idUser, $_GET['idReceita'])
            );
        } else {
            echo json_encode(
                $controller->listByUser($idUser)
            );
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (!isset($data['idReceita'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados incompletos']);
            exit;
        }

        $response = $controller->add($idUser, $data['idReceita']);

        if (!($response['success'] ?? false)) {
            http_response_code(409); // Conflict — já favoritado, ou não encontrado
        }

        echo json_encode($response);
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        // Remoção em massa (seleção na área de Favoritos)
        if (is_array($data['idsReceitas'] ?? null)) {
            $ids = array_slice(array_unique(array_filter(array_map('intval', $data['idsReceitas']))), 0, 200);
            foreach ($ids as $idReceita) {
                $res = $controller->remove($idUser, $idReceita);
                if (!($res['success'] ?? false)) {
                    http_response_code(500);
                    echo json_encode($res);
                    exit;
                }
            }
            echo json_encode(['success' => true]);
            break;
        }

        if (!isset($data['idReceita'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados incompletos']);
            exit;
        }

        $response = $controller->remove($idUser, $data['idReceita']);

        if (!($response['success'] ?? false)) {
            http_response_code(500);
        }

        echo json_encode($response);
        break;

    case 'PUT':
        // Status ("quero_fazer" | "ja_fiz" | null) e nota pessoal
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (!isset($data['idReceita'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados incompletos']);
            exit;
        }

        // Só os campos enviados mudam (status sem apagar a nota e vice-versa)
        $response = $controller->atualizarExtras(
            $idUser,
            (int) $data['idReceita'],
            array_intersect_key($data, ['status' => 1, 'nota' => 1])
        );

        if (!($response['success'] ?? false)) {
            http_response_code(400);
        }

        echo json_encode($response);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método não suportado']);
}
