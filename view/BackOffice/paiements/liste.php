<?php
declare(strict_types=1);

$BASE_URL = (defined('BASE_URL') ? BASE_URL : '');

if (!isset($paiements)) {
    include_once __DIR__ . '/../../../config.php';
    $db = config::getConnexion();

    $paiements = $db->query("
        SELECT p.*, o.nom_offre, o.type_offre
        FROM paiement p
        LEFT JOIN offre o ON p.id_offre = o.id_offre
        ORDER BY p.date_paiement DESC
    ")->fetchAll();

    $stats = $db->query("
        SELECT
            COUNT(*)                    AS total,
            SUM(statut='en_attente')    AS en_attente,
            SUM(statut='valide')        AS valides,
            SUM(statut='refuse')        AS refuses,
            SUM(statut='rembourse')     AS rembourses,
            SUM(CASE WHEN statut='valide' THEN montant ELSE 0 END) AS chiffre_affaires
        FROM paiement
    ")->fetch() ?: [];

    $echeances = $db->query("
        SELECT reference, date_echeance
        FROM paiement
        WHERE date_echeance BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
        AND statut = 'valide'
        ORDER BY date_echeance ASC
    ")->fetchAll();

    $message = $_GET['message'] ?? '';
    $erreur  = $_GET['erreur']  ?? '';
} else {
    $message = $message ?? ($_GET['message'] ?? '');
    $erreur  = $erreur  ?? ($_GET['erreur']  ?? '');
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatMoney($value): string
{
    return number_format((float)$value, 3, '.', ' ') . ' TND';
}

function formatDateFr(?string $date): string
{
    if (empty($date)) {
        return '—';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '—';
    }

    return date('d/m/Y', $timestamp);
}

function formatTimeFr(?string $date): string
{
    if (empty($date)) {
        return '';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '';
    }

    return date('H:i', $timestamp);
}

function ucfirstSafe(?string $value): string
{
    $value = (string)$value;
    if ($value === '') {
        return '—';
    }
    return ucfirst($value);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Gestion des paiements — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e($BASE_URL) ?>/view/BackOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= e($BASE_URL) ?>/view/BackOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= e($BASE_URL) ?>/view/BackOffice/assets/css/layout.css">
    <link rel="stylesheet" href="<?= e($BASE_URL) ?>/view/BackOffice/assets/css/client.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .page-hero {
            position: relative;
            margin-bottom: 24px;
            padding: 26px 28px 22px;
            border-radius: 22px;
            background:
                radial-gradient(circle at top right, rgba(0,180,216,.14), transparent 35%),
                radial-gradient(circle at bottom left, rgba(16,185,129,.08), transparent 40%),
                linear-gradient(135deg, rgba(255,255,255,.05), rgba(255,255,255,.03));
            border: 1px solid rgba(255,255,255,.08);
            overflow: hidden;
        }

        .page-hero::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(180deg, rgba(255,255,255,.03), transparent 35%);
        }

        .page-hero-head {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            flex-wrap: wrap;
        }

        .page-hero-title {
            margin: 0;
            font-size: 26px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.3px;
        }

        .page-hero-sub {
            margin: 8px 0 0;
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.6;
            max-width: 640px;
        }

        .page-hero-pills {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .page-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 13px;
            border-radius: 999px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.08);
            color: #fff;
            font-size: 12px;
            font-weight: 600;
        }

        .page-pill i { color: var(--accent); }

        .hero-side {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            flex-shrink: 0;
        }

        .hero-mini-card {
            min-width: 130px;
            padding: 14px 16px;
            border-radius: 16px;
            text-align: center;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.08);
            transition: .2s;
        }

        .hero-mini-card:hover {
            background: rgba(255,255,255,.08);
        }

        .hero-mini-card strong {
            display: block;
            color: #fff;
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .hero-mini-card span {
            color: var(--text-secondary);
            font-size: 12px;
        }

        .stats-grid-pay {
            display: grid;
            grid-template-columns: repeat(4,1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-pay {
            position: relative;
            overflow: hidden;
            padding: 18px 20px 16px;
            border-radius: 18px;
            border: 1px solid rgba(255,255,255,.07);
            background: rgba(255,255,255,.04);
            transition: transform .2s, box-shadow .2s;
        }

        .stat-pay:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0,0,0,.2);
        }

        .stat-pay::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            opacity: .9;
        }

        .stat-pay.blue::before  { background: linear-gradient(90deg,#3b82f6,#60a5fa); }
        .stat-pay.green::before { background: linear-gradient(90deg,#10b981,#34d399); }
        .stat-pay.gold::before  { background: linear-gradient(90deg,#f59e0b,#fbbf24); }
        .stat-pay.red::before   { background: linear-gradient(90deg,#ef4444,#f87171); }

        .stat-pay .ic {
            width: 42px;
            height: 42px;
            border-radius: 13px;
            display: grid;
            place-items: center;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.08);
            color: #fff;
            font-size: 18px;
            margin-bottom: 12px;
        }

        .stat-pay .val {
            font-size: 28px;
            font-weight: 800;
            color: #fff;
            line-height: 1;
            margin-bottom: 5px;
        }

        .stat-pay .lbl {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .stat-pay .sub {
            font-size: 11px;
            color: var(--accent);
            margin-top: 4px;
        }

        .ca-banner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            padding: 18px 22px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(0,180,216,.1), rgba(16,185,129,.06));
            border: 1px solid rgba(0,180,216,.2);
            margin-bottom: 22px;
        }

        .ca-banner-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .ca-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: rgba(0,180,216,.15);
            border: 1px solid rgba(0,180,216,.25);
            color: var(--accent);
            font-size: 20px;
            flex-shrink: 0;
        }

        .ca-title {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }

        .ca-value {
            font-family: var(--font-display);
            font-size: 22px;
            font-weight: 800;
            color: #fff;
        }

        .ca-right {
            font-size: 12px;
            color: var(--text-secondary);
            text-align: right;
        }

        .ca-right strong {
            display: block;
            color: #fff;
            font-size: 14px;
            margin-bottom: 3px;
        }

        .echeance-alert {
            background: rgba(245,158,11,.07);
            border: 1px solid rgba(245,158,11,.2);
            border-radius: 16px;
            padding: 14px 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .echeance-alert i {
            color: #fbbf24;
            font-size: 18px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .echeance-alert-title {
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 6px;
        }

        .echeance-list {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .echeance-chip {
            padding: 5px 12px;
            border-radius: 999px;
            background: rgba(245,158,11,.12);
            border: 1px solid rgba(245,158,11,.2);
            color: #fcd34d;
            font-size: 12px;
            font-weight: 600;
        }

        .filter-section {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 18px;
            align-items: center;
        }

        .filter-tabs {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            flex: 1;
        }

        .filter-tab {
            padding: 8px 16px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.04);
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            font-family: var(--font-body);
            transition: .2s ease;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .filter-tab:hover {
            background: rgba(255,255,255,.08);
            color: #fff;
        }

        .filter-tab.active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
            box-shadow: 0 4px 14px rgba(0,180,216,.3);
        }

        .filter-tab .count {
            padding: 2px 7px;
            border-radius: 999px;
            background: rgba(255,255,255,.2);
            font-size: 11px;
            font-weight: 700;
        }

        .filter-search {
            position: relative;
            flex-shrink: 0;
        }

        .filter-search i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 14px;
        }

        .filter-search input {
            height: 40px;
            padding: 0 14px 0 38px;
            border-radius: 13px;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.05);
            color: #fff;
            font-size: 13px;
            font-family: var(--font-body);
            outline: none;
            width: 240px;
            transition: .2s;
        }

        .filter-search input:focus {
            border-color: rgba(0,180,216,.35);
            box-shadow: 0 0 0 3px rgba(0,180,216,.08);
        }

        .filter-search input::placeholder {
            color: var(--text-secondary);
        }

        .table-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            backdrop-filter: blur(20px);
            overflow: hidden;
        }

        .table-card-head {
            padding: 18px 24px;
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .table-card-title {
            font-family: var(--font-display);
            font-size: 16px;
            font-weight: 700;
            color: #fff;
        }

        .table-card-sub {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 3px;
        }

        .admin-table-wrap {
            overflow-x: auto;
        }

        .table-pay {
            width: 100%;
            border-collapse: collapse;
        }

        .table-pay thead th {
            background: rgba(255,255,255,.03);
            color: #cdd6f4;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            border-bottom: 1px solid rgba(255,255,255,.07);
            white-space: nowrap;
            padding: 13px 18px;
        }

        .table-pay thead th.sortable-pay {
            cursor: pointer;
            user-select: none;
            transition: .15s ease;
        }
        .table-pay thead th.sortable-pay:hover {
            background: rgba(0,180,216,.1);
            color: #fff;
        }
        .table-pay thead th .sort-ico-pay {
            display: inline-block;
            margin-left: 6px;
            opacity: .35;
            font-size: 10px;
            transition: .15s ease;
        }
        .table-pay thead th.sort-asc-pay .sort-ico-pay,
        .table-pay thead th.sort-desc-pay .sort-ico-pay {
            opacity: 1;
            color: var(--accent);
        }

        .table-pay tbody td {
            border-bottom: 1px solid rgba(255,255,255,.05);
            vertical-align: middle;
            padding: 13px 18px;
        }

        .table-pay tbody tr {
            transition: .18s ease;
        }

        .table-pay tbody tr:hover {
            background: rgba(255,255,255,.03);
        }

        .table-pay tbody tr:last-child td {
            border-bottom: none;
        }

        .ref-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 11px;
            border-radius: 8px;
            background: rgba(0,180,216,.1);
            border: 1px solid rgba(0,180,216,.2);
            color: var(--accent);
            font-family: monospace;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .5px;
        }

        .offre-cell {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .offre-dot {
            width: 36px;
            height: 36px;
            border-radius: 11px;
            display: grid;
            place-items: center;
            font-size: 15px;
            color: #fff;
            flex-shrink: 0;
        }

        .offre-dot.auto       { background: rgba(59,130,246,.2); }
        .offre-dot.sante      { background: rgba(16,185,129,.2); }
        .offre-dot.habitation { background: rgba(245,158,11,.2); }
        .offre-dot.vie        { background: rgba(236,72,153,.2); }

        .offre-name {
            font-weight: 600;
            color: #fff;
            font-size: 13px;
            margin-bottom: 2px;
        }

        .offre-type {
            font-size: 11px;
            color: var(--text-secondary);
        }

        .montant-val {
            font-weight: 700;
            color: #fff;
            font-size: 14px;
        }

        .montant-per {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        .badge-methode {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            color: var(--text-primary);
        }

        .badge-statut {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-statut.en_attente { background: rgba(245,158,11,.14); color: #fcd34d; }
        .badge-statut.valide     { background: rgba(16,185,129,.14); color: #86efac; }
        .badge-statut.refuse     { background: rgba(239,68,68,.14); color: #fca5a5; }
        .badge-statut.rembourse  { background: rgba(0,180,216,.14); color: #7dd3fc; }

        .echeance-soon {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            color: #fbbf24;
            margin-top: 3px;
        }

        .action-wrap {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .action-btn {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #fff;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.08);
            font-size: 14px;
            transition: .2s;
        }

        .action-btn:hover {
            background: rgba(255,255,255,.1);
            transform: translateY(-1px);
        }

        .action-btn.validate {
            background: rgba(16,185,129,.12);
            color: #86efac;
            border-color: rgba(16,185,129,.2);
        }

        .action-btn.validate:hover {
            background: rgba(16,185,129,.22);
        }

        .action-btn.refuse {
            background: rgba(239,68,68,.12);
            color: #fca5a5;
            border-color: rgba(239,68,68,.2);
        }

        .action-btn.refuse:hover {
            background: rgba(239,68,68,.22);
        }

        .action-btn.refund {
            background: rgba(0,180,216,.12);
            color: #7dd3fc;
            border-color: rgba(0,180,216,.2);
        }

        .action-btn.refund:hover {
            background: rgba(0,180,216,.22);
        }

        .empty-box {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-box i {
            font-size: 42px;
            display: block;
            margin-bottom: 12px;
            opacity: .6;
        }

        .empty-box strong {
            display: block;
            color: #fff;
            margin-bottom: 8px;
            font-size: 18px;
        }

        .no-results {
            display: none;
            text-align: center;
            padding: 28px;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .alert-ok {
            background: rgba(16,185,129,.08);
            border: 1px solid rgba(16,185,129,.25);
            border-radius: 14px;
            padding: 13px 18px;
            color: #86efac;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .alert-err {
            background: rgba(239,68,68,.08);
            border: 1px solid rgba(239,68,68,.25);
            border-radius: 14px;
            padding: 13px 18px;
            color: #fca5a5;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        @media(max-width:1100px) {
            .stats-grid-pay { grid-template-columns: repeat(2,1fr); }
        }

        @media(max-width:768px) {
            .hero-side { display: none; }
            .filter-search input { width: 180px; }
        }

        @media(max-width:600px) {
            .stats-grid-pay { grid-template-columns: 1fr; }
            .filter-tabs { flex-direction: column; }
        }

        @media print {
            body * { visibility: hidden !important; }
            #printAreaPay { display: block !important; position: absolute !important; left: 0; top: 0; width: 100%; background: #fff !important; color: #000 !important; padding: 20px !important; font-family: Arial, sans-serif !important; }
            #printAreaPay, #printAreaPay * { visibility: visible !important; }
            #printAreaPay table { width:100%; border-collapse:collapse; font-size:11px; }
            #printAreaPay th, #printAreaPay td {
                border: 1px solid #999 !important;
                padding: 7px 9px !important;
                color: #000 !important;
            }
            #printAreaPay th {
                background: #FF6B1A !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                text-align: left;
            }
            #printAreaPay .print-header {
                border-bottom: 3px solid #FF6B1A !important;
                padding-bottom: 12px;
                margin-bottom: 18px;
            }
            #printAreaPay .print-brand {
                font-size: 24px;
                font-weight: 800;
                color: #1A3A7A !important;
                margin: 0;
            }
            #printAreaPay .print-brand span { color: #FF6B1A !important; }
            #printAreaPay .print-info {
                color: #666 !important;
                font-size: 12px;
                margin-top: 4px;
            }
        }
        #printAreaPay { display: none !important; }

        .export-menu-pay.show {
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateY(0) !important;
        }
        .export-menu-pay button:hover {
            background: rgba(255,255,255,.06);
        }
    </style>
</head>
<body>
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">
<?php include __DIR__ . '/../assets/includes/sidebar.php'; ?>

    <main class="main">
        <div class="topbar">
            <div>
                <div class="topbar-title">Gestion des paiements</div>
                <div class="topbar-sub" id="topbarDate"></div>
            </div>
            <div class="topbar-actions">
                <a href="#" class="topbar-btn" title="Notifications">
                    <i class="bi bi-bell"></i>
                    <span class="notif-dot"></span>
                </a>
            </div>
        </div>

        <div class="content">
            <div class="page-breadcrumb" style="margin-bottom:24px;">
                <i class="bi bi-house"></i>
                <span>Admin</span>
                <i class="bi bi-chevron-right" style="font-size:10px"></i>
                <span>Paiements</span>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert-ok">
                    <i class="bi bi-check-circle-fill"></i>
                    <strong><?= e($message) ?></strong>
                </div>
            <?php endif; ?>

            <?php if ($erreur !== ''): ?>
                <div class="alert-err">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong><?= e($erreur) ?></strong>
                </div>
            <?php endif; ?>

            <section class="page-hero">
                <div class="page-hero-head">
                    <div>
                        <h1 class="page-hero-title">Gestion des paiements</h1>
                        <p class="page-hero-sub">
                            Suivez en temps réel tous les paiements effectués par vos clients,
                            validez ou refusez les transactions en attente et consultez
                            les statistiques financières du module.
                        </p>
                        <div class="page-hero-pills">
                            <span class="page-pill"><i class="bi bi-shield-check"></i> Transactions sécurisées</span>
                            <span class="page-pill"><i class="bi bi-graph-up"></i> Suivi en temps réel</span>
                            <span class="page-pill"><i class="bi bi-lightning-charge"></i> Actions rapides</span>
                        </div>
                    </div>

                    <div class="hero-side">
                        <div class="hero-mini-card">
                            <strong><?= (int)($stats['total'] ?? 0) ?></strong>
                            <span>Total paiements</span>
                        </div>
                        <div class="hero-mini-card">
                            <strong style="color:#fcd34d;"><?= (int)($stats['en_attente'] ?? 0) ?></strong>
                            <span>En attente</span>
                        </div>
                        <div class="hero-mini-card">
                            <strong style="color:#86efac;"><?= (int)($stats['valides'] ?? 0) ?></strong>
                            <span>Validés</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- MAIN TABS -->
            <div class="main-tabs" style="display:flex; gap:10px; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:15px;">
                <button class="main-tab active" onclick="switchMainTab('dashboard')" style="background:rgba(255,255,255,0.05); color:#fff; border:1px solid rgba(255,255,255,0.1); padding:10px 20px; border-radius:12px; cursor:pointer; font-weight:bold; transition:0.2s;"><i class="bi bi-pie-chart-fill"></i> Tableau de bord</button>
                <button class="main-tab" onclick="switchMainTab('historique')" style="background:transparent; color:var(--text-secondary); border:1px solid transparent; padding:10px 20px; border-radius:12px; cursor:pointer; font-weight:bold; transition:0.2s;"><i class="bi bi-list-ul"></i> Historique des paiements</button>
                <button class="main-tab" onclick="switchMainTab('retards')" style="background:transparent; color:var(--text-secondary); border:1px solid transparent; padding:10px 20px; border-radius:12px; cursor:pointer; font-weight:bold; transition:0.2s;"><i class="bi bi-exclamation-triangle-fill" style="color:#ef4444"></i> Paiements en retard <span style="background:#ef4444; color:#fff; padding:2px 8px; border-radius:10px; font-size:11px; margin-left:5px;"><?= count($retardsList ?? []) ?></span></button>
            </div>

            <div id="tab-dashboard" class="main-tab-content">
                <!-- DASHBOARD CONTENT -->
                <div class="stats-grid-pay">
                    <div class="stat-pay blue">
                        <div class="ic"><i class="bi bi-graph-up-arrow"></i></div>
                        <div class="val" id="kpi-ca-mois">-- TND</div>
                        <div class="lbl">CA ce mois-ci</div>
                    </div>
                    <div class="stat-pay green">
                        <div class="ic"><i class="bi bi-cash-stack"></i></div>
                        <div class="val" id="kpi-ca-annee">-- TND</div>
                        <div class="lbl">CA cumulé (Année)</div>
                    </div>
                    <div class="stat-pay red">
                        <div class="ic"><i class="bi bi-exclamation-triangle"></i></div>
                        <div class="val" id="kpi-retards">--</div>
                        <div class="lbl">Paiements en retard</div>
                    </div>
                    <div class="stat-pay gold">
                        <div class="ic"><i class="bi bi-percent"></i></div>
                        <div class="val" id="kpi-recouvrement">-- %</div>
                        <div class="lbl">Taux de recouvrement</div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:2fr 1fr; gap:20px; margin-bottom:20px;">
                    <div class="table-card" style="padding:20px;">
                        <h3 style="margin-top:0; color:#fff; font-size:16px;">CA Mensuel (12 derniers mois)</h3>
                        <canvas id="caChart" style="max-height:300px;"></canvas>
                    </div>
                    <div class="table-card" style="padding:20px;">
                        <h3 style="margin-top:0; color:#fff; font-size:16px;">Répartition par offre</h3>
                        <canvas id="repartChart" style="max-height:300px;"></canvas>
                    </div>
                </div>
                <div class="table-card" style="padding:20px; margin-bottom:20px;">
                    <h3 style="margin-top:0; color:#fff; font-size:16px;">Paiements à temps vs. retards</h3>
                    <canvas id="punctualiteChart" style="max-height:320px;"></canvas>
                </div>
            </div>

            <div id="tab-historique" class="main-tab-content" style="display:none;">
                <div class="stats-grid-pay">
                    <div class="stat-pay blue">
                        <div class="ic"><i class="bi bi-credit-card"></i></div>
                    <div class="val"><?= (int)($stats['total'] ?? 0) ?></div>
                    <div class="lbl">Total paiements</div>
                </div>

                <div class="stat-pay gold">
                    <div class="ic"><i class="bi bi-hourglass-split"></i></div>
                    <div class="val"><?= (int)($stats['en_attente'] ?? 0) ?></div>
                    <div class="lbl">En attente de validation</div>
                    <?php if ((int)($stats['en_attente'] ?? 0) > 0): ?>
                        <div class="sub"><i class="bi bi-exclamation-circle"></i> À traiter</div>
                    <?php endif; ?>
                </div>

                <div class="stat-pay green">
                    <div class="ic"><i class="bi bi-check-circle"></i></div>
                    <div class="val"><?= (int)($stats['valides'] ?? 0) ?></div>
                    <div class="lbl">Paiements validés</div>
                </div>

                <div class="stat-pay red">
                    <div class="ic"><i class="bi bi-x-circle"></i></div>
                    <div class="val"><?= (int)($stats['refuses'] ?? 0) + (int)($stats['rembourses'] ?? 0) ?></div>
                    <div class="lbl">Refusés / Remboursés</div>
                </div>
            </div>

            <?php if (!empty($stats['chiffre_affaires']) || !empty($stats['ca_ce_mois'])): ?>
                <div class="ca-banner">
                    <div class="ca-banner-left">
                        <div class="ca-icon"><i class="bi bi-graph-up-arrow"></i></div>
                        <div>
                            <div class="ca-title">Chiffre d'affaires total</div>
                            <div class="ca-value"><?= formatMoney($stats['chiffre_affaires'] ?? 0) ?></div>
                        </div>
                    </div>
                    <div class="ca-right">
                        <strong><?= formatMoney($stats['ca_ce_mois'] ?? 0) ?></strong>
                        Ce mois-ci
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($echeances)): ?>
                <div class="echeance-alert">
                    <i class="bi bi-alarm-fill"></i>
                    <div>
                        <div class="echeance-alert-title">
                            <?= count($echeances) ?> échéance(s) dans les 3 prochains jours
                        </div>
                        <div class="echeance-list">
                            <?php foreach ($echeances as $eItem): ?>
                                <span class="echeance-chip">
                                    <i class="bi bi-calendar-event"></i>
                                    <?= e($eItem['reference'] ?? '') ?> — <?= formatDateFr($eItem['date_echeance'] ?? null) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="filter-section">
                <div class="filter-tabs">
                    <button class="filter-tab active" id="tab-tous" onclick="filtrerStatut('', 'tab-tous')">
                        <i class="bi bi-list-ul"></i> Tous
                        <span class="count"><?= count($paiements) ?></span>
                    </button>
                    <button class="filter-tab" id="tab-en_attente" onclick="filtrerStatut('en_attente', 'tab-en_attente')">
                        <i class="bi bi-hourglass-split"></i> En attente
                        <span class="count"><?= (int)($stats['en_attente'] ?? 0) ?></span>
                    </button>
                    <button class="filter-tab" id="tab-valide" onclick="filtrerStatut('valide', 'tab-valide')">
                        <i class="bi bi-check-circle"></i> Validés
                        <span class="count"><?= (int)($stats['valides'] ?? 0) ?></span>
                    </button>
                    <button class="filter-tab" id="tab-refuse" onclick="filtrerStatut('refuse', 'tab-refuse')">
                        <i class="bi bi-x-circle"></i> Refusés
                        <span class="count"><?= (int)($stats['refuses'] ?? 0) ?></span>
                    </button>
                    <button class="filter-tab" id="tab-rembourse" onclick="filtrerStatut('rembourse', 'tab-rembourse')">
                        <i class="bi bi-arrow-counterclockwise"></i> Remboursés
                        <span class="count"><?= (int)($stats['rembourses'] ?? 0) ?></span>
                    </button>
                </div>

                <div class="filter-search">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchPay" placeholder="Référence, offre...">
                </div>
            </div>

            <div class="filter-section" style="margin-bottom:14px;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <label style="font-size:12px;color:var(--text-secondary);font-weight:700;">Du</label>
                    <input type="date" id="dateDebut" value="<?= e($dateDebut) ?>" style="height:36px;padding:0 12px;border-radius:10px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.05);color:#fff;font-size:13px;font-family:var(--font-body);">
                    <label style="font-size:12px;color:var(--text-secondary);font-weight:700;">Au</label>
                    <input type="date" id="dateFin" value="<?= e($dateFin) ?>" style="height:36px;padding:0 12px;border-radius:10px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.05);color:#fff;font-size:13px;font-family:var(--font-body);">
                    <select id="filterMethode" style="height:36px;padding:0 12px;border-radius:10px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.05);color:#fff;font-size:13px;font-family:var(--font-body);">
                        <option value="">Toutes méthodes</option>
                        <option value="carte" <?= $methode === 'carte' ? 'selected' : '' ?>>Carte</option>
                        <option value="virement" <?= $methode === 'virement' ? 'selected' : '' ?>>Virement</option>
                        <option value="mobile" <?= $methode === 'mobile' ? 'selected' : '' ?>>Mobile</option>
                    </select>
                    <button class="btn-reset-filter" onclick="resetFilters()" type="button" style="height:36px;padding:0 14px;border-radius:10px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.05);color:var(--text-secondary);cursor:pointer;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:5px;">
                        <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                    </button>
                </div>

                <div class="export-wrapper" style="position:relative;">
                    <button class="btn-export-pay" type="button" onclick="toggleExportPay()" style="height:36px;padding:0 16px;border-radius:10px;border:1px solid rgba(0,180,216,.3);background:rgba(0,180,216,.12);color:var(--accent);cursor:pointer;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:7px;">
                        <i class="bi bi-download"></i> Exporter <i class="bi bi-chevron-down" style="font-size:10px"></i>
                    </button>
                    <div class="export-menu-pay" id="exportMenuPay" style="position:absolute;top:calc(100% + 6px);right:0;min-width:200px;background:#0e1c33;border:1px solid rgba(255,255,255,.12);border-radius:12px;padding:6px;box-shadow:0 16px 40px rgba(0,0,0,.5);z-index:100;opacity:0;visibility:hidden;transform:translateY(-6px);transition:.2s ease;">
                        <button type="button" onclick="exportPayData('csv')" style="width:100%;padding:10px 12px;border-radius:8px;border:none;background:transparent;color:#fff;text-align:left;cursor:pointer;font-size:12px;font-weight:600;display:flex;align-items:center;gap:10px;transition:.15s;">
                            <i class="bi bi-filetype-csv" style="font-size:16px;color:#86efac;"></i> Exporter en CSV
                        </button>
                        <button type="button" onclick="exportPayData('excel')" style="width:100%;padding:10px 12px;border-radius:8px;border:none;background:transparent;color:#fff;text-align:left;cursor:pointer;font-size:12px;font-weight:600;display:flex;align-items:center;gap:10px;transition:.15s;">
                            <i class="bi bi-file-earmark-excel-fill" style="font-size:16px;color:#6ee7b7;"></i> Exporter en Excel
                        </button>
                        <button type="button" onclick="exportPayData('pdf')" style="width:100%;padding:10px 12px;border-radius:8px;border:none;background:transparent;color:#fff;text-align:left;cursor:pointer;font-size:12px;font-weight:600;display:flex;align-items:center;gap:10px;transition:.15s;">
                            <i class="bi bi-file-earmark-pdf-fill" style="font-size:16px;color:#fca5a5;"></i> Exporter en PDF
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-card">
                <div class="table-card-head">
                    <div>
                        <div class="table-card-title">Liste des paiements</div>
                        <div class="table-card-sub">
                            <span id="countVisible"><?= count($paiements) ?></span> paiement(s) affiché(s)
                        </div>
                    </div>
                </div>

                <div class="admin-table-wrap">
                    <?php if (empty($paiements)): ?>
                        <div class="empty-box">
                            <i class="bi bi-inbox"></i>
                            <strong>Aucun paiement trouvé</strong>
                            <p>Les paiements effectués par les clients apparaîtront ici.</p>
                        </div>
                    <?php else: ?>
                        <table class="table-pay" id="paiementsTable">
                            <thead>
                                <tr>
                                    <th class="sortable-pay" data-key="reference" data-type="text">Référence <span class="sort-ico-pay">↕</span></th>
                                    <th class="sortable-pay" data-key="offre" data-type="text">Offre <span class="sort-ico-pay">↕</span></th>
                                    <th class="sortable-pay" data-key="montant" data-type="num">Montant <span class="sort-ico-pay">↕</span></th>
                                    <th class="sortable-pay" data-key="methode" data-type="text">Méthode <span class="sort-ico-pay">↕</span></th>
                                    <th class="sortable-pay" data-key="periodicite" data-type="text">Périodicité <span class="sort-ico-pay">↕</span></th>
                                    <th class="sortable-pay" data-key="statut" data-type="text">Statut <span class="sort-ico-pay">↕</span></th>
                                    <th class="sortable-pay" data-key="date" data-type="date">Date paiement <span class="sort-ico-pay">↕</span></th>
                                    <th class="sortable-pay" data-key="echeance" data-type="date">Échéance <span class="sort-ico-pay">↕</span></th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="payBody">
                            <?php foreach ($paiements as $p):
                                $statut = strtolower((string)($p['statut'] ?? ''));
                                $type   = strtolower((string)($p['type_offre'] ?? ''));
                                $icons  = [
                                    'auto' => 'bi-car-front',
                                    'sante' => 'bi-heart-pulse',
                                    'habitation' => 'bi-house-door',
                                    'vie' => 'bi-shield-check'
                                ];
                                $icon = $icons[$type] ?? 'bi-tags';

                                $methIcons = [
                                    'carte' => 'bi-credit-card-2-front',
                                    'virement' => 'bi-bank',
                                    'mobile' => 'bi-phone'
                                ];
                                $methode = strtolower((string)($p['methode'] ?? ''));
                                $methIcon = $methIcons[$methode] ?? 'bi-credit-card';

                                $search = mb_strtolower(
                                    (string)($p['reference'] ?? '') . ' ' .
                                    (string)($p['nom_offre'] ?? '') . ' ' .
                                    $type . ' ' .
                                    (string)($p['client_nom'] ?? '') . ' ' .
                                    (string)($p['client_prenom'] ?? '')
                                );

                                $dateTs = !empty($p['date_paiement']) ? strtotime((string)$p['date_paiement']) : 0;
                                $echeanceTs = !empty($p['date_echeance']) ? strtotime((string)$p['date_echeance']) : 0;

                                $echeanceSoon = false;
                                if (!empty($p['date_echeance'])) {
                                    $timestamp = strtotime((string)$p['date_echeance']);
                                    if ($timestamp !== false) {
                                        $diff = ($timestamp - time()) / 86400;
                                        $echeanceSoon = ($diff >= 0 && $diff <= 3);
                                    }
                                }
                            ?>
                                <tr class="pay-row"
                                    data-statut="<?= e($statut) ?>"
                                    data-search="<?= e($search) ?>"
                                    data-reference="<?= e($p['reference'] ?? '') ?>"
                                    data-offre="<?= e(mb_strtolower($p['nom_offre'] ?? '')) ?>"
                                    data-montant="<?= (float)($p['montant'] ?? 0) ?>"
                                    data-methode="<?= e($methode) ?>"
                                    data-periodicite="<?= e(mb_strtolower($p['periodicite'] ?? '')) ?>"
                                    data-date="<?= $dateTs ?>"
                                    data-echeance="<?= $echeanceTs ?>">

                                    <td>
                                        <span class="ref-badge">
                                            <i class="bi bi-hash"></i>
                                            <?= e($p['reference'] ?? '—') ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="offre-cell">
                                            <div class="offre-dot <?= e($type) ?>">
                                                <i class="bi <?= e($icon) ?>"></i>
                                            </div>
                                            <div>
                                                <div class="offre-name"><?= e($p['nom_offre'] ?? '—') ?></div>
                                                <div class="offre-type"><?= e(ucfirstSafe($type)) ?></div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="montant-val"><?= formatMoney($p['montant'] ?? 0) ?></div>
                                        <div class="montant-per"><?= e(ucfirstSafe($p['periodicite'] ?? '')) ?></div>
                                    </td>

                                    <td>
                                        <span class="badge-methode">
                                            <i class="bi <?= e($methIcon) ?>"></i>
                                            <?= e(ucfirstSafe($p['methode'] ?? '')) ?>
                                        </span>
                                    </td>

                                    <td style="color:var(--text-secondary);font-size:13px;">
                                        <?= e(ucfirstSafe($p['periodicite'] ?? '')) ?>
                                    </td>

                                    <td>
                                        <?php if ($statut === 'en_attente'): ?>
                                            <span class="badge-statut en_attente">
                                                <i class="bi bi-hourglass-split"></i> En attente
                                            </span>
                                        <?php elseif ($statut === 'valide'): ?>
                                            <span class="badge-statut valide">
                                                <i class="bi bi-check-circle-fill"></i> Validé
                                            </span>
                                        <?php elseif ($statut === 'refuse'): ?>
                                            <span class="badge-statut refuse">
                                                <i class="bi bi-x-circle-fill"></i> Refusé
                                            </span>
                                        <?php elseif ($statut === 'en_attente_remboursement'): ?>
                                            <span class="badge-statut en_attente" style="background:rgba(236,72,153,0.14); color:#f472b6;">
                                                <i class="bi bi-clock-history"></i> Remboursement en attente
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-statut rembourse">
                                                <i class="bi bi-arrow-counterclockwise"></i> Remboursé
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td style="font-size:12px;color:var(--text-secondary);">
                                        <?= formatDateFr($p['date_paiement'] ?? null) ?>
                                        <div style="font-size:11px;margin-top:2px;color:var(--text-secondary);opacity:.7;">
                                            <?= e(formatTimeFr($p['date_paiement'] ?? null)) ?>
                                        </div>
                                    </td>

                                    <td style="font-size:12px;color:var(--text-secondary);">
                                        <?= formatDateFr($p['date_echeance'] ?? null) ?>
                                        <?php if ($echeanceSoon): ?>
                                            <div class="echeance-soon">
                                                <i class="bi bi-alarm-fill"></i> Bientôt
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="action-wrap">
                                            <a href="<?= e($BASE_URL) ?>/controller/PaiementController.php?action=detail&id=<?= (int)($p['id_paiement'] ?? 0) ?>"
                                               class="action-btn" title="Voir le détail">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <a href="<?= e($BASE_URL) ?>/view/FrontOffice/recu_paiement.php?id=<?= (int)($p['id_paiement'] ?? 0) ?>"
                                               class="action-btn" title="Télécharger le reçu" target="_blank">
                                                <i class="bi bi-receipt"></i>
                                            </a>

                                            <?php if ($statut === 'en_attente'): ?>
                                                <a href="<?= e($BASE_URL) ?>/controller/PaiementController.php?action=valider&id=<?= (int)($p['id_paiement'] ?? 0) ?>"
                                                   class="action-btn validate" title="Valider"
                                                   onclick="return confirm('Valider ce paiement ?')">
                                                    <i class="bi bi-check2"></i>
                                                </a>

                                                <a href="<?= e($BASE_URL) ?>/controller/PaiementController.php?action=refuser&id=<?= (int)($p['id_paiement'] ?? 0) ?>"
                                                   class="action-btn refuse" title="Refuser">
                                                    <i class="bi bi-x-lg"></i>
                                                </a>
                                            <?php elseif ($statut === 'valide'): ?>
                                                <a href="<?= e($BASE_URL) ?>/controller/PaiementController.php?action=rembourser&id=<?= (int)($p['id_paiement'] ?? 0) ?>"
                                                   class="action-btn refund" title="Rembourser">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </a>
                                            <?php elseif ($statut === 'en_attente_remboursement'): ?>
                                                <?php require_once __DIR__ . '/../../../helpers/RoleHelper.php'; if (RoleHelper::isSuperAdmin()): ?>
                                                    <a href="<?= e($BASE_URL) ?>/controller/PaiementController.php?action=approuver_remboursement&id=<?= (int)($p['id_paiement'] ?? 0) ?>"
                                                       class="action-btn validate" title="Approuver Remboursement" onclick="return confirm('Approuver ce remboursement ?')">
                                                        <i class="bi bi-check2-all"></i>
                                                    </a>
                                                    <a href="<?= e($BASE_URL) ?>/controller/PaiementController.php?action=rejeter_remboursement&id=<?= (int)($p['id_paiement'] ?? 0) ?>"
                                                       class="action-btn refuse" title="Rejeter Remboursement" onclick="return confirm('Rejeter ce remboursement ?')">
                                                        <i class="bi bi-x-circle"></i>
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="no-results" id="noResults">
                            <i class="bi bi-search"></i> Aucun paiement trouvé pour ce filtre.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            </div> <!-- End tab-historique -->

            <div id="tab-retards" class="main-tab-content" style="display:none;">
                <div class="table-card">
                    <div class="table-card-head">
                        <div>
                            <div class="table-card-title">Paiements en retard</div>
                            <div class="table-card-sub">Contrats dont l'échéance est dépassée sans paiement ce mois-ci</div>
                        </div>
                        <div>
                            <?php if (!empty($retardsList)): ?>
                                <button class="btn-export-pay" type="button" onclick="relancerTous()" style="height:36px;padding:0 16px;border-radius:10px;border:1px solid #ef4444;background:rgba(239,68,68,0.12);color:#ef4444;cursor:pointer;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:7px;">
                                    <i class="bi bi-envelope-paper"></i> Relancer tous
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="admin-table-wrap">
                        <?php if (empty($retardsList)): ?>
                            <div class="empty-box">
                                <i class="bi bi-check-circle" style="color:#10b981"></i>
                                <strong>Aucun retard</strong>
                                <p>Tous les clients sont à jour dans leurs paiements.</p>
                            </div>
                        <?php else: ?>
                            <table class="table-pay">
                                <thead>
                                    <tr>
                                        <th>Contrat</th>
                                        <th>Client</th>
                                        <th>Prime</th>
                                        <th>Échéance</th>
                                        <th>Retard</th>
                                        <th>Dernière relance</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($retardsList as $r): ?>
                                    <tr>
                                        <td>
                                            <span class="ref-badge"><i class="bi bi-file-earmark-text"></i> <?= e($r['numero_contrat']) ?></span>
                                        </td>
                                        <td>
                                            <div style="font-weight:600; color:#fff; font-size:13px;"><?= e(trim($r['prenom'] . ' ' . $r['nom'])) ?></div>
                                            <div style="font-size:11px; color:var(--text-secondary);"><?= e($r['email']) ?></div>
                                        </td>
                                        <td>
                                            <div class="montant-val"><?= formatMoney($r['prime']) ?></div>
                                        </td>
                                        <td style="font-size:12px; color:var(--text-secondary);">
                                            <?= formatDateFr($r['date_echeance_contrat']) ?>
                                        </td>
                                        <td>
                                            <span style="display:inline-flex; align-items:center; gap:4px; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700; <?= $r['jours_retard'] > 7 ? 'background:rgba(239,68,68,0.2); color:#fca5a5;' : 'background:rgba(245,158,11,0.2); color:#fcd34d;' ?>">
                                                <i class="bi bi-clock-history"></i> <?= $r['jours_retard'] ?> jour(s)
                                            </span>
                                        </td>
                                        <td style="font-size:12px; color:var(--text-secondary);">
                                            <?= $r['derniere_relance'] ? formatDateFr($r['derniere_relance']) : 'Jamais relancé' ?>
                                        </td>
                                        <td>
                                            <button class="action-btn refuse" title="Envoyer relance" onclick="relancerClient(<?= $r['id_contrat'] ?>)">
                                                <i class="bi bi-send"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div> <!-- End tab-retards -->

        </div>
    </main>
</div>

<div id="printAreaPay"></div>

<script src="<?= e($BASE_URL) ?>/view/BackOffice/assets/js/main.js"></script>
<script>
document.getElementById('topbarDate').textContent =
    new Date().toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });

function switchMainTab(tabName) {
    document.querySelectorAll('.main-tab-content').forEach(el => el.style.display = 'none');
    document.getElementById('tab-' + tabName).style.display = 'block';
    
    document.querySelectorAll('.main-tab').forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = 'transparent';
        btn.style.border = '1px solid transparent';
    });
    
    const activeBtn = Array.from(document.querySelectorAll('.main-tab')).find(b => b.getAttribute('onclick').includes(tabName));
    if (activeBtn) {
        activeBtn.classList.add('active');
        activeBtn.style.background = 'rgba(255,255,255,0.05)';
        activeBtn.style.border = '1px solid rgba(255,255,255,0.1)';
    }

    if (tabName === 'dashboard' && !window.dashboardLoaded) {
        loadDashboard();
    }
}

let caChartObj = null;
let repartChartObj = null;
let punctualiteChartObj = null;

function loadDashboard() {
    window.dashboardLoaded = true;
    fetch('<?= e($BASE_URL) ?>/api.php?action=paiement_dashboard_stats')
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                const d = res.data;
                document.getElementById('kpi-ca-mois').textContent = new Intl.NumberFormat('fr-FR', {style: 'currency', currency: 'TND'}).format(d.ca_mois);
                document.getElementById('kpi-ca-annee').textContent = new Intl.NumberFormat('fr-FR', {style: 'currency', currency: 'TND'}).format(d.ca_annee);
                document.getElementById('kpi-retards').textContent = d.retards;
                document.getElementById('kpi-recouvrement').textContent = d.taux_recouvrement + ' %';

                if (caChartObj) caChartObj.destroy();
                if (repartChartObj) repartChartObj.destroy();
                if (punctualiteChartObj) punctualiteChartObj.destroy();

                const caCtx = document.getElementById('caChart').getContext('2d');
                caChartObj = new Chart(caCtx, {
                    type: 'line',
                    data: {
                        labels: d.ca_mensuel.map(x => x.mois),
                        datasets: [{
                            label: 'CA Mensuel',
                            data: d.ca_mensuel.map(x => x.total),
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.2)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: { responsive: true, plugins: { legend: { labels: { color: '#fff' } } }, scales: { x: { ticks: { color: '#b8c2d1' } }, y: { ticks: { color: '#b8c2d1' } } } }
                });

                const repCtx = document.getElementById('repartChart').getContext('2d');
                repartChartObj = new Chart(repCtx, {
                    type: 'doughnut',
                    data: {
                        labels: d.repartition.map(x => x.type),
                        datasets: [{
                            data: d.repartition.map(x => x.total),
                            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6'],
                            borderWidth: 0
                        }]
                    },
                    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { color: '#fff' } } } }
                });

                const pCtx = document.getElementById('punctualiteChart').getContext('2d');
                punctualiteChartObj = new Chart(pCtx, {
                    type: 'bar',
                    data: {
                        labels: d.paiements_par_mois.map(x => x.mois),
                        datasets: [
                            {
                                label: 'À temps',
                                data: d.paiements_par_mois.map(x => x.a_temps),
                                backgroundColor: '#10b981',
                                stack: 'status'
                            },
                            {
                                label: 'En retard',
                                data: d.paiements_par_mois.map(x => x.en_retard),
                                backgroundColor: '#ef4444',
                                stack: 'status'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { labels: { color: '#fff' } } },
                        scales: {
                            x: { ticks: { color: '#b8c2d1' }, stacked: true },
                            y: { ticks: { color: '#b8c2d1' }, stacked: true }
                        }
                    }
                });
            }
        });
}

// Load dashboard by default
switchMainTab('dashboard');

function relancerClient(idContrat) {
    if (!confirm('Envoyer une relance pour ce contrat ?')) return;
    
    const formData = new FormData();
    formData.append('id_contrat', idContrat);
    
    fetch('<?= e($BASE_URL) ?>/api.php?action=relancer_paiement', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            alert(res.message);
            window.location.reload();
        } else {
            alert(res.message || 'Erreur lors de la relance');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Erreur réseau');
    });
}

function relancerTous() {
    if (!confirm('Envoyer une relance à tous les contrats en retard ?')) return;

    fetch('<?= e($BASE_URL) ?>/api.php?action=relancer_tous', {
        method: 'POST'
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            alert(res.message);
            window.location.reload();
        } else {
            alert(res.message || 'Erreur lors de la relance globale');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Erreur réseau');
    });
}

const rows = document.querySelectorAll('.pay-row');
const counter = document.getElementById('countVisible');
const noRes = document.getElementById('noResults');
let activeStatut = '';

function filtrerStatut(statut, tabId) {
    activeStatut = statut;
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    const activeTab = document.getElementById(tabId);
    if (activeTab) activeTab.classList.add('active');
    appliquerFiltres();
}

function appliquerFiltres() {
    const searchInput = document.getElementById('searchPay');
    const search = (searchInput ? searchInput.value : '').toLowerCase().trim();
    const dateDebut = document.getElementById('dateDebut')?.value || '';
    const dateFin = document.getElementById('dateFin')?.value || '';
    const methode = document.getElementById('filterMethode')?.value || '';
    let count = 0;

    const dateDebutTs = dateDebut ? new Date(dateDebut).getTime() : 0;
    const dateFinTs = dateFin ? new Date(dateFin + 'T23:59:59').getTime() : Infinity;

    rows.forEach(row => {
        const okStatut = !activeStatut || row.dataset.statut === activeStatut;
        const okSearch = !search || row.dataset.search.includes(search);
        const okMethode = !methode || row.dataset.methode === methode;
        const dateVal = parseInt(row.dataset.date) || 0;
        const okDate = (!dateDebut || dateVal >= dateDebutTs) && (!dateFin || dateVal <= dateFinTs);
        const visible = okStatut && okSearch && okMethode && okDate;
        row.style.display = visible ? '' : 'none';
        if (visible) count++;
    });

    if (counter) counter.textContent = String(count);
    if (noRes) noRes.style.display = (count === 0 && rows.length > 0) ? 'block' : 'none';
}

const searchField = document.getElementById('searchPay');
if (searchField) searchField.addEventListener('input', appliquerFiltres);

const dateDebutField = document.getElementById('dateDebut');
const dateFinField = document.getElementById('dateFin');
const methodeField = document.getElementById('filterMethode');
if (dateDebutField) dateDebutField.addEventListener('change', appliquerFiltres);
if (dateFinField) dateFinField.addEventListener('change', appliquerFiltres);
if (methodeField) methodeField.addEventListener('change', appliquerFiltres);

function resetFilters() {
    if (searchField) searchField.value = '';
    if (dateDebutField) dateDebutField.value = '';
    if (dateFinField) dateFinField.value = '';
    if (methodeField) methodeField.value = '';
    activeStatut = '';
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    const tabTous = document.getElementById('tab-tous');
    if (tabTous) tabTous.classList.add('active');
    document.querySelectorAll('.sortable-pay').forEach(th => {
        th.classList.remove('sort-asc-pay', 'sort-desc-pay');
        const ico = th.querySelector('.sort-ico-pay');
        if (ico) ico.textContent = '↕';
    });
    appliquerFiltres();
}

let paySortState = { key: null, dir: 'asc' };

document.querySelectorAll('.sortable-pay').forEach(th => {
    th.addEventListener('click', () => {
        const key = th.dataset.key;
        const type = th.dataset.type || 'text';
        if (paySortState.key === key) {
            paySortState.dir = paySortState.dir === 'asc' ? 'desc' : 'asc';
        } else {
            paySortState.key = key;
            paySortState.dir = 'asc';
        }
        document.querySelectorAll('.sortable-pay').forEach(t => {
            t.classList.remove('sort-asc-pay', 'sort-desc-pay');
            const ico = t.querySelector('.sort-ico-pay');
            if (ico) ico.textContent = '↕';
        });
        th.classList.add(paySortState.dir === 'asc' ? 'sort-asc-pay' : 'sort-desc-pay');
        const icoTh = th.querySelector('.sort-ico-pay');
        if (icoTh) icoTh.textContent = paySortState.dir === 'asc' ? '↑' : '↓';
        sortPayRows(key, type, paySortState.dir);
    });
});

function sortPayRows(key, type, dir) {
    const tbody = document.getElementById('payBody');
    const rowsArr = Array.from(tbody.querySelectorAll('.pay-row'));
    rowsArr.sort((a, b) => {
        let va = a.dataset[key] || '';
        let vb = b.dataset[key] || '';
        if (type === 'num' || type === 'date') {
            va = parseFloat(va) || 0;
            vb = parseFloat(vb) || 0;
            return dir === 'asc' ? va - vb : vb - va;
        } else {
            va = va.toString().toLowerCase();
            vb = vb.toString().toLowerCase();
            return dir === 'asc' ? va.localeCompare(vb, 'fr') : vb.localeCompare(va, 'fr');
        }
    });
    rowsArr.forEach(r => tbody.appendChild(r));
}

function getVisiblePayData() {
    const data = [];
    rows.forEach(row => {
        if (row.style.display === 'none') return;
        const cells = row.querySelectorAll('td');
        data.push({
            reference: row.dataset.reference || '',
            offre: cells[1]?.querySelector('.offre-name')?.textContent.trim() || '',
            type: row.dataset.offre || '',
            montant: row.dataset.montant || '0',
            methode: row.dataset.methode || '',
            periodicite: row.dataset.periodicite || '',
            statut: row.dataset.statut || '',
            date: cells[6]?.textContent.trim() || '',
            echeance: cells[7]?.textContent.trim() || '',
        });
    });
    return data;
}

function downloadPayFile(content, filename, mime) {
    const blob = new Blob(["\uFEFF" + content], { type: mime + ';charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = filename;
    document.body.appendChild(a); a.click();
    document.body.removeChild(a); URL.revokeObjectURL(url);
}

function toggleExportPay() {
    document.getElementById('exportMenuPay')?.classList.toggle('show');
}
document.addEventListener('click', (e) => {
    const menu = document.getElementById('exportMenuPay');
    if (!menu) return;
    if (!e.target.closest('.export-wrapper')) menu.classList.remove('show');
});

function exportPayData(format) {
    document.getElementById('exportMenuPay')?.classList.remove('show');
    const data = getVisiblePayData();
    if (data.length === 0) { alert('⚠️ Aucune donnée à exporter.'); return; }
    const ts = new Date().toISOString().slice(0,16).replace(/[:T]/g,'-');
    if (format === 'csv') exportPayCSV(data, ts);
    if (format === 'excel') exportPayExcel(data, ts);
    if (format === 'pdf') exportPayPDF(data, ts);
}

function exportPayCSV(data, ts) {
    const sep = ';';
    const headers = ['Référence','Offre','Type','Montant (TND)','Méthode','Périodicité','Statut','Date paiement','Échéance'];
    const esc = (s) => '"' + String(s).replace(/"/g,'""') + '"';
    const lines = [headers.map(esc).join(sep)];
    data.forEach(d => {
        lines.push([d.reference, d.offre, d.type, parseFloat(d.montant).toFixed(3), ucfirst(d.methode), ucfirst(d.periodicite), ucfirst(d.statut), d.date, d.echeance].map(esc).join(sep));
    });
    downloadPayFile(lines.join('\n'), `paiements_protex_${ts}.csv`, 'text/csv');
}

function exportPayExcel(data, ts) {
    let html = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head><meta charset="UTF-8"><style>
table{border-collapse:collapse;font-family:Arial;font-size:12px;}
th{background:#FF6B1A;color:#fff;font-weight:bold;padding:10px;border:1px solid #999;text-align:center;}
td{padding:8px;border:1px solid #ccc;}
.title{font-size:18px;font-weight:bold;color:#1A3A7A;}
.sub{font-size:11px;color:#666;}
.num{text-align:right;}
.center{text-align:center;}
</style></head><body>
<table>
<tr><td colspan="9" class="title">PROTEX ASSURANCE — Rapport des paiements</td></tr>
<tr><td colspan="9" class="sub">Exporté le ${new Date().toLocaleString('fr-FR')} — ${data.length} paiement(s)</td></tr>
<tr><td colspan="9">&nbsp;</td></tr>
<tr><th>Référence</th><th>Offre</th><th>Type</th><th>Montant</th><th>Méthode</th><th>Périodicité</th><th>Statut</th><th>Date paiement</th><th>Échéance</th></tr>`;
    data.forEach(d => {
        html += `<tr>
            <td class="center">${d.reference}</td>
            <td>${d.offre}</td>
            <td class="center">${ucfirst(d.type)}</td>
            <td class="num">${parseFloat(d.montant).toFixed(3)} TND</td>
            <td class="center">${ucfirst(d.methode)}</td>
            <td class="center">${ucfirst(d.periodicite)}</td>
            <td class="center">${ucfirst(d.statut)}</td>
            <td class="center">${d.date}</td>
            <td class="center">${d.echeance}</td>
        </tr>`;
    });
    html += '</table></body></html>';
    downloadPayFile(html, `paiements_protex_${ts}.xls`, 'application/vnd.ms-excel');
}

function exportPayPDF(data, ts) {
    let rowsHtml = '';
    data.forEach(d => {
        rowsHtml += `<tr>
            <td>${d.reference}</td>
            <td><strong>${d.offre}</strong></td>
            <td>${ucfirst(d.type)}</td>
            <td style="text-align:right">${parseFloat(d.montant).toFixed(3)} TND</td>
            <td>${ucfirst(d.methode)}</td>
            <td>${ucfirst(d.statut)}</td>
            <td>${d.date}</td>
            <td>${d.echeance}</td>
        </tr>`;
    });
    const content = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Export Paiements</title>
<style>
body { font-family: Arial, sans-serif; padding: 20px; color: #000; }
.print-header { border-bottom: 3px solid #FF6B1A; padding-bottom: 12px; margin-bottom: 18px; }
.print-brand { font-size: 24px; font-weight: 800; color: #1A3A7A; margin: 0; }
.print-brand span { color: #FF6B1A; }
.print-info { color: #666; font-size: 12px; margin-top: 4px; }
table { width: 100%; border-collapse: collapse; font-size: 11px; }
th, td { border: 1px solid #999; padding: 7px 9px; }
th { background: #FF6B1A; color: #fff; text-align: left; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
@media print { body { padding: 0; } }
</style></head><body>
<div class="print-header">
    <h1 class="print-brand">PROTEX <span>Assurance</span></h1>
    <p class="print-info">Rapport des paiements — Export du ${new Date().toLocaleString('fr-FR')} — ${data.length} paiement(s)</p>
</div>
<table><thead><tr>
    <th>Référence</th><th>Offre</th><th>Type</th><th>Montant</th>
    <th>Méthode</th><th>Statut</th><th>Date</th><th>Échéance</th>
</tr></thead><tbody>${rowsHtml}</tbody></table>
<p style="margin-top:20px;font-size:10px;color:#888;text-align:center;">
    Document généré automatiquement par Protex Admin — ${new Date().toLocaleDateString('fr-FR')}
</p>
<script>window.onload = function() { window.print(); }<\/script>
</body></html>`;
    const win = window.open('', '_blank');
    if (win) { win.document.write(content); win.document.close(); }
}

function ucfirst(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
</script>
</body>
</html>
