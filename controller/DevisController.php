<?php
/**
 * DevisController.php
 * Gestion complète des devis : ajout, liste, détails, modification, suppression
 * Validation renforcée + 📧 ENVOI EMAIL AUTOMATIQUE — Protex 2026
 */

require_once __DIR__ . '/../helpers/SessionGuard.php';
// if (empty($_SESSION['user_id'])) { header('Location: ../view/BackOffice/connexion.php'); exit; } // Retiré d'ici pour permettre l'ajout public

require_once __DIR__ . '/../config.php';
include(__DIR__ . '/../model/Devis.php');
include_once(__DIR__ . '/MailerService.php'); // 🆕 Service email

class DevisController
{
    // ═══════════════════════════════════════════════════════════════
    // CONSTANTES
    // ═══════════════════════════════════════════════════════════════

    private const TYPES_VALIDES   = ['auto', 'habitation', 'sante'];
    private const STATUTS_VALIDES = ['en_attente', 'en_cours', 'traite', 'converti', 'refuse', 'accepte', 'expire'];

    private const ANNEE_MIN_VEHICULE  = 1950;
    private const SURFACE_MIN         = 5;        // m²
    private const SURFACE_MAX         = 10000;    // m²
    private const VALEUR_BIEN_MAX     = 99999999;
    private const AGE_MIN             = 0;
    private const AGE_MAX             = 120;
    private const PUISSANCE_MIN       = 1;
    private const PUISSANCE_MAX       = 50;       // chevaux fiscaux

