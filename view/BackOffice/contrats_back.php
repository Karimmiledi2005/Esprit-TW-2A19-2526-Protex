<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();

require_once __DIR__ . '/../../controller/ContratController.php';

if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
$base = defined('BASE_URL') ? BASE_URL : '';

$contratController = new ContratController();
$contrats = $contratController->getAll();

$totalContrats = count($contrats);
$totalActifs = 0;
$totalAttente = 0;
$totalExpires = 0;

foreach ($contrats as $contrat) {
    $statut = strtolower(trim($contrat->getStatutContrat()));

    if ($statut === 'actif') {
        $totalActifs++;
    } elseif ($statut === 'en attente' || $statut === 'en_attente') {
        $totalAttente++;
    } else {
        $totalExpires++;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contrats é Protex Admin</title>
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

  <!-- ===== SIDEBAR ===== -->
    <?php require_once __DIR__.'/assets/includes/sidebar.php'; ?>

  <main class="main">

    <div class="topbar">
      <div>
        <div class="topbar-title">Gestion des contrats</div>
        <div class="topbar-sub" id="topbarDate"></div>
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

      <div class="page-header-bar">
        <div>
          <div class="page-title">Contrats</div>
          <div class="page-breadcrumb">
            <i class="bi bi-house"></i>
            <a href="#">Accueil</a>
            <i class="bi bi-chevron-right" style="font-size:10px;"></i>
            <span>Contrats</span>
          </div>
        </div>
        <div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="calendrier_contrats.php" class="btn btn-outline">
              <i class="bi bi-calendar-event"></i> Calendrier
            </a>
            <a href="addContrat.php" class="btn btn-primary">
              <i class="bi bi-plus"></i> Ajouter un contrat
            </a>
          </div>
        </div>
      </div>

      <div class="stats-grid">
        <div class="stat-card blue">
          <div class="stat-icon"><i class="bi bi-file-earmark-text"></i></div>
          <div class="stat-value"><?= $totalContrats ?></div>
          <div class="stat-label">Total contrats</div>
          <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> Portefeuille</div>
        </div>

        <div class="stat-card gold">
          <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
          <div class="stat-value"><?= $totalAttente ?></div>
          <div class="stat-label">En attente</div>
          <div class="stat-trend trend-warn"><i class="bi bi-clock"></i> é valider</div>
        </div>

        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
          <div class="stat-value"><?= $totalActifs ?></div>
          <div class="stat-label">Actifs</div>
          <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> En cours</div>
        </div>

        <div class="stat-card red">
          <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
          <div class="stat-value"><?= $totalExpires ?></div>
          <div class="stat-label">Expirés / résiliés</div>
          <div class="stat-trend trend-down"><i class="bi bi-exclamation-triangle"></i> é suivre</div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <i class="bi bi-table"></i> Liste des contrats
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button class="btn btn-outline btn-sm" onclick="exportPDF()">
              <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </button>
          </div>
        </div>

        <div class="toolbar-inner">
          <div class="toolbar" style="margin-bottom:0;">
            <div class="search-box">
              <i class="bi bi-search"></i>
              <input type="text" id="searchInput" placeholder="Rechercher par numéro, client, formule, catégorie...">
            </div>

            <select class="filter-select" id="filterStatut">
              <option value="">Tous les statuts</option>
              <option value="actif">Actif</option>
              <option value="en attente">En attente</option>
              <option value="expire">Expiré</option>
              <option value="resilie">Résilié</option>
            </select>

            <select class="filter-select" id="filterType">
              <option value="">Toutes les catégories</option>
              <option value="auto">Auto</option>
              <option value="sante">Santé</option>
              <option value="habitation">Habitation</option>
              <option value="protection">Protection</option>
            </select>

            <input type="date" class="filter-select" id="filterDate" style="padding-right:10px;">

            <select class="filter-select" id="sortContrats">
              <option value="">Tri par défaut</option>
              <option value="prime_asc">Prime croissante</option>
              <option value="prime_desc">Prime décroissante</option>
              <option value="date_debut_asc">Date début ancienne ? récente</option>
              <option value="date_debut_desc">Date début récente ? ancienne</option>
            </select>

            <button class="btn btn-outline btn-sm" onclick="resetFilters()">
              <i class="bi bi-x-circle"></i> Réinitialiser
            </button>
          </div>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Né Contrat</th>
                <th>Formule choisie</th>
                <th>Catégorie</th>
                <th>Prime</th>
                <th>Date début</th>
                <th>Date fin</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="contratBody">
              <?php foreach ($contrats as $contrat): ?>
                <?php
                  $type = $contrat->getTypeContrat();
                  $statutRaw = strtolower(trim($contrat->getStatutContrat()));
                  $statutClass = str_replace([' ', 'é'], ['_', 'e'], $statutRaw);

                  $icon = 'bi-file-earmark';
                  if ($type === 'Auto') $icon = 'bi-car-front';
                  elseif ($type === 'Santé' || $type === 'Sante') $icon = 'bi-heart-pulse';
                  elseif ($type === 'Habitation') $icon = 'bi-house-door';

                  $nomFormule = $contrat->getNomFormule();
                  if (!$nomFormule || $nomFormule === 'é') {
                      $nomFormule = $contrat->getFormuleContrat();
                  }
                  if (!$nomFormule) {
                      $nomFormule = 'é';
                  }
                ?>
                <tr
                  data-search="<?= htmlspecialchars(strtolower(
                    $contrat->getNumeroContrat() . ' ' .
                    $contrat->getTypeContrat() . ' ' .
                    $contrat->getNomCategorie() . ' ' .
                    $nomFormule . ' ' .
                    $contrat->getNomClient() . ' ' .
                    $contrat->getPrenomClient() . ' ' .
                    $contrat->getEmailClient()
                  )) ?>"
                  data-statut="<?= htmlspecialchars(str_replace(['é', '_'], ['e', ' '], $statutRaw)) ?>"
                  data-type="<?= htmlspecialchars(strtolower(str_replace(['é', 'é'], ['e', 'E'], $contrat->getTypeContrat()))) ?>"
                  data-date-debut="<?= htmlspecialchars($contrat->getDateDebutContrat()) ?>"
                  data-date-fin="<?= htmlspecialchars($contrat->getDateFinContrat()) ?>"
                  data-prime="<?= htmlspecialchars((float)$contrat->getPrimeContrat()) ?>"
                >
                  <td style="color:var(--accent);font-weight:700;">
                    <?= htmlspecialchars($contrat->getNumeroContrat()) ?>
                  </td>
                  <td>
                    <div class="type-cell">
                      <div class="type-icon">
                        <i class="bi <?= $icon ?>"></i>
                      </div>
                      <span><?= htmlspecialchars($nomFormule) ?></span>
                    </div>
                  </td>
                  <td style="color:#fff;font-weight:600;">
                    <?= htmlspecialchars($contrat->getNomCategorie() ?: 'é') ?>
                  </td>
                  <td>
                    <span class="prime-badge">
                      <i class="bi bi-cash-stack"></i>
                      <?= htmlspecialchars($contrat->getPrimeContrat()) ?> DT
                    </span>
                  </td>
                  <td><?= htmlspecialchars($contrat->getDateDebutContrat()) ?></td>
                  <td><?= htmlspecialchars($contrat->getDateFinContrat()) ?></td>
                  <td>
                    <span class="status-select <?= $statutClass ?>">
                      <?= htmlspecialchars($contrat->getStatutContrat()) ?>
                    </span>
                  </td>
                  <td>
                    <div style="display:flex;gap:8px;">
                      <a href="showContrat.php?id=<?= (int)$contrat->getIdContrat() ?>"
                         class="btn btn-soft btn-sm"
                         title="Voir détails">
                        <i class="bi bi-eye"></i>
                      </a>
                      <a href="<?= $base ?>/download_contrat_admin.php?id=<?= (int)$contrat->getIdContrat() ?>"
                         class="btn btn-soft btn-sm"
                         title="Télécharger PDF"
                         target="_blank">
                        <i class="bi bi-file-earmark-pdf"></i>
                      </a>
                      <a href="updateContrat.php?id=<?= (int)$contrat->getIdContrat() ?>"
                         class="btn btn-soft btn-sm"
                         title="Modifier">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <form method="POST" action="deleteContrat.php" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce contrat ?');">
                        <input type="hidden" name="id" value="<?= (int)$contrat->getIdContrat() ?>">
                        <?= CsrfHelper::field() ?>
                        <button type="submit" class="btn btn-soft danger btn-sm" title="Supprimer" style="border:none;">
                          <i class="bi bi-trash3"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <div id="emptyState" style="display:none;text-align:center;padding:48px 20px;color:var(--text-secondary);">
            <i class="bi bi-folder-x" style="font-size:36px;display:block;margin-bottom:10px;opacity:0.3;"></i>
            <p style="font-size:14px;">Aucun contrat trouvé</p>
          </div>
        </div>

        <div class="pagination">
          <div class="pagination-info" id="paginationInfo"></div>
          <div class="pagination-btns">
            <button class="page-btn" disabled><i class="bi bi-chevron-left"></i></button>
            <button class="page-btn active">1</button>
            <button class="page-btn" disabled><i class="bi bi-chevron-right"></i></button>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<div class="modal-overlay" id="modalDetail">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><i class="bi bi-file-earmark-text"></i> Détails du contrat</div>
      <button class="modal-close" onclick="closeModal('modalDetail')"><i class="bi bi-x"></i></button>
    </div>
    <div id="modalDetailBody"></div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalDetail')">Fermer</button>
    </div>
  </div>
