<?php
require_once __DIR__ . '/../../controller/GarantieController.php';

$controller = new GarantieController();
$garanties = $controller->listGaranties();

$totalGaranties = count($garanties);
$garantiesLieesFormules = method_exists($controller, 'countGarantiesLieesAuxFormules')
    ? $controller->countGarantiesLieesAuxFormules()
    : 0;
$categoriesListe = [];

foreach ($garanties as $g) {
    $cat = $g->getNomCategorie();
    if ($cat) {
        $categoriesListe[] = $cat;
    }
}

$categoriesLiees = count(array_unique($categoriesListe));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Garanties é Protex Admin</title>
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
                <div class="topbar-title">Gestion des garanties</div>
                <div class="topbar-sub" id="topbarDate"></div>
            </div>
        </div>

        <div class="content">
            <div class="page-header-bar">
                <div>
                    <div class="page-title">Garanties</div>
                    <div class="page-breadcrumb">
                        <i class="bi bi-house"></i>
                        <a href="admin.php">Accueil</a>
                        <i class="bi bi-chevron-right" style="font-size:10px;"></i>
                        <span>Garanties</span>
                    </div>
                </div>
                <a href="addGarantie.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Ajouter une garantie
                </a>
            </div>

            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon"><i class="bi bi-shield-check"></i></div>
                    <div class="stat-value"><?= $totalGaranties ?></div>
                    <div class="stat-label">Total garanties</div>
                </div>

                <div class="stat-card gold">
                    <div class="stat-icon"><i class="bi bi-link-45deg"></i></div>
                    <div class="stat-value"><?= $garantiesLieesFormules ?></div>
                    <div class="stat-label">Garanties liées aux formules</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-table"></i> Liste des garanties</div>
                    <button type="button" class="btn btn-outline btn-sm" onclick="exportPDF()">
                        <i class="bi bi-file-earmark-pdf"></i> Export PDF
                    </button>
                </div>

                <div style="padding:16px 24px; border-bottom:1px solid var(--glass-border);">
                    <div class="toolbar">
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="text" id="searchInput" placeholder="Rechercher une garantie...">
                        </div>

                        <select class="filter-select" id="filterCategory">
                            <option value="">Toutes les catégories</option>
                            <option value="Auto">Auto</option>
                            <option value="Santé">Santé</option>
                            <option value="Habitation">Habitation</option>
                            <option value="Protection">Protection</option>
                        </select>

                        <select class="filter-select" id="sortSelect">
                            <option value="default">Tri par défaut</option>
                            <option value="plafond-asc">Plafond croissant</option>
                            <option value="plafond-desc">Plafond décroissant</option>
                        </select>

                        <button class="btn btn-outline btn-sm" onclick="resetFilters()">
                            <i class="bi bi-x-circle"></i> Réinitialiser
                        </button>
                    </div>
                </div>

                <div class="table-wrap">
                    <table id="garantiesTable">
                        <thead>
                            <tr>
                                <th>Garantie</th>
                                <th>Catégorie</th>
                                <th>Plafond couverture</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="garantiesBody">
                            <?php foreach ($garanties as $garantie): ?>
                                <?php
                                    $nom = $garantie->getNomGarantie();
                                    $description = $garantie->getDescriptionGarantie();
                                    $categorie = $garantie->getNomCategorie() ?? 'é';
                                    $plafond = method_exists($garantie, 'getPlafond') ? (float)$garantie->getPlafond() : 0;
                                ?>
                                <tr data-name="<?= strtolower(htmlspecialchars($nom . ' ' . $description)) ?>"
                                    data-category="<?= htmlspecialchars($categorie) ?>"
                                    data-plafond="<?= htmlspecialchars((string)$plafond) ?>">
                                    <td>
                                        <div class="garantie-name"><?= htmlspecialchars($nom) ?></div>
                                        <div class="garantie-desc"><?= htmlspecialchars($description) ?></div>
                                    </td>
                                    <td>
                                        <span class="cat-badge">
                                            <i class="bi bi-folder2-open"></i>
                                            <?= htmlspecialchars($categorie) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= number_format($plafond, 2, ',', ' ') ?> DT</strong>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a class="btn-soft" title="Voir" href="showGarantie.php?id=<?= $garantie->getIdGarantie() ?>">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a class="btn-soft" title="Modifier" href="updateGarantie.php?id=<?= $garantie->getIdGarantie() ?>">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a class="btn-soft danger" title="Supprimer" href="deleteGarantie.php?id=<?= $garantie->getIdGarantie() ?>" onclick="return confirm('Supprimer cette garantie ?')">
                                                <i class="bi bi-trash3"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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

<script>
document.getElementById('topbarDate').textContent =
    new Date().toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });

const perPage = 8;
let currentPage = 1;
let filteredRows = [];

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterCategory').value = '';
    document.getElementById('sortSelect').value = 'default';
    currentPage = 1;
    filterGaranties();
}

