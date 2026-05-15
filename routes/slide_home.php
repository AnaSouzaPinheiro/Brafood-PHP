<?php

require_once __DIR__ . '/../config/core.php';

use App\Controller\SlideHomeCtl;

$ctl    = new SlideHomeCtl();
$slides = $ctl->index();

echo json_encode($slides, JSON_UNESCAPED_UNICODE);
