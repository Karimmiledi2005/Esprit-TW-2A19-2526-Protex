<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();
$pageTitle  = 'Tableau de bord — Protex';
$activeMenu = 'dashboard';
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Tableau de bord — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <!-- global script moved to end of body -->
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="assets/vendor/leaflet/leaflet.css" />
    <script src="assets/vendor/leaflet/leaflet.js"></script>

    <style>
        /* Force dark text in recommendation cards — prevent CSS override */
        #ai-recommandations .recomm-card-text { color: #15233C !important; }
        #ai-recommandations .recomm-card-price { color: #15233C !important; }
        #ai-recommandations .recomm-raison { color: #6b7280 !important; }
        .toast-notif {
            position: fixed; bottom: 24px; right: 24px;
            background: var(--navy-mid); border: 1px solid var(--border);
            border-radius: 12px; padding: 14px 20px;
            display: flex; align-items: center; gap: 10px;
            font-size: 14px; color: var(--text-primary);
            z-index: 9999; opacity: 0; transform: translateY(10px);
            transition: all 0.3s ease; box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }
        .toast-notif.show { opacity: 1; transform: translateY(0); }
        .toast-success i { color: var(--success); font-size: 18px; }
        .toast-warning i { color: var(--gold); font-size: 18px; }
        .toast-danger  i { color: var(--danger); font-size: 18px; }

        /* ===== SINISTRES SLIDE PANEL ===== */
        .sinistres-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            z-index: 8000;
            align-items: flex-start;
            justify-content: flex-end;
        }
        .sinistres-overlay.open { display: flex; }

        .sinistres-panel {
            width: min(860px, 92vw);
            height: 100vh;
            background: var(--navy-mid, #0f1f3d);
            border-left: 1px solid var(--border, rgba(255,255,255,0.1));
            overflow-y: auto;
            transform: translateX(100%);
            transition: transform 0.38s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
        }
        .sinistres-overlay.open .sinistres-panel {
            transform: translateX(0);
        }

        /* Panel header */
        .sp-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border, rgba(255,255,255,0.08));
            position: sticky;
            top: 0;
            background: var(--navy-mid, #0f1f3d);
            z-index: 10;
        }
        .sp-header-left { display: flex; align-items: center; gap: 12px; }
        .sp-icon {
            width: 38px; height: 38px; border-radius: 10px;
            background: rgba(0,180,216,0.15); color: var(--accent, #00b4d8);
            display: flex; align-items: center; justify-content: center; font-size: 17px;
        }
        .sp-title { font-size: 16px; font-weight: 600; color: #fff; }
        .sp-sub   { font-size: 12px; color: var(--text-secondary, rgba(255,255,255,0.5)); margin-top: 1px; }
        .sp-close {
            width: 36px; height: 36px; border-radius: 10px;
            background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
            color: #fff; cursor: pointer; font-size: 16px;
            display: flex; align-items: center; justify-content: center;
            transition: 0.2s;
        }
        .sp-close:hover { background: rgba(255,255,255,0.14); transform: rotate(90deg); }

        /* Panel body */
        .sp-body { padding: 20px 24px; flex: 1; }

        /* ===== Inline sinistre styles (scoped) ===== */
        .sp-section-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 16px;
        }
        .sp-section-title { font-size: 14px; font-weight: 600; color: #fff; }
        .sp-section-sub   { font-size: 12px; color: var(--text-secondary, rgba(255,255,255,0.5)); }

        .sp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 640px) { .sp-grid { grid-template-columns: 1fr; } }

        /* sinistre-box already defined in client.css, reuse it */
        .sinistre-box { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 16px; margin-bottom: 12px; }
        .sinistre-box.sp-selected { border-color: var(--accent, #00b4d8) !important; }
        .sinistre-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
        .sinistre-title  { font-size: 14px; font-weight: 500; color: #fff; }
        .sinistre-meta   { font-size: 12px; color: var(--text-secondary, rgba(255,255,255,0.5)); margin-bottom: 12px; }

        .badge { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 500; }
        .badge-warning { background: rgba(244,162,97,0.15); color: #f4a261; animation: sp-pulse 2s infinite; }
        .badge-success { background: rgba(46,196,182,0.15); color: #2ec4b6; }
        .badge-info    { background: rgba(0,180,216,0.15);  color: #00b4d8; }
        .badge-danger  { background: rgba(230,57,70,0.15);  color: #e63946; }
        @keyframes sp-pulse {
            0%,100% { box-shadow: 0 0 0 0 rgba(244,162,97,0.4); }
            50%      { box-shadow: 0 0 0 6px rgba(244,162,97,0); }
        }

        .montant-banner {
            background: linear-gradient(135deg, rgba(46,196,182,0.15), rgba(46,196,182,0.05));
            border: 1px solid rgba(46,196,182,0.3); border-radius: 10px;
            padding: 12px 14px; display: flex; align-items: center; gap: 12px; margin-bottom: 10px;
        }
        .montant-banner i { font-size: 22px; color: #2ec4b6; }
        .montant-banner-label  { font-size: 10px; color: var(--text-secondary, rgba(255,255,255,0.5)); }
        .montant-banner-amount { font-size: 18px; font-weight: 700; color: #2ec4b6; }

        .sp-action-row { display: flex; gap: 8px; margin-top: 10px; }
        .sp-btn {
            padding: 7px 14px; border-radius: 8px; font-size: 12px; cursor: pointer;
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            color: #fff; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px;
        }
        .sp-btn:hover { background: rgba(255,255,255,0.12); }
        .sp-btn.danger { border-color: rgba(230,57,70,0.3); color: #e63946; }
        .sp-btn.danger:hover { background: rgba(230,57,70,0.1); }
        .sp-btn.primary {
            background: var(--accent, #00b4d8); border-color: var(--accent, #00b4d8); font-weight: 600;
        }
        .sp-btn.primary:hover { opacity: 0.9; transform: translateY(-1px); }

        /* Detail panel */
        .sp-detail { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07); border-radius: 12px; padding: 16px; }

        /* Timeline */
        .sp-timeline { display: flex; flex-direction: column; gap: 0; margin-bottom: 14px; }
        .sp-tl-item  { display: flex; gap: 12px; align-items: flex-start; padding-bottom: 18px; position: relative; }
        .sp-tl-item:last-child { padding-bottom: 0; }
        .sp-tl-item:not(:last-child)::before {
            content: ''; position: absolute; left: 4px; top: 13px;
            width: 1px; height: calc(100% - 4px); background: rgba(255,255,255,0.1);
        }
        .sp-tl-dot { width: 10px; height: 10px; border-radius: 50%; margin-top: 3px; flex-shrink: 0; position: relative; z-index: 1; }
        .sp-tl-dot.green { background: #2ec4b6; box-shadow: 0 0 7px rgba(46,196,182,0.5); }
        .sp-tl-dot.blue  { background: #00b4d8; box-shadow: 0 0 7px rgba(0,180,216,0.5); }
        .sp-tl-dot.gold  { background: #f4a261; box-shadow: 0 0 7px rgba(244,162,97,0.5); }
        .sp-tl-dot.gray  { background: rgba(255,255,255,0.3); }
        .sp-tl-dot.red   { background: #e63946; }
        .sp-tl-title { font-size: 13px; font-weight: 500; color: #fff; }
        .sp-tl-date  { font-size: 11px; color: rgba(255,255,255,0.4); margin-top: 2px; }
        .sp-tl-desc  { font-size: 12px; color: rgba(255,255,255,0.4); margin-top: 3px; }

        /* Traitement */
        .sp-trait-item {
            border: 1px solid rgba(255,255,255,0.07); border-radius: 10px;
            padding: 12px; margin-bottom: 10px; background: rgba(255,255,255,0.02);
        }
        .sp-trait-item.final { border-color: rgba(46,196,182,0.25); background: rgba(46,196,182,0.04); }
        .sp-trait-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
        .sp-trait-step   { display: flex; align-items: center; gap: 8px; }
        .sp-trait-num {
            width: 26px; height: 26px; border-radius: 7px;
            background: rgba(0,180,216,0.15); color: #00b4d8;
            font-size: 10px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
        }
        .sp-trait-num.success { background: rgba(46,196,182,0.15); color: #2ec4b6; }
        .sp-trait-label { font-size: 12px; font-weight: 500; color: #fff; }
        .sp-trait-date  { font-size: 10px; color: rgba(255,255,255,0.4); }
        .sp-trait-row   { display: flex; align-items: center; gap: 6px; font-size: 11px; color: #2ec4b6; padding-left: 34px; }
        .sp-trait-row i { font-size: 12px; }

        /* Comments */
        .sp-comment-section { margin-top: 14px; }
        .sp-comment-title   { font-size: 13px; font-weight: 500; color: #fff; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
        .sp-comment-item    { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07); border-radius: 9px; padding: 9px 11px; margin-bottom: 7px; }
        .sp-comment-meta    { font-size: 10px; color: rgba(255,255,255,0.4); margin-bottom: 3px; }
        .sp-comment-text    { font-size: 12px; color: #fff; }
        .sp-comment-form    { display: flex; gap: 7px; margin-top: 8px; }
        .sp-comment-input {
            flex: 1; padding: 9px 11px; border-radius: 9px;
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            color: #fff; font-size: 12px; outline: none; font-family: inherit; transition: 0.2s;
        }
        .sp-comment-input:focus { border-color: #00b4d8; box-shadow: 0 0 10px rgba(0,180,216,0.2); }
        .sp-comment-input::placeholder { color: rgba(255,255,255,0.3); }
        .sp-comment-send {
            padding: 9px 13px; background: #00b4d8; border: none;
            border-radius: 9px; color: #fff; cursor: pointer; font-size: 12px; transition: 0.2s;
        }
        .sp-comment-send:hover { transform: translateY(-1px); opacity: 0.9; }

        /* Declare / Edit modal (inside panel) */
        .sp-modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(8px);
            z-index: 9500; align-items: center; justify-content: center;
        }
        .sp-modal-overlay.open { display: flex; }
        .sp-modal-box {
            width: 460px; max-width: 94vw; padding: 26px;
            border-radius: 18px;
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255,255,255,0.18);
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            color: #fff; position: relative;
            animation: spPop 0.22s ease;
        }
        @keyframes spPop { from { transform: scale(0.92); opacity:0; } to { transform: scale(1); opacity:1; } }
        .sp-modal-title { font-size: 16px; font-weight: 600; margin-bottom: 18px; }
        .sp-form-group  { margin-bottom: 14px; }
        .sp-form-label  { font-size: 12px; color: rgba(255,255,255,0.6); margin-bottom: 6px; display: block; }
        .sp-form-control {
            width: 100%; padding: 11px 13px; border-radius: 11px;
            background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.14);
            color: #fff; outline: none; font-family: inherit; font-size: 13px; transition: 0.2s;
            box-sizing: border-box;
        }
        .sp-form-control:focus { border-color: #00b4d8; box-shadow: 0 0 14px rgba(0,180,216,0.2); }
        .sp-form-control::placeholder { color: rgba(255,255,255,0.3); }
        select.sp-form-control { appearance: none; cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 24 24'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 12px center; background-size: 14px; padding-right: 36px;
        }
        select.sp-form-control option { background: #0f172a; }
        .sp-btn-submit {
            width: 100%; padding: 12px; border: none; border-radius: 11px;
            background: #00b4d8; color: #fff; font-size: 14px; font-weight: 600;
            cursor: pointer; transition: 0.2s; margin-top: 4px;
        }
        .sp-btn-submit:hover { opacity: 0.9; transform: translateY(-1px); }

        /* Confirm delete modal */
        .sp-confirm-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(6px);
            z-index: 9600; align-items: center; justify-content: center;
        }
        .sp-confirm-overlay.open { display: flex; }
        .sp-confirm-box {
            background: var(--navy-mid, #0f1f3d); border: 1px solid rgba(230,57,70,0.3);
            border-radius: 16px; padding: 28px; width: 340px; max-width: 90vw;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5); text-align: center;
            animation: spPop 0.2s ease;
        }
        .sp-confirm-icon  { font-size: 34px; color: #e63946; margin-bottom: 10px; }
        .sp-confirm-title { font-size: 15px; font-weight: 600; color: #fff; margin-bottom: 6px; }
        .sp-confirm-sub   { font-size: 12px; color: rgba(255,255,255,0.5); margin-bottom: 18px; }
        .sp-confirm-actions { display: flex; gap: 10px; }
        .sp-confirm-cancel {
            flex: 1; padding: 10px; border-radius: 9px;
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.6); cursor: pointer; font-size: 13px; transition: 0.2s;
        }
        .sp-confirm-cancel:hover { background: rgba(255,255,255,0.1); }
        .sp-confirm-delete {
            flex: 1; padding: 10px; border-radius: 9px;
            background: linear-gradient(135deg, #e63946, #f55); border: none;
            color: #fff; cursor: pointer; font-size: 13px; font-weight: 500; transition: 0.2s;
        }
        .sp-confirm-delete:hover { transform: translateY(-1px); }

        .sp-divider-title {
            font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.5);
            text-transform: uppercase; letter-spacing: 0.7px;
            margin: 14px 0 10px;
            display: flex; align-items: center; gap: 8px;
        }
        .sp-divider-title::after {
            content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.07);
        }

        /* ===== AVATAR ANIMATION ===== */
        @media (prefers-reduced-motion: no-preference) {
            .avatar-btn, .dropdown-avatar, .avatar-placeholder {
                position: relative;
                z-index: 1; /* For stacking context */
                transition: all 0.3s ease;
            }
            .avatar-btn::before, .dropdown-avatar::before, .avatar-placeholder::before {
                content: '';
                position: absolute;
                top: -3px; left: -3px; right: -3px; bottom: -3px;
                border-radius: 50%;
                background: linear-gradient(45deg, #FF6B1A, #1A3A7A);
                z-index: -1;
                animation: ptx_spin_ring 3s linear infinite;
            }
            .avatar-btn:hover, .dropdown-avatar:hover, .avatar-placeholder:hover {
                transform: scale(1.08);
                box-shadow: 0 0 20px rgba(255,107,26,0.5);
            }
            .avatar-btn::after, .dropdown-avatar::after, .avatar-placeholder::after {
                content: 'EN LIGNE';
                position: absolute;
                bottom: -4px; right: -22px;
                background: #2ed573;
                color: white;
                font-size: 7px;
                padding: 2px 4px;
                border-radius: 4px;
                font-weight: bold;
                animation: ptx_pulse 1.5s infinite;
                z-index: 10;
                pointer-events: none;
            }
            @keyframes ptx_spin_ring {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            @keyframes ptx_pulse {
                0% { box-shadow: 0 0 0 0 rgba(46,213,115,0.7); }
                70% { box-shadow: 0 0 0 6px rgba(46,213,115,0); }
                100% { box-shadow: 0 0 0 0 rgba(46,213,115,0); }
            }
        }
        /* ===== AGENCY LOCATOR ===== */
        .agency-item {
            padding: 16px;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
            border: 1px solid transparent;
            margin-bottom: 8px;
        }
        .agency-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        .agency-item.active {
            background: rgba(255, 107, 26, 0.1);
            border-color: rgba(255, 107, 26, 0.3);
        }
        .agency-name { font-weight: 600; color: #fff; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
        .agency-address { font-size: 12px; color: rgba(255, 255, 255, 0.5); line-height: 1.4; }
        .agency-phone { font-size: 12px; color: #FF6B1A; margin-top: 8px; display: block; }
        
        /* Leaflet Dark Mode Filter */
        .leaflet-tile-pane {
            filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%);
        }
        .leaflet-container { background: #0a0f1e !important; }
    </style>

    <!-- FrontOffice unifie - surcharge thème camarades dark-navy -->
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css"></head>
<body>

<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">
    <!-- ===== NAVBAR ===== -->
    <?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

    <!-- ===== MAIN ===== -->
    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title-main" id="welcome"></div>
                <div class="page-breadcrumb">
                    <i class="bi bi-house"></i>
                    <a href="client.php" style="color:inherit;text-decoration:none;">Accueil</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <span>Tableaux De Bord</span>
                    &nbsp;—&nbsp;
                    <span id="currentDate"></span>
                </div>
            </div>
        </div>

        <div class="content">

            <!-- ALERTE PAIEMENT J-7 -->
            <div id="payment-alert-banner" style="display:none; margin-bottom:20px; padding:16px 20px; border-radius:16px; background:linear-gradient(135deg, rgba(245,158,11,0.15), rgba(239,68,68,0.10)); border:1px solid rgba(245,158,11,0.35); display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                <div style="width:44px; height:44px; border-radius:12px; background:rgba(245,158,11,0.2); display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">
                    <i class="bi bi-alarm" style="color:#fbbf24;"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-size:15px; font-weight:700; color:#fcd34d; margin-bottom:3px;">Paiement à venir dans moins de 7 jours</div>
                    <div id="payment-alert-detail" style="font-size:13px; color:rgba(255,255,255,0.7);"></div>
                </div>
                <a href="offres.php" style="padding:9px 18px; border-radius:10px; background:rgba(245,158,11,0.2); border:1px solid rgba(245,158,11,0.4); color:#fcd34d; font-size:13px; font-weight:700; text-decoration:none; white-space:nowrap;">
                    <i class="bi bi-credit-card"></i> Payer maintenant
                </a>
            </div>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon"><i class="bi bi-file-earmark-check"></i></div>
                    <div class="stat-value" id="kpi-contrats-actifs"><div class="skeleton" style="width:60px;height:32px;"></div></div>
                    <div class="stat-label">Contrats actifs</div>
                </div>
                <div class="stat-card gold" style="cursor:pointer;" onclick="openSinistresPanel(event)" title="Voir mes sinistres">
                    <div class="stat-icon"><i class="bi bi-shield-exclamation"></i></div>
                    <div class="stat-value" id="kpi-sinistres"><div class="skeleton" style="width:60px;height:32px;"></div></div>
                    <div class="stat-label">Sinistre(s) ouvert(s)</div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon"><i class="bi bi-credit-card"></i></div>
                    <div class="stat-value" id="kpi-montant"><div class="skeleton" style="width:80px;height:32px;"></div></div>
                    <div class="stat-label">Total payé</div>
                </div>
                <div class="stat-card red">
                    <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
                    <div class="stat-value" id="kpi-devis-attente"><div class="skeleton" style="width:60px;height:32px;"></div></div>
                    <div class="stat-label">Devis en attente</div>
                </div>
            </div>

            <!-- QUICK ACTIONS WIDGET -->
            <div style="background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: var(--radius-lg); padding: 20px 24px; margin-bottom: 28px; box-shadow: var(--shadow-sm);">
                <div class="section-title" style="margin-bottom: 14px; font-size: 15px;">
                    <i class="bi bi-lightning-charge" style="color: var(--orange); font-size: 18px;"></i>
                    Actions rapides
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px;">
                    <a href="declarer-sinistre.php" class="btn btn-primary" style="width: 100%; justify-content: center; gap: 8px;">
                        <i class="bi bi-exclamation-octagon"></i>
                        <span>Déclarer sinistre</span>
                    </a>
                    <a href="ajoutdevis.php" class="btn btn-outline" style="width: 100%; justify-content: center; gap: 8px; border-color: var(--orange); color: var(--orange);">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Nouveau devis</span>
                    </a>
                    <a href="contrat.php" class="btn btn-secondary" style="width: 100%; justify-content: center; gap: 8px;">
                        <i class="bi bi-file-earmark-check"></i>
                        <span>Mes contrats</span>
                    </a>
                    <a href="offres.php" class="btn btn-ghost" style="width: 100%; justify-content: center; gap: 8px; border-color: var(--border); color: var(--text-secondary);">
                        <i class="bi bi-tag"></i>
                        <span>Découvrir offres</span>
                    </a>
                </div>
            </div>

            <!-- O6: IA Recommandations Widget -->
            <div id="ai-recommandations" style="background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: var(--radius-lg); padding: 20px 24px; margin-bottom: 28px; box-shadow: var(--shadow-sm);">
                <div class="section-title" style="margin-bottom: 14px; font-size: 15px; display:flex; align-items:center; justify-content:space-between;">
                    <span><i class="bi bi-stars" style="color: #a78bfa; font-size: 18px;"></i> Recommandé pour vous</span>
                    <span style="font-size:11px; font-weight:500; color:#7c3aed; background:rgba(167,139,250,0.1); padding:3px 10px; border-radius:20px; border:1px solid rgba(167,139,250,0.2);">IA Protex</span>
                </div>
                <div id="recomm-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap:12px;">
                    <div class="skeleton" style="height:110px; border-radius:14px;"></div>
                    <div class="skeleton" style="height:110px; border-radius:14px;"></div>
                    <div class="skeleton" style="height:110px; border-radius:14px;"></div>
                </div>
            </div>

            <!-- CONTRATS -->
            <div class="section-header">
                <div>
                    <div class="section-title">Mes contrats</div>
                    <div class="section-sub" id="contrats-sub"><span class="skeleton" style="width:120px;height:14px;display:inline-block;"></span></div>
                </div>
                <a href="contrat.php" class="btn btn-outline btn-sm">
                    <i class="bi bi-arrow-right"></i> Voir tout
                </a>
            </div>

            <div class="grid-3" style="margin-bottom: 28px;" id="contrats-list">
                <div class="skeleton" style="height:180px;border-radius:16px;"></div>
                <div class="skeleton" style="height:180px;border-radius:16px;"></div>
                <div class="skeleton" style="height:180px;border-radius:16px;"></div>
            </div>

            <!-- SINISTRES + PAIEMENTS -->
            <div class="grid-2">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Sinistres récents</div>
                        <a href="mes-sinistres.php" class="btn btn-outline btn-sm">Voir tout</a>
                    </div>
                    <div class="card-body" id="sinistres-list">
                        <div class="skeleton" style="height:120px;border-radius:12px;"></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Derniers paiements</div>
                        <a href="paiement.php" class="btn btn-outline btn-sm">Voir tout</a>
                    </div>
                    <div class="card-body" id="paiements-list">
                        <div class="skeleton" style="height:120px;border-radius:12px;"></div>
                    </div>
                </div>
            </div>



        </div>
    </main>
</div>

<!-- ======================================================
     SINISTRES SLIDE PANEL
     ====================================================== -->
<div class="sinistres-overlay" id="sinistresOverlay" onclick="handleOverlayClick(event)">
    <div class="sinistres-panel" id="sinistresPanel">

        <!-- Panel Header -->
        <div class="sp-header">
            <div class="sp-header-left">
                <div class="sp-icon"><i class="bi bi-shield-exclamation"></i></div>
                <div>
                    <div class="sp-title">Mes sinistres</div>
                    <div class="sp-sub">Déclarer et suivre vos demandes</div>
                </div>
            </div>
            <button class="sp-close" onclick="closeSinistresPanel()" title="Fermer">?</button>
        </div>

        <!-- Panel Body -->
        <div class="sp-body">

            <!-- Top action bar -->
            <div class="sp-section-header" style="margin-bottom:18px;">
                <div>
                    <div class="sp-section-title" id="spCount">2 sinistres</div>
                    <div class="sp-section-sub">Cliquez sur un sinistre pour voir les détails</div>
                </div>
                <button class="sp-btn primary" onclick="spOpenDeclareModal()">
                    <i class="bi bi-plus-lg"></i> Déclarer
                </button>
            </div>

            <!-- Two-column grid: list | detail -->
            <div class="sp-grid">
                <div id="spList"></div>
                <div id="spDetail">
                    <div class="sp-detail" style="text-align:center;padding:40px 20px;color:rgba(255,255,255,0.3);">
                        <i class="bi bi-shield" style="font-size:34px;opacity:0.25;display:block;margin-bottom:10px;"></i>
                        <span style="font-size:13px;">Sélectionnez un sinistre</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Declare / Edit modal -->
<div class="sp-modal-overlay" id="spModal">
    <div class="sp-modal-box">
        <div class="sp-modal-title" id="spModalTitle">Déclarer un sinistre</div>
        <form onsubmit="spHandleSubmit(event)">
            <div class="sp-form-group">
                <label class="sp-form-label">Votre contrat</label>
                <select id="sp_contrat_id" class="sp-form-control" onchange="spOnContratChange()">
                    <option value="">Chargement des contrats...</option>
                </select>
            </div>
            <div class="sp-form-group">
                <label class="sp-form-label">Type de sinistre</label>
                <select id="sp_type" class="sp-form-control">
                    <option value="">Choisissez d'abord un contrat</option>
                </select>
            </div>
            <div class="sp-form-group">
                <label class="sp-form-label">Description</label>
                <textarea id="sp_desc" class="sp-form-control" rows="3" placeholder="Décrivez le sinistre..." style="resize:vertical;"></textarea>
            </div>
            <div class="sp-form-group">
                <label class="sp-form-label">Date de déclaration</label>
                <input type="date" id="sp_date" class="sp-form-control">
            </div>
            <div class="sp-form-group">
                <label class="sp-form-label">Photo</label>
                <input type="file" id="sp_photo" class="sp-form-control" accept="image/*">
            </div>
            <button type="submit" class="sp-btn-submit">Envoyer</button>
        </form>
        <button class="sp-close" style="position:absolute;top:14px;right:14px;" onclick="spCloseModal()">?</button>
    </div>
</div>

<!-- Confirm delete modal -->
<div class="sp-confirm-overlay" id="spConfirmDel">
    <div class="sp-confirm-box">
        <div class="sp-confirm-icon"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="sp-confirm-title">Supprimer ce sinistre ?</div>
        <div class="sp-confirm-sub">Cette action est irréversible.</div>
        <div class="sp-confirm-actions">
            <button class="sp-confirm-cancel" onclick="spCloseConfirm()">Annuler</button>
            <button class="sp-confirm-delete" onclick="spConfirmDelete()">Supprimer</button>
        </div>
    </div>
</div>

<div class="toast-notif" id="toastNotif">
    <i class="bi bi-check-circle"></i>
    <span id="toastMsg"></span>
</div>

<script>
    // -- Dashboard date --
    const now = new Date();
    const dateStr = now.toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
    document.getElementById('currentDate').textContent =
        'Tableau de bord — ' + dateStr.charAt(0).toUpperCase() + dateStr.slice(1);

    // -------------------------------------------
    //  DYNAMIC DASHBOARD LOADER
    // -------------------------------------------
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            const res  = await fetch('api_client_dashboard.php');
            const data = await res.json();
            if (!data.success) throw new Error(data.error);

            const welcome = document.getElementById('welcome');
            if (welcome) {
                const hour = new Date().getHours();
                const greeting = hour >= 18 ? 'Bonsoir' : 'Bonjour';
                welcome.textContent = `${greeting}, ${data.kpi.nom} 👋`;
                welcome.style.fontFamily = 'var(--font-display)';
                welcome.style.fontSize = '28px';
                welcome.style.fontWeight = '700';
                welcome.style.color = 'var(--text-primary)';
            }

            injectKpi('kpi-contrats-actifs', data.kpi.contrats_actifs, 'Contrats actifs');
            injectKpi('kpi-devis-attente', data.kpi.devis_en_attente, 'Devis en attente');
            injectKpi('kpi-sinistres', data.kpi.sinistres_ouverts, 'Sinistres ouverts');
            injectKpi('kpi-montant', formatMoney(data.kpi.montant_total), 'Total payé');

            renderContrats(data.contrats);
            renderSinistres(data.sinistres);
            renderPaiements(data.paiements);

            // J-7 Payment alert
            checkUpcomingPayments();

        } catch (err) {
            console.error('Dashboard load error:', err);
            showToast('Erreur de chargement du tableau de bord', 'danger');
        }
    });

    async function checkUpcomingPayments() {
        try {
            const res = await fetch('api_paiements.php?action=upcoming_alerts');
            if (!res.ok) return;
            const data = await res.json();
            if (data.success && data.alerts && data.alerts.length > 0) {
                const banner = document.getElementById('payment-alert-banner');
                const detail = document.getElementById('payment-alert-detail');
                if (banner && detail) {
                    const alert = data.alerts[0];
                    const days = alert.jours_restants;
                    detail.textContent = `Contrat ${alert.numero_contrat} — Échéance le ${alert.date_echeance} (dans ${days} jour${days > 1 ? 's' : ''}) — Prime : ${alert.prime}`;
                    banner.style.display = 'flex';
                }
            }
        } catch(e) {
            // Non-blocking
        }
    }

    function injectKpi(id, value, label) {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerHTML = `
            <div class="stat-value counter" data-target="${value}">${value}</div>
            <div class="stat-label">${label}</div>
        `;
        animateCounter(el.querySelector('.counter'));
    }

    function animateCounter(el) {
        if (!el) return;
        const target = parseFloat(el.dataset.target) || 0;
        let current = 0;
        const step  = target / 40;
        const timer = setInterval(() => {
            current += step;
            if (current >= target) { el.textContent = el.dataset.target; clearInterval(timer); return; }
            el.textContent = Math.floor(current);
        }, 30);
    }

    function formatMoney(val) {
        return new Intl.NumberFormat('fr-TN', { style: 'currency', currency: 'TND' }).format(val || 0);
    }

    function renderContrats(contrats) {
        const sub = document.getElementById('contrats-sub');
        if (sub) sub.textContent = (contrats ? contrats.length : 0) + ' contrat(s)';

        const container = document.getElementById('contrats-list');
        if (!container) return;
        if (!contrats || contrats.length === 0) {
            container.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-secondary);">Aucun contrat trouvé.</div>';
            return;
        }
        container.innerHTML = contrats.slice(0, 3).map(c => {
            const type = (c.type_offre || c.nom_categorie || 'contrat').toLowerCase();
            const iconMap = { 'auto':'bi-car-front', 'habitation':'bi-house-heart', 'sante':'bi-heart-pulse', 'vie':'bi-shield-heart' };
            const icon = Object.keys(iconMap).find(k => type.includes(k)) ? iconMap[Object.keys(iconMap).find(k => type.includes(k))] : 'bi-file-earmark';
            return `<div class="contrat-card hover-lift">
                <div class="contrat-type">
                    <div class="contrat-icon"><i class="bi ${icon}"></i></div>
                    <div>
                        <div class="contrat-name">${escHtml(c.nom_offre || 'Contrat')}</div>
                        <div class="contrat-ref">${escHtml(c.statut || '')}</div>
                    </div>
                </div>
                <div class="contrat-info">
                    <div class="info-item">
                        <label>Début</label>
                        <span>${escHtml(c.date_debut || '—')}</span>
                    </div>
                    <div class="info-item">
                        <label>Fin</label>
                        <span>${escHtml(c.date_fin || '—')}</span>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    function renderSinistres(sinistres) {
        const container = document.getElementById('sinistres-list');
        if (!container) return;
        if (!sinistres || sinistres.length === 0) {
            container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-secondary);font-size:13px;">Aucun sinistre déclaré.</div>';
            return;
        }
        container.innerHTML = sinistres.slice(0, 3).map(s => `
            <div class="sinistre-box" style="margin-bottom:8px;">
                <div class="sinistre-header">
                    <div class="sinistre-title">${escHtml(s.type_sinistre || 'Sinistre')}</div>
                    <span class="badge ${s.statut === 'ouvert' ? 'badge-warning' : 'badge-info'}">${escHtml(s.statut || '—')}</span>
                </div>
                <div class="sinistre-meta">${escHtml(s.date_declaration || '—')}</div>
            </div>
        `).join('');
    }

    function renderPaiements(paiements) {
        const container = document.getElementById('paiements-list');
        if (!container) return;
        if (!paiements || paiements.length === 0) {
            container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-secondary);font-size:13px;">Aucun paiement effectué.</div>';
            return;
        }
        container.innerHTML = paiements.slice(0, 3).map(p => `
            <div class="payment-item">
                <div class="payment-left">
                    <div class="payment-icon"><i class="bi bi-receipt"></i></div>
                    <div>
                        <div class="payment-name">${escHtml(p.nom_offre || 'Paiement')}</div>
                        <div class="payment-date">${escHtml(p.date_paiement || '—')}</div>
                    </div>
                </div>
                <div class="payment-right" style="display:flex; align-items:center; gap:10px;">
                    <div class="payment-amount">${formatMoney(p.montant)}</div>
                    <span class="badge ${p.statut === 'valide' ? 'badge-success' : 'badge-warning'}" style="font-size:10px">${escHtml(p.statut || '—')}</span>
                    ${p.statut === 'valide' ? `<a href="recu_paiement.php?id=${p.id_paiement}" target="_blank" style="color:#00b4d8; font-size:16px;" title="Télécharger le reçu"><i class="bi bi-download"></i></a>` : ''}
                </div>
            </div>
        `).join('');
    }

    function renderRecommandations(recommandations) {
        const container = document.getElementById('recomm-grid');
        if (!container) return;
        if (!recommandations || recommandations.length === 0) {
            container.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:20px;color:#6b7280;font-size:13px;">Aucune recommandation disponible pour le moment.</div>';
            return;
        }
        const borderMap = { 'auto':'#1a3a7a', 'sante':'#059669', 'habitation':'#FF6B1A', 'vie':'#7c3aed' };
        const iconMap = { 'auto':'bi-car-front', 'habitation':'bi-house-heart', 'sante':'bi-heart-pulse', 'vie':'bi-shield-heart' };
        container.innerHTML = recommandations.map(r => {
            const type = (r.type_offre || '').toLowerCase();
            const icon = iconMap[type] || 'bi-stars';
            const bc = borderMap[type] || '#e5e7eb';
            return `
            <div style="background:#fff; border:1px solid ${bc}33; border-radius:14px; padding:16px; cursor:pointer; transition:all 0.2s;" onclick="window.location.href='wizard_souscription.php?id_offre=${r.id_offre}'" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='';this.style.boxShadow='';">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                    <i class="bi ${icon}" style="font-size:18px; color:${bc};"></i>
                    <div>
                        <div style="font-size:14px; font-weight:700; color:#15233C;">${escHtml(r.nom_offre)}</div>
                        <div style="font-size:10px; color:${bc}; font-weight:600; text-transform:uppercase;">${escHtml(r.type_offre)}</div>
                    </div>
                </div>
                <div style="font-size:11px; color:#6b7280; margin-bottom:12px; line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                    ${escHtml(r.description || 'Découvrez cette offre conçue pour vous.')}
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:14px; font-weight:700; color:#15233C;">À partir de ${formatMoney(r.prime_de_base)}</span>
                    <span style="font-size:11px; color:#FF6B1A; font-weight:600;">Voir →</span>
                </div>
            </div>`;
        }).join('');
    }

    function escHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // -------------------------------------------
    //  SINISTRES PANEL  —  data & logic
    // -------------------------------------------
    let spSinistres = [
        { id:1, contrat:'#1023', type:'Accident auto', description:'Accident sur autoroute A1, choc arriére.', date:'2026-04-05', statut:'en_attente', montant:null, photo:'user/img/sample.jpg', commentaires:[], traitements:[{ num:'T1', decision:'Documents reéus, en cours de vérification', date:'02/04/2026', montant:null }] },
        { id:2, contrat:'#998',  type:'Dégét des eaux', description:'Inondation cuisine suite rupture canalisation.', date:'2026-04-01', statut:'rembourse', montant:300, photo:'user/img/sample.jpg', commentaires:[{ texte:'Merci pour le traitement rapide !', date:'05/04/2026' }], traitements:[{ num:'T1', decision:'Documents reéus', date:'02/04/2026', montant:null },{ num:'T2', decision:'Sinistre validé aprés vérification', date:'05/04/2026', montant:300 }] }
    ];

    let spEditingId  = null;
    let spDeletingId = null;
    let spSelectedId = null;
    let spUserContrats = [];

    const SP_TYPE_MAP = {
        'auto':       [
            { val: 'Accident auto',         label: 'Accident auto' },
            { val: 'Vol',                   label: 'Vol de véhicule' },
            { val: 'Bris de glace',         label: 'Bris de glace' },
            { val: 'Incendie',              label: 'Incendie véhicule' },
        ],
        'habitation': [
            { val: 'Incendie',              label: 'Incendie' },
            { val: 'Vol',                   label: 'Cambriolage / Vol' },
            { val: 'Dégét des eaux',        label: 'Dégét des eaux' },
            { val: 'Catastrophe naturelle', label: 'Catastrophe naturelle' },
        ],
        'vie':        [
            { val: 'Décès',                 label: 'Décès' },
            { val: 'Invalidité',            label: 'Invalidité' },
            { val: 'Hospitalisation',       label: 'Hospitalisation' },
        ],
        'sante':      [
            { val: 'Hospitalisation',       label: 'Hospitalisation' },
            { val: 'Accident',              label: 'Accident corporel' },
            { val: 'Maladie',               label: 'Maladie grave' },
        ],
        'protection': [
            { val: 'Vol',                   label: 'Vol / Vandalisme' },
            { val: 'Dégét des eaux',        label: 'Dégét des eaux' },
            { val: 'Incendie',              label: 'Incendie' },
            { val: 'Catastrophe naturelle', label: 'Catastrophe naturelle' },
        ],
        'default':    [
            { val: 'Accident auto',         label: 'Accident auto' },
            { val: 'Incendie',              label: 'Incendie' },
            { val: 'Vol',                   label: 'Vol' },
            { val: 'Dégét des eaux',        label: 'Dégét des eaux' },
        ],
    };

    function spGetContratTypeKey(c) {
        const raw = (c.type_contrat || c.nom_categorie || '').toLowerCase();
        if (raw === 'auto' || raw.includes('auto') || raw.includes('voiture') || raw.includes('vehicule')) return 'auto';
        if (raw === 'habitation' || raw.includes('habitation') || raw.includes('maison') || raw.includes('logement')) return 'habitation';
        if (raw === 'sante' || raw.includes('sante') || raw.includes('santé') || raw.includes('medical')) return 'sante';
        if (raw === 'protection' || raw.includes('protection')) return 'protection';
        if (raw.includes('vie') || raw.includes('deces') || raw.includes('décés')) return 'vie';
        return 'default';
    }

    function spUpdateTypeOptions(contratId) {
        const sel = document.getElementById('sp_type');
        sel.innerHTML = '';
        const c = spUserContrats.find(x => x.id_contrat == contratId);
        const key = c ? spGetContratTypeKey(c) : 'default';
        const types = SP_TYPE_MAP[key] || SP_TYPE_MAP['default'];

        if (!contratId) {
            sel.innerHTML = '<option value="">Choisissez d\'abord un contrat</option>';
            return;
        }

        types.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.val;
            opt.textContent = t.label;
            sel.appendChild(opt);
        });
    }

    function spOnContratChange() {
        const val = document.getElementById('sp_contrat_id').value;
        spUpdateTypeOptions(val);
    }

    const SP_STATUTS = {
        en_attente: { label:'En attente', css:'badge-warning' },
        valide:     { label:'Validé',     css:'badge-info' },
        rembourse:  { label:'Remboursé',  css:'badge-success' },
        refuse:     { label:'Refusé',     css:'badge-danger' },
    };

    function spFmt(d) {
        if (!d) return 'é';
        const [y,m,day] = d.split('-');
        return `${day}/${m}/${y}`;
    }

    // -- Panel open / close --
    function openSinistresPanel(e) {
        if (e) e.preventDefault();
        document.getElementById('sinistresOverlay').classList.add('open');
        document.body.style.overflow = 'hidden';
        spRenderList();
    }
    function closeSinistresPanel() {
        document.getElementById('sinistresOverlay').classList.remove('open');
        document.body.style.overflow = '';
    }
    function handleOverlayClick(e) {
        if (e.target === document.getElementById('sinistresOverlay')) closeSinistresPanel();
    }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeSinistresPanel();
    });

    // -- Render list --
    function spRenderList() {
        const el = document.getElementById('spList');
        document.getElementById('spCount').textContent =
            spSinistres.length + ' sinistre' + (spSinistres.length !== 1 ? 's' : '');

        if (!spSinistres.length) {
            el.innerHTML = `<div style="text-align:center;padding:40px;color:rgba(255,255,255,0.3);font-size:13px;">Aucun sinistre déclaré.</div>`;
            return;
        }
        el.innerHTML = spSinistres.map(s => {
            const st = SP_STATUTS[s.statut] || SP_STATUTS.en_attente;
            const canEdit = s.statut === 'en_attente';
            return `<div class="sinistre-box ${spSelectedId===s.id?'sp-selected':''}"
                        style="cursor:pointer;" onclick="spSelect(${s.id})">
                <div class="sinistre-header">
                    <div class="sinistre-title">${s.type}</div>
                    <span class="badge ${st.css}">${st.label}</span>
                </div>
                <div class="sinistre-meta">Déclaré le ${spFmt(s.date)} — Contrat ${s.contrat}</div>
                ${s.statut==='rembourse'&&s.montant
                    ? `<div class="montant-banner" style="margin-top:8px;margin-bottom:6px;">
                           <i class="bi bi-cash-stack"></i>
                           <div><div class="montant-banner-label">Montant remboursé</div>
                           <div class="montant-banner-amount">${s.montant} DT</div></div>
                       </div>` : ''}
                <div class="sp-action-row">
                    <button class="sp-btn" onclick="event.stopPropagation();spSelect(${s.id})"><i class="bi bi-eye"></i> Voir</button>
                    ${canEdit
                        ? `<button class="sp-btn" onclick="event.stopPropagation();spOpenEdit(${s.id})"><i class="bi bi-pencil"></i> Modifier</button>
                           <button class="sp-btn danger" onclick="event.stopPropagation();spOpenConfirm(${s.id})"><i class="bi bi-trash"></i></button>`
                        : ''}
                </div>
            </div>`;
        }).join('');
    }

    // -- Select / detail --
    function spSelect(id) {
        spSelectedId = id;
        spRenderList();
        const s   = spSinistres.find(x => x.id === id);
        const st  = SP_STATUTS[s.statut] || SP_STATUTS.en_attente;

        const traitHTML = s.traitements.length
            ? s.traitements.map((t, i) => `
                <div class="sp-trait-item ${i===s.traitements.length-1&&t.montant?'final':''}">
                    <div class="sp-trait-header">
                        <div class="sp-trait-step">
                            <span class="sp-trait-num ${t.montant?'success':''}">${t.num}</span>
                            <span class="sp-trait-label">${t.decision}</span>
                        </div>
                        <span class="sp-trait-date">${t.date}</span>
                    </div>
                    ${t.montant ? `<div class="sp-trait-row"><i class="bi bi-currency-exchange"></i> Montant indemnisé : <strong>${t.montant} DT</strong></div>` : ''}
                </div>`).join('')
            : `<p style="font-size:12px;color:rgba(255,255,255,0.4);">Aucun traitement enregistré.</p>`;

        const commHTML = s.commentaires.length
            ? s.commentaires.map(c => `
                <div class="sp-comment-item">
                    <div class="sp-comment-meta">Vous — ${c.date}</div>
                    <div class="sp-comment-text">${c.texte}</div>
                </div>`).join('')
            : `<p style="font-size:12px;color:rgba(255,255,255,0.4);">Aucun commentaire.</p>`;

        document.getElementById('spDetail').innerHTML = `
            <div class="sp-detail">
                <div class="sinistre-header" style="margin-bottom:6px;">
                    <div class="sinistre-title">${s.type}</div>
                    <span class="badge ${st.css}">${st.label}</span>
                </div>
                <div class="sinistre-meta">Contrat ${s.contrat} — ${spFmt(s.date)}</div>

                ${s.statut==='rembourse'&&s.montant
                    ? `<div class="montant-banner"><i class="bi bi-cash-stack"></i>
                          <div><div class="montant-banner-label">Montant indemnisé accordé</div>
                          <div class="montant-banner-amount">${s.montant} DT</div></div></div>` : ''}

                <p style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:12px;">${s.description}</p>
                <img src="${s.photo}" style="width:100%;border-radius:9px;margin-bottom:12px;" alt="photo" onerror="this.style.display='none'">

                <div class="sp-divider-title"><i class="bi bi-clock-history"></i> Suivi</div>
                <div class="sp-timeline">
                    <div class="sp-tl-item">
                        <div class="sp-tl-dot gray"></div>
                        <div><div class="sp-tl-title">Déclaration reçue</div><div class="sp-tl-date">${spFmt(s.date)}</div></div>
                    </div>
                    ${s.statut!=='en_attente'
                        ? `<div class="sp-tl-item"><div class="sp-tl-dot blue"></div><div><div class="sp-tl-title">En cours de traitement</div></div></div>` : ''}
                    ${s.statut==='rembourse'
                        ? `<div class="sp-tl-item"><div class="sp-tl-dot green"></div><div><div class="sp-tl-title">Remboursé</div><div class="sp-tl-desc">${s.montant} DT</div></div></div>` : ''}
                    ${s.statut==='valide'
                        ? `<div class="sp-tl-item"><div class="sp-tl-dot green"></div><div><div class="sp-tl-title">Validé</div></div></div>` : ''}
                    ${s.statut==='refuse'
                        ? `<div class="sp-tl-item"><div class="sp-tl-dot red"></div><div><div class="sp-tl-title">Refusé</div></div></div>` : ''}
                </div>

                <div class="sp-divider-title"><i class="bi bi-journal-text"></i> Traitements</div>
                ${traitHTML}

                <div class="sp-comment-section">
                    <div class="sp-comment-title"><i class="bi bi-chat-left-text"></i> Commentaires</div>
                    <div id="spCommentList_${s.id}">${commHTML}</div>
                    <div class="sp-comment-form">
                        <input class="sp-comment-input" id="spCommentInput_${s.id}" placeholder="Ajouter un commentaire...">
                        <button class="sp-comment-send" onclick="spAddComment(${s.id})"><i class="bi bi-send"></i></button>
                    </div>
                </div>
            </div>`;
    }

    // -- Comments --
    function spAddComment(id) {
        const input = document.getElementById(`spCommentInput_${id}`);
        const texte = input.value.trim();
        if (!texte) return;
        spSinistres.find(x=>x.id===id).commentaires.push({ texte, date: new Date().toLocaleDateString('fr-FR') });
        input.value = '';
        spSelect(id);
        showToast('Commentaire ajouté.', 'success');
    }

    // -- Declare / edit modal --
    async function spOpenDeclareModal() {
        spEditingId = null;
        document.getElementById('spModalTitle').textContent = 'Déclarer un sinistre';
        document.getElementById('sp_desc').value = '';
        document.getElementById('sp_date').value = new Date().toISOString().split('T')[0];
        
        // Load contracts
        const selC = document.getElementById('sp_contrat_id');
        selC.innerHTML = '<option value="">Chargement...</option>';
        
        try {
            const res = await fetch('contrat_list_client.php');
            const json = await res.json();
            if (json.success && json.data) {
                spUserContrats = json.data;
                selC.innerHTML = '<option value="">é Sélectionnez un contrat é</option>';
                json.data.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id_contrat;
                    opt.textContent = (c.numero_contrat || ('CNT-'+c.id_contrat)) + ' (' + (c.type_contrat || 'Contrat') + ')';
                    selC.appendChild(opt);
                });
            } else {
                selC.innerHTML = '<option value="">Aucun contrat trouvé</option>';
            }
        } catch(e) {
            selC.innerHTML = '<option value="">Erreur de chargement</option>';
        }

        spUpdateTypeOptions(null);
        document.getElementById('spModal').classList.add('open');
    }
    async function spOpenEdit(id) {
        spEditingId = id;
        const s = spSinistres.find(x=>x.id===id);
        document.getElementById('spModalTitle').textContent = 'Modifier le sinistre';
        
        // Load contracts first
        const selC = document.getElementById('sp_contrat_id');
        selC.innerHTML = '<option value="">Chargement...</option>';
        try {
            const res = await fetch('contrat_list_client.php');
            const json = await res.json();
            if (json.success && json.data) {
                spUserContrats = json.data;
                selC.innerHTML = '<option value="">é Sélectionnez un contrat é</option>';
                json.data.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id_contrat;
                    opt.textContent = (c.numero_contrat || ('CNT-'+c.id_contrat)) + ' (' + (c.type_contrat || 'Contrat') + ')';
                    selC.appendChild(opt);
                });
                selC.value = s.id_contrat || s.contrat.replace('#','');
                spUpdateTypeOptions(selC.value);
                document.getElementById('sp_type').value = s.type;
            }
        } catch(e) {}

        document.getElementById('sp_desc').value = s.description;
        document.getElementById('sp_date').value = s.date;
        document.getElementById('spModal').classList.add('open');
    }
    function spCloseModal() { document.getElementById('spModal').classList.remove('open'); }

    function spHandleSubmit(e) {
        e.preventDefault();
        const selC    = document.getElementById('sp_contrat_id');
        const contratId = selC.value;
        const contratNum = selC.options[selC.selectedIndex].text.split(' ')[0];
        const type    = document.getElementById('sp_type').value;
        const desc    = document.getElementById('sp_desc').value.trim();
        const date    = document.getElementById('sp_date').value;
        if (!contratId||!type||!desc||!date) { showToast('Remplissez tous les champs.','warning'); return; }

        if (spEditingId) {
            const s = spSinistres.find(x=>x.id===spEditingId);
            Object.assign(s, { id_contrat: contratId, contrat: contratNum, type, description:desc, date });
            showToast('Sinistre modifié.','success');
            if (spSelectedId===spEditingId) spSelect(spEditingId);
        } else {
            const newId = spSinistres.length ? Math.max(...spSinistres.map(x=>x.id))+1 : 1;
            spSinistres.push({ id:newId, id_contrat: contratId, contrat: contratNum, type, description:desc, date,
                               statut:'en_attente', montant:null, photo:'', commentaires:[], traitements:[] });
            showToast('Sinistre déclaré avec succès.','success');
        }
        spCloseModal();
        spRenderList();
    }

    // -- Delete --
    function spOpenConfirm(id) { spDeletingId=id; document.getElementById('spConfirmDel').classList.add('open'); }
    function spCloseConfirm()  { document.getElementById('spConfirmDel').classList.remove('open'); spDeletingId=null; }
    function spConfirmDelete() {
        spSinistres = spSinistres.filter(x=>x.id!==spDeletingId);
        if (spSelectedId===spDeletingId) {
            spSelectedId=null;
            document.getElementById('spDetail').innerHTML=`<div class="sp-detail" style="text-align:center;padding:40px 20px;color:rgba(255,255,255,0.3);"><i class="bi bi-shield" style="font-size:34px;opacity:0.25;display:block;margin-bottom:10px;"></i><span style="font-size:13px;">Sélectionnez un sinistre</span></div>`;
        }
        spCloseConfirm();
        spRenderList();
        showToast('Sinistre supprimé.','danger');
    }
</script>
    <!-- duplicate script removed -->
    <script>
        const ptx_agencies = [
            { id: 1, name: "Protex Tunis - Siège", address: "Av. Habib Bourguiba, Tunis", phone: "+216 71 000 111", lat: 36.8065, lng: 10.1815 },
            { id: 2, name: "Protex Sousse", address: "Av. 14 Janvier, Sousse", phone: "+216 73 222 333", lat: 35.8256, lng: 10.6369 },
            { id: 3, name: "Protex Sfax", address: "Route de Teniour, Sfax", phone: "+216 74 444 555", lat: 34.7406, lng: 10.7603 }
        ];

        let ptx_map;
        const ptx_markers = {};

        function initPtxMap() {
            ptx_map = L.map('ptx-map').setView([35.6759, 10.1005], 7);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(ptx_map);

            const listEl = document.getElementById('ptx-agency-list');
            
            ptx_agencies.forEach(agency => {
                const marker = L.marker([agency.lat, agency.lng]).addTo(ptx_map);
                marker.bindPopup(`<strong>${agency.name}</strong><br>${agency.address}`);
                
                ptx_markers[agency.id] = marker;

                // Add to list
                const item = document.createElement('div');
                item.className = 'agency-item';
                item.id = `agency-item-${agency.id}`;
                item.innerHTML = `
                    <div class="agency-name"><i class="bi bi-geo-alt-fill"></i> ${agency.name}</div>
                    <div class="agency-address">${agency.address}</div>
                    <span class="agency-phone"><i class="bi bi-telephone"></i> ${agency.phone}</span>
                `;
                item.onclick = () => {
                    ptx_map.flyTo([agency.lat, agency.lng], 14);
                    marker.openPopup();
                    highlightAgency(agency.id);
                };
                listEl.appendChild(item);
            });
        }

        function highlightAgency(id) {
            document.querySelectorAll('.agency-item').forEach(el => el.classList.remove('active'));
            const activeItem = document.getElementById(`agency-item-${id}`);
            if (activeItem) {
                activeItem.classList.add('active');
                activeItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        const ptxSearch = document.getElementById('ptx-map-search');
        if (ptxSearch) {
            ptxSearch.addEventListener('input', function(e) {
                const query = e.target.value.toLowerCase();
                document.querySelectorAll('.agency-item').forEach(el => {
                    const text = el.innerText.toLowerCase();
                    el.style.display = text.includes(query) ? 'block' : 'none';
                });
            });
        }

        // Trigger init
        const ptxMapEl = document.getElementById('ptx-map');
        if (ptxMapEl) initPtxMap();
    </script>
    <script>
        // Check for friend requests
        fetch('friends.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.pending && data.pending.length > 0) {
                const dot = document.querySelector('.notif-dot');
                if (dot) {
                    dot.style.display = 'block';
                    dot.style.background = 'var(--ptx-orange)';
                    dot.title = `${data.pending.length} nouvelles invitations`;
                }
            }
        });
    </script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/TourGuide.js"></script>
    <script>
    // ── O6: AI Recommendations Widget ──
    document.addEventListener('DOMContentLoaded', async function loadRecommendations() {
        const grid = document.getElementById('recomm-grid');
        if (!grid) return;
        try {
            const res  = await fetch('api_recommandations.php');
            const data = await res.json();
            if (!data.success || !data.offres || data.offres.length === 0) {
                grid.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:24px; color:#6b7280; font-size:13px;"><i class="bi bi-stars" style="font-size:22px; margin-bottom:8px; display:block;"></i> Aucune recommandation disponible pour le moment.</div>';
                return;
            }
            const iconMap = { 'auto':'bi-car-front', 'sante':'bi-heart-pulse', 'habitation':'bi-house-check', 'vie':'bi-shield-heart' };
            const borderMap = {
                'auto':       '#1a3a7a',
                'sante':      '#059669',
                'habitation': '#FF6B1A',
                'vie':        '#7c3aed'
            };
            grid.innerHTML = data.offres.map(function(o) {
                const t = (o.type_offre || '').toLowerCase();
                const icon = iconMap[t] || 'bi-tags';
                const borderColor = borderMap[t] || '#e5e7eb';
                const raison = o.raison || 'Populaire dans votre catégorie';
                const badge = o.badge || 'Recommandé';
                return `<div style="background:#fff; border:1px solid ${borderColor}33; border-radius:14px; padding:16px; display:flex; flex-direction:column; gap:8px; transition:all 0.2s; cursor:pointer;" onclick="window.location.href='wizard_souscription.php?id_offre=${o.id_offre}'" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)';" onmouseout="this.style.transform=''; this.style.boxShadow='';">
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <i class="bi ${icon}" style="font-size:18px; color:${borderColor};"></i>
                            <span class="recomm-card-text" style="font-size:13px; font-weight:700; color:#15233C;">${o.nom_offre || '—'}</span>
                        </div>
                        <span style="font-size:10px; background:${borderColor}15; color:${borderColor}; padding:3px 8px; border-radius:20px; white-space:nowrap; font-weight:600;">${badge}</span>
                    </div>
                    <div class="recomm-raison" style="font-size:11px; color:#6b7280; line-height:1.5;">${raison}</div>
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-top:4px;">
                        <span class="recomm-card-price" style="font-size:17px; font-weight:900; color:#15233C;">${parseFloat(o.prix_mensuel || 0).toLocaleString('fr-FR')} TND<span style="font-size:11px; font-weight:400; color:#6b7280">/mois</span></span>
                        <span style="font-size:11px; color:#FF6B1A; font-weight:600;">Voir l'offre →</span>
                    </div>
                </div>`;
            }).join('');
        } catch(e) {
            if (grid) grid.innerHTML = '';
        }
    });

    // ── Onboarding Tour ──
    (function initTour() {
        if (localStorage.getItem('ptx_onboarding_done')) return;
        var tour = new TourGuide([
            { selector: '#welcome',            icon: '👋', title: 'Bienvenue sur Protex !',             description: 'Voici votre tableau de bord. Tout est centralisé ici pour gérer vos contrats, sinistres et paiements.', position: 'bottom' },
            { selector: '.stats-grid',         icon: '📊', title: 'Vue d\'ensemble',                    description: 'Consultez vos statistiques clés : contrats actifs, sinistres ouverts, montant total payé et devis en attente.', position: 'bottom' },
            { selector: '#ai-recommandations',  icon: '🤖', title: 'Recommandations intelligentes',     description: 'Découvrez des offres personnalisées selon votre profil, suggérées par notre IA.', position: 'top' },
            { selector: '#contrats-list',       icon: '📄', title: 'Vos contrats',                      description: 'Retrouvez tous vos contrats d\'assurance, leurs détails et leur état en un coup d\'œil.', position: 'top' },
            { selector: '#sinistres-list',      icon: '🆘', title: 'Sinistres récents',                 description: 'Suivez vos déclarations de sinistre et leur statut de traitement.', position: 'left' },
            { selector: '#paiements-list',      icon: '💳', title: 'Derniers paiements',                description: 'Consultez l\'historique de vos paiements et vos échéances à venir.', position: 'right' },
            { selector: '#notifBtn',            icon: '🔔', title: 'Notifications en temps réel',        description: 'Recevez des alertes pour vos échéances, sinistres et messages.', position: 'bottom' },
            { selector: '#avatarBtn',           icon: '👤', title: 'Votre profil & fidélité',            description: 'Accédez à votre profil, modifiez vos informations et cumulez des points fidélité.', position: 'left' },
            { selector: '.nav-link[href*="contrat"]', icon: '📋', title: 'Navigation rapide',           description: 'Utilisez la barre de navigation pour accéder à toutes les sections : contrats, sinistres, paiements, offres et plus encore.', position: 'bottom' },
        ], {
            completeUrl: 'api_onboarding_complete.php'
        });
        // Wait for dashboard data to load, then start
        var waitForData = setInterval(function() {
            if (document.querySelector('#welcome') && document.querySelector('.stats-grid') && document.querySelector('#contrats-list')) {
                clearInterval(waitForData);
                setTimeout(function() { tour.start(); }, 600);
            }
        }, 300);
    })();
</script>
<script>
setTimeout(function() {
    document.querySelectorAll('#recomm-grid .recomm-card-text').forEach(function(el) {
        console.log('recomm-card-text color:', getComputedStyle(el).color, '| text:', el.textContent);
        console.log('  inline style:', el.getAttribute('style'));
        console.log('  parent classes:', el.parentElement.className);
    });
}, 2000);
</script>
</body>
</html>



