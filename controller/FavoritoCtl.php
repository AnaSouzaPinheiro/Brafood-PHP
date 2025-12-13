<?php
class FavoritoCtl
{
    private $model;
    private $db;

    public function __construct($db)
    {
        require_once __DIR__ . '/../model/FavoritoModel.php';
        $this->model = new FavoritoModel($db);
        $this->db = $db;
    }

    public function check($idUser, $idReceita)
    {
        return ['favorite' => $this->model->isFavorite($idUser, $idReceita)];
    }

    public function add($idUser, $idReceita)
    {
        try {
            $this->db->beginTransaction();

            $this->model->add($idUser, $idReceita);

            $sql = "UPDATE receitas 
                    SET qtd_fav = qtd_fav + 1 
                    WHERE id = :idReceita";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':idReceita' => $idReceita]);

            $this->db->commit();

            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function remove($idUser, $idReceita)
    {
        try {
            $this->db->beginTransaction();

            $this->model->remove($idUser, $idReceita);

            $sql = "UPDATE receitas 
                    SET qtd_fav = GREATEST(qtd_fav - 1, 0)
                    WHERE id = :idReceita";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':idReceita' => $idReceita]);

            $this->db->commit();

            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false];
        }
    }

    public function listByUser($idUser)
    {
        return $this->model->listByUser($idUser);
    }
}