    // URL de base du projet
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = defined('BASE_URL') ? BASE_URL : '';
    }

    // ═══════════════════════════════════════════════════════════════
    // REDIRECTIONS
    // ═══════════════════════════════════════════════════════════════

    private function redirectFront(string $message = '', string $erreur = ''): void
    {
        $params = [];
        if ($message !== '') $params['success'] = urlencode($message);
        if ($erreur  !== '') $params['erreur']  = urlencode($erreur);

        $query = $params ? '?' . http_build_query($params) : '';
        header('Location: ' . $this->baseUrl . '/view/FrontOffice/ajoutdevis.php' . $query);
        exit;
    }

    private function redirectBack(string $message = '', string $erreur = ''): void
    {
        $params = ['action' => 'index'];
        if ($message !== '') $params['message'] = $message;
        if ($erreur  !== '') $params['erreur']  = $erreur;

        header('Location: ' . $this->baseUrl . '/controller/DevisController.php?' . http_build_query($params));
        exit;
    }

    // ═══════════════════════════════════════════════════════════════
    // INDEX — liste des devis (Back-Office)
    // ═══════════════════════════════════════════════════════════════

    public function index(): void
    {
        SessionGuard::requireBackoffice();
        $db      = config::getConnexion();
        $message = $_GET['message'] ?? '';
        $erreur  = $_GET['erreur']  ?? '';

        $dateDebut = $_GET['date_debut'] ?? '';
        $dateFin   = $_GET['date_fin'] ?? '';
        $statut    = $_GET['statut'] ?? '';
        $type      = $_GET['type'] ?? '';

        $where   = [];
        $params  = [];

        if ($dateDebut !== '') {
            $where[] = 'd.date_demande >= :date_debut';
            $params[':date_debut'] = $dateDebut;
        }
        if ($dateFin !== '') {
            $where[] = 'd.date_demande <= :date_fin';
            $params[':date_fin'] = $dateFin . ' 23:59:59';
        }
        if ($statut !== '') {
            $where[] = 'd.statut = :statut';
            $params[':statut'] = $statut;
        }
        if ($type !== '') {
            $where[] = 'd.type_assurance = :type_assurance';
            $params[':type_assurance'] = $type;
        }

        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();

        if ($role === 'admin' && $agence) {
            $where[] = 'd.id_agence = :agence';
            $params[':agence'] = $agence;
        } elseif ($role === 'agent' && $agence) {
            $where[] = 'd.id_agence = :agence';
            $params[':agence'] = $agence;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $sql = "
                SELECT d.*,
                       o.nom_offre,
                       (SELECT COUNT(*) FROM paiement p WHERE p.id_devis = d.id_devis) AS nb_paiements
                FROM devis d
                LEFT JOIN offre o ON d.id_offre = o.id_offre
                $whereSql
                ORDER BY d.date_demande DESC, d.id_devis DESC
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $devis = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('DevisController: erreur chargement devis: ' . $e->getMessage());
            $devis = [];
        }

        try {
            $statsWhere = '';
            $statsParams = [];
            if ($role !== 'superadmin' && $agence) {
                $statsWhere = 'WHERE id_agence = :agence';
                $statsParams[':agence'] = $agence;
            }

            $statsSql = "
                SELECT
                    COUNT(*)                          AS total,
                    SUM(statut = 'en_attente')        AS en_attente,
                    SUM(statut = 'en_cours')          AS en_cours,
                    SUM(statut IN ('accepte','traite')) AS acceptes,
                    SUM(statut = 'refuse')            AS refuses,
                    SUM(statut IN ('expire','converti')) AS expires,
                    AVG(NULLIF(montant_estime, 0))    AS montant_moyen,
                    SUM(CASE WHEN statut IN ('accepte','traite') THEN montant_estime ELSE 0 END) AS total_accepte
                FROM devis
                $statsWhere
            ";
            $stmtStats = $db->prepare($statsSql);
            $stmtStats->execute($statsParams);
            $stats = $stmtStats->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            $stats = [];
        }

        try {
            $statsByType = $db->query("
                SELECT d.type_assurance,
                       COUNT(d.id_devis) AS nb,
                       SUM(CASE WHEN d.statut IN ('accepte','traite') THEN 1 ELSE 0 END) AS acceptes,
                       AVG(NULLIF(d.montant_estime, 0)) AS montant_moyen
                FROM devis d
                GROUP BY d.type_assurance
                ORDER BY nb DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $statsByType = [];
        }

        try {
            $devisMois = $db->query("
                SELECT
                    DATE_FORMAT(date_demande, '%Y-%m') AS mois,
                    COUNT(*) AS nb,
                    SUM(CASE WHEN statut = 'accepte' THEN 1 ELSE 0 END) AS acceptes
                FROM devis
                WHERE date_demande >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(date_demande, '%Y-%m')
                ORDER BY mois ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $devisMois = [];
        }

        if (file_exists(__DIR__ . '/../view/BackOffice/devis/liste.php')) {
            require_once __DIR__ . '/../view/BackOffice/devis/liste.php';
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['devis' => $devis, 'stats' => $stats]);
        }
    }

    public function exporter(): void
    {
        SessionGuard::requireBackoffice();
        $db     = config::getConnexion();
        $format = $_GET['format'] ?? 'csv';

        $dateDebut = $_GET['date_debut'] ?? '';
        $dateFin   = $_GET['date_fin'] ?? '';
        $statut    = $_GET['statut'] ?? '';
        $type      = $_GET['type'] ?? '';

        $where  = [];
        $params = [];

        if ($dateDebut !== '') {
            $where[] = 'd.date_demande >= :date_debut';
            $params[':date_debut'] = $dateDebut;
        }
        if ($dateFin !== '') {
            $where[] = 'd.date_demande <= :date_fin';
            $params[':date_fin'] = $dateFin . ' 23:59:59';
        }
        if ($statut !== '') {
            $where[] = 'd.statut = :statut';
            $params[':statut'] = $statut;
        }
        if ($type !== '') {
            $where[] = 'd.type_assurance = :type_assurance';
            $params[':type_assurance'] = $type;
        }

        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();

        if ($role !== 'superadmin' && $agence) {
            $where[] = 'd.id_agence = :agence';
            $params[':agence'] = $agence;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT d.id_devis, d.nom, d.prenom, d.email, d.telephone,
                   d.type_assurance, d.statut, d.montant_estime, d.date_demande, d.reponse_admin,
                   o.nom_offre
            FROM devis d
            LEFT JOIN offre o ON d.id_offre = o.id_offre
            $whereSql
            ORDER BY d.date_demande DESC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $ts = date('Y-m-d_H-i');

        if ($format === 'csv') {
            $this->exportDevisCSV($data, $ts);
        } elseif ($format === 'excel') {
            $this->exportDevisExcel($data, $ts);
        } else {
            $this->exportDevisPDF($data, $ts);
        }
    }

    private function exportDevisCSV(array $data, string $ts): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="devis_protex_' . $ts . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ID', 'Nom', 'Prénom', 'Email', 'Téléphone', 'Type', 'Offre', 'Statut', 'Montant (TND)', 'Date demande', 'Réponse admin'], ';');
        foreach ($data as $row) {
            fputcsv($out, [
                'DEV-2026-' . str_pad($row['id_devis'], 4, '0', STR_PAD_LEFT),
                $row['nom'] ?? '',
                $row['prenom'] ?? '',
                $row['email'] ?? '',
                $row['telephone'] ?? '',
                $row['type_assurance'] ?? '',
                $row['nom_offre'] ?? '',
                $row['statut'] ?? '',
                $row['montant_estime'] !== null ? number_format((float)$row['montant_estime'], 3, '.', '') : '',
                $row['date_demande'] ?? '',
                $row['reponse_admin'] ?? '',
            ], ';');
        }
        fclose($out);
        exit;
    }

    private function exportDevisExcel(array $data, string $ts): void
    {
        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head><meta charset="UTF-8"><style>
table{border-collapse:collapse;font-family:Arial;font-size:11px;}
th{background:#FF6B1A;color:#fff;font-weight:bold;padding:8px;border:1px solid #999;text-align:center;}
td{padding:6px;border:1px solid #ccc;}
.title{font-size:16px;font-weight:bold;color:#1A3A7A;}
.sub{font-size:10px;color:#666;}
.num{text-align:right;}
.center{text-align:center;}
</style></head><body>
<table>
<tr><td colspan="11" class="title">PROTEX ASSURANCE — Rapport des devis</td></tr>
<tr><td colspan="11" class="sub">Exporté le ' . date('d/m/Y H:i') . ' — ' . count($data) . ' devis</td></tr>
<tr><td colspan="11">&nbsp;</td></tr>
<tr>
    <th>Référence</th><th>Nom</th><th>Prénom</th><th>Email</th><th>Téléphone</th>
    <th>Type</th><th>Offre</th><th>Statut</th><th>Montant</th><th>Date</th><th>Réponse</th>
</tr>';
        foreach ($data as $row) {
            $ref = 'DEV-2026-' . str_pad($row['id_devis'], 4, '0', STR_PAD_LEFT);
            $montant = $row['montant_estime'] !== null ? number_format((float)$row['montant_estime'], 3) . ' TND' : '—';
            $html .= '<tr>
                <td class="center">' . htmlspecialchars($ref) . '</td>
                <td>' . htmlspecialchars($row['nom'] ?? '') . '</td>
                <td>' . htmlspecialchars($row['prenom'] ?? '') . '</td>
                <td>' . htmlspecialchars($row['email'] ?? '') . '</td>
                <td class="center">' . htmlspecialchars($row['telephone'] ?? '') . '</td>
                <td class="center">' . htmlspecialchars(ucfirst($row['type_assurance'] ?? '')) . '</td>
                <td>' . htmlspecialchars($row['nom_offre'] ?? '') . '</td>
                <td class="center">' . htmlspecialchars(ucfirst($row['statut'] ?? '')) . '</td>
                <td class="num">' . htmlspecialchars($montant) . '</td>
                <td class="center">' . htmlspecialchars($row['date_demande'] ?? '') . '</td>
                <td>' . htmlspecialchars($row['reponse_admin'] ?? '') . '</td>
            </tr>';
        }
        $html .= '</table></body></html>';
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="devis_protex_' . $ts . '.xls"');
        echo "\xEF\xBB\xBF" . $html;
        exit;
    }

    private function exportDevisPDF(array $data, string $ts): void
    {
        $rowsHtml = '';
        foreach ($data as $row) {
            $ref = 'DEV-2026-' . str_pad($row['id_devis'], 4, '0', STR_PAD_LEFT);
            $client = trim(($row['prenom'] ?? '') . ' ' . ($row['nom'] ?? ''));
            $montant = $row['montant_estime'] !== null ? number_format((float)$row['montant_estime'], 3) . ' TND' : '—';
            $rowsHtml .= '<tr>
                <td>' . htmlspecialchars($ref) . '</td>
                <td>' . htmlspecialchars($client) . '</td>
                <td>' . htmlspecialchars(ucfirst($row['type_assurance'] ?? '')) . '</td>
                <td style="text-align:right">' . htmlspecialchars($montant) . '</td>
                <td>' . htmlspecialchars(ucfirst($row['statut'] ?? '')) . '</td>
                <td>' . htmlspecialchars($row['date_demande'] ?? '') . '</td>
            </tr>';
        }
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
            body{font-family:Arial,sans-serif;padding:20px;color:#000;}
            .print-header{border-bottom:3px solid #FF6B1A;padding-bottom:12px;margin-bottom:18px;}
            .print-brand{font-size:22px;font-weight:800;color:#1A3A7A;margin:0;}
            .print-brand span{color:#FF6B1A;}
            .print-info{color:#666;font-size:11px;margin-top:4px;}
            table{width:100%;border-collapse:collapse;font-size:10px;}
            th,td{border:1px solid #999;padding:6px 8px;}
            th{background:#FF6B1A;color:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;text-align:left;}
        </style></head><body>
        <div class="print-header">
            <h1 class="print-brand">PROTEX <span>Assurance</span></h1>
            <p class="print-info">Rapport des devis — Export du ' . date('d/m/Y H:i') . ' — ' . count($data) . ' devis</p>
        </div>
        <table><thead><tr>
            <th>Référence</th><th>Client</th><th>Type</th><th>Montant</th>
            <th>Statut</th><th>Date</th>
        </tr></thead><tbody>' . $rowsHtml . '</tbody></table>
        <p style="margin-top:20px;font-size:9px;color:#888;text-align:center;">
            Document généré automatiquement par Protex Admin — ' . date('d/m/Y') . '
        </p></body></html>';
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="devis_protex_' . $ts . '.html"');
        echo $html;
        exit;
    }

    // ═══════════════════════════════════════════════════════════════
    // AJOUTER — formulaire Front-Office
    // ═══════════════════════════════════════════════════════════════

    public function ajouter(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $this->valider($_POST);

            if (empty($errors)) {
                try {
                    $db = config::getConnexion();
                    $db->beginTransaction();

                    // ── Vérifier que l'offre existe et est active ──
                    if (!empty($_POST['id_offre'])) {
                        $check = $db->prepare("SELECT id_offre FROM offre WHERE id_offre = ? AND statut = 'active'");
                        $check->execute([(int)$_POST['id_offre']]);
                        if (!$check->fetch()) {
                            $errors['id_offre'] = "L'offre sélectionnée n'existe pas ou n'est plus disponible.";
                            $db->rollBack();
                        }
                    }

                    if (empty($errors)) {
                        // ── Déterminer l'agence ──
                        $idAgence = null;
                        if (isset($_SESSION['user_id'])) {
                            $stmtAg = $db->prepare("SELECT id_agence FROM client WHERE id_user = ?");
                            $stmtAg->execute([(int)$_SESSION['user_id']]);
                            $idAgence = $stmtAg->fetchColumn() ?: null;
                        }

                        // ── Déterminer l'utilisateur connecté ──
                        $idUserSession = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : null);

                        // ── Insertion principale ──
                        $stmt = $db->prepare("
                            INSERT INTO devis
                                (id_offre, nom, prenom, email, telephone, type_assurance, statut, montant_estime, reponse_admin, id_agence, id_user)
                            VALUES
                                (:id_offre, :nom, :prenom, :email, :telephone, :type_assurance, :statut, :montant_estime, :reponse_admin, :id_agence, :id_user)
                        ");

                        $stmt->execute([
                            ':id_offre'       => !empty($_POST['id_offre']) ? (int)$_POST['id_offre'] : null,
                            ':nom'            => trim($_POST['nom']),
                            ':prenom'         => trim($_POST['prenom']),
                            ':email'          => strtolower(trim($_POST['email'])),
                            ':telephone'      => preg_replace('/\s+/', '', trim($_POST['telephone'])),
                            ':type_assurance' => trim($_POST['type_assurance']),
                            ':statut'         => 'en_attente',
                            ':montant_estime' => null,
                            ':reponse_admin'  => null,
                            ':id_agence'      => $idAgence,
                            ':id_user'        => $idUserSession,
                        ]);

                        $id_devis = (int)$db->lastInsertId();
                        $type     = strtolower(trim($_POST['type_assurance']));

                        $this->insererDetails($db, $id_devis, $type, $_POST);
                        $db->commit();

                        $reference = 'DEV-2026-' . str_pad($id_devis, 4, '0', STR_PAD_LEFT);
                        $this->redirectFront("Votre demande de devis a bien été enregistrée. Référence : $reference");
                    }

                } catch (Exception $e) {
                    if (isset($db) && $db->inTransaction()) {
                        $db->rollBack();
                    }
                    error_log('DevisController::store error: ' . $e->getMessage());
                    $errors['general'] = "Erreur lors de l'enregistrement. Veuillez réessayer plus tard.";
                }
            }

            if (!empty($errors)) {
                $_SESSION['errors'] = $errors;
                $_SESSION['old']    = $_POST;
                $this->redirectFront();
            }
        }

        $this->redirectFront();
    }

    // ═══════════════════════════════════════════════════════════════
    // DÉTAILS — Back-Office
    // ═══════════════════════════════════════════════════════════════

    public function details(): void
    {
        SessionGuard::requireBackoffice();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            $this->redirectBack('', 'Identifiant de devis invalide.');
        }

        $db = config::getConnexion();

        try {
            $role   = RoleHelper::getRole();
            $agence = RoleHelper::getAgenceId();

            $sql = "
                SELECT d.*, o.nom_offre
                FROM devis d
                LEFT JOIN offre o ON d.id_offre = o.id_offre
                WHERE d.id_devis = ?
            ";
            if ($role !== 'superadmin' && $agence) {
                $sql .= " AND d.id_agence = " . (int)$agence;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            $devis = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$devis) {
                $this->redirectBack('', "Le devis #$id n'existe pas ou vous n'avez pas les droits.");
            }

            $details = $this->chargerDetails($db, $id, $devis['type_assurance']);

        } catch (Exception $e) {
            error_log('DevisController::details error: ' . $e->getMessage());
            $this->redirectBack('', 'Erreur interne serveur.');
            return;
        }

        if (file_exists(__DIR__ . '/../view/BackOffice/devis/details.php')) {
            require_once __DIR__ . '/../view/BackOffice/devis/details.php';
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['devis' => $devis, 'details' => $details]);
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // 🆕 MODIFIER — Back-Office (avec ENVOI EMAIL AUTOMATIQUE)
    // ═══════════════════════════════════════════════════════════════

    public function modifier(): void
    {
        SessionGuard::requireBackoffice();
        $id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $errors = [];
        $old    = [];

        if ($id <= 0) {
            $this->redirectBack('', 'Identifiant de devis invalide.');
        }

        $db   = config::getConnexion();
        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();

        $sql = "
            SELECT d.*, o.nom_offre
            FROM devis d
            LEFT JOIN offre o ON d.id_offre = o.id_offre
            WHERE d.id_devis = ?
        ";
        $params = [$id];
        if ($role !== 'superadmin' && $agence) {
            $sql .= " AND d.id_agence = ?";
            $params[] = $agence;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $devis = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$devis) {
            $this->redirectBack('', "Le devis #$id n'existe pas.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old    = $this->sanitizePost($_POST);
            $errors = $this->validerModification($_POST);

            if (empty($errors)) {
                try {
                    // ──────── Mémoriser l'ancien statut pour comparaison ────────
                    $ancienStatut    = $devis['statut'] ?? '';
                    $nouveauStatut   = trim($_POST['statut']);
                    $nouveauMontant  = (isset($_POST['montant_estime']) && $_POST['montant_estime'] !== '')
                        ? round((float)$_POST['montant_estime'], 3) : null;
                    $nouvelleReponse = trim($_POST['reponse_admin'] ?? '');

                    // ──────── UPDATE en BDD ────────
                    $stmt = $db->prepare("
                        UPDATE devis
                        SET statut         = :statut,
                            montant_estime = :montant_estime,
                            reponse_admin  = :reponse_admin
                        WHERE id_devis = :id_devis
                    ");

                    $stmt->execute([
                        ':statut'         => $nouveauStatut,
                        ':montant_estime' => $nouveauMontant,
                        ':reponse_admin'  => $nouvelleReponse,
                        ':id_devis'       => $id,
                    ]);

                    // ═══════════════════════════════════════════════════
                    // 📧 ENVOI EMAIL AUTOMATIQUE AU CLIENT
                    // (seulement si le statut a changé)
                    // ═══════════════════════════════════════════════════
                    $messageEmail = '';

                        if ($ancienStatut !== $nouveauStatut) {
                        try {
                            $mailer = new MailerService();

                            // Mettre à jour les données du devis pour l'email
                            $devis['statut']         = $nouveauStatut;
                            $devis['montant_estime'] = $nouveauMontant;
                            $devis['reponse_admin']  = $nouvelleReponse;

                            $resultat = $mailer->envoyerNotificationDevis(
                                $devis,
                                $nouveauStatut,
                                $nouveauMontant,
                                $nouvelleReponse
                            );

                            if ($resultat['success']) {
                                $messageEmail = ' 📧 Email envoyé au client.';
                            } else {
                                $messageEmail = ' ⚠️ Email non envoyé : ' . $resultat['message'];
                            }
                            } catch (Exception $e) {
                                error_log('DevisController::modifier email error: ' . $e->getMessage());
                                $messageEmail = ' ⚠️ Erreur email';
                            }
                    }

                    $stmtU = $db->prepare("SELECT id_user FROM user WHERE email = ?");
                    $stmtU->execute([$devis['email']]);
                    $uid = (int)$stmtU->fetchColumn();
                    if ($uid) {
                        $db->prepare("INSERT INTO notification (id_user, message, type, lien) VALUES (?, ?, 'devis', ?)")->execute([
                            $uid,
                            "Votre devis #$id a été mis à jour (statut : $nouveauStatut).",
                            "/view/FrontOffice/mesdevis.php"
                        ]);
                    }

                    $this->redirectBack('Devis modifié avec succès.' . $messageEmail);

                } catch (Exception $e) {
                    error_log('DevisController::modifier error: ' . $e->getMessage());
                    $errors['general'] = 'Erreur serveur';
                }
            }
        }

        if (file_exists(__DIR__ . '/../view/BackOffice/devis/modifier.php')) {
            require_once __DIR__ . '/../view/BackOffice/devis/modifier.php';
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // 🔄 CONVERTIR DEVIS ACCEPTÉ EN PAIEMENT
    // ═══════════════════════════════════════════════════════════════

    public function convertirEnContrat(): void
    {
        SessionGuard::requireBackoffice();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            $this->redirectBack('', 'Identifiant de devis invalide.');
        }

        $db = config::getConnexion();

        try {
            $role   = RoleHelper::getRole();
            $agence = RoleHelper::getAgenceId();

            $sql = "
                SELECT d.*, o.nom_offre, o.prix_mensuel, o.prix_annuel
                FROM devis d
                LEFT JOIN offre o ON d.id_offre = o.id_offre
                WHERE d.id_devis = ?
            ";
            $params = [$id];
            if ($role !== 'superadmin' && $agence) {
                $sql .= " AND d.id_agence = ?";
                $params[] = $agence;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $devis = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$devis) {
                $this->redirectBack('', "Le devis #$id n'existe pas.");
            }

            if (!in_array($devis['statut'], ['accepte', 'traite'], true)) {
                $this->redirectBack('', "Seuls les devis acceptés peuvent être convertis en contrat.");
            }

            $stmtCtr = $db->prepare("
                SELECT COUNT(*) FROM contrat WHERE id_devis = ? OR details_contrat LIKE ?
            ");
            $likeId = '%"id_devis":' . (int)$id . '%';
            $stmtCtr->execute([(int)$id, $likeId]);
            if ((int)$stmtCtr->fetchColumn() > 0) {
                $this->redirectBack('', "Un contrat est déjà lié à ce devis.");
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $periodicite = $_POST['periodicite'] ?? 'mensuel';
                $notes       = trim($_POST['notes'] ?? '');

                if (!in_array($periodicite, ['mensuel', 'annuel'], true)) {
                    $periodicite = 'mensuel';
                }

                // INSERT contrat directement (sans dépendre du modèle camarades)
                $numeroContrat = 'CTR-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));
                $montant = $periodicite === 'annuel'
                    ? (float)($devis['prix_annuel'] ?? 0)
                    : (float)($devis['prix_mensuel'] ?? 0);
                if ($montant <= 0 && isset($devis['montant_estime'])) {
                    $montant = (float)$devis['montant_estime'];
                }
                $dateDebut = date('Y-m-d');
                $dateFin = $periodicite === 'annuel'
                    ? date('Y-m-d', strtotime('+1 year'))
                    : date('Y-m-d', strtotime('+1 month'));

                // Try to find the user by email to link the contract
                $stmtUser = $db->prepare("SELECT id_user FROM user WHERE email = ?");
                $stmtUser->execute([$devis['email']]);
                $idUser = $stmtUser->fetchColumn();

                $details = json_encode(['id_devis' => $devis['id_devis'], 'notes' => $notes]);

                if ($devis['id_user'] && !$idUser) {
                    $idUser = (int)$devis['id_user'];
                }
                if (!$idUser) {
                    $this->redirectBack('', "Aucun utilisateur trouvé avec l'email " . $devis['email'] . ". Le client doit d'abord créer un compte.");
                    return;
                }
                $db->prepare("
                    INSERT INTO contrat (numero_contrat, id_categorie, type_contrat,
                        prime_contrat, date_debut_contrat, date_fin_contrat, statut_contrat, id_user, mode_paiement, details_contrat, id_devis)
                    VALUES (:numero, :id_categorie, :type, :prime, :debut, :fin, 'actif', :id_user, :mode_paiement, :details_contrat, :id_devis)
                ")->execute([
                    ':numero'       => $numeroContrat,
                    ':id_categorie' => (int)($devis['id_offre'] ?? 0),
                    ':type'         => $devis['type_assurance'] ?? 'auto',
                    ':prime'        => $montant,
                    ':debut'        => $dateDebut,
                    ':fin'          => $dateFin,
                    ':id_user'      => $idUser,
                    ':mode_paiement'=> $periodicite,
                    ':details_contrat' => $details,
                    ':id_devis'     => (int)$devis['id_devis'],
                ]);
                $db->prepare("UPDATE devis SET statut = 'converti' WHERE id_devis = ?")
                   ->execute([(int)$devis['id_devis']]);

                // ── Points fidélité pour souscription contrat ──
                if ($idUser) {
                    $db->prepare("INSERT INTO points_fidelite (id_user, points, motif) VALUES (?, 50, 'Souscription contrat')")->execute([$idUser]);
                    $db->prepare("INSERT INTO notification (id_user, message, type, lien) VALUES (?, ?, 'contrat', ?)")
                        ->execute([$idUser, "Votre devis #$id a été converti en contrat {$numeroContrat}.", "/view/FrontOffice/contrat.php"]);
                }

                $this->redirectBack("Devis #$id converti en contrat — Référence : {$numeroContrat}");
            }
        } catch (Exception $e) {
            error_log('DevisController::convertirEnContrat error: ' . $e->getMessage());
            $this->redirectBack('', 'Erreur interne serveur.');
            return;
        }

        require_once __DIR__ . '/../view/BackOffice/devis/convertir.php';
    }

    // ═══════════════════════════════════════════════════════════════
    // SUPPRIMER — Back-Office
    // ═══════════════════════════════════════════════════════════════

    public function supprimer(): void
    {
        SessionGuard::requireBackoffice();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            $this->redirectBack('', 'Identifiant de devis invalide.');
        }

        $db   = config::getConnexion();
        $role   = RoleHelper::getRole();
        $agence = RoleHelper::getAgenceId();

        $sql = "SELECT id_devis, nom, prenom, email, type_assurance FROM devis WHERE id_devis = ?";
        $params = [$id];
        if ($role !== 'superadmin' && $agence) {
            $sql .= " AND id_agence = ?";
            $params[] = $agence;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $devis = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$devis) {
            $this->redirectBack('', "Le devis #$id n'existe pas.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db->beginTransaction();
                $db->prepare("DELETE FROM devis_auto       WHERE id_devis = ?")->execute([$id]);
                $db->prepare("DELETE FROM devis_habitation WHERE id_devis = ?")->execute([$id]);
                $db->prepare("DELETE FROM devis_sante      WHERE id_devis = ?")->execute([$id]);
                $db->prepare("DELETE FROM devis            WHERE id_devis = ?")->execute([$id]);
                $stmtU = $db->prepare("SELECT id_user FROM user WHERE email = ?");
                $stmtU->execute([$devis['email']]);
                $uid = (int)$stmtU->fetchColumn();
                if ($uid) {
                    $db->prepare("INSERT INTO notification (id_user, message, type) VALUES (?, ?, 'devis')")
                        ->execute([$uid, "Votre devis #$id a été supprimé."]);
                }
                $db->commit();
                $this->redirectBack('Devis supprimé avec succès.');

            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                $this->redirectBack('', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        }

        if (file_exists(__DIR__ . '/../view/BackOffice/devis/supprimer.php')) {
            require_once __DIR__ . '/../view/BackOffice/devis/supprimer.php';
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // VALIDATION — création (avec messages PRÉCIS)
    // ═══════════════════════════════════════════════════════════════

    private function valider(array $data): array
    {
        $errors = [];

        $nom            = trim($data['nom'] ?? '');
        $prenom         = trim($data['prenom'] ?? '');
        $email          = trim($data['email'] ?? '');
        $telephone      = preg_replace('/\s+/', '', trim($data['telephone'] ?? ''));
        $type           = trim($data['type_assurance'] ?? '');
        $id_offre       = trim($data['id_offre'] ?? '');

        // 👤 NOM
        if ($nom === '') {
            $errors['nom'] = "Le nom est obligatoire.";
        } elseif (mb_strlen($nom) < 2) {
            $errors['nom'] = "Le nom doit contenir au moins 2 caractères.";
        } elseif (mb_strlen($nom) > 100) {
            $errors['nom'] = "Le nom ne peut pas dépasser 100 caractères.";
        } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s'-]+$/u", $nom)) {
            $errors['nom'] = "Le nom ne doit contenir que des lettres, espaces, apostrophes ou tirets (pas de chiffres).";
        }

        // 👤 PRÉNOM
        if ($prenom === '') {
            $errors['prenom'] = "Le prénom est obligatoire.";
        } elseif (mb_strlen($prenom) < 2) {
            $errors['prenom'] = "Le prénom doit contenir au moins 2 caractères.";
        } elseif (mb_strlen($prenom) > 100) {
            $errors['prenom'] = "Le prénom ne peut pas dépasser 100 caractères.";
        } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s'-]+$/u", $prenom)) {
            $errors['prenom'] = "Le prénom ne doit contenir que des lettres, espaces, apostrophes ou tirets (pas de chiffres).";
        }

        // 📧 EMAIL
        if ($email === '') {
            $errors['email'] = "L'adresse email est obligatoire.";
        } elseif (mb_strlen($email) > 150) {
            $errors['email'] = "L'email ne peut pas dépasser 150 caractères.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Format d'email invalide. Exemple correct : nom@exemple.com";
        }

        // 📞 TÉLÉPHONE — 8 chiffres
        if ($telephone === '') {
            $errors['telephone'] = "Le numéro de téléphone est obligatoire.";
        } elseif (!preg_match('/^[0-9]{8}$/', $telephone)) {
            $errors['telephone'] = "Le téléphone doit contenir exactement 8 chiffres (ex: 20123456).";
        } elseif (!preg_match('/^[2-59]/', $telephone)) {
            $errors['telephone'] = "Le numéro doit commencer par 2, 3, 4, 5 ou 9 (numéro tunisien valide).";
        }

        // 🛡️ TYPE D'ASSURANCE
        if ($type === '') {
            $errors['type_assurance'] = "Veuillez choisir un type d'assurance (Auto, Habitation ou Santé).";
        } elseif (!in_array($type, self::TYPES_VALIDES, true)) {
            $errors['type_assurance'] = "Type d'assurance invalide. Choix possibles : Auto, Habitation ou Santé.";
        }

        // 🎁 OFFRE
        if ($id_offre === '') {
            $errors['id_offre'] = "Veuillez sélectionner une offre.";
        } elseif (!ctype_digit($id_offre) || (int)$id_offre <= 0) {
            $errors['id_offre'] = "Offre invalide.";
        }

        // 🚗 SECTION AUTO
        if ($type === 'auto') {
            $marque          = trim($data['marque'] ?? '');
            $modele          = trim($data['modele'] ?? '');
            $annee           = trim($data['annee'] ?? '');
            $immatriculation = trim($data['immatriculation'] ?? '');
            $puissance       = trim($data['puissance'] ?? '');
            $valeur_vehicule = trim($data['valeur_vehicule'] ?? '');

            if ($marque === '') {
                $errors['marque'] = "La marque du véhicule est obligatoire (ex: Peugeot, Renault, Toyota).";
            } elseif (mb_strlen($marque) < 2) {
                $errors['marque'] = "La marque doit contenir au moins 2 caractères.";
            } elseif (!preg_match("/^[a-zA-Z0-9À-ÿ\s'-]+$/u", $marque)) {
                $errors['marque'] = "La marque contient des caractères invalides.";
            }

            if ($modele === '') {
                $errors['modele'] = "Le modèle du véhicule est obligatoire (ex: 208, Clio, Yaris).";
            } elseif (mb_strlen($modele) < 1) {
                $errors['modele'] = "Le modèle doit contenir au moins 1 caractère.";
            }

            $anneeActuelle = (int)date('Y');
            if ($annee === '') {
                $errors['annee'] = "L'année du véhicule est obligatoire.";
            } elseif (!ctype_digit($annee) || strlen($annee) !== 4) {
                $errors['annee'] = "L'année doit être un nombre à 4 chiffres (ex: 2020).";
            } elseif ((int)$annee < self::ANNEE_MIN_VEHICULE) {
                $errors['annee'] = "L'année doit être supérieure à " . self::ANNEE_MIN_VEHICULE . ".";
            } elseif ((int)$annee > $anneeActuelle + 1) {
                $errors['annee'] = "L'année ne peut pas être supérieure à " . ($anneeActuelle + 1) . ".";
            }

            if ($immatriculation === '') {
                $errors['immatriculation'] = "L'immatriculation est obligatoire (ex: 123 TUN 4567).";
            } elseif (mb_strlen($immatriculation) < 4) {
                $errors['immatriculation'] = "L'immatriculation doit contenir au moins 4 caractères.";
            } elseif (mb_strlen($immatriculation) > 20) {
                $errors['immatriculation'] = "L'immatriculation ne peut pas dépasser 20 caractères.";
            }

            if ($puissance !== '') {
                if (!ctype_digit($puissance)) {
                    $errors['puissance'] = "La puissance fiscale doit être un nombre entier.";
                } elseif ((int)$puissance < self::PUISSANCE_MIN || (int)$puissance > self::PUISSANCE_MAX) {
                    $errors['puissance'] = "La puissance doit être entre " . self::PUISSANCE_MIN . " et " . self::PUISSANCE_MAX . " chevaux.";
                }
            }

            if ($valeur_vehicule !== '') {
                if (!is_numeric($valeur_vehicule)) {
                    $errors['valeur_vehicule'] = "La valeur estimée doit être un nombre (ex: 25000).";
                } elseif ((float)$valeur_vehicule <= 0) {
                    $errors['valeur_vehicule'] = "La valeur doit être supérieure à 0.";
                } elseif ((float)$valeur_vehicule > self::VALEUR_BIEN_MAX) {
                    $errors['valeur_vehicule'] = "La valeur est trop élevée (maximum " . number_format(self::VALEUR_BIEN_MAX, 0, ',', ' ') . " DT).";
                }
            }
        }

        // 🏠 SECTION HABITATION
        if ($type === 'habitation') {
            $type_logement = trim($data['type_logement'] ?? $data['type_habitation'] ?? '');
            $surface       = trim($data['surface'] ?? $data['superficie'] ?? '');
            $valeur_bien   = trim($data['valeur_bien'] ?? '');
            $ville         = trim($data['ville'] ?? '');
            $adresse       = trim($data['adresse'] ?? '');
            $statut_occ    = trim($data['proprietaire_locataire'] ?? $data['statut_occupation'] ?? '');

            if ($type_logement === '') {
                $errors['type_logement'] = "Le type de logement est obligatoire (Appartement, Maison, Studio, Villa).";
            }

            if ($surface === '') {
                $errors['surface'] = "La surface est obligatoire.";
            } elseif (!is_numeric($surface)) {
                $errors['surface'] = "La surface doit être un nombre (ex: 80).";
            } elseif ((float)$surface < self::SURFACE_MIN) {
                $errors['surface'] = "La surface doit être d'au moins " . self::SURFACE_MIN . " m².";
            } elseif ((float)$surface > self::SURFACE_MAX) {
                $errors['surface'] = "La surface ne peut pas dépasser " . self::SURFACE_MAX . " m².";
            }

            if ($valeur_bien !== '') {
                if (!is_numeric($valeur_bien)) {
                    $errors['valeur_bien'] = "La valeur du bien doit être un nombre.";
                } elseif ((float)$valeur_bien <= 0) {
                    $errors['valeur_bien'] = "La valeur du bien doit être supérieure à 0.";
                } elseif ((float)$valeur_bien > self::VALEUR_BIEN_MAX) {
                    $errors['valeur_bien'] = "La valeur est trop élevée (maximum " . number_format(self::VALEUR_BIEN_MAX, 0, ',', ' ') . " DT).";
                }
            }

            /* 
            if ($ville === '') {
                $errors['ville'] = "La ville est obligatoire.";
            } elseif (mb_strlen($ville) < 2) {
                $errors['ville'] = "La ville doit contenir au moins 2 caractères.";
            } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s'-]+$/u", $ville)) {
                $errors['ville'] = "La ville ne doit contenir que des lettres.";
            }
            */

            if ($adresse === '') {
                $errors['adresse'] = "L'adresse est obligatoire.";
            } elseif (mb_strlen($adresse) < 5) {
                $errors['adresse'] = "L'adresse doit contenir au moins 5 caractères.";
            }

            if ($statut_occ === '') {
                $errors['proprietaire_locataire'] = "Veuillez préciser si vous êtes propriétaire ou locataire.";
            } elseif (!in_array($statut_occ, ['proprietaire', 'locataire'], true)) {
                $errors['proprietaire_locataire'] = "Statut invalide (propriétaire ou locataire).";
            }
        }

        // ❤️ SECTION SANTÉ
        if ($type === 'sante') {
            $age              = trim($data['age'] ?? '');
            $situation        = trim($data['situation_familiale'] ?? '');
            $nb_benef         = trim($data['nombre_beneficiaires'] ?? '');
            $couverture       = trim($data['couverture_souhaitee'] ?? '');
            $profession       = trim($data['profession'] ?? '');

            if ($age === '') {
                $errors['age'] = "L'âge est obligatoire.";
            } elseif (!ctype_digit($age)) {
                $errors['age'] = "L'âge doit être un nombre entier.";
            } elseif ((int)$age < self::AGE_MIN) {
                $errors['age'] = "L'âge ne peut pas être négatif.";
            } elseif ((int)$age > self::AGE_MAX) {
                $errors['age'] = "L'âge ne peut pas dépasser " . self::AGE_MAX . " ans.";
            } elseif ((int)$age < 18) {
                $errors['age'] = "Vous devez être majeur (18 ans minimum) pour souscrire.";
            }

            if ($situation === '') {
                $errors['situation_familiale'] = "La situation familiale est obligatoire (Célibataire, Marié, Divorcé, Veuf).";
            }

            if ($nb_benef !== '') {
                if (!ctype_digit($nb_benef)) {
                    $errors['nombre_beneficiaires'] = "Le nombre de bénéficiaires doit être un nombre entier.";
                } elseif ((int)$nb_benef < 1) {
                    $errors['nombre_beneficiaires'] = "Au moins 1 bénéficiaire est requis.";
                } elseif ((int)$nb_benef > 20) {
                    $errors['nombre_beneficiaires'] = "Le nombre de bénéficiaires ne peut pas dépasser 20.";
                }
            }

            if ($couverture === '') {
                $errors['couverture_souhaitee'] = "Veuillez choisir un niveau de couverture (Basique, Standard, Premium).";
            }

            if ($profession !== '' && mb_strlen($profession) > 100) {
                $errors['profession'] = "La profession ne peut pas dépasser 100 caractères.";
            }
        }

        return $errors;
    }

    // ═══════════════════════════════════════════════════════════════
    // VALIDATION — modification (admin)
    // ═══════════════════════════════════════════════════════════════

    private function validerModification(array $data): array
    {
        $errors = [];

        $statut         = trim((string)($data['statut']         ?? ''));
        $montant_estime = trim((string)($data['montant_estime'] ?? ''));
        $reponse_admin  = trim((string)($data['reponse_admin']  ?? ''));

        if ($statut === '') {
            $errors['statut'] = "Le statut est obligatoire.";
        } elseif (!in_array($statut, self::STATUTS_VALIDES, true)) {
            $errors['statut'] = "Statut invalide. Valeurs acceptées : " . implode(', ', self::STATUTS_VALIDES) . ".";
        }

        if ($montant_estime !== '') {
            if (!is_numeric($montant_estime)) {
                $errors['montant_estime'] = "Le montant estimé doit être un nombre valide (ex: 1500.000).";
            } elseif ((float)$montant_estime < 0) {
                $errors['montant_estime'] = "Le montant ne peut pas être négatif.";
            } elseif ((float)$montant_estime > self::VALEUR_BIEN_MAX) {
                $errors['montant_estime'] = "Le montant est trop élevé (maximum " . number_format(self::VALEUR_BIEN_MAX, 0, ',', ' ') . " DT).";
            }
        }

        if ($reponse_admin !== '' && mb_strlen($reponse_admin) > 1000) {
            $errors['reponse_admin'] = "La réponse ne peut pas dépasser 1000 caractères (actuellement : " . mb_strlen($reponse_admin) . ").";
        }

        return $errors;
    }

    // ═══════════════════════════════════════════════════════════════
    // INSERTION DES DÉTAILS PAR TYPE
    // ═══════════════════════════════════════════════════════════════

    private function insererDetails(PDO $db, int $id_devis, string $type, array $post): void
    {
        if ($type === 'auto') {
            $db->prepare("
                INSERT INTO devis_auto
                    (id_devis, marque, modele, annee, immatriculation, puissance, carburant, usage_vehicule)
                VALUES
                    (:id_devis, :marque, :modele, :annee, :immatriculation, :puissance, :carburant, :usage_vehicule)
            ")->execute([
                ':id_devis'        => $id_devis,
                ':marque'          => trim($post['marque'] ?? ''),
                ':modele'          => trim($post['modele'] ?? ''),
                ':annee'           => !empty($post['annee']) ? (int)$post['annee'] : null,
                ':immatriculation' => strtoupper(trim($post['immatriculation'] ?? '')),
                ':puissance'       => !empty($post['puissance']) ? (int)$post['puissance'] : null,
                ':carburant'       => trim($post['carburant'] ?? ''),
                ':usage_vehicule'  => trim($post['usage_vehicule'] ?? ''),
            ]);

        } elseif ($type === 'habitation') {
            $db->prepare("
                INSERT INTO devis_habitation
                    (id_devis, type_habitation, type_logement, surface, valeur_bien, ville, adresse, proprietaire_locataire)
                VALUES
                    (:id_devis, :type_habitation, :type_logement, :surface, :valeur_bien, :ville, :adresse, :proprietaire_locataire)
            ")->execute([
                ':id_devis'                 => $id_devis,
                ':type_habitation'          => trim($post['type_habitation'] ?? $post['type_logement'] ?? ''),
                ':type_logement'            => trim($post['type_logement'] ?? $post['type_habitation'] ?? ''),
                ':surface'                  => isset($post['surface']) && $post['surface'] !== ''
                    ? (float)$post['surface']
                    : (isset($post['superficie']) && $post['superficie'] !== '' ? (float)$post['superficie'] : null),
                ':valeur_bien'              => isset($post['valeur_bien']) && $post['valeur_bien'] !== ''
                    ? round((float)$post['valeur_bien'], 3) : null,
                ':ville'                    => ucfirst(strtolower(trim($post['ville'] ?? ''))),
                ':adresse'                  => trim($post['adresse'] ?? ''),
                ':proprietaire_locataire'   => trim($post['proprietaire_locataire'] ?? $post['statut_occupation'] ?? ''),
            ]);

        } elseif ($type === 'sante') {
            $db->prepare("
                INSERT INTO devis_sante
                    (id_devis, age, situation_familiale, nombre_beneficiaires, antecedents_medicaux, couverture_souhaitee, profession)
                VALUES
                    (:id_devis, :age, :situation_familiale, :nombre_beneficiaires, :antecedents_medicaux, :couverture_souhaitee, :profession)
            ")->execute([
                ':id_devis'               => $id_devis,
                ':age'                    => !empty($post['age']) ? (int)$post['age'] : null,
                ':situation_familiale'    => trim($post['situation_familiale']    ?? ''),
                ':nombre_beneficiaires'   => !empty($post['nombre_beneficiaires']) ? (int)$post['nombre_beneficiaires'] : 1,
                ':antecedents_medicaux'   => trim($post['antecedents_medicaux']   ?? ''),
                ':couverture_souhaitee'   => trim($post['couverture_souhaitee']   ?? ''),
                ':profession'             => trim($post['profession']             ?? ''),
            ]);
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // CHARGER LES DÉTAILS D'UN DEVIS
    // ═══════════════════════════════════════════════════════════════

    private function chargerDetails(PDO $db, int $id_devis, string $type): ?array
    {
        $tables = [
            'auto'       => 'devis_auto',
            'habitation' => 'devis_habitation',
            'sante'      => 'devis_sante',
        ];

        if (!isset($tables[$type])) return null;

        $stmt = $db->prepare("SELECT * FROM {$tables[$type]} WHERE id_devis = ?");
        $stmt->execute([$id_devis]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ═══════════════════════════════════════════════════════════════
    // CHARGER LES OFFRES PAR TYPE
    // ═══════════════════════════════════════════════════════════════

    private function chargerOffresParType(): array
    {
        $offres = ['auto' => [], 'habitation' => [], 'sante' => []];

        try {
            $db   = config::getConnexion();
            $stmt = $db->query("
                SELECT id_offre, nom_offre, type_offre, prix_mensuel, prix_annuel, couverture
                FROM offre
                WHERE statut = 'active'
                ORDER BY type_offre, prix_annuel ASC
            ");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $o) {
                if (isset($offres[$o['type_offre']])) {
                    $offres[$o['type_offre']][] = $o;
                }
            }
        } catch (Exception $e) {
            // Silencieux — formulaire affiché sans offres
        }

        return $offres;
    }

    // ═══════════════════════════════════════════════════════════════
    // SANITIZE POST
    // ═══════════════════════════════════════════════════════════════

    private function sanitizePost(array $post): array
    {
        return array_map(
            fn($v) => htmlspecialchars(trim((string)($v ?? '')), ENT_QUOTES, 'UTF-8'),
            $post
        );
    }
}

// ═══════════════════════════════════════════════════════════════
// ROUTEUR
// ═══════════════════════════════════════════════════════════════

$controller = new DevisController();
$action     = $_GET['action'] ?? 'index';

switch ($action) {
    case 'ajouter':          $controller->ajouter();          break;
    case 'details':          $controller->details();          break;
    case 'modifier':         $controller->modifier();         break;
    case 'convertir':        $controller->convertirEnContrat(); break;
    case 'supprimer':        $controller->supprimer();        break;
    case 'exporter':         $controller->exporter();         break;
    default:                 $controller->index();            break;
}
