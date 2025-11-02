<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../model/ReceitaModel.php';

// Conexão com o banco
$database = new Database();
$db = $database->getConnection();

// Instancia o model
$receitaModel = new ReceitaModel($db);

// Busca as receitas
$stmt = $receitaModel->listarTodas();
$num = $stmt->rowCount();

if ($num > 0) {
    $receitas_arr = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);

        $receitas_arr[] = [
            "id" => intval($id),
            "titulo" => $titulo,
            "categoria" => $categoria ?? "",
            "resumo" => $resumo,
            "dificuldade" => intval($dificuldade),
            "tempo_preparo" => $tempo_preparo,
            "porcoes" => $porcoes,
            "autor" => $autor,
            "data_criacao" => $data_criacao,
            "imagem" => $imagem1,
            "qtd_fav" => intval($qtd_fav)
        ];
    }

    echo json_encode($receitas_arr, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([]);
}
?>
