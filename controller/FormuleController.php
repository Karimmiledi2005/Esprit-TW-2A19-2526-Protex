<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers/RoleHelper.php';

require_once __DIR__ . '/../connexion.php';
require_once __DIR__ . '/../model/Formule.php';

class FormuleController
{
    private PDO $db;

    public function __construct()
    {
        if (!RoleHelper::getUserId()) {
            header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/view/FrontOffice/login.php');
            exit;
        }
        if (!RoleHelper::isSuperAdmin() && !RoleHelper::isAdminAgence()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accès réservé aux administrateurs.']);
            exit;
        }
        $this->db = config::getConnexion();
    }

    private function baseSelect(string $where = '', string $order = 'ORDER BY f.id_formule DESC'): string
    {
        return "
            SELECT
                f.*,
                c.nom_categorie,
                COUNT(DISTINCT fg.id_garantie) AS nb_garanties,
                COUNT(DISTINCT ct.id_contrat) AS nb_contrats
            FROM formule f
            LEFT JOIN categorie c ON f.id_categorie = c.id_categorie
            LEFT JOIN formule_garantie fg ON f.id_formule = fg.id_formule
            LEFT JOIN contrat ct ON f.id_formule = ct.id_formule
            $where
            GROUP BY
                f.id_formule,
                f.nom_formule,
                f.description_formule,
                f.prix_formule,
                f.franchise_formule,
                f.niveau_formule,
                f.id_categorie,
                c.nom_categorie
            $order
        ";
    }

    public function listFormules()
    {
        try {
            return $this->db->query($this->baseSelect());
        } catch (Exception $e) {
            error_log('FormuleController::listFormules error: ' . $e->getMessage());
            throw new Exception('Erreur interne lors de la récupération des formules.');
        }
    }

    public function listFormulesArray(): array
    {
        $stmt = $this->listFormules();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listFormulesByCategorie($id_categorie): array
    {
        try {
            $sql = $this->baseSelect(
                'WHERE f.id_categorie = :id_categorie',
                'ORDER BY f.id_formule DESC'
            );

            $query = $this->db->prepare($sql);
            $query->execute([
                'id_categorie' => (int)$id_categorie
            ]);

            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('FormuleController::listFormulesByCategorie error: ' . $e->getMessage());
            throw new Exception('Erreur interne lors de la récupération des formules par catégorie.');
        }
    }

    public function searchFormules(string $keyword): array
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return $this->listFormulesArray();
        }

