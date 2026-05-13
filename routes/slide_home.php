<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/SlideHomeModel.php';
require_once __DIR__ . '/../controller/SlideHomeCtl.php';

$db         = (new Database())->getConnection();
$controller = new SlideHomeCtl(new SlideHome($db));
$slides     = $controller->index();

echo json_encode($slides, JSON_UNESCAPED_UNICODE);
