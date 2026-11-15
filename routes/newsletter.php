<?php

require_once __DIR__ . '/../config/core.php';

use App\Model\NewsletterModel;
use App\Service\RequestThrottle;

header('Content-Type: application/json; charset=utf-8');

$model  = new NewsletterModel();
$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// No máximo 10 inscrições/cancelamentos por hora por IP
if ($method === 'POST' || $method === 'DELETE') {
    RequestThrottle::porIp('newsletter', 10, 3600);
}

try {
    switch ($method) {

        // POST { email } — inscrever
        case 'POST':
            $email = trim($body['email'] ?? '');

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Informe um e-mail válido.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $resultado = $model->inscrever($email);

            if ($resultado === 'ja_inscrito') {
                http_response_code(409);
                echo json_encode([
                    'success' => false,
                    'message' => 'Este e-mail já está inscrito na newsletter.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Inscrição realizada com sucesso!'
            ], JSON_UNESCAPED_UNICODE);
            break;

        // DELETE { email } — cancelar inscrição
        case 'DELETE':
            $email = trim($body['email'] ?? '');

            if (!$email) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'E-mail é obrigatório.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $cancelado = $model->cancelar($email);
            echo json_encode([
                'success' => $cancelado,
                'message' => $cancelado ? 'Inscrição cancelada.' : 'E-mail não encontrado.'
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(405);
            echo json_encode(['message' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => \App\Config\Logger::falha('newsletter', $e)
    ], JSON_UNESCAPED_UNICODE);
}
