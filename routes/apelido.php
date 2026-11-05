<?php
/**
 * GET ?verificar=<apelido>  → { valido, disponivel, mensagem }
 * GET ?sugerir=<nome>       → { sugestao }
 *
 * Se a requisição vier autenticada, o próprio apelido do usuário conta
 * como disponível (para a tela de editar perfil).
 */

require_once __DIR__ . '/../config/core.php';

use App\Config\Auth;
use App\Service\ApelidoService;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['mensagem' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$service = new ApelidoService();
$usuario = Auth::usuarioAutenticado();
$meuId   = $usuario ? (int) $usuario['id'] : null;

if (isset($_GET['sugerir'])) {
    echo json_encode(['sugestao' => $service->sugerir((string) $_GET['sugerir'], $meuId)], JSON_UNESCAPED_UNICODE);
    exit;
}

if (isset($_GET['verificar'])) {
    $apelido = trim((string) $_GET['verificar']);
    $erroFormato = ApelidoService::erroDeFormato($apelido);

    if ($erroFormato) {
        echo json_encode(['valido' => false, 'disponivel' => false, 'mensagem' => $erroFormato], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $disponivel = $service->disponivel($apelido, $meuId);
    echo json_encode([
        'valido'     => true,
        'disponivel' => $disponivel,
        'mensagem'   => $disponivel ? 'Apelido disponível.' : 'Este apelido já está em uso.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['mensagem' => 'Informe "verificar" ou "sugerir".'], JSON_UNESCAPED_UNICODE);
