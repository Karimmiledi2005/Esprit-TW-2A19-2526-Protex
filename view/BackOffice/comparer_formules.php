<?php
/**
 * C4: BackOffice - Formula comparison
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();

require_once __DIR__ . '/../../controller/ContratController.php';

$db = config::getConnexion();
$categories = $db->query("SELECT * FROM categorie ORDER BY nom_categorie")->fetchAll(PDO::FETCH_ASSOC);

$formules = [];
$selectedIds = $_GET['ids'] ?? [];
if (is_array($selectedIds)) {
    $selectedIds = array_filter(array_map('intval', $selectedIds));
} else if (is_string($selectedIds) && trim($selectedIds) !== '') {
    $selectedIds = array_filter(array_map('intval', explode(',', $selectedIds)));
}

if (!empty($selectedIds)) {
    $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
    $stmt = $db->prepare("
        SELECT f.*, c.nom_categorie 
        FROM formule f 
        LEFT JOIN categorie c ON f.id_categorie = c.id_categorie 
        WHERE f.id_formule IN ($placeholders)
    ");
    $stmt->execute($selectedIds);
    $formules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch garanties for each formule
    foreach ($formules as &$f) {
        $stmtG = $db->prepare("
            SELECT g.nom_garantie, fg.niveau_couvert_garantie 
            FROM formule_garantie fg
            JOIN garantie g ON fg.id_garantie = g.id_garantie
            WHERE fg.id_formule = ?
        ");
        $stmtG->execute([$f['id_formule']]);
        $f['garanties'] = [];
        while ($row = $stmtG->fetch(PDO::FETCH_ASSOC)) {
            $f['garanties'][$row['nom_garantie']] = $row['niveau_couvert_garantie'];
        }
    }
    unset($f);
}

// Get all unique garanties from selected formules
$allGaranties = [];
foreach ($formules as $f) {
    foreach ($f['garanties'] as $nom => $niveau) {
        if (!in_array($nom, $allGaranties)) {
            $allGaranties[] = $nom;
        }
    }
}
sort($allGaranties);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparer les formules — Protex Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/admin-users.css">
    <style>
        .compare-container {
            display: flex;
            gap: 24px;
            margin-top: 24px;
        }
        .selector-panel {
            width: 320px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 20px;
            height: fit-content;
        }
        .compare-panel {
            flex: 1;
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            color: #1a202c;
            overflow-x: auto;
        }
        .formule-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-radius: 8px;
            background: rgba(0,0,0,0.2);
            margin-bottom: 8px;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .formule-item:hover {
            border-color: rgba(255,255,255,0.2);
        }
        .formule-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #00c6ff;
        }
        .formule-info { flex: 1; }
        .formule-name { font-size: 14px; font-weight: 700; color: #fff; }
        .formule-price { font-size: 12px; color: #00c6ff; }
        
        .compare-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .compare-table th {
            text-align: center;
            padding: 16px;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            color: #1a202c;
            font-size: 16px;
            font-weight: 800;
        }
        .compare-table th:first-child {
            text-align: left;
            width: 25%;
        }
        .compare-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            text-align: center;
            vertical-align: middle;
        }
        .compare-table td:first-child {
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            background: #f8fafc;
        }
        .feature-yes { color: #059669; font-size: 18px; }
        .feature-no { color: #cbd5e1; font-size: 18px; }
        .feature-text { font-weight: 700; color: #0A1931; }
        
        .btn-compare {
            width: 100%;
            margin-top: 16px;
            padding: 12px;
            background: linear-gradient(135deg, #00c6ff, #0891b2);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 700;
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
    <?php require_once __DIR__.'/assets/includes/sidebar.php'; ?>
    <main class="main">
        <div class="topbar">
            <div>
                <div class="topbar-title">Comparateur de Formules</div>
            </div>
        </div>

        <div class="content">
            <div class="page-header-bar">
                <div class="page-title">Comparer les formules</div>
            </div>

            <div class="compare-container">
                <div class="selector-panel">
                    <h3 style="margin-bottom:16px;font-size:16px;">Sélectionnez (2 à 3 max)</h3>
                    <form method="GET" action="comparer_formules.php">
                        <?php foreach ($categories as $cat): ?>
                            <div style="font-size:12px;color:rgba(255,255,255,0.5);text-transform:uppercase;margin:16px 0 8px;font-weight:700;">
                                <?= htmlspecialchars($cat['nom_categorie']) ?>
                            </div>
                            <?php 
                            $catFormules = $db->prepare("SELECT id_formule, nom_formule, prix_formule FROM formule WHERE id_categorie = ?");
                            $catFormules->execute([$cat['id_categorie']]);
                            while ($cf = $catFormules->fetch(PDO::FETCH_ASSOC)):
                                $checked = in_array($cf['id_formule'], $selectedIds) ? 'checked' : '';
                            ?>
                                <label class="formule-item">
                                    <input type="checkbox" name="ids[]" value="<?= $cf['id_formule'] ?>" <?= $checked ?> onchange="checkLimit(this)">
                                    <div class="formule-info">
                                        <div class="formule-name"><?= htmlspecialchars($cf['nom_formule']) ?></div>
                                        <div class="formule-price"><?= number_format((float)$cf['prix_formule'], 2) ?> DT</div>
                                    </div>
                                </label>
                            <?php endwhile; ?>
                        <?php endforeach; ?>
                        <button type="submit" class="btn-compare"><i class="bi bi-arrow-left-right"></i> Comparer</button>
                    </form>
                </div>

                <?php if (!empty($formules)): ?>
                <div class="compare-panel">
                    <table class="compare-table">
                        <thead>
                            <tr>
                                <th>Critères</th>
                                <?php foreach ($formules as $f): ?>
                                    <th>
                                        <div style="font-size:12px;color:#718096;text-transform:uppercase;font-weight:700;margin-bottom:4px;"><?= htmlspecialchars($f['nom_categorie']) ?></div>
                                        <?= htmlspecialchars($f['nom_formule']) ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Prime</td>
                                <?php foreach ($formules as $f): ?>
                                    <td class="feature-text"><?= number_format((float)$f['prix_formule'], 2) ?> DT</td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td>Franchise</td>
                                <?php foreach ($formules as $f): ?>
                                    <td class="feature-text"><?= number_format((float)$f['franchise_formule'], 2) ?> DT</td>
                                <?php endforeach; ?>
                            </tr>
                            
                            <!-- Garanties -->
                            <tr>
                                <td colspan="<?= count($formules) + 1 ?>" style="background:#f1f5f9;text-align:center;font-weight:800;color:#0f172a;letter-spacing:1px;text-transform:uppercase;">
                                    Garanties Incluses
                                </td>
                            </tr>
                            <?php foreach ($allGaranties as $g): ?>
                                <tr>
                                    <td><?= htmlspecialchars($g) ?></td>
                                    <?php foreach ($formules as $f): 
                                        $niveau = $f['garanties'][$g] ?? null;
                                    ?>
                                        <td>
                                            <?php if ($niveau === 'basique' || $niveau === 'option'): ?>
                                                <i class="bi bi-check-circle-fill feature-yes"></i>
                                                <?php if($niveau==='option') echo '<br><span style="font-size:10px;color:#f59e0b;">En option</span>'; ?>
                                            <?php else: ?>
                                                <i class="bi bi-dash-circle feature-no"></i>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="compare-panel" style="display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.5);font-size:18px;">
                    Sélectionnez des formules à gauche pour les comparer.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
function checkLimit(checkbox) {
    const checkedBoxes = document.querySelectorAll('input[type="checkbox"]:checked');
    if (checkedBoxes.length > 3) {
        checkbox.checked = false;
        alert("Vous ne pouvez comparer que 3 formules au maximum.");
    }
}
</script>
<script src="assets/js/main.js"></script>
</body>
</html>
