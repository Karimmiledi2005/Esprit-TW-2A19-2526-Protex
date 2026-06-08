<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 3) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
$__base = defined('BASE_URL') ? BASE_URL : '';
?>
<script>const BASE_URL_PHP = '<?= $__base ?>';</script>
<!DOCTYPE html>
<html lang="fr">
<head>
    
    <meta charset="utf-8">
    <title>Gestion Devis — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- ========================================================= -->
    <!-- FONTS ET ICONES                                           -->
    <!-- ========================================================= -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- ========================================================= -->
    <!-- CSS PROJET                                                -->
    <!-- ========================================================= -->
    <link rel="stylesheet" href="<?= $__base ?>/view/BackOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= $__base ?>/view/BackOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= $__base ?>/view/BackOffice/assets/css/layout.css">
    <link rel="stylesheet" href="<?= $__base ?>/view/BackOffice/assets/css/admin-users.css">

    <!-- ========================================================= -->
    <!-- STYLE LOCAL PAGE DEVIS                                    -->
    <!-- ========================================================= -->
    <style>
        .sidebar-logo img {
            border-radius: 10px;
            object-fit: cover;
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .devis-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.04);
            color: #ffffff;
            white-space: nowrap;
        }

        .devis-type-auto {
            background: rgba(0, 194, 255, 0.12);
            color: #8fe9ff;
            border-color: rgba(0, 194, 255, 0.25);
        }

        .devis-type-habitation {
            background: rgba(255, 153, 0, 0.12);
            color: #ffd28a;
            border-color: rgba(255, 153, 0, 0.25);
        }

        .devis-type-sante {
            background: rgba(0, 214, 143, 0.12);
            color: #94ffd8;
            border-color: rgba(0, 214, 143, 0.25);
        }

        .devis-status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-en_attente {
            background: rgba(255, 193, 7, 0.12);
            border: 1px solid rgba(255, 193, 7, 0.24);
            color: #ffd66e;
        }

        .status-en_cours {
            background: rgba(13, 202, 240, 0.12);
            border: 1px solid rgba(13, 202, 240, 0.24);
            color: #8eeaff;
        }

        .status-accepte {
            background: rgba(25, 135, 84, 0.12);
            border: 1px solid rgba(25, 135, 84, 0.24);
            color: #90f1bc;
        }

        .status-refuse {
            background: rgba(220, 53, 69, 0.12);
            border: 1px solid rgba(220, 53, 69, 0.24);
            color: #ff9cab;
        }

        .status-expire {
            background: rgba(108, 117, 125, 0.16);
            border: 1px solid rgba(108, 117, 125, 0.24);
            color: #d0d5dd;
        }

        .devis-client {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .devis-client-avatar {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-weight: 800;
            color: #fff;
            background: linear-gradient(135deg, rgba(255,107,26,0.95), rgba(255,140,66,0.85));
            box-shadow: 0 10px 24px rgba(255, 107, 26, 0.2);
            flex-shrink: 0;
        }

        .devis-client-name {
            font-weight: 700;
            color: #ffffff;
            line-height: 1.2;
        }

        .devis-client-meta {
            color: var(--text-secondary);
            font-size: 12px;
            margin-top: 2px;
        }

        .devis-amount {
            font-weight: 800;
            color: #fff;
            white-space: nowrap;
        }

        .devis-reference {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: var(--text-secondary);
            white-space: nowrap;
        }

        .devis-toolbar-grid {
            display: grid;
            grid-template-columns: 1.3fr 1fr 1fr 1fr auto;
            gap: 12px;
            align-items: center;
        }

        .devis-kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 18px;
            margin-bottom: 22px;
        }

        .devis-kpi-card {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
            border: 1px solid rgba(255,255,255,0.08);
            padding: 18px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.14);
        }

        .devis-kpi-card::after {
            content: "";
            position: absolute;
            inset: auto -25px -25px auto;
            width: 85px;
            height: 85px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.12), transparent 68%);
            pointer-events: none;
        }

        .devis-kpi-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .devis-kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            font-size: 20px;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .devis-kpi-blue .devis-kpi-icon {
            background: linear-gradient(135deg, rgba(0,194,255,0.85), rgba(16,85,255,0.75));
        }

        .devis-kpi-gold .devis-kpi-icon {
            background: linear-gradient(135deg, rgba(255,166,0,0.9), rgba(255,107,26,0.85));
        }

        .devis-kpi-green .devis-kpi-icon {
            background: linear-gradient(135deg, rgba(0,214,143,0.9), rgba(0,166,126,0.8));
        }

        .devis-kpi-red .devis-kpi-icon {
            background: linear-gradient(135deg, rgba(255,92,92,0.9), rgba(220,53,69,0.85));
        }

        .devis-kpi-purple .devis-kpi-icon {
            background: linear-gradient(135deg, rgba(137,100,255,0.9), rgba(88,80,236,0.85));
        }

        .devis-kpi-value {
            color: #ffffff;
            font-size: 28px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 6px;
        }

        .devis-kpi-label {
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 600;
        }

        .devis-kpi-trend {
            margin-top: 10px;
            font-size: 12px;
            color: var(--text-secondary);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .devis-card-highlight {
            margin-bottom: 18px;
            padding: 18px 20px;
            border-radius: 22px;
            border: 1px solid rgba(255,255,255,0.08);
            background: linear-gradient(135deg, rgba(255,107,26,0.12), rgba(255,255,255,0.03));
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .devis-card-highlight-title {
            color: #fff;
            font-weight: 800;
            font-size: 15px;
            margin-bottom: 6px;
        }

        .devis-card-highlight-text {
            color: var(--text-secondary);
            font-size: 13px;
        }

        .devis-mini-stats {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .devis-mini-pill {
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
        }

        .devis-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-top: 20px;
        }

        .devis-detail-card {
            border-radius: 22px;
            padding: 18px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.03);
        }

        .devis-detail-title {
            font-size: 14px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .devis-detail-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .devis-detail-item {
            padding: 12px;
            border-radius: 16px;
            background: rgba(255,255,255,0.035);
            border: 1px solid rgba(255,255,255,0.06);
        }

        .devis-detail-label {
            color: var(--text-secondary);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 6px;
        }

        .devis-detail-value {
            color: #fff;
            font-size: 14px;
            font-weight: 700;
        }

        .devis-form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .devis-form-grid .full {
            grid-column: 1 / -1;
        }

        .devis-hidden {
            display: none !important;
        }

        .devis-section-card {
            margin-top: 18px;
            padding: 18px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.025);
        }

        .devis-section-title {
            color: #fff;
            font-weight: 800;
            font-size: 14px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .devis-empty {
            text-align: center;
            padding: 48px 18px;
            color: var(--text-secondary);
        }

        .devis-empty i {
            display: block;
            font-size: 30px;
            margin-bottom: 10px;
            color: rgba(255,255,255,0.55);
        }

        .devis-refuse-box,
        .devis-reponse-box {
            width: 100%;
            min-height: 110px;
            resize: vertical;
        }

        .devis-summary-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .devis-summary-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.04);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
        }

        .devis-modal-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .devis-modal-tab {
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.035);
            color: var(--text-secondary);
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .devis-modal-tab.active {
            color: #fff;
            background: rgba(255,107,26,0.12);
            border-color: rgba(255,107,26,0.25);
        }

        .devis-top-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .devis-soft-tag {
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.05);
            color: var(--text-secondary);
            border: 1px solid rgba(255,255,255,0.07);
            font-size: 12px;
            font-weight: 700;
        }

        .devis-inline-badges {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 6px;
        }

        .devis-montant-input-wrap {
            position: relative;
        }

        .devis-montant-input-wrap .currency {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-weight: 700;
            pointer-events: none;
        }

        .devis-montant-input-wrap input {
            padding-right: 54px;
        }

        .devis-header-flex {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .devis-view-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
            padding-bottom: 18px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .devis-view-avatar {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            font-size: 18px;
            font-weight: 800;
            color: #fff;
            background: linear-gradient(135deg, rgba(255,107,26,0.95), rgba(255,140,66,0.85));
            box-shadow: 0 10px 24px rgba(255, 107, 26, 0.2);
            flex-shrink: 0;
        }

        .devis-filter-pill-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .devis-filter-pill {
            padding: 9px 12px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.04);
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all .2s ease;
        }

        .devis-filter-pill.active {
            background: rgba(255,107,26,0.14);
            border-color: rgba(255,107,26,0.22);
            color: #fff;
        }

        @media (max-width: 1320px) {
            .devis-kpi-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 1080px) {
            .devis-toolbar-grid {
                grid-template-columns: 1fr 1fr;
            }

            .devis-detail-grid,
            .devis-form-grid,
            .devis-detail-list {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 880px) {
            .devis-kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .devis-kpi-grid,
            .devis-toolbar-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <script>
// Charge la sidebar depuis PHP via fetch
fetch(BASE_URL_PHP + '/view/BackOffice/assets/includes/sidebar.php')
    .then(r => r.text())
    .then(html => {
        document.querySelector('.sidebar')?.outerHTML === undefined
            ? document.querySelector('.layout').insertAdjacentHTML('afterbegin', html)
            : document.querySelector('.sidebar').outerHTML = html;
    });
</script>

<!-- ========================================================= -->
<!-- FOND VISUEL                                               -->
<!-- ========================================================= -->
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<!-- ========================================================= -->
<!-- LAYOUT PRINCIPAL                                           -->
<!-- ========================================================= -->
<div class="layout">

    <!-- ===================================================== -->
    <!-- SIDEBAR                                               -->
    <!-- ===================================================== -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="<?= $__base ?>/view/FrontOffice/logo.png" alt="logo" width="40" height="40">
            <div>
                <div class="logo-text">Protex</div>
                <div class="logo-sub">Back-Office</div>
            </div>
        </div>

        <div class="sidebar-user">
            <div class="user-avatar">KM</div>
            <div>
                <div class="user-name">Karim Miledi</div>
                <span class="user-role">Administrateur</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">Principal</div>
            <a class="nav-item" href="javascript:void(0)" onclick="window.location.href=BASE_URL_PHP+'/controller/DashboardController.php'" id="navDashboard">
                <i class="bi bi-grid-1x2"></i> Tableau de bord
            </a>

            <div class="nav-section">Gestion</div>
            <a class="nav-item" href="javascript:void(0)" onclick="window.location.href=BASE_URL_PHP+'/view/BackOffice/admin-users.php'" id="navUsers">
                <i class="bi bi-people"></i> Utilisateurs
                <span class="nav-badge accent">24</span>
            </a>

            <a class="nav-item" href="admin-contrats.html" id="navContrats">
                <i class="bi bi-file-earmark-text"></i> Contrats
            </a>

            <a class="nav-item" href="admin-sinistres.html" id="navSinistres">
                <i class="bi bi-shield-exclamation"></i> Sinistres
            </a>

            <a class="nav-item" href="javascript:void(0)" onclick="window.location.href=BASE_URL_PHP+'/view/BackOffice/paiements.php'" id="navPaiements">
                <i class="bi bi-credit-card"></i> Paiements
            </a>

            <a class="nav-item" href="admin-reclamations.html" id="navReclamations">
                <i class="bi bi-chat-dots"></i> Réclamations
            </a>

            <a class="nav-item" href="admin-agences.php" id="navAgences">
                <i class="bi bi-geo-alt"></i> Agences
            </a>

            <div class="nav-section">Mes modules</div>
            <a class="nav-item" href="javascript:void(0)" onclick="window.location.href=BASE_URL_PHP+'/view/BackOffice/offres.php'" id="navOffres">
                <i class="bi bi-tags"></i> Offres
            </a>

            <a class="nav-item" href="<?= $__base ?>/controller/DevisController.php?action=index" id="navDevis">
                <i class="bi bi-file-earmark-text"></i> Devis
            </a>

            <div class="nav-section">Compte</div>
            <a class="nav-item" href="monprofile.html" id="navProfile">
                <i class="bi bi-person-gear"></i> Mon profil
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="connexion.html" class="logout-btn">
                <i class="bi bi-box-arrow-left"></i> Se déconnecter
            </a>
        </div>
    </aside>

    <!-- ===================================================== -->
    <!-- MAIN                                                  -->
    <!-- ===================================================== -->
    <main class="main">
        <div class="topbar">
            <div>
                <div class="topbar-title">Suivi des devis clients</div>
                <div class="topbar-sub" id="topbarDate"></div>
            </div>
            <div class="topbar-actions">
                <a href="#" class="topbar-btn" title="Notifications">
                    <i class="bi bi-bell"></i>
                    <span class="notif-dot"></span>
                </a>
                <a href="#" class="topbar-btn" title="Aide">
                    <i class="bi bi-question-circle"></i>
                </a>
            </div>
        </div>

        <div class="content">
            <!-- ============================================= -->
            <!-- HEADER                                         -->
            <!-- ============================================= -->
            <div class="page-header-bar">
                <div class="devis-header-flex">
                    <div>
                        <div class="page-title">Devis</div>
                        <div class="page-breadcrumb">
                            <i class="bi bi-house"></i>
                            <a href="javascript:void(0)" onclick="window.location.href=BASE_URL_PHP+'/controller/DashboardController.php'">Accueil</a>
                            <i class="bi bi-chevron-right" style="font-size:10px"></i>
                            <span>Devis</span>
                        </div>
                    </div>

                    <div class="devis-top-actions">
                        <span class="devis-soft-tag"><i class="bi bi-building-check"></i> Assurance en ligne</span>
                        <button class="btn btn-outline" onclick="refreshDevis()">
                            <i class="bi bi-arrow-clockwise"></i> Actualiser
                        </button>
                        <button class="btn btn-outline">
                            <i class="bi bi-eye"></i> Consultation 
                        </button>
                    </div>
                </div>
            </div>

            <!-- ============================================= -->
            <!-- KPI CARDS                                      -->
            <!-- ============================================= -->
            <div class="devis-kpi-grid">
                <div class="devis-kpi-card devis-kpi-blue">
                    <div class="devis-kpi-top">
                        <div>
                            <div class="devis-kpi-value" id="kpiTotal">0</div>
                            <div class="devis-kpi-label">Total devis</div>
                        </div>
                        <div class="devis-kpi-icon">
                            <i class="bi bi-files"></i>
                        </div>
                    </div>
                    <div class="devis-kpi-trend"><i class="bi bi-bar-chart-line"></i> Vue globale des demandes</div>
                </div>

                <div class="devis-kpi-card devis-kpi-gold">
                    <div class="devis-kpi-top">
                        <div>
                            <div class="devis-kpi-value" id="kpiAttente">0</div>
                            <div class="devis-kpi-label">En attente</div>
                        </div>
                        <div class="devis-kpi-icon">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                    <div class="devis-kpi-trend"><i class="bi bi-clock-history"></i> Demandes à traiter</div>
                </div>

                <div class="devis-kpi-card devis-kpi-green">
                    <div class="devis-kpi-top">
                        <div>
                            <div class="devis-kpi-value" id="kpiAcceptes">0</div>
                            <div class="devis-kpi-label">Acceptés</div>
                        </div>
                        <div class="devis-kpi-icon">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                    </div>
                    <div class="devis-kpi-trend"><i class="bi bi-check-circle"></i> Conversion validée</div>
                </div>

                <div class="devis-kpi-card devis-kpi-red">
                    <div class="devis-kpi-top">
                        <div>
                            <div class="devis-kpi-value" id="kpiRefuses">0</div>
                            <div class="devis-kpi-label">Refusés</div>
                        </div>
                        <div class="devis-kpi-icon">
                            <i class="bi bi-x-octagon"></i>
                        </div>
                    </div>
                    <div class="devis-kpi-trend"><i class="bi bi-exclamation-circle"></i> Rejetés ou non retenus</div>
                </div>

                <div class="devis-kpi-card devis-kpi-purple">
                    <div class="devis-kpi-top">
                        <div>
                            <div class="devis-kpi-value" id="kpiMontant">0 DT</div>
                            <div class="devis-kpi-label">Montant moyen</div>
                        </div>
                        <div class="devis-kpi-icon">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                    <div class="devis-kpi-trend"><i class="bi bi-coin"></i> Estimation moyenne actuelle</div>
                </div>
            </div>

            <!-- ============================================= -->
            <!-- HIGHLIGHT BAR                                   -->
            <!-- ============================================= -->
            <div class="devis-card-highlight">
                <div>
                    <div class="devis-card-highlight-title">Pilotage des demandes de devis</div>
                    <div class="devis-card-highlight-text">
                        Suivez les demandes clients, consultez les détails spécifiques par type d’assurance, mettez à jour le montant estimé et gérez le cycle de vie de chaque devis.
                    </div>
                </div>
                <div class="devis-mini-stats">
                    <div class="devis-mini-pill"><i class="bi bi-car-front"></i> Auto</div>
                    <div class="devis-mini-pill"><i class="bi bi-house-door"></i> Habitation</div>
                    <div class="devis-mini-pill"><i class="bi bi-heart-pulse"></i> Santé</div>
                </div>
            </div>

            <!-- ============================================= -->
            <!-- TABLE CARD                                      -->
            <!-- ============================================= -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="bi bi-table"></i> Liste des devis
                    </div>
                    <button class="btn btn-outline btn-sm" onclick="exportDevis()">
                        <i class="bi bi-download"></i> Exporter
                    </button>
                </div>

                <div style="padding: 16px 24px; border-bottom: 1px solid var(--glass-border);">
                    <div class="devis-toolbar-grid">
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="text" id="searchInput" placeholder="Rechercher par client, email, référence...">
                        </div>

                        <select class="filter-select" id="filterType">
                            <option value="">Tous les types</option>
                            <option value="auto">Auto</option>
                            <option value="habitation">Habitation</option>
                            <option value="sante">Santé</option>
                        </select>

                        <select class="filter-select" id="filterStatut">
                            <option value="">Tous les statuts</option>
                            <option value="en_attente">En attente</option>
                            <option value="en_cours">En cours</option>
                            <option value="accepte">Accepté</option>
                            <option value="refuse">Refusé</option>
                            <option value="expire">Expiré</option>
                        </select>

                        <select class="filter-select" id="filterOffre">
                            <option value="">Toutes les offres</option>
                        </select>

                        <button class="btn btn-outline btn-sm" onclick="resetFilters()">
                            <i class="bi bi-x-circle"></i> Réinitialiser
                        </button>
                    </div>

                    <div class="devis-summary-bar">
                        <div class="devis-summary-chip"><i class="bi bi-funnel"></i> Filtres actifs dynamiques</div>
                        <div class="devis-summary-chip"><i class="bi bi-journal-text"></i> Réponses admin visibles</div>
                        <div class="devis-summary-chip"><i class="bi bi-arrow-left-right"></i> Lecture multi-types</div>
                    </div>
                </div>

                <div style="padding: 0 24px 18px 24px; border-bottom: 1px solid var(--glass-border);">
                    <div class="devis-filter-pill-group" id="quickFilters">
                        <button class="devis-filter-pill active" data-quick="all">Tous</button>
                        <button class="devis-filter-pill" data-quick="en_attente">À traiter</button>
                        <button class="devis-filter-pill" data-quick="accepte">Acceptés</button>
                        <button class="devis-filter-pill" data-quick="refuse">Refusés</button>
                        <button class="devis-filter-pill" data-quick="auto">Auto</button>
                        <button class="devis-filter-pill" data-quick="habitation">Habitation</button>
                        <button class="devis-filter-pill" data-quick="sante">Santé</button>
                    </div>
                </div>

                <div class="table-wrap">
                    <table id="devisTable">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Référence</th>
                                <th>Type</th>
                                <th>Offre</th>
                                <th>Statut</th>
                                <th>Montant</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="devisBody"></tbody>
                    </table>
                </div>

                <div class="pagination">
                    <div class="pagination-info" id="paginationInfo"></div>
                    <div class="pagination-btns" id="paginationBtns"></div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- ========================================================= -->
<!-- MODAL AJOUT / MODIFICATION                                 -->
<!-- ========================================================= -->
<div class="modal-overlay" id="modalAddEdit">
    <div class="modal" style="max-width: 980px;">
        <div class="modal-header">
            <div class="modal-title" id="modalAddTitle">
                <i class="bi bi-file-earmark-plus"></i> Ajouter un devis
            </div>
            <button class="modal-close" onclick="closeModal('modalAddEdit')">
                <i class="bi bi-x"></i>
            </button>
        </div>

        <div class="devis-modal-tabs">
            <button class="devis-modal-tab active" type="button" data-tab="general" onclick="switchModalTab('general')">Informations générales</button>
            <button class="devis-modal-tab" type="button" data-tab="specific" onclick="switchModalTab('specific')">Détails du type</button>
            <button class="devis-modal-tab" type="button" data-tab="admin" onclick="switchModalTab('admin')">Traitement admin</button>
        </div>

        <div id="tab-general" class="devis-tab-panel">
            <div class="devis-form-grid">
                <div class="form-group">
                    <label>Nom *</label>
                    <input type="text" class="form-control" id="fNom" placeholder="Ben Salah">
                    <div class="form-error" id="errNom">Champ requis</div>
                </div>

                <div class="form-group">
                    <label>Prénom *</label>
                    <input type="text" class="form-control" id="fPrenom" placeholder="Ali">
                    <div class="form-error" id="errPrenom">Champ requis</div>
                </div>

                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" class="form-control" id="fEmail" placeholder="ali@gmail.com">
                    <div class="form-error" id="errEmail">Email invalide</div>
                </div>

                <div class="form-group">
                    <label>Téléphone *</label>
                    <input type="tel" class="form-control" id="fTelephone" placeholder="+216 XX XXX XXX">
                    <div class="form-error" id="errTelephone">Téléphone requis</div>
                </div>

                <div class="form-group">
                    <label>Type d’assurance *</label>
                    <select class="form-control" id="fType" onchange="handleTypeChange()">
                        <option value="auto">Auto</option>
                        <option value="habitation">Habitation</option>
                        <option value="sante">Santé</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Offre *</label>
                    <select class="form-control" id="fOffre"></select>
                </div>
            </div>
        </div>

        <div id="tab-specific" class="devis-tab-panel devis-hidden">
            <!-- AUTO -->
            <div id="section-auto" class="devis-section-card">
                <div class="devis-section-title">
                    <i class="bi bi-car-front"></i> Informations véhicule
                </div>
                <div class="devis-form-grid">
                    <div class="form-group">
                        <label>Marque *</label>
                        <input type="text" class="form-control" id="fAutoMarque" placeholder="Peugeot">
                    </div>
                    <div class="form-group">
                        <label>Modèle *</label>
                        <input type="text" class="form-control" id="fAutoModele" placeholder="208">
                    </div>
                    <div class="form-group">
                        <label>Année *</label>
                        <input type="number" class="form-control" id="fAutoAnnee" placeholder="2021">
                    </div>
                    <div class="form-group">
                        <label>Immatriculation</label>
                        <input type="text" class="form-control" id="fAutoImmatriculation" placeholder="123 TUN 456">
                    </div>
                    <div class="form-group">
                        <label>Puissance fiscale</label>
                        <input type="number" class="form-control" id="fAutoPuissance" placeholder="5">
                    </div>
                    <div class="form-group">
                        <label>Carburant</label>
                        <select class="form-control" id="fAutoCarburant">
                            <option value="Essence">Essence</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Hybride">Hybride</option>
                            <option value="Électrique">Électrique</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Valeur véhicule</label>
                        <input type="number" class="form-control" id="fAutoValeur" placeholder="45000">
                    </div>
                    <div class="form-group">
                        <label>Usage véhicule</label>
                        <select class="form-control" id="fAutoUsage">
                            <option value="Personnel">Personnel</option>
                            <option value="Professionnel">Professionnel</option>
                            <option value="Mixte">Mixte</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- HABITATION -->
            <div id="section-habitation" class="devis-section-card devis-hidden">
                <div class="devis-section-title">
                    <i class="bi bi-house-door"></i> Informations habitation
                </div>
                <div class="devis-form-grid">
                    <div class="form-group">
                        <label>Type habitation *</label>
                        <select class="form-control" id="fHabType">
                            <option value="Appartement">Appartement</option>
                            <option value="Maison">Maison</option>
                            <option value="Villa">Villa</option>
                            <option value="Studio">Studio</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Adresse *</label>
                        <input type="text" class="form-control" id="fHabAdresse" placeholder="Adresse complète du bien">
                    </div>
                    <div class="form-group">
                        <label>Superficie (m²)</label>
                        <input type="number" class="form-control" id="fHabSuperficie" placeholder="120">
                    </div>
                    <div class="form-group">
                        <label>Nombre de pièces</label>
                        <input type="number" class="form-control" id="fHabPieces" placeholder="4">
                    </div>
                    <div class="form-group">
                        <label>Valeur du bien</label>
                        <input type="number" class="form-control" id="fHabValeur" placeholder="180000">
                    </div>
                    <div class="form-group">
                        <label>Statut occupation</label>
                        <select class="form-control" id="fHabOccupation">
                            <option value="Propriétaire">Propriétaire</option>
                            <option value="Locataire">Locataire</option>
                            <option value="Occupant">Occupant</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- SANTE -->
            <div id="section-sante" class="devis-section-card devis-hidden">
                <div class="devis-section-title">
                    <i class="bi bi-heart-pulse"></i> Informations santé
                </div>
                <div class="devis-form-grid">
                    <div class="form-group">
                        <label>Âge *</label>
                        <input type="number" class="form-control" id="fSanteAge" placeholder="35">
                    </div>
                    <div class="form-group">
                        <label>Situation familiale</label>
                        <select class="form-control" id="fSanteSituation">
                            <option value="Célibataire">Célibataire</option>
                            <option value="Marié(e)">Marié(e)</option>
                            <option value="Divorcé(e)">Divorcé(e)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nombre bénéficiaires</label>
                        <input type="number" class="form-control" id="fSanteBeneficiaires" placeholder="1">
                    </div>
                    <div class="form-group">
                        <label>Profession</label>
                        <input type="text" class="form-control" id="fSanteProfession" placeholder="Ingénieur">
                    </div>
                    <div class="form-group full">
                        <label>Couverture souhaitée</label>
                        <input type="text" class="form-control" id="fSanteCouverture" placeholder="Hospitalisation + consultations + dentaire">
                    </div>
                    <div class="form-group full">
                        <label>Antécédents médicaux</label>
                        <textarea class="form-control" id="fSanteAntecedents" rows="4" placeholder="Informations médicales importantes"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div id="tab-admin" class="devis-tab-panel devis-hidden">
            <div class="devis-form-grid">
                <div class="form-group">
                    <label>Statut</label>
                    <select class="form-control" id="fStatut">
                        <option value="en_attente">En attente</option>
                        <option value="en_cours">En cours</option>
                        <option value="accepte">Accepté</option>
                        <option value="refuse">Refusé</option>
                        <option value="expire">Expiré</option>
                    </select>
                </div>

                <div class="form-group devis-montant-input-wrap">
                    <label>Montant estimé</label>
                    <input type="number" step="0.001" class="form-control" id="fMontant" placeholder="0.000">
                    <span class="currency">DT</span>
                </div>

                <div class="form-group full">
                    <label>Réponse admin</label>
                    <textarea class="form-control devis-reponse-box" id="fReponseAdmin" placeholder="Réponse envoyée au client"></textarea>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('modalAddEdit')">Annuler</button>
            <button class="btn btn-primary" id="btnSaveDevis" onclick="saveDevis()">
                <i class="bi bi-save"></i> Enregistrer
            </button>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- MODAL DETAIL                                               -->
<!-- ========================================================= -->
<div class="modal-overlay" id="modalView">
    <div class="modal" style="max-width: 1000px;">
        <div class="modal-header">
            <div class="modal-title"><i class="bi bi-eye"></i> Détail du devis</div>
            <button class="modal-close" onclick="closeModal('modalView')"><i class="bi bi-x"></i></button>
        </div>
        <div id="modalViewBody"></div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('modalView')">Fermer</button>
            <button class="btn btn-primary" id="btnEditFromView" onclick="editFromView()">
                <i class="bi bi-pencil"></i> Modifier
            </button>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- MODAL SUPPRESSION                                           -->
<!-- ========================================================= -->
<div class="modal-overlay delete-modal" id="modalDelete">
    <div class="modal" style="text-align:center">
        <div class="delete-icon"><i class="bi bi-trash3"></i></div>
        <div class="delete-title">Supprimer le devis</div>
        <div class="delete-msg" id="deleteMsg"></div>
        <div class="modal-footer" style="justify-content:center; margin-top:28px">
            <button class="btn btn-outline" onclick="closeModal('modalDelete')">Annuler</button>
            <button class="btn btn-danger" id="btnConfirmDelete" onclick="confirmDelete()">
                <i class="bi bi-trash3"></i> Supprimer définitivement
            </button>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- MAIN JS DU PROJET                                           -->
<!-- ========================================================= -->
<script src="<?= $__base ?>/view/BackOffice/assets/js/main.js"></script>

<!-- ========================================================= -->
<!-- SCRIPT PAGE DEVIS                                           -->
<!-- ========================================================= -->
<<script>
/* ============================================================
   ÉTAT GLOBAL
   ============================================================ */
let devis = [];
let offresDB = [];
let currentPage = 1;
const perPage = 6;
let editingId = null;
let deletingId = null;
let viewingId = null;
let currentQuickFilter = 'all';
let currentModalTab = 'general';

/* ============================================================
   DATE TOPBAR
   ============================================================ */
document.getElementById('topbarDate').textContent =
    new Date().toLocaleDateString('fr-FR', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    });

/* ============================================================
   CHARGEMENT DEPUIS L'API
   ============================================================ */
async function loadOffres() {
    try {
        const r = await fetch(' + BASE_URL_PHP + '/api.php?action=offres');
        const raw = await r.json();
        offresDB = raw.map(o => ({
            id:       Number(o.id_offre),
            nom:      o.nom_offre,
            type:     o.type_offre,
            prixBase: Number(o.prix_annuel)
        }));
    } catch (e) {
        showToast('Erreur chargement offres', 'danger');
    }
}

async function loadDevis() {
    try {
        const r = await fetch(' + BASE_URL_PHP + '/api.php?action=devis_liste');
        const raw = await r.json();
        devis = raw.map(d => ({
            id:           Number(d.id_devis),
            reference:    'DEV-2026-' + String(d.id_devis).padStart(4, '0'),
            nom:          d.nom,
            prenom:       d.prenom,
            email:        d.email,
            telephone:    d.telephone,
            type:         d.type_assurance,
            idOffre:      Number(d.id_offre),
            offreNom:     d.nom_offre || '—',
            statut:       d.statut,
            montant:      d.montant_estime,
            date:         (d.date_demande || '').split(' ')[0],
            reponseAdmin: d.reponse_admin || '',
            details:      {}
        }));
    } catch (e) {
        showToast('Erreur chargement devis', 'danger');
    }
}

async function init() {
    await Promise.all([loadOffres(), loadDevis()]);
    populateOffreSelects();
    syncActiveNav();
    resetForm();
    render();
}

/* ============================================================
   HELPERS
   ============================================================ */
function escapeHtml(text) {
    return String(text ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function initials(item) {
    return `${(item.prenom||'').charAt(0)}${(item.nom||'').charAt(0)}`.toUpperCase();
}
function formatDate(dateString) {
    const date = new Date(dateString);
    if (Number.isNaN(date.getTime())) return '—';
    return date.toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric' });
}
function formatMoney(value) {
    if (value === null || value === undefined || value === '') return '—';
    const n = Number(value);
    if (Number.isNaN(n)) return '—';
    return `${n.toFixed(3)} DT`;
}
function statusIcon(s) {
    return {en_attente:'hourglass-split',en_cours:'arrow-repeat',accepte:'check-circle',refuse:'x-circle',expire:'clock-history'}[s]||'circle';
}
function statusLabel(s) {
    return {en_attente:'En attente',en_cours:'En cours',accepte:'Accepté',refuse:'Refusé',expire:'Expiré'}[s]||s;
}
function typeIcon(t) {
    return {auto:'car-front',habitation:'house-door',sante:'heart-pulse'}[t]||'file-earmark';
}
function typeLabel(t) {
    return {auto:'Auto',habitation:'Habitation',sante:'Santé'}[t]||t;
}
function getOffreById(id) {
    return offresDB.find(o => o.id === Number(id)) || null;
}

/* ============================================================
   SELECTS OFFRES
   ============================================================ */
function populateOffreSelects() {
    const filterOffre = document.getElementById('filterOffre');
    const fOffre      = document.getElementById('fOffre');
    filterOffre.innerHTML = '<option value="">Toutes les offres</option>';
    fOffre.innerHTML = '';
    offresDB.forEach(o => {
        const opt1 = document.createElement('option');
        opt1.value = String(o.id);
        opt1.textContent = `${o.nom} (${typeLabel(o.type)})`;
        filterOffre.appendChild(opt1);
        const opt2 = opt1.cloneNode(true);
        fOffre.appendChild(opt2);
    });
    filterOffersByType(document.getElementById('fType').value);
}

function filterOffersByType(type) {
    const select  = document.getElementById('fOffre');
    const current = select.value;
    const filtered = offresDB.filter(o => o.type === type);
    select.innerHTML = '';
    filtered.forEach(o => {
        const opt = document.createElement('option');
        opt.value = String(o.id);
        opt.textContent = `${o.nom} — ${formatMoney(o.prixBase)}`;
        select.appendChild(opt);
    });
    if (filtered.some(o => String(o.id) === current)) select.value = current;
}

/* ============================================================
   KPI
   ============================================================ */
function updateStats() {
    const total    = devis.length;
    const attente  = devis.filter(d => d.statut === 'en_attente').length;
    const acceptes = devis.filter(d => d.statut === 'accepte').length;
    const refuses  = devis.filter(d => d.statut === 'refuse').length;
    const moy      = total ? devis.reduce((s,d)=>s+Number(d.montant||0),0)/total : 0;
    document.getElementById('kpiTotal').textContent    = total;
    document.getElementById('kpiAttente').textContent  = attente;
    document.getElementById('kpiAcceptes').textContent = acceptes;
    document.getElementById('kpiRefuses').textContent  = refuses;
    document.getElementById('kpiMontant').textContent  = `${Math.round(moy)} DT`;
}

/* ============================================================
   FILTRAGE
   ============================================================ */
function getFiltered() {
    const search  = document.getElementById('searchInput').value.trim().toLowerCase();
    const type    = document.getElementById('filterType').value;
    const statut  = document.getElementById('filterStatut').value;
    const offreId = document.getElementById('filterOffre').value;
    return devis.filter(d => {
        const text = [d.reference,d.nom,d.prenom,d.email,d.telephone,d.offreNom,d.type,d.statut].join(' ').toLowerCase();
        const quickPass = currentQuickFilter === 'all' || d.statut === currentQuickFilter || d.type === currentQuickFilter;
        return (!search || text.includes(search)) && (!type || d.type === type)
            && (!statut || d.statut === statut) && (!offreId || Number(offreId) === Number(d.idOffre)) && quickPass;
    });
}

function resetFilters() {
    document.getElementById('searchInput').value  = '';
    document.getElementById('filterType').value   = '';
    document.getElementById('filterStatut').value = '';
    document.getElementById('filterOffre').value  = '';
    currentQuickFilter = 'all';
    document.querySelectorAll('.devis-filter-pill').forEach(b => b.classList.toggle('active', b.dataset.quick === 'all'));
    currentPage = 1;
    render();
}

/* ============================================================
   RENDER TABLE
   ============================================================ */
function render() {
    const filtered = getFiltered();
    const total    = filtered.length;
    const pages    = Math.max(1, Math.ceil(total / perPage));
    if (currentPage > pages) currentPage = pages;
    const slice = filtered.slice((currentPage-1)*perPage, currentPage*perPage);
    const tbody = document.getElementById('devisBody');

    tbody.innerHTML = slice.length === 0
        ? `<tr><td colspan="8"><div class="devis-empty"><i class="bi bi-inbox"></i>Aucun devis trouvé.</div></td></tr>`
        : slice.map(d => `
            <tr>
                <td>
                    <div class="devis-client">
                        <div class="devis-client-avatar">${escapeHtml(initials(d))}</div>
                        <div>
                            <div class="devis-client-name">${escapeHtml(d.prenom)} ${escapeHtml(d.nom)}</div>
                            <div class="devis-client-meta">${escapeHtml(d.email)}</div>
                        </div>
                    </div>
                </td>
                <td><div class="devis-reference"><i class="bi bi-upc-scan"></i>${escapeHtml(d.reference)}</div></td>
                <td><span class="devis-type-badge devis-type-${escapeHtml(d.type)}"><i class="bi bi-${typeIcon(d.type)}"></i>${typeLabel(d.type)}</span></td>
                <td>
                    <div style="font-weight:700;color:#fff">${escapeHtml(d.offreNom)}</div>
                    <div style="font-size:12px;color:var(--text-secondary)">#${escapeHtml(String(d.idOffre))}</div>
                </td>
                <td><span class="devis-status status-${escapeHtml(d.statut)}"><i class="bi bi-${statusIcon(d.statut)}"></i>${statusLabel(d.statut)}</span></td>
                <td><span class="devis-amount">${formatMoney(d.montant)}</span></td>
                <td style="color:var(--text-secondary)">${formatDate(d.date)}</td>
                <td>
                    <div class="actions">
                        <button class="btn btn-outline btn-sm" onclick="viewDevis(${d.id})"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-outline btn-sm" onclick="editDevis(${d.id})"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-${d.statut==='accepte'?'warning':'success'} btn-sm" onclick="toggleStatusFast(${d.id})">
                            <i class="bi bi-${d.statut==='accepte'?'arrow-counterclockwise':'check2-circle'}"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteDevis(${d.id})"><i class="bi bi-trash3"></i></button>
                    </div>
                </td>
            </tr>`).join('');

    const start = total === 0 ? 0 : (currentPage-1)*perPage+1;
    const end   = Math.min(currentPage*perPage, total);
    document.getElementById('paginationInfo').textContent = `Affichage ${start}–${end} sur ${total} devis`;
    const btns = document.getElementById('paginationBtns');
    btns.innerHTML = `
        <button class="page-btn" onclick="goPage(${currentPage-1})" ${currentPage<=1?'disabled':''}>
            <i class="bi bi-chevron-left"></i></button>
        ${Array.from({length:pages},(_,i)=>`<button class="page-btn ${i+1===currentPage?'active':''}" onclick="goPage(${i+1})">${i+1}</button>`).join('')}
        <button class="page-btn" onclick="goPage(${currentPage+1})" ${currentPage>=pages?'disabled':''}>
            <i class="bi bi-chevron-right"></i></button>`;
    updateStats();
}

function goPage(p) {
    const pages = Math.max(1, Math.ceil(getFiltered().length/perPage));
    if (p < 1 || p > pages) return;
    currentPage = p; render();
}

/* ============================================================
   MODALS
   ============================================================ */
function openModal(id)  { document.getElementById(id)?.classList.add('open'); document.body.style.overflow='hidden'; }
function closeModal(id) {
    document.getElementById(id)?.classList.remove('open');
    document.body.style.overflow='';
    if (id==='modalAddEdit') resetForm();
}
document.addEventListener('keydown', e => {
    if (e.key==='Escape') { document.querySelectorAll('.modal-overlay.open').forEach(m=>m.classList.remove('open')); document.body.style.overflow=''; }
});
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target===overlay) { overlay.classList.remove('open'); document.body.style.overflow=''; if(overlay.id==='modalAddEdit') resetForm(); }
    });
});