function getSortedRows(rows) {
    const sortValue = document.getElementById('sortSelect').value;
    const sorted = [...rows];

    sorted.sort((a, b) => {
        const indexA = Number(a.dataset.index || 0);
        const indexB = Number(b.dataset.index || 0);
        const plafondA = Number(a.dataset.plafond || 0);
        const plafondB = Number(b.dataset.plafond || 0);

        if (sortValue === 'plafond-asc') {
            return plafondA - plafondB;
        }

        if (sortValue === 'plafond-desc') {
            return plafondB - plafondA;
        }

        return indexA - indexB;
    });

    return sorted;
}

function filterGaranties() {
    const search = document.getElementById('searchInput').value.toLowerCase().trim();
    const category = document.getElementById('filterCategory').value;
    const tbody = document.getElementById('garantiesBody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    filteredRows = rows.filter(row => {
        const name = row.dataset.name || '';
        const rowCategory = row.dataset.category || '';

        const matchSearch = !search || name.includes(search);
        const matchCategory = !category || rowCategory === category;

        return matchSearch && matchCategory;
    });

    filteredRows = getSortedRows(filteredRows);

    const totalPages = Math.max(1, Math.ceil(filteredRows.length / perPage));
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    rows.forEach(row => row.style.display = 'none');

    filteredRows.forEach(row => tbody.appendChild(row));

    const start = (currentPage - 1) * perPage;
    const end = start + perPage;
    filteredRows.slice(start, end).forEach(row => row.style.display = '');

    renderPagination();
}

function renderPagination() {
    const total = filteredRows.length;
    const totalPages = Math.max(1, Math.ceil(total / perPage));
    const start = total === 0 ? 0 : (currentPage - 1) * perPage + 1;
    const end = Math.min(currentPage * perPage, total);

    const info = document.getElementById('paginationInfo');
    const btns = document.getElementById('paginationBtns');

    if (info) {
        info.textContent = `Affichage ${start}é${end} sur ${total} garantie${total > 1 ? 's' : ''}`;
    }

    if (!btns) return;

    let pagesHtml = '';
    const maxButtons = 5;
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + maxButtons - 1);

    if (endPage - startPage < maxButtons - 1) {
        startPage = Math.max(1, endPage - maxButtons + 1);
    }

    for (let p = startPage; p <= endPage; p++) {
        pagesHtml += `<button class="page-btn ${p === currentPage ? 'active' : ''}" onclick="goPage(${p})">${p}</button>`;
    }

    btns.innerHTML = `
        <button class="page-btn" onclick="goPage(${currentPage - 1})" ${currentPage <= 1 ? 'disabled' : ''}>
            <i class="bi bi-chevron-left"></i>
        </button>
        ${pagesHtml}
        <button class="page-btn" onclick="goPage(${currentPage + 1})" ${currentPage >= totalPages ? 'disabled' : ''}>
            <i class="bi bi-chevron-right"></i>
        </button>
    `;
}

function goPage(page) {
    const totalPages = Math.max(1, Math.ceil(filteredRows.length / perPage));
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    filterGaranties();
}