</div>

<div class="modal-overlay delete-modal" id="modalDelete">
  <div class="modal">
    <div class="delete-icon"><i class="bi bi-trash3"></i></div>
    <div class="delete-title">Supprimer ce contrat ?</div>
    <div class="delete-msg" id="deleteMsg">Cette action est irréversible.</div>
    <div class="modal-footer" style="justify-content:center;margin-top:28px;">
      <button class="btn btn-outline" onclick="closeModal('modalDelete')">Annuler</button>
      <button class="btn btn-danger">
        <i class="bi bi-trash3"></i> Supprimer définitivement
      </button>
    </div>
  </div>
</div>

<script>
  document.getElementById('topbarDate').textContent =
    new Date().toLocaleDateString('fr-FR', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    });

  function openModal(id) {
    document.getElementById(id).classList.add('open');
  }

  function closeModal(id) {
    document.getElementById(id).classList.remove('open');
  }

  function showDetails(numero, type, categorie, prime, dateDebut, dateFin, franchise, statut, clientNom, clientEmail) {
    let icon = 'bi-file-earmark';
    if (type === 'Auto') icon = 'bi-car-front';
    else if (type === 'Santé' || type === 'Sante') icon = 'bi-heart-pulse';
    else if (type === 'Habitation') icon = 'bi-house-door';

    document.getElementById('modalDetailBody').innerHTML = `
      <div style="padding:24px;">
        <div class="contrat-modal-header">
          <div class="contrat-modal-icon">
            <i class="bi ${icon}"></i>
          </div>
          <div>
            <div class="contrat-modal-type">${type} é ${categorie}</div>
            <div class="contrat-modal-id">${numero}</div>
          </div>
        </div>

        <div class="detail-grid">
          <div class="detail-field">
            <div class="detail-field-label"><i class="bi bi-person"></i> Client</div>
            <div class="detail-field-value">${clientNom}</div>
          </div>

          <div class="detail-field">
            <div class="detail-field-label"><i class="bi bi-envelope"></i> Email</div>
            <div class="detail-field-value">${clientEmail}</div>
          </div>

          <div class="detail-field">
            <div class="detail-field-label"><i class="bi bi-cash-stack"></i> Prime</div>
            <div class="detail-field-value">${prime} DT</div>
          </div>

          <div class="detail-field">
            <div class="detail-field-label"><i class="bi bi-shield"></i> Franchise</div>
            <div class="detail-field-value">${franchise} DT</div>
          </div>

          <div class="detail-field">
            <div class="detail-field-label"><i class="bi bi-calendar-event"></i> Date début</div>
            <div class="detail-field-value">${dateDebut}</div>
          </div>

          <div class="detail-field">
            <div class="detail-field-label"><i class="bi bi-calendar-check"></i> Date fin</div>
            <div class="detail-field-value">${dateFin}</div>
          </div>

          <div class="detail-field">
            <div class="detail-field-label"><i class="bi bi-info-circle"></i> Statut</div>
            <div class="detail-field-value">${statut}</div>
          </div>

          <div class="detail-field">
            <div class="detail-field-label"><i class="bi bi-tags"></i> Catégorie</div>
            <div class="detail-field-value">${categorie}</div>
          </div>
        </div>
      </div>
    `;
    openModal('modalDetail');
  }

  function openDeleteModal(numero) {
    document.getElementById('deleteMsg').textContent =
      `Le contrat ${numero} sera supprimé définitivement.`;
    openModal('modalDelete');
  }

  function normalizeValue(value) {
    return (value || '')
      .toString()
      .toLowerCase()
      .trim()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/_/g, ' ');
  }

  function filterRows() {
    const search = normalizeValue(document.getElementById('searchInput').value);
    const statut = normalizeValue(document.getElementById('filterStatut').value);
    const type = normalizeValue(document.getElementById('filterType').value);
    const date = document.getElementById('filterDate').value;

    const rows = document.querySelectorAll('#contratBody tr');
    let visible = 0;

    rows.forEach(row => {
      const rowSearch = normalizeValue(row.dataset.search || '');
      const rowStatut = normalizeValue(row.dataset.statut || '');
      const rowType = normalizeValue(row.dataset.type || '');
      const rowDateDebut = row.dataset.dateDebut || '';
      const rowDateFin = row.dataset.dateFin || '';

      const okSearch = !search || rowSearch.includes(search);
      const okStatut = !statut || rowStatut === statut;
      const okType = !type || rowType === type;
      const okDate = !date || rowDateDebut === date || rowDateFin === date;

      if (okSearch && okStatut && okType && okDate) {
        row.style.display = '';
        visible++;
      } else {
        row.style.display = 'none';
      }
    });

    sortRows();

    document.getElementById('emptyState').style.display = visible ? 'none' : 'block';
    document.getElementById('paginationInfo').textContent =
      visible > 0
        ? `Affichage 1é${visible} sur ${visible} contrat${visible > 1 ? 's' : ''}`
        : 'Affichage 0é0 sur 0 contrat';
  }

  function sortRows() {
    const sortValue = document.getElementById('sortContrats').value;
    const tbody = document.getElementById('contratBody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    if (!sortValue) {
      return;
    }

    rows.sort((a, b) => {
      if (sortValue === 'prime_asc' || sortValue === 'prime_desc') {
        const primeA = parseFloat(a.dataset.prime || '0');
        const primeB = parseFloat(b.dataset.prime || '0');
        return sortValue === 'prime_asc' ? primeA - primeB : primeB - primeA;
      }

      if (sortValue === 'date_debut_asc' || sortValue === 'date_debut_desc') {
        const dateA = new Date(a.dataset.dateDebut || '1900-01-01').getTime();
        const dateB = new Date(b.dataset.dateDebut || '1900-01-01').getTime();
        return sortValue === 'date_debut_asc' ? dateA - dateB : dateB - dateA;
      }

      return 0;
    });

    rows.forEach(row => tbody.appendChild(row));
  }

  function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterStatut').value = '';
    document.getElementById('filterType').value = '';
    document.getElementById('filterDate').value = '';
    document.getElementById('sortContrats').value = '';
    filterRows();
  }

  function getVisibleContratsData() {
    const rows = document.querySelectorAll('#contratBody tr');
    const data = [];

    rows.forEach(row => {
      if (row.style.display === 'none') return;

      const cols = row.querySelectorAll('td');
      if (cols.length >= 7) {
        data.push({
          numero: cols[0].innerText.trim(),
          formule: cols[1].innerText.trim(),
          categorie: cols[2].innerText.trim(),
          prime: cols[3].innerText.trim(),
          dateDebut: cols[4].innerText.trim(),
          dateFin: cols[5].innerText.trim(),
          statut: cols[6].innerText.trim()
        });
      }
    });

    return data;
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
    const data = getVisibleContratsData();

    if (data.length === 0) {
      alert('Aucun contrat é exporter.');
      return;
    }

    const logoUrl = new URL('../FrontOffice/logo.png', window.location.href).href;
    const exportDate = new Date().toLocaleDateString('fr-FR');

    const rowsHTML = data.map(item => `
      <tr>
        <td class="ref">${escapeHTML(item.numero)}</td>
        <td>${escapeHTML(item.formule)}</td>
        <td><span class="badge">${escapeHTML(item.categorie)}</span></td>
        <td class="money">${escapeHTML(item.prime)}</td>
        <td>${escapeHTML(item.dateDebut)}</td>
        <td>${escapeHTML(item.dateFin)}</td>
        <td><span class="status">${escapeHTML(item.statut)}</span></td>
      </tr>
    `).join('');

    const printWindow = window.open('', '_blank');

    printWindow.document.write(`
      <!DOCTYPE html>
      <html lang="fr">
      <head>
        <meta charset="UTF-8">
        <title>Export PDF - Contrats</title>
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
            vertical-align: middle;
          }

          tbody tr:nth-child(even) td {
            background: #f8fbff;
          }

          .ref {
            color: #00a7d5;
            font-weight: 800;
          }

          .money {
            color: #0A1931;
            font-weight: 800;
          }

          .badge, .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 11px;
          }

          .badge {
            background: #e9f7fb;
            color: #007da3;
            border: 1px solid #bdebf5;
          }

          .status {
            background: #fff3e8;
            color: #e05a0f;
            border: 1px solid #ffd3b6;
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
              <span class="meta-pill">${data.length} contrat${data.length > 1 ? 's' : ''} exporté${data.length > 1 ? 's' : ''}</span>
            </div>
          </div>

          <div class="content">
            <div class="doc-title">
              <div>
                <h1>Liste des contrats</h1>
                <div class="note">Contrats visibles aprés recherche, filtre et tri.</div>
              </div>
            </div>

            <table>
              <thead>
                <tr>
                  <th>Né Contrat</th>
                  <th>Formule</th>
                  <th>Catégorie</th>
                  <th>Prime</th>
                  <th>Date début</th>
                  <th>Date fin</th>
                  <th>Statut</th>
                </tr>
              </thead>
              <tbody>${rowsHTML}</tbody>
            </table>

            <div class="footer">
              <span>Protex Assurance é Document généré automatiquement</span>
              <span>Export PDF Back Office</span>
            </div>
          </div>
        </section>

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

  document.getElementById('searchInput').addEventListener('input', filterRows);
  document.getElementById('filterStatut').addEventListener('change', filterRows);
  document.getElementById('filterType').addEventListener('change', filterRows);
  document.getElementById('filterDate').addEventListener('change', filterRows);
  document.getElementById('sortContrats').addEventListener('change', filterRows);

  filterRows();
</script>

<script src="assets/js/main.js"></script>
<script src="assets/js/admin.js"></script>
</body>
</html>

