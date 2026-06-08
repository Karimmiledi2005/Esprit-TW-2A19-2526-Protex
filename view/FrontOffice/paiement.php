<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireLogin();

/* =============================================
   paiement.php — Protex FrontOffice
   Paiement réel avec Stripe Elements intégré
   ============================================= */

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function findProjectConfig(): ?string
{
    $paths = [
        __DIR__ . '/../../config.php',
        __DIR__ . '/../../../config.php',
        __DIR__ . '/config.php',
        dirname(__DIR__, 2) . '/config.php',
        dirname(__DIR__, 3) . '/config.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    return null;
}

$configPath = findProjectConfig();
if ($configPath !== null) {
    require_once $configPath;
}

$offreId = 0;
if (isset($_GET['id_offre'])) {
    $offreId = (int)$_GET['id_offre'];
} elseif (isset($_GET['offre'])) {
    $offreId = (int)$_GET['offre'];
}

// Params from wizard_souscription
$idDevisFromWizard = (int)($_GET['id_devis'] ?? 0);
$modeFromWizard    = in_array($_GET['mode'] ?? '', ['mensuel','trimestriel','annuel']) ? $_GET['mode'] : '';
$montantFromWizard = (float)($_GET['montant'] ?? 0);

$offresFallback = [
    1 => ['id_offre' => 1, 'nom' => 'Auto Premium', 'icon' => 'bi-car-front', 'color' => 'auto', 'prix_mensuel' => 85, 'prix_annuel' => 950, 'type' => 'Tous risques', 'duree' => '1 mois', 'ref' => 'POL-0001', 'description' => 'Protection automobile complète avec couverture tous risques et assistance 24/7.'],
    2 => ['id_offre' => 2, 'nom' => 'Santé Premium', 'icon' => 'bi-heart-pulse', 'color' => 'sante', 'prix_mensuel' => 120, 'prix_annuel' => 1350, 'type' => 'Couverture médicale', 'duree' => '12 mois', 'ref' => 'POL-0002', 'description' => 'Couverture médicale élargie hospitalisation, consultations et médicaments.'],
    3 => ['id_offre' => 3, 'nom' => 'Habitation Eco', 'icon' => 'bi-house-check', 'color' => 'maison', 'prix_mensuel' => 45, 'prix_annuel' => 500, 'type' => 'Protection habitation', 'duree' => '12 mois', 'ref' => 'POL-0003', 'description' => 'Protection complète de votre logement à un tarif accessible et transparent.'],
    4 => ['id_offre' => 4, 'nom' => 'Vie Sérénité', 'icon' => 'bi-shield-heart', 'color' => 'vie', 'prix_mensuel' => 25, 'prix_annuel' => 290, 'type' => 'Assurance vie', 'duree' => '24 mois', 'ref' => 'POL-0004', 'description' => 'Assurez l\'avenir de vos proches avec capital décès et invalidité.'],
];

function normalizeOffre(array $row, int $id): array
{
    $prixMensuel = $row['prix_mensuel'] ?? $row['prix'] ?? $row['montant'] ?? 0;
    $prixAnnuel  = $row['prix_annuel'] ?? ((float)$prixMensuel * 12);
    return [
        'id_offre'     => (int)($row['id_offre'] ?? $id),
        'nom'          => (string)($row['nom_offre'] ?? $row['nom'] ?? $row['titre'] ?? 'Offre Protex'),
        'icon'         => 'bi-shield-check',
        'color'        => strtolower((string)($row['type_offre'] ?? $row['type'] ?? 'auto')),
        'prix_mensuel' => (float)$prixMensuel,
        'prix_annuel'  => (float)$prixAnnuel,
        'type'         => (string)($row['type_offre'] ?? $row['type'] ?? 'Assurance'),
        'duree'        => (string)($row['duree_min'] ?? $row['duree'] ?? '12 mois'),
        'ref'          => 'OFF-' . (int)($row['id_offre'] ?? $id),
        'description'  => (string)($row['description'] ?? 'Offre sélectionnée depuis la base de données.'),
    ];
}

$offres = $offresFallback;
$offre = null;

if ($offreId <= 0) {
    $offreId = 1;
}

try {
    if (class_exists('config')) {
        $db = config::getConnexion();
        $tableName = 'offres';
        try {
            $test = $db->query("SHOW TABLES LIKE 'offres'");
            if ($test && $test->rowCount() === 0) {
                $tableName = 'offre';
            }
        } catch (Throwable $ignore) {
            $tableName = 'offres';
        }
        $stmt = $db->prepare("SELECT * FROM {$tableName} WHERE id_offre = :id_offre LIMIT 1");
        $stmt->execute([':id_offre' => $offreId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $offre = normalizeOffre($row, $offreId);
        }
    }
} catch (Throwable $e) {
}

if ($offre === null) {
    $offre = $offresFallback[$offreId] ?? $offresFallback[1];
    $offreId = (int)$offre['id_offre'];
}

$offres = $offresFallback;
if (!isset($offres[$offreId])) {
    $offres[$offreId] = $offre;
}

$showSuccess = isset($_GET['success']) && $_GET['success'] === '1';
$showError   = isset($_GET['error']) && $_GET['error'] === '1';
$reference   = $_GET['reference'] ?? '';

$promoInput  = isset($_GET['promo']) ? trim((string)$_GET['promo']) : '';
$promoGain   = null;
$promoError  = '';
$montantReduit = null;
$reductionLabel = '';

if ($promoInput !== '') {
    try {
        if (class_exists('config')) {
            require_once __DIR__ . '/../../model/Roulette.php';
            $dbPromo = config::getConnexion();
            $result = Roulette::validerCodePromo($dbPromo, $promoInput);
            if ($result && !isset($result['error'])) {
                $promoGain = $result;
                $montantOriginal = $offre['prix_mensuel'];
                $montantReduit = Roulette::appliquerReduction($promoGain, $montantOriginal);
                $type = $promoGain['type_recompense'] ?? $promoGain['cadeau_type'] ?? '';
                $valeur = (float)($promoGain['valeur_reduction'] ?? $promoGain['valeur'] ?? 0);
                if ($type === 'reduction_pct') {
                    $reductionLabel = '-' . (int)$valeur . '%';
                } elseif ($type === 'reduction_fixe') {
                    $reductionLabel = '-' . (int)$valeur . ' TND';
                } elseif ($type === 'bonus_service') {
                    $reductionLabel = 'Bonus service';
                    $montantReduit = $montantOriginal;
                }
            } else {
                $promoError = $result['error'] ?? 'Code promo invalide.';
            }
        }
    } catch (Throwable $e) {
        $promoError = 'Impossible de vérifier le code promo.';
    }
}

$old = [
    'fullname'   => e($_GET['fullname']   ?? ''),
    'email'      => e($_GET['email']      ?? ''),
    'phone'      => e($_GET['phone']      ?? ''),
    'cardnumber' => e($_GET['cardnumber'] ?? ''),
    'cardholder' => e($_GET['cardholder'] ?? ''),
    'expiry'     => e($_GET['expiry']     ?? ''),
    'cvv'        => e($_GET['cvv']        ?? ''),
    'address'    => e($_GET['address']    ?? ''),
    'periodicite'=> e($_GET['periodicite'] ?? ($modeFromWizard ?: 'mensuel')),
    'methode'    => e($_GET['methode'] ?? 'carte'),
    'promo'      => e($promoInput),
];

if (!in_array($old['periodicite'], ['mensuel', 'annuel'], true)) {
    $old['periodicite'] = 'mensuel';
}

if ($montantFromWizard > 0) {
    $defaultMontant = $montantFromWizard;
} else {
    $defaultMontant = $old['periodicite'] === 'annuel'
        ? $offre['prix_annuel']
        : $offre['prix_mensuel'];
}

$stripeConfig = require __DIR__ . '/../../controller/stripe_config.php';
$publishableKey = $stripeConfig['publishable_key'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Paiement — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <script src="https://js.stripe.com/v3/"></script>

    <style>
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(20px); }
            to   { opacity:1; transform:translateY(0); }
        }
        @keyframes pulse-green {
            0%,100% { box-shadow: 0 0 0 0 rgba(26,58,122,0.3); }
            50%     { box-shadow: 0 0 0 8px rgba(26,58,122,0); }
        }
        .page-header {
            padding: 24px 32px 0;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 8px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .page-title-main {
            font-family: 'Sora', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: #15233C;
        }
        .page-breadcrumb {
            font-size: 12px;
            color: rgba(21,35,60,0.5);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .page-breadcrumb span { color: #FF6B1A; font-weight: 500; }
        .pay-hero {
            background: linear-gradient(135deg, #1A3A7A 0%, #0f2456 100%);
            border-radius: 22px;
            padding: 30px 36px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            animation: fadeUp .4s ease both;
            position: relative;
            overflow: hidden;
        }
        .pay-hero::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 220px;
            height: 220px;
            background: rgba(255,107,26,0.12);
            border-radius: 50%;
        }
        .pay-hero::after {
            content: '';
            position: absolute;
            bottom: -60px;
            right: 150px;
            width: 160px;
            height: 160px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
        }
        .pay-hero-content { position: relative; z-index: 1; }
        .pay-hero-title {
            font-family: 'Sora', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 8px;
        }
        .pay-hero-sub {
            font-size: 14px;
            color: rgba(255,255,255,0.65);
            line-height: 1.65;
            max-width: 580px;
        }
        .pay-pills { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; }
        .pay-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 14px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.15);
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.85);
            font-size: 12px;
            font-weight: 500;
        }
        .pay-pill i { color: #4ade80; }
        .pay-hero-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        .pay-offre-btn {
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.75);
            background: rgba(255,255,255,0.08);
        }
        .pay-offre-btn:hover { background: rgba(255,255,255,0.15); color: #fff; }
        .pay-offre-btn.active { background: #FF6B1A; border-color: #FF6B1A; color: #fff; }
        .alert-error {
            background: rgba(230,57,70,0.06);
            border: 1px solid rgba(230,57,70,0.20);
            border-radius: 14px;
            padding: 16px 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 24px;
            animation: fadeUp .3s ease both;
        }
        .alert-error > i { color: #e63946; font-size: 18px; flex-shrink: 0; margin-top: 2px; }
        .alert-error strong { color: #e63946; font-size: 13px; }
        .alert-error p { margin: 6px 0 0; color: rgba(21,35,60,0.7); font-size: 13px; line-height: 1.8; }
        .pay-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 24px;
            animation: fadeUp .4s .1s ease both;
            margin-bottom: 28px;
        }
        .pay-panel {
            background: #fff;
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(26,58,122,0.07);
            transition: box-shadow 0.25s;
        }
        .pay-panel:hover { box-shadow: 0 8px 32px rgba(26,58,122,0.10); }
        .pay-panel-head {
            padding: 22px 26px 16px;
            border-bottom: 1px solid rgba(26,58,122,0.07);
            background: rgba(26,58,122,0.02);
        }
        .pay-panel-title {
            font-family: 'Sora', sans-serif;
            font-size: 17px;
            font-weight: 700;
            color: #15233C;
            margin-bottom: 3px;
        }
        .pay-panel-sub { font-size: 12px; color: rgba(21,35,60,0.5); }
        .pay-panel-body { padding: 24px 26px; }
        .summary-top {
            background: linear-gradient(135deg, #f8faff, #fff5f0);
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 18px;
        }
        .summary-tag {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 12px;
            border-radius: 999px;
            border: 1px solid rgba(255,107,26,0.2);
            background: rgba(255,107,26,0.08);
            font-size: 11px;
            font-weight: 700;
            color: #FF6B1A;
            margin-bottom: 14px;
        }
        .summary-icon {
            width: 52px;
            height: 52px;
            border-radius: 15px;
            display: grid;
            place-items: center;
            font-size: 24px;
            color: #fff;
            margin-bottom: 12px;
        }
        .summary-icon.auto   { background: linear-gradient(135deg,#1A3A7A,#2d5cc4); }
        .summary-icon.sante  { background: linear-gradient(135deg,#059669,#34d399); }
        .summary-icon.maison { background: linear-gradient(135deg,#FF6B1A,#ff9a5c); }
        .summary-icon.vie    { background: linear-gradient(135deg,#7c3aed,#a78bfa); }
        .summary-name {
            font-family: 'Sora', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: #15233C;
            margin-bottom: 6px;
        }
        .summary-desc { color: rgba(21,35,60,0.55); font-size: 13px; line-height: 1.6; }
        .summary-amount {
            margin-top: 16px;
            display: flex;
            align-items: baseline;
            gap: 6px;
            padding: 12px 16px;
            background: #fff;
            border-radius: 12px;
            border: 1px solid rgba(26,58,122,0.08);
        }
        .summary-amount strong {
            font-family: 'Sora', sans-serif;
            font-size: 34px;
            font-weight: 900;
            color: #15233C;
        }
        .summary-amount span { font-size: 14px; color: rgba(21,35,60,0.5); }
        .summary-rows { display: grid; gap: 8px; margin-top: 16px; }
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid rgba(26,58,122,0.07);
            background: rgba(26,58,122,0.02);
            transition: background 0.2s;
        }
        .summary-row:hover { background: rgba(26,58,122,0.04); }
        .summary-row span:first-child { font-size: 12px; color: rgba(21,35,60,0.5); }
        .summary-row span:last-child { font-size: 13px; font-weight: 700; color: #15233C; text-align: right; }
        .summary-note {
            margin-top: 16px;
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid rgba(255,107,26,0.15);
            background: rgba(255,107,26,0.04);
            display: flex;
            gap: 10px;
            align-items: flex-start;
            font-size: 12px;
            color: rgba(21,35,60,0.7);
            line-height: 1.6;
        }
        .summary-note i { color: #FF6B1A; font-size: 15px; margin-top: 1px; flex-shrink: 0; }
        .period-toggle { display: flex; gap: 10px; margin-bottom: 22px; }
        .period-btn {
            flex: 1;
            padding: 16px 14px;
            border-radius: 16px;
            border: 1px solid rgba(26,58,122,0.12);
            background: rgba(26,58,122,0.02);
            color: rgba(21,35,60,0.55);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s ease;
            text-align: center;
        }
        .period-btn:hover { border-color: rgba(255,107,26,0.3); color: #15233C; }
        .period-btn.active { border-color: #FF6B1A; background: rgba(255,107,26,0.06); color: #FF6B1A; }
        .period-btn .period-price {
            font-family: 'Sora', sans-serif;
            font-size: 22px;
            font-weight: 900;
            color: #15233C;
            display: block;
            margin-top: 5px;
        }
        .period-btn.active .period-price { color: #FF6B1A; }
        .period-btn .period-save { font-size: 11px; color: rgba(21,35,60,0.45); display: block; margin-top: 3px; }
        .period-btn.active .period-save { color: #FF6B1A; }
        .method-row { display: flex; gap: 8px; margin-bottom: 22px; }
        .method-card {
            flex: 1;
            padding: 13px 10px;
            border-radius: 13px;
            border: 1px solid rgba(26,58,122,0.12);
            background: rgba(26,58,122,0.02);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 600;
            color: rgba(21,35,60,0.55);
            cursor: pointer;
            transition: all .2s ease;
        }
        .method-card:hover { border-color: rgba(255,107,26,0.3); color: #15233C; }
        .method-card.active { border-color: #FF6B1A; background: rgba(255,107,26,0.06); color: #FF6B1A; }
        .method-card i { font-size: 17px; }
        .pay-form { display: grid; gap: 16px; }
        .form-group { display: grid; gap: 6px; }
        .form-label {
            font-size: 12px;
            color: rgba(21,35,60,0.6);
            font-weight: 700;
            letter-spacing: .4px;
            text-transform: uppercase;
        }
        .form-input, .form-select {
            width: 100%;
            padding: 13px 16px;
            border-radius: 13px;
            border: 1px solid rgba(26,58,122,0.12);
            background: #fafbff;
            color: #15233C;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            outline: none;
            transition: all .2s ease;
            box-sizing: border-box;
        }
        .form-input::placeholder { color: rgba(21,35,60,0.35); }
        .form-input:focus, .form-select:focus {
            border-color: #FF6B1A;
            box-shadow: 0 0 0 3px rgba(255,107,26,0.10);
            background: #fff;
            transform: translateY(-1px);
        }
        .two-cols { display: grid; grid-template-columns: repeat(2,1fr); gap: 14px; }
        .three-cols { display: grid; grid-template-columns: 1.3fr .85fr .85fr; gap: 14px; }
        .btn {
            padding: 10px 20px;
            border-radius: 11px;
            font-size: 13px;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: all 0.2s;
            text-decoration: none;
            white-space: nowrap;
            justify-content: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, #FF6B1A, #e05a0f);
            color: #fff;
            box-shadow: 0 4px 14px rgba(255,107,26,0.25);
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #e05a0f, #cc4f00);
            box-shadow: 0 6px 20px rgba(255,107,26,0.35);
            transform: translateY(-1px);
        }
        .btn-outline {
            background: transparent;
            color: #1A3A7A;
            border: 1px solid rgba(26,58,122,0.20);
        }
        .btn-outline:hover { background: rgba(26,58,122,0.06); border-color: #1A3A7A; }
        .btn-success {
            background: linear-gradient(135deg, #1A3A7A, #2d5cc4);
            color: #fff;
            box-shadow: 0 4px 14px rgba(26,58,122,0.25);
        }
        .btn-success:hover { box-shadow: 0 6px 20px rgba(26,58,122,0.35); transform: translateY(-1px); }
        .btn-sm { padding: 7px 14px; font-size: 12px; }
        .btn-lg { padding: 14px 28px; font-size: 15px; }
        .trust-row {
            display: grid;
            grid-template-columns: repeat(3,1fr);
            gap: 16px;
            margin-bottom: 28px;
            animation: fadeUp .4s .2s ease both;
        }
        .trust-card {
            background: #fff;
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 2px 12px rgba(26,58,122,0.05);
            transition: all 0.25s;
        }
        .trust-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(26,58,122,0.10);
            border-color: rgba(255,107,26,0.2);
        }
        .trust-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 14px;
        }
        .trust-card:nth-child(1) .trust-icon { background: rgba(26,58,122,0.08); color: #1A3A7A; }
        .trust-card:nth-child(2) .trust-icon { background: rgba(255,107,26,0.10); color: #FF6B1A; }
        .trust-card:nth-child(3) .trust-icon { background: rgba(5,150,105,0.10); color: #059669; }
        .trust-card h4 {
            font-family: 'Sora', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: #15233C;
            margin-bottom: 8px;
        }
        .trust-card p { font-size: 13px; color: rgba(21,35,60,0.55); line-height: 1.65; margin: 0; }
        .success-wrap {
            background: linear-gradient(135deg, #f8faff 0%, #fff5f0 100%);
            border: 1px solid rgba(26,58,122,0.10);
            border-radius: 24px;
            padding: 56px 40px;
            text-align: center;
            margin-bottom: 24px;
            animation: fadeUp .5s ease both;
            box-shadow: 0 8px 40px rgba(26,58,122,0.08);
        }
        .success-icon-wrap {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1A3A7A, #2d5cc4);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 8px 28px rgba(26,58,122,0.3);
            animation: pulse-green 2s infinite;
        }
        .success-icon-wrap i { font-size: 40px; color: #fff; }
        .success-title {
            font-family: 'Sora', sans-serif;
            font-size: 32px;
            font-weight: 900;
            color: #15233C;
            margin-bottom: 12px;
        }
        .success-sub {
            font-size: 15px;
            color: rgba(21,35,60,0.6);
            line-height: 1.7;
            max-width: 520px;
            margin: 0 auto 30px;
        }
        .success-ref {
            display: inline-block;
            background: rgba(26,58,122,0.06);
            border: 1px solid rgba(26,58,122,0.15);
            color: #1A3A7A;
            font-family: monospace;
            font-size: 18px;
            font-weight: 700;
            padding: 12px 28px;
            border-radius: 12px;
            letter-spacing: 2px;
            margin-bottom: 32px;
        }
        .success-grid {
            display: grid;
            grid-template-columns: repeat(2,1fr);
            gap: 12px;
            max-width: 500px;
            margin: 0 auto 36px;
            text-align: left;
        }
        .success-item {
            padding: 15px 18px;
            border-radius: 14px;
            border: 1px solid rgba(26,58,122,0.08);
            background: #fff;
            box-shadow: 0 2px 8px rgba(26,58,122,0.04);
        }
        .success-item .label { font-size: 10px; color: rgba(21,35,60,0.45); margin-bottom: 5px; text-transform: uppercase; letter-spacing: .6px; }
        .success-item .value { font-size: 14px; font-weight: 700; color: #15233C; }
        .success-actions { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }

        #stripe-card-element {
            width: 100%;
            padding: 13px 16px;
            border-radius: 13px;
            border: 1px solid rgba(26,58,122,0.12);
            background: #fafbff;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            transition: all .2s ease;
            box-sizing: border-box;
        }
        #stripe-card-element.StripeElement--focus {
            border-color: #FF6B1A;
            box-shadow: 0 0 0 3px rgba(255,107,26,0.10);
            background: #fff;
        }
        #stripe-card-element.StripeElement--invalid {
            border-color: #e63946;
        }
        .old-card-fields { display: none; }
        .method-section { display: none; }
        .method-section.active { display: block; }
        .stripe-badge { margin-top: 12px; text-align: center; font-size: 12px; color: rgba(21,35,60,0.45); display: flex; align-items: center; justify-content: center; gap: 6px; }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width:1000px) { .pay-grid { grid-template-columns: 1fr; } .trust-row { grid-template-columns: 1fr 1fr; } }
        @media (max-width:768px) { .trust-row { grid-template-columns: 1fr; } .pay-hero { padding: 22px 20px; } .pay-hero-title { font-size: 20px; } .page-header { padding: 16px 16px 0; } }
        @media (max-width:640px) { .two-cols { grid-template-columns: 1fr; } .success-grid { grid-template-columns: 1fr; } .pay-panel-body { padding: 18px; } }
    </style>
</head>
<body>

<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">
 <?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title-main">Paiement sécurisé</div>
                <div class="page-breadcrumb">
                    <i class="bi bi-house"></i>
                    <a href="client.php" style="color:inherit;text-decoration:none;">Accueil</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <a href="offres.php" style="color:inherit;text-decoration:none;">Nos offres</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <span>Paiement</span>
                </div>
            </div>
            <a href="offres.php" class="btn btn-outline btn-sm">
                <i class="bi bi-arrow-left"></i> Retour aux offres
            </a>
        </div>

        <div class="content">

<?php if ($showSuccess): ?>

            <section class="success-wrap">
                <div class="success-icon-wrap"><i class="bi bi-check-lg"></i></div>
                <div class="success-title">Paiement enregistré !</div>
                <div class="success-sub">Votre demande de paiement a bien été enregistrée. Elle sera visible dans votre espace et traitée par l'administration.</div>
                <div class="success-ref"><?= e($reference !== '' ? $reference : 'Référence générée') ?></div>
                <div class="success-grid">
                    <div class="success-item">
                        <div class="label">Offre souscrite</div>
                        <div class="value"><?= e($offre['nom']) ?></div>
                    </div>
                    <div class="success-item">
                        <div class="label">Montant</div>
                        <div class="value" id="successAmount"><?= e((string)$defaultMontant) ?> TND</div>
                    </div>
                    <div class="success-item">
                        <div class="label">Date</div>
                        <div class="value"><?= date('d/m/Y à H:i') ?></div>
                    </div>
                    <div class="success-item">
                        <div class="label">Statut</div>
                        <div class="value" style="color:#FF6B1A;"><i class="bi bi-hourglass-split"></i> En attente</div>
                    </div>
                </div>
                <div class="success-actions">
                    <a href="client.php" class="btn btn-success btn-lg"><i class="bi bi-grid-1x2"></i> Tableau de bord</a>
                    <a href="offres.php" class="btn btn-outline btn-lg"><i class="bi bi-stars"></i> Voir d'autres offres</a>
                </div>
            </section>

<?php else: ?>

            <section class="pay-hero">
                <div class="pay-hero-content">
                    <div class="pay-hero-title">Finalisez votre souscription</div>
                    <div class="pay-hero-sub">Vérifiez le résumé de votre formule et complétez le formulaire de paiement dans une interface sécurisée et professionnelle.</div>
                    <div class="pay-pills">
                        <span class="pay-pill"><i class="bi bi-shield-lock"></i> Paiement protégé</span>
                        <span class="pay-pill"><i class="bi bi-patch-check"></i> Données sécurisées</span>
                        <span class="pay-pill"><i class="bi bi-lightning-charge"></i> Validation instantanée</span>
                    </div>
                </div>
                <div class="pay-hero-actions">
                    <?php foreach ([1,2,3,4] as $id): ?>
                        <a href="paiement.php?id_offre=<?= $id ?>" class="pay-offre-btn <?= $id === $offreId ? 'active' : '' ?>"><?= e($offres[$id]['nom']) ?></a>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php if ($showError): ?>
                <div class="alert-error">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        <strong>Le paiement a échoué.</strong>
                        <p>Merci de vérifier vos informations et de réessayer.</p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="pay-grid">
                <div class="pay-panel">
                    <div class="pay-panel-head">
                        <div class="pay-panel-title"><i class="bi bi-file-earmark-text" style="color:#FF6B1A;margin-right:8px;"></i> Résumé de l'offre</div>
                        <div class="pay-panel-sub">Détails avant confirmation finale</div>
                    </div>
                    <div class="pay-panel-body">
                        <div class="summary-top">
                            <div class="summary-tag"><i class="bi bi-stars"></i> Offre sélectionnée</div>
                            <div class="summary-icon <?= e($offre['color']) ?>"><i class="bi <?= e($offre['icon']) ?>"></i></div>
                            <div class="summary-name"><?= e($offre['nom']) ?></div>
                            <div class="summary-desc"><?= e($offre['description']) ?></div>
                            <div class="summary-amount" id="displayAmount">
                                <?php if ($montantReduit !== null && $montantReduit < $defaultMontant): ?>
                                    <span style="font-size:16px;color:rgba(21,35,60,0.35);text-decoration:line-through;margin-right:6px;"><?= $defaultMontant ?></span>
                                    <strong style="color:#059669;"><?= number_format($montantReduit, 0) ?></strong>
                                    <span>TND / <?= $old['periodicite'] === 'annuel' ? 'an' : 'mois' ?></span>
                                    <span style="background:rgba(16,185,129,0.1);color:#059669;font-size:11px;font-weight:700;padding:3px 8px;border-radius:999px;margin-left:auto;"><?= e($reductionLabel) ?></span>
                                <?php else: ?>
                                    <strong><?= $defaultMontant ?></strong>
                                    <span>TND / <?= $old['periodicite'] === 'annuel' ? 'an' : 'mois' ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="summary-rows">
                            <div class="summary-row">
                                <span><i class="bi bi-hash" style="margin-right:6px;color:#FF6B1A;"></i>Référence</span>
                                <span><?= e($offre['ref']) ?></span>
                            </div>
                            <div class="summary-row">
                                <span><i class="bi bi-tag" style="margin-right:6px;color:#FF6B1A;"></i>Type</span>
                                <span><?= e($offre['type']) ?></span>
                            </div>
                            <div class="summary-row">
                                <span><i class="bi bi-calendar3" style="margin-right:6px;color:#FF6B1A;"></i>Durée minimale</span>
                                <span><?= e($offre['duree']) ?></span>
                            </div>
                            <div class="summary-row">
                                <span><i class="bi bi-circle-half" style="margin-right:6px;color:#FF6B1A;"></i>Statut</span>
                                <span style="color:#FF6B1A;font-weight:700;"><i class="bi bi-hourglass-split"></i> En attente</span>
                            </div>
                            <?php if ($montantReduit !== null && $montantReduit < $defaultMontant): ?>
                            <div class="summary-row" style="border-color:rgba(16,185,129,0.2);background:rgba(16,185,129,0.04);">
                                <span><i class="bi bi-ticket-perforated-fill" style="margin-right:6px;color:#059669;"></i>Réduction roulette</span>
                                <span style="color:#059669;font-weight:700;"><?= e($reductionLabel) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="summary-note">
                            <i class="bi bi-info-circle-fill"></i>
                            <div>Après soumission, votre paiement sera enregistré et visible dans votre espace d'administration pour traitement.</div>
                        </div>
                    </div>
                </div>

                <div class="pay-panel">
                    <div class="pay-panel-head">
                        <div class="pay-panel-title"><i class="bi bi-credit-card" style="color:#1A3A7A;margin-right:8px;"></i> Formulaire de paiement</div>
                        <div class="pay-panel-sub">Tous les champs * sont obligatoires</div>
                    </div>
                    <div class="pay-panel-body">
                            <?php if ($montantFromWizard <= 0): ?>
                            <div class="period-toggle">
                                <div class="period-btn <?= $old['periodicite'] === 'mensuel' ? 'active' : '' ?>" id="btn-mensuel" onclick="setPeriod('mensuel')">
                                    <i class="bi bi-calendar3"></i> Mensuel
                                    <span class="period-price"><?= $offre['prix_mensuel'] ?> TND</span>
                                    <span class="period-save">par mois</span>
                                </div>
                                <div class="period-btn <?= $old['periodicite'] === 'annuel' ? 'active' : '' ?>" id="btn-annuel" onclick="setPeriod('annuel')">
                                    <i class="bi bi-calendar-check"></i> Annuel
                                    <span class="period-price"><?= $offre['prix_annuel'] ?> TND</span>
                                    <span class="period-save">économisez <?= ($offre['prix_mensuel'] * 12 - $offre['prix_annuel']) ?> TND</span>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="alert-info" style="background:rgba(26,58,122,0.04);border:1px solid rgba(26,58,122,0.12);border-radius:14px;padding:16px 20px;margin-bottom:20px;">
                                <i class="bi bi-lock-fill" style="color:#1A3A7A;margin-right:8px;"></i>
                                <strong>Périodicité et montant validés</strong>
                                <div style="margin-top:4px;font-size:13px;color:rgba(21,35,60,0.65);">Ces paramètres ont été calculés sur mesure lors de votre devis.</div>
                            </div>
                            <?php endif; ?>

                            <div class="method-row">
                                <div class="method-card <?= $old['methode'] === 'carte' ? 'active' : '' ?>" onclick="setMethod(this,'carte')">
                                    <i class="bi bi-credit-card-2-front"></i> Carte
                                </div>
                                <div class="method-card <?= $old['methode'] === 'virement' ? 'active' : '' ?>" onclick="setMethod(this,'virement')">
                                    <i class="bi bi-bank"></i> Virement
                                </div>
                                <div class="method-card <?= $old['methode'] === 'mobile' ? 'active' : '' ?>" onclick="setMethod(this,'mobile')">
                                    <i class="bi bi-phone"></i> Mobile
                                </div>
                            </div>

                            <div id="section-carte" class="method-section active">
                                <form class="pay-form" id="form-carte" novalidate>
                                    <input type="hidden" id="carte-periodicite" value="<?= e($old['periodicite']) ?>">
                                    <input type="hidden" id="carte-hidden-montant" value="<?= $defaultMontant ?>">
                                    <input type="hidden" id="carte-hidden-promo" value="<?= $old['promo'] ?>">
                                    <input type="hidden" id="carte-hidden-montant-original" value="<?= $defaultMontant ?>">
                                    <div class="form-group">
                                        <label class="form-label">Nom complet *</label>
                                        <input class="form-input" type="text" id="carte-fullname" placeholder="Votre nom complet" value="<?= $old['fullname'] ?>">
                                    </div>

                                    <div class="two-cols">
                                        <div class="form-group">
                                            <label class="form-label">E-mail *</label>
                                            <input class="form-input" type="email" id="carte-email" placeholder="exemple@email.com" value="<?= $old['email'] ?>">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Téléphone *</label>
                                            <input class="form-input" type="tel" id="carte-phone" placeholder="+216 XX XXX XXX" value="<?= $old['phone'] ?>">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Carte bancaire *</label>
                                        <div id="stripe-card-element"></div>
                                        <div id="stripe-card-errors" style="font-size:12px;color:#e63946;margin-top:4px;display:none;"></div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Adresse de facturation *</label>
                                        <input class="form-input" type="text" id="carte-address" placeholder="Votre adresse complète" value="<?= $old['address'] ?>">
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label"><i class="bi bi-ticket-perforated" style="color:#FF6B1A;"></i> Code promo Roulette</label>
                                        <div style="display:flex;gap:8px;">
                                            <input class="form-input" type="text" id="carte-promo-input" placeholder="PROTEX-SPIN-XXXXXX" value="<?= $old['promo'] ?>" style="text-transform:uppercase;letter-spacing:1px;font-family:'Courier New',monospace;font-size:13px;">
                                            <button type="button" class="btn btn-primary" onclick="applyPromo('carte')" style="white-space:nowrap;">
                                                <i class="bi bi-arrow-right-circle"></i> Appliquer
                                            </button>
                                        </div>
                                        <div id="carte-promo-msg" style="font-size:12px;margin-top:6px;display:none;"></div>
                                    </div>

                                    <div id="carte-promo-summary" style="display:none;" class="summary-row">
                                        <span><i class="bi bi-tag-fill" style="margin-right:6px;color:#059669;"></i>Réduction</span>
                                        <span id="carte-promo-discount-label" style="color:#059669;font-weight:700;"></span>
                                    </div>

                                    <div style="display:flex;gap:10px;margin-top:8px;flex-wrap:wrap;">
                                        <button type="button" id="pay-stripe-btn" class="btn btn-primary btn-lg" onclick="processStripePayment()" style="flex:1;">
                                            <i class="bi bi-lock-fill"></i>
                                            Payer — <span id="btnAmount"><?= $defaultMontant ?> TND</span>
                                        </button>
                                        <a href="offres.php" class="btn btn-outline">
                                            <i class="bi bi-arrow-left"></i> Changer
                                        </a>
                                    </div>
                                    <div class="stripe-badge" style="margin-top:12px;text-align:center;font-size:12px;color:rgba(21,35,60,0.45);display:flex;align-items:center;justify-content:center;gap:6px;">
                                        <i class="bi bi-shield-lock-fill"></i>
                                        Paiement sécurisé par Stripe — Vos données sont chiffrées
                                    </div>
                                </form>
                            </div>

                            <div id="section-virement" class="method-section" style="display:none;">
                                <form class="pay-form" method="post" action="confirmer_paiement.php" id="form-virement" novalidate>
                                    <input type="hidden" name="id_offre" value="<?= $offreId ?>">
                                    <input type="hidden" name="offre_id" value="<?= $offreId ?>">
                                    <input type="hidden" name="periodicite" id="virement-periodicite" value="<?= e($old['periodicite']) ?>">
                                    <input type="hidden" name="methode" value="virement">
                                    <input type="hidden" name="montant" id="virement-montant" value="<?= $defaultMontant ?>">
                                    <input type="hidden" name="code_promo" id="virement-promo" value="<?= $old['promo'] ?>">
                                    <input type="hidden" name="montant_original" id="virement-montant-original" value="<?= $defaultMontant ?>">

                                    <div class="alert-info" style="background:rgba(26,58,122,0.04);border:1px solid rgba(26,58,122,0.12);border-radius:14px;padding:16px 20px;display:flex;gap:12px;align-items:flex-start;margin-bottom:20px;">
                                        <i class="bi bi-bank" style="color:#1A3A7A;font-size:18px;flex-shrink:0;"></i>
                                        <div>
                                            <strong style="font-size:13px;color:#1A3A7A;">Instructions de virement</strong>
                                            <p style="margin:8px 0 0;font-size:12px;color:rgba(21,35,60,0.65);line-height:1.7;">
                                                Effectuez votre virement bancaire avec les coordonnées ci-dessous. Votre offre sera activée après réception du paiement.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="summary-rows" style="margin-bottom:18px;">
                                        <div class="summary-row">
                                            <span><i class="bi bi-building" style="margin-right:6px;color:#FF6B1A;"></i>Banque</span>
                                            <span>Attijari Bank</span>
                                        </div>
                                        <div class="summary-row">
                                            <span><i class="bi bi-person" style="margin-right:6px;color:#FF6B1A;"></i>Bénéficiaire</span>
                                            <span>Protex Assurance SARL</span>
                                        </div>
                                        <div class="summary-row">
                                            <span><i class="bi bi-upc-scan" style="margin-right:6px;color:#FF6B1A;"></i>IBAN</span>
                                            <span style="font-family:monospace;">TN59 1234 5678 9012 3456 7890</span>
                                        </div>
                                        <div class="summary-row">
                                            <span><i class="bi bi-hash" style="margin-right:6px;color:#FF6B1A;"></i>RIB</span>
                                            <span style="font-family:monospace;">12345678901234567890</span>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Nom complet *</label>
                                        <input class="form-input" type="text" name="fullname" placeholder="Votre nom complet" value="<?= $old['fullname'] ?>" required>
                                    </div>

                                    <div class="two-cols">
                                        <div class="form-group">
                                            <label class="form-label">E-mail *</label>
                                            <input class="form-input" type="email" name="email" placeholder="exemple@email.com" value="<?= $old['email'] ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Téléphone *</label>
                                            <input class="form-input" type="tel" name="phone" placeholder="+216 XX XXX XXX" value="<?= $old['phone'] ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Référence du virement *</label>
                                        <input class="form-input" type="text" name="virement_ref" placeholder="Référence de votre virement" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label"><i class="bi bi-ticket-perforated" style="color:#FF6B1A;"></i> Code promo Roulette</label>
                                        <div style="display:flex;gap:8px;">
                                            <input class="form-input" type="text" id="virement-promo-input" placeholder="PROTEX-SPIN-XXXXXX" value="<?= $old['promo'] ?>" style="text-transform:uppercase;letter-spacing:1px;font-family:'Courier New',monospace;font-size:13px;">
                                            <button type="button" class="btn btn-primary" onclick="applyPromo('virement')" style="white-space:nowrap;">
                                                <i class="bi bi-arrow-right-circle"></i> Appliquer
                                            </button>
                                        </div>
                                        <div id="virement-promo-msg" style="font-size:12px;margin-top:6px;display:none;"></div>
                                    </div>

                                    <div id="virement-promo-summary" style="display:none;" class="summary-row">
                                        <span><i class="bi bi-tag-fill" style="margin-right:6px;color:#059669;"></i>Réduction</span>
                                        <span id="virement-promo-discount-label" style="color:#059669;font-weight:700;"></span>
                                    </div>

                                    <div style="display:flex;gap:10px;margin-top:8px;flex-wrap:wrap;">
                                        <button type="submit" class="btn btn-primary btn-lg" style="flex:1;">
                                            <i class="bi bi-bank"></i>
                                            Confirmer — <span id="virement-btn-amount"><?= $defaultMontant ?> TND</span>
                                        </button>
                                        <a href="offres.php" class="btn btn-outline">
                                            <i class="bi bi-arrow-left"></i> Changer
                                        </a>
                                    </div>
                                </form>
                            </div>

                            <div id="section-mobile" class="method-section" style="display:none;">
                                <form class="pay-form" method="post" action="confirmer_paiement.php" id="form-mobile" novalidate>
                                    <input type="hidden" name="id_offre" value="<?= $offreId ?>">
                                    <input type="hidden" name="offre_id" value="<?= $offreId ?>">
                                    <input type="hidden" name="periodicite" id="mobile-periodicite" value="<?= e($old['periodicite']) ?>">
                                    <input type="hidden" name="methode" value="mobile">
                                    <input type="hidden" name="montant" id="mobile-montant" value="<?= $defaultMontant ?>">
                                    <input type="hidden" name="code_promo" id="mobile-promo" value="<?= $old['promo'] ?>">
                                    <input type="hidden" name="montant_original" id="mobile-montant-original" value="<?= $defaultMontant ?>">

                                    <div class="form-group">
                                        <label class="form-label">Nom complet *</label>
                                        <input class="form-input" type="text" name="fullname" placeholder="Votre nom complet" value="<?= $old['fullname'] ?>" required>
                                    </div>

                                    <div class="two-cols">
                                        <div class="form-group">
                                            <label class="form-label">E-mail *</label>
                                            <input class="form-input" type="email" name="email" placeholder="exemple@email.com" value="<?= $old['email'] ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Téléphone *</label>
                                            <input class="form-input" type="tel" name="phone" placeholder="+216 XX XXX XXX" value="<?= $old['phone'] ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Opérateur mobile *</label>
                                        <select class="form-input" name="operator" required>
                                            <option value="">— Choisir —</option>
                                            <option value="orange">Orange Money</option>
                                            <option value="tunisie_telecom">Tunisie Telecom</option>
                                            <option value="ooredoo">Ooredoo</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Numéro de téléphone mobile *</label>
                                        <input class="form-input" type="tel" name="mobile_number" placeholder="+216 XX XXX XXX" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label"><i class="bi bi-ticket-perforated" style="color:#FF6B1A;"></i> Code promo Roulette</label>
                                        <div style="display:flex;gap:8px;">
                                            <input class="form-input" type="text" id="mobile-promo-input" placeholder="PROTEX-SPIN-XXXXXX" value="<?= $old['promo'] ?>" style="text-transform:uppercase;letter-spacing:1px;font-family:'Courier New',monospace;font-size:13px;">
                                            <button type="button" class="btn btn-primary" onclick="applyPromo('mobile')" style="white-space:nowrap;">
                                                <i class="bi bi-arrow-right-circle"></i> Appliquer
                                            </button>
                                        </div>
                                        <div id="mobile-promo-msg" style="font-size:12px;margin-top:6px;display:none;"></div>
                                    </div>

                                    <div id="mobile-promo-summary" style="display:none;" class="summary-row">
                                        <span><i class="bi bi-tag-fill" style="margin-right:6px;color:#059669;"></i>Réduction</span>
                                        <span id="mobile-promo-discount-label" style="color:#059669;font-weight:700;"></span>
                                    </div>

                                    <div style="display:flex;gap:10px;margin-top:8px;flex-wrap:wrap;">
                                        <button type="submit" class="btn btn-primary btn-lg" style="flex:1;">
                                            <i class="bi bi-phone"></i>
                                            Payer — <span id="mobile-btn-amount"><?= $defaultMontant ?> TND</span>
                                        </button>
                                        <a href="offres.php" class="btn btn-outline">
                                            <i class="bi bi-arrow-left"></i> Changer
                                        </a>
                                    </div>
                                </form>
                            </div>
                    </div>
                </div>
            </div>

            <div class="trust-row">
                <div class="trust-card">
                    <div class="trust-icon"><i class="bi bi-lock-fill"></i></div>
                    <h4>Sécurité maximale</h4>
                    <p>Vos données de paiement sont chiffrées SSL. Le numéro de carte n'est jamais stocké en clair sur nos serveurs.</p>
                </div>
                <div class="trust-card">
                    <div class="trust-icon"><i class="bi bi-speedometer2"></i></div>
                    <h4>Validation rapide</h4>
                    <p>Le processus est optimisé pour être rapide, simple et sans friction. Votre paiement est enregistré en quelques instants.</p>
                </div>
                <div class="trust-card">
                    <div class="trust-icon"><i class="bi bi-check2-square"></i></div>
                    <h4>Transparence totale</h4>
                    <p>Le montant et les détails de votre offre sont toujours visibles avant de confirmer. Aucune surprise cachée.</p>
                </div>
            </div>

<?php endif; ?>

        </div>
    </main>
</div>

<script src="assets/js/main.js"></script>
<script>
    const BASE = '<?= defined('BASE_URL') ? BASE_URL : '' ?>';
    const stripe = Stripe('<?= e($publishableKey) ?>');
    const elements = stripe.elements();

    const card = elements.create('card', {
        style: {
            base: {
                color: '#15233C',
                fontFamily: '"DM Sans", sans-serif',
                fontSize: '14px',
                '::placeholder': { color: 'rgba(21,35,60,0.35)' },
                iconColor: '#FF6B1A'
            },
            invalid: { color: '#e63946', iconColor: '#e63946' }
        },
        hidePostalCode: true
    });

    let cardMounted = false;

    document.getElementById('cardnumber')?.addEventListener('input', function() {
        let v = this.value.replace(/\D/g, '').substring(0, 16);
        this.value = v.replace(/(.{4})/g, '$1 ').trim();
    });

    document.getElementById('expiry')?.addEventListener('input', function() {
        let v = this.value.replace(/\D/g, '').substring(0, 4);
        if (v.length >= 3) {
            v = v.substring(0, 2) + '/' + v.substring(2);
        }
        this.value = v;
    });

    const montantFromWizard = <?= $montantFromWizard ?>;
    const prixMensuel = montantFromWizard > 0 ? montantFromWizard : <?= (float)$offre['prix_mensuel'] ?>;
    const prixAnnuel  = montantFromWizard > 0 ? montantFromWizard : <?= (float)$offre['prix_annuel'] ?>;
    let activePromoGain = null;

    function setPeriod(p) {
        const btnMensuel = document.getElementById('btn-mensuel');
        const btnAnnuel = document.getElementById('btn-annuel');
        if(btnMensuel) btnMensuel.classList.toggle('active', p === 'mensuel');
        if(btnAnnuel) btnAnnuel.classList.toggle('active', p === 'annuel');

        const montant = (p === 'annuel' && montantFromWizard <= 0) ? prixAnnuel : prixMensuel;
        let periode = 'mois';
        if (p === 'annuel') periode = 'an';
        if (p === 'trimestriel') periode = 'trimestre';

        document.querySelectorAll('[id$="-periodicite"]').forEach(el => el.value = p);
        document.querySelectorAll('[id$="-montant-original"]').forEach(el => el.value = montant);
        document.querySelectorAll('[id$="-montant"]').forEach(el => el.value = montant);

        if (activePromoGain) {
            applyPromoFromGain(activePromoGain, montant, periode);
        } else {
            document.querySelectorAll('[id$="-btn-amount"]').forEach(el => el.textContent = montant + ' TND');
            const btnAmount = document.getElementById('btnAmount');
            if(btnAmount) btnAmount.textContent = montant + ' TND';
            document.getElementById('displayAmount').innerHTML =
                '<strong>' + montant + '</strong><span>TND / ' + periode + '</span>';
        }
    }

    function applyPromo(method) {
        const inputId = method === 'carte' ? 'carte-promo-input' : (method === 'virement' ? 'virement-promo-input' : 'mobile-promo-input');
        const msgId = method + '-promo-msg';
        const code = document.getElementById(inputId).value.trim();
        const msg = document.getElementById(msgId);

        if (!code) {
            msg.style.display = 'block';
            msg.innerHTML = '<span style="color:#e63946;">Veuillez entrer un code promo.</span>';
            return;
        }
        msg.style.display = 'block';
        msg.innerHTML = '<span style="color:rgba(21,35,60,0.5);"><i class="bi bi-hourglass-split"></i> Vérification...</span>';

        const formData = new FormData();
        formData.append('code', code);

        fetch(BASE + '/controller/RouletteController.php?action=valider_promo', {
            method: 'POST', body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                activePromoGain = data.gain;
                const sectionId = 'section-' + method;
                const section = document.getElementById(sectionId);
                const periodiciteEl = section ? section.querySelector('[id$="-periodicite"]') : null;
                const currentPeriod = periodiciteEl ? periodiciteEl.value : 'mensuel';
                const montant = currentPeriod === 'annuel' ? prixAnnuel : prixMensuel;
                const periode = currentPeriod === 'annuel' ? 'an' : 'mois';
                applyPromoFromGain(data.gain, montant, periode);

                const promoHiddenId = method === 'carte' ? 'carte-hidden-promo' : (method === 'virement' ? 'virement-promo' : 'mobile-promo');
                const promoHidden = document.getElementById(promoHiddenId);
                if(promoHidden) promoHidden.value = code;

                msg.innerHTML = '<span style="color:#059669;"><i class="bi bi-check-circle-fill"></i> ' + e(data.label) + ' appliqué !</span>';
                const summaryId = method + '-promo-summary';
                const summary = document.getElementById(summaryId);
                if (summary) {
                    summary.style.display = 'flex';
                    const labelId = method + '-promo-discount-label';
                    document.getElementById(labelId).textContent = data.label;
                }
            } else {
                activePromoGain = null;
                const promoHiddenId = method === 'carte' ? 'carte-hidden-promo' : (method === 'virement' ? 'virement-promo' : 'mobile-promo');
                const promoHidden = document.getElementById(promoHiddenId);
                if(promoHidden) promoHidden.value = '';

                msg.innerHTML = '<span style="color:#e63946;"><i class="bi bi-x-circle-fill"></i> ' + e(data.message || 'Code invalide.') + '</span>';
                const summaryId = method + '-promo-summary';
                const summary = document.getElementById(summaryId);
                if (summary) summary.style.display = 'none';
                setPeriod(document.getElementById('carte-periodicite')?.value || 'mensuel');
            }
        })
        .catch(err => {
            msg.innerHTML = '<span style="color:#e63946;">Erreur de connexion.</span>';
        });
    }

    function applyPromoFromGain(gain, montant, periode) {
        const type = gain.type_recompense || gain.cadeau_type || '';
        const valeur = parseFloat(gain.valeur_reduction || gain.valeur || 0);
        let finalMontant = montant;
        let label = '';

        if (type === 'reduction_pct') {
            finalMontant = Math.max(0, montant * (1 - valeur / 100));
            label = '-' + Math.round(valeur) + '%';
        } else if (type === 'reduction_fixe') {
            finalMontant = Math.max(0, montant - valeur);
            label = '-' + Math.round(valeur) + ' TND';
        } else if (type === 'bonus_service') {
            label = 'Bonus service';
        }

        const finalRounded = Math.round(finalMontant);
        document.querySelectorAll('[id$="-btn-amount"]').forEach(el => el.textContent = finalRounded + ' TND');
        const btnAmount = document.getElementById('btnAmount');
        if(btnAmount) btnAmount.textContent = finalRounded + ' TND';

        document.querySelectorAll('[id$="-montant"]').forEach(el => el.value = finalRounded);

        document.getElementById('displayAmount').innerHTML =
            '<span style="font-size:16px;color:rgba(21,35,60,0.35);text-decoration:line-through;margin-right:6px;">' + montant + '</span>' +
            '<strong style="color:#059669;">' + finalRounded + '</strong>' +
            '<span>TND / ' + periode + '</span>' +
            '<span style="background:rgba(16,185,129,0.1);color:#059669;font-size:11px;font-weight:700;padding:3px 8px;border-radius:999px;margin-left:auto;">' + e(label) + '</span>';
    }

    function e(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    let currentMethod = 'carte';

    function setMethod(el, m) {
        document.querySelectorAll('.method-card').forEach(c => c.classList.remove('active'));
        el.classList.add('active');
        currentMethod = m;

        document.querySelectorAll('.method-section').forEach(s => {
            s.style.display = 'none';
            s.classList.remove('active');
        });
        const target = document.getElementById('section-' + m);
        if (target) {
            target.style.display = '';
            target.classList.add('active');
        }

        if (m === 'carte') {
            if (!cardMounted) {
                card.mount('#stripe-card-element');
                cardMounted = true;
                console.log('Stripe card mounted successfully');

                card.on('change', (ev) => {
                    const errDiv = document.getElementById('stripe-card-errors');
                    if (ev.error) {
                        errDiv.textContent = ev.error.message;
                        errDiv.style.display = 'block';
                    } else {
                        errDiv.textContent = '';
                        errDiv.style.display = 'none';
                    }
                });
            }
        }
    }

    function processStripePayment() {
        const btn = document.getElementById('pay-stripe-btn');
        const errDiv = document.getElementById('stripe-card-errors');
        errDiv.style.display = 'none';
        errDiv.textContent = '';

        const fullname = document.getElementById('carte-fullname').value.trim();
        const email = document.getElementById('carte-email').value.trim();
        const phone = document.getElementById('carte-phone').value.trim();
        const address = document.getElementById('carte-address').value.trim();

        if (!fullname) {
            errDiv.textContent = 'Le nom complet est requis.';
            errDiv.style.display = 'block';
            return;
        }
        if (!email || !email.includes('@')) {
            errDiv.textContent = 'Veuillez entrer un email valide.';
            errDiv.style.display = 'block';
            return;
        }
        if (!phone) {
            errDiv.textContent = 'Le numéro de téléphone est requis.';
            errDiv.style.display = 'block';
            return;
        }
        if (!address) {
            errDiv.textContent = 'L\'adresse de facturation est requise.';
            errDiv.style.display = 'block';
            return;
        }

        if (!cardMounted) {
            errDiv.textContent = 'Veuillez patienter pendant le chargement du formulaire de carte...';
            errDiv.style.display = 'block';
            return;
        }

        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner" style="display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;margin-right:6px;"></span> Traitement en cours...';

        fetch(BASE + '/controller/StripePaymentController.php?action=creer_session', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                offre_id: <?= $offreId ?>,
                devis_id: <?= $idDevisFromWizard ?>,
                montant: parseFloat(document.getElementById('carte-hidden-montant').value),
                nom: fullname,
                email: email,
                adresse: address,
                phone: phone,
                periode: document.getElementById('carte-periodicite').value || 'mensuel',
                code_promo: document.getElementById('carte-hidden-promo')?.value || ''
            })
        }).then(res => res.json()).then(data => {
            if (data.error) {
                errDiv.textContent = data.error;
                errDiv.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = originalText;
                return;
            }

            if (data.clientSecret) {
                return stripe.confirmCardPayment(data.clientSecret, {
                    payment_method: { card: card }
                }).then(result => {
                    return { result: result, data: data };
                });
            }

            return null;
        }).then(payload => {
            if (!payload) return;
            const { result, data } = payload;

            if (result.error) {
                errDiv.textContent = 'Paiement refusé: ' + result.error.message;
                errDiv.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = originalText;
                return;
            }

            if (result.paymentIntent && result.paymentIntent.status === 'succeeded') {
                btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Paiement réussi !';
                btn.style.background = '#059669';

                fetch(BASE + '/controller/StripePaymentController.php?action=confirmer', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ paiement_id: data.paiement_id, payment_intent_id: result.paymentIntent.id })
                }).then(() => {
                    setTimeout(() => {
                        window.location.href = 'paiement.php?id_offre=<?= $offreId ?>&success=1&reference=' + data.reference;
                    }, 1500);
                });
            }
        }).catch(err => {
            console.error(err);
            errDiv.textContent = 'Erreur réseau. Vérifiez votre connexion et réessayez.';
            errDiv.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }

    window.addEventListener('DOMContentLoaded', () => {
        setPeriod('<?= e($old['periodicite']) ?>');

        // Show the active method based on PHP's $old['methode']
        const initialMethod = '<?= e($old['methode']) ?>';
        const methodCard = Array.from(document.querySelectorAll('.method-card')).find(c => {
            const txt = c.textContent.trim().toLowerCase();
            if (initialMethod === 'carte') return txt.includes('carte');
            if (initialMethod === 'virement') return txt.includes('virement');
            if (initialMethod === 'mobile') return txt.includes('mobile');
            return false;
        });

        if (methodCard) {
            setMethod(methodCard, initialMethod);
        }

        <?php if ($montantReduit !== null && $montantReduit < $defaultMontant): ?>
        activePromoGain = <?= json_encode($promoGain) ?>;
        const summary = document.getElementById('carte-promo-summary');
        if (summary) {
            summary.style.display = 'flex';
            document.getElementById('carte-promo-discount-label').textContent = <?= json_encode($reductionLabel) ?>;
        }
        <?php endif; ?>
    });
</script>
</body>
</html>


