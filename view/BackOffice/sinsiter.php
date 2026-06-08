<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sinistres — Protex Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets_sinistre_traitement/css/variables.css">
    <link rel="stylesheet" href="assets_sinistre_traitement/css/base.css">
    <link rel="stylesheet" href="assets_sinistre_traitement/css/layout.css">
    <link rel="stylesheet" href="assets_sinistre_traitement/css/admin-users.css">
    <script src="assets_sinistre_traitement/js/validation.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- ANTIFRAUD STYLES -->
  <style>
  .fraud-badge { display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.4px;white-space:nowrap; }
  .fraud-badge i { font-size:10px; }
  .fraud-badge.faible   { background:rgba(46,196,182,.15);color:#2ec4b6;border:1px solid rgba(46,196,182,.3); }
  .fraud-badge.normal   { background:rgba(244,162,97,.15);color:#f4a261;border:1px solid rgba(244,162,97,.3); }
  .fraud-badge.fraude   { background:rgba(180,0,50,.25);color:#ff4d6d;border:1px solid rgba(180,0,50,.4);animation:fraud-pulse 1.4s infinite; }
  .fraud-badge.none     { background:rgba(255,255,255,.04);color:var(--text-secondary);border:1px dashed var(--glass-border); }
  @keyframes fraud-pulse { 0%,100%{box-shadow:0 0 0 0 rgba(255,77,109,.4)} 50%{box-shadow:0 0 0 6px rgba(255,77,109,0)} }
  .fraud-panel { margin-top:20px;background:rgba(255,255,255,.03);border:1px solid var(--glass-border);border-radius:var(--radius-md);overflow:hidden; }
  .fraud-panel-header { display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--glass-border);background:rgba(255,255,255,.02); }
  .fraud-panel-title  { display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:var(--text-primary); }
  .fraud-panel-title i { color:var(--accent); }
  .fraud-score-row { display:flex;align-items:center;gap:16px;padding:16px 18px;border-bottom:1px solid var(--glass-border); }
  .fraud-score-circle { width:72px;height:72px;border-radius:50%;border:3px solid var(--glass-border);display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;background:var(--glass-bg);transition:border-color .3s; }
  .fraud-score-circle.faible   { border-color:#2ec4b6; }
  .fraud-score-circle.normal   { border-color:#f4a261; }
  .fraud-score-circle.fraude   { border-color:#ff4d6d;box-shadow:0 0 14px rgba(255,77,109,.3); }
  .fraud-score-num   { font-size:22px;font-weight:800;line-height:1;color:#fff; }
  .fraud-score-denom { font-size:10px;color:var(--text-secondary); }
  .fraud-score-meta  { flex:1; }
  .fraud-score-meta .niveau-label { font-size:15px;font-weight:700;margin-bottom:4px; }
  .niveau-label.faible   { color:#2ec4b6; }
  .niveau-label.normal   { color:#f4a261; }
  .niveau-label.fraude   { color:#ff4d6d; }
  .suggestion-pill { display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;margin-bottom:8px; }
  .suggestion-pill.accepter    { background:rgba(46,196,182,.15);color:#2ec4b6;border:1px solid rgba(46,196,182,.3); }
  .suggestion-pill.investiguer { background:rgba(244,162,97,.15);color:#f4a261;border:1px solid rgba(244,162,97,.3); }
  .suggestion-pill.refuser     { background:rgba(230,57,70,.15);color:#e63946;border:1px solid rgba(230,57,70,.3); }
  .fraud-bars { padding:14px 18px;border-bottom:1px solid var(--glass-border); }
  .fraud-bar-row { margin-bottom:10px; }
  .fraud-bar-label { display:flex;justify-content:space-between;font-size:11px;color:var(--text-secondary);margin-bottom:4px; }
  .fraud-bar-track { height:5px;background:var(--glass-bg);border-radius:99px;overflow:hidden; }
  .fraud-bar-fill  { height:100%;border-radius:99px;transition:width .6s ease; }
  .bar-texte        { background:linear-gradient(90deg,#00b4d8,#0096c7); }
  .bar-comportement { background:linear-gradient(90deg,#f4a261,#e76f51); }
  .bar-contrat      { background:linear-gradient(90deg,#2ec4b6,#219ebc); }
  .fraud-flags { display:flex;flex-wrap:wrap;gap:8px;padding:14px 18px;border-bottom:1px solid var(--glass-border); }
  .fraud-flag { display:inline-flex;align-items:center;gap:5px;font-size:11px;padding:4px 10px;border-radius:20px;font-weight:600; }
  .fraud-flag.active   { background:rgba(230,57,70,.15);color:#e63946;border:1px solid rgba(230,57,70,.3); }
  .fraud-flag.inactive { background:rgba(255,255,255,.04);color:var(--text-secondary);border:1px solid var(--glass-border);text-decoration:line-through;opacity:.5; }
  .fraud-recommandation { padding:14px 18px;font-size:13px;line-height:1.7;color:var(--text-secondary);font-style:italic;border-left:3px solid var(--accent);margin:14px 18px;border-radius:0 var(--radius-sm) var(--radius-sm) 0;background:rgba(0,180,216,.05); }
  .fraud-reanalyse-btn { display:flex;align-items:center;gap:6px;font-size:11px;font-weight:600;padding:5px 12px;border-radius:var(--radius-sm);border:1px solid var(--glass-border);background:var(--glass-bg);color:var(--text-secondary);cursor:pointer;transition:var(--transition); }
  .fraud-reanalyse-btn:hover { border-color:var(--accent);color:var(--accent); }
  .fraud-reanalyse-btn.loading { opacity:.6;pointer-events:none; }
  @keyframes spin { to { transform:rotate(360deg); } }
  .spin { display:inline-block;animation:spin .8s linear infinite; }
  </style>
</head>
  <style>
    /* ── Status select inline ── */
    .status-select {
      padding: 4px 24px 4px 10px;
      border-radius: 20px;
      font-size: 11px; font-weight: 600;
      border: 1px solid transparent;
      cursor: pointer; outline: none;
      font-family: var(--font-body);
      transition: var(--transition);
      appearance: none; -webkit-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 7px center;
    }
    .status-select.en_attente { background-color:rgba(244,162,97,0.15); color:var(--gold);    border-color:rgba(244,162,97,0.3); }
    .status-select.valide     { background-color:rgba(0,180,216,0.15);  color:var(--accent);  border-color:rgba(0,180,216,0.3); }
    .status-select.rembourse  { background-color:rgba(46,196,182,0.15); color:var(--success); border-color:rgba(46,196,182,0.3); }
    .status-select.refuse     { background-color:rgba(230,57,70,0.15);  color:var(--danger);  border-color:rgba(230,57,70,0.3); }
    .status-select option { background:#0d2845; color:var(--text-primary); }

    /* ── Type icon cell ── */
    .type-cell { display:flex; align-items:center; gap:8px; color:var(--text-secondary); font-size:13px; }
    .type-icon {
      width:28px; height:28px; border-radius:var(--radius-sm);
      background:var(--glass-bg); border:1px solid var(--glass-border);
      display:flex; align-items:center; justify-content:center;
      font-size:13px; color:var(--accent); flex-shrink:0;
    }

    /* ── Detail modal ── */
    .detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:4px; }
    .detail-field {
      background:var(--glass-bg); border:1px solid var(--glass-border);
      border-radius:var(--radius-md); padding:14px;
    }
    .detail-field.full { grid-column:span 2; }
    .detail-field-label {
      font-size:10px; font-weight:600; text-transform:uppercase;
      letter-spacing:1px; color:var(--text-secondary);
      display:flex; align-items:center; gap:5px; margin-bottom:6px;
    }
    .detail-field-value { font-size:13px; color:var(--text-primary); font-weight:500; line-height:1.6; }

    /* ── Sinistre header in detail modal ── */
    .sinistre-modal-header {
      display:flex; align-items:center; gap:14px;
      margin-bottom:20px; padding-bottom:18px;
      border-bottom:1px solid var(--glass-border);
    }
    .sinistre-modal-icon {
      width:48px; height:48px; border-radius:var(--radius-md);
      background:var(--accent-glow); border:1px solid rgba(0,180,216,0.25);
      display:flex; align-items:center; justify-content:center;
      font-size:22px; color:var(--accent); flex-shrink:0;
    }
    .sinistre-modal-type { font-family:var(--font-display); font-size:16px; font-weight:700; color:#fff; }
    .sinistre-modal-id   { font-size:12px; color:var(--text-secondary); margin-top:3px; }

    /* toolbar padding override */
    .toolbar-inner {
      padding:16px 24px;
      border-bottom:1px solid var(--glass-border);
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

    <div class="topbar">
      <div>
        <div class="topbar-title">Gestion des sinistres</div>
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
          <div class="page-title">Sinistres</div>
          <div class="page-breadcrumb">
            <i class="bi bi-house"></i>
            <a href="#">Accueil</a>
            <i class="bi bi-chevron-right" style="font-size:10px;"></i>
            <span>Sinistres</span>
          </div>
        </div>
        <div class="view-toggle">
          <button class="toggle-btn active" id="listViewBtn" onclick="toggleView('list')">
            <i class="bi bi-list-ul"></i> Liste
          </button>
          <button class="toggle-btn" id="kanbanViewBtn" onclick="toggleView('kanban')">
            <i class="bi bi-columns-gap"></i> Kanban
          </button>
        </div>
      </div>

      <!-- STATS -->
      <div class="stats-grid">
        <div class="stat-card blue">
          <div class="stat-icon"><i class="bi bi-shield-exclamation"></i></div>
          <div class="stat-value" id="statTotal">0</div>
          <div class="stat-label">Total sinistres</div>
          <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> +8.2% ce mois</div>
        </div>
        <div class="stat-card gold">
          <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
          <div class="stat-value" id="statAttente">0</div>
          <div class="stat-label">En attente</div>
          <div class="stat-trend trend-warn"><i class="bi bi-clock"></i> À traiter</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
          <div class="stat-value" id="statValides">0</div>
          <div class="stat-label">Validés</div>
          <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> +2 ce mois</div>
        </div>
        <div class="stat-card red">
          <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
          <div class="stat-value" id="statRefuses">0</div>
          <div class="stat-label">Refusés</div>
          <div class="stat-trend trend-down"><i class="bi bi-exclamation-triangle"></i> À vérifier</div>
        </div>
      </div>

      <!-- CHART CARD -->
      <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
          <div class="card-title">
            <i class="bi bi-graph-up"></i> Évolution des Sinistres
          </div>
        </div>
        <div class="card-body" style="height: 300px; padding: 20px; display: flex; gap: 20px;">
          <div style="flex: 2;"><canvas id="sinistresChart"></canvas></div>
          <div style="flex: 1;"><canvas id="statutChart"></canvas></div>
        </div>
      </div>

      <!-- TABLE CARD -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <i class="bi bi-table"></i> Liste des sinistres
          </div>
            <div style="display: flex; gap: 8px;">
            <button class="btn btn-outline btn-sm" onclick="openAgentGrid()">
              <i class="bi bi-people"></i> Charge Agents
            </button>
            <a href="sinistre_export.php?format=pdf&month=<?= date('Y-m') ?>" class="btn btn-outline btn-sm" style="text-decoration:none;" target="_blank">
              <i class="bi bi-file-pdf"></i> PDF
            </a>
            <a href="sinistre_export.php?format=excel&month=<?= date('Y-m') ?>" class="btn btn-outline btn-sm" style="text-decoration:none;" target="_blank">
              <i class="bi bi-file-earmark-excel"></i> Excel
            </a>
          </div>
        </div>

        <!-- TOOLBAR -->
        <div class="toolbar-inner">
          <div class="toolbar" style="margin-bottom:0;">
            <div class="search-box">
              <i class="bi bi-search"></i>
              <input type="text" id="searchInput" placeholder="Rechercher par ID, contrat, type...">
            </div>
            <select class="filter-select" id="filterStatut">
              <option value="">Tous les statuts</option>
              <option value="en_attente">En attente</option>
              <option value="valide">Validé</option>
              <option value="rembourse">Remboursé</option>
              <option value="refuse">Refusé</option>
            </select>
            <select class="filter-select" id="filterType">
              <option value="">Tous les types</option>
              <option value="Accident auto">Accident auto</option>
              <option value="Incendie">Incendie</option>
              <option value="Vol">Vol</option>
              <option value="Dégât des eaux">Dégât des eaux</option>
            </select>
            <input type="date" class="filter-select" id="filterDate" style="padding-right:10px;">
            <button class="btn btn-outline btn-sm" onclick="resetFilters()">
              <i class="bi bi-x-circle"></i> Réinitialiser
            </button>
          </div>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th class="sortable" data-sort="id" onclick="toggleSort('id')">ID</th>
                <th class="sortable" data-sort="contrat" onclick="toggleSort('contrat')">Contrat</th>
                <th class="sortable" data-sort="type" onclick="toggleSort('type')">Type</th>
                <th class="sortable" data-sort="client" onclick="toggleSort('client')">Client</th>
                <th class="sortable" data-sort="date" onclick="toggleSort('date')">Date</th>
                <th class="sortable" data-sort="statut" onclick="toggleSort('statut')">Statut</th>
                <th><i class="bi bi-shield-shaded"></i> Risque IA</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="sinistreBody"></tbody>
          </table>
          <div id="emptyState" style="display:none;text-align:center;padding:48px 20px;color:var(--text-secondary);">
            <i class="bi bi-shield-slash" style="font-size:36px;display:block;margin-bottom:10px;opacity:0.3;"></i>
            <p style="font-size:14px;">Aucun sinistre trouvé</p>
          </div>
        </div>

        <div class="pagination" id="listPagination">
          <div class="pagination-info" id="paginationInfo"></div>
          <div class="pagination-btns" id="paginationBtns"></div>
        </div>

        <!-- KANBAN CONTAINER -->
        <div class="kanban-container" id="kanbanContainer">
          <!-- Col 1: En attente -->
          <div class="kanban-column" data-statut="en_attente">
            <div class="kanban-header">
              <div class="kanban-title"><i class="bi bi-hourglass-split" style="color:var(--gold);"></i> En attente</div>
              <span class="kanban-count" id="countAttente">0</span>
            </div>
            <div class="kanban-cards" id="kanbanAttente"></div>
          </div>
          <!-- Col 2: Remboursé -->
          <div class="kanban-column" data-statut="rembourse">
            <div class="kanban-header">
              <div class="kanban-title"><i class="bi bi-check-circle" style="color:var(--success);"></i> Remboursés</div>
              <span class="kanban-count" id="countRembourse">0</span>
            </div>
            <div class="kanban-cards" id="kanbanRembourse"></div>
          </div>
          <!-- Col 3: Refusé -->
          <div class="kanban-column" data-statut="refuse">
            <div class="kanban-header">
              <div class="kanban-title"><i class="bi bi-x-circle" style="color:var(--danger);"></i> Refusés</div>
              <span class="kanban-count" id="countRefuse">0</span>
            </div>
            <div class="kanban-cards" id="kanbanRefuse"></div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- ===== MODAL DÉTAIL ===== -->
<div class="modal-overlay" id="modalDetail">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><i class="bi bi-shield-exclamation"></i> Détails du sinistre</div>
      <button class="modal-close" onclick="closeModal('modalDetail')"><i class="bi bi-x"></i></button>
    </div>
    <div id="modalDetailBody"></div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalDetail')">Fermer</button>
    </div>
  </div>
</div>
<!-- ===== MODAL SUPPRIMER ===== -->
<div class="modal-overlay delete-modal" id="modalDelete">
  <div class="modal">
    <div class="delete-icon"><i class="bi bi-trash3"></i></div>
    <div class="delete-title">Supprimer ce sinistre ?</div>
    <div class="delete-msg" id="deleteMsg">Cette action est irréversible.</div>
    <div class="modal-footer" style="justify-content:center;margin-top:28px;">
      <button class="btn btn-outline" onclick="closeModal('modalDelete')">Annuler</button>
      <button class="btn btn-danger" onclick="confirmDelete()">
        <i class="bi bi-trash3"></i> Supprimer définitivement
      </button>
    </div>
  </div>
</div>

<!-- ===== MODAL ASSIGNER SINISTRE ===== -->
<div class="modal-overlay" id="modalAssignSinistre">
  <div class="modal" style="max-width:860px;width:96vw;">
    <div class="modal-header">
      <div class="modal-title"><i class="bi bi-person-check"></i> Assigner un sinistre à un agent</div>
      <button class="modal-close" onclick="closeModal('modalAssignSinistre')"><i class="bi bi-x"></i></button>
    </div>
    <div style="padding:20px 24px;">

      <!-- Step indicator -->
      <div id="assignStepBar" style="display:flex;gap:0;margin-bottom:20px;border-radius:10px;overflow:hidden;border:1px solid var(--glass-border);">
        <div id="assignStep1Tab" style="flex:1;padding:10px 14px;text-align:center;font-size:12px;font-weight:700;background:var(--accent);color:#fff;transition:.2s;">
          <i class="bi bi-shield-exclamation"></i> 1. Choisir le sinistre
        </div>
        <div id="assignStep2Tab" style="flex:1;padding:10px 14px;text-align:center;font-size:12px;font-weight:700;background:var(--glass-bg);color:var(--text-secondary);transition:.2s;">
          <i class="bi bi-person"></i> 2. Choisir l'agent
        </div>
        <div id="assignStep3Tab" style="flex:1;padding:10px 14px;text-align:center;font-size:12px;font-weight:700;background:var(--glass-bg);color:var(--text-secondary);transition:.2s;">
          <i class="bi bi-check2-circle"></i> 3. Confirmer
        </div>
      </div>

      <!-- Step 1: Choose sinistre -->
      <div id="assignStep1">
        <div style="margin-bottom:12px;">
          <input type="text" id="assignSinistreSearch" placeholder="Rechercher un sinistre (ID, type, client)…"
            style="width:100%;padding:10px 14px;border-radius:10px;border:1px solid var(--glass-border);background:var(--glass-bg);color:#fff;font-size:13px;box-sizing:border-box;outline:none;"
            oninput="filterAssignSinistres()">
        </div>
        <div id="assignSinistreList" style="max-height:340px;overflow-y:auto;display:flex;flex-direction:column;gap:8px;">
          <div style="text-align:center;padding:30px;color:var(--text-secondary);"><i class="bi bi-arrow-repeat spin"></i> Chargement…</div>
        </div>
      </div>

      <!-- Step 2: Choose agent -->
      <div id="assignStep2" style="display:none;">
        <div style="margin-bottom:14px;padding:12px 16px;background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:10px;font-size:13px;">
          <span style="color:var(--text-secondary);">Sinistre sélectionné :</span>
          <strong id="assignSelectedSinistre" style="color:#fff;margin-left:6px;"></strong>
        </div>
        <div id="assignAgentList" style="max-height:300px;overflow-y:auto;display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;">
          <div style="text-align:center;padding:30px;color:var(--text-secondary);grid-column:1/-1;"><i class="bi bi-arrow-repeat spin"></i> Chargement…</div>
        </div>
        <div style="margin-top:16px;">
          <button class="btn btn-outline btn-sm" onclick="assignGoStep(1)"><i class="bi bi-arrow-left"></i> Retour</button>
        </div>
      </div>

      <!-- Step 3: Confirm -->
      <div id="assignStep3" style="display:none;text-align:center;padding:20px 0;">
        <div style="font-size:36px;margin-bottom:12px;">🎯</div>
        <div style="font-size:15px;font-weight:700;color:#fff;margin-bottom:6px;">Confirmer l'assignation ?</div>
        <div id="assignConfirmText" style="color:var(--text-secondary);font-size:13px;margin-bottom:24px;"></div>
        <div style="display:flex;gap:12px;justify-content:center;">
          <button class="btn btn-outline" onclick="assignGoStep(2)"><i class="bi bi-arrow-left"></i> Retour</button>
          <button class="btn btn-primary" id="assignConfirmBtn" onclick="confirmAssignment()">
            <i class="bi bi-person-check"></i> Confirmer l'assignation
          </button>
        </div>
      </div>

      <div id="assignSuccessMsg" style="display:none;text-align:center;padding:20px;">
        <div style="font-size:36px;margin-bottom:10px;">✅</div>
        <div style="font-size:15px;font-weight:700;color:#2ec4b6;">Sinistre assigné avec succès !</div>
        <button class="btn btn-outline btn-sm" style="margin-top:16px;" onclick="closeModal('modalAssignSinistre');loadSinistres();">Fermer</button>
      </div>

    </div>
  </div>
</div>

<script src="assets_sinistre_traitement/js/sinistre.js?v=4"></script>
<script>
let _assignSinistres = [];
let _assignAgents    = [];
let _assignSinistreId = null;
let _assignSinistreLabel = '';
let _assignAgentId    = null;
let _assignAgentLabel = '';

async function openAgentGrid() {
  // Reset state
  _assignSinistreId = null; _assignAgentId = null;
  document.getElementById('assignSuccessMsg').style.display = 'none';
  document.querySelectorAll('#assignStep1,#assignStep2,#assignStep3').forEach(el => el.style.display = 'none');
  document.getElementById('assignStep1').style.display = '';
  document.getElementById('assignSinistreSearch').value = '';
  assignGoStep(1);
  openModal('modalAssignSinistre');

  // Load sinistres not yet assigned (en_attente)
  document.getElementById('assignSinistreList').innerHTML = '<div style="text-align:center;padding:30px;color:var(--text-secondary);"><i class="bi bi-arrow-repeat spin"></i> Chargement…</div>';
  try {
    const res  = await fetch('sinistre_list.php');
    const json = await res.json();
    _assignSinistres = (json.data || []).filter(s => s.statut === 'en_attente');
    renderAssignSinistres();
  } catch(e) {
    document.getElementById('assignSinistreList').innerHTML = '<div style="color:#ff6b6b;">Erreur de chargement.</div>';
  }

  // Load agents
  try {
    const res2  = await fetch('get_agents_by_agence.php');
    const json2 = await res2.json();
    _assignAgents = json2.data || [];
  } catch(e) { _assignAgents = []; }
}

function assignGoStep(n) {
  [1,2,3].forEach(i => {
    document.getElementById('assignStep'+i).style.display = i===n ? '' : 'none';
    const tab = document.getElementById('assignStep'+i+'Tab');
    tab.style.background = i===n ? 'var(--accent)' : 'var(--glass-bg)';
    tab.style.color      = i===n ? '#fff' : 'var(--text-secondary)';
  });
}

function renderAssignSinistres() {
  const q   = document.getElementById('assignSinistreSearch').value.toLowerCase();
  const list = _assignSinistres.filter(s =>
    !q ||
    String(s.id_sinistre).includes(q) ||
    (s.type||'').toLowerCase().includes(q) ||
    (s.client_nom||'').toLowerCase().includes(q) ||
    (s.numero_contrat||'').toLowerCase().includes(q)
  );
  const container = document.getElementById('assignSinistreList');
  if (!list.length) {
    container.innerHTML = '<div style="text-align:center;padding:30px;color:var(--text-secondary);">Aucun sinistre en attente non assigné trouvé.</div>';
    return;
  }
  container.innerHTML = list.map(s => `
    <div onclick="selectAssignSinistre(${s.id_sinistre}, '${escAssign(s.type)} #${s.id_sinistre} — ${escAssign(s.client_nom)}')"
      style="padding:12px 16px;border-radius:10px;border:1px solid var(--glass-border);background:var(--glass-bg);cursor:pointer;transition:.15s;"
      onmouseenter="this.style.borderColor='var(--accent)'" onmouseleave="this.style.borderColor='var(--glass-border)'">
      <div style="display:flex;align-items:center;gap:12px;">
        <div style="width:36px;height:36px;border-radius:8px;background:var(--accent-glow);display:flex;align-items:center;justify-content:center;color:var(--accent);font-size:16px;flex-shrink:0;">
          <i class="bi bi-shield-exclamation"></i>
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-weight:700;color:#fff;font-size:13px;">${escAssign(s.type)} <span style="color:var(--text-secondary);font-weight:400;">#${s.id_sinistre}</span></div>
          <div style="font-size:11px;color:var(--text-secondary);margin-top:2px;">${escAssign(s.client_nom)} · Contrat ${escAssign(s.numero_contrat)} · ${escAssign(s.date_declaration||'').slice(0,10)}</div>
        </div>
        <i class="bi bi-chevron-right" style="color:var(--text-secondary);"></i>
      </div>
    </div>`
  ).join('');
}

function filterAssignSinistres() { renderAssignSinistres(); }

function selectAssignSinistre(id, label) {
  _assignSinistreId    = id;
  _assignSinistreLabel = label;
  document.getElementById('assignSelectedSinistre').textContent = label;
  renderAssignAgents();
  assignGoStep(2);
}

function renderAssignAgents() {
  const container = document.getElementById('assignAgentList');
  if (!_assignAgents.length) {
    container.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:30px;color:var(--text-secondary);">Aucun agent disponible.</div>';
    return;
  }
  container.innerHTML = _assignAgents.map(a => `
    <div onclick="selectAssignAgent(${a.id_user}, '${escAssign(a.prenom)} ${escAssign(a.nom)}')"
      style="padding:14px;border-radius:10px;border:1px solid var(--glass-border);background:var(--glass-bg);cursor:pointer;text-align:center;transition:.15s;"
      onmouseenter="this.style.borderColor='var(--accent)'" onmouseleave="this.style.borderColor='var(--glass-border)'">
      <div style="width:42px;height:42px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;margin:0 auto 8px;">
        ${escAssign(a.prenom).charAt(0).toUpperCase()}${escAssign(a.nom).charAt(0).toUpperCase()}
      </div>
      <div style="font-weight:700;color:#fff;font-size:13px;">${escAssign(a.prenom)} ${escAssign(a.nom)}</div>
    </div>`
  ).join('');
}

function selectAssignAgent(id, label) {
  _assignAgentId    = id;
  _assignAgentLabel = label;
  document.getElementById('assignConfirmText').textContent =
    `Assigner le sinistre « ${_assignSinistreLabel} » à l'agent ${_assignAgentLabel} ?`;
  assignGoStep(3);
}

async function confirmAssignment() {
  const btn = document.getElementById('assignConfirmBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> En cours…';
  try {
    const fd = new FormData();
    fd.append('id_sinistre', _assignSinistreId);
    fd.append('id_agent',    _assignAgentId);
    const res  = await fetch('sinistre_assign.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
      document.querySelectorAll('#assignStep1,#assignStep2,#assignStep3').forEach(el => el.style.display = 'none');
      document.getElementById('assignSuccessMsg').style.display = '';
    } else {
      alert('Erreur : ' + (json.message || 'Inconnue'));
    }
  } catch(e) {
    alert('Erreur réseau.');
  }
  btn.disabled = false;
  btn.innerHTML = '<i class="bi bi-person-check"></i> Confirmer l\'assignation';
}

function escAssign(str) {
  return String(str||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
}
</script>

<script src="assets/js/main.js"></script>
</body>
</html>
