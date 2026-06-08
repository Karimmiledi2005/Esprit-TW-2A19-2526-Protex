<?php
/**
 * controller/FrontOffice/PaiementController.php
 *
 * FIX P-01 : Remplace Database::getConnection() → config::getConnexion()
 * FIX P-02 : Remplace OffreModel/PaiementModel → Offre/Paiement (noms réels des classes)
 * FIX P-15 : Ajoute CSRF token dans le traitement POST
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/Offre.php';
require_once __DIR__ . '/../../model/Paiement.php';

class PaiementFrontController
{
    private Offre   $offreModel;
    private Paiement $paiementModel;

    public function __construct()
    {
        $this->offreModel    = new Offre();
        $this->paiementModel = new Paiement();
    }

    public function index(): void
    {
        $offreId = isset($_GET['offre']) ? (int)$_GET['offre'] : 1;
        $offre   = $this->offreModel->getById($offreId);

        if (!$offre || $offre['statut'] !== 'active') {
            $offres  = $this->offreModel->getActives();
            $offre   = $offres[0] ?? null;
            $offreId = $offre['id_offre'] ?? 1;
        }

        $errors  = [];
        $success = [];
        $old     = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // FIX P-15 : validation CSRF
            if (class_exists('CsrfHelper')) CsrfHelper::validate();

            $old = array_map(
                fn($v) => htmlspecialchars(trim($v ?? ''), ENT_QUOTES, 'UTF-8'),
                $_POST
            );

            $periodicite = $_POST['periodicite'] ?? 'mensuel';
            $montant     = ($periodicite === 'annuel')
                ? $offre['prix_annuel']
                : $offre['prix_mensuel'];

            $errors = $this->valider($_POST);

            if (empty($errors)) {
                $userId = (int)($_SESSION['user_id'] ?? $_SESSION['id_user'] ?? 0);

                $data = [
                    'id_offre'    => $offreId,
                    'id_user'     => $userId ?: null, // FIX LM-02 : lier paiement à user
                    'montant'     => $montant,
                    'methode'     => $_POST['methode']    ?? 'carte',
                    'periodicite' => $periodicite,
                    'num_carte'   => $_POST['cardnumber'] ?? '',
                ];

                $result = $this->paiementModel->creer($data);

                if ($result) {
                    $ref = $this->getDerniereReference();
                    $dernierPaiement = $this->paiementModel->getByReference($ref);

                    $success = [
                        'reference' => $dernierPaiement['reference']  ?? 'PTX-XXXXXXXX',
                        'offre'     => $offre['nom_offre'],
                        'montant'   => $montant . ' TND / ' . $periodicite,
                        'date'      => date('d/m/Y à H:i'),
                        'titulaire' => htmlspecialchars(trim($_POST['fullname'])),
                        'echeance'  => ($periodicite === 'annuel')
                                        ? date('d/m/Y', strtotime('+1 year'))
                                        : date('d/m/Y', strtotime('+1 month')),
                    ];
                } else {
                    $errors['general'] = 'Une erreur est survenue. Veuillez réessayer.';
                }
            }
        }

        require_once __DIR__ . '/../../view/FrontOffice/paiement.php';
    }

    private function valider(array $data): array
    {
        $errors   = [];
        $required = [
            'fullname'   => 'Nom complet',
            'email'      => 'Adresse e-mail',
            'phone'      => 'Téléphone',
            'cardnumber' => 'Numéro de carte',
            'cardholder' => 'Titulaire',
            'expiry'     => 'Expiration',
            'cvv'        => 'CVV',
            'address'    => 'Adresse de facturation',
        ];

        foreach ($required as $field => $label) {
            if (empty(trim($data[$field] ?? ''))) {
                $errors[$field] = "Le champ « {$label} » est obligatoire.";
            }
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Adresse e-mail invalide.';
        }

        if (!empty($data['cardnumber'])) {
            $card = preg_replace('/\s+/', '', $data['cardnumber']);
            if (!preg_match('/^\d{16}$/', $card)) {
                $errors['cardnumber'] = 'Le numéro de carte doit contenir 16 chiffres.';
            }
        }

        if (!empty($data['expiry'])) {
            if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $data['expiry'])) {
                $errors['expiry'] = 'Format attendu : MM/AA.';
            } else {
                [$m, $y] = explode('/', $data['expiry']);
                if (DateTime::createFromFormat('m/y', "{$m}/{$y}") < new DateTime()) {
                    $errors['expiry'] = 'Cette carte est expirée.';
                }
            }
        }

        if (!empty($data['cvv']) && !preg_match('/^\d{3,4}$/', $data['cvv'])) {
            $errors['cvv'] = 'Le CVV doit contenir 3 ou 4 chiffres.';
        }

        if (!empty($data['phone'])) {
            $phone = preg_replace('/[\s\+\-\(\)]/', '', $data['phone']);
            if (!preg_match('/^\d{8,15}$/', $phone)) {
                $errors['phone'] = 'Numéro de téléphone invalide.';
            }
        }

        return $errors;
    }

    // FIX P-01 : utilise config::getConnexion() au lieu de Database::getConnection()
    private function getDerniereReference(): string
    {
        $pdo  = config::getConnexion();
        $stmt = $pdo->prepare("SELECT reference FROM paiement ORDER BY id_paiement DESC LIMIT 1");
        $stmt->execute();
        return (string)($stmt->fetchColumn() ?? '');
    }
}

$controller = new PaiementFrontController();
$controller->index();