/* ============================================================
   TABS MODAL
   ============================================================ */
function switchModalTab(tab) {
    currentModalTab = tab;
    document.querySelectorAll('.devis-modal-tab').forEach(b => b.classList.toggle('active', b.dataset.tab===tab));
    document.querySelectorAll('.devis-tab-panel').forEach(p => p.classList.add('devis-hidden'));
    document.getElementById(`tab-${tab}`)?.classList.remove('devis-hidden');
}

/* ============================================================
   FORM RESET
   ============================================================ */
function resetForm() {
    editingId = null; currentModalTab = 'general'; switchModalTab('general');
    document.getElementById('modalAddTitle').innerHTML = '<i class="bi bi-file-earmark-plus"></i> Ajouter un devis';
    document.getElementById('btnSaveDevis').innerHTML  = '<i class="bi bi-save"></i> Enregistrer';
    ['fNom','fPrenom','fEmail','fTelephone','fMontant','fReponseAdmin',
     'fAutoMarque','fAutoModele','fAutoAnnee','fAutoImmatriculation','fAutoPuissance','fAutoValeur',
     'fHabAdresse','fHabSuperficie','fHabPieces','fHabValeur',
     'fSanteAge','fSanteBeneficiaires','fSanteProfession','fSanteCouverture','fSanteAntecedents'
    ].forEach(id => { const el=document.getElementById(id); if(el){el.value='';el.classList.remove('error');} });
    document.getElementById('fType').value='auto';
    document.getElementById('fStatut').value='en_attente';
    document.getElementById('fAutoCarburant').value='Essence';
    document.getElementById('fAutoUsage').value='Personnel';
    document.getElementById('fHabType').value='Appartement';
    document.getElementById('fHabOccupation').value='Propriétaire';
    document.getElementById('fSanteSituation').value='Célibataire';
    document.querySelectorAll('.form-error').forEach(el=>el.classList.remove('show'));
    handleTypeChange();
}

