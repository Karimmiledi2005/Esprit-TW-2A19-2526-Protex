<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/Paiement.php';
require_once __DIR__ . '/../../model/Roulette.php';

/**
 * ============================================================
 * confirmer_paiement.php
 * Traitement du paiement côté FrontOffice
 * ============================================================
 */

/* ============================================================
   CONFIGURATION
   ============================================================ */
const FRONT_PAYMENT_PAGE = 'paiement.php';
const DEFAULT_STATUS = 'en_attente';
const ALLOWED_METHODS = ['carte', 'virement', 'mobile'];
const ALLOWED_PERIODS = ['mensuel', 'annuel'];

/* ============================================================
   OUTILS
   ============================================================ */
function redirectToPayment(int $offreId, array $params = []): never
{
    $query = array_merge(['offre' => $offreId], $params);
    header('Location: ' . FRONT_PAYMENT_PAGE . '?' . http_build_query($query));
    exit;
}

function cleanString(?string $value): string
{
    return trim((string)$value);
}

function cleanCardNumber(?string $value): string
{
    return preg_replace('/\D/', '', (string)$value) ?? '';
}

function cleanPhone(?string $value): string
{
    return preg_replace('/[^\d]/', '', (string)$value) ?? '';
}

function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidMethod(string $method): bool
{
    return in_array($method, ALLOWED_METHODS, true);
}

function isValidPeriod(string $period): bool
{
    return in_array($period, ALLOWED_PERIODS, true);
}

function isValidCardNumber(string $cardNumber): bool
{
    return preg_match('/^\d{16}$/', $cardNumber) === 1;
}

function isValidCvv(string $cvv): bool
{
    return preg_match('/^\d{3,4}$/', $cvv) === 1;
}

function isValidPhone(string $phone): bool
{
    return preg_match('/^\d{8,15}$/', $phone) === 1;
}

function isValidExpiry(string $expiry): bool
{
    if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
        return false;
    }

    [$month, $year] = explode('/', $expiry);
    $month = (int)$month;
    $year = (int)$year + 2000;

    $expiryDate = DateTime::createFromFormat('Y-m-d H:i:s', sprintf('%04d-%02d-01 23:59:59', $year, $month));
    if (!$expiryDate) {
        return false;
    }

    $expiryDate->modify('last day of this month');
    $now = new DateTime();

    return $expiryDate >= $now;
}

function generateReference(): string
{
    return 'PAY-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));
}

function maskCardNumber(?string $cardNumber): ?string
{
    $cardNumber = cleanCardNumber($cardNumber);

    if ($cardNumber === '') {
        return null;
    }

    return '**** **** **** ' . substr($cardNumber, -4);
}

function computeDueDate(string $periodicity): string
{
    if ($periodicity === 'annuel') {
        return date('Y-m-d', strtotime('+1 year'));
    }

    return date('Y-m-d', strtotime('+1 month'));
}

function getPostValue(string $key, string $default = ''): string
{
    return cleanString($_POST[$key] ?? $default);
}

function collectFormData(): array
{
    return [
        'offre_id'     => (int)($_POST['offre_id'] ?? 0),
        'montant'      => (float)($_POST['montant'] ?? 0),
        'methode'      => getPostValue('methode'),
        'periodicite'  => getPostValue('periodicite'),
        'cardnumber'   => getPostValue('cardnumber'),
        'fullname'     => getPostValue('fullname'),
        'email'        => getPostValue('email'),
        'phone'        => getPostValue('phone'),
        'cardholder'   => getPostValue('cardholder'),
        'expiry'       => getPostValue('expiry'),
        'cvv'          => getPostValue('cvv'),
        'address'      => getPostValue('address'),
        'code_promo'   => getPostValue('code_promo'),
    ];
}

