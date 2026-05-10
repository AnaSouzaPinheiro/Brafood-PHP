<?php
require_once __DIR__ . '/../controller/SlideHomeCtl.php';

//header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$controller = new SlideHomeCtl();
$slides = $controller->index();

echo json_encode($slides, JSON_UNESCAPED_UNICODE);
