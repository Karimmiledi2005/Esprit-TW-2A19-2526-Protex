<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Inclusion de la config (qui définit BASE_URL de manière dynamique)
require_once __DIR__ . '/../../config.php';

// User pre-fill logic
require_once __DIR__ . '/../../helpers/RoleHelper.php';
$userId = RoleHelper::getUserId();
$userNom    = $_SESSION['nom']    ?? $_SESSION['user_nom']    ?? '';
$userPrenom = $_SESSION['prenom'] ?? $_SESSION['user_prenom'] ?? '';
$userEmail  = $_SESSION['email']  ?? $_SESSION['user_email']  ?? '';
$userTel    = $_SESSION['telephone'] ?? $_SESSION['tel']      ?? '';

if ($userId > 0) {
    try {
        $db = config::getConnexion();
        $stmt = $db->prepare("SELECT nom, prenom, email, telephone FROM user WHERE id_user = ?");
        $stmt->execute([$userId]);
        $uData = $stmt->fetch();
        if ($uData) {
            if (!empty($uData['nom']))       $userNom    = $uData['nom'];
            if (!empty($uData['prenom']))    $userPrenom = $uData['prenom'];
            if (!empty($uData['email']))     $userEmail  = $uData['email'];
            if (!empty($uData['telephone'])) $userTel    = $uData['telephone'];
        }
    } catch (Exception $e) {}
}

$success = isset($_GET['success']) ? urldecode($_GET['success']) : '';
$erreur  = isset($_GET['erreur'])  ? urldecode($_GET['erreur'])  : '';
$old         = $_SESSION['old']    ?? [];
$form_errors = $_SESSION['errors'] ?? [];
unset($_SESSION['old'], $_SESSION['errors']);


$offres = ['auto' => [], 'habitation' => [], 'sante' => []];
try {
    $db   = config::getConnexion();
    $stmt = $db->query("SELECT id_offre, nom_offre, type_offre, prix_mensuel, prix_annuel, couverture FROM offre WHERE statut = 'active' ORDER BY type_offre, prix_annuel ASC");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $o) {
        if (isset($offres[$o['type_offre']])) $offres[$o['type_offre']][] = $o;
    }
} catch (Exception $e) {}