/* ============================================================
   GESTION DU TYPE
   ============================================================ */
function handleTypeChange() {
    const type = document.getElementById('fType').value;
    filterOffersByType(type);
    ['auto','habitation','sante'].forEach(t =>
        document.getElementById(`section-${t}`)?.classList.toggle('devis-hidden', t!==type));
}

/* ============================================================
   VALIDATION
   ============================================================ */
function showErr(inputId, errId, show) {
    document.getElementById(inputId)?.classList.toggle('error', show);
    document.getElementById(errId)?.classList.toggle('show', show);
}
function validateEmail(email) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email); }
function validateForm() {
    let ok = true;
    const nom=document.getElementById('fNom').value.trim();
    const prenom=document.getElementById('fPrenom').value.trim();
    const email=document.getElementById('fEmail').value.trim();
    const tel=document.getElementById('fTelephone').value.trim();
    const type=document.getElementById('fType').value;
    if(!nom){showErr('fNom','errNom',true);ok=false;}else showErr('fNom','errNom',false);
    if(!prenom){showErr('fPrenom','errPrenom',true);ok=false;}else showErr('fPrenom','errPrenom',false);
    if(!validateEmail(email)){showErr('fEmail','errEmail',true);ok=false;}else showErr('fEmail','errEmail',false);
    if(!tel){showErr('fTelephone','errTelephone',true);ok=false;}else showErr('fTelephone','errTelephone',false);
    if(type==='auto'){
        if(!document.getElementById('fAutoMarque').value.trim()) ok=false;
        if(!document.getElementById('fAutoModele').value.trim()) ok=false;
        if(!document.getElementById('fAutoAnnee').value.trim())  ok=false;
    }
    if(type==='habitation' && !document.getElementById('fHabAdresse').value.trim()) ok=false;
    if(type==='sante'      && !document.getElementById('fSanteAge').value.trim())   ok=false;
    if(!ok) showToast('Veuillez compléter les champs obligatoires.','warning');
    return ok;
}

