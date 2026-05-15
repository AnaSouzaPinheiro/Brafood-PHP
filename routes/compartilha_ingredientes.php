<?php

require_once __DIR__ . '/../config/core.php';

use App\Controller\FavoritoCtl;

$controller = new FavoritoCtl();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não suportado']);
    exit;
}

if (!isset($_GET['ingredientes'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetro ingredientes ausente']);
    exit;
}

$ingredientes = json_decode($_GET['ingredientes'], true);
$link         = $_GET['link'] ?? '';

echo json_encode($controller->getLinkWhatsApp($ingredientes, $link));
