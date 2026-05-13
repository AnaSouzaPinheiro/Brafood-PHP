<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/SecaoCarrossel.php';
require_once __DIR__ . '/../controller/SecaoCarrosselCtl.php';

$db         = (new Database())->getConnection();
$controller = new SecaoCarrosselCtl(new SecaoCarrossel($db));
$secoes     = $controller->index();

echo json_encode($secoes, JSON_UNESCAPED_UNICODE);
