<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers/RoleHelper.php';

require_once __DIR__ . '/../connexion.php';
require_once __DIR__ . '/../model/Garantie.php';

class GarantieController
{
    private $db;

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

    public function listGaranties()
    {
        $sql = "
            SELECT
                g.id_garantie,
                g.nom_garantie,
                g.description_garantie,
                g.plafond_couvert_garantie,
                g.id_categorie,
                c.nom_categorie
            FROM garantie g
            LEFT JOIN categorie c ON g.id_categorie = c.id_categorie
            ORDER BY g.id_garantie DESC
        ";

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $garanties = [];

        foreach ($rows as $row) {
            $garantie = new Garantie(
                $row['nom_garantie'],
                $row['description_garantie'],
                (float)$row['plafond_couvert_garantie'],
                $row['id_categorie'] !== null ? (int)$row['id_categorie'] : null
            );

            $garantie->setIdGarantie((int)$row['id_garantie']);
            $garantie->setNomCategorie($row['nom_categorie'] ?? null);

            $garanties[] = $garantie;
        }

        return $garanties;
    }

    public function getAll()
    {
        return $this->listGaranties();
    }

    public function showGarantie($id)
    {
        $sql = "
            SELECT
                g.id_garantie,
                g.nom_garantie,
                g.description_garantie,
                g.plafond_couvert_garantie,
                g.id_categorie,
                c.nom_categorie
            FROM garantie g
            LEFT JOIN categorie c ON g.id_categorie = c.id_categorie
            WHERE g.id_garantie = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => (int)$id
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function addGarantie(Garantie $garantie)
    {
        $sql = "
            INSERT INTO garantie (
                nom_garantie,
                description_garantie,
                plafond_couvert_garantie,
                id_categorie
            ) VALUES (
                :nom_garantie,
                :description_garantie,
                :plafond_couvert_garantie,
                :id_categorie
            )
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'nom_garantie' => $garantie->getNomGarantie(),
            'description_garantie' => $garantie->getDescriptionGarantie(),
            'plafond_couvert_garantie' => $garantie->getPlafond(),
            'id_categorie' => $garantie->getIdCategorie()
        ]);
    }

    public function updateGarantie($id, Garantie $garantie)
    {
        $sql = "
            UPDATE garantie
            SET
                nom_garantie = :nom_garantie,
                description_garantie = :description_garantie,
                plafond_couvert_garantie = :plafond_couvert_garantie,
                id_categorie = :id_categorie
            WHERE id_garantie = :id_garantie
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'nom_garantie' => $garantie->getNomGarantie(),
            'description_garantie' => $garantie->getDescriptionGarantie(),
            'plafond_couvert_garantie' => $garantie->getPlafond(),
            'id_categorie' => $garantie->getIdCategorie(),
            'id_garantie' => (int)$id
        ]);
    }

    public function garantieExists($nom, $idCategorie, $idGarantie = null)
    {
        $sql = "
            SELECT COUNT(*)
            FROM garantie
            WHERE nom_garantie = :nom_garantie
              AND id_categorie = :id_categorie
        ";

        $params = [
            'nom_garantie' => $nom,
            'id_categorie' => (int)$idCategorie
        ];

        if ($idGarantie !== null) {
            $sql .= " AND id_garantie != :id_garantie";
            $params['id_garantie'] = (int)$idGarantie;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }

    public function deleteGarantie($id)
    {
        $stmt = $this->db->prepare("
            DELETE FROM garantie
            WHERE id_garantie = :id
        ");

        return $stmt->execute([
            'id' => (int)$id
        ]);
    }

    public function countGarantiesLieesAuxFormules()
    {
        $sql = "
            SELECT COUNT(DISTINCT id_garantie) AS total
            FROM formule_garantie
        ";

        $stmt = $this->db->query($sql);

        return (int)$stmt->fetchColumn();
    }

    public function getGarantiesSortedByPlafond(string $order = 'ASC')
    {
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "
            SELECT
                g.id_garantie,
                g.nom_garantie,
                g.description_garantie,
                g.plafond_couvert_garantie,
                g.id_categorie,
                c.nom_categorie
            FROM garantie g
            LEFT JOIN categorie c ON g.id_categorie = c.id_categorie
            ORDER BY g.plafond_couvert_garantie $order
        ";

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $garanties = [];

        foreach ($rows as $row) {
            $garantie = new Garantie(
                $row['nom_garantie'],
                $row['description_garantie'],
                (float)$row['plafond_couvert_garantie'],
                $row['id_categorie'] !== null ? (int)$row['id_categorie'] : null
            );

            $garantie->setIdGarantie((int)$row['id_garantie']);
            $garantie->setNomCategorie($row['nom_categorie'] ?? null);

            $garanties[] = $garantie;
        }

        return $garanties;
    }

    public function searchGaranties(string $keyword)
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return $this->listGaranties();
        }

        $sql = "
            SELECT
                g.id_garantie,
                g.nom_garantie,
                g.description_garantie,
                g.plafond_couvert_garantie,
                g.id_categorie,
                c.nom_categorie
            FROM garantie g
            LEFT JOIN categorie c ON g.id_categorie = c.id_categorie
            WHERE g.nom_garantie LIKE :keyword
               OR g.description_garantie LIKE :keyword
               OR c.nom_categorie LIKE :keyword
            ORDER BY g.id_garantie DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'keyword' => '%' . $keyword . '%'
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $garanties = [];

        foreach ($rows as $row) {
            $garantie = new Garantie(
                $row['nom_garantie'],
                $row['description_garantie'],
                (float)$row['plafond_couvert_garantie'],
                $row['id_categorie'] !== null ? (int)$row['id_categorie'] : null
            );

            $garantie->setIdGarantie((int)$row['id_garantie']);
            $garantie->setNomCategorie($row['nom_categorie'] ?? null);
            $garanties[] = $garantie;
        }

        return $garanties;
    }

    public function countFormulesUsingGarantie($idGarantie)
    {
        $sql = "
            SELECT COUNT(*)
            FROM formule_garantie
            WHERE id_garantie = :id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => (int)$idGarantie
        ]);

        return (int)$stmt->fetchColumn();
    }

    public function canDeleteGarantie($idGarantie)
    {
        return $this->countFormulesUsingGarantie($idGarantie) === 0;
    }

}