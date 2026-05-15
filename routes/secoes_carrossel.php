<?php

require_once __DIR__ . '/../config/core.php';

use App\Controller\SecaoCarrosselCtl;

$ctl    = new SecaoCarrosselCtl();
$secoes = $ctl->index();

echo json_encode($secoes, JSON_UNESCAPED_UNICODE);
