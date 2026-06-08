<?php
/**
 * view/BackOffice/devis/liste.php
 * Liste des devis — Back-office Protex
 * Appelée par DevisController.php?action=index
 */

// $devis, $stats, $message, $erreur viennent du contrôleur
$devis   = $devis   ?? [];
$stats   = $stats   ?? [];
$message = $message ?? '';
$erreur  = $erreur  ?? '';

$base = (defined('BASE_URL') ? BASE_URL : '');

// ── Helpers ─────────────────────────────────────────────────────
function statutBadge(string $statut): string {
    $map = [
        'en_attente' => ['class' => 'status-en_attente', 'icon' => 'hourglass-split', 'label' => 'En attente'],
        'en_cours'   => ['class' => 'status-en_cours',   'icon' => 'arrow-repeat',     'label' => 'En cours'],
        'accepte'    => ['class' => 'status-accepte',    'icon' => 'check-circle',     'label' => 'Accepté'],
        'refuse'     => ['class' => 'status-refuse',     'icon' => 'x-circle',         'label' => 'Refusé'],
        'expire'     => ['class' => 'status-expire',     'icon' => 'clock-history',    'label' => 'Expiré'],
    ];

    $s = $map[$statut] ?? ['class' => 'status-en_attente', 'icon' => 'circle', 'label' => $statut];

    return '<span class="devis-status ' . htmlspecialchars($s['class']) . '">' .
           '<i class="bi bi-' . htmlspecialchars($s['icon']) . '"></i> ' .
           htmlspecialchars($s['label']) .
           '</span>';
}

function typeBadge(string $type): string {
    $map = [
        'auto'       => ['class' => 'type-auto',       'icon' => 'car-front',   'label' => 'Auto'],
        'habitation' => ['class' => 'type-habitation', 'icon' => 'house-door',  'label' => 'Habitation'],
        'sante'      => ['class' => 'type-sante',      'icon' => 'heart-pulse', 'label' => 'Santé'],
    ];

    $t = $map[$type] ?? ['class' => 'type-auto', 'icon' => 'file-earmark', 'label' => $type];

    return '<span class="devis-type-badge ' . htmlspecialchars($t['class']) . '">' .
           '<i class="bi bi-' . htmlspecialchars($t['icon']) . '"></i> ' .
           htmlspecialchars($t['label']) .
           '</span>';
}

function initiales(array $d): string {
    return strtoupper(
        mb_substr((string)($d['prenom'] ?? ''), 0, 1, 'UTF-8') .
        mb_substr((string)($d['nom'] ?? ''), 0, 1, 'UTF-8')
    );
}

function formatDate(?string $date): string {
    if (!$date) return '—';
    try {
        $dt = new DateTime($date);
        return $dt->format('d/m/Y');
    } catch (Exception $e) {
        return htmlspecialchars($date);
    }
}

function formatMontant($v): string {
    if ($v === null || $v === '') return '—';
    return number_format((float)$v, 3, '.', ' ') . ' DT';
}

function devisReference($id): string {
    return 'DEV-2026-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
}

// ── Statistiques ────────────────────────────────────────────────
// On utilise $stats du contrôleur si disponible, sinon fallback
$total    = isset($stats['total'])        ? (int)$stats['total']        : count($devis);
$attente  = isset($stats['en_attente'])   ? (int)$stats['en_attente']   : count(array_filter($devis, fn($d) => ($d['statut'] ?? '') === 'en_attente'));
$acceptes = isset($stats['acceptes'])     ? (int)$stats['acceptes']     : count(array_filter($devis, fn($d) => ($d['statut'] ?? '') === 'accepte'));
$refuses  = isset($stats['refuses'])      ? (int)$stats['refuses']      : count(array_filter($devis, fn($d) => ($d['statut'] ?? '') === 'refuse'));
$moy      = isset($stats['montant_moyen']) ? (float)$stats['montant_moyen'] : 0.0;