function escapeHTML(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function exportPDF() {
    const visibleRows = Array.from(document.querySelectorAll('#garantiesBody tr'))
        .filter(row => row.style.display !== 'none');

    if (visibleRows.length === 0) {
        alert('Aucune garantie é exporter.');
        return;
    }

    const logoUrl = new URL('../FrontOffice/logo.png', window.location.href).href;
    const exportDate = new Date().toLocaleDateString('fr-FR');

    const tableRows = visibleRows.map(row => {
        const cells = row.querySelectorAll('td');
        return `
            <tr>
                <td>${escapeHTML(cells[0].innerText.trim())}</td>
                <td><span class="badge">${escapeHTML(cells[1].innerText.trim())}</span></td>
                <td class="money">${escapeHTML(cells[2].innerText.trim())}</td>
            </tr>
        `;
    }).join('');

    const printWindow = window.open('', '_blank');

    printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Export PDF - Garanties</title>
            <style>
                * { box-sizing: border-box; }

                body {
                    margin: 0;
                    padding: 28px;
                    font-family: "Segoe UI", Arial, sans-serif;
                    background: #f4f7fb;
                    color: #0A1931;
                }

                .page {
                    min-height: calc(100vh - 56px);
                    background: #ffffff;
                    border: 1px solid #dbe7f3;
                    border-radius: 22px;
                    overflow: hidden;
                    box-shadow: 0 18px 45px rgba(10, 25, 49, 0.12);
                }

                .hero {
                    display: flex;
                    justify-content: space-between;
                    gap: 24px;
                    padding: 26px 30px;
                    color: #ffffff;
                    background:
                        radial-gradient(circle at 85% 0%, rgba(255, 107, 26, 0.42), transparent 34%),
                        linear-gradient(135deg, #0A1931 0%, #0A274C 58%, #123B63 100%);
                }

                .brand {
                    display: flex;
                    align-items: center;
                    gap: 14px;
                }

                .brand img {
                    width: 54px;
                    height: 54px;
                    object-fit: contain;
                }

                .brand-title {
                    font-size: 26px;
                    font-weight: 800;
                    letter-spacing: 0.2px;
                    line-height: 1;
                }

                .brand-sub {
                    margin-top: 5px;
                    color: #00b4d8;
                    font-size: 12px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 1.3px;
                }

                .export-meta {
                    text-align: right;
                    font-size: 13px;
                    color: rgba(255, 255, 255, 0.86);
                    line-height: 1.7;
                }

                .meta-pill {
                    display: inline-block;
                    margin-top: 7px;
                    padding: 6px 12px;
                    border-radius: 999px;
                    background: rgba(0, 180, 216, 0.15);
                    border: 1px solid rgba(0, 180, 216, 0.45);
                    color: #ffffff;
                    font-weight: 700;
                }

                .content {
                    padding: 26px 30px 30px;
                }

                .doc-title {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-end;
                    gap: 20px;
                    margin-bottom: 18px;
                    padding-bottom: 14px;
                    border-bottom: 3px solid #FF6B1A;
                }

                h1 {
                    margin: 0;
                    color: #0A1931;
                    font-size: 23px;
                }

                .note {
                    margin-top: 5px;
                    color: #5d6b7c;
                    font-size: 12px;
                }

                table {
                    width: 100%;
                    border-collapse: separate;
                    border-spacing: 0;
                    overflow: hidden;
                    border: 1px solid #d9e3ee;
                    border-radius: 14px;
                    font-size: 12px;
                }

                thead th {
                    background: #0A1931;
                    color: #ffffff;
                    text-align: left;
                    padding: 12px 10px;
                    font-size: 11px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    border-right: 1px solid rgba(255, 255, 255, 0.14);
                }

                tbody td {
                    padding: 11px 10px;
                    border-top: 1px solid #d9e3ee;
                    border-right: 1px solid #edf2f7;
                    vertical-align: top;
                    white-space: pre-line;
                }

                tbody tr:nth-child(even) td {
                    background: #f8fbff;
                }

                .money {
                    color: #0A1931;
                    font-weight: 800;
                }

                .badge {
                    display: inline-block;
                    padding: 5px 9px;
                    border-radius: 999px;
                    font-weight: 700;
                    font-size: 11px;
                    background: #e9f7fb;
                    color: #007da3;
                    border: 1px solid #bdebf5;
                }

                .footer {
                    margin-top: 18px;
                    display: flex;
                    justify-content: space-between;
                    gap: 20px;
                    color: #64748b;
                    font-size: 11px;
                    border-top: 1px solid #e2e8f0;
                    padding-top: 12px;
                }

                @media print {
                    body {
                        padding: 0;
                        background: #ffffff;
                    }

                    .page {
                        box-shadow: none;
                        border-radius: 0;
                        border: none;
                    }
                }
            </style>
        </head>
        <body>
            <section class="page">
                <div class="hero">
                    <div class="brand">
                        <img src="${logoUrl}" alt="Protex Logo">
                        <div>
                            <div class="brand-title">Protex</div>
                            <div class="brand-sub">Assurance Digitale</div>
                        </div>
                    </div>
                    <div class="export-meta">
                        <strong>Back Office</strong><br>
                        Exporté le ${exportDate}<br>
                        <span class="meta-pill">${visibleRows.length} garantie${visibleRows.length > 1 ? 's' : ''} exportée${visibleRows.length > 1 ? 's' : ''}</span>
                    </div>
                </div>

                <div class="content">
                    <div class="doc-title">
                        <div>
                            <h1>Liste des garanties</h1>
                            <div class="note">Garanties visibles aprés recherche, filtre catégorie et tri par plafond.</div>
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Garantie</th>
                                <th>Catégorie</th>
                                <th>Plafond couverture</th>
                            </tr>
                        </thead>
                        <tbody>${tableRows}</tbody>
                    </table>

                    <div class="footer">
                        <span>Protex Assurance é Document généré automatiquement</span>
                        <span>Export PDF Back Office</span>
                    </div>
                </div>
            </section>

            <script>
                window.onload = function() {
                    window.print();
                };
            <\/script>
        </body>
        </html>
    `);

    printWindow.document.close();
}

document.querySelectorAll('#garantiesBody tr').forEach((row, index) => {
    row.dataset.index = index;
});

document.getElementById('searchInput').addEventListener('input', () => {
    currentPage = 1;
    filterGaranties();
});
document.getElementById('filterCategory').addEventListener('change', () => {
    currentPage = 1;
    filterGaranties();
});
document.getElementById('sortSelect').addEventListener('change', () => {
    currentPage = 1;
    filterGaranties();
});

filterGaranties();
</script>

<script src="assets/js/main.js"></script>
<script src="assets/js/admin.js"></script>

</body>
</html>


