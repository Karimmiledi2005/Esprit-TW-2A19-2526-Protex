<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers/RoleHelper.php';
require_once __DIR__ . '/../model/Offre.php';

require_once __DIR__ . '/../helpers/SessionGuard.php';
SessionGuard::requireBackoffice();

class OffreController
{
    private PDO $db;
    private string $baseUrl;

    public function __construct()
    {
        $this->db = config::getConnexion();

        if (!defined('BASE_URL')) {
            define('BASE_URL', (defined('BASE_URL') ? BASE_URL : ''));
        }

        $this->baseUrl = BASE_URL . '/controller/OffreController.php';
    }

    private function redirectToIndex(string $message = '', string $erreur = ''): void
    {
        $params = ['action' => 'index'];

        if ($message !== '') {
            $params['message'] = $message;
        }

        if ($erreur !== '') {
            $params['erreur'] = $erreur;
        }

        header('Location: ' . $this->baseUrl . '?' . http_build_query($params));
        exit;
    }

    public function avis(): void
    {
        require_once __DIR__ . '/../view/BackOffice/offres/avis.php';
    }

    public function toggleAvis(): void
    {
        if (!RoleHelper::isAdmin() && !RoleHelper::isSuperAdmin()) {
            header('Location: ' . $this->baseUrl . '?action=avis&erreur=' . urlencode('Accès refusé'));
            exit;
        }
        $id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $hidden = isset($_GET['hidden']) ? (int)$_GET['hidden'] : 1;

        if ($id <= 0) {
            header('Location: ' . $this->baseUrl . '?action=avis&erreur=' . urlencode('Identifiant invalide'));
            exit;
        }

        try {
            $stmt = $this->db->prepare("UPDATE avis_offre SET hidden = ? WHERE id = ?");
            $stmt->execute([$hidden, $id]);
            $msg = $hidden ? 'Avis masqué' : 'Avis rendu visible';
            header('Location: ' . $this->baseUrl . '?action=avis&message=' . urlencode($msg));
            exit;
        } catch (Exception $e) {
            header('Location: ' . $this->baseUrl . '?action=avis&erreur=' . urlencode('Erreur : ' . $e->getMessage()));
            exit;
        }
    }

