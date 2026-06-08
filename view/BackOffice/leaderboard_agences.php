<?php
/**
 * MODULE 9 — A2 — Classement des agences SuperAdmin
 */

session_start();
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../helpers/SessionGuard.php';
require_once __DIR__ . '/../../helpers/RoleHelper.php';

SessionGuard::requireRole('superadmin');
$db = config::getConnexion();

$period = $_GET['period'] ?? 'month';
$year = (int)($_GET['year'] ?? date('Y'));
$month = (int)($_GET['month'] ?? date('m'));
$quarter = $_GET['quarter'] ?? '';

$periodLabel = '';
$periodCondition = '1=1';
$params = [];

if ($period === 'month') {
    $periodLabel = sprintf('Mois %02d / %d', $month, $year);
    $periodCondition = 'MONTH(p.date_paiement) = :month AND YEAR(p.date_paiement) = :year';
    $params[':month'] = $month;
    $params[':year'] = $year;
} elseif ($period === 'quarter') {
    $periodLabel = sprintf('Trimestre %s / %d', $quarter ?: '1', $year);
    $quarters = [
        '1' => [1, 3],
        '2' => [4, 6],
        '3' => [7, 9],
        '4' => [10, 12],
    ];
    $q = $quarters[$quarter] ?? $quarters['1'];
    $periodCondition = 'MONTH(p.date_paiement) BETWEEN :start_month AND :end_month AND YEAR(p.date_paiement) = :year';
    $params[':start_month'] = $q[0];
    $params[':end_month'] = $q[1];
    $params[':year'] = $year;
} else {
    $period = 'year';
    $periodLabel = sprintf('Année %d', $year);
    $periodCondition = 'YEAR(p.date_paiement) = :year';
    $params[':year'] = $year;
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="classement_agences_' . date('Ymd_His') . '.csv"');
    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Classement', 'Agence', 'CA', 'Clients', 'Contrats', 'Agents', 'Note moyenne', 'Sinistres en cours', 'Taux sinistres (%)', 'Score global'], ';');

    $stmt = $db->prepare("SELECT a.id_agence, a.nom_agence, a.adresse AS adresse, a.tel AS telephone, a.email,
        COUNT(DISTINCT c.id_user) as nb_clients,
        COUNT(DISTINCT ct.id_contrat) as nb_contrats,
        SUM(CASE WHEN p.statut = 'valide' AND {$periodCondition} THEN p.montant ELSE 0 END) as ca_total,
        AVG(aa.note) as satisfaction_moyenne,
        COUNT(DISTINCT u.id_user) as nb_agents,
        COUNT(DISTINCT s.id_sinistre) as nb_sinistres,
        SUM(CASE WHEN s.statut IN ('en_attente','en_analyse','assigne','en_cours') THEN 1 ELSE 0 END) as nb_sinistres_en_cours
        FROM agence a
        LEFT JOIN `user` u ON u.id_agence = a.id_agence AND u.role = 'agent'
        LEFT JOIN client c ON c.id_agence = a.id_agence
        LEFT JOIN contrat ct ON c.id_user = ct.id_user
        LEFT JOIN paiement p ON ct.id_contrat = p.id_offre
        LEFT JOIN sinistre s ON ct.id_contrat = s.id_contrat
        LEFT JOIN agence_avis aa ON a.id_agence = aa.id_agence
        GROUP BY a.id_agence");
    $stmt->execute($params);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    usort($rows, function ($a, $b) {
        $scoreA = (($a['ca_total'] ?? 0) / 1000) * 0.4 + ($a['nb_clients'] ?? 0) * 1.5 + (($a['satisfaction_moyenne'] ?? 0) * 5) - (($a['nb_sinistres'] ?? 0) * 1.2) + (($a['nb_agents'] ?? 0) * 1.8);
        $scoreB = (($b['ca_total'] ?? 0) / 1000) * 0.4 + ($b['nb_clients'] ?? 0) * 1.5 + (($b['satisfaction_moyenne'] ?? 0) * 5) - (($b['nb_sinistres'] ?? 0) * 1.2) + (($b['nb_agents'] ?? 0) * 1.8);
        return $scoreB <=> $scoreA;
    });

    $rank = 1;
    foreach ($rows as $row) {
        $tauxSinistres = ($row['nb_contrats'] > 0) ? round(($row['nb_sinistres'] / $row['nb_contrats']) * 100, 1) : 0;
        $score = round((($row['ca_total'] ?? 0) / 1000) * 0.4 + ($row['nb_clients'] ?? 0) * 1.5 + (($row['satisfaction_moyenne'] ?? 0) * 5) - $tauxSinistres * 1.2 + (($row['nb_agents'] ?? 0) * 1.8), 1);
        fputcsv($output, [$rank, $row['nom_agence'], number_format((float)$row['ca_total'], 3, ',', ' '), $row['nb_clients'], $row['nb_contrats'], $row['nb_agents'], number_format((float)$row['satisfaction_moyenne'], 1, ',', ' '), $row['nb_sinistres_en_cours'], $tauxSinistres, $score], ';');
        $rank++;
    }
    fclose($output);
    exit;
}

$stmt = $db->prepare("SELECT a.id_agence, a.nom_agence, a.adresse AS adresse, a.tel AS telephone, a.email,
    COUNT(DISTINCT c.id_user) as nb_clients,
    COUNT(DISTINCT ct.id_contrat) as nb_contrats,
    SUM(CASE WHEN p.statut = 'valide' AND {$periodCondition} THEN p.montant ELSE 0 END) as ca_total,
    AVG(aa.note) as satisfaction_moyenne,
    COUNT(DISTINCT u.id_user) as nb_agents,
    COUNT(DISTINCT s.id_sinistre) as nb_sinistres,
    SUM(CASE WHEN s.statut IN ('en_attente','en_analyse','assigne','en_cours') THEN 1 ELSE 0 END) as nb_sinistres_en_cours
    FROM agence a
    LEFT JOIN `user` u ON u.id_agence = a.id_agence AND u.role = 'agent'
    LEFT JOIN client c ON c.id_agence = a.id_agence
    LEFT JOIN contrat ct ON c.id_user = ct.id_user
    LEFT JOIN paiement p ON ct.id_contrat = p.id_offre
    LEFT JOIN sinistre s ON ct.id_contrat = s.id_contrat
    LEFT JOIN agence_avis aa ON a.id_agence = aa.id_agence
    GROUP BY a.id_agence");
$stmt->execute($params);
$agencies = $stmt->fetchAll(PDO::FETCH_ASSOC);

usort($agencies, function ($a, $b) {
    $scoreA = (($a['ca_total'] ?? 0) / 1000) * 0.4 + ($a['nb_clients'] ?? 0) * 1.5 + (($a['satisfaction_moyenne'] ?? 0) * 5) - (($a['nb_sinistres'] ?? 0) * 1.2) + (($a['nb_agents'] ?? 0) * 1.8);
    $scoreB = (($b['ca_total'] ?? 0) / 1000) * 0.4 + ($b['nb_clients'] ?? 0) * 1.5 + (($b['satisfaction_moyenne'] ?? 0) * 5) - (($b['nb_sinistres'] ?? 0) * 1.2) + (($b['nb_agents'] ?? 0) * 1.8);
    return $scoreB <=> $scoreA;
});

// Previous period CA for trend
$prevCondition = '1=0';
if ($period === 'month') {
    $prevMonth = $month - 1;
    $prevYear = $year;
    if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
    $prevCondition = "MONTH(p.date_paiement) = $prevMonth AND YEAR(p.date_paiement) = $prevYear";
} elseif ($period === 'quarter') {
    $qIdx = $quarter ? (int)$quarter : 1;
    $prevQ = $qIdx - 1;
    $prevQYear = $year;
    if ($prevQ < 1) { $prevQ = 4; $prevQYear--; }
    $qRanges = [1=>[1,3],2=>[4,6],3=>[7,9],4=>[10,12]];
    $pq = $qRanges[$prevQ];
    $prevCondition = "MONTH(p.date_paiement) BETWEEN {$pq[0]} AND {$pq[1]} AND YEAR(p.date_paiement) = $prevQYear";
} elseif ($period === 'year') {
    $prevCondition = "YEAR(p.date_paiement) = " . ($year - 1);
}
$stmtPrev = $db->query("
    SELECT c.id_agence, COALESCE(SUM(p.montant), 0) as ca_prev
    FROM paiement p
    JOIN contrat ct ON p.id_offre = ct.id_contrat
    JOIN client c ON ct.id_user = c.id_user
    WHERE $prevCondition AND p.statut = 'valide'
    GROUP BY c.id_agence
");
$prevCA = [];
foreach ($stmtPrev->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $prevCA[(int)$row['id_agence']] = (float)$row['ca_prev'];
}

// 6-month CA for sparklines
$stmtSpark = $db->query("
    SELECT c.id_agence, MONTH(p.date_paiement) as mois, YEAR(p.date_paiement) as annee, COALESCE(SUM(p.montant), 0) as total
    FROM paiement p
    JOIN contrat ct ON p.id_offre = ct.id_contrat
    JOIN client c ON ct.id_user = c.id_user
    WHERE p.date_paiement >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND p.statut = 'valide'
    GROUP BY c.id_agence, YEAR(p.date_paiement), MONTH(p.date_paiement)
    ORDER BY annee ASC, mois ASC
");
$sparkData = [];
foreach ($stmtSpark->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $sparkData[(int)$row['id_agence']][] = (float)$row['total'];
}

function formatNumber($value): string {
    return number_format((float)$value, 0, ',', ' ') . ' DT';
}
function formatRate($value): string {
    return number_format((float)$value, 1, ',', ' ') . ' %';
}
function renderSparkline(array $values): string {
    if (empty($values)) return '<span class="text-muted">—</span>';
    $max = max($values) ?: 1;
    $w = 80; $h = 24;
    $points = [];
    $count = count($values);
    foreach ($values as $i => $v) {
        $x = ($count > 1) ? round($i / ($count - 1) * $w) : $w / 2;
        $y = $h - round(($v / $max) * ($h - 4)) - 2;
        $points[] = "$x,$y";
    }
    $polyline = implode(' ', $points);
    return '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 '.$w.' '.$h.'" style="vertical-align:middle;">
        <polyline fill="none" stroke="#667eea" stroke-width="2" points="'.$polyline.'"/>
        <polyline fill="rgba(102,126,234,0.1)" stroke="none" points="'.$polyline.' '.$w.','.$h.' 0,'.$h.'"/>
    </svg>';
}
function trendArrow(?float $current, ?float $previous): string {
    if ($previous === null || $previous <= 0) return '<span class="text-muted">→</span>';
    $diff = (($current ?? 0) - $previous) / $previous;
    if ($diff > 0.05) return '<span class="text-success" title="+'.round($diff*100).'%"><i class="bi bi-arrow-up"></i></span>';
    if ($diff < -0.05) return '<span class="text-danger" title="'.round($diff*100).'%"><i class="bi bi-arrow-down"></i></span>';
    return '<span class="text-muted">→</span>';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Classement des agences — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <style>
        .sortable { cursor:pointer; user-select:none; }
        .sortable:hover { color: var(--accent); }
        .sortable .sort-icon { margin-left:4px; font-size:10px; opacity:0.5; }
        .toolbar { display: flex;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
            margin: 0;
        }
        .toolbar > div {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .filter-select {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-family: var(--font-body);
            font-size: 13px;
            padding: 10px 12px;
            min-width: 140px;
        }
        .filter-select:focus {
            border-color: var(--accent);
            outline: none;
        }
        .filter-select option {
            background: var(--navy);
            color: var(--text-primary);
        }
        .btn-primary {
            min-width: 140px;
        }
        .rank-badge { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: rgba(59, 130, 246, .12); color: #1d4ed8; font-weight: 700; }
        .rank-badge.gold { background: #fef3c7; color: #b45309; }
        .rank-badge.silver { background: #e2e8f0; color: #475569; }
        .rank-badge.bronze { background: #fef2f2; color: #b91c1c; }
        .page-title { font-size: 1.25rem; font-weight: 700; }
        .toolbar-actions .btn { white-space: nowrap; }
    </style>
</head>
<body>

<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">

    <!-- ===== SIDEBAR ===== -->
    <?php require_once __DIR__.'/assets/includes/sidebar.php'; ?>

    <!-- ===== MAIN ===== -->
    <main class="main">

        <!-- TOPBAR -->
        <div class="topbar">
            <div>
                <div class="topbar-title">Classement agences</div>
                <div class="topbar-sub" id="topbarDate"></div>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="content">

            <div class="page-header-bar">
                <div>
                    <div class="page-title">Classement des agences</div>
                    <div class="page-breadcrumb">
                        <i class="bi bi-house"></i>
                        <a href="admin.php">Accueil</a>
                        <i class="bi bi-chevron-right" style="font-size:10px"></i>
                        <span>Classement agences</span>
                    </div>
                </div>
                <div class="toolbar-actions">
                    <a class="btn btn-outline btn-sm" href="?export=csv&period=<?php echo urlencode($period); ?>&year=<?php echo $year; ?>&month=<?php echo $month; ?>&quarter=<?php echo htmlspecialchars($quarter); ?>">
                        <i class="bi bi-download"></i> Exporter
                    </a>
                </div>
            </div>

            <div class="card mb-24">
                <div class="card-body">
                    <form method="GET" action="leaderboard_agences.php">
                        <div class="toolbar">
                            <div style="min-width: 190px;">
                                <label class="form-label mb-1">Période</label>
                                <select name="period" class="filter-select" onchange="this.form.submit()">
                                    <option value="month"<?php echo $period === 'month' ? ' selected' : ''; ?>>Mois</option>
                                    <option value="quarter"<?php echo $period === 'quarter' ? ' selected' : ''; ?>>Trimestre</option>
                                    <option value="year"<?php echo $period === 'year' ? ' selected' : ''; ?>>Année</option>
                                </select>
                            </div>
                            <div style="min-width: 140px;">
                                <label class="form-label mb-1">Année</label>
                                <input type="number" name="year" class="filter-select" value="<?php echo $year; ?>" min="2020" max="2100">
                            </div>
                            <div style="min-width: 140px;">
                                <label class="form-label mb-1">Mois</label>
                                <select name="month" class="filter-select">
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?php echo $m; ?>"<?php echo $m === $month ? ' selected' : ''; ?>><?php echo sprintf('%02d', $m); ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div style="min-width: 140px;">
                                <label class="form-label mb-1">Trimestre</label>
                                <select name="quarter" class="filter-select">
                                    <option value=""<?php echo $quarter === '' ? ' selected' : ''; ?>>1</option>
                                    <option value="1"<?php echo $quarter === '1' ? ' selected' : ''; ?>>1</option>
                                    <option value="2"<?php echo $quarter === '2' ? ' selected' : ''; ?>>2</option>
                                    <option value="3"<?php echo $quarter === '3' ? ' selected' : ''; ?>>3</option>
                                    <option value="4"<?php echo $quarter === '4' ? ' selected' : ''; ?>>4</option>
                                </select>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary">Actualiser</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-24">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table-protex" id="leaderboardTable">
                        <thead>
                            <tr>
                                <th data-sort="rank" class="sortable">#</th>
                                <th data-sort="name" class="sortable">Agence</th>
                                <th data-sort="ca" class="sortable">CA <span id="caTrend"></span></th>
                                <th>Tendance</th>
                                <th>CA 6 mois</th>
                                <th data-sort="clients" class="sortable">Clients</th>
                                <th data-sort="contrats" class="sortable">Contrats</th>
                                <th data-sort="agents" class="sortable">Agents</th>
                                <th data-sort="satisfaction" class="sortable">Satisfaction</th>
                                <th data-sort="sinistres" class="sortable">Sinistres</th>
                                <th data-sort="taux" class="sortable">Taux sinistres</th>
                                <th data-sort="score" class="sortable">Score</th>
                            </tr>
                        </thead>
                        <tbody id="leaderboardBody">
                            <?php foreach ($agencies as $index => $row):
                                $rank = $index + 1;
                                $tauxSinistres = ($row['nb_contrats'] > 0) ? round(($row['nb_sinistres'] / $row['nb_contrats']) * 100, 1) : 0;
                                $score = round((($row['ca_total'] ?? 0) / 1000) * 0.4 + ($row['nb_clients'] ?? 0) * 1.5 + (($row['satisfaction_moyenne'] ?? 0) * 5) - $tauxSinistres * 1.2 + (($row['nb_agents'] ?? 0) * 1.8), 1);
                                $badgeClass = '';
                                if ($rank === 1) $badgeClass = 'gold';
                                if ($rank === 2) $badgeClass = 'silver';
                                if ($rank === 3) $badgeClass = 'bronze';
                                $id = (int)$row['id_agence'];
                                $caPrev = $prevCA[$id] ?? null;
                                $sparkVals = $sparkData[$id] ?? [];
                            ?>
                            <tr>
                                <td><span class="rank-badge <?php echo $badgeClass; ?>"><?php echo $rank <= 3 ? ['🥇','🥈','🥉'][$rank-1] : $rank; ?></span></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['nom_agence']); ?></strong><br>
                                    <small class="text-secondary"><?php echo htmlspecialchars($row['adresse']); ?></small>
                                </td>
                                <td data-value="<?php echo (float)$row['ca_total']; ?>"><?php echo formatNumber($row['ca_total']); ?></td>
                                <td><?php echo trendArrow((float)$row['ca_total'], $caPrev); ?></td>
                                <td><?php echo renderSparkline($sparkVals); ?></td>
                                <td data-value="<?php echo (int)$row['nb_clients']; ?>"><?php echo (int)$row['nb_clients']; ?></td>
                                <td data-value="<?php echo (int)$row['nb_contrats']; ?>"><?php echo (int)$row['nb_contrats']; ?></td>
                                <td data-value="<?php echo (int)$row['nb_agents']; ?>"><?php echo (int)$row['nb_agents']; ?></td>
                                <td data-value="<?php echo (float)$row['satisfaction_moyenne']; ?>"><?php echo number_format((float)$row['satisfaction_moyenne'], 1, ',', ' ') ?: '–'; ?></td>
                                <td data-value="<?php echo (int)$row['nb_sinistres_en_cours']; ?>"><?php echo (int)$row['nb_sinistres_en_cours']; ?></td>
                                <td data-value="<?php echo $tauxSinistres; ?>"><?php echo formatRate($tauxSinistres); ?></td>
                                <td data-value="<?php echo $score; ?>"><?php echo formatRate($score); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
// Sortable table
document.addEventListener('DOMContentLoaded', function() {
    const topbarDate = document.getElementById('topbarDate');
    if (topbarDate) {
        topbarDate.textContent = new Date().toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
    }

    // Column sorting
    const table = document.getElementById('leaderboardTable');
    if (!table) return;
    const headers = table.querySelectorAll('th.sortable');
    headers.forEach(function(header) {
        header.addEventListener('click', function() {
            const key = this.dataset.sort;
            const tbody = document.getElementById('leaderboardBody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const isAsc = this.classList.contains('asc');

            headers.forEach(function(h) { h.classList.remove('asc', 'desc'); });
            this.classList.toggle('asc', !isAsc);
            this.classList.toggle('desc', isAsc);

            rows.sort(function(a, b) {
                var aVal, bVal;
                if (key === 'rank') {
                    aVal = parseInt(a.cells[0].textContent.replace(/[^\d]/g,'')) || 0;
                    bVal = parseInt(b.cells[0].textContent.replace(/[^\d]/g,'')) || 0;
                } else if (key === 'name') {
                    aVal = a.cells[1].textContent.trim().toLowerCase();
                    bVal = b.cells[1].textContent.trim().toLowerCase();
                    return isAsc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
                } else {
                    aVal = parseFloat(a.querySelector('td[data-value]')?.dataset.value) || 0;
                    bVal = parseFloat(b.querySelector('td[data-value]')?.dataset.value) || 0;
                }
                return isAsc ? aVal - bVal : bVal - aVal;
            });

            rows.forEach(function(row) { tbody.appendChild(row); });
            // Update rank badges
            rows.forEach(function(row, idx) {
                var rankCell = row.cells[0];
                rankCell.innerHTML = '<span class="rank-badge ' + (idx < 3 ? ['gold','silver','bronze'][idx] : '') + '">' + (idx < 3 ? ['🥇','🥈','🥉'][idx] : (idx+1)) + '</span>';
            });
        });
    });
});
</script>

</body>
</html>
