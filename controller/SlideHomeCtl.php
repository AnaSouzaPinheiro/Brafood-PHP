<?php
require_once __DIR__ . '/../model/SlideHomeModel.php';

class SlideHomeCtl {
    private $model;

    public function __construct() {
        $this->model = new SlideHome();
    }

    public function index() {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 5;
        return $this->model->getRandomRecipes($limit);
    }
}

// Roteamento simples
/*$ctl = new SlideHomeCtl();
header('Content-Type: application/json');
echo json_encode($ctl->index());
*/