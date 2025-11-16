<?php 

header('Content-Type: application/json');
include_once '../model/ReceitaModel.php';

$receitas = new ReceitaModel($db);
$stmt = $receitas->listarTodas();

echo json_encode($receitas);

?>