<?php
$BASE_URL = (defined('BASE_URL') ? BASE_URL : '');

if (!isset($offres)) {
    include_once __DIR__ . '/../../../config.php';
    $db      = config::getConnexion();
    $offres  = $db->query("SELECT * FROM offre ORDER BY date_creation DESC")->fetchAll();
    $stats   = $db->query("SELECT COUNT(*) AS total, SUM(statut='active') AS actives, SUM(statut='suspendue') AS suspendues, SUM(statut='archivee') AS archivees FROM offre")->fetch() ?: [];
    $message = $_GET['message'] ?? '';
    $erreur  = $_GET['erreur']  ?? '';
} else {
    $message = $message ?? ($_GET['message'] ?? '');
    $erreur  = $erreur  ?? ($_GET['erreur']  ?? '');
}

// Calcul des stats par type pour le graphique
$statsTypes = ['auto' => 0, 'sante' => 0, 'habitation' => 0, 'vie' => 0];
foreach ($offres as $o) {
    $t = strtolower($o['type_offre'] ?? '');
    if (isset($statsTypes[$t])) $statsTypes[$t]++;
}

$total      = (int)($stats['total']      ?? 0);
$actives    = (int)($stats['actives']    ?? 0);
$suspendues = (int)($stats['suspendues'] ?? 0);
$archivees  = (int)($stats['archivees']  ?? 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Offres — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/view/BackOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/view/BackOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/view/BackOffice/assets/css/layout.css">
    
    <style>
        /* ════════════════════════════════════
           HERO SECTION
        ═══════════════════════════════════════ */
        .page-hero {
            position: relative;
            margin-bottom: 24px;
            padding: 28px;
            border-radius: 24px;
            background:
                radial-gradient(circle at top right, rgba(255,107,26,.18), transparent 35%),
                radial-gradient(circle at bottom left, rgba(0,180,216,.12), transparent 30%),
                linear-gradient(135deg, rgba(255,255,255,.05), rgba(255,255,255,.02));
            border: 1px solid rgba(255,255,255,0.1);
            overflow: hidden;
        }
        .page-hero-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            flex-wrap: wrap;
        }
        .page-hero-title {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.5px;
            background: linear-gradient(135deg, #fff 0%, #ffa380 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .page-hero-sub {
            margin: 10px 0 0;
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.6;
            max-width: 720px;
        }
        .page-hero-pills {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .page-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            transition: .2s ease;
        }
        .page-pill:hover {
            background: rgba(255,107,26,.15);
            border-color: rgba(255,107,26,.3);
            transform: translateY(-2px);
        }

        /* ════════════════════════════════════
           STATS ANIMÉES (cartes principales)
        ═══════════════════════════════════════ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0,1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            position: relative;
            overflow: hidden;
            padding: 22px 22px 18px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.08);
            background: linear-gradient(135deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
            transition: all .3s cubic-bezier(.4,0,.2,1);
            cursor: pointer;
        }
        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(0,0,0,.3);
            border-color: rgba(255,255,255,0.18);
        }
        .stat-card::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: var(--card-color, #FF6B1A);
            box-shadow: 0 0 20px var(--card-color, #FF6B1A);
        }
        .stat-card::after {
            content: "";
            position: absolute;
            top: -50%; right: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle at center, var(--card-color, #FF6B1A) 0%, transparent 60%);
            opacity: .08;
            pointer-events: none;
            transition: opacity .3s;
        }
        .stat-card:hover::after { opacity: .16; }

        .stat-card.stat-blue   { --card-color: #3b82f6; }
        .stat-card.stat-green  { --card-color: #10b981; }
        .stat-card.stat-gold   { --card-color: #f59e0b; }
        .stat-card.stat-red    { --card-color: #ef4444; }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
            position: relative;
            z-index: 1;
        }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: var(--card-color);
            color: #fff;
            font-size: 20px;
            box-shadow: 0 8px 24px color-mix(in srgb, var(--card-color) 40%, transparent);
        }
        .stat-trend {
            font-size: 11px;
            color: #fff;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.1);
            font-weight: 600;
        }
        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: #fff;
            line-height: 1;
            margin-bottom: 6px;
            font-variant-numeric: tabular-nums;
        }
        .stat-label {
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 500;
        }
        .stat-progress {
            margin-top: 14px;
            height: 6px;
            border-radius: 3px;
            background: rgba(255,255,255,.06);
            overflow: hidden;
            position: relative;
        }
        .stat-progress-bar {
            height: 100%;
            background: var(--card-color);
            border-radius: 3px;
            width: 0;
            transition: width 1.6s cubic-bezier(.4,0,.2,1);
            box-shadow: 0 0 12px var(--card-color);
        }
        .stat-percent {
            margin-top: 6px;
            font-size: 11px;
            color: var(--text-secondary);
            font-weight: 600;
        }

        /* ════════════════════════════════════
           GRAPHIQUES (donut + barres)
        ═══════════════════════════════════════ */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1.4fr;
            gap: 16px;
            margin-bottom: 24px;
        }
        .chart-card {
            padding: 22px;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
            border: 1px solid rgba(255,255,255,0.08);
        }
        .chart-card-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }
        .chart-card-head h4 {
            margin: 0;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .chart-card-head h4 i {
            color: #ff9b5e;
        }
        .chart-card-head .total-mini {
            color: var(--text-secondary);
            font-size: 12px;
            padding: 4px 10px;
            background: rgba(255,255,255,.05);
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.08);
        }

        /* Donut chart */
        .donut-wrap {
            display: flex;
            align-items: center;
            gap: 22px;
        }
        .donut-svg {
            width: 160px;
            height: 160px;
            flex-shrink: 0;
            transform: rotate(-90deg);
        }
        .donut-svg circle {
            transition: stroke-dashoffset 1.4s cubic-bezier(.4,0,.2,1);
        }
        .donut-center {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .donut-text {
            position: absolute;
            text-align: center;
            color: #fff;
        }
        .donut-text .num {
            font-size: 28px;
            font-weight: 800;
            line-height: 1;
        }
        .donut-text .lbl {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 3px;
        }
        .donut-legend { flex: 1; }
        .donut-legend-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px dashed rgba(255,255,255,.06);
        }
        .donut-legend-item:last-child { border-bottom: none; }
        .donut-legend-left {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
        }
        .donut-dot {
            width: 11px; height: 11px;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 10px currentColor;
        }
        .donut-legend-right {
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 700;
        }

        /* Barres horizontales */
        .bar-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .bar-row { }
        .bar-top {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .bar-name {
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .bar-value {
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 700;
        }
        .bar-track {
            height: 10px;
            border-radius: 5px;
            background: rgba(255,255,255,.04);
            overflow: hidden;
        }
        .bar-fill {
            height: 100%;
            border-radius: 5px;
            width: 0;
            transition: width 1.4s cubic-bezier(.4,0,.2,1);
            box-shadow: 0 0 12px currentColor;
        }
        .bar-row.auto       .bar-fill { background: #3b82f6; color: #3b82f6; }
        .bar-row.sante      .bar-fill { background: #10b981; color: #10b981; }
        .bar-row.habitation .bar-fill { background: #f59e0b; color: #f59e0b; }
        .bar-row.vie        .bar-fill { background: #ec4899; color: #ec4899; }
        .bar-row.auto       .bar-name i { color: #93c5fd; }
        .bar-row.sante      .bar-name i { color: #86efac; }
        .bar-row.habitation .bar-name i { color: #fcd34d; }
        .bar-row.vie        .bar-name i { color: #f9a8d4; }

        /* ════════════════════════════════════
           TOOLBAR
        ═══════════════════════════════════════ */
        .admin-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 22px;
            padding: 20px 24px;
            border-radius: 18px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
        }
        .admin-toolbar h2 { margin:0; font-size:20px; font-weight:700; color:#fff; }
        .admin-toolbar p  { margin:6px 0 0; color:var(--text-secondary); font-size:13px; }
        .admin-toolbar-right { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }

        /* ════════════════════════════════════
           FILTRES + EXPORT
        ═══════════════════════════════════════ */
        .admin-filter-bar {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr auto auto;
            gap: 12px;
            margin-bottom: 18px;
            align-items: center;
        }
        .admin-filter-bar .input-group { position:relative; }
        .admin-filter-bar .input-group i {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 14px;
            pointer-events: none;
        }
        .admin-filter-bar input,
        .admin-filter-bar select {
            width: 100%; height: 44px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.05);
            color: #fff;
            outline: none;
            font-size: 13px;
            font-family: var(--font-body, inherit);
            transition: .2s ease;
        }
        .admin-filter-bar input:focus,
        .admin-filter-bar select:focus {
            border-color: rgba(255,107,26,.4);
            box-shadow: 0 0 0 3px rgba(255,107,26,.1);
        }
        .admin-filter-bar input  { padding: 0 12px 0 40px; }
        .admin-filter-bar select { padding: 0 12px; }
        .admin-filter-bar select option { background: #0e1c33; color:#fff; }

        .btn-reset-filter {
            height: 44px; padding: 0 16px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.05);
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 13px;
            transition: .2s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-reset-filter:hover { background:rgba(255,255,255,0.09); color:#fff; }

        /* Export dropdown */
        .export-wrapper { position: relative; }
        .btn-export {
            height: 44px; padding: 0 18px;
            border-radius: 14px;
            border: 1px solid rgba(255,107,26,.4);
            background: linear-gradient(135deg, rgba(255,107,26,.2), rgba(255,107,26,.08));
            color: #ffb380;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            transition: .2s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-export:hover {
            background: linear-gradient(135deg, rgba(255,107,26,.35), rgba(255,107,26,.18));
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255,107,26,.25);
        }
        .export-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            min-width: 220px;
            background: #0e1c33;
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 14px;
            padding: 8px;
            box-shadow: 0 20px 50px rgba(0,0,0,.5);
            z-index: 100;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: .2s ease;
        }
        .export-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .export-menu button {
            width: 100%;
            padding: 11px 14px;
            border-radius: 10px;
            border: none;
            background: transparent;
            color: #fff;
            text-align: left;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: .15s ease;
        }
        .export-menu button:hover { background: rgba(255,255,255,.06); transform: translateX(3px); }
        .export-menu button i { font-size: 18px; }
        .export-menu .ic-csv   { color: #86efac; }
        .export-menu .ic-excel { color: #6ee7b7; }
        .export-menu .ic-pdf   { color: #fca5a5; }

        /* ════════════════════════════════════
           LIST HEADER + RESULT BADGE
        ═══════════════════════════════════════ */
        .list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .list-header h3 { margin:0; font-size:18px; color:#fff; font-weight:700; }
        .list-header p  { margin:4px 0 0; color:var(--text-secondary); font-size:12px; }
        .result-badge {
            padding: 8px 14px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(255,107,26,.15), rgba(255,107,26,.05));
            border: 1px solid rgba(255,107,26,.25);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
        }

        /* ════════════════════════════════════
           TABLE + TRI
        ═══════════════════════════════════════ */
        .admin-table-wrap {
            overflow-x: auto;
            border-radius: 18px;
            border: 1px solid rgba(255,255,255,.06);
        }
        .table-protex { width:100%; border-collapse:collapse; }
        .table-protex thead th {
            background: rgba(255,255,255,0.04);
            color: #cdd6f4;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            white-space: nowrap;
            padding: 14px 18px;
            text-align: left;
        }
        .table-protex thead th.sortable {
            cursor: pointer;
            user-select: none;
            transition: .15s ease;
        }
        .table-protex thead th.sortable:hover {
            background: rgba(255,107,26,.1);
            color: #fff;
        }
        .table-protex thead th .sort-ico {
            display: inline-block;
            margin-left: 6px;
            opacity: .35;
            font-size: 10px;
            transition: .15s ease;
        }
        .table-protex thead th.sort-asc  .sort-ico,
        .table-protex thead th.sort-desc .sort-ico {
            opacity: 1;
            color: #ff9b5e;
        }
        .table-protex tbody td {
            border-bottom:1px solid rgba(255,255,255,0.05);
            vertical-align:middle;
            padding: 14px 18px;
        }
        .table-protex tbody tr { transition:.18s ease; }
        .table-protex tbody tr:hover { background:rgba(255,255,255,0.03); }
        .table-protex tbody tr:last-child td { border-bottom:none; }

        .offre-id-badge {
            display: inline-flex; align-items:center; justify-content:center;
            min-width: 42px; padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.08);
            color: #fff; font-size:11px; font-weight:700;
        }
        .offre-main { display:flex; align-items:center; gap:12px; }
        .offre-avatar {
            width: 42px; height: 42px;
            border-radius: 14px;
            display: grid; place-items: center;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.08);
            color: #fff; font-size:17px; flex-shrink:0;
        }
        .offre-title { font-weight:700; color:#fff; margin-bottom:4px; font-size:14px; }
        .offre-desc  { font-size:11px; color:var(--text-secondary); line-height:1.4; max-width:260px; }

        .price-block strong { color:#fff; display:block; font-size:14px; }
        .price-block span   { display:block; margin-top:3px; color:var(--text-secondary); font-size:11px; }

        .badge-type {
            display: inline-flex; align-items:center; gap:6px;
            padding: 7px 11px; border-radius:999px;
            font-size:11px; font-weight:700; border:1px solid transparent;
        }
        .badge-type.auto       { background:rgba(59,130,246,.12);  color:#93c5fd; border-color:rgba(59,130,246,.24); }
        .badge-type.sante      { background:rgba(16,185,129,.12);  color:#86efac; border-color:rgba(16,185,129,.24); }
        .badge-type.habitation { background:rgba(245,158,11,.12);  color:#fcd34d; border-color:rgba(245,158,11,.24); }
        .badge-type.vie        { background:rgba(236,72,153,.12);  color:#f9a8d4; border-color:rgba(236,72,153,.24); }

        .status-badge {
            display: inline-flex; align-items:center; gap:6px;
            padding: 7px 11px; border-radius:999px;
            font-size:11px; font-weight:700;
        }
        .status-badge.active   { background:rgba(16,185,129,.14); color:#86efac; }
        .status-badge.suspendue{ background:rgba(245,158,11,.14);  color:#fcd34d; }
        .status-badge.archivee { background:rgba(148,163,184,.14); color:#cbd5e1; }

        .action-group { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .action-icon {
            width:36px; height:36px; border-radius:11px;
            display:inline-flex; align-items:center; justify-content:center;
            text-decoration:none; color:#fff;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            transition: .2s ease;
        }
        .action-icon:hover { background:rgba(255,255,255,0.1); transform:translateY(-2px); box-shadow:0 6px 16px rgba(0,0,0,.15); }
        .action-icon.delete { background:rgba(239,68,68,.12); color:#fca5a5; border-color:rgba(239,68,68,.2); }
        .action-icon.delete:hover { background:rgba(239,68,68,.22); }

        .empty-box { text-align:center; padding:60px 20px; color:var(--text-secondary); }
        .empty-box i      { font-size:42px; display:block; margin-bottom:12px; opacity:.7; }
        .empty-box strong { display:block; color:#fff; margin-bottom:8px; font-size:18px; }
        .empty-box p      { margin:0 0 12px; }
        .no-results { display:none; text-align:center; padding:30px; color:var(--text-secondary); font-size:14px; }
        .no-results i { font-size:30px; display:block; margin-bottom:10px; opacity:.5; }

        .alert-ok  { background:rgba(16,185,129,.08); border:1px solid rgba(16,185,129,.25); border-radius:14px; padding:13px 18px; color:#86efac; font-size:13px; display:flex; align-items:center; gap:10px; margin-bottom:20px; }
        .alert-err { background:rgba(239,68,68,.08);  border:1px solid rgba(239,68,68,.25);  border-radius:14px; padding:13px 18px; color:#fca5a5; font-size:13px; display:flex; align-items:center; gap:10px; margin-bottom:20px; }

        /* ════════════════════════════════════
           PRINT (PDF EXPORT)
        ═══════════════════════════════════════ */
        @media print {
            body * { visibility: hidden !important; }
            #printArea { display: block !important; position: absolute !important; left: 0; top: 0; width: 100%; background: #fff !important; color: #000 !important; padding: 20px !important; font-family: Arial, sans-serif !important; }
            #printArea, #printArea * { visibility: visible !important; }
            #printArea table { width:100%; border-collapse:collapse; font-size:11px; }
            #printArea th, #printArea td {
                border: 1px solid #999 !important;
                padding: 7px 9px !important;
                color: #000 !important;
            }
            #printArea th {
                background: #FF6B1A !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                text-align: left;
            }
            #printArea .print-header {
                border-bottom: 3px solid #FF6B1A !important;
                padding-bottom: 12px;
                margin-bottom: 18px;
            }
            #printArea .print-brand {
                font-size: 24px;
                font-weight: 800;
                color: #1A3A7A !important;
                margin: 0;
            }
            #printArea .print-brand span { color: #FF6B1A !important; }
            #printArea .print-info {
                color: #666 !important;
                font-size: 12px;
                margin-top: 4px;
            }
        }
        #printArea { display: none !important; }

        @media(max-width:1100px){
            .stats-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
            .charts-grid{grid-template-columns:1fr;}
        }
        @media(max-width:900px) { .admin-filter-bar{grid-template-columns:1fr 1fr;} }
        @media(max-width:600px) { .admin-filter-bar,.stats-grid{grid-template-columns:1fr;} .page-hero{padding:20px;} .page-hero-title{font-size:22px;} }
    </style>
</head>
<body>
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">

    <!-- ===== SIDEBAR ===== -->
    <?php include __DIR__ . '/../assets/includes/sidebar.php'; ?>

    <!-- ===== MAIN ===== -->
    <main class="main">
        <div class="topbar">
            <div>
                <div class="topbar-title">Gestion des offres</div>
                <div class="topbar-sub" id="topbarDate"></div>
            </div>
            <div class="topbar-actions">
                <a href="<?= $BASE_URL ?>/controller/OffreController.php?action=ajouter" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Ajouter une offre
                </a>
                <a href="<?= $BASE_URL ?>/controller/OffreController.php?action=avis" class="btn btn-secondary" style="display:inline-flex;align-items:center;gap:6px;padding:0 18px;height:44px;border-radius:14px;background:rgba(255,255,255,0.06);color:var(--text-secondary);text-decoration:none;font-size:13px;font-weight:600;transition:.2s;">
                    <i class="bi bi-chat-square-text"></i> Avis clients
                </a>
                <a href="#" class="topbar-btn">
                    <i class="bi bi-bell"></i>
                    <span class="notif-dot"></span>
                </a>
            </div>
        </div>

        <div class="content">

            <div class="page-breadcrumb" style="margin-bottom:24px;">
                <i class="bi bi-house"></i> <span>Admin</span>
                <i class="bi bi-chevron-right" style="font-size:10px;"></i>
                <span>Offres</span>
            </div>

            <!-- Alertes -->
            <?php if (!empty($message)): ?>
            <div class="alert-ok">
                <i class="bi bi-check-circle-fill"></i>
                <strong><?= htmlspecialchars($message) ?></strong>
            </div>
            <?php endif; ?>

            <?php if (!empty($erreur)): ?>
            <div class="alert-err">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong><?= htmlspecialchars($erreur) ?></strong>
            </div>
            <?php endif; ?>

            <!-- HERO -->
            <section class="page-hero">
                <div class="page-hero-head">
                    <div>
                        <h1 class="page-hero-title">Catalogue des offres Protex</h1>
                        <p class="page-hero-sub">
                            Pilotez votre catalogue d'offres d'assurance en temps réel. Recherche, tri et export
                            disponibles en un clic.
                        </p>
                        <div class="page-hero-pills">
                            <span class="page-pill"><i class="bi bi-stars"></i> Vue centralisée</span>
                            <span class="page-pill"><i class="bi bi-funnel"></i> Filtres rapides</span>
                            <span class="page-pill"><i class="bi bi-download"></i> Export CSV / Excel / PDF</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══ STATS ANIMÉES ═══ -->
            <section class="stats-grid">
                <div class="stat-card stat-blue">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="bi bi-tags-fill"></i></div>
                        <span class="stat-trend"><i class="bi bi-bar-chart-line"></i> Total</span>
                    </div>
                    <div class="stat-value" data-target="<?= $total ?>">0</div>
                    <div class="stat-label">Total des offres</div>
                    <div class="stat-progress"><div class="stat-progress-bar" data-percent="100"></div></div>
                    <div class="stat-percent">100% du catalogue</div>
                </div>

                <div class="stat-card stat-green">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                        <span class="stat-trend"><i class="bi bi-arrow-up"></i> Actives</span>
                    </div>
                    <div class="stat-value" data-target="<?= $actives ?>">0</div>
                    <div class="stat-label">Offres actives</div>
                    <div class="stat-progress"><div class="stat-progress-bar" data-percent="<?= $total > 0 ? round($actives/$total*100) : 0 ?>"></div></div>
                    <div class="stat-percent"><?= $total > 0 ? round($actives/$total*100) : 0 ?>% du catalogue</div>
                </div>

                <div class="stat-card stat-gold">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="bi bi-pause-circle-fill"></i></div>
                        <span class="stat-trend"><i class="bi bi-clock"></i> En pause</span>
                    </div>
                    <div class="stat-value" data-target="<?= $suspendues ?>">0</div>
                    <div class="stat-label">Offres suspendues</div>
                    <div class="stat-progress"><div class="stat-progress-bar" data-percent="<?= $total > 0 ? round($suspendues/$total*100) : 0 ?>"></div></div>
                    <div class="stat-percent"><?= $total > 0 ? round($suspendues/$total*100) : 0 ?>% du catalogue</div>
                </div>

                <div class="stat-card stat-red">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="bi bi-archive-fill"></i></div>
                        <span class="stat-trend"><i class="bi bi-archive"></i> Archives</span>
                    </div>
                    <div class="stat-value" data-target="<?= $archivees ?>">0</div>
                    <div class="stat-label">Offres archivées</div>
                    <div class="stat-progress"><div class="stat-progress-bar" data-percent="<?= $total > 0 ? round($archivees/$total*100) : 0 ?>"></div></div>
                    <div class="stat-percent"><?= $total > 0 ? round($archivees/$total*100) : 0 ?>% du catalogue</div>
                </div>
            </section>

            <!-- ═══ GRAPHIQUES ═══ -->
            <section class="charts-grid">
                <!-- DONUT par statut -->
                <div class="chart-card">
                    <div class="chart-card-head">
                        <h4><i class="bi bi-pie-chart-fill"></i> Répartition par statut</h4>
                        <span class="total-mini"><?= $total ?> total</span>
                    </div>
                    <div class="donut-wrap">
                        <div class="donut-center">
                            <svg class="donut-svg" id="donutChart" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="48" fill="none" stroke="rgba(255,255,255,.05)" stroke-width="14"/>
                                <circle id="donutActive"    cx="60" cy="60" r="48" fill="none" stroke="#10b981" stroke-width="14" stroke-dasharray="0 999" stroke-linecap="round"/>
                                <circle id="donutSuspendue" cx="60" cy="60" r="48" fill="none" stroke="#f59e0b" stroke-width="14" stroke-dasharray="0 999" stroke-linecap="round"/>
                                <circle id="donutArchivee"  cx="60" cy="60" r="48" fill="none" stroke="#94a3b8" stroke-width="14" stroke-dasharray="0 999" stroke-linecap="round"/>
                            </svg>
                            <div class="donut-text">
                                <div class="num"><?= $total ?></div>
                                <div class="lbl">offres</div>
                            </div>
                        </div>
                        <div class="donut-legend">
                            <div class="donut-legend-item">
                                <div class="donut-legend-left"><span class="donut-dot" style="background:#10b981;color:#10b981"></span> Actives</div>
                                <div class="donut-legend-right"><?= $actives ?> (<?= $total > 0 ? round($actives/$total*100) : 0 ?>%)</div>
                            </div>
                            <div class="donut-legend-item">
                                <div class="donut-legend-left"><span class="donut-dot" style="background:#f59e0b;color:#f59e0b"></span> Suspendues</div>
                                <div class="donut-legend-right"><?= $suspendues ?> (<?= $total > 0 ? round($suspendues/$total*100) : 0 ?>%)</div>
                            </div>
                            <div class="donut-legend-item">
                                <div class="donut-legend-left"><span class="donut-dot" style="background:#94a3b8;color:#94a3b8"></span> Archivées</div>
                                <div class="donut-legend-right"><?= $archivees ?> (<?= $total > 0 ? round($archivees/$total*100) : 0 ?>%)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BARRES par type -->
                <div class="chart-card">
                    <div class="chart-card-head">
                        <h4><i class="bi bi-bar-chart-fill"></i> Répartition par type d'assurance</h4>
                        <span class="total-mini"><?= array_sum($statsTypes) ?> classifiées</span>
                    </div>
                    <div class="bar-list">
                        <?php
                        $maxBar = max(array_values($statsTypes) ?: [1]);
                        $barConfig = [
                            'auto'       => ['Auto',       'bi-car-front'],
                            'sante'      => ['Santé',      'bi-heart-pulse'],
                            'habitation' => ['Habitation', 'bi-house-door'],
                            'vie'        => ['Vie',        'bi-shield-check'],
                        ];
                        foreach ($barConfig as $key => $cfg):
                            $count = $statsTypes[$key];
                            $pct   = $maxBar > 0 ? round($count / $maxBar * 100) : 0;
                        ?>
                        <div class="bar-row <?= $key ?>">
                            <div class="bar-top">
                                <span class="bar-name"><i class="bi <?= $cfg[1] ?>"></i> <?= $cfg[0] ?></span>
                                <span class="bar-value"><?= $count ?> offre(s)</span>
                            </div>
                            <div class="bar-track">
                                <div class="bar-fill" data-percent="<?= $pct ?>"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- ═══ O1: ANALYTICS PERFORMANCE ═══ -->
            <section class="charts-grid" style="margin-bottom:24px;">
                <div class="chart-card" style="grid-column: 1 / -1;">
                    <div class="chart-card-head" style="margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 12px; cursor: pointer;" onclick="toggleAnalyticsPanel()">
                        <h4><i class="bi bi-graph-up-arrow"></i> Tableau de bord Analytics (Dérouler)</h4>
                        <i class="bi bi-chevron-down" id="analyticsChevron"></i>
                    </div>
                    <div id="analyticsPanel" style="display: none;">
                        
                        <?php
                            // Calcul des offres sous-performantes (conversion < 10% avec au moins 5 devis)
                            $underperforming = [];
                            foreach ($offres as $o) {
                                $devis = (int)($o['nb_devis'] ?? 0);
                                $contrats = (int)($o['nb_contrats'] ?? 0);
                                if ($devis >= 5) {
                                    $conversion = ($contrats / $devis) * 100;
                                    if ($conversion < 10) {
                                        $underperforming[] = [
                                            'nom' => $o['nom_offre'],
                                            'conv' => round($conversion, 1)
                                        ];
                                    }
                                }
                            }
                            if (!empty($underperforming)):
                        ?>
                        <div class="alert-err" style="margin-bottom: 20px;">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <div>
                                <strong>Alerte : Offres sous-performantes (Taux de conversion < 10%)</strong>
                                <ul style="margin: 5px 0 0 20px; padding: 0;">
                                    <?php foreach ($underperforming as $up): ?>
                                    <li><?= htmlspecialchars($up['nom']) ?> (<?= $up['conv'] ?>%)</li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <h5>Top 5 Offres par Revenu</h5>
                                <canvas id="revenueChart" height="250"></canvas>
                            </div>
                            <div>
                                <h5>Évolution Souscriptions (6 mois)</h5>
                                <canvas id="subsChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TOOLBAR -->
            <div class="admin-toolbar">
                <div>
                    <h2>Administration des offres</h2>
                    <p>Recherchez, triez, exportez et gérez vos offres en un clic.</p>
                </div>
                <div class="admin-toolbar-right">
                    <a href="<?= $BASE_URL ?>/controller/OffreController.php?action=ajouter" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Nouvelle offre
                    </a>
                </div>
            </div>

            <!-- ═══ TABLEAU + FILTRES + EXPORT ═══ -->
            <div class="card">
                <div class="card-body" style="padding:24px;">

                    <div class="list-header">
                        <div>
                            <h3>Liste des offres</h3>
                            <p>Cliquez sur les en-têtes pour trier. Utilisez les filtres pour affiner.</p>
                        </div>
                        <div class="result-badge">
                            <i class="bi bi-funnel-fill"></i> <span id="visibleCount"><?= count($offres) ?></span> résultat(s)
                        </div>
                    </div>

                    <!-- Filtres + Export -->
                    <div class="admin-filter-bar">
                        <div class="input-group">
                            <i class="bi bi-search"></i>
                            <input type="text" id="searchInput" placeholder="Rechercher une offre, description, ID...">
                        </div>
                        <select id="typeFilter">
                            <option value="">Tous les types</option>
                            <option value="auto">Auto</option>
                            <option value="sante">Santé</option>
                            <option value="habitation">Habitation</option>
                            <option value="vie">Vie</option>
                        </select>
                        <select id="statusFilter">
                            <option value="">Tous les statuts</option>
                            <option value="active">Active</option>
                            <option value="suspendue">Suspendue</option>
                            <option value="archivee">Archivée</option>
                        </select>
                        <button class="btn-reset-filter" onclick="resetFilters()" type="button">
                            <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                        </button>

                        <!-- 🆕 Export dropdown -->
                        <div class="export-wrapper">
                            <button class="btn-export" type="button" onclick="toggleExportMenu(event)">
                                <i class="bi bi-download"></i> Exporter <i class="bi bi-chevron-down" style="font-size:10px"></i>
                            </button>
                            <div class="export-menu" id="exportMenu">
                                <button type="button" onclick="exportData('csv')">
                                    <i class="bi bi-filetype-csv ic-csv"></i>
                                    <div>
                                        <div>Exporter en CSV</div>
                                        <div style="font-size:11px;opacity:.6;font-weight:400;">Format texte universel</div>
                                    </div>
                                </button>
                                <button type="button" onclick="exportData('excel')">
                                    <i class="bi bi-file-earmark-excel-fill ic-excel"></i>
                                    <div>
                                        <div>Exporter en Excel</div>
                                        <div style="font-size:11px;opacity:.6;font-weight:400;">Tableur Microsoft</div>
                                    </div>
                                </button>
                                <button type="button" onclick="exportData('pdf')">
                                    <i class="bi bi-file-earmark-pdf-fill ic-pdf"></i>
                                    <div>
                                        <div>Exporter en PDF</div>
                                        <div style="font-size:11px;opacity:.6;font-weight:400;">Document imprimable</div>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($offres)): ?>
                    <div class="empty-box">
                        <i class="bi bi-inbox"></i>
                        <strong>Aucune offre disponible</strong>
                        <p>Commencez par ajouter une première offre dans le système.</p>
                        <a href="<?= $BASE_URL ?>/controller/OffreController.php?action=ajouter" style="color:var(--accent);">
                            Ajouter la première offre
                        </a>
                    </div>
                    <?php else: ?>

                    <div class="admin-table-wrap">
                        <table class="table-protex" id="offresTable">
                            <thead>
                                <tr>
                                    <th class="sortable" data-key="id"      data-type="num">#                <span class="sort-ico">↕</span></th>
                                    <th class="sortable" data-key="nom"     data-type="text">Offre           <span class="sort-ico">↕</span></th>
                                    <th class="sortable" data-key="type"    data-type="text">Type            <span class="sort-ico">↕</span></th>
                                    <th class="sortable" data-key="prixm"   data-type="num">Prix mensuel    <span class="sort-ico">↕</span></th>
                                    <th class="sortable" data-key="prixa"   data-type="num">Prix annuel     <span class="sort-ico">↕</span></th>
                                    <th class="sortable" data-key="plafond" data-type="num">Plafond         <span class="sort-ico">↕</span></th>
                                    <th class="sortable" data-key="statut"  data-type="text">Statut         <span class="sort-ico">↕</span></th>
                                    <th class="sortable" data-key="date"    data-type="date">Créée le       <span class="sort-ico">↕</span></th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="offresTableBody">
                            <?php foreach ($offres as $o):
                                $type      = strtolower($o['type_offre'] ?? '');
                                $statut    = strtolower($o['statut']     ?? '');
                                $statusData= ($statut === 'archivée') ? 'archivee' : $statut;
                                $desc      = trim((string)($o['description'] ?? ''));
                                $shortDesc = mb_strlen($desc) > 58 ? mb_substr($desc,0,58).'...' : $desc;
                                $typeIcon  = 'bi-tags';
                                if ($type==='auto')           $typeIcon = 'bi-car-front';
                                elseif ($type==='sante')      $typeIcon = 'bi-heart-pulse';
                                elseif ($type==='habitation') $typeIcon = 'bi-house-door';
                                elseif ($type==='vie')        $typeIcon = 'bi-shield-check';
                                $dateTs = !empty($o['date_creation']) ? strtotime($o['date_creation']) : 0;
                            ?>
                            <tr class="offre-row"
                                data-type="<?= htmlspecialchars($type) ?>"
                                data-status="<?= htmlspecialchars($statusData) ?>"
                                data-search="<?= htmlspecialchars(mb_strtolower(($o['id_offre']??'').' '.($o['nom_offre']??'').' '.$desc)) ?>"
                                data-id="<?= (int)($o['id_offre'] ?? 0) ?>"
                                data-nom="<?= htmlspecialchars(mb_strtolower($o['nom_offre'] ?? '')) ?>"
                                data-prixm="<?= (float)($o['prix_mensuel'] ?? 0) ?>"
                                data-prixa="<?= (float)($o['prix_annuel'] ?? 0) ?>"
                                data-plafond="<?= (float)($o['plafond'] ?? 0) ?>"
                                data-statut="<?= htmlspecialchars($statusData) ?>"
                                data-date="<?= $dateTs ?>">

                                <td>
                                    <span class="offre-id-badge">#<?= (int)($o['id_offre']??0) ?></span>
                                </td>

                                <td>
                                    <div class="offre-main">
                                        <div class="offre-avatar">
                                            <i class="bi <?= $typeIcon ?>"></i>
                                        </div>
                                        <div>
                                            <div class="offre-title"><?= htmlspecialchars($o['nom_offre']??'—') ?></div>
                                            <div class="offre-desc"><?= htmlspecialchars($shortDesc?:'Aucune description') ?></div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge-type <?= htmlspecialchars($type) ?>">
                                        <i class="bi <?= $typeIcon ?>"></i>
                                        <?= ucfirst($type?:'N/A') ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="price-block">
                                        <strong><?= number_format((float)($o['prix_mensuel']??0),3) ?> TND</strong>
                                        <span>Mensuel</span>
                                    </div>
                                </td>

                                <td>
                                    <div class="price-block">
                                        <strong><?= number_format((float)($o['prix_annuel']??0),3) ?> TND</strong>
                                        <span>Annuel</span>
                                    </div>
                                </td>

                                <td style="color:var(--text-secondary);">
                                    <?= !empty($o['plafond']) ? number_format((float)$o['plafond'],0,'.',' ').' TND' : '—' ?>
                                </td>

                                <td>
                                    <?php if ($statut==='active'): ?>
                                        <span class="status-badge active"><i class="bi bi-check-circle-fill"></i> Active</span>
                                    <?php elseif ($statut==='suspendue'): ?>
                                        <span class="status-badge suspendue"><i class="bi bi-pause-circle-fill"></i> Suspendue</span>
                                    <?php else: ?>
                                        <span class="status-badge archivee"><i class="bi bi-archive-fill"></i> Archivée</span>
                                    <?php endif; ?>
                                </td>

                                <td style="font-size:12px;color:var(--text-secondary);">
                                    <?= !empty($o['date_creation']) ? date('d/m/Y',strtotime($o['date_creation'])) : '—' ?>
                                </td>

                                <td>
                                    <div class="action-group">
                                        <a href="<?= $BASE_URL ?>/controller/OffreController.php?action=modifier&id=<?= (int)($o['id_offre']??0) ?>" class="action-icon edit" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($statut==='active'): ?>
                                        <a href="<?= $BASE_URL ?>/controller/OffreController.php?action=statut&id=<?= (int)($o['id_offre']??0) ?>&statut=suspendue" class="action-icon pause" title="Suspendre" onclick="return confirm('Suspendre cette offre ?')">
                                            <i class="bi bi-pause-circle"></i>
                                        </a>
                                        <?php elseif ($statut==='suspendue'): ?>
                                        <a href="<?= $BASE_URL ?>/controller/OffreController.php?action=statut&id=<?= (int)($o['id_offre']??0) ?>&statut=active" class="action-icon play" title="Activer" onclick="return confirm('Activer cette offre ?')">
                                            <i class="bi bi-play-circle"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="<?= $BASE_URL ?>/controller/OffreController.php?action=supprimer&id=<?= (int)($o['id_offre']??0) ?>" class="action-icon delete" title="Supprimer" onclick="return confirm('Supprimer cette offre ?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="no-results" id="noResults">
                            <i class="bi bi-search"></i>
                            Aucun résultat trouvé. Essayez de modifier vos filtres.
                        </div>
                    </div>

                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Zone cachée pour l'impression PDF -->
<div id="printArea"></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    // ======= O1: ANALYTICS ========
    function toggleAnalyticsPanel() {
        const p = document.getElementById('analyticsPanel');
        const i = document.getElementById('analyticsChevron');
        if (p.style.display === 'none') {
            p.style.display = 'block';
            i.classList.replace('bi-chevron-down', 'bi-chevron-up');
            renderAnalyticsCharts();
        } else {
            p.style.display = 'none';
            i.classList.replace('bi-chevron-up', 'bi-chevron-down');
        }
    }

    let chartsRendered = false;
    function renderAnalyticsCharts() {
        if (chartsRendered) return;
        chartsRendered = true;

        const offresData = <?= json_encode($offres) ?>;
        const monthlyStats = <?= json_encode($monthlyStats ?? []) ?>;

        // Top 5 by Revenue
        const top5 = [...offresData].sort((a,b) => parseFloat(b.revenu_valide || 0) - parseFloat(a.revenu_valide || 0)).slice(0,5);
        
        new Chart(document.getElementById('revenueChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: top5.map(o => o.nom_offre),
                datasets: [{
                    label: 'Revenu (TND)',
                    data: top5.map(o => parseFloat(o.revenu_valide || 0)),
                    backgroundColor: '#FF6B1A',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#cdd6f4' } },
                    x: { grid: { display: false }, ticks: { color: '#cdd6f4' } }
                }
            }
        });

        // Line Chart (Subs)
        const grouped = {};
        const allMonths = new Set();
        monthlyStats.forEach(m => {
            if (!grouped[m.nom_offre]) grouped[m.nom_offre] = {};
            grouped[m.nom_offre][m.mois] = parseInt(m.nb);
            allMonths.add(m.mois);
        });
        const months = Array.from(allMonths).sort();
        const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6'];
        let colorIdx = 0;

        const datasets = Object.keys(grouped).slice(0,5).map(nom => { // max 5 lines
            const data = months.map(m => grouped[nom][m] || 0);
            const c = colors[colorIdx++ % colors.length];
            return {
                label: nom,
                data: data,
                borderColor: c,
                backgroundColor: c + '33',
                tension: 0.3,
                fill: true
            };
        });

        new Chart(document.getElementById('subsChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: months,
                datasets: datasets
            },
            options: {
                responsive: true,
                plugins: { legend: { labels: { color: '#cdd6f4' } } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#cdd6f4' } },
                    x: { grid: { display: false }, ticks: { color: '#cdd6f4' } }
                }
            }
        });
    }

    // ======= ANIMATION STATS ========
    function animateCounters() {
        const counters = document.querySelectorAll('.stat-value');
        counters.forEach(cnt => {
            const tgt = +cnt.getAttribute('data-target');
            let val = 0, speed = tgt / 40;
            if (tgt === 0) return;
            const update = () => {
                val += speed;
                if (val < tgt) { cnt.innerText = Math.ceil(val); requestAnimationFrame(update); }
                else { cnt.innerText = tgt; }
            };
            update();
        });
        setTimeout(() => {
            document.querySelectorAll('.stat-progress-bar').forEach(b => {
                b.style.width = b.getAttribute('data-percent') + '%';
            });
        }, 100);
        setTimeout(() => {
            document.querySelectorAll('.bar-fill').forEach(b => {
                b.style.width = b.getAttribute('data-percent') + '%';
            });
        }, 300);
    }
    animateCounters();
</script>
<script src="<?= $BASE_URL ?>/view/BackOffice/assets/js/main.js"></script>
<script>
/* ════════════════════════════════════════════════════════════
   1) DATE TOPBAR
═══════════════════════════════════════════════════════════════ */
const topbarDate = document.getElementById('topbarDate');
if (topbarDate) {
    topbarDate.textContent = new Date().toLocaleDateString('fr-FR', {
        weekday:'long', day:'numeric', month:'long', year:'numeric'
    });
}

/* ════════════════════════════════════════════════════════════
   2) ANIMATION DES STATS (compteurs + barres)
═══════════════════════════════════════════════════════════════ */
function animateValue(el, target, duration = 1400) {
    const start = 0;
    const startTime = performance.now();
    function tick(now) {
        const t = Math.min(1, (now - startTime) / duration);
        const eased = 1 - Math.pow(1 - t, 3);
        el.textContent = Math.round(start + (target - start) * eased);
        if (t < 1) requestAnimationFrame(tick);
        else el.textContent = target;
    }
    requestAnimationFrame(tick);
}

document.querySelectorAll('.stat-value').forEach(el => {
    const target = parseInt(el.dataset.target || '0', 10);
    animateValue(el, target);
});

setTimeout(() => {
    document.querySelectorAll('.stat-progress-bar, .bar-fill').forEach(bar => {
        bar.style.width = (bar.dataset.percent || 0) + '%';
    });
}, 200);

/* ════════════════════════════════════════════════════════════
   3) DONUT CHART
═══════════════════════════════════════════════════════════════ */
const total      = <?= $total ?>;
const actives    = <?= $actives ?>;
const suspendues = <?= $suspendues ?>;
const archivees  = <?= $archivees ?>;

if (total > 0) {
    const radius = 48;
    const C = 2 * Math.PI * radius;
    const segActive    = (actives    / total) * C;
    const segSuspendue = (suspendues / total) * C;
    const segArchivee  = (archivees  / total) * C;

    setTimeout(() => {
        const a = document.getElementById('donutActive');
        const s = document.getElementById('donutSuspendue');
        const r = document.getElementById('donutArchivee');
        if (a) a.setAttribute('stroke-dasharray', `${segActive} ${C - segActive}`);
        if (s) {
            s.setAttribute('stroke-dasharray', `${segSuspendue} ${C - segSuspendue}`);
            s.setAttribute('stroke-dashoffset', -segActive);
        }
        if (r) {
            r.setAttribute('stroke-dasharray', `${segArchivee} ${C - segArchivee}`);
            r.setAttribute('stroke-dashoffset', -(segActive + segSuspendue));
        }
    }, 300);
}

/* ════════════════════════════════════════════════════════════
   4) RECHERCHE + FILTRES
═══════════════════════════════════════════════════════════════ */
const searchInput  = document.getElementById('searchInput');
const typeFilter   = document.getElementById('typeFilter');
const statusFilter = document.getElementById('statusFilter');
const rows         = document.querySelectorAll('.offre-row');
const visibleCount = document.getElementById('visibleCount');
const noResults    = document.getElementById('noResults');

function applyFilters() {
    const search = (searchInput?.value || '').toLowerCase().trim();
    const type   = typeFilter?.value   || '';
    const status = statusFilter?.value || '';
    let count = 0;

    rows.forEach(row => {
        const matchSearch = !search || row.dataset.search.includes(search);
        const matchType   = !type   || row.dataset.type   === type;
        const matchStatus = !status || row.dataset.status === status;
        const visible = matchSearch && matchType && matchStatus;
        row.style.display = visible ? '' : 'none';
        if (visible) count++;
    });

    if (visibleCount) visibleCount.textContent = count;
    if (noResults)   noResults.style.display = (count === 0 && rows.length > 0) ? 'block' : 'none';
}

function resetFilters() {
    if (searchInput)  searchInput.value  = '';
    if (typeFilter)   typeFilter.value   = '';
    if (statusFilter) statusFilter.value = '';
    document.querySelectorAll('.table-protex thead th.sortable').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
        const ico = th.querySelector('.sort-ico');
        if (ico) ico.textContent = '↕';
    });
    applyFilters();
}

searchInput?.addEventListener('input',  applyFilters);
typeFilter?.addEventListener('change',  applyFilters);
statusFilter?.addEventListener('change', applyFilters);

/* ════════════════════════════════════════════════════════════
   5) TRI ALPHABÉTIQUE / NUMÉRIQUE / DATE
═══════════════════════════════════════════════════════════════ */
let sortState = { key: null, dir: 'asc' };

document.querySelectorAll('.table-protex thead th.sortable').forEach(th => {
    th.addEventListener('click', () => {
        const key  = th.dataset.key;
        const type = th.dataset.type || 'text';

        if (sortState.key === key) {
            sortState.dir = sortState.dir === 'asc' ? 'desc' : 'asc';
        } else {
            sortState.key = key;
            sortState.dir = 'asc';
        }

        // Mise à jour des flèches
        document.querySelectorAll('.table-protex thead th.sortable').forEach(t => {
            t.classList.remove('sort-asc', 'sort-desc');
            const ico = t.querySelector('.sort-ico');
            if (ico) ico.textContent = '↕';
        });
        th.classList.add(sortState.dir === 'asc' ? 'sort-asc' : 'sort-desc');
        const icoTh = th.querySelector('.sort-ico');
        if (icoTh) icoTh.textContent = sortState.dir === 'asc' ? '↑' : '↓';

        sortRows(key, type, sortState.dir);
    });
});

function sortRows(key, type, dir) {
    const tbody = document.getElementById('offresTableBody');
    const rowsArr = Array.from(tbody.querySelectorAll('.offre-row'));

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

/* ════════════════════════════════════════════════════════════
   6) MENU EXPORT (toggle)
═══════════════════════════════════════════════════════════════ */
function toggleExportMenu(e) {
    e?.stopPropagation();
    document.getElementById('exportMenu')?.classList.toggle('show');
}
document.addEventListener('click', (e) => {
    const menu = document.getElementById('exportMenu');
    if (!menu) return;
    if (!e.target.closest('.export-wrapper')) menu.classList.remove('show');
});

/* ════════════════════════════════════════════════════════════
   7) EXPORT (CSV / Excel / PDF) — RESPECTE LES FILTRES
═══════════════════════════════════════════════════════════════ */
function getVisibleData() {
    const data = [];
    rows.forEach(row => {
        if (row.style.display === 'none') return;
        const cells = row.querySelectorAll('td');
        data.push({
            id:       row.dataset.id,
            nom:      cells[1].querySelector('.offre-title')?.textContent.trim() || '',
            desc:     cells[1].querySelector('.offre-desc')?.textContent.trim() || '',
            type:     row.dataset.type || '',
            prixm:    row.dataset.prixm,
            prixa:    row.dataset.prixa,
            plafond:  row.dataset.plafond,
            statut:   row.dataset.statut,
            date:     cells[7].textContent.trim()
        });
    });
    return data;
}

function downloadFile(content, filename, mime) {
    const blob = new Blob(["\uFEFF" + content], { type: mime + ';charset=utf-8' });
    const url  = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function exportData(format) {
    document.getElementById('exportMenu')?.classList.remove('show');
    const data = getVisibleData();
    if (data.length === 0) {
        alert('⚠️ Aucune donnée à exporter. Vérifiez vos filtres.');
        return;
    }
    const ts = new Date().toISOString().slice(0,16).replace(/[:T]/g,'-');

    if (format === 'csv')   exportCSV(data, ts);
    if (format === 'excel') exportExcel(data, ts);
    if (format === 'pdf')   exportPDF(data, ts);
}

function exportCSV(data, ts) {
    const sep = ';';
    const headers = ['ID','Nom de l\'offre','Description','Type','Prix mensuel (TND)','Prix annuel (TND)','Plafond (TND)','Statut','Date création'];
    const escape  = (s) => '"' + String(s).replace(/"/g,'""') + '"';
    const lines   = [ headers.map(escape).join(sep) ];

    data.forEach(d => {
        lines.push([
            d.id, d.nom, d.desc,
            d.type.charAt(0).toUpperCase() + d.type.slice(1),
            parseFloat(d.prixm).toFixed(3),
            parseFloat(d.prixa).toFixed(3),
            d.plafond,
            d.statut.charAt(0).toUpperCase() + d.statut.slice(1),
            d.date
        ].map(escape).join(sep));
    });

    downloadFile(lines.join('\n'), `offres_protex_${ts}.csv`, 'text/csv');
}

function exportExcel(data, ts) {
    let html = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head><meta charset="UTF-8"><style>
table{border-collapse:collapse;font-family:Arial;font-size:12px;}
th{background:#FF6B1A;color:#fff;font-weight:bold;padding:10px;border:1px solid #999;text-align:center;}
td{padding:8px;border:1px solid #ccc;}
.title{font-size:18px;font-weight:bold;color:#1A3A7A;}
.sub{font-size:11px;color:#666;}
.num{text-align:right;}
.center{text-align:center;}
.active{background:#d4edda;color:#155724;}
.suspendue{background:#fff3cd;color:#856404;}
.archivee{background:#f8d7da;color:#721c24;}
</style></head><body>
<table>
<tr><td colspan="9" class="title">PROTEX ASSURANCE — Catalogue des offres</td></tr>
<tr><td colspan="9" class="sub">Exporté le ${new Date().toLocaleString('fr-FR')} — ${data.length} offre(s)</td></tr>
<tr><td colspan="9">&nbsp;</td></tr>
<tr>
    <th>ID</th><th>Nom</th><th>Description</th><th>Type</th>
    <th>Prix mensuel</th><th>Prix annuel</th><th>Plafond</th>
    <th>Statut</th><th>Date création</th>
</tr>`;

    data.forEach(d => {
        html += `<tr>
            <td class="center">#${d.id}</td>
            <td>${d.nom}</td>
            <td>${d.desc}</td>
            <td class="center">${d.type.charAt(0).toUpperCase() + d.type.slice(1)}</td>
            <td class="num">${parseFloat(d.prixm).toFixed(3)} TND</td>
            <td class="num">${parseFloat(d.prixa).toFixed(3)} TND</td>
            <td class="num">${d.plafond > 0 ? parseFloat(d.plafond).toLocaleString('fr-FR') + ' TND' : '—'}</td>
            <td class="center ${d.statut}">${d.statut.charAt(0).toUpperCase() + d.statut.slice(1)}</td>
            <td class="center">${d.date}</td>
        </tr>`;
    });

    html += `</table></body></html>`;
    downloadFile(html, `offres_protex_${ts}.xls`, 'application/vnd.ms-excel');
}

function exportPDF(data, ts) {
    let rowsHtml = '';
    data.forEach(d => {
        rowsHtml += `<tr>
            <td>#${d.id}</td>
            <td><strong>${d.nom}</strong><br><small style="color:#666">${d.desc || ''}</small></td>
            <td>${d.type.charAt(0).toUpperCase() + d.type.slice(1)}</td>
            <td style="text-align:right">${parseFloat(d.prixm).toFixed(3)} TND</td>
            <td style="text-align:right">${parseFloat(d.prixa).toFixed(3)} TND</td>
            <td style="text-align:right">${d.plafond > 0 ? parseFloat(d.plafond).toLocaleString('fr-FR') + ' TND' : '—'}</td>
            <td>${d.statut.charAt(0).toUpperCase() + d.statut.slice(1)}</td>
            <td>${d.date}</td>
        </tr>`;
    });

    const content = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Export Offres</title>
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
    <p class="print-info">Catalogue des offres — Export du ${new Date().toLocaleString('fr-FR')} — ${data.length} offre(s)</p>
</div>
<table>
    <thead>
        <tr>
            <th>ID</th><th>Offre</th><th>Type</th>
            <th>Prix mensuel</th><th>Prix annuel</th><th>Plafond</th>
            <th>Statut</th><th>Date</th>
        </tr>
    </thead>
    <tbody>${rowsHtml}</tbody>
</table>
<p style="margin-top:20px;font-size:10px;color:#888;text-align:center;">
    Document généré automatiquement par Protex Admin — ${new Date().toLocaleDateString('fr-FR')}
</p>
<script>window.onload = function() { window.print(); }<\/script>
</body></html>`;
    const win = window.open('', '_blank');
    if (win) { win.document.write(content); win.document.close(); }
}
</script>
</body>
</html>
