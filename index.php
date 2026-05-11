<?php
header('Content-Type: application/json; charset=UTF-8');
http_response_code(404);
echo json_encode([
    'error'   => 'Rota não encontrada.',
    'message' => 'Acesse as rotas disponíveis em /routes/'
]);
