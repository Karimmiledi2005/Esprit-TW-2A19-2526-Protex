<?php
/**
 * StripePaymentController.php
 * Gestion des paiements Stripe via PaymentIntent (Stripe Elements)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/stripe-php/init.php';

require_once __DIR__ . '/../bootstrap.php';

class StripePaymentController
{
    private \Stripe\StripeClient $stripe;
    private array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/stripe_config.php';
        \Stripe\Stripe::setApiKey($this->config['secret_key']);
        \Stripe\Stripe::setCABundlePath('C:\xampp\apache\bin\curl-ca-bundle.crt');
        $this->stripe = new \Stripe\StripeClient($this->config['secret_key']);
    }

    /**
     * Crée un PaymentIntent et retourne le clientSecret
     */
    public function creerSession(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);

        $offreId   = (int)($input['offre_id'] ?? 0);
        $montant   = (float)($input['montant'] ?? 0);
        $nom       = trim($input['nom'] ?? '');
        $email     = trim($input['email'] ?? '');
        $periode   = trim($input['periode'] ?? 'mensuel');
        $devisId   = !empty($input['devis_id']) ? (int)$input['devis_id'] : null;
        $codePromo = trim($input['code_promo'] ?? '');

        if ($offreId <= 0 || $montant <= 0) {
            echo json_encode(['error' => 'Données invalides']);
            return;
        }

        $db = config::getConnexion();

        $stmt = $db->prepare("SELECT * FROM offre WHERE id_offre = ? AND statut = 'active'");
        $stmt->execute([$offreId]);
        $offre = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$offre) {
            echo json_encode(['error' => 'Offre introuvable ou inactive']);
            return;
        }

        $reference    = 'PAY-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));
        $datePaiement = date('Y-m-d H:i:s');
        $dateEcheance = $periode === 'annuel'
            ? date('Y-m-d', strtotime('+1 year'))
            : date('Y-m-d', strtotime('+1 month'));

        $hasPromo = false;
        try {
            $cols = $db->query("SHOW COLUMNS FROM paiement LIKE 'code_promo'");
            $hasPromo = $cols->rowCount() > 0;
        } catch (Throwable $e) {}

        if ($hasPromo) {
            $sql = "INSERT INTO paiement
                (id_offre, id_devis, reference, montant, methode, periodicite, statut, date_paiement, date_echeance, code_promo)
                VALUES (?, ?, ?, ?, 'stripe', ?, 'en_attente', ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$offreId, $devisId, $reference, $montant, $periode, $datePaiement, $dateEcheance, $codePromo ?: null]);
        } else {
            $sql = "INSERT INTO paiement
                (id_offre, id_devis, reference, montant, methode, periodicite, statut, date_paiement, date_echeance)
                VALUES (?, ?, ?, ?, 'stripe', ?, 'en_attente', ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$offreId, $devisId, $reference, $montant, $periode, $datePaiement, $dateEcheance]);
        }

        $paiementId = (int)$db->lastInsertId();

        try {
            $intent = $this->stripe->paymentIntents->create([
                'amount'      => (int)round($montant * 100),
                'currency'    => strtolower($this->config['currency']),
                'description' => "Paiement {$offre['nom_offre']} — {$reference}",
                'metadata'    => [
                    'paiement_id' => $paiementId,
                    'reference'   => $reference,
                    'offre_id'    => $offreId,
                    'email'       => $email,
                    'nom'         => $nom,
                    'code_promo'  => $codePromo,
                ],
            ]);

            echo json_encode([
                'clientSecret'  => $intent->client_secret,
                'reference'     => $reference,
                'paiement_id'   => $paiementId,
                'publishableKey' => $this->config['publishable_key'],
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe API Error: ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur Stripe']);
        }
    }

    /**
     * Webhook Stripe — reçoit les événements de paiement
     */
    public function webhook(): void
    {
        $payload   = file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $this->config['webhook_secret']
            );
        } catch (\UnexpectedValueException $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Payload invalide']);
            return;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Signature invalide']);
            return;
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $intent = $event->data->object;
                $paiementId = (int)($intent->metadata->paiement_id ?? 0);
                if ($paiementId > 0) {
                    $this->validerPaiement($paiementId);
                }
                break;

            case 'payment_intent.payment_failed':
                $intent = $event->data->object;
                $paiementId = (int)($intent->metadata->paiement_id ?? 0);
                if ($paiementId > 0) {
                    $db = config::getConnexion();
                    $stmt = $db->prepare("UPDATE paiement SET statut = 'refuse' WHERE id_paiement = ?");
                    $stmt->execute([$paiementId]);
                }
                break;
        }

        http_response_code(200);
        echo json_encode(['received' => true]);
    }

    /**
     * Confirme manuellement le paiement (fallback si webhook non reçu)
     */
    public function confirmer(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $paiementId = (int)($input['paiement_id'] ?? 0);

        if ($paiementId <= 0) {
            echo json_encode(['error' => 'ID paiement invalide']);
            return;
        }

        try {
            $db = config::getConnexion();
            $stmt = $db->prepare("SELECT statut, id_offre FROM paiement WHERE id_paiement = ?");
            $stmt->execute([$paiementId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                echo json_encode(['error' => 'Paiement introuvable']);
                return;
            }

            if ($row['statut'] === 'valide') {
                echo json_encode(['success' => true, 'statut' => 'deja_valide']);
                return;
            }

            $this->validerPaiement($paiementId);
            echo json_encode(['success' => true, 'statut' => 'valide']);
        } catch (\Exception $e) {
            error_log('StripePaymentController::confirmer error: ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
        }
    }

    /**
     * Vérifie le statut d'un paiement
     */
    public function verifierStatus(): void
    {
        header('Content-Type: application/json');

        $paiementId = (int)($_GET['paiement_id'] ?? 0);

        if ($paiementId <= 0) {
            echo json_encode(['error' => 'ID invalide']);
            return;
        }

        try {
            $db = config::getConnexion();
            $stmt = $db->prepare("
                SELECT p.statut, p.reference, p.montant, o.nom_offre
                FROM paiement p
                LEFT JOIN offre o ON p.id_offre = o.id_offre
                WHERE p.id_paiement = ?
            ");
            $stmt->execute([$paiementId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                echo json_encode([
                    'success'   => true,
                    'statut'    => $row['statut'],
                    'reference' => $row['reference'],
                    'montant'   => $row['montant'],
                    'offre'     => $row['nom_offre'],
                ]);
            } else {
                echo json_encode(['error' => 'Paiement introuvable']);
            }
        } catch (\Exception $e) {
            error_log('StripePaymentController::verifierStatus error: ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
        }
    }

    private function validerPaiement(int $paiementId): void
    {
        try {
            $db = config::getConnexion();
            $stmt = $db->prepare("UPDATE paiement SET statut = 'valide' WHERE id_paiement = ?");
            $stmt->execute([$paiementId]);

            $stmt2 = $db->prepare("
                SELECT d.email, p.id_offre, p.code_promo, p.id_contrat, p.id_user, p.montant
                FROM paiement p
                LEFT JOIN devis d ON p.id_devis = d.id_devis
                WHERE p.id_paiement = ?
            ");
            $stmt2->execute([$paiementId]);
            $row = $stmt2->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // ── Activer automatiquement le contrat lié ──
                if (!empty($row['id_contrat'])) {
                    $ctr = $db->prepare("UPDATE contrat SET statut_contrat = 'actif' WHERE id_contrat = ? AND statut_contrat = 'en attente'");
                    $ctr->execute([(int)$row['id_contrat']]);
                }

                // ── Points fidélité pour paiement validé ──
                if (!empty($row['id_user'])) {
                    $db->prepare("INSERT INTO points_fidelite (id_user, points, motif) VALUES (?, 10, 'Paiement validé')")->execute([(int)$row['id_user']]);
                }

                if (!empty($row['code_promo'])) {
                    require_once __DIR__ . '/../model/Roulette.php';
                    Roulette::marquerCodeUtilise($db, $row['code_promo']);
                }

                if (!empty($row['email'])) {
                    $nbPaiements = Roulette::compterPaiementsValides($db, $row['email']);
                    if ($nbPaiements > 0 && $nbPaiements % Roulette::SEUIL_PAR_SPIN === 0) {
                        $nbSpins = $nbPaiements / Roulette::SEUIL_PAR_SPIN;
                        require_once __DIR__ . '/MailerService.php';
                        $mailer = new MailerService();
                        $mailer->envoyer(
                            $row['email'],
                            'Client Protex',
                            '🎰 Bravo — Vous avez débloqué un tour de roulette',
                            "<html><body><h1>Bravo!</h1><p>Vous avez débloqué {$nbSpins} spin(s) de roulette!</p></body></html>"
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            error_log('Erreur validation paiement: ' . $e->getMessage());
        }
    }
}

/* ═══ ROUTEUR ═══ */
$controller = new StripePaymentController();
$action     = $_GET['action'] ?? '';

switch ($action) {
    case 'creer_session':  $controller->creerSession();   break;
    case 'webhook':        $controller->webhook();         break;
    case 'confirmer':      $controller->confirmer();       break;
    case 'verifier':       $controller->verifierStatus();  break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Action inconnue']);
        break;
}
