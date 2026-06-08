<?php
/**
 * controller/RouletteController.php
 * Gestion de la roulette de fidélité — Système de TENTATIVES
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . '/../model/Roulette.php');
include_once(__DIR__ . '/MailerService.php');

class RouletteController
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = defined('BASE_URL') ? BASE_URL : '';
    }

    // ═══════════════════════════════════════════════════════════════
    // 🎰 INDEX — Page roulette
    // ═══════════════════════════════════════════════════════════════

    public function index(): void
    {
        $email = trim($_GET['email'] ?? $_SESSION['email_client'] ?? '');

        if (empty($email)) {
            $this->afficherFormulaireEmail();
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->afficherErreur('Email invalide. Veuillez entrer un email correct.');
            return;
        }

        $db = config::getConnexion();
        $client = $this->chargerClient($db, $email);

        $nbPaiements   = Roulette::compterPaiementsValides($db, $email);
        $spinsGagnes   = Roulette::calculerSpinsGagnes($nbPaiements);
        $spinsUtilises = Roulette::compterSpinsUtilises($db, $email);
        $spinsRestants = max(0, $spinsGagnes - $spinsUtilises);

        $prochainSpinSeuil = ($spinsGagnes + 1) * Roulette::SEUIL_PAR_SPIN;
        $paiementsRestants = max(0, $prochainSpinSeuil - $nbPaiements);
        $progress = $spinsGagnes > 0
            ? (($nbPaiements % Roulette::SEUIL_PAR_SPIN) / Roulette::SEUIL_PAR_SPIN) * 100
            : ($nbPaiements / Roulette::SEUIL_PAR_SPIN) * 100;

        $eligible = $spinsRestants > 0;
        $cadeaux  = Roulette::getCadeaux();
        $historique = Roulette::getHistorique($db, $email);

        $messageEligibilite = '';
        if (!$eligible) {
            if ($nbPaiements === 0) {
                $messageEligibilite = "Faites " . Roulette::SEUIL_PAR_SPIN . " paiements pour débloquer votre premier tour de roulette 🎰";
            } else {
                $messageEligibilite = "Plus que <strong>$paiementsRestants</strong> paiement" . ($paiementsRestants > 1 ? 's' : '') . " pour gagner un nouveau tour !";
            }
        }

        require_once __DIR__ . '/../view/FrontOffice/roulette.php';
    }

    // ═══════════════════════════════════════════════════════════════
    // 📧 FORMULAIRE EMAIL (quand pas connecté)
    // ═══════════════════════════════════════════════════════════════

    private function afficherFormulaireEmail(): void
    {
        $baseUrl = $this->baseUrl;
        $frontBase = $baseUrl . '/view/FrontOffice';
        require_once __DIR__ . '/../view/FrontOffice/roulette_email.php';
    }

    // ═══════════════════════════════════════════════════════════════
    // 🎯 TOURNER — Effectue le tirage (AJAX)
    // ═══════════════════════════════════════════════════════════════

    public function tourner(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Email invalide']);
            return;
        }

        $db = config::getConnexion();

        if (!Roulette::peutJouer($db, $email)) {
            echo json_encode([
                'success' => false,
                'message' => "Vous n'avez plus de tentatives disponibles. Effectuez " . Roulette::SEUIL_PAR_SPIN . " paiements pour en gagner une nouvelle !"
            ]);
            return;
        }

        // TIRAGE
        $resultat = Roulette::tirerCadeau();
        $cadeau   = $resultat['cadeau'];
        $index    = $resultat['index'];

        // CODE PROMO
        $codePromo = ($cadeau['type'] !== 'aucun') ? Roulette::genererCodePromo() : '';

        // INFOS CLIENT
        $client = $this->chargerClient($db, $email);
        $nbPaiements = Roulette::compterPaiementsValides($db, $email);

        // ENREGISTRER
        $ok = Roulette::enregistrerGain($db, [
            'email'             => $email,
            'nom'               => $client['nom'] ?? '',
            'prenom'            => $client['prenom'] ?? '',
            'paiements'         => $nbPaiements,
            'cadeau_label'      => $cadeau['label'],
            'cadeau_icone'      => $cadeau['icone'],
            'code_promo'        => $codePromo,
            'valeur_reduction'  => $cadeau['valeur'],
            'type_recompense'   => $cadeau['type'],
        ]);

        if (!$ok) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement']);
            return;
        }

        // EMAIL (si gain réel)
        $emailEnvoye = false;
        if ($cadeau['type'] !== 'aucun') {
            try {
                $mailer = new MailerService();
                $resEmail = $mailer->envoyerNotificationRoulette([
                    'email'        => $email,
                    'nom'          => $client['nom'] ?? '',
                    'prenom'       => $client['prenom'] ?? '',
                    'cadeau_label' => $cadeau['label'],
                    'cadeau_icone' => $cadeau['icone'],
                    'code_promo'   => $codePromo,
                    'valeur'       => $cadeau['valeur'],
                ]);
                $emailEnvoye = $resEmail['success'] ?? false;
            } catch (Exception $e) {
                $emailEnvoye = false;
            }
        }

        echo json_encode([
            'success'      => true,
            'index'        => $index,
            'cadeau'       => $cadeau,
            'code_promo'   => $codePromo,
            'email_envoye' => $emailEnvoye,
            'spins_restants' => max(0, Roulette::calculerSpinsGagnes($nbPaiements) - Roulette::compterSpinsUtilises($db, $email) - 1),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // 📜 HISTORIQUE
    // ═══════════════════════════════════════════════════════════════

    public function historique(): void
    {
        $email = trim($_GET['email'] ?? '');

        if (empty($email)) {
            $this->afficherErreur('Email manquant');
            return;
        }

        $db = config::getConnexion();
        $historique = Roulette::getHistorique($db, $email);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'historique' => $historique]);
    }

    // ═══════════════════════════════════════════════════════════════
    // 🎟️ VALIDER PROMO — Vérifie un code promo (AJAX depuis paiement.php)
    // ═══════════════════════════════════════════════════════════════

    public function validerPromo(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        $code = trim($_POST['code'] ?? '');
        if (empty($code)) {
            echo json_encode(['success' => false, 'message' => 'Code vide']);
            return;
        }

        $db = config::getConnexion();
        $result = Roulette::validerCodePromo($db, $code);

        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Code promo introuvable.']);
            return;
        }

        if (isset($result['error'])) {
            echo json_encode(['success' => false, 'message' => $result['error']]);
            return;
        }

        $type = $result['type_recompense'] ?? '';
        $valeur = (float)($result['valeur_reduction'] ?? 0);

        $label = '';
        if ($type === 'reduction_pct') $label = '-' . (int)$valeur . '%';
        elseif ($type === 'reduction_fixe') $label = '-' . (int)$valeur . ' TND';
        elseif ($type === 'bonus_service') $label = 'Bonus service gratuit';

        echo json_encode([
            'success' => true,
            'gain'    => $result,
            'label'   => $label,
            'type'    => $type,
            'valeur'  => $valeur,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // 🛠 HELPERS
    // ═══════════════════════════════════════════════════════════════

    private function chargerClient(PDO $db, string $email): array
    {
        try {
            $stmt = $db->prepare("
                SELECT nom, prenom, email
                FROM devis
                WHERE email = :email
                ORDER BY date_demande DESC
                LIMIT 1
            ");
            $stmt->execute([':email' => $email]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['email' => $email, 'nom' => '', 'prenom' => 'Client'];
        } catch (Exception $e) {
            return ['email' => $email, 'nom' => '', 'prenom' => 'Client'];
        }
    }

    private function afficherErreur(string $message): void
    {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Erreur — Protex</title></head><body>";
        echo "<div style='max-width:500px;margin:80px auto;padding:30px;background:#fff5f5;border:1px solid #fecaca;border-radius:14px;font-family:Arial;'>";
        echo "<h2 style='color:#991b1b;margin-top:0;'>⚠️ " . htmlspecialchars($message) . "</h2>";
        echo "<p><a href='" . $this->baseUrl . "/view/FrontOffice/client.html' style='color:#FF6B1A;font-weight:700;'>← Retour à mon espace client</a></p>";
        echo "</div></body></html>";
    }
}

// ═══════════════════════════════════════════════════════════════
// ROUTEUR
// ═══════════════════════════════════════════════════════════════

$controller = new RouletteController();
$action     = $_GET['action'] ?? 'index';

switch ($action) {
    case 'tourner':       $controller->tourner();       break;
    case 'historique':    $controller->historique();    break;
    case 'valider_promo': $controller->validerPromo();  break;
    default:              $controller->index();         break;
}