/* ============================================================
   BUILD DETAILS
   ============================================================ */
function buildDetailsByType(type) {
    if(type==='auto') return {
        marque:           document.getElementById('fAutoMarque').value.trim(),
        modele:           document.getElementById('fAutoModele').value.trim(),
        annee:            Number(document.getElementById('fAutoAnnee').value||0),
        immatriculation:  document.getElementById('fAutoImmatriculation').value.trim(),
        puissance:Number(document.getElementById('fAutoPuissance').value||0),
        carburant:        document.getElementById('fAutoCarburant').value,
        valeur_vehicule:  Number(document.getElementById('fAutoValeur').value||0),
        usage_vehicule:   document.getElementById('fAutoUsage').value,
    };
    if(type==='habitation') return {
        type_habitation:  document.getElementById('fHabType').value,
        adresse:          document.getElementById('fHabAdresse').value.trim(),
        superficie:       Number(document.getElementById('fHabSuperficie').value||0),
        nombre_pieces:    Number(document.getElementById('fHabPieces').value||0),
        valeur_bien:      Number(document.getElementById('fHabValeur').value||0),
        statut_occupation:document.getElementById('fHabOccupation').value,
    };
    return {
        age:                  Number(document.getElementById('fSanteAge').value||0),
        situation_familiale:  document.getElementById('fSanteSituation').value,
        nombre_beneficiaires: Number(document.getElementById('fSanteBeneficiaires').value||1),
        antecedents_medicaux: document.getElementById('fSanteAntecedents').value.trim(),
        couverture_souhaitee: document.getElementById('fSanteCouverture').value.trim(),
        profession:           document.getElementById('fSanteProfession').value.trim(),
    };
}

