<?php

/**
 * Controla os slides exibidos no banner rotativo da home.
 */

require_once __DIR__ . '/../model/SlideHomeModel.php';

class SlideHomeCtl
{
    private $model;

    public function __construct()
    {
        $this->model = new SlideHome();
    }

    public function index(): array
    {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 5;
        return $this->model->getRandomRecipes($limit);
    }
}