$offresJson = json_encode($offres);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Demande de devis — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">
<link rel="stylesheet" href="<?= BASE_URL ?>/view/FrontOffice/assets/css/variables.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/view/FrontOffice/assets/css/base.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/view/FrontOffice/assets/css/layout.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/view/FrontOffice/assets/css/client.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/view/FrontOffice/assets/css/light-theme.css">
    <style>
        :root {
            --devis-shadow:0 18px 45px rgba(31,63,134,.10);
            --devis-ring:0 0 0 3px rgba(255,107,26,.12);
            --devis-card:rgba(255,255,255,0.96);
            --devis-border:rgba(31,63,134,.10);
            --devis-text:#1f2f4a;
            --devis-muted:#6f7d95;
            --devis-blue:#1f3f86;
            --devis-blue-dark:#18336c;
            --devis-orange:#ff6b1a;
            --devis-orange-dark:#ef5d10;
            --devis-green:#16a36f;
            --devis-danger:#d94b4b;
        }
        body{background:radial-gradient(circle at top left,rgba(255,107,26,.08),transparent 24%),radial-gradient(circle at bottom right,rgba(66,133,244,.09),transparent 28%),linear-gradient(180deg,#f8fafc 0%,#eef3f9 100%);}

        /* ALERTES */
        .alert-box{padding:16px 20px;border-radius:18px;margin:0 0 16px;display:flex;align-items:flex-start;gap:12px;font-size:14px;font-weight:600;line-height:1.6;}
        .alert-box i{font-size:20px;margin-top:1px;flex-shrink:0;}
        .alert-success{background:#e6f9f0;border:1px solid #1aa56c;color:#0a5c38;}
        .alert-success i{color:#1aa56c;}
        .alert-danger{background:#fdf0f0;border:1px solid var(--devis-danger);color:#7a1e1e;}
        .alert-danger i{color:var(--devis-danger);}
        .alert-warning{background:#fff8ec;border:1px solid #ff9c2b;color:#7a4a00;}
        .alert-warning i{color:#ff9c2b;}

        /* HERO */
        .hero-banner{position:relative;overflow:hidden;padding:34px;border-radius:30px;background:radial-gradient(circle at 78% 18%,rgba(255,255,255,.10),transparent 22%),linear-gradient(135deg,var(--devis-blue),var(--devis-blue-dark));border:1px solid rgba(255,255,255,.10);box-shadow:0 24px 55px rgba(29,53,105,.16);margin-bottom:28px;}
        .hero-banner::before{content:"";position:absolute;width:220px;height:220px;right:-40px;top:-38px;border-radius:50%;background:rgba(255,255,255,.08);}
        .hero-banner::after{content:"";position:absolute;width:220px;height:220px;left:44%;bottom:-130px;border-radius:50%;background:rgba(255,255,255,.07);}
        .hero-grid{display:grid;grid-template-columns:1.3fr .9fr;gap:24px;align-items:center;position:relative;z-index:1;}
        .hero-title{font-family:var(--font-display);font-size:clamp(34px,3.4vw,58px);line-height:1.02;font-weight:800;color:#fff;margin-bottom:16px;letter-spacing:-.03em;max-width:760px;}
        .hero-title .accent{color:#ffb07e;}
        .hero-sub{color:rgba(255,255,255,.84);font-size:15px;line-height:1.95;margin-bottom:22px;}
        .hero-badges{display:flex;flex-wrap:wrap;gap:12px;}
        .hero-badge{display:inline-flex;align-items:center;gap:8px;padding:11px 16px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.08);color:#fff;font-size:12px;font-weight:700;}
        .hero-badge i{color:#73ff97;}
        .hero-stats{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;}
        .hero-stat-card{padding:22px 20px;border-radius:24px;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.08);}
        .hero-stat-value{font-size:34px;font-weight:800;color:#fff;margin-bottom:8px;}
        .hero-stat-label{color:rgba(255,255,255,.74);font-size:12px;line-height:1.7;font-weight:600;}

        /* LAYOUT */
        .client-shell{display:grid;grid-template-columns:1.15fr .85fr;gap:22px;align-items:start;}
        .devis-card{background:var(--devis-card);border:1px solid var(--devis-border);border-radius:28px;backdrop-filter:blur(18px);overflow:hidden;box-shadow:var(--devis-shadow);}
        .devis-card-header{padding:24px 28px;border-bottom:1px solid rgba(31,63,134,.08);display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;background:linear-gradient(180deg,rgba(255,255,255,.82),rgba(255,255,255,.68));}
        .devis-card-title{font-family:var(--font-display);font-size:22px;font-weight:800;color:var(--devis-text);display:flex;align-items:center;gap:10px;}
        .devis-card-title i{color:var(--devis-orange);}
        .devis-card-sub{font-size:13px;color:var(--devis-muted);margin-top:6px;line-height:1.7;}
        .step-pills{display:flex;flex-wrap:wrap;gap:10px;}
        .step-pill{border:1px solid rgba(31,63,134,.12);background:#fff;color:var(--devis-muted);border-radius:999px;padding:11px 14px;font-size:12px;font-weight:800;display:inline-flex;align-items:center;gap:8px;transition:all .25s;box-shadow:0 6px 16px rgba(31,63,134,.06);}
        .step-pill.active{color:var(--devis-orange);background:rgba(255,107,26,.10);border-color:rgba(255,107,26,.22);box-shadow:none;}
        .step-pill.done{color:var(--devis-green);background:rgba(22,163,111,.10);border-color:rgba(22,163,111,.18);box-shadow:none;}
        .devis-card-body{padding:28px;}
        .section-divider{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:8px 0 18px;}
        .section-divider .left{display:flex;align-items:center;gap:10px;font-weight:800;color:var(--devis-text);font-size:16px;}
        .section-divider .left i{color:var(--devis-orange);}
        .section-divider .right{font-size:12px;color:var(--devis-muted);font-weight:600;}
        .devis-grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:18px;}
        .devis-grid-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:18px;}
        .devis-full{grid-column:1 / -1;}
        .field{display:flex;flex-direction:column;gap:8px;}
        .field label{color:var(--devis-text);font-size:13px;font-weight:700;display:flex;align-items:center;gap:6px;}
        .field label i{color:var(--devis-orange);}
        .field input,.field textarea,.field select{width:100%;padding:15px 16px;border-radius:18px;border:1px solid rgba(31,63,134,.10);background:rgba(255,255,255,.98);color:var(--devis-text);outline:none;font-size:14px;transition:.22s;box-shadow:0 10px 24px rgba(31,63,134,.04);}
        .field input::placeholder,.field textarea::placeholder{color:#98a3b6;}
        .field input:focus,.field textarea:focus,.field select:focus{border-color:rgba(255,107,26,.35);box-shadow:var(--devis-ring);background:#fff;}
        .field textarea{resize:vertical;min-height:120px;}

        /* ✅ FIX VALIDATION : par défaut caché, ne s'affiche QUE quand .show */
        .field-error{
            color:var(--devis-danger);
            font-size:12px;
            font-weight:600;
            display:none;
            align-items:center;
            gap:6px;
        }
        .field-error.show{
            display:flex;
            animation: errorShake .3s ease;
        }
        .field-error::before{
            content:'⚠';
            font-size:13px;
        }
        @keyframes errorShake {
            0%,100% { transform: translateX(0); }
            25% { transform: translateX(-3px); }
            75% { transform: translateX(3px); }
        }

        /* ✅ FIX : champ valide en VERT */
        .field input.error,.field textarea.error,.field select.error{
            border-color:rgba(217,75,75,.55) !important;
            box-shadow:0 0 0 3px rgba(217,75,75,.12) !important;
            background:#fff7f7 !important;
        }
        .field input.valid,.field textarea.valid,.field select.valid{
            border-color:rgba(22,163,111,.45) !important;
            background:#f5fdf9 !important;
        }
        .field input.valid + .field-error,
        .field textarea.valid + .field-error{
            display:none !important;
        }

        /* TYPE CARDS */
        .type-selector{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:24px;}
        .type-card{position:relative;border-radius:24px;padding:22px;cursor:pointer;border:1px solid rgba(31,63,134,.10);background:rgba(255,255,255,.98);transition:.25s;overflow:hidden;box-shadow:0 12px 26px rgba(31,63,134,.05);}
        .type-card:hover{transform:translateY(-2px);border-color:rgba(255,107,26,.18);}
        .type-card.active{background:linear-gradient(135deg,rgba(255,107,26,.08),rgba(255,255,255,.96));border-color:rgba(255,107,26,.22);box-shadow:0 16px 32px rgba(255,107,26,.10);}
        .type-card-icon{width:54px;height:54px;border-radius:18px;display:grid;place-items:center;font-size:22px;color:#fff;background:linear-gradient(135deg,var(--devis-orange),#ff9f66);margin-bottom:14px;box-shadow:0 14px 28px rgba(255,107,26,.16);}
        .type-card-title{color:var(--devis-text);font-size:17px;font-weight:800;margin-bottom:6px;}
        .type-card-text{color:var(--devis-muted);font-size:12px;line-height:1.7;}
        .type-card input[type="radio"]{position:absolute;inset:0;opacity:0;pointer-events:none;}

        /* OFFRES */
        .offer-radio-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:12px;}
        .offer-radio{position:relative;border-radius:22px;border:1px solid rgba(31,63,134,.08);background:rgba(255,255,255,.97);padding:18px;cursor:pointer;transition:.22s;box-shadow:0 12px 22px rgba(31,63,134,.04);}
        .offer-radio:hover{border-color:rgba(255,107,26,.18);transform:translateY(-1px);}
        .offer-radio.active{border-color:rgba(255,107,26,.22);background:linear-gradient(135deg,rgba(255,107,26,.08),rgba(255,255,255,.98));box-shadow:0 16px 30px rgba(255,107,26,.08);}
        .offer-radio input[type="radio"]{position:absolute;opacity:0;inset:0;}
        .offer-radio-title{color:var(--devis-text);font-size:15px;font-weight:800;margin-bottom:8px;}
        .offer-radio-meta{color:var(--devis-muted);font-size:12px;line-height:1.7;margin-bottom:10px;}
        .offer-radio-price{color:var(--devis-text);font-size:13px;font-weight:800;display:inline-flex;align-items:center;gap:8px;padding:8px 10px;border-radius:999px;background:rgba(255,255,255,.94);border:1px solid rgba(31,63,134,.08);}

        /* SOUS-FORMULAIRES */
        .sub-form{display:none;margin-top:6px;animation:fadeSlide .25s ease;}
        .sub-form.active{display:block;}
        @keyframes fadeSlide{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}

        /* INFO BANNER */
        .info-banner{margin-bottom:18px;padding:18px;border-radius:22px;border:1px solid rgba(31,63,134,.08);background:linear-gradient(135deg,rgba(255,255,255,.92),rgba(246,248,252,.92));display:flex;align-items:flex-start;gap:12px;box-shadow:0 12px 28px rgba(31,63,134,.04);}
        .info-banner i{font-size:18px;color:var(--devis-orange);margin-top:2px;}
        .info-banner strong{color:var(--devis-text);display:block;margin-bottom:4px;}
        .info-banner span{color:var(--devis-muted);font-size:13px;line-height:1.75;}

        /* SUMMARY */
        .summary-panel{position:sticky;top:95px;}
        .summary-box{padding:24px;background:linear-gradient(180deg,rgba(241,248,255,.82),rgba(255,255,255,.88));}
        .summary-head{display:flex;align-items:center;gap:12px;margin-bottom:18px;}
        .summary-head-icon{width:56px;height:56px;border-radius:20px;display:grid;place-items:center;font-size:22px;color:#fff;background:linear-gradient(135deg,var(--devis-orange),#ff9b5f);box-shadow:0 16px 28px rgba(255,107,26,.18);}
        .summary-head-title{color:var(--devis-text);font-size:22px;font-weight:800;margin-bottom:4px;}
        .summary-head-sub{color:var(--devis-muted);font-size:12px;}
        .summary-list{display:flex;flex-direction:column;gap:12px;margin-bottom:20px;}
        .summary-item{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:12px 14px;border-radius:16px;border:1px solid rgba(31,63,134,.07);background:rgba(255,255,255,.78);}
        .summary-item .label{color:var(--devis-muted);font-size:12px;line-height:1.5;font-weight:600;}
        .summary-item .value{color:var(--devis-text);font-size:13px;font-weight:800;text-align:right;line-height:1.5;}
        .summary-note{padding:16px;border-radius:18px;border:1px solid rgba(31,63,134,.07);background:rgba(255,255,255,.75);color:var(--devis-muted);font-size:13px;line-height:1.8;margin-bottom:18px;}
        .summary-note strong{color:var(--devis-text);}
        .offer-chips{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;}
        .offer-chip{padding:10px 12px;border-radius:999px;background:#fff;border:1px solid rgba(31,63,134,.08);color:var(--devis-text);font-size:12px;font-weight:700;}
        .cta-stack{display:flex;flex-direction:column;gap:12px;}
        .btn-client-primary{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px 18px;border-radius:16px;border:none;cursor:pointer;text-decoration:none;transition:.25s;font-size:14px;font-weight:800;background:linear-gradient(135deg,var(--devis-orange),var(--devis-orange-dark));color:#fff;box-shadow:0 10px 24px rgba(255,107,26,.18);width:100%;}
        .btn-client-primary:hover{transform:translateY(-1px);}
        .btn-client-primary:disabled{opacity:.6;cursor:not-allowed;transform:none;}
        .btn-client-soft{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px 18px;border-radius:16px;border:1px solid rgba(31,63,134,.08);cursor:pointer;font-size:14px;font-weight:800;background:rgba(255,255,255,.82);color:var(--devis-muted);width:100%;}

        /* TOAST */
        .toast-notif{position:fixed;bottom:24px;right:24px;background:#fff;border:1px solid rgba(31,63,134,.10);border-radius:14px;padding:14px 20px;display:flex;align-items:center;gap:10px;font-size:14px;color:var(--devis-text);z-index:9999;opacity:0;transform:translateY(10px);transition:all .3s;box-shadow:0 12px 28px rgba(31,63,134,.10);}
        .toast-notif.show{opacity:1;transform:translateY(0);}
        .toast-success i{color:var(--devis-green);font-size:18px;}
        .toast-warning i{color:#ff9c2b;font-size:18px;}
        .toast-danger  i{color:var(--devis-danger);font-size:18px;}
        .spin{animation:spin .8s linear infinite;}
        @keyframes spin{to{transform:rotate(360deg)}}

        @media(max-width:1180px){.client-shell,.hero-grid{grid-template-columns:1fr;}.summary-panel{position:static;}}
        @media(max-width:980px){.type-selector,.offer-radio-list,.devis-grid-3,.hero-stats{grid-template-columns:1fr;}.devis-grid-2{grid-template-columns:1fr;}}
        @media(max-width:720px){.hero-banner,.devis-card-body,.devis-card-header,.summary-box{padding:18px;}}
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
                <div class="page-title-main">Demande de devis</div>
                <div class="page-breadcrumb">
                    <i class="bi bi-house"></i>
                    <a href="client.php" style="color:inherit;text-decoration:none;">Accueil</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <span>Demande de devis</span>
                    &nbsp;·&nbsp; <span id="now"></span>
                </div>
            </div>
        </div>
        <div class="content">
<?php if ($success): ?>
<div class="alert-box alert-success"><i class="bi bi-check-circle-fill"></i><div><strong>Demande envoyée !</strong><br><?= htmlspecialchars($success) ?></div></div>
<?php endif; ?>
<?php if ($erreur): ?>
<div class="alert-box alert-danger"><i class="bi bi-exclamation-circle-fill"></i><div><strong>Erreur</strong><br><?= htmlspecialchars($erreur) ?></div></div>
<?php endif; ?>
<?php if (!empty($form_errors)): ?>
<div class="alert-box alert-warning"><i class="bi bi-exclamation-triangle-fill"></i><div><strong>Corrigez les erreurs :</strong><br><?php foreach ($form_errors as $e): ?>— <?= htmlspecialchars($e) ?><br><?php endforeach; ?></div></div>
<?php endif; ?>

            <section class="hero-banner">
                <div class="hero-grid">
                    <div>
                        <div class="hero-title">Obtenez un <span class="accent">devis personnalisé</span> en quelques étapes</div>
                        <div class="hero-sub">Sélectionnez votre type d'assurance, renseignez vos informations et recevez une estimation adaptée à votre situation.</div>
                        <div class="hero-badges">
                            <span class="hero-badge"><i class="bi bi-check2-circle"></i> Demande 100% en ligne</span>
                            <span class="hero-badge"><i class="bi bi-lightning-charge"></i> Réponse rapide</span>
                            <span class="hero-badge"><i class="bi bi-shield-check"></i> Données sécurisées</span>
                        </div>
                    </div>
                    <div class="hero-stats">
                        <div class="hero-stat-card"><div class="hero-stat-value">24h</div><div class="hero-stat-label">Délai moyen de réponse</div></div>
                        <div class="hero-stat-card"><div class="hero-stat-value">3</div><div class="hero-stat-label">Types de devis disponibles</div></div>
                        <div class="hero-stat-card"><div class="hero-stat-value">+2.5k</div><div class="hero-stat-label">Demandes traitées en ligne</div></div>
                        <div class="hero-stat-card"><div class="hero-stat-value">98%</div><div class="hero-stat-label">Clients satisfaits</div></div>
                    </div>
                </div>
            </section>

            <div class="client-shell">
                <section class="devis-card">
                    <div class="devis-card-header">
                        <div>
                            <div class="devis-card-title"><i class="bi bi-ui-checks-grid"></i> Formulaire de devis</div>
                            <div class="devis-card-sub">Les champs changent automatiquement selon votre type d'assurance.</div>
                        </div>
                        <div class="step-pills" id="stepPills">
                            <span class="step-pill active" data-step="1"><i class="bi bi-1-circle"></i> Type</span>
                            <span class="step-pill" data-step="2"><i class="bi bi-2-circle"></i> Offre</span>
                            <span class="step-pill" data-step="3"><i class="bi bi-3-circle"></i> Infos</span>
                            <span class="step-pill" data-step="4"><i class="bi bi-4-circle"></i> Envoi</span>
                        </div>
                    </div>
                    <div class="devis-card-body">
                        <div class="info-banner">
                            <i class="bi bi-info-circle"></i>
                            <div>
                                <strong>Avant de commencer</strong>
                                <span>Choisissez d'abord le type de devis puis sélectionnez l'offre souhaitée.</span>
                            </div>
                        </div>
                        <form id="devisForm" action="<?= BASE_URL ?>/controller/DevisController.php?action=ajouter" method="POST" novalidate>

                            <div class="section-divider">
                                <div class="left"><i class="bi bi-diagram-3"></i> 1. Choisissez votre type de devis</div>
                                <div class="right">Une seule sélection</div>
                            </div>
                            <div class="type-selector">
                                <label class="type-card active" data-type="auto">
                                    <input type="radio" name="type_assurance" value="auto" checked>
                                    <div class="type-card-icon"><i class="bi bi-car-front"></i></div>
                                    <div class="type-card-title">Assurance auto</div>
                                    <div class="type-card-text">Marque, modèle, valeur, usage et année du véhicule.</div>
                                </label>
                                <label class="type-card" data-type="habitation">
                                    <input type="radio" name="type_assurance" value="habitation">
                                    <div class="type-card-icon"><i class="bi bi-house-door"></i></div>
                                    <div class="type-card-title">Assurance habitation</div>
                                    <div class="type-card-text">Superficie, adresse et valeur estimée du bien.</div>
                                </label>
                                <label class="type-card" data-type="sante">
                                    <input type="radio" name="type_assurance" value="sante">
                                    <div class="type-card-icon"><i class="bi bi-heart-pulse"></i></div>
                                    <div class="type-card-title">Assurance santé</div>
                                    <div class="type-card-text">Profil santé, bénéficiaires et couverture souhaitée.</div>
                                </label>
                            </div>

                            <div class="section-divider">
                                <div class="left"><i class="bi bi-stars"></i> 2. Choisissez l'offre</div>
                                <div class="right">Offres réelles depuis la base de données</div>
                            </div>
                            <div class="offer-radio-list" id="offerList">
                                <div style="color:var(--devis-muted);font-size:13px;">Chargement...</div>
                            </div>
                            <div class="field-error" id="err-offre" style="margin-top:8px;">Veuillez sélectionner une offre.</div>

                            <div class="section-divider" style="margin-top:22px;">
                                <div class="left"><i class="bi bi-person-vcard"></i> 3. Vos informations personnelles</div>
                                <div class="right">Champs obligatoires *</div>
                            </div>
                            <div class="devis-grid-2">
                                <div class="field">
                                    <label for="nom"><i class="bi bi-person"></i> Nom *</label>
                                    <input type="text" id="nom" name="nom" placeholder="Ben Salah" value="<?= htmlspecialchars($old['nom'] ?? $userNom) ?>">
                                    <div class="field-error" id="err-nom"></div>
                                </div>
                                <div class="field">
                                    <label for="prenom"><i class="bi bi-person"></i> Prénom *</label>
                                    <input type="text" id="prenom" name="prenom" placeholder="Ali" value="<?= htmlspecialchars($old['prenom'] ?? $userPrenom) ?>">
                                    <div class="field-error" id="err-prenom"></div>
                                </div>
                            </div>
                            <div class="devis-grid-2">
                                <div class="field">
                                    <label for="email"><i class="bi bi-envelope"></i> Email *</label>
                                    <input type="email" id="email" name="email" placeholder="ali@gmail.com" value="<?= htmlspecialchars($old['email'] ?? $userEmail) ?>">
                                    <div class="field-error" id="err-email"></div>
                                </div>
                                <div class="field">
                                    <label for="telephone"><i class="bi bi-telephone"></i> Téléphone *</label>
                                    <input type="tel" id="telephone" name="telephone" placeholder="20123456" maxlength="8" value="<?= htmlspecialchars($old['telephone'] ?? $userTel) ?>">
                                    <div class="field-error" id="err-telephone"></div>
                                </div>
                            </div>

                            <!-- AUTO -->
                            <div class="sub-form active" id="form-auto">
                                <div class="section-divider" style="margin-top:22px;"><div class="left"><i class="bi bi-car-front"></i> 4. Informations véhicule</div><div class="right">Section auto</div></div>
                                <div class="devis-grid-3">
                                    <div class="field">
                                        <label>Marque *</label>
                                        <input type="text" name="marque" id="marque" placeholder="Peugeot" value="<?= htmlspecialchars($old['marque'] ?? '') ?>">
                                        <div class="field-error" id="err-marque"></div>
                                    </div>
                                    <div class="field">
                                        <label>Modèle *</label>
                                        <input type="text" name="modele" id="modele" placeholder="208" value="<?= htmlspecialchars($old['modele'] ?? '') ?>">
                                        <div class="field-error" id="err-modele"></div>
                                    </div>
                                    <div class="field">
                                        <label>Année *</label>
                                        <input type="number" name="annee" id="annee" placeholder="<?= date('Y') ?>" min="1950" max="<?= date('Y')+1 ?>" value="<?= htmlspecialchars($old['annee'] ?? '') ?>">
                                        <div class="field-error" id="err-annee"></div>
                                    </div>
                                </div>
                                <div class="devis-grid-3">
                                    <div class="field">
                                        <label>Immatriculation *</label>
                                        <input type="text" name="immatriculation" id="immatriculation" placeholder="123 TUN 456" value="<?= htmlspecialchars($old['immatriculation'] ?? '') ?>">
                                        <div class="field-error" id="err-immatriculation"></div>
                                    </div>
                                    <div class="field">
                                        <label>Puissance fiscale</label>
                                        <input type="number" name="puissance" id="puissance" placeholder="5" min="1" max="50" value="<?= htmlspecialchars($old['puissance'] ?? '') ?>">
                                        <div class="field-error" id="err-puissance"></div>
                                    </div>
                                    <div class="field">
                                        <label>Carburant</label>
                                        <select name="carburant">
                                            <option value="Essence">Essence</option>
                                            <option value="Diesel">Diesel</option>
                                            <option value="Hybride">Hybride</option>
                                            <option value="Électrique">Électrique</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="devis-grid-2">
                                    <div class="field"><label>Valeur estimée (DT)</label><input type="number" step="0.001" name="valeur_vehicule" placeholder="45000" value="<?= htmlspecialchars($old['valeur_vehicule'] ?? '') ?>"></div>
                                    <div class="field"><label>Usage véhicule</label>
                                        <select name="usage_vehicule">
                                            <option value="Personnel">Personnel</option>
                                            <option value="Professionnel">Professionnel</option>
                                            <option value="Mixte">Mixte</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- HABITATION -->
                            <div class="sub-form" id="form-habitation">
                                <div class="section-divider" style="margin-top:22px;"><div class="left"><i class="bi bi-house-door"></i> 4. Informations habitation</div><div class="right">Section habitation</div></div>
                                <div class="devis-grid-2">
                                    <div class="field"><label>Type d'habitation *</label>
                                        <select name="type_habitation">
                                            <option value="Appartement">Appartement</option>
                                            <option value="Maison">Maison</option>
                                            <option value="Villa">Villa</option>
                                            <option value="Studio">Studio</option>
                                        </select>
                                    </div>
                                    <div class="field"><label>Statut occupation</label>
                                        <select name="statut_occupation">
                                            <option value="proprietaire">Propriétaire</option>
                                            <option value="locataire">Locataire</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="devis-grid-2">
                                    <div class="field devis-full">
                                        <label>Adresse *</label>
                                        <input type="text" name="adresse" id="hab_adresse" placeholder="Résidence, rue, ville, gouvernorat" value="<?= htmlspecialchars($old['adresse'] ?? '') ?>">
                                        <div class="field-error" id="err-hab_adresse"></div>
                                    </div>
                                </div>
                                <div class="devis-grid-3">
                                    <div class="field">
                                        <label>Superficie (m²) *</label>
                                        <input type="number" name="superficie" id="superficie" placeholder="120" min="5" max="10000" value="<?= htmlspecialchars($old['superficie'] ?? '') ?>">
                                        <div class="field-error" id="err-superficie"></div>
                                    </div>
                                    <div class="field"><label>Nombre de pièces</label><input type="number" name="nombre_pieces" placeholder="4" min="1" max="20" value="<?= htmlspecialchars($old['nombre_pieces'] ?? '') ?>"></div>
                                    <div class="field"><label>Valeur estimée (DT)</label><input type="number" step="0.001" name="valeur_bien" placeholder="180000" value="<?= htmlspecialchars($old['valeur_bien'] ?? '') ?>"></div>
                                </div>
                            </div>

                            <!-- SANTE -->
                            <div class="sub-form" id="form-sante">
                                <div class="section-divider" style="margin-top:22px;"><div class="left"><i class="bi bi-heart-pulse"></i> 4. Informations santé</div><div class="right">Section santé</div></div>
                                <div class="devis-grid-3">
                                    <div class="field">
                                        <label>Âge *</label>
                                        <input type="number" name="age" id="sante_age" placeholder="35" min="18" max="120" value="<?= htmlspecialchars($old['age'] ?? '') ?>">
                                        <div class="field-error" id="err-sante_age"></div>
                                    </div>
                                    <div class="field"><label>Situation familiale</label>
                                        <select name="situation_familiale">
                                            <option value="celibataire">Célibataire</option>
                                            <option value="marie">Marié(e)</option>
                                            <option value="divorce">Divorcé(e)</option>
                                        </select>
                                    </div>
                                    <div class="field"><label>Nb bénéficiaires</label><input type="number" name="nombre_beneficiaires" placeholder="1" min="1" max="20" value="<?= htmlspecialchars($old['nombre_beneficiaires'] ?? '1') ?>"></div>
                                </div>
                                <div class="devis-grid-2">
                                    <div class="field">
                                        <label>Profession *</label>
                                        <input type="text" name="profession" id="profession" placeholder="Ingénieur, médecin..." value="<?= htmlspecialchars($old['profession'] ?? '') ?>">
                                        <div class="field-error" id="err-profession"></div>
                                    </div>
                                    <div class="field"><label>Couverture souhaitée</label><input type="text" name="couverture_souhaitee" placeholder="Hospitalisation, consultations..." value="<?= htmlspecialchars($old['couverture_souhaitee'] ?? '') ?>"></div>
                                </div>
                                <div class="devis-grid-2">
                                    <div class="field devis-full"><label>Antécédents médicaux</label><textarea name="antecedents_medicaux" placeholder="Informations médicales importantes (optionnel)"><?= htmlspecialchars($old['antecedents_medicaux'] ?? '') ?></textarea></div>
                                </div>
                            </div>

                            <!-- MESSAGE -->
                            <div class="section-divider" style="margin-top:22px;"><div class="left"><i class="bi bi-chat-left-text"></i> 5. Votre message</div><div class="right">Expliquez votre besoin</div></div>
                            <div class="devis-grid-2">
                                <div class="field devis-full">
                                    <label for="objet">Objet *</label>
                                    <input type="text" id="objet" name="objet" placeholder="Ex : Demande de devis pour assurance auto familiale" value="<?= htmlspecialchars($old['objet'] ?? '') ?>">
                                    <div class="field-error" id="err-objet"></div>
                                </div>
                            </div>
                            <div class="devis-grid-2">
                                <div class="field devis-full">
                                    <label for="message">Message</label>
                                    <textarea id="message" name="message" placeholder="Décrivez brièvement votre besoin..."><?= htmlspecialchars($old['message'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>

                <aside class="devis-card summary-panel">
                    <div class="summary-box">
                        <div class="summary-head">
                            <div class="summary-head-icon"><i class="bi bi-clipboard2-check"></i></div>
                            <div>
                                <div class="summary-head-title">Résumé de votre demande</div>
                                <div class="summary-head-sub">Mis à jour en temps réel pendant la saisie</div>
                            </div>
                        </div>
                        <div class="summary-list" id="summaryList">
                            <div class="summary-item"><div class="label">Type sélectionné</div><div class="value" id="sumType">Auto</div></div>
                            <div class="summary-item"><div class="label">Offre choisie</div><div class="value" id="sumOffre">—</div></div>
                            <div class="summary-item"><div class="label">Client</div><div class="value" id="sumClient">—</div></div>
                            <div class="summary-item"><div class="label">Contact</div><div class="value" id="sumContact">—</div></div>
                            <div class="summary-item"><div class="label">Objet</div><div class="value" id="sumObjet">—</div></div>
                        </div>
                        <div class="summary-note"><strong>Conseil Protex :</strong> plus votre formulaire est détaillé, plus notre retour sera précis.</div>
                        <div class="cta-stack" style="margin-top:20px;">
                            <button class="btn-client-primary" id="submitBtn" type="button"><i class="bi bi-send"></i> Envoyer ma demande de devis</button>
                            <button type="button" class="btn-client-soft" id="resetBtn"><i class="bi bi-arrow-counterclockwise"></i> Réinitialiser le formulaire</button>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>
</div>

<script src="assets/js/main.js"></script>
<script>
// ════════════════════════════════════════════════════
// DONNÉES & CONFIG
// ════════════════════════════════════════════════════
const offresDB = <?= $offresJson ?>;
const typeLabels = { auto:'Auto', habitation:'Habitation', sante:'Santé' };
let currentType = 'auto';
let selectedOfferId = null;

// ════════════════════════════════════════════════════
// 🔥 RÈGLES DE VALIDATION (avec messages PRÉCIS)
// ════════════════════════════════════════════════════
const RULES = {
    nom: {
        validate: v => {
            if (!v.trim()) return "Le nom est obligatoire.";
            if (v.trim().length < 2) return "Le nom doit contenir au moins 2 caractères.";
            if (!/^[a-zA-ZÀ-ÿ\s'-]+$/.test(v.trim())) return "Le nom ne doit contenir que des lettres (pas de chiffres).";
            return null;
        }
    },
    prenom: {
        validate: v => {
            if (!v.trim()) return "Le prénom est obligatoire.";
            if (v.trim().length < 2) return "Le prénom doit contenir au moins 2 caractères.";
            if (!/^[a-zA-ZÀ-ÿ\s'-]+$/.test(v.trim())) return "Le prénom ne doit contenir que des lettres (pas de chiffres).";
            return null;
        }
    },
    email: {
        validate: v => {
            if (!v.trim()) return "L'email est obligatoire.";
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim())) return "Format invalide. Exemple : nom@exemple.com";
            return null;
        }
    },
    telephone: {
        validate: v => {
            const t = v.replace(/\s/g, '');
            if (!t) return "Le téléphone est obligatoire.";
            if (!/^[0-9]+$/.test(t)) return "Le téléphone ne doit contenir que des chiffres.";
            if (t.length !== 8) return "Le téléphone doit faire exactement 8 chiffres (actuellement : " + t.length + ").";
            if (!/^[2-59]/.test(t)) return "Le numéro doit commencer par 2, 3, 4, 5 ou 9.";
            return null;
        }
    },
    objet: {
        validate: v => {
            if (!v.trim()) return "L'objet de la demande est obligatoire.";
            if (v.trim().length < 5) return "L'objet doit contenir au moins 5 caractères.";
            return null;
        }
    },
    // AUTO
    marque: {
        validate: v => {
            if (!v.trim()) return "La marque est obligatoire (ex: Peugeot, Renault).";
            if (v.trim().length < 2) return "La marque doit contenir au moins 2 caractères.";
            if (!/^[a-zA-Z0-9À-ÿ\s'-]+$/.test(v.trim())) return "La marque contient des caractères invalides.";
            return null;
        }
    },
    modele: {
        validate: v => {
            if (!v.trim()) return "Le modèle est obligatoire (ex: 208, Clio).";
            if (v.trim().length < 1) return "Le modèle est obligatoire.";
            return null;
        }
    },
    annee: {
        validate: v => {
            if (!v.trim()) return "L'année est obligatoire.";
            if (!/^[0-9]{4}$/.test(v.trim())) return "L'année doit avoir 4 chiffres (ex: 2020).";
            const an = parseInt(v);
            const cur = new Date().getFullYear();
            if (an < 1950) return "L'année doit être >= 1950.";
            if (an > cur + 1) return "L'année ne peut dépasser " + (cur + 1) + ".";
            return null;
        }
    },
    immatriculation: {
        validate: v => {
            if (!v.trim()) return "L'immatriculation est obligatoire (ex: 123 TUN 456).";
            if (v.trim().length < 4) return "L'immatriculation doit faire au moins 4 caractères.";
            return null;
        }
    },
    puissance: {
        validate: v => {
            if (!v.trim()) return null; // optionnel
            if (!/^[0-9]+$/.test(v.trim())) return "La puissance doit être un nombre.";
            const p = parseInt(v);
            if (p < 1 || p > 50) return "La puissance doit être entre 1 et 50 chevaux.";
            return null;
        }
    },
    // HABITATION
    hab_adresse: {
        validate: v => {
            if (!v.trim()) return "L'adresse est obligatoire.";
            if (v.trim().length < 5) return "L'adresse doit contenir au moins 5 caractères.";
            return null;
        }
    },
    superficie: {
        validate: v => {
            if (!v.trim()) return "La superficie est obligatoire.";
            const s = parseFloat(v);
            if (isNaN(s) || s <= 0) return "La superficie doit être un nombre positif.";
            if (s < 5) return "La superficie doit être >= 5 m².";
            if (s > 10000) return "La superficie doit être <= 10000 m².";
            return null;
        }
    },
    // SANTE
    sante_age: {
        validate: v => {
            if (!v.trim()) return "L'âge est obligatoire.";
            if (!/^[0-9]+$/.test(v.trim())) return "L'âge doit être un nombre.";
            const a = parseInt(v);
            if (a < 18) return "Vous devez être majeur (18 ans minimum).";
            if (a > 120) return "L'âge ne peut pas dépasser 120 ans.";
            return null;
        }
    },
    profession: {
        validate: v => {
            if (!v.trim()) return "La profession est obligatoire.";
            if (v.trim().length < 2) return "La profession doit contenir au moins 2 caractères.";
            return null;
        }
    }
};

// ════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════
function escHtml(s) {
    return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function showFieldError(id, message) {
    const input = document.getElementById(id);
    const err = document.getElementById('err-' + id);
    if (input) {
        input.classList.add('error');
        input.classList.remove('valid');
    }
    if (err) {
        err.textContent = message;
        err.classList.add('show');
    }
}

function clearFieldError(id) {
    const input = document.getElementById(id);
    const err = document.getElementById('err-' + id);
    if (input) {
        input.classList.remove('error');
        input.classList.add('valid');
    }
    if (err) {
        err.classList.remove('show');
        err.textContent = '';
    }
}

function clearFieldNeutral(id) {
    const input = document.getElementById(id);
    const err = document.getElementById('err-' + id);
    if (input) {
        input.classList.remove('error');
        input.classList.remove('valid');
    }
    if (err) {
        err.classList.remove('show');
        err.textContent = '';
    }
}

// ✅ Validation d'un champ unique
function validateField(id) {
    const input = document.getElementById(id);
    if (!input) return true;
    const rule = RULES[id];
    if (!rule) return true;

    const value = input.value;
    const error = rule.validate(value);

    // Si le champ est vide ET pas encore touché, on reste neutre
    if (value === '' && !input.dataset.touched) {
        clearFieldNeutral(id);
        return false;
    }

    if (error) {
        showFieldError(id, error);
        return false;
    } else {
        clearFieldError(id);
        return true;
    }
}

// ════════════════════════════════════════════════════
// RENDU DES OFFRES
// ════════════════════════════════════════════════════
function renderOffers() {
    const offerList = document.getElementById('offerList');
    const items = offresDB[currentType] || [];
    if (!items.length) {
        offerList.innerHTML = '<div style="color:var(--devis-muted);font-size:13px;padding:8px;grid-column:1/-1">Aucune offre disponible pour ce type.</div>';
        selectedOfferId = null;
        return;
    }
    selectedOfferId = items[0].id_offre;
    offerList.innerHTML = items.map((o, i) => `
        <label class="offer-radio ${i===0?'active':''}" data-offer="${o.id_offre}">
            <input type="radio" name="id_offre" value="${o.id_offre}" ${i===0?'checked':''}>
            <div class="offer-radio-title">${escHtml(o.nom_offre)}</div>
            <div class="offer-radio-meta">${escHtml(o.couverture||'')}</div>
            <div class="offer-radio-price"><i class="bi bi-cash-coin"></i> ${parseFloat(o.prix_annuel).toFixed(3)} DT/an</div>
        </label>
    `).join('');

    document.querySelectorAll('.offer-radio').forEach(card => {
        card.addEventListener('click', function () {
            document.querySelectorAll('.offer-radio').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            selectedOfferId = Number(this.dataset.offer);
            this.querySelector('input').checked = true;
            document.getElementById('err-offre').classList.remove('show');
            refreshSummary();
            setStepState(3);
        });
    });
    refreshSummary();
}

// ════════════════════════════════════════════════════
// TYPE SELECTOR
// ════════════════════════════════════════════════════
function initTypeSelector() {
    document.querySelectorAll('.type-card').forEach(card => {
        card.addEventListener('click', function () {
            document.querySelectorAll('.type-card').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            currentType = this.dataset.type;
            this.querySelector('input').checked = true;
            document.querySelectorAll('.sub-form').forEach(f => f.classList.remove('active'));
            document.getElementById(`form-${currentType}`)?.classList.add('active');
            renderOffers();
            refreshSummary();
            setStepState(2);
        });
    });
}

// ════════════════════════════════════════════════════
// RÉSUMÉ EN TEMPS RÉEL
// ════════════════════════════════════════════════════
function refreshSummary() {
    document.getElementById('sumType').textContent = typeLabels[currentType] || currentType;
    const offer = (offresDB[currentType]||[]).find(o => o.id_offre == selectedOfferId);
    document.getElementById('sumOffre').textContent = offer ? offer.nom_offre : '—';
    const nom    = document.getElementById('nom')?.value.trim() || '';
    const prenom = document.getElementById('prenom')?.value.trim() || '';
    const email  = document.getElementById('email')?.value.trim() || '';
    const tel    = document.getElementById('telephone')?.value.trim() || '';
    const objet  = document.getElementById('objet')?.value.trim() || '';
    document.getElementById('sumClient').textContent  = (prenom||nom) ? `${prenom} ${nom}`.trim() : '—';
    document.getElementById('sumContact').textContent = (email||tel) ? [email,tel].filter(Boolean).join(' · ') : '—';
    document.getElementById('sumObjet').textContent   = objet || '—';
}

// ════════════════════════════════════════════════════
// STEPS
// ════════════════════════════════════════════════════
function setStepState(n) {
    document.querySelectorAll('.step-pill').forEach((pill, i) => {
        const s = i + 1;
        pill.classList.remove('active','done');
        if (s < n) pill.classList.add('done');
        if (s === n) pill.classList.add('active');
    });
}

// ════════════════════════════════════════════════════
// VALIDATION GLOBALE AU SUBMIT
// ════════════════════════════════════════════════════
function validateForm() {
    let firstError = null;
    let ok = true;

    // Champs communs (toujours requis)
    const commonFields = ['nom', 'prenom', 'email', 'telephone', 'objet'];
    commonFields.forEach(id => {
        const input = document.getElementById(id);
        if (input) input.dataset.touched = '1';
        if (!validateField(id)) {
            ok = false;
            if (!firstError) firstError = id;
        }
    });

    // Champs spécifiques par type
    if (currentType === 'auto') {
        ['marque', 'modele', 'annee', 'immatriculation'].forEach(id => {
            const input = document.getElementById(id);
            if (input) input.dataset.touched = '1';
            if (!validateField(id)) {
                ok = false;
                if (!firstError) firstError = id;
            }
        });
        // Puissance optionnelle mais valide
        if (!validateField('puissance')) {
            ok = false;
            if (!firstError) firstError = 'puissance';
        }
    } else if (currentType === 'habitation') {
        ['hab_adresse', 'superficie'].forEach(id => {
            const input = document.getElementById(id);
            if (input) input.dataset.touched = '1';
            if (!validateField(id)) {
                ok = false;
                if (!firstError) firstError = id;
            }
        });
    } else if (currentType === 'sante') {
        ['sante_age', 'profession'].forEach(id => {
            const input = document.getElementById(id);
            if (input) input.dataset.touched = '1';
            if (!validateField(id)) {
                ok = false;
                if (!firstError) firstError = id;
            }
        });
    }

    // Offre
    if (!selectedOfferId) {
        document.getElementById('err-offre').classList.add('show');
        ok = false;
    } else {
        document.getElementById('err-offre').classList.remove('show');
    }

    if (!ok) {
        showToast('Veuillez corriger les erreurs avant d\'envoyer.', 'warning');
        // Scroll vers le premier champ en erreur
        if (firstError) {
            const el = document.getElementById(firstError);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => el.focus(), 300);
            }
        }
    }
    return ok;
}

// ════════════════════════════════════════════════════
// SOUMISSION
// ════════════════════════════════════════════════════
document.getElementById('submitBtn').addEventListener('click', function () {
    if (!validateForm()) return;
    this.disabled = true;
    this.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Envoi en cours...';
    document.getElementById('devisForm').submit();
});

// ════════════════════════════════════════════════════
// RESET
// ════════════════════════════════════════════════════
document.getElementById('resetBtn').addEventListener('click', function () {
    document.getElementById('devisForm').reset();
    currentType = 'auto';
    selectedOfferId = null;
    document.querySelectorAll('.type-card').forEach(c => c.classList.toggle('active', c.dataset.type === 'auto'));
    document.querySelectorAll('.sub-form').forEach(f => f.classList.remove('active'));
    document.getElementById('form-auto').classList.add('active');

    // Effacer toutes les erreurs et états valid
    document.querySelectorAll('.field input, .field textarea').forEach(el => {
        el.classList.remove('error', 'valid');
        delete el.dataset.touched;
    });
    document.querySelectorAll('.field-error').forEach(e => {
        e.classList.remove('show');
        e.textContent = '';
    });

    renderOffers();
    setStepState(1);
    refreshSummary();
    showToast('Formulaire réinitialisé.', 'warning');
});

// ════════════════════════════════════════════════════
// TOAST
// ════════════════════════════════════════════════════
function showToast(msg, type='success') {
    const icons = { success:'check-circle', warning:'exclamation-triangle', danger:'x-circle' };
    const t = document.createElement('div');
    t.className = `toast-notif toast-${type}`;
    t.innerHTML = `<i class="bi bi-${icons[type]}"></i><span>${msg}</span>`;
    document.body.appendChild(t);
    setTimeout(() => t.classList.add('show'), 40);
    setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 3000);
}

// ════════════════════════════════════════════════════
// INIT — Validation TEMPS RÉEL (le ❤️ du fichier)
// ════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function () {
    // Date
    const el = document.getElementById('now');
    if (el) el.textContent = new Date().toLocaleDateString('fr-FR', { day:'numeric', month:'long', year:'numeric' });

    initTypeSelector();
    renderOffers();

    // ✅ Liste de TOUS les champs à valider en temps réel
    const allFields = [
        'nom', 'prenom', 'email', 'telephone', 'objet',
        'marque', 'modele', 'annee', 'immatriculation', 'puissance',
        'hab_adresse', 'superficie',
        'sante_age', 'profession'
    ];

    allFields.forEach(id => {
        const input = document.getElementById(id);
        if (!input) return;

        // 🔥 Validation à CHAQUE caractère tapé
        input.addEventListener('input', function () {
            this.dataset.touched = '1';
            validateField(id);
            refreshSummary();
        });

        // 🔥 Validation au blur (quand on quitte le champ)
        input.addEventListener('blur', function () {
            if (this.value.trim() !== '') {
                this.dataset.touched = '1';
                validateField(id);
            }
        });
    });

    // ✅ Filtrer les caractères pour le téléphone (chiffres uniquement)
    document.getElementById('telephone')?.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').substring(0, 8);
    });

    // ✅ Filtrer les caractères pour nom et prénom (lettres uniquement)
    ['nom', 'prenom'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', function() {
            this.value = this.value.replace(/[0-9]/g, ''); // bloquer chiffres
        });
    });

    // ✅ Année : forcer 4 chiffres maximum
    document.getElementById('annee')?.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').substring(0, 4);
    });

    refreshSummary();
});
</script>
</body>
</html>


