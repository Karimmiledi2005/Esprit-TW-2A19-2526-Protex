<?php
/**
 * view/BackOffice/dashboard.php
 * Dashboard unifié BackOffice — Protex 2026
 */

require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
$currentRole   = SessionGuard::role();
$isSuperAdmin  = $currentRole === 'superadmin';
$isAdminAgence = $currentRole === 'admin';
$isAgent       = $currentRole === 'agent';

if (!defined('BASE_URL')) define('BASE_URL', (defined('BASE_URL') ? BASE_URL : ''));
$base = (defined('BASE_URL') ? BASE_URL : '');

function dE($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function dMoney($v): string { return number_format((float)$v, 3, '.', ' ') . ' DT'; }
function dDate($d): string { if (!$d) return '—'; try { return (new DateTime($d))->format('d/m/Y H:i'); } catch (Exception $e) { return dE($d); } }

$typeIcons = ['auto' => 'car-front', 'habitation' => 'house-door', 'sante' => 'heart-pulse', 'vie' => 'shield-heart'];
$typeColors = ['auto' => '#00c2ff', 'habitation' => '#ff9900', 'sante' => '#00d68f', 'vie' => '#a855f7'];
$statusBadges = [
    'en_attente' => '<span class="badge-warn"><i class="bi bi-hourglass-split"></i> En attente</span>',
    'valide'     => '<span class="badge-ok"><i class="bi bi-check-circle"></i> Validé</span>',
    'refuse'     => '<span class="badge-err"><i class="bi bi-x-circle"></i> Refusé</span>',
    'accepte'    => '<span class="badge-ok"><i class="bi bi-check-circle"></i> Accepté</span>',
    'en_cours'   => '<span class="badge-info"><i class="bi bi-arrow-repeat"></i> En cours</span>',
];

// ═══ KPIs serveur ═══
$dbKpi = config::getConnexion();
$idAgence = SessionGuard::agenceId();
$role = SessionGuard::role();
$kpi = [];

// Devis
if ($role === 'superadmin') {
    $stmt = $dbKpi->query("SELECT COUNT(*) FROM devis"); $kpi['devis_total'] = (int)$stmt->fetchColumn();
    $stmt = $dbKpi->query("SELECT COUNT(*) FROM devis WHERE statut = 'en_attente'"); $kpi['devis_en_attente'] = (int)$stmt->fetchColumn();
    $stmt = $dbKpi->query("SELECT COUNT(*) FROM devis WHERE statut = 'accepte'"); $kpi['devis_acceptes'] = (int)$stmt->fetchColumn();
    $stmt = $dbKpi->query("SELECT COUNT(*) FROM devis WHERE statut = 'converti'"); $kpi['devis_convertis'] = (int)$stmt->fetchColumn();
    $stmt = $dbKpi->query("SELECT COALESCE(SUM(montant), 0) FROM paiement WHERE statut = 'valide'"); $kpi['revenus'] = (float)$stmt->fetchColumn();
    $stmt = $dbKpi->query("SELECT COUNT(*) FROM paiement"); $kpi['paiements_total'] = (int)$stmt->fetchColumn();
    $stmt = $dbKpi->query("SELECT COUNT(*) FROM paiement WHERE statut = 'en_attente'"); $kpi['paiements_en_attente'] = (int)$stmt->fetchColumn();
    $stmt = $dbKpi->query("SELECT COUNT(*) FROM paiement WHERE statut = 'valide'"); $kpi['paiements_valides'] = (int)$stmt->fetchColumn();
} elseif ($role === 'admin' && $idAgence) {
    $stmt = $dbKpi->prepare("SELECT COUNT(*) FROM devis WHERE id_agence = ?"); $stmt->execute([$idAgence]); $kpi['devis_total'] = (int)$stmt->fetchColumn();
    $stmt = $dbKpi->prepare("SELECT COUNT(*) FROM devis WHERE statut = 'en_attente' AND id_agence = ?"); $stmt->execute([$idAgence]); $kpi['devis_en_attente'] = (int)$stmt->fetchColumn();
    $stmt = $dbKpi->prepare("SELECT COUNT(*) FROM devis WHERE statut = 'accepte' AND id_agence = ?"); $stmt->execute([$idAgence]); $kpi['devis_acceptes'] = (int)$stmt->fetchColumn();
    $stmt = $dbKpi->prepare("SELECT COUNT(*) FROM devis WHERE statut = 'converti' AND id_agence = ?"); $stmt->execute([$idAgence]); $kpi['devis_convertis'] = (int)$stmt->fetchColumn();
    $stmt = $dbKpi->prepare("SELECT COALESCE(SUM(montant), 0) FROM paiement WHERE statut = 'valide' AND id_agence = ?"); $stmt->execute([$idAgence]); $kpi['revenus'] = (float)$stmt->fetchColumn();
    $stmt = $dbKpi->prepare("SELECT COUNT(*) FROM paiement WHERE id_agence = ?"); $stmt->execute([$idAgence]); $kpi['paiements_total'] = (int)$stmt->fetchColumn();
    $stmt = $dbKpi->prepare("SELECT COUNT(*) FROM paiement WHERE statut = 'en_attente' AND id_agence = ?"); $stmt->execute([$idAgence]); $kpi['paiements_en_attente'] = (int)$stmt->fetchColumn();
    $stmt = $dbKpi->prepare("SELECT COUNT(*) FROM paiement WHERE statut = 'valide' AND id_agence = ?"); $stmt->execute([$idAgence]); $kpi['paiements_valides'] = (int)$stmt->fetchColumn();
} else {
    $kpi = ['devis_total'=>0,'devis_en_attente'=>0,'devis_acceptes'=>0,'devis_convertis'=>0,'revenus'=>0,'paiements_total'=>0,'paiements_en_attente'=>0,'paiements_valides'=>0];
}

$kpi['taux_acceptation'] = $kpi['devis_total'] > 0 ? round($kpi['devis_acceptes'] / $kpi['devis_total'] * 100, 1) : 0;
$kpi['taux_conversion'] = $kpi['devis_acceptes'] > 0 ? round($kpi['devis_convertis'] / $kpi['devis_acceptes'] * 100, 1) : 0;
$kpi['devis_sans_paiement'] = $kpi['devis_convertis'];

// Derniers devis
if ($role === 'superadmin') {
    $stmt = $dbKpi->query("
        SELECT d.*, u.nom, u.prenom, o.nom_offre
        FROM devis d
        LEFT JOIN user u ON d.id_user = u.id_user
        LEFT JOIN offre o ON d.id_offre = o.id_offre
        ORDER BY d.date_demande DESC LIMIT 5
    ");
    $recentDevis = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($role === 'admin' && $idAgence) {
    $stmt = $dbKpi->prepare("
        SELECT d.*, u.nom, u.prenom, o.nom_offre
        FROM devis d
        LEFT JOIN user u ON d.id_user = u.id_user
        LEFT JOIN offre o ON d.id_offre = o.id_offre
        WHERE d.id_agence = ?
        ORDER BY d.date_demande DESC LIMIT 5
    ");
    $stmt->execute([$idAgence]);
    $recentDevis = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $recentDevis = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/layout.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/admin-users.css">
    <style>
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 28px; }
        .kpi-card {
            padding: 22px 24px; border-radius: 20px;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.03);
            position: relative; overflow: hidden;
        }
        .kpi-card::before {
            content: ''; position: absolute; top: -20px; right: -20px;
            width: 80px; height: 80px; border-radius: 50%; opacity: .12;
        }
        .kpi-card.orange::before { background: var(--accent); }
        .kpi-card.green::before { background: #198754; }
        .kpi-card.blue::before { background: #0dcaf0; }
        .kpi-card.purple::before { background: #a855f7; }
        .kpi-icon { font-size: 24px; margin-bottom: 10px; }
        .kpi-value { font-size: 32px; font-weight: 900; color: #fff; margin-bottom: 4px; }
        .kpi-label { font-size: 12px; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; letter-spacing: .08em; }
        .kpi-sub { font-size: 12px; color: var(--text-secondary); margin-top: 6px; }
        .kpi-sub strong { color: #fff; }
        .live-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
            margin: 10px 0 24px;
        }
        .live-stat-card {
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.04);
            color: #fff;
        }
        .live-stat-label { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: var(--text-secondary); font-weight: 700; }
        .live-stat-value { font-size: 28px; font-weight: 900; margin-top: 8px; line-height: 1; }
        .live-stat-sub { font-size: 12px; color: var(--text-secondary); margin-top: 6px; }
        .live-dot {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 700;
            color: #90f1bc;
        }
        .live-dot::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #2ec4b6;
            box-shadow: 0 0 0 0 rgba(46,196,182,0.6);
            animation: livePulse 1.4s infinite;
        }
        @keyframes livePulse {
            0% { box-shadow: 0 0 0 0 rgba(46,196,182,0.55); }
            70% { box-shadow: 0 0 0 10px rgba(46,196,182,0); }
            100% { box-shadow: 0 0 0 0 rgba(46,196,182,0); }
        }

        .dash-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; margin-bottom: 24px; }
        @media (max-width: 1100px) { .dash-grid { grid-template-columns: 1fr; } }

        .dash-card {
            border-radius: 22px; padding: 24px;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.03);
        }
        .dash-card-head {
            font-size: 16px; font-weight: 800; color: #fff; margin-bottom: 18px;
            display: flex; align-items: center; justify-content: space-between;
            padding-bottom: 14px; border-bottom: 1px solid rgba(255,255,255,.06);
        }
        .dash-card-head i { color: var(--accent); margin-right: 8px; }

        .dash-link { font-size: 12px; color: var(--accent); text-decoration: none; font-weight: 700; }
        .dash-link:hover { text-decoration: underline; }

        .dash-table { width: 100%; border-collapse: collapse; }
        .dash-table th { text-align: left; padding: 10px 12px; font-size: 11px; color: var(--text-secondary); font-weight: 700; text-transform: uppercase; letter-spacing: .06em; border-bottom: 1px solid rgba(255,255,255,.06); }
        .dash-table td { padding: 12px; font-size: 13px; color: #fff; border-bottom: 1px solid rgba(255,255,255,.04); }
        .dash-table tr:last-child td { border-bottom: none; }
        .dash-table a { color: var(--accent); text-decoration: none; font-weight: 600; }
        .dash-table a:hover { text-decoration: underline; }

        .badge-warn { padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; background: rgba(255,193,7,.12); color: #ffd66e; }
        .badge-ok   { padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; background: rgba(25,135,84,.12); color: #90f1bc; }
        .badge-err  { padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; background: rgba(220,53,69,.12); color: #ff9cab; }
        .badge-info { padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; background: rgba(13,202,240,.12); color: #8eeaff; }

        .perf-row { display: flex; align-items: center; gap: 14px; padding: 14px 0; border-bottom: 1px solid rgba(255,255,255,.06); }
        .perf-row:last-child { border-bottom: none; }
        .perf-bar-bg { flex: 1; height: 8px; border-radius: 4px; background: rgba(255,255,255,.06); overflow: hidden; }
        .perf-bar { height: 100%; border-radius: 4px; background: var(--accent); }
        .perf-name { font-size: 13px; color: #fff; font-weight: 700; min-width: 130px; }
        .perf-num  { font-size: 13px; color: var(--text-secondary); min-width: 50px; text-align: right; }

        .conversion-box {
            padding: 20px; border-radius: 18px;
            background: rgba(255,107,26,.06); border: 1px solid rgba(255,107,26,.18);
            text-align: center; margin-bottom: 18px;
        }
        .conversion-val { font-size: 40px; font-weight: 900; color: var(--accent); }
        .conversion-label { font-size: 12px; color: var(--text-secondary); margin-top: 4px; }

        .diag-card { border-radius: 22px; border: 1px solid rgba(255,255,255,.08); background: linear-gradient(135deg, rgba(255,255,255,.04), rgba(255,255,255,.02)); padding: 24px; margin-bottom: 22px; }
        .diag-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .diag-title { font-size: 16px; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 10px; }
        .diag-title i { color: var(--accent); font-size: 20px; }
        .score-circle { width: 72px; height: 72px; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; border: 3px solid; }
        .score-num { font-size: 24px; font-weight: 900; color: #fff; line-height: 1; }
        .score-lbl { font-size: 8px; font-weight: 700; text-transform: uppercase; color: rgba(255,255,255,.6); margin-top: 2px; }

        .diag-alert { display: flex; gap: 14px; padding: 14px 16px; border-radius: 14px; margin-bottom: 10px; align-items: flex-start; }
        .diag-alert:last-child { margin-bottom: 0; }
        .diag-alert-danger { background: rgba(220,53,69,.08); border: 1px solid rgba(220,53,69,.15); }
        .diag-alert-warning { background: rgba(255,193,7,.08); border: 1px solid rgba(255,193,7,.15); }
        .diag-alert-info { background: rgba(13,202,240,.08); border: 1px solid rgba(13,202,240,.15); }
        .diag-alert-success { background: rgba(0,214,143,.08); border: 1px solid rgba(0,214,143,.15); }
        .diag-alert-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
        .diag-alert-danger .diag-alert-icon { background: rgba(220,53,69,.15); color: #ff9cab; }
        .diag-alert-warning .diag-alert-icon { background: rgba(255,193,7,.15); color: #ffd66e; }
        .diag-alert-info .diag-alert-icon { background: rgba(13,202,240,.15); color: #8eeaff; }
        .diag-alert-success .diag-alert-icon { background: rgba(0,214,143,.15); color: #90f1bc; }
        .diag-alert-body { flex: 1; }
        .diag-alert-title { font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 2px; }
        .diag-alert-msg { font-size: 12px; color: var(--text-secondary); line-height: 1.5; }

        .diag-rec { padding: 12px 16px; border-radius: 12px; background: rgba(255,255,255,.03); border-left: 3px solid var(--accent); margin-bottom: 8px; font-size: 13px; color: rgba(255,255,255,.85); line-height: 1.6; }
        .diag-rec:last-child { margin-bottom: 0; }
        .diag-rec i { color: var(--accent); margin-right: 8px; }

        .diag-trend { display: flex; gap: 10px; align-items: center; padding: 10px 14px; border-radius: 10px; background: rgba(255,255,255,.03); margin-bottom: 8px; }
        .diag-trend:last-child { margin-bottom: 0; }
        .diag-trend i { font-size: 16px; width: 28px; text-align: center; }
        .diag-trend-body { flex: 1; }
        .diag-trend-title { font-size: 12px; font-weight: 700; color: #fff; }
        .diag-trend-msg { font-size: 11px; color: var(--text-secondary); }

        @keyframes diagSpin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body>
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="layout">
    <?php include __DIR__ . '/assets/includes/sidebar.php'; ?>

    <main class="main">
        <div class="topbar">
            <h1 class="topbar-title">📊 Tableau de bord <span class="live-dot">Live</span></h1>
            <div style="display:flex;align-items:center;gap:12px;">
                <span class="role-badge" style="padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;background:rgba(0,180,216,0.15);color:var(--accent);border:1px solid rgba(0,180,216,0.3);">
                    <i class="bi bi-shield-check"></i> <?= ucfirst(htmlspecialchars($currentRole)) ?>
                </span>
                <div style="font-size:13px;color:var(--text-secondary);"><?= date('d M Y') ?></div>
            </div>
        </div>

        <div class="content">
            <!-- ═══ KPI CARDS ═══ -->
            <div class="kpi-grid">
                <div class="kpi-card orange">
                    <div class="kpi-icon" style="color:var(--accent);">📋</div>
                    <div class="kpi-value" data-count="<?= (int)$kpi['devis_total'] ?>" data-format="number"><?= $kpi['devis_total'] ?></div>
                    <div class="kpi-label">Total devis</div>
                    <div class="kpi-sub"><strong><?= $kpi['devis_en_attente'] ?></strong> en attente</div>
                </div>
                <div class="kpi-card green">
                    <div class="kpi-icon" style="color:#198754;">✅</div>
                    <div class="kpi-value" data-count="<?= (int)$kpi['devis_acceptes'] ?>" data-format="number"><?= $kpi['devis_acceptes'] ?></div>
                    <div class="kpi-label">Devis acceptés</div>
                    <div class="kpi-sub">Taux <strong><?= $kpi['taux_acceptation'] ?>%</strong></div>
                </div>
                <div class="kpi-card blue">
                    <div class="kpi-icon" style="color:#0dcaf0;">💳</div>
                    <div class="kpi-value" data-count="<?= (int)$kpi['paiements_total'] ?>" data-format="number"><?= $kpi['paiements_total'] ?></div>
                    <div class="kpi-label">Paiements</div>
                    <div class="kpi-sub"><strong><?= $kpi['paiements_en_attente'] ?></strong> en attente</div>
                </div>
                <div class="kpi-card purple">
                    <div class="kpi-icon" style="color:#a855f7;">💰</div>
                    <div class="kpi-value" data-count="<?= (float)$kpi['revenus'] ?>" data-format="money"><?= dMoney($kpi['revenus']) ?></div>
                    <div class="kpi-label">Revenus validés</div>
                    <div class="kpi-sub"><strong><?= $kpi['paiements_valides'] ?></strong> paiements validés</div>
                </div>
            </div>

            <div class="live-stats-grid" aria-label="Statistiques en direct">
                <div class="live-stat-card">
                    <div class="live-stat-label">Utilisateurs</div>
                    <div class="live-stat-value" data-live-stat="total_users">0</div>
                    <div class="live-stat-sub">Total comptes actifs</div>
                </div>
                <div class="live-stat-card">
                    <div class="live-stat-label">Contrats</div>
                    <div class="live-stat-value" data-live-stat="total_contracts">0</div>
                    <div class="live-stat-sub">Contrats enregistrés</div>
                </div>
                <div class="live-stat-card">
                    <div class="live-stat-label">Sinistres du mois</div>
                    <div class="live-stat-value" data-live-stat="total_sinistres_month">0</div>
                    <div class="live-stat-sub">Dossiers ouverts ce mois</div>
                </div>
                <div class="live-stat-card">
                    <div class="live-stat-label">Revenu du mois</div>
                    <div class="live-stat-value" data-live-stat="revenue_month">0 DT</div>
                    <div class="live-stat-sub">Encaissements validés</div>
                </div>
                <div class="live-stat-card">
                    <div class="live-stat-label">Alertes fraude</div>
                    <div class="live-stat-value" data-live-stat="fraud_alerts_open">0</div>
                    <div class="live-stat-sub">Cas à traiter</div>
                </div>
            </div>

            <!-- ═══ AI DIAGNOSTIC ═══ -->
            <div class="dash-card" style="margin-bottom:22px;">
                <div class="dash-card-head">
                    <span><i class="bi bi-robot"></i> Diagnostic IA</span>
                    <button id="diagBtn" onclick="runDiagnostic()" style="padding:8px 20px;border-radius:12px;border:1px solid rgba(0,180,216,.4);background:linear-gradient(135deg,rgba(0,180,216,.2),rgba(0,180,216,.05));color:var(--accent);font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
                        <i class="bi bi-play-circle"></i>
                        Analyser
                    </button>
                </div>
                <div id="diagLoading" style="display:none;text-align:center;padding:40px;">
                    <div style="font-size:32px;animation:diagSpin 1s linear infinite;">⚙️</div>
                    <div style="color:var(--text-secondary);font-size:13px;margin-top:12px;">Analyse en cours...</div>
                </div>
                <div id="diagResult"></div>
            </div>

            <!-- ═══ CONVERSION ═══ -->
            <div class="dash-grid">
                <div>
                    <div class="dash-card" style="margin-bottom:20px;">
                        <div class="dash-card-head">
                            <span><i class="bi bi-funnel"></i> Conversion Devis → Contrat</span>
                        </div>
                        <div class="conversion-box">
                            <div class="conversion-val"><?= $kpi['taux_conversion'] ?>%</div>
                            <div class="conversion-label">des devis acceptés convertis en contrat</div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;text-align:center;">
                            <div>
                                <div style="font-size:24px;font-weight:900;color:#fff;"><?= $kpi['devis_acceptes'] ?></div>
                                <div style="font-size:11px;color:var(--text-secondary);">Devis acceptés</div>
                            </div>
                            <div>
                                <div style="font-size:24px;font-weight:900;color:#90f1bc;"><?= $kpi['devis_convertis'] ?></div>
                                <div style="font-size:11px;color:var(--text-secondary);">Convertis</div>
                            </div>
            <?php if ($kpi['devis_sans_paiement'] > 0): ?>
                            <div>
                                <div style="font-size:24px;font-weight:900;color:#ffd66e;"><?= $kpi['devis_sans_paiement'] ?></div>
                                <div style="font-size:11px;color:var(--text-secondary);">Sans contrat</div>
                            </div>
                            <?php else: ?>
                            <div>
                                <div style="font-size:24px;font-weight:900;color:#90f1bc;">0</div>
                                <div style="font-size:11px;color:var(--text-secondary);">Tout lié ✓</div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="dash-card">
                        <div class="dash-card-head">
                            <span><i class="bi bi-clock-history"></i> Derniers devis</span>
                            <a href="<?= $base ?>/controller/DevisController.php" class="dash-link">Voir tout →</a>
                        </div>
                        <table class="dash-table">
                            <thead><tr><th>Client</th><th>Offre</th><th>Statut</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php foreach ($recentDevis as $d): ?>
                                <tr>
                                    <td><?= dE(($d['prenom'] ?? '') . ' ' . ($d['nom'] ?? '')) ?></td>
                                    <td><?= dE($d['nom_offre'] ?? '—') ?></td>
                                    <td><?= $statusBadges[$d['statut']] ?? dE($d['statut']) ?></td>
                                    <td style="color:var(--text-secondary);font-size:12px;"><?= dDate($d['date_demande']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <div class="dash-card" style="margin-bottom:20px;">
                        <div class="dash-card-head">
                            <span><i class="bi bi-receipt"></i> Derniers paiements</span>
                            <a href="<?= $base ?>/controller/PaiementController.php" class="dash-link">Voir tout →</a>
                        </div>
                        <table class="dash-table">
                            <thead><tr><th>Ref.</th><th>Client</th><th>Montant</th><th>Statut</th></tr></thead>
                            <tbody>
                                <?php foreach ($recentPaiements as $p): ?>
                                <tr>
                                    <td><a href="<?= $base ?>/controller/PaiementController.php?action=detail&id=<?= (int)$p['id_paiement'] ?>"><?= dE($p['reference']) ?></a></td>
                                    <td><?= dE(($p['client_prenom'] ?? '') . ' ' . ($p['client_nom'] ?? '—')) ?></td>
                                    <td style="font-weight:700;"><?= dMoney($p['montant']) ?></td>
                                    <td><?= $statusBadges[$p['statut']] ?? dE($p['statut']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="dash-card">
                        <div class="dash-card-head">
                            <span><i class="bi bi-bar-chart"></i> Performance par offre</span>
                            <a href="<?= $base ?>/controller/OffreController.php" class="dash-link">Gérer →</a>
                        </div>
                        <?php
                        $maxRevenus = 0;
                        foreach ($offresPerf as $o) { $r = (float)($o['revenus'] ?? 0); if ($r > $maxRevenus) $maxRevenus = $r; }
                        ?>
                        <?php foreach ($offresPerf as $o): ?>
                            <?php $rev = (float)($o['revenus'] ?? 0); $pct = $maxRevenus > 0 ? ($rev / $maxRevenus) * 100 : 0; $type = $o['type_offre'] ?? 'auto'; $color = $typeColors[$type] ?? 'var(--accent)'; ?>
                            <div class="perf-row">
                                <div class="perf-name" style="color:<?= $color ?>;">
                                    <i class="bi bi-<?= $typeIcons[$type] ?? 'shield' ?>"></i>
                                    <?= dE($o['nom_offre']) ?>
                                </div>
                                <div class="perf-bar-bg"><div class="perf-bar" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div></div>
                                <div class="perf-num"><?= dMoney($rev) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ═══ CHART.JS ANALYTICS ═══ -->
            <div class="dash-grid" style="margin-bottom:24px;">
                <div class="dash-card">
                    <div class="dash-card-head">
                        <span><i class="bi bi-graph-up"></i> Sinistres par mois</span>
                        <span class="live-dot">Live</span>
                    </div>
                    <div style="position:relative;height:260px;">
                        <canvas id="chartSinistresMonthly"></canvas>
                    </div>
                </div>
                <div class="dash-card">
                    <div class="dash-card-head">
                        <span><i class="bi bi-pie-chart"></i> Contrats par type</span>
                    </div>
                    <div style="position:relative;height:260px;">
                        <canvas id="chartContractsType"></canvas>
                    </div>
                </div>
            </div>

            <div class="dash-grid" style="margin-bottom:24px;">
                <div class="dash-card">
                    <div class="dash-card-head">
                        <span><i class="bi bi-shield-exclamation"></i> Tranches score fraude</span>
                    </div>
                    <div style="position:relative;height:260px;">
                        <canvas id="chartFraudDistribution"></canvas>
                    </div>
                </div>

                <!-- ═══ TUNISIA MAP ═══ -->
                <div class="dash-card">
                    <div class="dash-card-head">
                        <span><i class="bi bi-geo-alt"></i> Carte des sinistres — Tunisie</span>
                    </div>
                    <div id="tunisiaMapContainer" style="position:relative;display:flex;justify-content:center;align-items:center;min-height:340px;">
                        <img src="<?= $base ?>/view/FrontOffice/images/carte-tunisie.png" alt="Carte Tunisie" id="tunisiaMapImg"
                             style="max-height:320px;opacity:0.85;filter:brightness(0.9) contrast(1.1);border-radius:12px;">

                        <!-- Region bubbles — positioned absolutely over the map -->
                        <div id="regionBubbles" style="position:absolute;inset:0;pointer-events:none;">
                            <!-- Bubbles injected by JS -->
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ═══ SOS MAP ═══ -->
        <div class="dash-card" style="margin-bottom:24px;">
            <div class="dash-card-head">
                <span><i class="bi bi-geo-fill"></i> 🆘 Alertes SOS en temps réel <span class="badge bg-danger ms-2 rounded-pill" id="sosActiveCount">0</span></span>
                <span class="live-dot">Live</span>
            </div>
            <div id="sosMap" style="height: 400px; border-radius: 12px; z-index: 1;"></div>
        </div>

    </main>
</div>

<!-- Helper CSS -->
<link rel="stylesheet" href="<?= $base ?>/view/FrontOffice/css/theme.css">
<link rel="stylesheet" href="<?= $base ?>/helpers/darkmode.css">
<link rel="stylesheet" href="<?= $base ?>/helpers/toast.css">
<link rel="stylesheet" href="<?= $base ?>/helpers/loader.css">
<link rel="stylesheet" href="<?= $base ?>/helpers/skeleton.css">
<link rel="stylesheet" href="<?= $base ?>/helpers/responsive-fixes.css">

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>

<script src="<?= $base ?>/view/BackOffice/assets/js/main.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="<?= $base ?>/helpers/darkmode.js"></script>
<script src="<?= $base ?>/helpers/toast.js"></script>
<script src="<?= $base ?>/helpers/loader.js"></script>
<script>
// --- SOS MAP LOGIC ---
document.addEventListener('DOMContentLoaded', () => {
    let sosMap = L.map('sosMap').setView([33.8869, 9.5375], 6); // Center on Tunisia
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(sosMap);

    let sosMarkers = {};

    function getSosColor(sos) {
        if (sos.statut === 'en_cours' || sos.statut === 'in_progress') return 'orange';
        if (sos.statut === 'resolu' || sos.statut === 'resolved') return 'green';
        
        let diffMins = (new Date() - new Date(sos.created_at)) / 60000;
        return diffMins < 5 ? 'red' : 'darkred';
    }

    async function fetchSosAlerts() {
        try {
            const res = await fetch(`${window.BASE_URL}/api.php?action=get_sos_admin`);
            const data = await res.json();
            if (data.success && data.data) {
                document.getElementById('sosActiveCount').textContent = data.data.length + ' alertes actives';
                
                // Clear existing markers not in data
                const currentIds = data.data.map(s => s.id);
                for (let id in sosMarkers) {
                    if (!currentIds.includes(parseInt(id))) {
                        sosMap.removeLayer(sosMarkers[id]);
                        delete sosMarkers[id];
                    }
                }

                data.data.forEach(sos => {
                    const color = getSosColor(sos);
                    const pulsingClass = color === 'red' ? 'sos-pulse' : '';
                    
                    const markerHtml = `<div class="sos-marker ${pulsingClass}" style="background-color: ${color}; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 10px ${color};"></div>`;
                    
                    const icon = L.divIcon({
                        className: 'custom-sos-icon',
                        html: markerHtml,
                        iconSize: [20, 20],
                        iconAnchor: [10, 10]
                    });

                    const popupContent = `
                        <div style="text-align:center; min-width: 150px;">
                            ${sos.avatar_url || sos.avatar ? `<img src="${window.BASE_URL}/${sos.avatar_url || 'uploads/avatars/' + sos.avatar}" style="width:50px; height:50px; border-radius:50%; object-fit:cover; margin-bottom:10px;">` : ''}
                            <h6 style="margin: 0; font-weight:bold;">${sos.prenom} ${sos.nom}</h6>
                            <p style="margin: 5px 0;"><i class="bi bi-telephone"></i> ${sos.telephone || 'Non renseigné'}</p>
                            <div style="display:flex; gap: 5px; justify-content:center; margin-top: 10px;">
                                ${sos.telephone ? `<a href="tel:${sos.telephone}" class="btn btn-sm btn-primary py-1 px-2" style="font-size:11px;"><i class="bi bi-telephone-fill"></i> Appeler</a>` : ''}
                                <button class="btn btn-sm btn-success py-1 px-2" style="font-size:11px;" onclick="resolveSos(${sos.id})"><i class="bi bi-check-circle"></i> Résolu</button>
                            </div>
                        </div>
                    `;

                    if (sosMarkers[sos.id]) {
                        sosMarkers[sos.id].setLatLng([sos.lat, sos.lng]);
                        sosMarkers[sos.id].setIcon(icon);
                        sosMarkers[sos.id].setPopupContent(popupContent);
                    } else {
                        const marker = L.marker([sos.lat, sos.lng], { icon: icon }).bindPopup(popupContent);
                        marker.addTo(sosMap);
                        sosMarkers[sos.id] = marker;
                    }
                });
            }
        } catch (e) {
            console.error('Error fetching SOS alerts', e);
        }
    }

    fetchSosAlerts();
    setInterval(fetchSosAlerts, 30000); // 30 seconds
    
    // Add pulse animation style
    const style = document.createElement('style');
    style.innerHTML = `
        @keyframes sosPulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(255, 0, 0, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 0, 0, 0); }
        }
        .sos-pulse { animation: sosPulse 1.5s infinite; }
    `;
    document.head.appendChild(style);
});

// Resolve SOS alert
window.resolveSos = async function(id) {
    if (!confirm('Marquer cette alerte SOS comme résolue ?')) return;
    try {
        const fd = new FormData();
        fd.append('id', id);
        const res = await fetch(`${window.BASE_URL}/api.php?action=resolve_sos`, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            fetchSosAlerts();
        } else {
            alert('Erreur : ' + (data.error || 'Inconnue'));
        }
    } catch (e) {
        console.error(e);
        alert('Erreur réseau');
    }
}
</script>
<script>
const DASHBOARD_STATS_URL = '<?= (defined('BASE_URL') ? BASE_URL : '') ?>/api.php?action=dashboard_stats';

function animateCounter(element, target, format) {
    const duration = 1500;
    const start = performance.now();
    const initial = 0;

    function tick(now) {
        const progress = Math.min((now - start) / duration, 1);
        const value = initial + (target - initial) * progress;
        if (format === 'money') {
            element.textContent = Number(value).toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + ' DT';
        } else {
            element.textContent = Math.round(value).toString();
        }
        if (progress < 1) {
            requestAnimationFrame(tick);
        }
    }

    requestAnimationFrame(tick);
}

function refreshDashboardStats() {
    fetch(DASHBOARD_STATS_URL, { headers: { 'Accept': 'application/json' } })
        .then(response => response.json())
        .then(data => {
            document.querySelectorAll('[data-live-stat]').forEach(el => {
                const key = el.dataset.liveStat;
                const format = key === 'revenue_month' ? 'money' : 'number';
                const target = Number(data[key] ?? 0);
                animateCounter(el, target, format);
            });
        })
        .catch(() => {});
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.kpi-value[data-count]').forEach(el => {
        const target = Number(el.dataset.count || 0);
        animateCounter(el, target, el.dataset.format || 'number');
    });

    refreshDashboardStats();
    setInterval(refreshDashboardStats, 30000);
});

function runDiagnostic() {
    const btn = document.getElementById('diagBtn');
    const loading = document.getElementById('diagLoading');
    const result = document.getElementById('diagResult');

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Analyse...';
    loading.style.display = 'block';
    result.innerHTML = '';

    fetch('DashboardController.php?action=diagnostic')
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                result.innerHTML = '<div style="text-align:center;padding:20px;color:#ff9cab;"><i class="bi bi-exclamation-triangle"></i> ' + data.error + '</div>';
                return;
            }
            renderDiagnostic(data);
        })
        .catch(err => {
            result.innerHTML = '<div style="text-align:center;padding:20px;color:#ff9cab;"><i class="bi bi-exclamation-triangle"></i> Erreur: ' + err.message + '</div>';
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Relancer';
            loading.style.display = 'none';
        });
}

function renderDiagnostic(data) {
    const result = document.getElementById('diagResult');
    const diagTypes = {
        warning: 'diag-alert-warning',
        danger: 'diag-alert-danger',
        info: 'diag-alert-info',
        success: 'diag-alert-success'
    };

    let html = '';

    // Score header
    html += '<div class="diag-header">';
    html += '    <div class="diag-title"><i class="bi bi-robot"></i> Résultat de l\'analyse</div>';
    html += '    <div class="score-circle" style="border-color:' + data.score_color + ';">';
    html += '        <div class="score-num" style="color:' + data.score_color + ';">' + data.score + '</div>';
    html += '        <div class="score-lbl">' + data.score_label + '</div>';
    html += '    </div>';
    html += '</div>';

    // Trends
    if (data.trends && data.trends.length > 0) {
        html += '<div style="margin-top:18px;margin-bottom:18px;">';
        html += '    <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);letter-spacing:.08em;margin-bottom:10px;">';
        html += '        <i class="bi bi-graph-up" style="color:var(--accent);margin-right:4px;"></i> Tendances détectées';
        html += '    </div>';
        data.trends.forEach(function(t) {
            var icoColor = t.type === 'success' ? '#90f1bc' : (t.type === 'warning' ? '#ffd66e' : '#8eeaff');
            html += '    <div class="diag-trend">';
            html += '        <i class="bi bi-' + t.icon + '" style="color:' + icoColor + ';"></i>';
            html += '        <div class="diag-trend-body">';
            html += '            <div class="diag-trend-title">' + escHtml(t.title) + '</div>';
            html += '            <div class="diag-trend-msg">' + escHtml(t.message) + '</div>';
            html += '        </div>';
            html += '    </div>';
        });
        html += '</div>';
    }

    // Alerts
    if (data.alerts && data.alerts.length > 0) {
        html += '<div style="margin-bottom:18px;">';
        html += '    <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);letter-spacing:.08em;margin-bottom:10px;">';
        html += '        <i class="bi bi-exclamation-circle" style="color:#ff9cab;margin-right:4px;"></i> Alertes (' + data.alerts.length + ')';
        html += '    </div>';
        data.alerts.forEach(function(a) {
            var cls = diagTypes[a.type] || 'diag-alert-info';
            html += '    <div class="diag-alert ' + cls + '">';
            html += '        <div class="diag-alert-icon"><i class="bi bi-' + a.icon + '"></i></div>';
            html += '        <div class="diag-alert-body">';
            html += '            <div class="diag-alert-title">' + escHtml(a.title) + '</div>';
            html += '            <div class="diag-alert-msg">' + escHtml(a.message) + '</div>';
            html += '        </div>';
            html += '    </div>';
        });
        html += '</div>';
    }

    // Recommendations
    if (data.recommendations && data.recommendations.length > 0) {
        html += '<div>';
        html += '    <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-secondary);letter-spacing:.08em;margin-bottom:10px;">';
        html += '        <i class="bi bi-lightbulb" style="color:#ffd66e;margin-right:4px;"></i> Recommandations';
        html += '    </div>';
        data.recommendations.forEach(function(r) {
            html += '    <div class="diag-rec"><i class="bi bi-arrow-right-circle"></i>' + escHtml(r) + '</div>';
        });
        html += '</div>';
    }

    result.innerHTML = html;
}

function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

// ═══ CHART.JS INITIALIZATION ═══
const CHART_COLORS = {
    auto:       '#00c2ff',
    habitation: '#ff9900',
    sante:      '#00d68f',
    vie:        '#a855f7',
    blue:       'rgba(0, 194, 255, 0.8)',
    orange:     'rgba(255, 153, 0, 0.8)',
    green:      'rgba(0, 214, 143, 0.8)',
    red:        'rgba(230, 57, 70, 0.8)',
    purple:     'rgba(168, 85, 247, 0.8)',
};

const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: { color: 'rgba(255,255,255,0.7)', font: { family: 'DM Sans', size: 11 } }
        }
    },
    scales: {
        x: { ticks: { color: 'rgba(255,255,255,0.5)', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.04)' } },
        y: { ticks: { color: 'rgba(255,255,255,0.5)', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.06)' } }
    }
};

// Sinistres par mois (Line chart)
function loadChartSinistresMonthly() {
    fetch('<?= $base ?>/api.php?action=chart_sinistres_monthly')
        .then(r => r.json())
        .then(data => {
            const labels = data.map(d => {
                const [y, m] = d.mois.split('-');
                return ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'][parseInt(m) - 1] + ' ' + y.slice(2);
            });
            const values = data.map(d => d.total);

            new Chart(document.getElementById('chartSinistresMonthly'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Sinistres',
                        data: values,
                        borderColor: CHART_COLORS.red,
                        backgroundColor: 'rgba(230, 57, 70, 0.1)',
                        borderWidth: 2.5,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointBackgroundColor: CHART_COLORS.red,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: { ...chartDefaults }
            });
        })
        .catch(() => {});
}

// Contrats par type (Doughnut chart)
function loadChartContractsType() {
    fetch('<?= $base ?>/api.php?action=chart_contracts_by_type')
        .then(r => r.json())
        .then(data => {
            const labels = data.map(d => d.libelle || 'Autre');
            const values = data.map(d => d.total);
            const colors = data.map(d => CHART_COLORS[d.libelle] || CHART_COLORS.purple);

            new Chart(document.getElementById('chartContractsType'), {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderColor: 'rgba(30,33,48,0.8)',
                        borderWidth: 3,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: 'rgba(255,255,255,0.7)', font: { family: 'DM Sans', size: 11 }, padding: 16, usePointStyle: true }
                        }
                    }
                }
            });
        })
        .catch(() => {});
}

// Fraud distribution (Bar chart)
function loadChartFraudDistribution() {
    fetch('<?= $base ?>/api.php?action=chart_fraud_distribution')
        .then(r => r.json())
        .then(data => {
            const labels = data.map(d => d.tranche);
            const values = data.map(d => d.total);
            const colors = ['rgba(46, 196, 182, 0.7)', 'rgba(239, 159, 39, 0.7)', 'rgba(230, 57, 70, 0.7)'];

            new Chart(document.getElementById('chartFraudDistribution'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Analyses',
                        data: values,
                        backgroundColor: colors,
                        borderColor: colors.map(c => c.replace('0.7', '1')),
                        borderWidth: 1.5,
                        borderRadius: 8,
                        maxBarThickness: 60
                    }]
                },
                options: { ...chartDefaults }
            });
        })
        .catch(() => {});
}

// ═══ TUNISIA MAP — REGION BUBBLES ═══
const REGION_POSITIONS = {
    'Tunis':     { top: '15%', left: '58%' },
    'Sfax':      { top: '55%', left: '48%' },
    'Sousse':    { top: '38%', left: '56%' },
    'Bizerte':   { top: '8%',  left: '52%' },
    'Gabès':     { top: '65%', left: '42%' },
    'Kairouan':  { top: '40%', left: '42%' },
    'Monastir':  { top: '42%', left: '58%' },
    'Nabeul':    { top: '22%', left: '62%' },
};

function loadTunisiaMap() {
    fetch('<?= $base ?>/api.php?action=sinistres_by_region')
        .then(r => r.json())
        .then(regions => {
            const container = document.getElementById('regionBubbles');
            if (!container) return;
            container.innerHTML = '';

            const maxTotal = Math.max(...regions.map(r => parseInt(r.total)), 1);

            regions.forEach(region => {
                const pos = REGION_POSITIONS[region.gouvernorat];
                if (!pos) return;

                const total = parseInt(region.total);
                const size = Math.max(24, Math.min(50, 24 + (total / maxTotal) * 26));

                const bubble = document.createElement('div');
                bubble.style.cssText = `
                    position:absolute; top:${pos.top}; left:${pos.left};
                    width:${size}px; height:${size}px;
                    border-radius:50%;
                    background: radial-gradient(circle, rgba(230,57,70,0.7), rgba(230,57,70,0.3));
                    border: 2px solid rgba(230,57,70,0.8);
                    display:flex; align-items:center; justify-content:center;
                    font-size:11px; font-weight:800; color:#fff;
                    transform:translate(-50%,-50%);
                    pointer-events:all; cursor:pointer;
                    transition: transform 0.2s ease, box-shadow 0.2s ease;
                    animation: livePulse 2s infinite;
                `;
                bubble.textContent = total;
                bubble.title = region.gouvernorat + ' — ' + total + ' sinistre(s)';

                bubble.addEventListener('mouseenter', function() {
                    this.style.transform = 'translate(-50%,-50%) scale(1.3)';
                    this.style.boxShadow = '0 0 20px rgba(230,57,70,0.6)';
                    this.style.zIndex = '10';
                });
                bubble.addEventListener('mouseleave', function() {
                    this.style.transform = 'translate(-50%,-50%) scale(1)';
                    this.style.boxShadow = 'none';
                    this.style.zIndex = '1';
                });

                container.appendChild(bubble);
            });
        })
        .catch(() => {});
}

// Load charts on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Wait for Chart.js to load
    if (typeof Chart !== 'undefined') {
        loadChartSinistresMonthly();
        loadChartContractsType();
        loadChartFraudDistribution();
    } else {
        setTimeout(() => {
            loadChartSinistresMonthly();
            loadChartContractsType();
            loadChartFraudDistribution();
        }, 500);
    }
    loadTunisiaMap();
});
</script>
</body>
</html>