function validatePaymentData(array $data): array
{
    $errors = [];

    if ($data['offre_id'] <= 0) {
        $errors[] = 'Offre invalide.';
    }

    if ($data['montant'] <= 0) {
        $errors[] = 'Montant invalide.';
    }

    if (!isValidMethod($data['methode'])) {
        $errors[] = 'Méthode invalide.';
    }

    if (!isValidPeriod($data['periodicite'])) {
        $errors[] = 'Périodicité invalide.';
    }

    if ($data['fullname'] === '') {
        $errors[] = 'Le nom complet est obligatoire.';
    }

    if ($data['email'] === '') {
        $errors[] = 'L’adresse e-mail est obligatoire.';
    } elseif (!isValidEmail($data['email'])) {
        $errors[] = 'Adresse e-mail invalide.';
    }

    if ($data['phone'] === '') {
        $errors[] = 'Le téléphone est obligatoire.';
    } elseif (!isValidPhone(cleanPhone($data['phone']))) {
        $errors[] = 'Numéro de téléphone invalide.';
    }

    if ($data['cardholder'] === '') {
        $errors[] = 'Le titulaire est obligatoire.';
    }

    if ($data['expiry'] === '') {
        $errors[] = 'La date d’expiration est obligatoire.';
    } elseif (!isValidExpiry($data['expiry'])) {
        $errors[] = 'Date d’expiration invalide ou carte expirée.';
    }

    if ($data['cvv'] === '') {
        $errors[] = 'Le CVV est obligatoire.';
    } elseif (!isValidCvv($data['cvv'])) {
        $errors[] = 'CVV invalide.';
    }

    if ($data['address'] === '') {
        $errors[] = 'L’adresse de facturation est obligatoire.';
    }

    if ($data['methode'] === 'carte') {
        $card = cleanCardNumber($data['cardnumber']);

        if ($card === '') {
            $errors[] = 'Le numéro de carte est obligatoire.';
        } elseif (!isValidCardNumber($card)) {
            $errors[] = 'Le numéro de carte doit contenir 16 chiffres.';
        }
    }

    return $errors;
}

function buildPaiementInsertPayload(array $data): array
{
    $reference = generateReference();
    $datePaiement = date('Y-m-d H:i:s');
    $dateEcheance = computeDueDate($data['periodicite']);
    $carteMasquee = $data['methode'] === 'carte'
        ? maskCardNumber($data['cardnumber'])
        : null;

    return [
        'id_offre'         => $data['offre_id'],
        'reference'        => $reference,
        'montant'          => $data['montant'],
        'methode'          => $data['methode'],
        'periodicite'      => $data['periodicite'],
        'statut'           => DEFAULT_STATUS,
        'date_paiement'    => $datePaiement,
        'date_echeance'    => $dateEcheance,
        'num_carte_masque' => $carteMasquee,
        'code_promo'       => $data['code_promo'] !== '' ? strtoupper(trim($data['code_promo'])) : null,
    ];
}

function insertPaiement(PDO $db, array $payload): bool
{
    $hasPromoCol = false;
    try {
        $cols = $db->query("SHOW COLUMNS FROM paiement LIKE 'code_promo'");
        $hasPromoCol = $cols && $cols->rowCount() > 0;
    } catch (Throwable $ignore) {}

    if ($hasPromoCol) {
        $sql = "INSERT INTO paiement (
                    id_offre,
                    reference,
                    montant,
                    methode,
                    periodicite,
                    statut,
                    date_paiement,
                    date_echeance,
                    num_carte_masque,
                    code_promo
                ) VALUES (
                    :id_offre,
                    :reference,
                    :montant,
                    :methode,
                    :periodicite,
                    :statut,
                    :date_paiement,
                    :date_echeance,
                    :num_carte_masque,
                    :code_promo
                )";
    } else {
        $sql = "INSERT INTO paiement (
                    id_offre,
                    reference,
                    montant,
                    methode,
                    periodicite,
                    statut,
                    date_paiement,
                    date_echeance,
                    num_carte_masque
                ) VALUES (
                    :id_offre,
                    :reference,
                    :montant,
                    :methode,
                    :periodicite,
                    :statut,
                    :date_paiement,
                    :date_echeance,
                    :num_carte_masque
                )";
    }

    $stmt = $db->prepare($sql);

    return $stmt->execute($payload);
}

/* ============================================================
   CONTRÔLE D’ACCÈS
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: ' . FRONT_PAYMENT_PAGE);
    exit;
}

/* ============================================================
   RÉCUPÉRATION DES DONNÉES
   ============================================================ */
$data = collectFormData();

/* ============================================================
   VALIDATION
   ============================================================ */
$errors = validatePaymentData($data);

if (!empty($errors)) {
    redirectToPayment($data['offre_id'], [
        'error' => 1
    ]);
}

/* ============================================================
   PRÉPARATION INSERTION
   ============================================================ */
$payload = buildPaiementInsertPayload($data);

/* ============================================================
   INSERTION BDD
   ============================================================ */
try {
    $db = config::getConnexion();

    $ok = insertPaiement($db, $payload);

    if ($ok) {
        $redirectParams = [
            'success'   => 1,
            'reference' => $payload['reference'],
        ];
        if (!empty($data['code_promo'])) {
            $redirectParams['promo'] = $data['code_promo'];
        }
        redirectToPayment($data['offre_id'], $redirectParams);
    }

    redirectToPayment($data['offre_id'], [
        'error' => 1
    ]);

} catch (Throwable $e) {
    error_log('Erreur confirmer_paiement.php : ' . $e->getMessage());

    redirectToPayment($data['offre_id'], [
        'error' => 1
    ]);
}