/* ============================================================
   SAVE — appel API réel
   ============================================================ */
function saveDevis() {
    if(!validateForm()) return;
    const btn=document.getElementById('btnSaveDevis');
    const orig=btn.innerHTML;
    btn.innerHTML='<i class="bi bi-arrow-repeat spin"></i> Enregistrement...';
    btn.disabled=true;

    const type=document.getElementById('fType').value;
    const idOffre=Number(document.getElementById('fOffre').value);
    const offre=getOffreById(idOffre);
    const montant=Number(document.getElementById('fMontant').value||0);

    const payload={
        nom:           document.getElementById('fNom').value.trim(),
        prenom:        document.getElementById('fPrenom').value.trim(),
        email:         document.getElementById('fEmail').value.trim(),
        telephone:     document.getElementById('fTelephone').value.trim(),
        type_assurance:type,
        id_offre:      idOffre,
        statut:        document.getElementById('fStatut').value,
        montant_estime:montant||(offre?offre.prixBase:0),
        reponse_admin: document.getElementById('fReponseAdmin').value.trim(),
        ...buildDetailsByType(type)
    };
    if(editingId) payload.id_devis=editingId;

    const action=editingId?'devis_modifier':'devis_ajouter';
    fetch(`${BASE_URL_PHP}/api.php?action=${action}`,{
        method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)
    })
    .then(r=>r.json())
    .then(async data=>{
        if(data.success){
            await loadDevis();
            showToast(editingId?'Devis modifié avec succès':'Devis ajouté avec succès','success');
            closeModal('modalAddEdit'); render();
        } else showToast('Erreur : '+(data.error||'Inconnue'),'danger');
    })
    .catch(()=>showToast('Erreur réseau.','danger'))
    .finally(()=>{btn.innerHTML=orig;btn.disabled=false;});
}

