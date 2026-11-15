<?php

/**
 * Controla os slides exibidos no banner rotativo da home.
 */

namespace App\Controller;

use App\Model\SlideHomeModel;

class SlideHomeCtl
{
    private $model;

    public function __construct(?SlideHomeModel $model = null)
    {
        $this->model = $model ?? new SlideHomeModel();
    }

    public function index(): array
    {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 5;
        return $this->model->getRandomRecipes($limit);
    }
}
