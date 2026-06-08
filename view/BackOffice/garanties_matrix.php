<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();

require_once dirname(__DIR__, 2) . '/config.php';
$db = config::getConnexion();

// Fetch categories for filter
$stmtCat = $db->query("SELECT id_categorie, nom_categorie FROM categorie ORDER BY nom_categorie ASC");
$categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

$selected_category = $_GET['categorie'] ?? '';

// Fetch formulas
$formulesQuery = "SELECT id_formule, nom_formule, niveau_formule FROM formule ORDER BY niveau_formule, nom_formule";
$stmtFormules = $db->query($formulesQuery);
$formules = $stmtFormules->fetchAll(PDO::FETCH_ASSOC);

// Fetch guarantees
$garantiesQuery = "SELECT id_garantie, nom_garantie, plafond_couvert_garantie, id_categorie FROM garantie";
if ($selected_category) {
    $garantiesQuery .= " WHERE id_categorie = " . (int)$selected_category;
}
$garantiesQuery .= " ORDER BY nom_garantie ASC";
$stmtGaranties = $db->query($garantiesQuery);
$garanties = $stmtGaranties->fetchAll(PDO::FETCH_ASSOC);

// Fetch links
$stmtLinks = $db->query("SELECT id_formule, id_garantie, plafond_formule, franchise_formule FROM formule_garantie");
$linksData = $stmtLinks->fetchAll(PDO::FETCH_ASSOC);

