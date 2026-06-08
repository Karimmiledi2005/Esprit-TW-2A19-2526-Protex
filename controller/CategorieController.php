<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers/RoleHelper.php';

require_once __DIR__ . '/../connexion.php';
require_once __DIR__ . '/../model/Categorie.php';

class CategorieController
{
    private PDO $db;

    public function __construct()
    {
        if (!RoleHelper::getUserId()) {
            header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/view/FrontOffice/login.php');
            exit;
        }
        $this->db = config::getConnexion();
    }

    private function baseSelect(string $where = '', string $order = 'ORDER BY c.id_categorie DESC'): string
    {
        return "
            SELECT
                c.*,
                COUNT(DISTINCT ct.id_contrat) AS nb_contrats,
                COUNT(DISTINCT f.id_formule) AS nb_formules,
                COUNT(DISTINCT g.id_garantie) AS nb_garanties
            FROM categorie c
            LEFT JOIN contrat ct ON c.id_categorie = ct.id_categorie
            LEFT JOIN formule f ON c.id_categorie = f.id_categorie
            LEFT JOIN garantie g ON c.id_categorie = g.id_categorie
            $where
            GROUP BY c.id_categorie, c.nom_categorie, c.description_categorie
            $order
        ";
    }

    public function listCategories()
    {
        try {
            return $this->db->query($this->baseSelect());
        } catch (Exception $e) {
            error_log('CategorieController::listCategories error: ' . $e->getMessage());
            throw new Exception('Erreur interne lors de la récupération des catégories.');
        }
    }

    public function listCategoriesArray(): array
    {
        $stmt = $this->listCategories();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchCategories(string $keyword): array
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return $this->listCategoriesArray();
        }

        try {
            $sql = $this->baseSelect(
                'WHERE c.nom_categorie LIKE :keyword OR c.description_categorie LIKE :keyword',
                'ORDER BY c.id_categorie DESC'
            );

            $query = $this->db->prepare($sql);
            $query->execute([
                'keyword' => '%' . $keyword . '%'
            ]);

            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('CategorieController::searchCategories error: ' . $e->getMessage());
            throw new Exception('Erreur interne lors de la recherche de catégories.');
        }
    }

    public function getCategoriesSortedByNom(string $order = 'ASC'): array
    {
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        try {
            $stmt = $this->db->query(
                $this->baseSelect('', "ORDER BY c.nom_categorie $order")
            );

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('CategorieController::getCategoriesSortedByNom error: ' . $e->getMessage());
            throw new Exception('Erreur interne lors du tri des catégories.');
        }
    }

    public function addCategorie($categorie): bool
    {
        $sql = "INSERT INTO categorie (nom_categorie, description_categorie)
                VALUES (:nom_categorie, :description_categorie)";

        try {
            $query = $this->db->prepare($sql);
            return $query->execute([
                'nom_categorie' => $categorie->getNomCategorie(),
                'description_categorie' => $categorie->getDescriptionCategorie()
            ]);
        } catch (Exception $e) {
            error_log('CategorieController::addCategorie error: ' . $e->getMessage());
            throw new Exception('Erreur interne lors de l\'ajout de la catégorie.');
        }
    }

    public function deleteCategorie($id): bool
    {
        $sql = "DELETE FROM categorie WHERE id_categorie = :id";

        try {
            $query = $this->db->prepare($sql);
            return $query->execute(['id' => (int)$id]);
        } catch (Exception $e) {
            error_log('CategorieController::deleteCategorie error: ' . $e->getMessage());
            throw new Exception('Erreur interne lors de la suppression de la catégorie.');
        }
    }

    public function showCategorie($id)
    {
        $sql = $this->baseSelect('WHERE c.id_categorie = :id', 'LIMIT 1');

        try {
            $query = $this->db->prepare($sql);
            $query->execute(['id' => (int)$id]);
            return $query->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('CategorieController::showCategorie error: ' . $e->getMessage());
            return null;
        }
    }

    public function updateCategorie($id, $categorie): bool
    {
        $sql = "UPDATE categorie
                SET nom_categorie = :nom_categorie,
                    description_categorie = :description_categorie
                WHERE id_categorie = :id";

        try {
            $query = $this->db->prepare($sql);
            return $query->execute([
                'id' => (int)$id,
                'nom_categorie' => $categorie->getNomCategorie(),
                'description_categorie' => $categorie->getDescriptionCategorie()
            ]);
        } catch (Exception $e) {
            error_log('CategorieController::updateCategorie error: ' . $e->getMessage());
            return false;
        }
    }

    public function countCategories(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM categorie";

        try {
            $query = $this->db->query($sql);
            $row = $query->fetch(PDO::FETCH_ASSOC);
            return (int)$row['total'];
        } catch (Exception $e) {
            error_log('CategorieController::countCategories error: ' . $e->getMessage());
            return 0;
        }
    }

    public function countGarantiesLiees(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM garantie WHERE id_categorie IS NOT NULL";

        try {
            $query = $this->db->query($sql);
            $row = $query->fetch(PDO::FETCH_ASSOC);
            return (int)$row['total'];
        } catch (Exception $e) {
            error_log('CategorieController::countGarantiesLiees error: ' . $e->getMessage());
            return 0;
        }
    }

    public function countContratsLiees(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM contrat WHERE id_categorie IS NOT NULL";

        try {
            $query = $this->db->query($sql);
            $row = $query->fetch(PDO::FETCH_ASSOC);
            return (int)$row['total'];
        } catch (Exception $e) {
            error_log('CategorieController::countContratsLiees error: ' . $e->getMessage());
            return 0;
        }
    }

    public function countFormulesLiees(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM formule WHERE id_categorie IS NOT NULL";

        try {
            $query = $this->db->query($sql);
            $row = $query->fetch(PDO::FETCH_ASSOC);
            return (int)$row['total'];
        } catch (Exception $e) {
            error_log('CategorieController::countFormulesLiees error: ' . $e->getMessage());
            return 0;
        }
    }

    public function countRelationsByCategorie($idCategorie): array
    {
        $sql = "
            SELECT
                (SELECT COUNT(*) FROM contrat WHERE id_categorie = :id1) AS nb_contrats,
                (SELECT COUNT(*) FROM formule WHERE id_categorie = :id2) AS nb_formules,
                (SELECT COUNT(*) FROM garantie WHERE id_categorie = :id3) AS nb_garanties
        ";

        try {
            $query = $this->db->prepare($sql);
            $query->execute([
                'id1' => (int)$idCategorie,
                'id2' => (int)$idCategorie,
                'id3' => (int)$idCategorie
            ]);

            $row = $query->fetch(PDO::FETCH_ASSOC) ?: [];

            return [
                'nb_contrats' => (int)($row['nb_contrats'] ?? 0),
                'nb_formules' => (int)($row['nb_formules'] ?? 0),
                'nb_garanties' => (int)($row['nb_garanties'] ?? 0)
            ];
        } catch (Exception $e) {
            error_log('CategorieController::countRelationsByCategorie error: ' . $e->getMessage());
            return ['nb_contrats' => 0, 'nb_formules' => 0, 'nb_garanties' => 0];
        }
    }

    public function canDeleteCategorie($idCategorie): bool
    {
        $relations = $this->countRelationsByCategorie($idCategorie);

        return $relations['nb_contrats'] === 0
            && $relations['nb_formules'] === 0
            && $relations['nb_garanties'] === 0;
    }
}