/* ============================================================
   EDIT
   ============================================================ */
function editDevis(id) {
    const d=devis.find(item=>item.id===id);
    if(!d) return;
    resetForm(); editingId=id;
    document.getElementById('modalAddTitle').innerHTML='<i class="bi bi-pencil"></i> Modifier le devis';
    document.getElementById('btnSaveDevis').innerHTML='<i class="bi bi-save"></i> Mettre à jour';
    document.getElementById('fNom').value=d.nom;
    document.getElementById('fPrenom').value=d.prenom;
    document.getElementById('fEmail').value=d.email;
    document.getElementById('fTelephone').value=d.telephone;
    document.getElementById('fType').value=d.type;
    filterOffersByType(d.type);
    document.getElementById('fOffre').value=String(d.idOffre);
    document.getElementById('fStatut').value=d.statut;
    document.getElementById('fMontant').value=d.montant||'';
    document.getElementById('fReponseAdmin').value=d.reponseAdmin||'';
    handleTypeChange();
    openModal('modalAddEdit');
}
function editFromView() { closeModal('modalView'); if(viewingId!==null) editDevis(viewingId); }

/* ============================================================
   VIEW DETAIL
   ============================================================ */
function field(label,value) {
    return `<div class="devis-detail-item">
        <div class="devis-detail-label">${escapeHtml(label)}</div>
        <div class="devis-detail-value">${escapeHtml(String(value??'—'))}</div>
    </div>`;
}
function buildSpecificDetail(d) {
    if(d.type==='auto') return `<div class="devis-detail-card"><div class="devis-detail-title"><i class="bi bi-car-front"></i> Détails véhicule</div><div class="devis-detail-list">${field('Marque',d.details?.marque)}${field('Modèle',d.details?.modele)}${field('Année',d.details?.annee)}${field('Immatriculation',d.details?.immatriculation)}${field('Puissance',d.details?.puissance)}${field('Carburant',d.details?.carburant)}${field('Valeur',formatMoney(d.details?.valeur_vehicule))}${field('Usage',d.details?.usage_vehicule)}</div></div>`;
    if(d.type==='habitation') return `<div class="devis-detail-card"><div class="devis-detail-title"><i class="bi bi-house-door"></i> Détails habitation</div><div class="devis-detail-list">${field('Type',d.details?.type_habitation)}${field('Adresse',d.details?.adresse)}${field('Superficie',d.details?.superficie?d.details.superficie+' m²':'—')}${field('Pièces',d.details?.nombre_pieces)}${field('Valeur',formatMoney(d.details?.valeur_bien))}${field('Occupation',d.details?.statut_occupation)}</div></div>`;
    return `<div class="devis-detail-card"><div class="devis-detail-title"><i class="bi bi-heart-pulse"></i> Détails santé</div><div class="devis-detail-list">${field('Âge',d.details?.age)}${field('Situation',d.details?.situation_familiale)}${field('Bénéficiaires',d.details?.nombre_beneficiaires)}${field('Profession',d.details?.profession)}${field('Couverture',d.details?.couverture_souhaitee)}${field('Antécédents',d.details?.antecedents_medicaux)}</div></div>`;
}
function viewDevis(id) {
    const d=devis.find(item=>item.id===id); if(!d) return;
    viewingId=id;
    document.getElementById('modalViewBody').innerHTML=`
        <div class="devis-view-header">
            <div class="devis-view-avatar">${escapeHtml(initials(d))}</div>
            <div>
                <div style="font-size:18px;font-weight:800;color:#fff">${escapeHtml(d.prenom)} ${escapeHtml(d.nom)}</div>
                <div style="font-size:13px;color:var(--text-secondary);margin-top:2px">${escapeHtml(d.email)} · ${escapeHtml(d.telephone)}</div>
                <div class="devis-inline-badges">
                    <span class="devis-type-badge devis-type-${escapeHtml(d.type)}"><i class="bi bi-${typeIcon(d.type)}"></i>${typeLabel(d.type)}</span>
                    <span class="devis-status status-${escapeHtml(d.statut)}"><i class="bi bi-${statusIcon(d.statut)}"></i>${statusLabel(d.statut)}</span>
                    <span class="devis-summary-chip"><i class="bi bi-upc-scan"></i>${escapeHtml(d.reference)}</span>
                </div>
            </div>
        </div>
        <div class="devis-detail-grid">
            <div class="devis-detail-card"><div class="devis-detail-title"><i class="bi bi-file-earmark-text"></i> Informations générales</div>
            <div class="devis-detail-list">${field('Offre',d.offreNom)}${field('Montant',formatMoney(d.montant))}${field('Date',formatDate(d.date))}${field('Statut',statusLabel(d.statut))}${field('Réponse admin',d.reponseAdmin||'—')}</div></div>
            ${buildSpecificDetail(d)}
        </div>`;
    openModal('modalView');
}

