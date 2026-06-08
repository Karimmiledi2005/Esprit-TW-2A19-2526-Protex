<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();

require_once __DIR__ . '/../../controller/CategorieController.php';
require_once __DIR__ . '/../../controller/FormuleController.php';

if (!isset($_GET['id'])) {
    die("ID catégorie manquant.");
}

$id = (int)$_GET['id'];

$categorieC = new CategorieController();
$formuleC = new FormuleController();

$categorie = $categorieC->showCategorie($id);
$formules = $formuleC->listFormulesByCategorie($id);

if (!$categorie) {
    die("Catégorie introuvable.");
}

$totalFormules = count($formules);
$totalGaranties = array_sum(array_map(fn($f) => (int)($f['nb_garanties'] ?? 0), $formules));
$totalPrix = array_sum(array_map(fn($f) => (float)($f['prix_formule'] ?? 0), $formules));
$prixMoyen = $totalFormules > 0 ? $totalPrix / $totalFormules : 0;
$formulesUtilisees = count(array_filter($formules, fn($f) => (int)($f['nb_contrats'] ?? 0) > 0));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail catégorie</title>
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

        .stats-grid { display: grid; grid-template-columns: repeat(3, minmax(180px, 1fr)); gap: 18px; margin: 22px 0 24px; }
        .stat-card { position: relative; overflow: hidden; border-radius: 22px; padding: 24px; min-height: 130px; border: 1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.06); box-shadow: 0 18px 50px rgba(0,0,0,.12); }
        .stat-card::after { content: ''; position: absolute; right: -38px; top: -38px; width: 118px; height: 118px; border-radius: 50%; background: rgba(255,255,255,.06); }
        .stat-card.blue { background: linear-gradient(135deg, rgba(0,180,216,.20), rgba(10,25,49,.55)); }
        .stat-card.gold { background: linear-gradient(135deg, rgba(238,88,40,.22), rgba(10,25,49,.55)); }
        .stat-card.green { background: linear-gradient(135deg, rgba(46,204,113,.18), rgba(10,25,49,.55)); }
        .stat-icon { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; background: rgba(255,255,255,.10); color: #00d4ff; font-size: 20px; margin-bottom: 14px; }
        .stat-card.gold .stat-icon { color: #ffb36b; }
        .stat-card.green .stat-icon { color: #55efc4; }
        .stat-value { font-size: 30px; font-weight: 900; color: #fff; line-height: 1; }
        .stat-label { margin-top: 8px; color: rgba(255,255,255,.78); font-weight: 600; }
        @media (max-width: 900px) { .stats-grid { grid-template-columns: 1fr; } }
        .mini-stats { display: grid; grid-template-columns: repeat(2, minmax(180px, 1fr)); gap: 14px; margin: 18px 0; }
        .mini-stat { border: 1px solid rgba(255,255,255,.1); background: rgba(255,255,255,.06); border-radius: 18px; padding: 16px; }
        .mini-stat-value { font-size: 26px; font-weight: 800; color: #fff; }
        .mini-stat-label { color: rgba(255,255,255,.7); margin-top: 4px; }
        .toolbar { display:flex; gap:12px; align-items:center; flex-wrap:wrap; padding:16px 24px; border-bottom:1px solid rgba(255,255,255,.08); }
        .search-box { flex:1; min-width:260px; position:relative; }
        .search-box i { position:absolute; top:50%; left:14px; transform:translateY(-50%); color:rgba(255,255,255,.65); }
        .toolbar input, .toolbar select { height:44px; border-radius:14px; border:1px solid rgba(255,255,255,.13); background:rgba(255,255,255,.07); color:#fff; padding:0 14px; outline:none; }
        .toolbar input { width:100%; padding-left:42px; }
        .toolbar select { min-width:210px; }
        .toolbar option { color:#0A1931; }
        .export-btn, .reset-btn { height:42px; border-radius:14px; border:1px solid rgba(255,255,255,.15); background:rgba(255,255,255,.08); color:#fff; padding:0 14px; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
        .export-btn:hover, .reset-btn:hover { border-color:#00b4d8; color:#00d4ff; }
        .badge-count { display:inline-flex; align-items:center; justify-content:center; min-width:34px; padding:6px 10px; border-radius:999px; background:rgba(0,180,216,.15); border:1px solid rgba(0,180,216,.35); color:#91eaff; font-weight:700; }
        .empty-row { display:none; text-align:center; padding:24px; color:rgba(255,255,255,.75); }

        .pagination {
            display:flex; align-items:center; justify-content:space-between; gap:14px;
            padding:16px 24px; border-top:1px solid rgba(255,255,255,.08);
        }
        .pagination-info { color:rgba(255,255,255,.72); font-size:13px; font-weight:600; }
        .pagination-btns { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .page-btn { min-width:38px; height:38px; border-radius:12px; border:1px solid rgba(255,255,255,.13); background:rgba(255,255,255,.07); color:#fff; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; font-weight:800; }
        .page-btn.active { border-color:#ff6b1a; color:#ff6b1a; background:rgba(255,107,26,.13); }
        .page-btn:disabled { opacity:.45; cursor:not-allowed; }
        @media (max-width:700px){ .pagination{flex-direction:column; align-items:stretch;} .pagination-btns{justify-content:center;} }
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
                <div class="topbar-title">Détail de la catégorie</div>
                <div class="topbar-sub" id="topbarDate"></div>
            </div>
        </div>

        <div class="content">

            <div class="page-header-bar">
                <div>
                    <div class="page-title">Catégorie : <?= htmlspecialchars($categorie['nom_categorie']) ?></div>
                    <div class="page-breadcrumb">
                        <i class="bi bi-house"></i>
                        <a href="categories_back.php">Catégories</a>
                        <i class="bi bi-chevron-right" style="font-size:10px;"></i>
                        <span>Détail</span>
                    </div>
                </div>

                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="categories_back.php" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Retour</a>
                    <a href="addFormule.php?id_categorie=<?= (int)$categorie['id_categorie'] ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Ajouter une formule</a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon"><i class="bi bi-list-check"></i></div>
                    <div class="stat-value"><?= $totalFormules ?></div>
                    <div class="stat-label">Total formules</div>
                </div>

                <div class="stat-card gold">
                    <div class="stat-icon"><i class="bi bi-shield-check"></i></div>
                    <div class="stat-value"><?= $totalGaranties ?></div>
                    <div class="stat-label">Garanties associées</div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon"><i class="bi bi-people-check"></i></div>
                    <div class="stat-value"><?= (int)$formulesUtilisees ?></div>
                    <div class="stat-label">Formules utilisées</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-info-circle"></i> Informations catégorie</div>
                </div>

                <div class="modal-body">
                    <div class="detail-grid">
                        <div class="detail-field">
                            <div class="detail-field-label">ID</div>
                            <div class="detail-field-value">#<?= (int)$categorie['id_categorie'] ?></div>
                        </div>

                        <div class="detail-field">
                            <div class="detail-field-label">Nom</div>
                            <div class="detail-field-value"><?= htmlspecialchars($categorie['nom_categorie']) ?></div>
                        </div>

                        <div class="detail-field full">
                            <div class="detail-field-label">Description</div>
                            <div class="detail-field-value"><?= htmlspecialchars($categorie['description_categorie'] ?? 'é') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top:20px;">
                <div class="card-header">
                    <div class="card-title"><i class="bi bi-list-check"></i> Formules de cette catégorie</div>
                    <button type="button" class="export-btn" onclick="exportPDF()"><i class="bi bi-file-earmark-pdf"></i> Export PDF</button>
                </div>

                <div class="toolbar">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchInput" placeholder="Rechercher par ID, nom, description, niveau, prix, franchise, garanties..." oninput="formulesCurrentPage = 1; applyFilters()">
                    </div>
                    <select id="sortSelect" onchange="formulesCurrentPage = 1; applyFilters()">
                        <option value="default">Tri par défaut</option>
                        <option value="price-asc">Prix croissant</option>
                        <option value="price-desc">Prix décroissant</option>
                        <option value="franchise-asc">Franchise croissante</option>
                        <option value="franchise-desc">Franchise décroissante</option>
                        <option value="garanties-desc">Garanties liées ?</option>
                        <option value="garanties-asc">Garanties liées ?</option>
                    </select>
                    <button type="button" class="reset-btn" onclick="resetFilters()"><i class="bi bi-x-circle"></i> Réinitialiser</button>
                </div>

                <div class="table-wrap">
                    <table id="formulesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom formule</th>
                                <th>Description</th>
                                <th>Prix</th>
                                <th>Franchise</th>
                                <th>Niveau</th>
                                <th>Garanties liées</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="formulesBody">
                            <?php foreach ($formules as $formule):
                                $prix = (float)($formule['prix_formule'] ?? 0);
                                $franchise = (float)($formule['franchise_formule'] ?? 0);
                                $nbGaranties = (int)($formule['nb_garanties'] ?? 0);
                            ?>
                                <tr class="formule-row"
                                    data-search="<?= htmlspecialchars(strtolower(
                                        '#' . ($formule['id_formule'] ?? '') . ' ' .
                                        ($formule['nom_formule'] ?? '') . ' ' .
                                        ($formule['description_formule'] ?? '') . ' ' .
                                        ($formule['niveau_formule'] ?? '') . ' ' .
                                        ($categorie['nom_categorie'] ?? '') . ' ' .
                                        number_format($prix, 2, '.', '') . ' ' .
                                        number_format($franchise, 2, '.', '') . ' ' .
                                        $nbGaranties . ' garanties'
                                    )) ?>"
                                    data-price="<?= $prix ?>"
                                    data-franchise="<?= $franchise ?>"
                                    data-garanties="<?= $nbGaranties ?>">
                                    <td>#<?= (int)$formule['id_formule'] ?></td>
                                    <td><strong><?= htmlspecialchars($formule['nom_formule']) ?></strong></td>
                                    <td><?= htmlspecialchars($formule['description_formule'] ?? 'é') ?></td>
                                    <td><?= number_format($prix, 2) ?> DT</td>
                                    <td><?= number_format($franchise, 2) ?> DT</td>
                                    <td><?= htmlspecialchars($formule['niveau_formule'] ?? 'é') ?></td>
                                    <td><span class="badge-count"><?= $nbGaranties ?></span></td>
                                    <td>
                                        <div class="actions">
                                            <a class="btn-soft" href="showFormule.php?id=<?= (int)$formule['id_formule'] ?>"><i class="bi bi-eye"></i></a>
                                            <a class="btn-soft" href="updateFormule.php?id=<?= (int)$formule['id_formule'] ?>"><i class="bi bi-pencil"></i></a>
                                            <form method="POST" action="deleteFormule.php" style="display:inline;" onsubmit="return confirm('Supprimer cette formule ?');">
                                                <input type="hidden" name="id" value="<?= (int)$formule['id_formule'] ?>">
                                                <input type="hidden" name="id_categorie" value="<?= (int)$categorie['id_categorie'] ?>">
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
        weekday:'long',
        day:'numeric',
        month:'long',
        year:'numeric'
    });

function normalize(text) {
    return (text || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

const formulesPerPage = 8;
let formulesCurrentPage = 1;
let formulesFilteredRows = [];

function applyFilters() {
    const searchValue = normalize(document.getElementById('searchInput').value.trim());
    const keywords = searchValue.split(/\s+/).filter(Boolean);
    const sort = document.getElementById('sortSelect').value;
    const tbody = document.getElementById('formulesBody');
    const rows = Array.from(document.querySelectorAll('.formule-row'));

    formulesFilteredRows = rows.filter(row => {
        const searchableText = normalize(row.dataset.search);
        return keywords.length === 0 || keywords.every(word => searchableText.includes(word));
    });

    formulesFilteredRows.sort((a, b) => {
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

    rows.forEach(row => row.style.display = 'none');
    formulesFilteredRows.forEach(row => tbody.appendChild(row));

    const total = formulesFilteredRows.length;
    const totalPages = Math.max(1, Math.ceil(total / formulesPerPage));
    if (formulesCurrentPage > totalPages) formulesCurrentPage = totalPages;

    const start = (formulesCurrentPage - 1) * formulesPerPage;
    const end = start + formulesPerPage;
    formulesFilteredRows.slice(start, end).forEach(row => row.style.display = '');

    document.getElementById('emptyRow').style.display = total === 0 ? 'block' : 'none';
    renderFormulesPagination(total, start, end, totalPages);
}

function renderFormulesPagination(total, start, end, totalPages) {
    const info = document.getElementById('paginationInfo');
    const btns = document.getElementById('paginationBtns');

    const shownStart = total === 0 ? 0 : start + 1;
    const shownEnd = Math.min(end, total);
    info.textContent = `Affichage ${shownStart}é${shownEnd} sur ${total} formule${total > 1 ? 's' : ''}`;

    let html = `
        <button class="page-btn" onclick="goFormulePage(${formulesCurrentPage - 1})" ${formulesCurrentPage <= 1 ? 'disabled' : ''}>
            <i class="bi bi-chevron-left"></i>
        </button>
    `;

    const maxButtons = 7;
    let startPage = Math.max(1, formulesCurrentPage - 3);
    let endPage = Math.min(totalPages, startPage + maxButtons - 1);
    if (endPage - startPage < maxButtons - 1) startPage = Math.max(1, endPage - maxButtons + 1);

    for (let p = startPage; p <= endPage; p++) {
        html += `<button class="page-btn ${p === formulesCurrentPage ? 'active' : ''}" onclick="goFormulePage(${p})">${p}</button>`;
    }

    html += `
        <button class="page-btn" onclick="goFormulePage(${formulesCurrentPage + 1})" ${formulesCurrentPage >= totalPages ? 'disabled' : ''}>
            <i class="bi bi-chevron-right"></i>
        </button>
    `;

    btns.innerHTML = html;
}

function goFormulePage(page) {
    const totalPages = Math.max(1, Math.ceil(formulesFilteredRows.length / formulesPerPage));
    if (page < 1 || page > totalPages) return;
    formulesCurrentPage = page;
    applyFilters();
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('sortSelect').value = 'default';
    formulesCurrentPage = 1;
    applyFilters();
}

function escapeHTML(value) {
    return (value || '').toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function exportPDF() {
    const visibleRows = formulesFilteredRows.length ? formulesFilteredRows : Array.from(document.querySelectorAll('.formule-row'));

    if (visibleRows.length === 0) {
        alert('Aucune formule é exporter.');
        return;
    }

    const logoUrl = new URL('../FrontOffice/logo.png', window.location.href).href;
    const exportDate = new Date().toLocaleDateString('fr-FR');
    const categoryName = <?= json_encode($categorie['nom_categorie'] ?? 'Catégorie') ?>;

    const tableRows = visibleRows.map(row => {
        const cells = row.querySelectorAll('td');
        return `
            <tr>
                <td class="ref">${escapeHTML(cells[0].innerText.trim())}</td>
                <td>${escapeHTML(cells[1].innerText.trim())}</td>
                <td>${escapeHTML(cells[2].innerText.trim())}</td>
                <td class="money">${escapeHTML(cells[3].innerText.trim())}</td>
                <td class="money">${escapeHTML(cells[4].innerText.trim())}</td>
                <td><span class="badge">${escapeHTML(cells[5].innerText.trim())}</span></td>
                <td><span class="count-pill">${escapeHTML(cells[6].innerText.trim())}</span></td>
            </tr>
        `;
    }).join('');

    const printWindow = window.open('', '_blank');

    printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Export PDF - Formules</title>
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

                .category-chip {
                    display: inline-flex;
                    align-items: center;
                    padding: 8px 13px;
                    border-radius: 999px;
                    background: rgba(0, 180, 216, 0.12);
                    border: 1px solid rgba(0, 180, 216, 0.35);
                    color: #0A274C;
                    font-weight: 800;
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

                th {
                    background: #0A1931;
                    color: #ffffff;
                    padding: 12px 11px;
                    text-align: left;
                    font-size: 11px;
                    text-transform: uppercase;
                    letter-spacing: 0.7px;
                }

                td {
                    padding: 11px;
                    border-bottom: 1px solid #e4ebf3;
                    color: #17293f;
                    vertical-align: middle;
                }

                tr:nth-child(even) td {
                    background: #f7f9fc;
                }

                tr:last-child td {
                    border-bottom: none;
                }

                .ref {
                    color: #00b4d8;
                    font-weight: 800;
                }

                .money {
                    font-weight: 800;
                    color: #0A1931;
                }

                .badge, .count-pill {
                    display: inline-block;
                    padding: 5px 10px;
                    border-radius: 999px;
                    background: rgba(0, 180, 216, 0.10);
                    border: 1px solid rgba(0, 180, 216, 0.28);
                    color: #0A274C;
                    font-size: 11px;
                    font-weight: 800;
                }

                .count-pill {
                    background: rgba(238, 88, 40, 0.10);
                    border-color: rgba(238, 88, 40, 0.30);
                    color: #EE5828;
                }

                .footer {
                    margin-top: 22px;
                    padding-top: 14px;
                    border-top: 1px solid #dbe7f3;
                    color: #637487;
                    font-size: 12px;
                    display: flex;
                    justify-content: space-between;
                    gap: 14px;
                }

                @media print {
                    body { background: #ffffff; padding: 0; }
                    .page { box-shadow: none; border-radius: 0; border: none; }
                }
            </style>
        </head>
        <body>
            <div class="page">
                <div class="hero">
                    <div class="brand">
                        <img src="${logoUrl}" alt="Protex">
                        <div>
                            <div class="brand-title">Protex</div>
                            <div class="brand-sub">Back Office</div>
                        </div>
                    </div>
                    <div class="export-meta">
                        Exporté le ${exportDate}<br>
                        <span class="meta-pill">${visibleRows.length} formules exportées</span>
                    </div>
                </div>

                <div class="content">
                    <div class="doc-title">
                        <div>
                            <h1>Liste des formules</h1>
                            <div class="note">Ce document contient uniquement les formules visibles aprés recherche et tri.</div>
                        </div>
                        <div class="category-chip">Catégorie : ${escapeHTML(categoryName)}</div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom formule</th>
                                <th>Description</th>
                                <th>Prix</th>
                                <th>Franchise</th>
                                <th>Niveau</th>
                                <th>Garanties liées</th>
                            </tr>
                        </thead>
                        <tbody>${tableRows}</tbody>
                    </table>

                    <div class="footer">
                        <span>Protex Assurance é Export Back Office</span>
                        <span>Document généré automatiquement</span>
                    </div>
                </div>
            </div>

            <script>
                window.onload = function () {
                    window.print();
                };
            <\/script>
        </body>
        </html>
    `);

    printWindow.document.close();
}
applyFilters();
</script>

<script src="assets/js/main.js"></script>
<script src="assets/js/admin.js"></script>
</body>
</html>