if (!isset($stats['montant_moyen'])) {
    $montants = array_filter(array_column($devis, 'montant_estime'), fn($v) => $v !== null && $v !== '');
    $moy = count($montants) > 0 ? array_sum(array_map('floatval', $montants)) / count($montants) : 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Devis — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="<?= htmlspecialchars($base) ?>/view/BackOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($base) ?>/view/BackOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($base) ?>/view/BackOffice/assets/css/layout.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($base) ?>/view/BackOffice/assets/css/admin-users.css">

    <style>
        .devis-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .type-auto {
            background: rgba(0,194,255,.12);
            color: #8fe9ff;
            border: 1px solid rgba(0,194,255,.25);
        }

        .type-habitation {
            background: rgba(255,153,0,.12);
            color: #ffd28a;
            border: 1px solid rgba(255,153,0,.25);
        }

        .type-sante {
            background: rgba(0,214,143,.12);
            color: #94ffd8;
            border: 1px solid rgba(0,214,143,.25);
        }

        .devis-status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-en_attente {
            background: rgba(255,193,7,.12);
            border: 1px solid rgba(255,193,7,.24);
            color: #ffd66e;
        }

        .status-en_cours {
            background: rgba(13,202,240,.12);
            border: 1px solid rgba(13,202,240,.24);
            color: #8eeaff;
        }

        .status-accepte {
            background: rgba(25,135,84,.12);
            border: 1px solid rgba(25,135,84,.24);
            color: #90f1bc;
        }

        .status-refuse {
            background: rgba(220,53,69,.12);
            border: 1px solid rgba(220,53,69,.24);
            color: #ff9cab;
        }

        .status-expire {
            background: rgba(108,117,125,.16);
            border: 1px solid rgba(108,117,125,.24);
            color: #d0d5dd;
        }

        .avatar-cell {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 13px;
            color: #fff;
            background: linear-gradient(135deg, rgba(255,107,26,.95), rgba(255,140,66,.85));
            flex-shrink: 0;
        }

        .client-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .client-name {
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
        }

        .client-email {
            color: var(--text-secondary);
            font-size: 12px;
            margin-top: 2px;
        }

        .ref-cell {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 12px;
            color: var(--text-secondary);
        }

        .amount {
            font-weight: 800;
            color: #fff;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 18px;
            margin-bottom: 22px;
        }

        .kpi-card {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            background: linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02));
            border: 1px solid rgba(255,255,255,.08);
            padding: 18px;
            box-shadow: 0 20px 40px rgba(0,0,0,.14);
        }

        .kpi-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }

        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            font-size: 20px;
            color: #fff;
            border: 1px solid rgba(255,255,255,.10);
        }

        .kpi-blue .kpi-icon   { background: linear-gradient(135deg, rgba(0,194,255,.85), rgba(16,85,255,.75)); }
        .kpi-gold .kpi-icon   { background: linear-gradient(135deg, rgba(255,166,0,.9), rgba(255,107,26,.85)); }
        .kpi-green .kpi-icon  { background: linear-gradient(135deg, rgba(0,214,143,.9), rgba(0,166,126,.8)); }
        .kpi-red .kpi-icon    { background: linear-gradient(135deg, rgba(255,92,92,.9), rgba(220,53,69,.85)); }
        .kpi-purple .kpi-icon { background: linear-gradient(135deg, rgba(137,100,255,.9), rgba(88,80,236,.85)); }

        .kpi-value {
            font-size: 28px;
            font-weight: 800;
            color: #fff;
            line-height: 1;
            margin-bottom: 4px;
        }

        .kpi-label {
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 600;
        }

        .kpi-trend {
            margin-top: 10px;
            font-size: 12px;
            color: var(--text-secondary);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .toolbar-grid {
            display: grid;
            grid-template-columns: 1.3fr 1fr 1fr auto;
            gap: 12px;
            align-items: center;
            padding: 16px 24px;
            border-bottom: 1px solid var(--glass-border);
        }

        .quick-pills {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            padding: 12px 24px;
            border-bottom: 1px solid var(--glass-border);
        }

        .qpill {
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.04);
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all .2s;
            text-decoration: none;
        }

        .qpill:hover,
        .qpill.active {
            background: rgba(255,107,26,.14);
            border-color: rgba(255,107,26,.22);
            color: #fff;
        }

        .alert-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 18px;
        }

        .alert-success {
            background: rgba(0,214,143,.10);
            border: 1px solid rgba(0,214,143,.24);
            color: #90f1bc;
        }

        .alert-danger {
            background: rgba(220,53,69,.10);
            border: 1px solid rgba(220,53,69,.24);
            color: #ff9cab;
        }

        .page-header-flex {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .empty-state {
            text-align: center;
            padding: 52px 18px;
            color: var(--text-secondary);
        }

        .empty-state i {
            display: block;
            font-size: 36px;
            margin-bottom: 12px;
            color: rgba(255,255,255,.3);
        }

        .spin {
            animation: spin .8s linear infinite;
        }

        .delete-form {
            display: inline;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media(max-width:1300px) {
            .kpi-grid { grid-template-columns: repeat(3,1fr); }
        }

        @media(max-width:900px) {
            .kpi-grid { grid-template-columns: repeat(2,1fr); }
            .toolbar-grid { grid-template-columns: 1fr 1fr; }
        }

        @media(max-width:640px) {
            .kpi-grid,
            .toolbar-grid { grid-template-columns: 1fr; }
        }

        .sortable-dev { cursor: pointer; user-select: none; transition: .15s ease; }
        .sortable-dev:hover { background: rgba(255,107,26,.1); color: #fff; }
        .sort-ico-dev { display: inline-block; margin-left: 6px; opacity: .35; font-size: 10px; transition: .15s ease; }
        .sort-asc-dev .sort-ico-dev, .sort-desc-dev .sort-ico-dev { opacity: 1; color: #ff9b5e; }

        .export-menu-dev.show { opacity: 1 !important; visibility: visible !important; transform: translateY(0) !important; }
        .export-menu-dev button:hover { background: rgba(255,255,255,.06); }

        @media print {
            body * { visibility: hidden !important; }
            #printAreaDev { display: block !important; position: absolute !important; left: 0; top: 0; width: 100%; background: #fff !important; color: #000 !important; padding: 20px !important; font-family: Arial, sans-serif !important; }
            #printAreaDev, #printAreaDev * { visibility: visible !important; }
            #printAreaDev table { width:100%; border-collapse:collapse; font-size:11px; }
            #printAreaDev th, #printAreaDev td { border: 1px solid #999 !important; padding: 7px 9px !important; color: #000 !important; }
            #printAreaDev th { background: #FF6B1A !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; text-align: left; }
            #printAreaDev .print-header { border-bottom: 3px solid #FF6B1A !important; padding-bottom: 12px; margin-bottom: 18px; }
            #printAreaDev .print-brand { font-size: 24px; font-weight: 800; color: #1A3A7A !important; margin: 0; }
            #printAreaDev .print-brand span { color: #FF6B1A !important; }
            #printAreaDev .print-info { color: #666 !important; font-size: 12px; margin-top: 4px; }
        }
        #printAreaDev { display: none !important; }
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
                <div class="topbar-title">Suivi des devis clients</div>
                <div class="topbar-sub"><?= htmlspecialchars(date('d/m/Y')) ?></div>
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

            <div class="page-header-flex">
                <div>
                    <div class="page-title">Devis</div>
                    <div class="page-breadcrumb">
                        <i class="bi bi-house"></i>
                        <a href="<?= htmlspecialchars($base) ?>/view/BackOffice/admin.php">Accueil</a>
                        <i class="bi bi-chevron-right" style="font-size:10px"></i>
                        <span>Devis</span>
                    </div>
                </div>
                <div class="header-actions">
                    <span style="padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.05);color:var(--text-secondary);border:1px solid rgba(255,255,255,.07);font-size:12px;font-weight:700;">
                        <i class="bi bi-building-check"></i> Assurance en ligne
                    </span>
                    <a href="<?= htmlspecialchars($base) ?>/controller/DevisController.php?action=index" class="btn btn-outline">
                        <i class="bi bi-arrow-clockwise"></i> Actualiser
                    </a>
                   
                </div>
            </div>
            

            <?php if ($message): ?>
                <div class="alert-bar alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($erreur): ?>
                <div class="alert-bar alert-danger">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <?= htmlspecialchars($erreur) ?>
                </div>
            <?php endif; ?>

            <div class="kpi-grid">
                <div class="kpi-card kpi-blue">
                    <div class="kpi-top">
                        <div>
                            <div class="kpi-value"><?= $total ?></div>
                            <div class="kpi-label">Total devis</div>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-files"></i></div>
                    </div>
                    <div class="kpi-trend"><i class="bi bi-bar-chart-line"></i> Vue globale</div>
                </div>

                <div class="kpi-card kpi-gold">
                    <div class="kpi-top">
                        <div>
                            <div class="kpi-value"><?= $attente ?></div>
                            <div class="kpi-label">En attente</div>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-hourglass-split"></i></div>
                    </div>
                    <div class="kpi-trend"><i class="bi bi-clock-history"></i> À traiter</div>
                </div>

                <div class="kpi-card kpi-green">
                    <div class="kpi-top">
                        <div>
                            <div class="kpi-value"><?= $acceptes ?></div>
                            <div class="kpi-label">Acceptés</div>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-check2-circle"></i></div>
                    </div>
                    <div class="kpi-trend"><i class="bi bi-check-circle"></i> Convertis</div>
                </div>

                <div class="kpi-card kpi-red">
                    <div class="kpi-top">
                        <div>
                            <div class="kpi-value"><?= $refuses ?></div>
                            <div class="kpi-label">Refusés</div>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-x-octagon"></i></div>
                    </div>
                    <div class="kpi-trend"><i class="bi bi-exclamation-circle"></i> Non retenus</div>
                </div>

                <div class="kpi-card kpi-purple">
                    <div class="kpi-top">
                        <div>
                            <div class="kpi-value"><?= number_format($moy, 0) ?></div>
                            <div class="kpi-label">Montant moy. DT</div>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-cash-stack"></i></div>
                    </div>
                    <div class="kpi-trend"><i class="bi bi-coin"></i> Estimation moyenne</div>
                </div>
            </div>

            <div style="margin-bottom:18px;padding:18px 20px;border-radius:22px;border:1px solid rgba(255,255,255,.08);background:linear-gradient(135deg,rgba(255,107,26,.12),rgba(255,255,255,.03));display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;">
                <div>
                    <div style="color:#fff;font-weight:800;font-size:15px;margin-bottom:6px;">Pilotage des demandes de devis</div>
                    <div style="color:var(--text-secondary);font-size:13px;">Consultez, modifiez et supprimez les devis clients. Accédez aux détails par type d'assurance.</div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <span style="padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);color:#fff;font-size:12px;font-weight:700;"><i class="bi bi-car-front"></i> Auto</span>
                    <span style="padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);color:#fff;font-size:12px;font-weight:700;"><i class="bi bi-house-door"></i> Habitation</span>
                    <span style="padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);color:#fff;font-size:12px;font-weight:700;"><i class="bi bi-heart-pulse"></i> Santé</span>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-table"></i> Liste des devis</div>
                    <span style="font-size:12px;color:var(--text-secondary);"><i class="bi bi-info-circle"></i> Utilisez les filtres puis le bouton Exporter</span>
                </div>

                <div class="toolbar-grid">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchInput" placeholder="Rechercher par client, email, référence...">
                    </div>

                    <select class="filter-select" id="filterType">
                        <option value="">Tous les types</option>
                        <option value="auto" <?= $type === 'auto' ? 'selected' : '' ?>>Auto</option>
                        <option value="habitation" <?= $type === 'habitation' ? 'selected' : '' ?>>Habitation</option>
                        <option value="sante" <?= $type === 'sante' ? 'selected' : '' ?>>Santé</option>
                    </select>

                    <select class="filter-select" id="filterStatut">
                        <option value="">Tous les statuts</option>
                        <option value="en_attente" <?= $statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                        <option value="en_cours" <?= $statut === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                        <option value="accepte" <?= $statut === 'accepte' ? 'selected' : '' ?>>Accepté</option>
                        <option value="refuse" <?= $statut === 'refuse' ? 'selected' : '' ?>>Refusé</option>
                        <option value="expire" <?= $statut === 'expire' ? 'selected' : '' ?>>Expiré</option>
                    </select>

                    <button class="btn btn-outline btn-sm" type="button" onclick="resetFilters()">
                        <i class="bi bi-x-circle"></i> Réinitialiser
                    </button>
                </div>

                <div style="display:flex;align-items:center;gap:10px;padding:10px 24px;border-bottom:1px solid var(--glass-border);flex-wrap:wrap;">
                    <label style="font-size:12px;color:var(--text-secondary);font-weight:700;">Du</label>
                    <input type="date" id="dateDebut" value="<?= htmlspecialchars($dateDebut ?? '') ?>" style="height:32px;padding:0 10px;border-radius:8px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.05);color:#fff;font-size:12px;">
                    <label style="font-size:12px;color:var(--text-secondary);font-weight:700;">Au</label>
                    <input type="date" id="dateFin" value="<?= htmlspecialchars($dateFin ?? '') ?>" style="height:32px;padding:0 10px;border-radius:8px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.05);color:#fff;font-size:12px;">

                    <div style="position:relative;margin-left:auto;" class="export-wrapper-dev">
                        <button class="btn btn-outline btn-sm" type="button" onclick="toggleExportDev()" style="display:inline-flex;align-items:center;gap:6px;">
                            <i class="bi bi-download"></i> Exporter <i class="bi bi-chevron-down" style="font-size:10px"></i>
                        </button>
                        <div class="export-menu-dev" id="exportMenuDev" style="position:absolute;top:calc(100% + 6px);right:0;min-width:200px;background:#0e1c33;border:1px solid rgba(255,255,255,.12);border-radius:12px;padding:6px;box-shadow:0 16px 40px rgba(0,0,0,.5);z-index:100;opacity:0;visibility:hidden;transform:translateY(-6px);transition:.2s ease;">
                            <button type="button" onclick="exportDevData('csv')" style="width:100%;padding:10px 12px;border-radius:8px;border:none;background:transparent;color:#fff;text-align:left;cursor:pointer;font-size:12px;font-weight:600;display:flex;align-items:center;gap:10px;transition:.15s;">
                                <i class="bi bi-filetype-csv" style="font-size:16px;color:#86efac;"></i> Exporter en CSV
                            </button>
                            <button type="button" onclick="exportDevData('excel')" style="width:100%;padding:10px 12px;border-radius:8px;border:none;background:transparent;color:#fff;text-align:left;cursor:pointer;font-size:12px;font-weight:600;display:flex;align-items:center;gap:10px;transition:.15s;">
                                <i class="bi bi-file-earmark-excel-fill" style="font-size:16px;color:#6ee7b7;"></i> Exporter en Excel
                            </button>
                            <button type="button" onclick="exportDevData('pdf')" style="width:100%;padding:10px 12px;border-radius:8px;border:none;background:transparent;color:#fff;text-align:left;cursor:pointer;font-size:12px;font-weight:600;display:flex;align-items:center;gap:10px;transition:.15s;">
                                <i class="bi bi-file-earmark-pdf-fill" style="font-size:16px;color:#fca5a5;"></i> Exporter en PDF
                            </button>
                        </div>
                    </div>
                </div>

                <div class="quick-pills">
                    <a href="#" class="qpill active" data-quick="all">Tous (<?= $total ?>)</a>
                    <a href="#" class="qpill" data-quick="en_attente">À traiter (<?= $attente ?>)</a>
                    <a href="#" class="qpill" data-quick="accepte">Acceptés (<?= $acceptes ?>)</a>
                    <a href="#" class="qpill" data-quick="refuse">Refusés (<?= $refuses ?>)</a>
                    <a href="#" class="qpill" data-quick="auto">Auto</a>
                    <a href="#" class="qpill" data-quick="habitation">Habitation</a>
                    <a href="#" class="qpill" data-quick="sante">Santé</a>
                </div>

                <div class="table-wrap">
                    <table id="devisTable">
                        <thead>
                            <tr>
                                <th class="sortable-dev" data-key="client" data-type="text">Client <span class="sort-ico-dev">↕</span></th>
                                <th class="sortable-dev" data-key="reference" data-type="text">Référence <span class="sort-ico-dev">↕</span></th>
                                <th class="sortable-dev" data-key="type" data-type="text">Type <span class="sort-ico-dev">↕</span></th>
                                <th>Offre</th>
                                <th class="sortable-dev" data-key="statut" data-type="text">Statut <span class="sort-ico-dev">↕</span></th>
                                <th class="sortable-dev" data-key="montant" data-type="num">Montant <span class="sort-ico-dev">↕</span></th>
                                <th class="sortable-dev" data-key="date" data-type="date">Date <span class="sort-ico-dev">↕</span></th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="devisBody">
                        <?php if (empty($devis)): ?>
                            <tr id="emptyRow">
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        Aucun devis enregistré pour le moment.
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($devis as $d): ?>
                                <?php
                                    $reference = devisReference($d['id_devis'] ?? 0);
                                    $searchText = mb_strtolower(
                                        trim(
                                            ($d['nom'] ?? '') . ' ' .
                                            ($d['prenom'] ?? '') . ' ' .
                                            ($d['email'] ?? '') . ' ' .
                                            ($d['type_assurance'] ?? '') . ' ' .
                                            ($d['statut'] ?? '') . ' ' .
                                            ($d['nom_offre'] ?? '') . ' ' .
                                            $reference
                                        ),
                                        'UTF-8'
                                    );
                                    $dateTs = !empty($d['date_demande']) ? strtotime((string)$d['date_demande']) : 0;
                                    $clientName = mb_strtolower(trim(($d['prenom'] ?? '') . ' ' . ($d['nom'] ?? '')));
                                ?>
                                <tr
                                    data-type="<?= htmlspecialchars((string)($d['type_assurance'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-statut="<?= htmlspecialchars((string)($d['statut'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>"
                                    data-reference="<?= htmlspecialchars($reference) ?>"
                                    data-client="<?= htmlspecialchars($clientName) ?>"
                                    data-montant="<?= (float)($d['montant_estime'] ?? 0) ?>"
                                    data-date="<?= $dateTs ?>"
                                >
                                    <td>
                                        <div class="client-cell">
                                            <div class="avatar-cell"><?= htmlspecialchars(initiales($d)) ?></div>
                                            <div>
                                                <div class="client-name"><?= htmlspecialchars(trim(($d['prenom'] ?? '') . ' ' . ($d['nom'] ?? ''))) ?></div>
                                                <div class="client-email"><?= htmlspecialchars((string)($d['email'] ?? '')) ?></div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="ref-cell">
                                            <i class="bi bi-upc-scan"></i>
                                            <?= htmlspecialchars($reference) ?>
                                        </span>
                                    </td>

                                    <td><?= typeBadge((string)($d['type_assurance'] ?? '')) ?></td>

                                    <td>
                                        <div style="font-weight:700;color:#fff;font-size:13px;"><?= htmlspecialchars((string)($d['nom_offre'] ?? '—')) ?></div>
                                        <div style="font-size:11px;color:var(--text-secondary);">#<?= htmlspecialchars((string)($d['id_offre'] ?? '—')) ?></div>
                                    </td>

                                    <td><?= statutBadge((string)($d['statut'] ?? '')) ?></td>

                                    <td><span class="amount"><?= htmlspecialchars(formatMontant($d['montant_estime'] ?? null)) ?></span></td>

                                    <td style="color:var(--text-secondary);font-size:13px;"><?= htmlspecialchars(formatDate($d['date_demande'] ?? null)) ?></td>

                                    <td>
                                        <div class="actions">
                                            <a href="<?= BASE_URL ?>/controller/DevisController.php?action=details&id=<?= urlencode((string)$d['id_devis']) ?>"
                                               class="btn btn-outline btn-sm"
                                               title="Voir">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <a href="<?= BASE_URL ?>/controller/DevisController.php?action=modifier&id=<?= urlencode((string)$d['id_devis']) ?>"
                                            class="btn btn-outline btn-sm"
                                            title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>

                                            <?php if (($d['statut'] ?? '') === 'accepte'): ?>
                                            <a href="<?= BASE_URL ?>/controller/DevisController.php?action=convertir&id=<?= urlencode((string)$d['id_devis']) ?>"
                                               class="btn btn-outline btn-sm"
                                               title="Convertir en contrat"
                                               style="border-color:rgba(0,214,143,.3);color:#90f1bc;">
                                                <i class="bi bi-file-earmark-check"></i>
                                            </a>
                                            <?php endif; ?>

                                            <form class="delete-form"
                                                action="<?= BASE_URL ?>/controller/DevisController.php?action=supprimer&id=<?= urlencode((string)$d['id_devis']) ?>"
                                                method="POST"
                                                onsubmit="return confirm('Supprimer le devis <?= htmlspecialchars($reference, ENT_QUOTES) ?> ?')">

                                                <button type="submit"
                                                        class="btn btn-danger btn-sm"
                                                        title="Supprimer">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
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

<div id="printAreaDev"></div>

<script src="<?= htmlspecialchars($base) ?>/view/BackOffice/assets/js/main.js"></script>
<script>
const perPage = 10;
let currentPage = 1;
let currentQuick = 'all';

function getAllRows() {
    return Array.from(document.querySelectorAll('#devisBody tr[data-type]'));
}

function getFiltered() {
    const search = document.getElementById('searchInput').value.trim().toLowerCase();
    const type = document.getElementById('filterType').value;
    const statut = document.getElementById('filterStatut').value;
    const dateDebut = document.getElementById('dateDebut')?.value || '';
    const dateFin = document.getElementById('dateFin')?.value || '';

    const dateDebutTs = dateDebut ? new Date(dateDebut).getTime() : 0;
    const dateFinTs = dateFin ? new Date(dateFin + 'T23:59:59').getTime() : Infinity;

    return getAllRows().filter(row => {
        const rowSearch = (row.dataset.search || '').toLowerCase();
        const rowType = row.dataset.type || '';
        const rowStatut = row.dataset.statut || '';
        const rowDate = parseInt(row.dataset.date) || 0;

        const quickOk = currentQuick === 'all'
            || rowStatut === currentQuick
            || rowType === currentQuick;

        return (!search || rowSearch.includes(search))
            && (!type || rowType === type)
            && (!statut || rowStatut === statut)
            && (!dateDebut || rowDate >= dateDebutTs)
            && (!dateFin || rowDate <= dateFinTs)
            && quickOk;
    });
}

function render() {
    const rows = getAllRows();
    const filtered = getFiltered();
    const total = filtered.length;
    const pages = Math.max(1, Math.ceil(total / perPage));

    if (currentPage > pages) currentPage = pages;

    rows.forEach(row => row.style.display = 'none');

    const slice = filtered.slice((currentPage - 1) * perPage, currentPage * perPage);
    slice.forEach(row => row.style.display = '');

    let emptyRow = document.getElementById('emptyRow');
    if (rows.length > 0) {
        if (slice.length === 0) {
            if (!emptyRow) {
                emptyRow = document.createElement('tr');
                emptyRow.id = 'emptyRow';
                emptyRow.innerHTML = '<td colspan="8"><div class="empty-state"><i class="bi bi-funnel"></i>Aucun devis ne correspond aux filtres.</div></td>';
                document.getElementById('devisBody').appendChild(emptyRow);
            } else {
                emptyRow.innerHTML = '<td colspan="8"><div class="empty-state"><i class="bi bi-funnel"></i>Aucun devis ne correspond aux filtres.</div></td>';
                emptyRow.style.display = '';
            }
        } else if (emptyRow) emptyRow.remove();
    }

    const start = total === 0 ? 0 : (currentPage - 1) * perPage + 1;
    const end = Math.min(currentPage * perPage, total);
    document.getElementById('paginationInfo').textContent = `Affichage ${start}–${end} sur ${total} devis`;

    const btns = document.getElementById('paginationBtns');
    if (pages <= 1) { btns.innerHTML = ''; return; }
    btns.innerHTML = `
        <button class="page-btn" onclick="goPage(${currentPage - 1})" ${currentPage <= 1 ? 'disabled' : ''}>
            <i class="bi bi-chevron-left"></i>
        </button>
        ${Array.from({length: pages}, (_, i) =>
            `<button class="page-btn ${i + 1 === currentPage ? 'active' : ''}" onclick="goPage(${i + 1})">${i + 1}</button>`
        ).join('')}
        <button class="page-btn" onclick="goPage(${currentPage + 1})" ${currentPage >= pages ? 'disabled' : ''}>
            <i class="bi bi-chevron-right"></i>
        </button>
    `;
}

function goPage(p) {
    const pages = Math.max(1, Math.ceil(getFiltered().length / perPage));
    if (p < 1 || p > pages) return;
    currentPage = p;
    render();
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterType').value = '';
    document.getElementById('filterStatut').value = '';
    const dd = document.getElementById('dateDebut'); if (dd) dd.value = '';
    const df = document.getElementById('dateFin'); if (df) df.value = '';
    currentQuick = 'all';
    document.querySelectorAll('.qpill').forEach(p => p.classList.toggle('active', p.dataset.quick === 'all'));
    document.querySelectorAll('.sortable-dev').forEach(th => {
        th.classList.remove('sort-asc-dev', 'sort-desc-dev');
        const ico = th.querySelector('.sort-ico-dev');
        if (ico) ico.textContent = '↕';
    });
    currentPage = 1;
    render();
}

document.querySelectorAll('.qpill').forEach(pill => {
    pill.addEventListener('click', function(e) {
        e.preventDefault();
        currentQuick = this.dataset.quick;
        document.querySelectorAll('.qpill').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        currentPage = 1;
        render();
    });
});

document.getElementById('searchInput').addEventListener('input', () => { currentPage = 1; render(); });
document.getElementById('filterType').addEventListener('change', () => { currentPage = 1; render(); });
document.getElementById('filterStatut').addEventListener('change', () => { currentPage = 1; render(); });
const ddEl = document.getElementById('dateDebut');
const dfEl = document.getElementById('dateFin');
if (ddEl) ddEl.addEventListener('change', () => { currentPage = 1; render(); });
if (dfEl) dfEl.addEventListener('change', () => { currentPage = 1; render(); });

render();

let devSortState = { key: null, dir: 'asc' };
document.querySelectorAll('.sortable-dev').forEach(th => {
    th.addEventListener('click', () => {
        const key = th.dataset.key;
        const type = th.dataset.type || 'text';
        if (devSortState.key === key) devSortState.dir = devSortState.dir === 'asc' ? 'desc' : 'asc';
        else { devSortState.key = key; devSortState.dir = 'asc'; }
        document.querySelectorAll('.sortable-dev').forEach(t => {
            t.classList.remove('sort-asc-dev', 'sort-desc-dev');
            const ico = t.querySelector('.sort-ico-dev');
            if (ico) ico.textContent = '↕';
        });
        th.classList.add(devSortState.dir === 'asc' ? 'sort-asc-dev' : 'sort-desc-dev');
        const icoTh = th.querySelector('.sort-ico-dev');
        if (icoTh) icoTh.textContent = devSortState.dir === 'asc' ? '↑' : '↓';
        sortDevRows(key, type, devSortState.dir);
    });
});

function sortDevRows(key, type, dir) {
    const tbody = document.getElementById('devisBody');
    const rowsArr = Array.from(tbody.querySelectorAll('tr[data-type]'));
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

function toggleExportDev() {
    document.getElementById('exportMenuDev')?.classList.toggle('show');
}
document.addEventListener('click', (e) => {
    const menu = document.getElementById('exportMenuDev');
    if (!menu) return;
    if (!e.target.closest('.export-wrapper-dev')) menu.classList.remove('show');
});

function getVisibleDevData() {
    const data = [];
    getAllRows().forEach(row => {
        if (row.style.display === 'none') return;
        const cells = row.querySelectorAll('td');
        data.push({
            reference: row.dataset.reference || '',
            client: cells[0]?.querySelector('.client-name')?.textContent.trim() || '',
            email: cells[0]?.querySelector('.client-email')?.textContent.trim() || '',
            type: row.dataset.type || '',
            offre: cells[3]?.textContent.trim() || '',
            statut: row.dataset.statut || '',
            montant: row.dataset.montant || '0',
            date: cells[6]?.textContent.trim() || '',
        });
    });
    return data;
}

function downloadDevFile(content, filename, mime) {
    const blob = new Blob(["\uFEFF" + content], { type: mime + ';charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = filename;
    document.body.appendChild(a); a.click();
    document.body.removeChild(a); URL.revokeObjectURL(url);
}

function exportDevData(format) {
    document.getElementById('exportMenuDev')?.classList.remove('show');
    const data = getVisibleDevData();
    if (data.length === 0) { alert('⚠️ Aucune donnée à exporter.'); return; }
    const ts = new Date().toISOString().slice(0,16).replace(/[:T]/g,'-');
    if (format === 'csv') exportDevCSV(data, ts);
    if (format === 'excel') exportDevExcel(data, ts);
    if (format === 'pdf') exportDevPDF(data, ts);
}

function exportDevCSV(data, ts) {
    const sep = ';';
    const headers = ['Référence','Client','Email','Type','Offre','Statut','Montant (DT)','Date'];
    const esc = (s) => '"' + String(s).replace(/"/g,'""') + '"';
    const lines = [headers.map(esc).join(sep)];
    data.forEach(d => {
        lines.push([d.reference, d.client, d.email, ucfirst(d.type), d.offre, ucfirst(d.statut), parseFloat(d.montant).toFixed(3), d.date].map(esc).join(sep));
    });
    downloadDevFile(lines.join('\n'), `devis_protex_${ts}.csv`, 'text/csv');
}

function exportDevExcel(data, ts) {
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
<tr><td colspan="8" class="title">PROTEX ASSURANCE — Rapport des devis</td></tr>
<tr><td colspan="8" class="sub">Exporté le ${new Date().toLocaleString('fr-FR')} — ${data.length} devis</td></tr>
<tr><td colspan="8">&nbsp;</td></tr>
<tr><th>Référence</th><th>Client</th><th>Email</th><th>Type</th><th>Offre</th><th>Statut</th><th>Montant</th><th>Date</th></tr>`;
    data.forEach(d => {
        html += `<tr>
            <td class="center">${d.reference}</td>
            <td>${d.client}</td>
            <td>${d.email}</td>
            <td class="center">${ucfirst(d.type)}</td>
            <td>${d.offre}</td>
            <td class="center">${ucfirst(d.statut)}</td>
            <td class="num">${parseFloat(d.montant).toFixed(3)} DT</td>
            <td class="center">${d.date}</td>
        </tr>`;
    });
    html += '</table></body></html>';
    downloadDevFile(html, `devis_protex_${ts}.xls`, 'application/vnd.ms-excel');
}

function exportDevPDF(data, ts) {
    let rowsHtml = '';
    data.forEach(d => {
        rowsHtml += `<tr>
            <td>${d.reference}</td>
            <td>${d.client}</td>
            <td>${ucfirst(d.type)}</td>
            <td style="text-align:right">${parseFloat(d.montant).toFixed(3)} DT</td>
            <td>${ucfirst(d.statut)}</td>
            <td>${d.date}</td>
        </tr>`;
    });
    const content = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Export Devis</title>
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
    <p class="print-info">Rapport des devis — Export du ${new Date().toLocaleString('fr-FR')} — ${data.length} devis</p>
</div>
<table><thead><tr>
    <th>Référence</th><th>Client</th><th>Type</th><th>Montant</th>
    <th>Statut</th><th>Date</th>
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




