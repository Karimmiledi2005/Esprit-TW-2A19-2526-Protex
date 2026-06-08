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
  <title>Traitements — Protex Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets_sinistre_traitement/css/variables.css">
    <link rel="stylesheet" href="assets_sinistre_traitement/css/base.css">
    <link rel="stylesheet" href="assets_sinistre_traitement/css/layout.css">
    <link rel="stylesheet" href="assets_sinistre_traitement/css/admin-users.css">

  <!-- ANTIFRAUD STYLES -->
  <style>
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
    /* ── Amount highlight ── */
    .amount-value { font-family:monospace; font-weight:700; color:var(--success); font-size:13px; }
    .amount-empty { color:var(--text-secondary); font-style:italic; }

    /* ── Agent cell ── */
    .agent-cell { display:flex; align-items:center; gap:10px; }
    .agent-avatar {
      width:32px; height:32px; border-radius:50%;
      background:linear-gradient(135deg,var(--accent-dark),#0077b6);
      border:2px solid rgba(0,180,216,0.3);
      display:flex; align-items:center; justify-content:center;
      font-family:var(--font-display); font-weight:700;
      font-size:11px; color:#fff; flex-shrink:0;
    }

    /* ── Toolbar padding ── */
    .toolbar-inner { padding:16px 24px; border-bottom:1px solid var(--glass-border); }

    /* ── Stats 3 cols ── */
    .stats-grid-3 { grid-template-columns:repeat(3,1fr); }

    /* ── Decision text clamp ── */
    .decision-cell {
      max-width:240px; font-size:13px; color:var(--text-secondary);
      display:-webkit-box; -webkit-line-clamp:2; line-clamp:2;
      -webkit-box-orient:vertical; overflow:hidden;
    }
  </style>
  <script src="assets_sinistre_traitement/js/validation.js"></script>
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
        <div class="topbar-title">Historique des traitements</div>
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
          <div class="page-title">Traitements</div>
          <div class="page-breadcrumb">
            <i class="bi bi-house"></i>
            <a href="#">Accueil</a>
            <i class="bi bi-chevron-right" style="font-size:10px;"></i>
            <span>Traitements</span>
          </div>
        </div>
        <button class="btn btn-primary" onclick="openCreateModal()">
          <i class="bi bi-plus-lg"></i> Ajouter un traitement
        </button>
      </div>

      <!-- STATS (4 cols) -->
      <div class="stats-grid">
        <div class="stat-card blue">
          <div class="stat-icon"><i class="bi bi-file-earmark-text"></i></div>
          <div class="stat-value" id="statTotal">0</div>
          <div class="stat-label">Total traitements</div>
          <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> +2 ce mois</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
          <div class="stat-value" id="statMontant">0</div>
          <div class="stat-label">Montant total (DT)</div>
          <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> +15%</div>
        </div>
        <div class="stat-card gold">
          <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
          <div class="stat-value" id="statRembourses">0</div>
          <div class="stat-label">Remboursés</div>
          <div class="stat-trend trend-up"><i class="bi bi-graph-up"></i> Validés</div>
        </div>
        <div class="stat-card red">
          <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
          <div class="stat-value" id="statRefuses">0</div>
          <div class="stat-label">Refusés</div>
          <div class="stat-trend trend-down"><i class="bi bi-exclamation-triangle"></i> Rejetés</div>
        </div>
      </div>

      <!-- TABLE CARD -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <i class="bi bi-table"></i> Liste des traitements
          </div>
          <button class="btn btn-outline btn-sm" onclick="exportCSV()">
            <i class="bi bi-download"></i> Exporter
          </button>
        </div>

        <!-- TOOLBAR -->
        <div class="toolbar-inner">
          <div class="toolbar" style="margin-bottom:0;">
            <div class="search-box">
              <i class="bi bi-search"></i>
              <input type="text" id="searchInput" placeholder="Rechercher par décision, sinistre, agent...">
            </div>
            <select class="filter-select" id="filterDecision">
              <option value="">Toutes les décisions</option>
              <option value="en_attente">En attente</option>
              <option value="refuse">Refusé</option>
              <option value="rembourse">Remboursé</option>
            </select>
            <select class="filter-select" id="filterMontant">
              <option value="">Tout montant</option>
              <option value="avec">Avec indemnité</option>
              <option value="sans">Sans indemnité</option>
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
                <th class="sortable" data-sort="id" onclick="toggleSort('id')">ID</th>
                <th class="sortable" data-sort="sinistre" onclick="toggleSort('sinistre')">Sinistre</th>
                <th class="sortable" data-sort="date" onclick="toggleSort('date')">Date</th>
                <th class="sortable" data-sort="agent" onclick="toggleSort('agent')">Agent</th>
                <th class="sortable" data-sort="decision" onclick="toggleSort('decision')">Décision</th>
                <th class="sortable" data-sort="montant" onclick="toggleSort('montant')">Montant indemnisé</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="traitBody"></tbody>
          </table>
          <div id="emptyState" style="display:none;text-align:center;padding:48px 20px;color:var(--text-secondary);">
            <i class="bi bi-inbox" style="font-size:36px;display:block;margin-bottom:10px;opacity:0.3;"></i>
            <p style="font-size:14px;">Aucun traitement trouvé</p>
          </div>
        </div>

        <div class="pagination">
          <div class="pagination-info" id="paginationInfo"></div>
          <div class="pagination-btns" id="paginationBtns"></div>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- ===== MODAL AJOUTER / MODIFIER ===== -->
<div class="modal-overlay" id="modalForm">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modalFormTitle">
        <i class="bi bi-plus-circle"></i> Ajouter un traitement
      </div>
      <button class="modal-close" onclick="closeModal('modalForm')"><i class="bi bi-x"></i></button>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="fSinistre">ID Sinistre *</label>
        <input type="number" class="form-control" id="fSinistre" placeholder="Ex: 3" min="1"
               onblur="validateSinistreId()" oninput="document.getElementById('sinistrePreview').style.display='none';">
        <div class="form-error" id="errSinistre">Champ requis</div>
        <div id="sinistrePreview" style="display:none;"></div>
      </div>
      <div class="form-group">
        <label for="fDate">Date *</label>
        <input type="date" class="form-control" id="fDate" readonly style="background:rgba(255,255,255,0.05);cursor:not-allowed;opacity:0.7;">
        <div class="form-error" id="errDate">Champ requis</div>
      </div>
    </div>

    <div class="form-group">
      <label for="fAgent">Agent responsable *</label>
      <select class="form-control" id="fAgent">
        <option value="">— Sélectionner un agent —</option>
      </select>
      <div class="form-error" id="errAgent">Champ requis</div>
    </div>

    <div class="form-group">
      <label for="fDecision">Décision *</label>
      <select class="form-control" id="fDecision"
              onchange="document.getElementById('fMontant').required = this.value==='rembourse';">
        <option value="">— Choisir une décision —</option>
        <option value="en_attente">En attente</option>
        <option value="refuse">Refusé</option>
        <option value="rembourse">Remboursé</option>
      </select>
      <div class="form-error" id="errDecision">Champ requis</div>
    </div>

    <div class="form-group">
      <label for="fMessage">Motif / Message (visible par le client)</label>
      <textarea class="form-control" id="fMessage" rows="2" placeholder="Ex: Remboursement validé suite au rapport d'expertise..."></textarea>
    </div>

    <div class="form-group">
      <label for="fMontant">Montant indemnisé (DT) <span style="color:var(--text-secondary);font-size:11px;">— obligatoire si Remboursé</span></label>
      <input type="number" class="form-control" id="fMontant" placeholder="Ex: 1500" min="0">
      <div class="form-error" id="errMontant">Champ requis</div>

      <label for="fStatut" style="margin-top:12px;">Statut du traitement</label>
      <select class="form-control" id="fStatut">
        <option value="">— Choisir un statut —</option>
        <option value="en_cours">En cours</option>
        <option value="accepte">Accepté (→ sinistre remboursé)</option>
        <option value="refuse">Refusé (→ sinistre refusé)</option>
      </select>
      <div class="form-error" id="errStatut">Champ requis</div>
    </div>

    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalForm')">Annuler</button>
      <button class="btn btn-primary" id="btnSave" onclick="saveTraitement()">
        <i class="bi bi-save"></i> Enregistrer
      </button>
    </div>
  </div>
</div>

<!-- ===== MODAL SUPPRIMER ===== -->
<div class="modal-overlay delete-modal" id="modalDelete">
  <div class="modal">
    <div class="delete-icon"><i class="bi bi-trash3"></i></div>
    <div class="delete-title">Supprimer ce traitement ?</div>
    <div class="delete-msg" id="deleteMsg">⚠️ Réservé à l'Admin. Cette action est irréversible.</div>
    <div class="modal-footer" style="justify-content:center;margin-top:28px;">
      <button class="btn btn-outline" onclick="closeModal('modalDelete')">Annuler</button>
      <button class="btn btn-danger" onclick="confirmDelete()">
        <i class="bi bi-trash3"></i> Supprimer définitivement
      </button>
    </div>
  </div>
</div>

<!-- ===== MODAL VOIR SINISTRE ===== -->
<div class="modal-overlay" id="modalView">
  <div class="modal" style="max-width:600px;">
    <div class="modal-header">
      <div class="modal-title" id="modalViewTitle">
        <i class="bi bi-eye"></i> Détails du sinistre
      </div>
      <button class="modal-close" onclick="closeModal('modalView')"><i class="bi bi-x"></i></button>
    </div>

    <div style="padding:20px;color:var(--text-secondary);" id="viewContent">
      <div style="text-align:center;padding:40px;">
        <i class="bi bi-hourglass" style="font-size:32px;opacity:0.5;display:block;margin-bottom:10px;"></i>
        <p>Chargement...</p>
      </div>
    </div>

    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalView')">Fermer</button>
    </div>
  </div>
</div>

<script src="assets_sinistre_traitement/js/traitement.js?v=3"></script>

<script src="assets_sinistre_traitement/js/main.js"></script>
</body>
</html>


