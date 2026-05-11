<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../controller/SlideHomeCtl.php';


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$controller = new SlideHomeCtl();
$slides = $controller->index();

echo json_encode($slides, JSON_UNESCAPED_UNICODE);