    public function deleteAvis(): void
    {
        if (!RoleHelper::isAdmin() && !RoleHelper::isSuperAdmin()) {
            header('Location: ' . $this->baseUrl . '?action=avis&erreur=' . urlencode('Accès refusé'));
            exit;
        }
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            header('Location: ' . $this->baseUrl . '?action=avis&erreur=' . urlencode('Identifiant invalide'));
            exit;
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM avis_offre WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: ' . $this->baseUrl . '?action=avis&message=' . urlencode('Avis supprimé définitivement'));
            exit;
        } catch (Exception $e) {
            header('Location: ' . $this->baseUrl . '?action=avis&erreur=' . urlencode('Erreur : ' . $e->getMessage()));
            exit;
        }
    }

    public function index(): void
    {
        $message = $_GET['message'] ?? '';
        $erreur  = $_GET['erreur'] ?? '';

        try {
            $stmt = $this->db->query("
                SELECT o.*,
                       (SELECT COUNT(*) FROM devis d WHERE d.id_offre = o.id_offre) AS nb_devis,
                       (SELECT COUNT(*) FROM contrat c WHERE c.id_offre = o.id_offre) AS nb_contrats,
                       (SELECT COALESCE(SUM(p.montant), 0) FROM paiement p WHERE p.id_offre = o.id_offre AND p.statut = 'valide') AS revenu_valide,
                       (SELECT AVG(note) FROM avis_offre a WHERE a.id_offre = o.id_offre AND a.hidden = 0) AS note_moyenne
                FROM offre o
                ORDER BY o.date_creation DESC
            ");
            $offres = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch monthly subscription data for line chart
            $stmtMonthly = $this->db->query("
                SELECT o.nom_offre, DATE_FORMAT(c.date_souscription, '%Y-%m') AS mois, COUNT(*) AS nb
                FROM contrat c
                JOIN offre o ON c.id_offre = o.id_offre
                WHERE c.date_souscription >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY o.nom_offre, mois
                ORDER BY mois ASC
            ");
            $monthlyStats = $stmtMonthly->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('OffreController::getOffres error: ' . $e->getMessage());
            $offres = [];
            $monthlyStats = [];
        }

        try {
            $stmtStats = $this->db->query("
                SELECT
                    COUNT(*) AS total,
                    SUM(statut = 'active') AS actives,
                    SUM(statut = 'suspendue') AS suspendues,
                    SUM(statut = 'archivee') AS archivees
                FROM offre
            ");
            $stats = $stmtStats->fetch() ?: [];
        } catch (Exception $e) {
            $stats = [];
        }

        require_once __DIR__ . '/../view/BackOffice/offres/liste.php';
    }

    public function ajouter(): void
    {
        $errors = [];
        $old    = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old    = $this->sanitizePost($_POST);
            $errors = $this->valider($_POST);

            if (empty($errors)) {
                $offre = new Offre(
                    null,
                    trim($_POST['nom_offre']),
                    trim($_POST['type_offre']),
                    trim($_POST['description']),
                    round((float)$_POST['prix_mensuel'], 3),
                    round((float)$_POST['prix_annuel'], 3),
                    trim($_POST['couverture']),
                    (isset($_POST['plafond']) && $_POST['plafond'] !== '')
                        ? round((float)$_POST['plafond'], 3)
                        : null,
                    (int)$_POST['duree_min'],
                    $_POST['statut'] ?? 'active'
                );

                try {
                    $stmt = $this->db->prepare("
                        INSERT INTO offre
                        (
                            nom_offre, type_offre, description, prix_mensuel, prix_annuel, couverture, plafond, duree_min, statut,
                            date_promo_debut, date_promo_fin, remise_promo
                        )
                        VALUES
                        (
                            :nom_offre, :type_offre, :description, :prix_mensuel, :prix_annuel, :couverture, :plafond, :duree_min, :statut,
                            :date_promo_debut, :date_promo_fin, :remise_promo
                        )
                    ");

                    $dateDebut = !empty($_POST['date_promo_debut']) ? $_POST['date_promo_debut'] : null;
                    $dateFin = !empty($_POST['date_promo_fin']) ? $_POST['date_promo_fin'] : null;
                    $remise = !empty($_POST['remise_promo']) ? (int)$_POST['remise_promo'] : 0;

                    $stmt->execute([
                        ':nom_offre'    => $offre->getNomOffre(),
                        ':type_offre'   => $offre->getTypeOffre(),
                        ':description'  => $offre->getDescription(),
                        ':prix_mensuel' => $offre->getPrixMensuel(),
                        ':prix_annuel'  => $offre->getPrixAnnuel(),
                        ':couverture'   => $offre->getCouverture(),
                        ':plafond'      => $offre->getPlafond(),
                        ':duree_min'    => $offre->getDureeMin(),
                        ':statut'       => $offre->getStatut(),
                        ':date_promo_debut' => $dateDebut,
                        ':date_promo_fin' => $dateFin,
                        ':remise_promo' => $remise,
                    ]);

                    $this->redirectToIndex('Offre ajoutée avec succès');
                } catch (Exception $e) {
                    $errors['general'] = 'Erreur lors de l\'ajout : ' . $e->getMessage();
                }
            }
        }

        require_once __DIR__ . '/../view/BackOffice/offres/ajouter.php';
    }

    public function modifier(): void
    {
        $id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $errors = [];
        $old    = [];

        if ($id <= 0) {
            $this->redirectToIndex('', 'Identifiant invalide');
        }

        $stmt = $this->db->prepare("SELECT * FROM offre WHERE id_offre = ?");
        $stmt->execute([$id]);
        $offre = $stmt->fetch();

        if (!$offre) {
            $this->redirectToIndex('', 'Offre introuvable');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old    = $this->sanitizePost($_POST);
            $errors = $this->valider($_POST);

            if (empty($errors)) {
                $offreObj = new Offre(
                    $id,
                    trim($_POST['nom_offre']),
                    trim($_POST['type_offre']),
                    trim($_POST['description']),
                    round((float)$_POST['prix_mensuel'], 3),
                    round((float)$_POST['prix_annuel'], 3),
                    trim($_POST['couverture']),
                    (isset($_POST['plafond']) && $_POST['plafond'] !== '')
                        ? round((float)$_POST['plafond'], 3)
                        : null,
                    (int)$_POST['duree_min'],
                    $_POST['statut'] ?? 'active'
                );

                try {
                    $stmtUpdate = $this->db->prepare("
                        UPDATE offre SET
                            nom_offre    = :nom_offre,
                            type_offre   = :type_offre,
                            description  = :description,
                            prix_mensuel = :prix_mensuel,
                            prix_annuel  = :prix_annuel,
                            couverture   = :couverture,
                            plafond      = :plafond,
                            duree_min    = :duree_min,
                            statut       = :statut,
                            date_promo_debut = :date_promo_debut,
                            date_promo_fin = :date_promo_fin,
                            remise_promo = :remise_promo
                        WHERE id_offre = :id_offre
                    ");

                    $dateDebut = !empty($_POST['date_promo_debut']) ? $_POST['date_promo_debut'] : null;
                    $dateFin = !empty($_POST['date_promo_fin']) ? $_POST['date_promo_fin'] : null;
                    $remise = !empty($_POST['remise_promo']) ? (int)$_POST['remise_promo'] : 0;

                    $stmtUpdate->execute([
                        ':nom_offre'    => $offreObj->getNomOffre(),
                        ':type_offre'   => $offreObj->getTypeOffre(),
                        ':description'  => $offreObj->getDescription(),
                        ':prix_mensuel' => $offreObj->getPrixMensuel(),
                        ':prix_annuel'  => $offreObj->getPrixAnnuel(),
                        ':couverture'   => $offreObj->getCouverture(),
                        ':plafond'      => $offreObj->getPlafond(),
                        ':duree_min'    => $offreObj->getDureeMin(),
                        ':statut'       => $offreObj->getStatut(),
                        ':date_promo_debut' => $dateDebut,
                        ':date_promo_fin' => $dateFin,
                        ':remise_promo' => $remise,
                        ':id_offre'     => $offreObj->getIdOffre(),
                    ]);

                    $this->redirectToIndex('Offre modifiée avec succès');
                } catch (Exception $e) {
                    $errors['general'] = 'Erreur lors de la modification : ' . $e->getMessage();
                }
            }
        }

        require_once __DIR__ . '/../view/BackOffice/offres/modifier.php';
    }

    public function supprimer(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            $this->redirectToIndex('', 'Identifiant invalide');
        }

        $stmt = $this->db->prepare("SELECT * FROM offre WHERE id_offre = ?");
        $stmt->execute([$id]);
        $offre = $stmt->fetch();

        if (!$offre) {
            $this->redirectToIndex('', 'Offre introuvable');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $checkP = $this->db->prepare("SELECT COUNT(*) FROM paiement WHERE id_offre = ?");
                $checkP->execute([$id]);
                $countP = (int)$checkP->fetchColumn();

                $checkC = $this->db->prepare("SELECT COUNT(*) FROM contrat WHERE id_offre = ?");
                $checkC->execute([$id]);
                $countC = (int)$checkC->fetchColumn();

                if ($countP > 0 || $countC > 0) {
                    $archive = $this->db->prepare("UPDATE offre SET statut = 'archivee' WHERE id_offre = ?");
                    $archive->execute([$id]);
                    $this->redirectToIndex('Offre archivée (paiements liés)');
                } else {
                    $delete = $this->db->prepare("DELETE FROM offre WHERE id_offre = ?");
                    $delete->execute([$id]);
                    $this->redirectToIndex('Offre supprimée avec succès');
                }
            } catch (Exception $e) {
                $this->redirectToIndex('', 'Erreur : ' . $e->getMessage());
            }
        }

        require_once __DIR__ . '/../view/BackOffice/offres/supprimer.php';
    }

    public function changerStatut(): void
    {
        $id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $statut = isset($_GET['statut']) ? trim((string)$_GET['statut']) : '';

        $statuts_valides = ['active', 'suspendue', 'archivee'];

        if ($id <= 0) {
            $this->redirectToIndex('', 'Identifiant invalide');
        }

        if (!in_array($statut, $statuts_valides, true)) {
            $this->redirectToIndex('', 'Statut invalide');
        }

        $stmt = $this->db->prepare("SELECT id_offre FROM offre WHERE id_offre = ?");
        $stmt->execute([$id]);

        if (!$stmt->fetch()) {
            $this->redirectToIndex('', 'Offre introuvable');
        }

        try {
            $update = $this->db->prepare("UPDATE offre SET statut = ? WHERE id_offre = ?");
            $update->execute([$statut, $id]);

            $labels = [
                'active'     => 'activée',
                'suspendue'  => 'suspendue',
                'archivee'   => 'archivée'
            ];

            $this->redirectToIndex('Offre ' . ($labels[$statut] ?? 'modifiée') . ' avec succès');
        } catch (Exception $e) {
            $this->redirectToIndex('', 'Erreur : ' . $e->getMessage());
        }
    }

    private function valider(array $data): array
    {
        $errors = [];

        $nom_offre    = trim((string)($data['nom_offre'] ?? ''));
        $type_offre   = trim((string)($data['type_offre'] ?? ''));
        $description  = trim((string)($data['description'] ?? ''));
        $couverture   = trim((string)($data['couverture'] ?? ''));
        $prix_mensuel = trim((string)($data['prix_mensuel'] ?? ''));
        $prix_annuel  = trim((string)($data['prix_annuel'] ?? ''));
        $plafond      = trim((string)($data['plafond'] ?? ''));
        $duree_min    = trim((string)($data['duree_min'] ?? ''));
        $statut       = trim((string)($data['statut'] ?? ''));

        if ($nom_offre === '') {
            $errors['nom_offre'] = 'Le nom de l\'offre est obligatoire.';
        } elseif (mb_strlen($nom_offre) < 3) {
            $errors['nom_offre'] = 'Le nom doit contenir au moins 3 caractères.';
        } elseif (mb_strlen($nom_offre) > 100) {
            $errors['nom_offre'] = 'Le nom ne peut pas dépasser 100 caractères.';
        } elseif (preg_match('/[0-9]/', $nom_offre)) {
            $errors['nom_offre'] = 'Le nom ne doit pas contenir de chiffres.';
        } elseif (!preg_match('/^[\p{L}\s\-\'\.]+$/u', $nom_offre)) {
            $errors['nom_offre'] = 'Le nom ne doit contenir que des lettres, espaces, tirets ou apostrophes.';
        }

        $types_valides = ['auto', 'sante', 'habitation', 'vie'];
        if ($type_offre === '') {
            $errors['type_offre'] = 'Le type est obligatoire.';
        } elseif (!in_array($type_offre, $types_valides, true)) {
            $errors['type_offre'] = 'Le type sélectionné est invalide.';
        }

        if ($description === '') {
            $errors['description'] = 'La description est obligatoire.';
        } elseif (mb_strlen($description) < 10) {
            $errors['description'] = 'La description doit contenir au moins 10 caractères.';
        } elseif (mb_strlen($description) > 1000) {
            $errors['description'] = 'La description ne peut pas dépasser 1000 caractères.';
        } elseif (preg_match('/^\d+$/', $description)) {
            $errors['description'] = 'La description ne peut pas contenir uniquement des chiffres.';
        }

        if ($couverture === '') {
            $errors['couverture'] = 'La couverture est obligatoire.';
        } elseif (mb_strlen($couverture) < 5) {
            $errors['couverture'] = 'La couverture doit contenir au moins 5 caractères.';
        } elseif (mb_strlen($couverture) > 500) {
            $errors['couverture'] = 'La couverture ne peut pas dépasser 500 caractères.';
        } elseif (!preg_match('/\p{L}/u', $couverture)) {
            $errors['couverture'] = 'La couverture doit contenir au moins une lettre.';
        }

        if ($prix_mensuel === '') {
            $errors['prix_mensuel'] = 'Le prix mensuel est obligatoire.';
        } elseif (!is_numeric($prix_mensuel)) {
            $errors['prix_mensuel'] = 'Le prix mensuel doit être un nombre valide.';
        } elseif ((float)$prix_mensuel <= 0) {
            $errors['prix_mensuel'] = 'Le prix mensuel doit être supérieur à 0.';
        } elseif ((float)$prix_mensuel > 99999.999) {
            $errors['prix_mensuel'] = 'Le prix mensuel ne peut pas dépasser 99 999 TND.';
        }

        if ($prix_annuel === '') {
            $errors['prix_annuel'] = 'Le prix annuel est obligatoire.';
        } elseif (!is_numeric($prix_annuel)) {
            $errors['prix_annuel'] = 'Le prix annuel doit être un nombre valide.';
        } elseif ((float)$prix_annuel <= 0) {
            $errors['prix_annuel'] = 'Le prix annuel doit être supérieur à 0.';
        } elseif ((float)$prix_annuel > 999999.999) {
            $errors['prix_annuel'] = 'Le prix annuel ne peut pas dépasser 999 999 TND.';
        }

        if (
            empty($errors['prix_mensuel']) &&
            empty($errors['prix_annuel']) &&
            is_numeric($prix_mensuel) &&
            is_numeric($prix_annuel)
        ) {
            $m = (float)$prix_mensuel;
            $a = (float)$prix_annuel;

            if ($a >= $m * 12) {
                $errors['prix_annuel'] = 'Le prix annuel doit être inférieur à prix mensuel × 12 (' . number_format($m * 12, 3) . ' TND).';
            } elseif ($a < $m) {
                $errors['prix_annuel'] = 'Le prix annuel ne peut pas être inférieur au prix mensuel.';
            }
        }

        if ($plafond !== '') {
            if (!is_numeric($plafond)) {
                $errors['plafond'] = 'Le plafond doit être un nombre valide.';
            } elseif ((float)$plafond <= 0) {
                $errors['plafond'] = 'Le plafond doit être supérieur à 0.';
            } elseif ((float)$plafond > 9999999.999) {
                $errors['plafond'] = 'Le plafond ne peut pas dépasser 9 999 999 TND.';
            }
        }

        if ($duree_min === '') {
            $errors['duree_min'] = 'La durée minimale est obligatoire.';
        } elseif (!ctype_digit($duree_min)) {
            $errors['duree_min'] = 'La durée minimale doit être un nombre entier positif.';
        } elseif ((int)$duree_min < 1) {
            $errors['duree_min'] = 'La durée minimale doit être au moins 1 mois.';
        } elseif ((int)$duree_min > 120) {
            $errors['duree_min'] = 'La durée minimale ne peut pas dépasser 120 mois.';
        }

        $statuts_valides = ['active', 'suspendue', 'archivee'];
        if ($statut !== '' && !in_array($statut, $statuts_valides, true)) {
            $errors['statut'] = 'Le statut sélectionné est invalide.';
        }

        return $errors;
    }

    private function sanitizePost(array $post): array
    {
        $clean = [];

        foreach ($post as $key => $value) {
            $clean[$key] = htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
        }

        return $clean;
    }
}

$controller = new OffreController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'ajouter':
        $controller->ajouter();
        break;

    case 'modifier':
        $controller->modifier();
        break;

    case 'supprimer':
        $controller->supprimer();
        break;

    case 'statut':
        $controller->changerStatut();
        break;

    case 'avis':
        $controller->avis();
        break;

    case 'toggleAvis':
        $controller->toggleAvis();
        break;

    case 'deleteAvis':
        $controller->deleteAvis();
        break;

    default:
        $controller->index();
        break;
}