/* ============================================================
   TOGGLE STATUT — appel API réel
   ============================================================ */
function toggleStatusFast(id) {
    const d=devis.find(item=>item.id===id); if(!d) return;
    const newStatut=d.statut==='accepte'?'en_cours':'accepte';
    fetch(' + BASE_URL_PHP + '/api.php?action=devis_modifier',{
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({id_devis:id,statut:newStatut,montant_estime:d.montant,reponse_admin:d.reponseAdmin})
    })
    .then(r=>r.json())
    .then(async data=>{ if(data.success){ await loadDevis(); render(); showToast(newStatut==='accepte'?'Devis accepté.':'Remis en cours.','success'); } })
    .catch(()=>showToast('Erreur réseau.','danger'));
}

/* ============================================================
   DELETE — appel API réel
   ============================================================ */
function deleteDevis(id) {
    const d=devis.find(item=>item.id===id); if(!d) return;
    deletingId=id;
    document.getElementById('deleteMsg').innerHTML=`Vous allez supprimer <span class="delete-name">${escapeHtml(d.reference)}</span> de <span class="delete-name">${escapeHtml(d.prenom)} ${escapeHtml(d.nom)}</span>.<br>Cette action est irréversible.`;
    openModal('modalDelete');
}
function confirmDelete() {
    const btn=document.getElementById('btnConfirmDelete');
    const orig=btn.innerHTML;
    btn.innerHTML='<i class="bi bi-arrow-repeat spin"></i> Suppression...'; btn.disabled=true;
    fetch(`${BASE_URL_PHP}/api.php?action=devis_supprimer&id=${deletingId}`)
    .then(r=>r.json())
    .then(async data=>{ if(data.success){ await loadDevis(); closeModal('modalDelete'); render(); showToast('Devis supprimé','danger'); } })
    .catch(()=>showToast('Erreur réseau.','danger'))
    .finally(()=>{btn.innerHTML=orig;btn.disabled=false;});
}

