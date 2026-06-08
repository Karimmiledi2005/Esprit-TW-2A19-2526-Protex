<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
// Restreindre l'accès au SuperAdmin uniquement
SessionGuard::requireRole('superadmin');

if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}

$db = config::getConnexion();

// Récupérer les types d'actions uniques pour le filtre
$stmtActions = $db->query("SELECT DISTINCT action FROM audit_log ORDER BY action ASC");
$actionTypes = $stmtActions->fetchAll(PDO::FETCH_COLUMN) ?: [];

// Gérer l'export CSV côté PHP pour exporter l'intégralité sans limite de pagination
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Nom du fichier avec date
    $filename = "audit_log_export_" . date('Y-m-d_H-i') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // BOM UTF-8
    fputs($output, "\xEF\xBB\xBF");

    fputcsv($output, ['ID', 'Date', 'Utilisateur', 'Rôle', 'Action', 'Cible', 'Détails', 'IP'], ';');

    // Récupérer l'historique complet avec jointure
    $stmt = $db->query("
        SELECT a.id, a.created_at, a.action, a.cible, a.details, a.ip, u.nom, u.prenom, u.email, u.role
        FROM audit_log a
        LEFT JOIN user u ON a.id_user = u.id_user
        ORDER BY a.created_at DESC
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $userStr = $row['nom'] ? "{$row['prenom']} {$row['nom']} ({$row['email']})" : 'Système / CLI';
        fputcsv($output, [
            $row['id'],
            $row['created_at'],
            $userStr,
            $row['role'] ?? 'N/A',
            $row['action'],
            $row['cible'],
            $row['details'],
            $row['ip']
        ], ';');
    }

    fclose($output);
    exit;
}

// Récupérer les 100 dernières lignes par défaut pour le chargement initial rapide (le reste est géré via AJAX)
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $actionType = $_GET['action_type'] ?? '';
    $search = trim($_GET['search'] ?? '');
    
    $conds = [];
    $params = [];
    
    if ($dateFrom) {
        $conds[] = "a.created_at >= :date_from";
        $params[':date_from'] = $dateFrom . ' 00:00:00';
    }
    if ($dateTo) {
        $conds[] = "a.created_at <= :date_to";
        $params[':date_to'] = $dateTo . ' 23:59:59';
    }
    if ($actionType) {
        $conds[] = "a.action = :action_type";
        $params[':action_type'] = $actionType;
    }
    if ($search) {
        $conds[] = "(a.details LIKE :search OR a.cible LIKE :search2 OR u.nom LIKE :search3 OR u.prenom LIKE :search4 OR u.email LIKE :search5 OR a.ip LIKE :search6)";
        $sVal = "%$search%";
        $params[':search'] = $sVal;
        $params[':search2'] = $sVal;
        $params[':search3'] = $sVal;
        $params[':search4'] = $sVal;
        $params[':search5'] = $sVal;
        $params[':search6'] = $sVal;
    }
    
    $whereSql = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
    
    $sql = "
        SELECT a.id, a.created_at, a.action, a.cible, a.details, a.ip, u.nom, u.prenom, u.email, u.role
        FROM audit_log a
        LEFT JOIN user u ON a.id_user = u.id_user
        $whereSql
        ORDER BY a.created_at DESC
        LIMIT 500
    ";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Journal d'Audit — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/admin-users.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <style>
        .audit-details-text {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
            cursor: pointer;
        }
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
                <div class="topbar-title">Journal d'Audit</div>
                <div class="topbar-sub" id="topbarDate"></div>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="content">

            <!-- Page header -->
            <div class="page-header-bar">
                <div>
                    <div class="page-title">Logs Système (SuperAdmin uniquement)</div>
                    <div class="page-breadcrumb">
                        <i class="bi bi-house"></i>
                        <a href="admin.php">Accueil</a>
                        <i class="bi bi-chevron-right" style="font-size:10px"></i>
                        <span>Journal d'Audit</span>
                    </div>
                </div>
                <a href="?export=csv" class="btn btn-primary">
                    <i class="bi bi-download"></i> Exporter tout en CSV
                </a>
            </div>

            <!-- TABLE CARD -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="bi bi-journal-text"></i> Enregistrements de sécurité
                    </div>
                </div>

                <!-- Toolbar Filters -->
                <div style="padding: 16px 24px; border-bottom: 1px solid var(--glass-border);">
                    <div class="toolbar" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
                        <div class="search-box" style="flex: 1; min-width: 200px;">
                            <i class="bi bi-search"></i>
                            <input type="text" id="searchInput" placeholder="Rechercher par détails, IP, user..." autocomplete="off">
                        </div>
                        <select class="filter-select" id="filterAction" autocomplete="off" style="min-width: 150px;">
                            <option value="">Tous les types d'actions</option>
                            <?php foreach ($actionTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <span style="font-size: 11px; color: var(--text-secondary);">Du :</span>
                            <input type="date" class="filter-select" id="filterDateFrom" autocomplete="off">
                        </div>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <span style="font-size: 11px; color: var(--text-secondary);">Au :</span>
                            <input type="date" class="filter-select" id="filterDateTo" autocomplete="off">
                        </div>
                        <button class="btn btn-outline btn-sm" onclick="resetFilters()">
                            <i class="bi bi-x-circle"></i> Réinitialiser
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date & Heure</th>
                                <th>Utilisateur</th>
                                <th>Rôle</th>
                                <th>Action</th>
                                <th>Cible</th>
                                <th>Détails</th>
                                <th>Adresse IP</th>
                            </tr>
                        </thead>
                        <tbody id="auditLogBody">
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px;">Chargement des données...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Modal Détails complets -->
<div class="modal-overlay" id="modalDetails">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="bi bi-info-circle"></i> Détails de l'enregistrement</div>
            <button class="modal-close" onclick="closeModal()"><i class="bi bi-x"></i></button>
        </div>
        <div class="modal-body" id="modalDetailsBody" style="padding: 20px 24px; color: var(--text-primary);">
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal()">Fermer</button>
        </div>
    </div>
</div>

<script>
// Date Topbar
const now = new Date();
document.getElementById('topbarDate').textContent =
    now.toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long', year:'numeric' });

let auditData = [];
let searchTimeout = null;

function loadAuditLogs() {
    const search = document.getElementById('searchInput').value.trim();
    const action = document.getElementById('filterAction').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;

    const params = new URLSearchParams({ ajax: '1' });
    if (search) params.set('search', search);
    if (action) params.set('action_type', action);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);

    const tbody = document.getElementById('auditLogBody');

    fetch(`audit_log.php?${params.toString()}`)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                auditData = res.data;
                renderAuditLogs();
            } else {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--danger); padding: 40px;">Erreur : ${res.message}</td></tr>`;
            }
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--danger); padding: 40px;">Erreur lors du chargement des logs d'audit.</td></tr>`;
        });
}

