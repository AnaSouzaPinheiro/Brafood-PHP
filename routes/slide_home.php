<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/env.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/SlideHomeModel.php';
require_once __DIR__ . '/../controller/SlideHomeCtl.php';

$db         = (new Database())->getConnection();
$controller = new SlideHomeCtl(new SlideHome($db));
$slides     = $controller->index();

echo json_encode($slides, JSON_UNESCAPED_UNICODE);
