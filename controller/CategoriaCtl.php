<?php header('Content-Type: application/json');
include_once '../model/CategoriaModel.php';

$categorias = new CategoriaModel($db);
$stmt = $categorias->listarCategorias();

echo json_encode($categorias);

?>