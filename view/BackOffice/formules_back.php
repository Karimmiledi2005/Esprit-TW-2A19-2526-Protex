<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();

require_once __DIR__ . '/../../controller/FormuleController.php';

$formuleC = new FormuleController();
$stmt = $formuleC->listFormules();
$list = $stmt instanceof PDOStatement ? $stmt->fetchAll(PDO::FETCH_ASSOC) : $stmt;

$totalFormules = $formuleC->countFormules();
$totalGarantiesAssociees = $formuleC->countGarantiesAssociees();
$totalFormulesUtilisees = $formuleC->countFormulesUtiliseesParContrats();

$categories = [];
foreach ($list as $f) {
    $cat = $f['nom_categorie'] ?? 'é';
    if ($cat !== 'é' && $cat !== '') {
        $categories[$cat] = $cat;
    }
}
ksort($categories);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Formules é Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/variables.css">
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/layout.css">
  <link rel="stylesheet" href="assets/css/contrats.css">

    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/admin-users.css">
    <link rel="stylesheet" href="assets/css/validation.css">
    <link rel="stylesheet" href="assets/css/animations.css">
  <script src="assets/js/validation.js"></script>
    <style>
        .toolbar {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            padding: 18px 24px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .toolbar .search-box {
            flex: 1;
            min-width: 260px;
            position: relative;
        }
        .toolbar .search-box i {
            position: absolute;
            top: 50%;
            left: 14px;
            transform: translateY(-50%);
            color: rgba(255,255,255,.65);
        }
        .toolbar input,
        .toolbar select {
            width: 100%;
            height: 46px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.13);
            background: rgba(255,255,255,.07);
            color: #fff;
            padding: 0 14px;
            outline: none;
        }
        .toolbar input { padding-left: 42px; }
        .toolbar select { min-width: 210px; }
        .toolbar option { color: #0A1931; }
        .toolbar-actions {
            margin-left: auto;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .export-btn, .reset-btn {
            height: 42px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.15);
            background: rgba(255,255,255,.08);
            color: #fff;
            padding: 0 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .export-btn:hover, .reset-btn:hover { border-color: #00b4d8; color: #00d4ff; }
        .badge-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(0,180,216,.15);
            border: 1px solid rgba(0,180,216,.35);
            color: #91eaff;
            font-weight: 700;
        }
        .empty-row {
            display: none;
            text-align: center;
            padding: 24px;
            color: rgba(255,255,255,.75);
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
                <div class="topbar-title">Gestion des formules</div>
                <div class="topbar-sub" id="topbarDate"></div>
            </div>
        </div>

        <div class="content">
            <div class="page-header-bar">
                <div>
                    <div class="page-title">Formules</div>
                    <div class="page-breadcrumb">
                        <i class="bi bi-house"></i>
                        <a href="#">Accueil</a>
                        <i class="bi bi-chevron-right" style="font-size:10px;"></i>
                        <span>Formules</span>
                    </div>
                </div>

                <a href="addFormule.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Ajouter une formule
                </a>
            </div>

            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon"><i class="bi bi-list-check"></i></div>
                    <div class="stat-value"><?= $totalFormules ?></div>
                    <div class="stat-label">Total formules</div>
                </div>

                <div class="stat-card gold">
                    <div class="stat-icon"><i class="bi bi-shield-check"></i></div>
                    <div class="stat-value"><?= $totalGarantiesAssociees ?></div>
                    <div class="stat-label">Garanties associées</div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon"><i class="bi bi-file-earmark-check"></i></div>
                    <div class="stat-value"><?= $totalFormulesUtilisees ?></div>
                    <div class="stat-label">Formules utilisées</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-table"></i> Liste des formules</div>
                    <div class="toolbar-actions">
                        <button type="button" class="export-btn" onclick="exportPDF()">
                            <i class="bi bi-file-earmark-pdf"></i> Export PDF
                        </button>
                    </div>
                </div>

                <div class="toolbar">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchInput" placeholder="Rechercher par nom, description, catégorie..." oninput="applyFilters()">
                    </div>

                    <select id="categoryFilter" onchange="applyFilters()">
                        <option value="all">Toutes les catégories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars(strtolower($cat)) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select id="sortSelect" onchange="applyFilters()">
                        <option value="default">Tri par défaut</option>
                        <option value="price-asc">Prix croissant</option>
                        <option value="price-desc">Prix décroissant</option>
                        <option value="franchise-asc">Franchise croissante</option>
                        <option value="franchise-desc">Franchise décroissante</option>
                        <option value="garanties-desc">Garanties liées ?</option>
                        <option value="garanties-asc">Garanties liées ?</option>
                    </select>

                    <button type="button" class="reset-btn" onclick="resetFilters()">
                        <i class="bi bi-x-circle"></i> Réinitialiser
                    </button>
                </div>

                <div class="table-wrap">
                    <table id="formulesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Prix</th>
                                <th>Franchise</th>
                                <th>Niveau</th>
                                <th>Catégorie</th>
                                <th>Garanties liées</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="formulesBody">
                            <?php foreach ($list as $f):
                                $id = (int)$f['id_formule'];
                                $nom = $f['nom_formule'] ?? '';
                                $description = $f['description_formule'] ?? 'é';
                                $prix = (float)($f['prix_formule'] ?? 0);
                                $franchise = (float)($f['franchise_formule'] ?? 0);
                                $niveau = $f['niveau_formule'] ?? 'é';
                                $categorie = $f['nom_categorie'] ?? 'é';
                                $nbGaranties = (int)($f['nb_garanties'] ?? 0);
                            ?>
                                <tr class="formule-row"
                                    data-search="<?= htmlspecialchars(strtolower($nom . ' ' . $description . ' ' . $niveau . ' ' . $categorie)) ?>"
                                    data-category="<?= htmlspecialchars(strtolower($categorie)) ?>"
                                    data-price="<?= $prix ?>"
                                    data-franchise="<?= $franchise ?>"
                                    data-garanties="<?= $nbGaranties ?>">
                                    <td>#<?= $id ?></td>
                                    <td><strong><?= htmlspecialchars($nom) ?></strong></td>
                                    <td><?= htmlspecialchars($description) ?></td>
                                    <td><?= number_format($prix, 2) ?> DT</td>
                                    <td><?= number_format($franchise, 2) ?> DT</td>
                                    <td><?= htmlspecialchars($niveau) ?></td>
                                    <td><?= htmlspecialchars($categorie) ?></td>
                                    <td><span class="badge-count"><?= $nbGaranties ?></span></td>
                                    <td>
                                        <div class="actions">
                                            <a class="btn-soft" href="showFormule.php?id=<?= $id ?>"><i class="bi bi-eye"></i></a>
                                            <a class="btn-soft" href="updateFormule.php?id=<?= $id ?>"><i class="bi bi-pencil"></i></a>
                                            <form method="POST" action="deleteFormule.php" style="display:inline;" onsubmit="return confirm('Supprimer cette formule ?');">
                                                <input type="hidden" name="id" value="<?= $id ?>">
                                                <?= CsrfHelper::field() ?>
                                                <button type="submit" class="btn-soft danger" style="border:none;background:none;"><i class="bi bi-trash3"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div id="emptyRow" class="empty-row">Aucune formule trouvée.</div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.getElementById('topbarDate').textContent =
    new Date().toLocaleDateString('fr-FR', {
        weekday:'long',
        day:'numeric',
        month:'long',
        year:'numeric'
    });

function normalize(text) {
    return (text || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

function applyFilters() {
    const search = normalize(document.getElementById('searchInput').value);
    const category = document.getElementById('categoryFilter').value;
    const sort = document.getElementById('sortSelect').value;
    const tbody = document.getElementById('formulesBody');
    const rows = Array.from(document.querySelectorAll('.formule-row'));

    rows.forEach(row => {
        const rowSearch = normalize(row.dataset.search);
        const rowCategory = row.dataset.category;
        const matchesSearch = rowSearch.includes(search);
        const matchesCategory = category === 'all' || rowCategory === category;
        row.style.display = matchesSearch && matchesCategory ? '' : 'none';
    });

    const visibleRows = rows.filter(row => row.style.display !== 'none');

    visibleRows.sort((a, b) => {
        const priceA = parseFloat(a.dataset.price || '0');
        const priceB = parseFloat(b.dataset.price || '0');
        const franchiseA = parseFloat(a.dataset.franchise || '0');
        const franchiseB = parseFloat(b.dataset.franchise || '0');
        const garantiesA = parseInt(a.dataset.garanties || '0', 10);
        const garantiesB = parseInt(b.dataset.garanties || '0', 10);

        switch (sort) {
            case 'price-asc': return priceA - priceB;
            case 'price-desc': return priceB - priceA;
            case 'franchise-asc': return franchiseA - franchiseB;
            case 'franchise-desc': return franchiseB - franchiseA;
            case 'garanties-asc': return garantiesA - garantiesB;
            case 'garanties-desc': return garantiesB - garantiesA;
            default: return 0;
        }
    });

    visibleRows.forEach(row => tbody.appendChild(row));
    document.getElementById('emptyRow').style.display = visibleRows.length === 0 ? 'block' : 'none';
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('categoryFilter').value = 'all';
    document.getElementById('sortSelect').value = 'default';
    applyFilters();
}

function exportPDF() {
    const visibleRows = Array.from(document.querySelectorAll('.formule-row'))
        .filter(row => row.style.display !== 'none');

    if (visibleRows.length === 0) {
        alert('Aucune formule é exporter.');
        return;
    }

    let tableRows = '';
    visibleRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        tableRows += `
            <tr>
                <td>${cells[0].innerText}</td>
                <td>${cells[1].innerText}</td>
                <td>${cells[2].innerText}</td>
                <td>${cells[3].innerText}</td>
                <td>${cells[4].innerText}</td>
                <td>${cells[5].innerText}</td>
                <td>${cells[6].innerText}</td>
                <td>${cells[7].innerText}</td>
            </tr>`;
    });

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Export PDF - Formules</title>
            <style>
                * { box-sizing: border-box; }
                body { font-family: Arial, sans-serif; margin: 0; padding: 32px; color: #0A1931; background: #fff; }
                .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 4px solid #EE5828; padding-bottom: 18px; margin-bottom: 24px; }
                .brand { display: flex; align-items: center; gap: 14px; }
                .logo { width: 54px; height: 54px; border-radius: 18px; background: linear-gradient(135deg, #0A1931, #00b4d8); color: #fff; display: grid; place-items: center; font-size: 26px; font-weight: 800; }
                h1 { margin: 0; font-size: 28px; color: #0A1931; }
                .subtitle { margin-top: 4px; color: #EE5828; font-weight: 700; }
                .meta { text-align: right; color: #34495e; font-size: 13px; line-height: 1.6; }
                table { width: 100%; border-collapse: collapse; font-size: 12px; }
                th { background: #0A1931; color: #fff; padding: 10px; text-align: left; }
                td { border: 1px solid #d8dee9; padding: 9px; }
                tr:nth-child(even) td { background: #f7f9fc; }
                .footer { margin-top: 22px; color: #667085; font-size: 12px; border-top: 1px solid #d8dee9; padding-top: 12px; }
                @media print { body { padding: 20px; } }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="brand">
                    <div class="logo">P</div>
                    <div>
                        <h1>Liste des formules</h1>
                        <div class="subtitle">Protex Assurance é Back Office</div>
                    </div>
                </div>
                <div class="meta">
                    Exporté le ${new Date().toLocaleDateString('fr-FR')}<br>
                    Total exporté : ${visibleRows.length} formules
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Prix</th>
                        <th>Franchise</th>
                        <th>Niveau</th>
                        <th>Catégorie</th>
                        <th>Garanties liées</th>
                    </tr>
                </thead>
                <tbody>${tableRows}</tbody>
            </table>

            <div class="footer">Ce document contient uniquement les formules visibles aprés recherche, filtre et tri.</div>
            <script>window.onload = function(){ window.print(); };<\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}
</script>

<script src="assets/js/main.js"></script>
<script src="assets/js/admin.js"></script>
</body>
</html>