/* ============================================================
   EXPORT CSV
   ============================================================ */
function exportDevis() {
    const filtered=getFiltered();
    const header=['Référence','Nom','Prénom','Email','Téléphone','Type','Offre','Statut','Montant','Date'];
    const rows=filtered.map(d=>[d.reference,d.nom,d.prenom,d.email,d.telephone,d.type,d.offreNom,d.statut,d.montant,d.date]);
    const csv=[header,...rows].map(r=>r.map(v=>`"${String(v??'').replace(/"/g,'""')}"`).join(';')).join('\n');
    const blob=new Blob([csv],{type:'text/csv;charset=utf-8;'});
    const url=URL.createObjectURL(blob);
    const link=document.createElement('a'); link.href=url; link.download='devis.csv'; link.click();
    URL.revokeObjectURL(url);
    showToast('Export réalisé avec succès','success');
}

/* ============================================================
   REFRESH
   ============================================================ */
async function refreshDevis() { await loadDevis(); render(); showToast('Vue devis actualisée','success'); }

/* ============================================================
   TOASTS
   ============================================================ */
function showToast(message,type='success') {
    const icons={success:'check-circle',warning:'exclamation-triangle',danger:'x-circle'};
    const t=document.createElement('div');
    t.className=`toast-notif toast-${type}`;
    t.innerHTML=`<i class="bi bi-${icons[type]}"></i><span>${escapeHtml(message)}</span>`;
    document.body.appendChild(t);
    setTimeout(()=>t.classList.add('show'),50);
    setTimeout(()=>{t.classList.remove('show');setTimeout(()=>t.remove(),300);},3000);
}

/* ============================================================
   NAV ACTIVE
   ============================================================ */
function syncActiveNav() {
    const path=window.location.pathname.toLowerCase();
    if(path.includes('devis'))     document.getElementById('navDevis')?.classList.add('active');
    if(path.includes('offres'))    document.getElementById('navOffres')?.classList.add('active');
    if(path.includes('paiements')) document.getElementById('navPaiements')?.classList.add('active');
}

/* ============================================================
   EVENTS
   ============================================================ */
document.getElementById('searchInput').addEventListener('input',  ()=>{currentPage=1;render();});
document.getElementById('filterType').addEventListener('change',  ()=>{currentPage=1;render();});
document.getElementById('filterStatut').addEventListener('change',()=>{currentPage=1;render();});
document.getElementById('filterOffre').addEventListener('change', ()=>{currentPage=1;render();});
document.querySelectorAll('.devis-filter-pill').forEach(btn=>{
    btn.addEventListener('click',()=>{
        currentQuickFilter=btn.dataset.quick;
        document.querySelectorAll('.devis-filter-pill').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active'); currentPage=1; render();
    });
});

/* ============================================================
   DÉMARRAGE
   ============================================================ */
init();
</script>
</body>
</html>
