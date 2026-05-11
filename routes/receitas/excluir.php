<?php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/env.php';

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../controller/ReceitaCtl.php';

$db  = (new Database())->getConnection();
$ctl = new ReceitaCtl($db);

$id = isset($_GET['id']) 
    ? intval($_GET['id']) 
    : 0;

$receita = $ctl->excluirReceita($id);
echo json_encode($receita, JSON_UNESCAPED_UNICODE);