$links = [];
foreach ($linksData as $link) {
    $links[$link['id_garantie']][$link['id_formule']] = $link;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Matrice Garanties — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    
    <style>
        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .matrix-table th, .matrix-table td {
            border: 1px solid var(--glass-border);
            padding: 12px;
            text-align: center;
        }
        .matrix-table th {
            background: rgba(255, 255, 255, 0.05);
            font-weight: 600;
        }
        .matrix-table td.garantie-name {
            text-align: left;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.02);
            position: sticky;
            left: 0;
            z-index: 10;
        }
        .cell-content {
            display: flex;
            flex-direction: column;
            gap: 4px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .cell-content:hover {
            background: rgba(255, 255, 255, 0.08);
        }
        .status-icon.linked {
            color: #10b981;
            font-size: 18px;
        }
        .status-icon.unlinked {
            color: #ef4444;
            font-size: 18px;
        }
        .val-badge {
            font-size: 11px;
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            color: rgba(255, 255, 255, 0.8);
        }
        
        /* Modal */
        .matrix-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .matrix-modal {
            background: var(--bg-color, #1a2234);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            width: 400px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.8);
        }
        .form-group input[type="number"] {
            width: 100%;
            padding: 10px;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--glass-border);
            border-radius: 6px;
            color: #fff;
        }
        .form-group input[type="checkbox"] {
            margin-right: 8px;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
        }
        
        @media print {
            body * {
                visibility: hidden;
            }
            .content, .content * {
                visibility: visible;
            }
            .content {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                color: #000;
            }
            .matrix-table th, .matrix-table td {
                border: 1px solid #000;
                color: #000;
            }
            .val-badge {
                color: #000;
                background: #eee;
            }
            .topbar, .sidebar, .page-header-bar a {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">
    <?php require_once __DIR__.'/assets/includes/sidebar.php'; ?>
    <main class="main">
        <div class="topbar">
            <div>
                <div class="topbar-title">Matrice Garanties × Formules</div>
                <div class="topbar-sub">Analyse rapide des garanties par formule</div>
            </div>
        </div>

        <div class="content">
            <div class="page-header-bar">
                <div>
                    <div class="page-title">Matrice Garanties × Formules</div>
                </div>
                <div>
                    <button class="btn btn-outline btn-sm" onclick="window.print()">
                        <i class="bi bi-printer"></i> Imprimer
                    </button>
                </div>
            </div>

            <div class="card mb-24">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="categorie" class="form-label">Filtrer par catégorie</label>
                            <select name="categorie" id="categorie" class="form-select" onchange="this.form.submit()">
                            <option value="">Toutes les catégories</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id_categorie'] ?>" <?= $selected_category == $cat['id_categorie'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nom_categorie']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <div class="text-secondary" style="font-size: 0.95rem;">Choisissez une catégorie pour limiter la matrice aux garanties actives de cette famille.</div>
                    </div>
                </form>
            </div>
        </div>

            <div class="card mb-24" style="overflow-x: auto; padding: 22px;">
                <table class="table-protex matrix-table">
                    <thead>
                        <tr>
                            <th style="min-width: 220px; text-align: left;">Garantie \ Formule</th>
                            <?php foreach($formules as $f): ?>
                                <th data-id-formule="<?= $f['id_formule'] ?>">
                                    <div style="font-size: 11px; opacity: 0.72; margin-bottom: 8px;">Niveau <?= htmlspecialchars($f['niveau_formule']) ?></div>
                                    <?= htmlspecialchars($f['nom_formule']) ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($garanties as $g): ?>
                            <tr data-id-garantie="<?= $g['id_garantie'] ?>">
                                <td class="garantie-name"><?= htmlspecialchars($g['nom_garantie']) ?></td>
                                <?php foreach($formules as $f):
                                    $is_linked = isset($links[$g['id_garantie']][$f['id_formule']]);
                                    $link_data = $is_linked ? $links[$g['id_garantie']][$f['id_formule']] : null;
                                    $plafond = $link_data['plafond_formule'] ?? null;
                                    $franchise = $link_data['franchise_formule'] ?? null;
                                ?>
                                    <td>
                                        <div class="cell-content">
                                            <?php if($is_linked): ?>
                                                <i class="bi bi-check-circle-fill status-icon linked"></i>
                                                <?php if($plafond !== null): ?><div class="val-badge">Pla: <?= number_format($plafond, 0, ',', ' ') ?></div><?php endif; ?>
                                                <?php if($franchise !== null): ?><div class="val-badge">Fra: <?= number_format($franchise, 0, ',', ' ') ?></div><?php endif; ?>
                                            <?php else: ?>
                                                <i class="bi bi-x-circle status-icon unlinked"></i>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div class="matrix-modal-overlay" id="editModal">
    <div class="matrix-modal">
        <h3 style="margin:0 0 20px; font-size:18px;" id="modalTitle">Modifier le lien</h3>
        <input type="hidden" id="editIdFormule">
        <input type="hidden" id="editIdGarantie">
        <div class="form-group">
            <label><input type="checkbox" id="editIsLinked"> Lier la garantie à cette formule</label>
        </div>
        <div class="form-group">
            <label>Plafond (TND)</label>
            <input type="number" step="0.01" id="editPlafond" placeholder="Plafond formule">
        </div>
        <div class="form-group">
            <label>Franchise (TND)</label>
            <input type="number" step="0.01" id="editFranchise" placeholder="Franchise formule">
        </div>
        <div class="modal-actions">
            <button class="btn btn-outline btn-sm" onclick="closeEditModal()">Annuler</button>
            <button class="btn btn-accent btn-sm" onclick="saveMatrixLink()"><i class="bi bi-save"></i> Enregistrer</button>
        </div>
    </div>
</div>

<script>
(function() {
    const BASE = window.BASE_URL || '/assurance';

    function byId(id) { return document.getElementById(id); }

    window.openEditModal = function(idFormule, idGarantie, garantieName, formuleName, isLinked, plafond, franchise) {
        byId('editIdFormule').value = idFormule || '';
        byId('editIdGarantie').value = idGarantie || '';
        byId('modalTitle').textContent = (garantieName || '?') + ' × ' + (formuleName || '?');
        byId('editIsLinked').checked = !!isLinked;
        byId('editPlafond').value = plafond != null ? plafond : '';
        byId('editFranchise').value = franchise != null ? franchise : '';
        byId('editModal').style.display = 'flex';
    };

    window.closeEditModal = function() {
        byId('editModal').style.display = 'none';
    };

    window.saveMatrixLink = function() {
        const idFormule = byId('editIdFormule').value;
        const idGarantie = byId('editIdGarantie').value;
        if (!idFormule || !idGarantie) {
            alert('Erreur : formule ou garantie non identifiée.');
            return;
        }
        const fd = new FormData();
        fd.append('id_formule', idFormule);
        fd.append('id_garantie', idGarantie);
        fd.append('is_linked', byId('editIsLinked').checked ? '1' : '0');
        fd.append('plafond_formule', byId('editPlafond').value || '');
        fd.append('franchise_formule', byId('editFranchise').value || '');

        fetch(BASE + '/api.php?action=update_garantie_formule', { method: 'POST', body: fd })
            .then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function(data) {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur : ' + (data.message || 'Réponse inconnue'));
                }
            })
            .catch(function(e) {
                alert('Erreur réseau : ' + e.message);
            });
    };

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.matrix-table td:not(.garantie-name)').forEach(function(td) {
            td.style.cursor = 'pointer';
            td.addEventListener('click', function() {
                var tr = this.closest('tr');
                var ths = document.querySelectorAll('.matrix-table thead th:not(:first-child)');
                var cellIndex = Array.from(tr.children).indexOf(this) - 1;
                if (cellIndex < 0) return;
                var idFormule = ths[cellIndex] && ths[cellIndex].dataset ? ths[cellIndex].dataset.idFormule : null;
                var idGarantie = tr.dataset ? tr.dataset.idGarantie : null;
                if (!idFormule || !idGarantie) return;
                var garantieName = (tr.querySelector('.garantie-name') || {}).textContent || '';
                var formuleName = (ths[cellIndex] || {}).textContent || '';
                var isLinked = this.querySelector('.status-icon.linked') !== null;
                var badges = this.querySelectorAll('.val-badge');
                var plafond = null, franchise = null;
                if (badges.length > 0) {
                    var txt = badges[0].textContent.replace(/[^0-9,.\-]/g, '').replace(',', '.');
                    var v = parseFloat(txt);
                    if (!isNaN(v)) plafond = v;
                }
                if (badges.length > 1) {
                    var txt2 = badges[1].textContent.replace(/[^0-9,.\-]/g, '').replace(',', '.');
                    var v2 = parseFloat(txt2);
                    if (!isNaN(v2)) franchise = v2;
                }
                window.openEditModal(idFormule, idGarantie, garantieName.trim(), formuleName.trim(), isLinked, plafond, franchise);
            });
        });
    });
})();
</script>

</body>
</html>