        try {
            $sql = $this->baseSelect(
                "WHERE f.nom_formule LIKE :keyword
                    OR f.description_formule LIKE :keyword
                    OR f.niveau_formule LIKE :keyword
                    OR c.nom_categorie LIKE :keyword",
                'ORDER BY f.id_formule DESC'
            );

            $query = $this->db->prepare($sql);
            $query->execute([
                'keyword' => '%' . $keyword . '%'
            ]);

            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('FormuleController::searchFormules error: ' . $e->getMessage());
            throw new Exception('Erreur interne lors de la recherche de formules.');
        }
    }

    public function getFormulesSortedByPrix(string $order = 'ASC'): array
    {
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        try {
            $stmt = $this->db->query(
                $this->baseSelect('', "ORDER BY f.prix_formule $order")
            );

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('FormuleController::getFormulesSortedByPrix error: ' . $e->getMessage());
            throw new Exception('Erreur interne lors du tri des formules par prix.');
        }
    }

    public function getFormulesSortedByFranchise(string $order = 'ASC'): array
    {
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        try {
            $stmt = $this->db->query(
                $this->baseSelect('', "ORDER BY f.franchise_formule $order")
            );

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('FormuleController::getFormulesSortedByFranchise error: ' . $e->getMessage());
            throw new Exception('Erreur interne lors du tri des formules par franchise.');
        }
    }

    public function addFormule($formule): bool
    {
        $sql = "INSERT INTO formule (
                    nom_formule,
                    description_formule,
                    prix_formule,
                    franchise_formule,
                    niveau_formule,
                    id_categorie
                ) VALUES (
                    :nom_formule,
                    :description_formule,
                    :prix_formule,
                    :franchise_formule,
                    :niveau_formule,
                    :id_categorie
                )";

        try {
            $query = $this->db->prepare($sql);
            return $query->execute([
                'nom_formule' => $formule->getNomFormule(),
                'description_formule' => $formule->getDescriptionFormule(),
                'prix_formule' => $formule->getPrixFormule(),
                'franchise_formule' => method_exists($formule, 'getFranchiseFormule') ? $formule->getFranchiseFormule() : 0,
                'niveau_formule' => $formule->getNiveauFormule(),
                'id_categorie' => $formule->getIdCategorie()
            ]);
        } catch (Exception $e) {
            error_log('FormuleController::addFormule error: ' . $e->getMessage());
            throw new Exception('Erreur interne lors de l\'ajout de la formule.');
        }
    }

    public function showFormule($id)
    {
        $sql = "
            SELECT
                f.*,
                c.nom_categorie,
                COUNT(DISTINCT fg.id_garantie) AS nb_garanties,
                COUNT(DISTINCT ct.id_contrat) AS nb_contrats
            FROM formule f
            LEFT JOIN categorie c ON f.id_categorie = c.id_categorie
            LEFT JOIN formule_garantie fg ON f.id_formule = fg.id_formule
            LEFT JOIN contrat ct ON f.id_formule = ct.id_formule
            WHERE f.id_formule = :id
            GROUP BY
                f.id_formule,
                f.nom_formule,
                f.description_formule,
                f.prix_formule,
                f.franchise_formule,
                f.niveau_formule,
                f.id_categorie,
                c.nom_categorie
            LIMIT 1
        ";

        try {
            $query = $this->db->prepare($sql);
            $query->execute(['id' => (int)$id]);
            return $query->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('FormuleController::showFormule error: ' . $e->getMessage());
            throw new Exception('Erreur interne lors de l\'affichage de la formule.');
        }
    }

    public function updateFormule($id, $formule): bool
    {
        $sql = "UPDATE formule
                SET nom_formule = :nom_formule,
                    description_formule = :description_formule,
                    prix_formule = :prix_formule,
                    franchise_formule = :franchise_formule,
                    niveau_formule = :niveau_formule,
                    id_categorie = :id_categorie
                WHERE id_formule = :id";

        try {
            $query = $this->db->prepare($sql);
            return $query->execute([
                'id' => (int)$id,
                'nom_formule' => $formule->getNomFormule(),
                'description_formule' => $formule->getDescriptionFormule(),
                'prix_formule' => $formule->getPrixFormule(),
                'franchise_formule' => method_exists($formule, 'getFranchiseFormule') ? $formule->getFranchiseFormule() : 0,
                'niveau_formule' => $formule->getNiveauFormule(),
                'id_categorie' => $formule->getIdCategorie()
            ]);
        } catch (Exception $e) {
            error_log('FormuleController::updateFormule error: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteFormule($id): bool
    {
        $sql = "DELETE FROM formule WHERE id_formule = :id";

        try {
            $query = $this->db->prepare($sql);
            return $query->execute(['id' => (int)$id]);
        } catch (Exception $e) {
            error_log('FormuleController::deleteFormule error: ' . $e->getMessage());
            return false;
        }
    }

    public function countFormules(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM formule";

        try {
            $query = $this->db->query($sql);
            $row = $query->fetch(PDO::FETCH_ASSOC);
            return (int)$row['total'];
        } catch (Exception $e) {
            error_log('FormuleController::countFormules error: ' . $e->getMessage());
            return 0;
        }
    }

    public function countGarantiesAssociees(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM formule_garantie";

        try {
            $query = $this->db->query($sql);
            $row = $query->fetch(PDO::FETCH_ASSOC);
            return (int)$row['total'];
        } catch (Exception $e) {
            error_log('FormuleController::countGarantiesAssociees error: ' . $e->getMessage());
            return 0;
        }
    }

    public function countGarantiesByFormule($idFormule): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM formule_garantie
            WHERE id_formule = :id
        ";

        try {
            $query = $this->db->prepare($sql);
            $query->execute(['id' => (int)$idFormule]);
            return (int)$query->fetchColumn();
        } catch (Exception $e) {
            error_log('FormuleController::countGarantiesByFormule error: ' . $e->getMessage());
            return 0;
        }
    }

    public function countContratsUsingFormule($idFormule): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM contrat
            WHERE id_formule = :id
        ";

        try {
            $query = $this->db->prepare($sql);
            $query->execute(['id' => (int)$idFormule]);
            return (int)$query->fetchColumn();
        } catch (Exception $e) {
            error_log('FormuleController::countContratsUsingFormule error: ' . $e->getMessage());
            return 0;
        }
    }

    public function canDeleteFormule($idFormule): bool
    {
        return $this->countContratsUsingFormule($idFormule) === 0;
    }

    public function countFormulesUtiliseesParContrats(): int
    {
        $sql = "
            SELECT COUNT(DISTINCT id_formule) AS total
            FROM contrat
            WHERE id_formule IS NOT NULL
        ";

        try {
            $query = $this->db->query($sql);
            $row = $query->fetch(PDO::FETCH_ASSOC);
            return (int)$row['total'];
        } catch (Exception $e) {
            error_log('FormuleController::countFormulesUtiliseesParContrats error: ' . $e->getMessage());
            return 0;
        }
    }
}