function renderAuditLogs() {
    const tbody = document.getElementById('auditLogBody');
    if (auditData.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--text-secondary);">Aucun log trouvé pour ces filtres.</td></tr>`;
        return;
    }

    tbody.innerHTML = auditData.map(row => {
        const dateStr = new Date(row.created_at).toLocaleString('fr-FR');
        const userStr = row.nom ? `${row.prenom} ${row.nom}<br><small style="color: var(--text-secondary);">${row.email}</small>` : '<i style="color: var(--text-secondary);">Système / CLI</i>';
        const roleBadge = row.role ? `<span class="badge badge-${row.role.toLowerCase()}">${row.role}</span>` : '—';
        const detailsEscaped = escapeHtml(row.details || '');
        const detailsHtml = row.details && row.details.length > 35 
            ? `<span class="audit-details-text" onclick="showFullDetails(${row.id})" title="Cliquez pour voir tout">${detailsEscaped}</span>`
            : detailsEscaped;
        
        return `
            <tr>
                <td style="color: var(--text-secondary); font-size: 13px;">${dateStr}</td>
                <td>${userStr}</td>
                <td>${roleBadge}</td>
                <td><strong style="color: var(--accent);">${escapeHtml(row.action)}</strong></td>
                <td style="color: var(--text-secondary); font-size: 13px;">${escapeHtml(row.cible)}</td>
                <td style="font-size: 13px;">${detailsHtml}</td>
                <td style="color: var(--text-secondary); font-family: monospace; font-size: 13px;">${escapeHtml(row.ip)}</td>
            </tr>
        `;
    }).join('');
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

function showFullDetails(id) {
    const record = auditData.find(r => r.id === id);
    if (!record) return;
    
    const body = document.getElementById('modalDetailsBody');
    const dateStr = new Date(record.created_at).toLocaleString('fr-FR');
    const userStr = record.nom ? `${record.prenom} ${record.nom} (${record.email}) [Rôle: ${record.role}]` : 'Système';
    
    body.innerHTML = `
        <div style="margin-bottom: 12px;"><strong>Date :</strong> ${dateStr}</div>
        <div style="margin-bottom: 12px;"><strong>Acteur :</strong> ${escapeHtml(userStr)}</div>
        <div style="margin-bottom: 12px;"><strong>Action :</strong> <span style="color: var(--accent);">${escapeHtml(record.action)}</span></div>
        <div style="margin-bottom: 12px;"><strong>Cible :</strong> ${escapeHtml(record.cible)}</div>
        <div style="margin-bottom: 12px;"><strong>Adresse IP :</strong> ${escapeHtml(record.ip)}</div>
        <div style="border-top: 1px solid var(--glass-border); padding-top: 12px; margin-top: 12px;">
            <strong>Détails complets :</strong>
            <pre style="background: rgba(0,0,0,0.2); padding: 12px; border-radius: 8px; margin-top: 6px; overflow-x: auto; font-family: monospace; font-size: 13px; color: #fff; white-space: pre-wrap; word-break: break-all;">${escapeHtml(record.details)}</pre>
        </div>
    `;
    
    document.getElementById('modalDetails').classList.add('active', 'open');
}

function closeModal() {
    document.getElementById('modalDetails').classList.remove('active', 'open');
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterAction').value = '';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    loadAuditLogs();
}

document.getElementById('searchInput').addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(loadAuditLogs, 350);
});
document.getElementById('filterAction').addEventListener('change', loadAuditLogs);
document.getElementById('filterDateFrom').addEventListener('change', loadAuditLogs);
document.getElementById('filterDateTo').addEventListener('change', loadAuditLogs);

// Init
loadAuditLogs();
</script>

<script src="assets/js/main.js"></script>
<script src="assets/js/admin.js"></script>

</body>
</html>
