<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}

// RC2: Load response templates
$db = config::getConnexion();
$templates = [];
try {
    $stmt = $db->prepare("SELECT id, titre, contenu, categorie FROM reponse_template ORDER BY categorie, titre");
    $stmt->execute();
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('reponse.php: erreur chargement templates: ' . $e->getMessage());
}
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Réclamations — Protex Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/variables.css">
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/layout.css">
  <link rel="stylesheet" href="assets/css/admin-users.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* ── Stats 4 cols ── */
    /* ── Stats grid responsive ── */
    .stats-grid { grid-template-columns: repeat(4, 1fr); }
    @media (max-width: 1200px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .stats-grid { grid-template-columns: 1fr; }
    }

    /* ── Toolbar & Search ── */
    .toolbar-inner {
        padding: 16px 24px;
        border-bottom: 1px solid var(--glass-border);
    }
    .toolbar-inner .toolbar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 0;
    }
    .toolbar-inner .search-box {
        flex: 1 1 220px;
        min-width: 180px;
    }
    .toolbar-inner .filter-select {
        flex: 0 1 160px;
        min-width: 130px;
    }

    /* ── Client cell ── */
    .client-cell   { display:flex; align-items:center; gap:10px; }
    .client-avatar { width:36px; height:36px; border-radius:50%;
                     background:linear-gradient(135deg,var(--accent),var(--accent-dark));
                     display:flex; align-items:center; justify-content:center;
                     font-size:12px; font-weight:800; color:#0b1628; flex-shrink:0; }
    .client-name   { 
        font-size: 13px; 
        font-weight: 600; 
        max-width: 160px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .client-ref    { font-size:11px; color:var(--text-secondary); }
    .desc-objet    { font-size:13px; font-weight:600; margin-bottom:2px; }
    .desc-text     { max-width:200px; white-space:nowrap; overflow:hidden;
                     text-overflow:ellipsis; color:var(--text-secondary); font-size:12px; }

    /* ── Action buttons ── */
    .action-btns { display:flex; align-items:center; gap:5px; }
    .act-btn { width:36px; height:36px; border-radius:8px;
               background:var(--glass-bg); border:1px solid var(--glass-border);
               display:inline-flex; align-items:center; justify-content:center;
               font-size:14px; color:var(--text-secondary); cursor:pointer;
               transition:all .2s; text-decoration:none; }
    .act-btn:hover            { background:rgba(255,255,255,.07); color:#fff; }
    .act-btn.reply:hover      { border-color:var(--accent); color:var(--accent); }
    .act-btn.view-btn:hover   { border-color:#60a5fa; color:#60a5fa; }
    .act-btn.edit-btn:hover   { border-color:#f59e0b; color:#f59e0b; }
    .act-btn.reject-btn:hover { border-color:var(--danger); color:var(--danger); }
    .act-btn.del-btn:hover    { border-color:var(--danger); color:var(--danger); background:rgba(239,68,68,.08); }
    .act-btn.disabled-btn     { opacity:.3; cursor:not-allowed; pointer-events:none; }

    @media (max-width: 768px) {
        .act-btn { width: 44px; height: 44px; font-size: 16px; }
    }

    .card {
        max-width: 100%;
        overflow: hidden;
    }
    .table-wrap {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 5px;
    }
    .table-wrap::-webkit-scrollbar { height: 8px; }
    .table-wrap::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); border-radius: 4px; }
    .table-wrap::-webkit-scrollbar-thumb { background: var(--accent); border-radius: 4px; }
    table { width: 100%; min-width: 1150px; border-collapse: collapse; table-layout: fixed; }
    table thead th:nth-child(1) { width: 180px; }   /* CLIENT */
    table thead th:nth-child(2) { min-width: 150px; } /* OBJET/DESC */
    table thead th:nth-child(3) { width: 90px; }    /* TYPE */
    table thead th:nth-child(4) { width: 90px; }    /* PRIORITÉ */
    table thead th:nth-child(5) { width: 90px; }    /* STATUT */
    table thead th:nth-child(6) { width: 90px; }    /* DATE */
    /* Les colonnes Agence / Agent s'intercalent ici via JS */
    table thead th.col-agence { width: 120px; }
    table thead th.col-agent  { width: 130px; }
    table thead th:last-child  { width: 130px; }    /* ACTIONS */


    /* ── Modal info block ── */
    .rec-info-block { background:var(--glass-bg); border:1px solid var(--glass-border);
                      border-radius:11px; padding:14px 18px; margin-bottom:16px; }
    .rec-info-row   { display:flex; gap:12px; padding:7px 0; border-bottom:1px solid var(--glass-border); }
    .rec-info-row:last-child { border-bottom:none; }
    .rec-info-label { font-size:11px; font-weight:700; color:var(--text-secondary);
                      text-transform:uppercase; letter-spacing:.5px; min-width:90px; padding-top:1px; }
    .rec-info-val   { font-size:13px; color:var(--text-primary); flex:1; }

    /* ── Reject warning ── */
    .reject-warning { padding:12px 16px; background:rgba(239,68,68,.08);
                      border:1px solid rgba(239,68,68,.2); border-radius:10px;
                      display:flex; gap:10px; align-items:flex-start;
                      font-size:12.5px; color:rgba(239,68,68,.9); margin-bottom:16px; }
    .reject-warning i { font-size:16px; flex-shrink:0; margin-top:1px; }

    /* ── Textarea dans modal ── */
    .modal-textarea { width:100%; padding:12px 14px; background:var(--glass-bg);
                      border:1px solid var(--glass-border); border-radius:10px;
                      color:var(--text-primary); font-size:13.5px; resize:vertical;
                      min-height:110px; outline:none; font-family:inherit; transition:border .2s; }
    .modal-textarea:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(0,180,216,.10); }
    .modal-textarea.reject-area:focus { border-color:var(--danger); box-shadow:0 0 0 3px rgba(239,68,68,.10); }

    /* ── Modal head icon ── */
    .modal-head-icon { width:40px; height:40px; border-radius:10px;
                       background:rgba(0,180,216,.12); color:var(--accent);
                       display:flex; align-items:center; justify-content:center; font-size:18px; }

    /* ── Boutons d'action modal ── */
    .btn-send { padding:10px 22px; border-radius:9px;
                background:linear-gradient(135deg,var(--accent),var(--accent-dark));
                border:none; color:#0b1628; font-size:13.5px; font-weight:700;
                cursor:pointer; display:inline-flex; align-items:center; gap:8px; transition:all .2s; }
    .btn-send:hover { opacity:.9; transform:translateY(-1px); }

    .btn-reject-confirm { padding:10px 22px; border-radius:9px;
                background:rgba(239,68,68,.15); border:1px solid rgba(239,68,68,.3);
                color:var(--danger); font-size:13.5px; font-weight:700;
                cursor:pointer; display:inline-flex; align-items:center; gap:8px; transition:all .2s; }
    .btn-reject-confirm:hover { background:rgba(239,68,68,.25); transform:translateY(-1px); }

    /* ── Toast ── */
    #toastBox { position:fixed; top:20px; right:24px; z-index:9999; }
    .toast-msg { display:flex; align-items:center; gap:10px; padding:14px 20px;
                 border-radius:12px; font-size:13.5px; font-weight:600;
                 box-shadow:0 10px 30px rgba(0,0,0,.4);
                 animation:toastIn .3s ease; margin-bottom:8px; }
    @keyframes toastIn { from{opacity:0;transform:translateX(60px)} to{opacity:1;transform:translateX(0)} }
    .toast-ok  { background:rgba(34,197,94,.15); border:1px solid rgba(34,197,94,.3); color:#22c55e; }

    /* ── Sort button ── */
    .btn-sort {
      display:inline-flex; align-items:center; gap:7px;
      padding:8px 16px; border-radius:9px;
      background:rgba(0,180,216,.12); border:1px solid rgba(0,180,216,.3);
      color:var(--accent); font-size:13px; font-weight:700;
      cursor:pointer; transition:all .2s;
    }
    .btn-sort:hover { background:rgba(0,180,216,.22); transform:translateY(-1px); }
    .btn-sort.active {
      background:rgba(0,180,216,.25); border-color:var(--accent);
      box-shadow:0 0 0 2px rgba(0,180,216,.15);
    }
    .toast-err { background:rgba(239,68,68,.15); border:1px solid rgba(239,68,68,.3); color:#ef4444; }

    /* ── SLA Indicators ── */
    .sla-warn { border-left: 3px solid var(--warning) !important; }
    .sla-late { border-left: 3px solid var(--danger) !important; }
    .sla-badge {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 10.5px; font-weight: 600; padding: 2px 7px;
        border-radius: 20px; margin-top: 4px;
    }
    .sla-badge.warn { background: rgba(234,179,8,.12); color: var(--warning); }
    .sla-badge.late { background: rgba(230,57,70,.12);  color: var(--danger); }

    /* ── Kanban View ── */
    .kanban-board { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; padding: 16px 24px; }
    .kanban-col   { background: var(--glass-bg); border: 1px solid var(--glass-border);
                    border-radius: 12px; padding: 12px; min-height: 200px; }
    .kanban-head  { font-size: 11px; font-weight: 700; text-transform: uppercase;
                    letter-spacing: .5px; color: var(--text-secondary); margin-bottom: 10px;
                    display: flex; align-items: center; justify-content: space-between; }
    .kanban-card  { background: rgba(255,255,255,.04); border: 1px solid var(--glass-border);
                    border-radius: 9px; padding: 10px 12px; margin-bottom: 8px; font-size: 12.5px; cursor: pointer;
                    transition: transform .2s, background .2s; }
    .kanban-card:hover { background: rgba(255,255,255,.07); transform: translateY(-2px); }
    .kanban-count { background: var(--glass-border); padding: 2px 8px; border-radius: 10px; font-size: 10px; }

    /* ── Pagination ── */
    .pagination-bar { padding: 16px 24px; border-top: 1px solid var(--glass-border); }
    .pagination { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
    .pagination-info { font-size: 13px; color: var(--text-secondary); }
    .pagination-btns { display: flex; gap: 5px; }
    .page-btn {
        width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
        background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 6px;
        color: var(--text-secondary); cursor: pointer; transition: all .2s; font-size: 13px;
    }
    .page-btn:hover { background: var(--glass-hover); color: #fff; }
    .page-btn.active { background: var(--accent); color: #0b1628; border-color: var(--accent); font-weight: 700; }
    .page-btn:disabled { opacity: 0.3; cursor: not-allowed; }

    /* ── AI Suggestions ── */
    .suggestion-item {
        background: var(--glass-bg); border: 1px solid var(--glass-border);
        border-radius: 9px; padding: 9px 12px; font-size: 12.5px; cursor: pointer;
        transition: all .2s; margin-bottom: 7px; border-left: 3px solid transparent;
    }
    .suggestion-item:hover { background: var(--glass-hover); border-left-color: var(--accent); }
    
    /* ── Descriptions ── */
    .btn-toggle-desc {
        font-size: 11px; background: none; border: none; color: var(--accent);
        cursor: pointer; padding: 0; margin-left: 4px; font-weight: 600;
    }
    .btn-toggle-desc:hover { text-decoration: underline; }

    /* ── Bouton traduction ── */
    .btn-translate {
      display:inline-flex; align-items:center; gap:5px;
      padding:4px 11px; border-radius:20px; font-size:11.5px; font-weight:700;
      background:rgba(0,180,216,.08); border:1px solid rgba(0,180,216,.25);
      color:var(--accent); cursor:pointer; transition:all .2s; white-space:nowrap;
    }
    .btn-translate:hover   { background:rgba(0,180,216,.18); transform:translateY(-1px); }
    .btn-translate:disabled { opacity:.5; cursor:wait; }
    .translate-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; }
    .translate-row label { font-size:11px; font-weight:700; color:var(--text-secondary);
                           text-transform:uppercase; letter-spacing:.5px; }
    @keyframes spin { to { transform:rotate(360deg); } }

    /* ── Bouton statistiques ── */
    .btn-stats { padding:8px 18px; border-radius:9px;
                 background:linear-gradient(135deg,#7c3aed,#6d28d9);
                 border:none; color:#fff; font-size:13px; font-weight:700;
                 cursor:pointer; display:inline-flex; align-items:center; gap:7px;
                 transition:all .2s; box-shadow:0 4px 15px rgba(124,58,237,.25); }
    .btn-stats:hover { opacity:.9; transform:translateY(-1px); box-shadow:0 6px 20px rgba(124,58,237,.35); }

    /* ── Modal statistiques ── */
    #modalStats .modal { max-width:560px; }
    .stats-modal-head-icon { width:40px; height:40px; border-radius:10px;
                             background:rgba(124,58,237,.15); color:#a78bfa;
                             display:flex; align-items:center; justify-content:center; font-size:18px; }
    .chart-container { display:flex; flex-direction:column; align-items:center; padding:10px 0 20px; }
    .donut-wrap { position:relative; width:220px; height:220px; margin:0 auto 24px; }
    .donut-wrap svg { width:100%; height:100%; }
    .donut-center { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
                    text-align:center; pointer-events:none; }
    .donut-center-val { font-size:28px; font-weight:800; color:var(--text-primary); line-height:1; }
    .donut-center-lbl { font-size:11px; color:var(--text-secondary); margin-top:4px; text-transform:uppercase; letter-spacing:.5px; }
    .legend-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; width:100%; max-width:420px; }
    .legend-item { display:flex; align-items:center; gap:10px; padding:12px 14px;
                   background:var(--glass-bg); border:1px solid var(--glass-border);
                   border-radius:10px; transition:background .2s; cursor:default; }
    .legend-item:hover { background:var(--glass-hover); }
    .legend-dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; }
    .legend-info { flex:1; min-width:0; }
    .legend-type { font-size:12.5px; font-weight:700; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .legend-pct  { font-size:11px; color:var(--text-secondary); margin-top:1px; }
    .legend-count { font-size:15px; font-weight:800; color:var(--text-primary); margin-left:auto; padding-left:8px; }
    .no-data-stats { text-align:center; padding:48px 20px; color:var(--text-secondary); font-size:14px; }
    .stats-loading { display:flex; align-items:center; justify-content:center; gap:10px;
                     padding:48px 20px; color:var(--text-secondary); font-size:13px; }
    @keyframes spin { to { transform:rotate(360deg); } }
    .spin-icon { animation:spin 1s linear infinite; display:inline-block; }
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
        <div class="topbar-title">Gestion des réclamations</div>
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
          <div class="page-title">Réclamations</div>
          <div class="page-breadcrumb">
            <i class="bi bi-house"></i>
            <a href="#">Accueil</a>
            <i class="bi bi-chevron-right" style="font-size:10px;"></i>
            <span>Réclamations</span>
          </div>
        </div>
      </div>

      <!-- STATS -->
      <div class="stats-grid">
        <div class="stat-card blue">
          <div class="stat-icon"><i class="bi bi-chat-left-text"></i></div>
          <div class="stat-value" id="statTotal">—</div>
          <div class="stat-label">Total réclamations</div>
          <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> Toutes périodes</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
          <div class="stat-value" id="statAnswered">—</div>
          <div class="stat-label">Répondues</div>
          <div class="stat-trend trend-up"><i class="bi bi-check2-circle"></i> Statut fermé</div>
        </div>
        <div class="stat-card gold">
          <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
          <div class="stat-value" id="statPending">—</div>
          <div class="stat-label">En attente</div>
          <div class="stat-trend trend-warn" id="statUrgent"><i class="bi bi-exclamation-circle"></i> — urgente(s)</div>
        </div>
        <div class="stat-card red">
          <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
          <div class="stat-value" id="statRejected">—</div>
          <div class="stat-label">Rejetées</div>
          <div class="stat-trend trend-down"><i class="bi bi-dash-circle"></i> Non traitées</div>
        </div>
      </div>

      <!-- TABLE CARD -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <i class="bi bi-table"></i> Liste des réclamations
          </div>
        </div>

        <div class="toolbar-inner">
          <div class="toolbar" style="margin-bottom:0;">
            <div class="search-box">
              <i class="bi bi-search"></i>
              <input type="text" id="searchInput" placeholder="Rechercher par client, objet, référence..." oninput="filterTable()">
            </div>
            <select class="filter-select" id="filterType" onchange="filterTable()">
              <option value="">Tous les types</option>
              <option value="Santé">Santé</option>
              <option value="Auto">Auto</option>
              <option value="Habitation">Habitation</option>
              <option value="Autre">Autre</option>
            </select>
            <select class="filter-select" id="filterStatut" onchange="filterTable()">
              <option value="">Tous les statuts</option>
              <option value="open">En attente</option>
              <option value="closed">Résolue</option>
              <option value="rejected">Rejetée</option>
            </select>
            <select class="filter-select" id="filterPriorite" onchange="filterTable()">
              <option value="">Toutes priorités</option>
              <option value="Faible">Faible</option>
              <option value="Normale">Normale</option>
              <option value="Urgente">Urgente</option>
            </select>
            <div style="display:flex; gap:5px; align-items:center;">
              <input type="date" id="filterDateFrom" class="filter-select" title="Date début" onchange="filterTable()" style="padding: 5px 10px; height: 38px;">
              <span style="color:var(--text-secondary); font-size:12px;">à</span>
              <input type="date" id="filterDateTo"   class="filter-select" title="Date fin" onchange="filterTable()" style="padding: 5px 10px; height: 38px;">
            </div>
            <button class="btn btn-outline btn-sm" onclick="resetFilters()">
              <i class="bi bi-x-circle"></i> Réinitialiser
            </button>
            <button class="btn-sort" id="btnSort" onclick="toggleSort()">
              <i class="bi bi-sort-alpha-down" id="sortIcon"></i>
              <span id="sortLabel">Trier A→Z</span>
            </button>
            <button class="btn btn-outline btn-sm" id="btnViewToggle" onclick="toggleView()">
              <i class="bi bi-kanban" id="viewIcon"></i> <span id="viewLabel">Vue Kanban</span>
            </button>
            <a href="export_reclamations.php" class="btn btn-outline btn-sm" target="_blank">
              <i class="bi bi-download"></i> Export CSV
            </a>
            <button class="btn-stats" onclick="openStatsModal()">
              <i class="bi bi-pie-chart-fill"></i> Statistiques
            </button>
          </div>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>CLIENT</th>
                <th>OBJET / DESCRIPTION</th>
                <th>TYPE</th>
                <th>PRIORITÉ</th>
                <th>STATUT</th>
                <th>DATE</th>
                <th>RÉPONSE</th>
                <th>ACTIONS</th>
              </tr>
            </thead>
            <tbody id="recBody">
              <tr><td colspan="10" style="text-align:center;padding:40px;color:var(--text-secondary);">
                <i class="bi bi-hourglass" style="font-size:24px;display:block;margin-bottom:8px;opacity:0.4;"></i>
                Chargement...
              </td></tr>
            </tbody>
          </table>
          <div id="emptyState" style="display:none;text-align:center;padding:48px 20px;color:var(--text-secondary);">
            <i class="bi bi-inbox" style="font-size:36px;display:block;margin-bottom:10px;opacity:0.3;"></i>
            <p style="font-size:14px;">Aucune réclamation trouvée</p>
          </div>
        </div>

        <!-- KANBAN BOARD -->
        <div class="kanban-board" id="kanbanBoard" style="display:none;">
          <div class="kanban-col">
            <div class="kanban-head"><span>En attente</span><span class="kanban-count" id="countOpen">0</span></div>
            <div id="colOpen"></div>
          </div>
          <div class="kanban-col">
            <div class="kanban-head"><span>Résolues</span><span class="kanban-count" id="countClosed">0</span></div>
            <div id="colClosed"></div>
          </div>
          <div class="kanban-col">
            <div class="kanban-head"><span>Rejetées</span><span class="kanban-count" id="countRejected">0</span></div>
            <div id="colRejected"></div>
          </div>
        </div>

        <!-- PAGINATION -->
        <div class="pagination-bar" id="paginationBar"></div>
      </div>

    </div><!-- /content -->
  </main>
</div><!-- /layout -->

<!-- ══ MODAL — STATISTIQUES PAR TYPE ══ -->
<div class="modal-overlay" id="modalStats" onclick="handleOverlayClick(event,'modalStats')">
  <div class="modal" style="max-width:560px;" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div class="modal-title">
        <div class="stats-modal-head-icon"><i class="bi bi-pie-chart-fill"></i></div>
        Répartition des réclamations par type
      </div>
      <button class="modal-close" onclick="closeModal('modalStats')"><i class="bi bi-x"></i></button>
    </div>

    <!-- Contenu dynamique injecté ici -->
    <div id="statsModalBody">
      <div class="donut-wrap">
        <canvas id="typeChart"></canvas>
        <div class="donut-center">
          <div class="donut-center-val" id="donutTotal">—</div>
          <div class="donut-center-lbl">Total</div>
        </div>
      </div>
      <div id="statsLegend" class="legend-grid" style="margin-left:auto; margin-right:auto;"></div>
    </div>

    <div class="modal-footer" style="justify-content:flex-end;">
      <button class="btn btn-outline" onclick="closeModal('modalStats')">Fermer</button>
    </div>
  </div>
</div>

<!-- ══ TOAST ══ -->
<div id="toastBox"></div>

<!-- ══ MODAL — RÉPONDRE ══ -->
<div class="modal-overlay" id="modalRepondre">
  <div class="modal" style="max-width:800px;">
    <div class="modal-header">
      <div class="modal-title">
        <div class="modal-head-icon"><i class="bi bi-reply-fill"></i></div>
        Répondre à la réclamation
      </div>
      <button class="modal-close" onclick="closeModal('modalRepondre')"><i class="bi bi-x"></i></button>
    </div>
    <div style="display:flex; gap:16px; padding:0 24px 16px;">
      <!-- Left column: main form -->
      <div style="flex:1; min-width:0;">
        <div class="rec-info-block">
          <div class="rec-info-row"><span class="rec-info-label">Client</span><span class="rec-info-val" id="mClient">—</span></div>
          <div class="rec-info-row"><span class="rec-info-label">Objet</span><span class="rec-info-val" id="mObjet">—</span></div>
          <div class="rec-info-row" style="flex-direction:column;gap:6px;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
              <span class="rec-info-label">Description</span>
              <button class="btn-translate" id="btnTranslateMDesc" title="Translate to English"><i class="bi bi-translate"></i> EN</button>
            </div>
            <span class="rec-info-val" id="mDesc">—</span>
          </div>
        </div>
        <div class="form-group">
          <div class="translate-row">
            <label>VOTRE RÉPONSE *</label>
            <button class="btn-translate" id="btnTranslateMContenu" title="Translate to English"><i class="bi bi-translate"></i> EN</button>
          </div>
          <textarea class="modal-textarea" id="mContenu" placeholder="Rédigez votre réponse au client..." rows="4"></textarea>
          <div class="form-error" id="errMContenu">Ce champ est requis.</div>
          <div class="char-counter" id="charCountMContenu"></div>
        </div>
      </div>
      <!-- Right column: template sidebar -->
      <div style="width:280px; flex-shrink:0; border-left:1px solid var(--glass-border); padding-left:16px;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
          <div style="font-size:11px; font-weight:700; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.5px; display:flex; align-items:center; gap:5px;">
            <i class="bi bi-file-text" style="color:var(--accent);"></i> Modèles
          </div>
          <a href="gestion_templates.php" target="_blank" style="font-size:11px; color:var(--accent); text-decoration:none;">
            <i class="bi bi-gear"></i> Gérer
          </a>
        </div>
        <div id="templateList" style="max-height:460px; overflow-y:auto;">
          <?php
          $catLabels = ['accusé' => 'Accusé', 'refus' => 'Refus', 'complement' => 'Compléments', 'resolution' => 'Résolution', 'autre' => 'Autre'];
          $grouped = [];
          foreach ($templates as $t) {
              $cat = $t['categorie'] ?: 'autre';
              $grouped[$cat][] = $t;
          }
          foreach (['accusé','refus','complement','resolution','autre'] as $cat):
              if (empty($grouped[$cat])) continue;
          ?>
            <div style="font-size:10px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.5px; margin:10px 0 4px;">
              <?php echo $catLabels[$cat] ?? $cat; ?>
            </div>
            <?php foreach ($grouped[$cat] as $t): ?>
              <div class="suggestion-item template-item" data-contenu="<?php echo htmlspecialchars($t['contenu'], ENT_QUOTES, 'UTF-8'); ?>" onclick="useTemplate(this)" style="font-size:12px; padding:7px 10px;">
                <strong><?php echo htmlspecialchars($t['titre']); ?></strong>
              </div>
            <?php endforeach; ?>
          <?php endforeach; ?>
          <?php if (empty($templates)): ?>
            <div style="color:var(--text-secondary); font-size:12px; text-align:center; padding:16px 0;">
              Aucun modèle. <a href="gestion_templates.php" target="_blank" style="color:var(--accent);">Créer</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div style="padding:0 24px 16px;">
      <!-- AI SUGGESTIONS -->
      <div id="suggestionsBox" style="margin-top:0; display:none;">
        <div style="font-size:11px; font-weight:700; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px; display:flex; align-items:center; gap:5px;">
          <i class="bi bi-stars" style="color:var(--accent);"></i> Suggestions IA
        </div>
        <div id="suggestionsList"></div>
      </div>
      <button type="button" id="btnSuggest" class="btn btn-outline btn-sm" style="margin-top:10px; width:100%; justify-content:center;" onclick="loadAI()">
        <i class="bi bi-stars"></i> Suggérer une réponse (IA)
      </button>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalRepondre')">Annuler</button>
      <button class="btn-send" onclick="submitRepondre()"><i class="bi bi-send"></i> Envoyer</button>
    </div>
  </div>
</div>

<!-- ══ MODAL — MODIFIER ══ -->
<div class="modal-overlay" id="modalModifier">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">
        <div class="modal-head-icon" style="background:rgba(245,158,11,.12);color:#f59e0b;"><i class="bi bi-pencil-fill"></i></div>
        Modifier la réponse
      </div>
      <button class="modal-close" onclick="closeModal('modalModifier')"><i class="bi bi-x"></i></button>
    </div>
    <div class="rec-info-block">
      <div class="rec-info-row"><span class="rec-info-label">Client</span><span class="rec-info-val" id="modClient">—</span></div>
      <div class="rec-info-row"><span class="rec-info-label">Objet</span><span class="rec-info-val" id="modObjet">—</span></div>
    </div>
    <div class="form-group">
      <div class="translate-row">
        <label>NOUVELLE RÉPONSE *</label>
        <button class="btn-translate" id="btnTranslateModContenu" title="Translate to English"><i class="bi bi-translate"></i> EN</button>
      </div>
      <textarea class="modal-textarea" id="modContenu" rows="4"></textarea>
      <div class="form-error" id="errModContenu">Ce champ est requis.</div>
      <div class="char-counter" id="charCountModContenu"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalModifier')">Annuler</button>
      <button class="btn-send" onclick="submitModifier()"><i class="bi bi-check-lg"></i> Enregistrer</button>
    </div>
  </div>
</div>

<!-- ══ MODAL — SUPPRIMER RÉPONSE ══ -->
<div class="modal-overlay delete-modal" id="modalSupprimer">
  <div class="modal">
    <div class="delete-icon"><i class="bi bi-trash3"></i></div>
    <div class="delete-title">Supprimer cette réponse ?</div>
    <div class="delete-msg" style="margin-bottom:16px;">
      ⚠️ La réponse sera supprimée définitivement et la réclamation repassera <strong>En cours</strong>. Action irréversible.
    </div>
    <div class="rec-info-block">
      <div class="rec-info-row"><span class="rec-info-label">Client</span><span class="rec-info-val" id="supClient">—</span></div>
      <div class="rec-info-row"><span class="rec-info-label">Objet</span><span class="rec-info-val" id="supObjet">—</span></div>
      <div class="rec-info-row"><span class="rec-info-label">Réponse</span><span class="rec-info-val" id="supContenu">—</span></div>
    </div>
    <div class="modal-footer" style="justify-content:center;">
      <button class="btn btn-outline" onclick="closeModal('modalSupprimer')">Annuler</button>
      <button class="btn btn-danger" onclick="confirmSupprimer()"><i class="bi bi-trash3"></i> Supprimer définitivement</button>
    </div>
  </div>
</div>

<!-- ══ MODAL — REJETER ══ -->
<div class="modal-overlay" id="modalRejeter">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">
        <div class="modal-head-icon" style="background:rgba(239,68,68,.12);color:var(--danger);"><i class="bi bi-x-circle-fill"></i></div>
        Rejeter la réclamation
      </div>
      <button class="modal-close" onclick="closeModal('modalRejeter')"><i class="bi bi-x"></i></button>
    </div>
    <div class="reject-warning">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <span>Cette action marquera la réclamation comme <strong>Rejetée</strong>. Elle est définitive.</span>
    </div>
    <div class="rec-info-block">
      <div class="rec-info-row"><span class="rec-info-label">Client</span><span class="rec-info-val" id="rejClient">—</span></div>
      <div class="rec-info-row"><span class="rec-info-label">Objet</span><span class="rec-info-val" id="rejObjet">—</span></div>
    </div>
    <div class="form-group">
      <label>MOTIF DU REJET *</label>
      <textarea class="modal-textarea reject-area" id="rejMotif" placeholder="Expliquez la raison du rejet..." rows="3"></textarea>
      <div class="form-error" id="errRejMotif">Ce champ est requis.</div>
      <div class="char-counter" id="charCountRejMotif"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalRejeter')">Annuler</button>
      <button class="btn-reject-confirm" onclick="submitRejeter()"><i class="bi bi-x-circle"></i> Confirmer le rejet</button>
    </div>
  </div>
</div>

<!-- ══ MODAL — VOIR DÉTAILS ══ -->
<div class="modal-overlay" id="modalVoir">
  <div class="modal" style="max-width:540px;">
    <div class="modal-header">
      <div class="modal-title">
        <div class="modal-head-icon" style="background:rgba(59,130,246,.12);color:#60a5fa;"><i class="bi bi-eye"></i></div>
        Détail de la réclamation
      </div>
      <button class="modal-close" onclick="closeModal('modalVoir')"><i class="bi bi-x"></i></button>
    </div>
    <div class="rec-info-block">
      <div class="rec-info-row"><span class="rec-info-label">Référence</span><span class="rec-info-val" id="vRef">—</span></div>
      <div class="rec-info-row"><span class="rec-info-label">Client</span><span class="rec-info-val" id="vClient">—</span></div>
      <div class="rec-info-row"><span class="rec-info-label">Objet</span><span class="rec-info-val" id="vObjet">—</span></div>
      <div class="rec-info-row"><span class="rec-info-label">Type</span><span class="rec-info-val" id="vType">—</span></div>
      <div class="rec-info-row"><span class="rec-info-label">Priorité</span><span class="rec-info-val" id="vPriorite">—</span></div>
      <div class="rec-info-row"><span class="rec-info-label">Date dépôt</span><span class="rec-info-val" id="vDate">—</span></div>
      <div class="rec-info-row" style="flex-direction:column;gap:6px;">
        <div style="display:flex;align-items:center;justify-content:space-between;">
          <span class="rec-info-label">Description</span>
          <button class="btn-translate" id="btnTranslateVDesc" title="Translate to English"><i class="bi bi-translate"></i> EN</button>
        </div>
        <span class="rec-info-val" id="vDesc">—</span>
      </div>
    </div>
    <div id="vReponseBlock" style="display:none;">
      <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--success);
                  display:flex;align-items:center;gap:7px;margin-bottom:8px;">
        <i class="bi bi-check-circle"></i> RÉPONSE ENVOYÉE
      </div>
      <div class="rec-info-block" style="border-color:rgba(34,197,94,.2);background:rgba(34,197,94,.04);">
        <div class="rec-info-row" style="flex-direction:column;gap:6px;">
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <span class="rec-info-label">Réponse</span>
            <button class="btn-translate" id="btnTranslateVReponse" title="Translate to English"><i class="bi bi-translate"></i> EN</button>
          </div>
          <span class="rec-info-val" id="vReponse">—</span>
        </div>
        <div class="rec-info-row"><span class="rec-info-label">Date</span><span class="rec-info-val" id="vDateRep">—</span></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-send" style="padding:10px 28px;" onclick="closeModal('modalVoir')">Fermer</button>
    </div>
  </div>
</div>

<script src="assets/js/reponse-validation.js"></script>
<script src="assets/js/translate.js"></script>
<script>
/* ══ Initialisation des boutons de traduction ══ */
function initTranslateRepondre() {
  var btnDesc   = document.getElementById('btnTranslateMDesc');
  var descEl    = document.getElementById('mDesc');
  var btnContenu = document.getElementById('btnTranslateMContenu');
  var taContenu  = document.getElementById('mContenu');
  if (btnDesc && descEl)       attachTranslateToggle(btnDesc, descEl);
  if (btnContenu && taContenu) attachTranslateTextarea(btnContenu, taContenu);
}
function initTranslateModifier() {
  var btnContenu = document.getElementById('btnTranslateModContenu');
  var taContenu  = document.getElementById('modContenu');
  if (btnContenu && taContenu) attachTranslateTextarea(btnContenu, taContenu);
}
function initTranslateVoir() {
  var btnDesc = document.getElementById('btnTranslateVDesc');
  var descEl  = document.getElementById('vDesc');
  var btnRep  = document.getElementById('btnTranslateVReponse');
  var repEl   = document.getElementById('vReponse');
  if (btnDesc && descEl) attachTranslateToggle(btnDesc, descEl);
  if (btnRep  && repEl)  attachTranslateToggle(btnRep,  repEl);
}
</script>
<script>
// ── Date topbar ────────────────────────────────────────────────────────────────
(function() {
  const ds = new Date().toLocaleDateString('fr-FR',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
  const el = document.getElementById('topbarDate');
  if(el) el.textContent = ds.charAt(0).toUpperCase() + ds.slice(1);
})();

// ── State ──────────────────────────────────────────────────────────────────────
let allRows      = [];
let currentRecId = null;
let currentRepId = null;
let currentEmail = '';
let sortAsc      = false;
let perms        = {};
let isKanban     = false;
let currentPage  = 1;
let totalRows    = 0;
let perPage      = 20;

// ── Modal helpers ──────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// ── Toast ──────────────────────────────────────────────────────────────────────
function showToast(msg, ok) {
  ok = (ok === undefined) ? true : ok;
  const box = document.getElementById('toastBox');
  const div = document.createElement('div');
  div.className = 'toast-msg ' + (ok ? 'toast-ok' : 'toast-err');
  div.innerHTML = `<i class="bi bi-${ok ? 'check-circle-fill' : 'exclamation-triangle-fill'}"></i> ${msg}`;
  box.appendChild(div);
  setTimeout(function() { div.remove(); }, 3500);
}

// ── API helpers ────────────────────────────────────────────────────────────────
function apiPost(url, data) {
  var fd = new FormData();
  Object.keys(data).forEach(function(k) { fd.append(k, data[k]); });
  // Ajout du jeton CSRF
  if (perms.csrfToken) fd.append('csrf_token', perms.csrfToken);
  return fetch(url, { method:'POST', body:fd }).then(function(r) { return r.json(); });
}

// ── Utilitaires ────────────────────────────────────────────────────────────────
function esc(s) {
  return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function initiales(nom, prenom) {
  if (!nom && !prenom) return '??';
  var n = (nom || '').trim();
  var p = (prenom || '').trim();
  return ((p ? p[0] : '') + (n ? n[0] : '')).toUpperCase() || '??';
}
function formatDate(d) {
  if (!d) return '—';
  var months = ['janv.','févr.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'];
  var dt = new Date(d);
  return dt.getDate() + ' ' + months[dt.getMonth()] + ' ' + dt.getFullYear();
}
function badgeStatut(s) {
  var m = {
    closed:  { l:'Résolue',    c:'status-badge success' },
    open:    { l:'En attente', c:'status-badge info'    },
    rejected:{ l:'Rejetée',    c:'status-badge danger'  }
  };
  return m[s] || {l:'En cours', c:'status-badge warning'};
}
function badgePriorite(p) {
  if (p==='Urgente') return 'status-badge danger';
  if (p==='Faible')  return 'status-badge';
  return 'status-badge info';
}
function badgeType(t) {
  if (t==='Santé')      return 'status-badge success';
  if (t==='Auto')       return 'status-badge info';
  if (t==='Habitation') return 'status-badge warning';
  return 'status-badge danger';
}

// ── Charger les données ────────────────────────────────────────────────────────
function loadReclamations(page) {
  page = page || 1;
  currentPage = page;
  return fetch('listreponse.php?page=' + page)
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.success) { showToast(data.message || 'Erreur chargement', false); return; }
      allRows   = data.rows;
      totalRows = data.total;

      updateKPIs(allRows);
      filterTable(false); // On applique le filtrage local sur la page chargée
      renderPagination(totalRows, page, perPage);
    })
    .catch(function() { showToast('Erreur réseau lors du chargement', false); });
}

function updateKPIs(rows) {
    var total=totalRows, answered=0, pending=0, urgent=0, rejected=0;
    rows.forEach(function(row) {
      if (row.statut === 'rejected')  rejected++;
      else if (row.reponse_contenu)   answered++;
      else                            pending++;
      if (row.priorite === 'Urgente') urgent++;
    });
    document.getElementById('statTotal').textContent    = total;
    document.getElementById('statAnswered').textContent = answered;
    document.getElementById('statPending').textContent  = pending;
    document.getElementById('statRejected').textContent = rejected;
    document.getElementById('statUrgent').innerHTML =
      '<i class="bi bi-exclamation-circle"></i> ' + urgent + ' sur cette page';
}

function renderPagination(total, page, perPage) {
    var pages = Math.ceil(total / perPage);
    var el    = document.getElementById('paginationBar');
    if (!el) return;
    if (pages <= 1) { el.innerHTML = ''; return; }
    
    var html  = '<div class="pagination">'
        + '<span class="pagination-info">Page <strong>' + page + '</strong> / ' + pages + ' — ' + total + ' réclamation(s)</span>'
        + '<div class="pagination-btns">';
    
    html += '<button class="page-btn" ' + (page===1?'disabled':'') + ' onclick="loadReclamations(' + (page-1) + ')">‹</button>';
    
    var start = Math.max(1, page - 2);
    var end   = Math.min(pages, page + 2);
    
    if (start > 1) html += '<button class="page-btn" onclick="loadReclamations(1)">1</button>' + (start>2?'<span style="color:var(--text-secondary)">...</span>':'');
    
    for (var i = start; i <= end; i++) {
        html += '<button class="page-btn ' + (i===page?'active':'') + '" onclick="loadReclamations(' + i + ')">' + i + '</button>';
    }
    
    if (end < pages) html += (end<pages-1?'<span style="color:var(--text-secondary)">...</span>':'') + '<button class="page-btn" onclick="loadReclamations(' + pages + ')">' + pages + '</button>';
    
    html += '<button class="page-btn" ' + (page===pages?'disabled':'') + ' onclick="loadReclamations(' + (page+1) + ')">›</button>';
    html += '</div></div>';
    el.innerHTML = html;
}

// ── Render table ───────────────────────────────────────────────────────────────
// ── Render table ───────────────────────────────────────────────────────────────
function getSLA(rec) {
  if (rec.reponse_contenu || rec.statut === 'closed' || rec.statut === 'rejected') return null;
  var depotDate = new Date(rec.date_depot);
  var diffMs    = Date.now() - depotDate.getTime();
  var hours     = diffMs / 3600000;
  if (hours > 72) return 'late';
  if (hours > 48) return 'warn';
  return null;
}

function renderTable(rows) {
  window._recRows = rows;
  var body = document.getElementById('recBody');
  if(!rows.length) {
    body.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:var(--text-secondary);">Aucune réclamation trouvée.</td></tr>';
    return;
  }

  // Kanban sync
  if (isKanban) { renderKanban(rows); return; }

  // Mise à jour de l'en-tête selon le rôle
  var headerRow = document.querySelector('thead tr');
  var htmlHead = '<th>CLIENT</th><th>OBJET / DESCRIPTION</th><th>TYPE</th><th>PRIORITÉ</th><th>STATUT</th><th>DATE</th>';
  
  if (perms.role === 'superadmin') {
    htmlHead += '<th class="col-agence">AGENCE</th>';
  }
  if (perms.role === 'superadmin' || perms.role === 'admin') {
    htmlHead += '<th class="col-agent">RÉPONDU PAR</th>';
  }
  htmlHead += '<th>RÉPONSE</th><th>ACTIONS</th>';
  headerRow.innerHTML = htmlHead;

  body.innerHTML = rows.map(function(rec, idx) {
    var statut     = badgeStatut(rec.statut || '');
    var replied    = !!rec.reponse_contenu;
    var isRej      = rec.statut === 'rejected';
    var isClosed   = rec.statut === 'closed';
    var actionDone = replied || isRej || isClosed;
    
    var sla        = getSLA(rec);
    var trClass    = sla ? 'class="sla-' + sla + '"' : '';
    var slaBadge   = sla === 'late' ? '<span class="sla-badge late">+72h</span>'
                   : sla === 'warn' ? '<span class="sla-badge warn">+48h</span>'
                   : '';

    var repBadge = isRej
      ? '<span class="status-badge danger"><i class="bi bi-x-circle-fill" style="margin-right:4px"></i> Rejetée</span>'
      : replied
        ? '<span class="status-badge success"><i class="bi bi-check-circle-fill" style="margin-right:4px"></i> Répondue</span>'
        : '<span class="status-badge warning"><i class="bi bi-clock" style="margin-right:4px"></i> En attente</span>';

    var btnVoir = '<button class="act-btn view-btn" title="Voir détails" onclick="openVoir('+rec.id+')"><i class="bi bi-eye"></i></button>';

    var btnRepondre = (!actionDone && perms.canRepondre)
      ? '<button class="act-btn reply" title="Répondre" onclick="openRepondre('+idx+')"><i class="bi bi-reply-fill"></i></button>'
      : (!actionDone ? '<button class="act-btn disabled-btn" disabled title="Permission refusée"><i class="bi bi-reply-fill"></i></button>' : '<button class="act-btn disabled-btn" disabled title="Déjà traitée"><i class="bi bi-reply-fill"></i></button>');

    var btnEdit = (replied && !isRej && perms.canModifier)
      ? '<button class="act-btn edit-btn" title="Modifier la réponse" onclick="openModifier('+idx+')"><i class="bi bi-pencil"></i></button>'
      : '';

    var btnDel = (replied && !isRej && perms.canSupprimer)
      ? '<button class="act-btn del-btn" title="Supprimer la réponse" onclick="openSupprimer('+idx+')"><i class="bi bi-trash3"></i></button>'
      : '';

    var btnRejeter = (!actionDone && perms.canRejeter)
      ? '<button class="act-btn reject-btn" title="Rejeter" onclick="openRejeter('+idx+')"><i class="bi bi-x-circle"></i></button>'
      : '';

    var displayName = (rec.client_nom || rec.client_prenom)
      ? esc((rec.client_prenom || '') + ' ' + (rec.client_nom || ''))
      : esc(rec.email || '—');

    var descFull  = esc(rec.description || '');
    var descShort = descFull.length > 80 ? descFull.substring(0, 80) + '...' : descFull;
    var descHtml  = descFull.length > 80
        ? '<span class="desc-text" id="desc-'+idx+'">'+descShort+'</span>'
          + '<button class="btn-toggle-desc" onclick="toggleDesc('+idx+',this)" data-full="'+descFull+'" data-short="'+descShort+'">Voir plus</button>'
        : '<span class="desc-text">'+descFull+'</span>';

    var row = '<tr ' + trClass + '>'
      +'<td>'
        +'<div class="client-cell">'
          +'<div class="client-avatar">'+initiales(rec.client_nom, rec.client_prenom)+'</div>'
          +'<div>'
            +'<div class="client-name" title="'+displayName+'">'+displayName+'</div>'
            +'<div class="client-ref" title="'+esc(rec.numero_client||'')+'">'+esc(rec.numero_client || 'Sans code')+'</div>'
          +'</div>'
        +'</div>'
      +'</td>'
      +'<td>'
        +'<div class="desc-objet">'+esc(rec.objet||'—')+'</div>'
        + descHtml
      +'</td>'
      +'<td><span class="'+badgeType(rec.type||'Autre')+'">'+esc(rec.type||'—')+'</span></td>'
      +'<td><span class="'+badgePriorite(rec.priorite||'Normale')+'">'+esc(rec.priorite||'—')+'</span></td>'
      +'<td><span class="'+statut.c+'">'+statut.l+'</span></td>'
      +'<td style="font-size:12px;color:var(--text-secondary);">' + formatDate(rec.date_depot) + (slaBadge ? '<br>'+slaBadge : '') + '</td>';

    // Colonnes dynamiques
    if (perms.role === 'superadmin') {
      row += '<td style="font-size:12px;color:var(--accent);font-weight:600;">'+esc(rec.nom_agence || 'N/A')+'</td>';
    }
    if (perms.role === 'superadmin' || perms.role === 'admin') {
      var agentName = (rec.agent_nom || rec.agent_prenom) 
        ? esc(rec.agent_prenom + ' ' + rec.agent_nom) 
        : (rec.rep_id || replied ? '<span style="opacity:0.5;font-style:italic;">Automatique</span>' : '—');
      row += '<td style="font-size:12px;color:var(--text-secondary);">'+agentName+'</td>';
    }

    row += '<td>'+repBadge+'</td>'
      +'<td><div class="action-btns">'+btnVoir+btnRepondre+btnEdit+btnDel+btnRejeter+'</div></td>'
      +'</tr>';
    return row;
  }).join('');
}

function toggleDesc(idx, btn) {
    var el = document.getElementById('desc-'+idx);
    var expanded = btn.dataset.expanded === 'true';
    el.innerHTML = expanded ? btn.dataset.short : btn.dataset.full;
    btn.textContent = expanded ? 'Voir plus' : 'Réduire';
    btn.dataset.expanded = expanded ? 'false' : 'true';
}

// ── Actions Modals ──────────────────
function openVoir(recId) {
  // On peut charger les données à la demande (Lazy loading)
  showToast('<i class="bi bi-hourglass-split"></i> Chargement des détails...', true);
  fetch('get_reclamation.php?id=' + recId)
    .then(r => r.json())
    .then(data => {
      if (!data.success) { showToast(data.message, false); return; }
      var rec = data.rec;
      var clientName = (rec.client_nom || rec.client_prenom) ? (rec.client_prenom + ' ' + rec.client_nom) : (rec.email || '—');
      document.getElementById('vRef').textContent      = rec.rec_ref      || '—';
      document.getElementById('vClient').textContent   = clientName + ' (' + (rec.numero_client || 'Sans code') + ')';
      document.getElementById('vObjet').textContent    = rec.objet        || '—';
      document.getElementById('vType').textContent     = rec.type         || '—';
      document.getElementById('vPriorite').textContent = rec.priorite     || '—';
      document.getElementById('vDate').textContent     = formatDate(rec.date_depot);
      document.getElementById('vDesc').textContent     = rec.description  || '—';
      var b = document.getElementById('vReponseBlock');
      if (rec.reponse_contenu) {
        document.getElementById('vReponse').textContent = rec.reponse_contenu;
        document.getElementById('vDateRep').textContent = formatDate(rec.rep_date) || '—';
        b.style.display = 'block';
      } else { b.style.display = 'none'; }
      openModal('modalVoir');
      setTimeout(initTranslateVoir, 50);
    });
}

function openRepondre(idx) {
  var rec = window._recRows[idx];
  currentRecId = rec.id; currentEmail = rec.email;
  var clientName = (rec.client_nom || rec.client_prenom) ? (rec.client_prenom + ' ' + rec.client_nom) : (rec.email || '—');
  document.getElementById('mClient').textContent = clientName;
  document.getElementById('mObjet').textContent  = rec.objet || '—';
  document.getElementById('mDesc').textContent   = rec.description || '—';
  document.getElementById('mContenu').value      = '';
  document.getElementById('suggestionsBox').style.display = 'none';
  
  if (rec.priorite === 'Urgente') {
    showToast('⚠ Réclamation urgente — réponse prioritaire requise', false);
  }

  openModal('modalRepondre');
  setTimeout(initTranslateRepondre, 50);
}
function submitRepondre() {
  var c = document.getElementById('mContenu').value.trim();
  if(!c) { document.getElementById('errMContenu').style.display='block'; return; }
  apiPost('addreponse.php', { reclamation_id: currentRecId, contenu: c, email_client: currentEmail })
    .then(function(res) {
      if (res.success) { closeModal('modalRepondre'); showToast(res.message, !!res.email_sent); loadReclamations(); }
      else showToast(res.message, false);
    }).catch(function(){ showToast('Erreur réseau', false); });
}

function openModifier(idx) {
  var rec = window._recRows[idx];
  currentRepId = rec.rep_id;
  var clientName = (rec.client_nom || rec.client_prenom) ? (rec.client_prenom + ' ' + rec.client_nom) : (rec.email || '—');
  document.getElementById('modClient').textContent = clientName;
  document.getElementById('modObjet').textContent  = rec.objet || '—';
  document.getElementById('modContenu').value      = rec.reponse_contenu || '';
  openModal('modalModifier');
  setTimeout(initTranslateModifier, 50);
}
function submitModifier() {
  var c = document.getElementById('modContenu').value.trim();
  if(!c) { document.getElementById('errModContenu').style.display='block'; return; }
  apiPost('updatereponse.php', { reponse_id: currentRepId, contenu: c })
    .then(function(res) {
      if (res.success) { closeModal('modalModifier'); showToast('Réponse modifiée.'); loadReclamations(); }
      else showToast(res.message, false);
    }).catch(function(){ showToast('Erreur réseau', false); });
}

function openSupprimer(idx) {
  var rec = window._recRows[idx];
  currentRepId = rec.rep_id;
  var clientName = (rec.client_nom || rec.client_prenom) ? (rec.client_prenom + ' ' + rec.client_nom) : (rec.email || '—');
  document.getElementById('supClient').textContent = clientName;
  document.getElementById('supObjet').textContent  = rec.objet || '—';
  document.getElementById('supContenu').textContent = rec.reponse_contenu || '—';
  openModal('modalSupprimer');
}
function confirmSupprimer() {
  apiPost('deletereponse.php', { reponse_id: currentRepId })
    .then(function(res) {
      if (res.success) { closeModal('modalSupprimer'); showToast('Réponse supprimée.'); loadReclamations(); }
      else showToast(res.message, false);
    }).catch(function(){ showToast('Erreur réseau', false); });
}

function openRejeter(idx) {
  var rec = window._recRows[idx];
  currentRecId = rec.id; currentEmail = rec.email;
  var clientName = (rec.client_nom || rec.client_prenom) ? (rec.client_prenom + ' ' + rec.client_nom) : (rec.email || '—');
  document.getElementById('rejClient').textContent = clientName;
  document.getElementById('rejObjet').textContent  = rec.objet || '—';
  document.getElementById('rejMotif').value        = '';
  openModal('modalRejeter');
}
function submitRejeter() {
  var m = document.getElementById('rejMotif').value.trim();
  if(!m) { document.getElementById('errRejMotif').style.display='block'; return; }
  apiPost('addreponse.php', { action:'rejeter', reclamation_id: currentRecId, motif: m, email_client: currentEmail })
    .then(function(res) {
      if (res.success) { closeModal('modalRejeter'); showToast(res.message, !!res.email_sent); loadReclamations(); }
      else showToast(res.message, false);
    }).catch(function(){ showToast('Erreur réseau', false); });
}

// ── Filtres & Tri ──────────────────
var sortMode = 0; // 0: None, 1: A-Z, 2: Z-A

function toggleSort() {
  sortMode = (sortMode + 1) % 3;
  const btn = document.getElementById('btnSort');
  const icon = document.getElementById('sortIcon');
  const lbl = document.getElementById('sortLabel');

  if (sortMode === 1) {
    icon.className = 'bi bi-sort-alpha-down';
    lbl.textContent = 'Trier A→Z';
    btn.classList.add('active');
  } else if (sortMode === 2) {
    icon.className = 'bi bi-sort-alpha-up-alt';
    lbl.textContent = 'Trier Z→A';
    btn.classList.add('active');
  } else {
    icon.className = 'bi bi-sort-alpha-down';
    lbl.textContent = 'Tri par défaut';
    btn.classList.remove('active');
  }
  filterTable();
}
function filterTable(sync = true) {
  var s  = document.getElementById('searchInput').value.toLowerCase();
  var t  = document.getElementById('filterType').value;
  var st = document.getElementById('filterStatut').value;
  var pr = document.getElementById('filterPriorite').value;
  var from = document.getElementById('filterDateFrom').value;
  var to   = document.getElementById('filterDateTo').value;
  
  var f = allRows.filter(function(r) {
    var rowDate = (r.date_depot || '').substring(0, 10);
    var searchStr = (
      String(r.client_nom||'') + ' ' + 
      String(r.client_prenom||'') + ' ' + 
      String(r.email||'') + ' ' + 
      String(r.objet||'') + ' ' + 
      String(r.rec_ref||'') + ' ' + 
      String(r.numero_client||'')
    ).toLowerCase();

    return (!s || searchStr.includes(s))
        && (!t || r.type === t)
        && (!st || r.statut === st)
        && (!pr || r.priorite === pr)
        && (!from || rowDate >= from)
        && (!to || rowDate <= to);
  });

  if (sortMode === 1) {
    f.sort((a,b) => String(a.objet).localeCompare(b.objet));
  } else if (sortMode === 2) {
    f.sort((a,b) => String(b.objet).localeCompare(a.objet));
  }
  
  renderTable(f);
}

function resetFilters() {
  document.getElementById('searchInput').value = '';
  document.getElementById('filterType').value = '';
  document.getElementById('filterStatut').value = '';
  document.getElementById('filterPriorite').value = '';
  document.getElementById('filterDateFrom').value = '';
  document.getElementById('filterDateTo').value   = '';
  filterTable();
}

function toggleView() {
    isKanban = !isKanban;
    document.getElementById('viewIcon').className = isKanban ? 'bi bi-table' : 'bi bi-kanban';
    document.getElementById('viewLabel').textContent = isKanban ? 'Vue Tableau' : 'Vue Kanban';
    document.querySelector('.table-wrap').style.display = isKanban ? 'none' : 'block';
    document.getElementById('paginationBar').style.display = isKanban ? 'none' : 'block';
    document.getElementById('kanbanBoard').style.display = isKanban ? 'grid' : 'none';
    if (isKanban) renderKanban(allRows);
    else renderTable(allRows);
}

function renderKanban(rows) {
    var cols = { open: [], closed: [], rejected: [] };
    rows.forEach(function(r) { (cols[r.statut] || cols.open).push(r); });

    function makeCard(r) {
        var idx = allRows.indexOf(r);
        return '<div class="kanban-card" onclick="openVoir(' + r.id + ')">'
            + '<div style="font-weight:700;margin-bottom:6px;font-size:13px;color:var(--text-primary);">' + esc(r.objet || '—') + '</div>'
            + '<div style="color:var(--text-secondary);font-size:11.5px;margin-bottom:8px;">' + esc(r.email || '—') + '</div>'
            + '<div style="display:flex;justify-content:space-between;align-items:center;">'
                + '<span class="' + badgePriorite(r.priorite) + '" style="font-size:10px;">' + esc(r.priorite) + '</span>'
                + '<span style="font-size:10px;color:var(--text-secondary);">' + formatDate(r.date_depot) + '</span>'
            + '</div>'
            + '</div>';
    }

    document.getElementById('colOpen').innerHTML     = cols.open.map(makeCard).join('') || '<div style="color:var(--text-secondary);font-size:11px;text-align:center;padding:16px;opacity:0.5;">Vide</div>';
    document.getElementById('colClosed').innerHTML   = cols.closed.map(makeCard).join('') || '<div style="color:var(--text-secondary);font-size:11px;text-align:center;padding:16px;opacity:0.5;">Vide</div>';
    document.getElementById('colRejected').innerHTML = cols.rejected.map(makeCard).join('') || '<div style="color:var(--text-secondary);font-size:11px;text-align:center;padding:16px;opacity:0.5;">Vide</div>';
    
    document.getElementById('countOpen').textContent = cols.open.length;
    document.getElementById('countClosed').textContent = cols.closed.length;
    document.getElementById('countRejected').textContent = cols.rejected.length;
}

function loadAI() {
    var btn = document.getElementById('btnSuggest');
    btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split spin-icon"></i> Analyse de la réclamation...';
    apiPost('suggest_response.php', { reclamation_id: currentRecId })
        .then(function(res) {
            btn.disabled = false; btn.innerHTML = '<i class="bi bi-stars"></i> Suggérer une réponse (IA)';
            if (!res.success || !res.suggestions || !res.suggestions.length) { 
                showToast(res.message || 'Aucune suggestion disponible', false); 
                return; 
            }
            var box  = document.getElementById('suggestionsBox');
            var list = document.getElementById('suggestionsList');
            list.innerHTML = res.suggestions.map(function(s) {
                return '<div class="suggestion-item" onclick="useSuggestion(this)">' + esc(s) + '</div>';
            }).join('');
            box.style.display = 'block';
        }).catch(function(e){ 
            btn.disabled = false; btn.innerHTML = '<i class="bi bi-stars"></i> Suggérer une réponse (IA)';
            showToast('Erreur réseau IA', false); 
        });
}

function useSuggestion(el) {
    document.getElementById('mContenu').value = el.textContent.trim();
    document.getElementById('suggestionsBox').style.display = 'none';
    showToast('Suggestion appliquée', true);
}

function useTemplate(el) {
    var contenu = el.getAttribute('data-contenu');
    document.getElementById('mContenu').value = contenu;
    showToast('Modèle inséré', true);
}

async function openStatsModal() {
  openModal('modalStats');
  try {
    const res = await fetch('statsType.php');
    const data = await res.json();
    if (!data.success) { document.getElementById('statsModalBody').innerHTML = 'Erreur stats'; return; }
    renderStatsChart(data.stats, data.total);
  } catch(e) { document.getElementById('statsModalBody').innerHTML = 'Erreur réseau'; }
}

function renderStatsChart(stats, total) {
  const ctx = document.getElementById('typeChart').getContext('2d');
  document.getElementById('donutTotal').textContent = total;
  
  if (window._typeChartObj) window._typeChartObj.destroy();

  const labels = stats.map(s => s.type);
  const data   = stats.map(s => s.total);
  
  // Palette Protex Premium
  const colors = [
    '#00b4d8', // Cyan
    '#2ec4b6', // Teal
    '#eab308', // Gold
    '#e63946', // Red
    '#7c3aed', // Purple
    '#fb7185'  // Rose
  ];

  window._typeChartObj = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [{
        data: data,
        backgroundColor: colors,
        hoverBackgroundColor: colors,
        borderColor: 'rgba(10, 25, 49, 0.8)',
        borderWidth: 4,
        hoverOffset: 15,
        borderRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '82%',
      animation: {
        animateScale: true,
        animateRotate: true,
        duration: 1500,
        easing: 'easeOutQuart'
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          enabled: true,
          backgroundColor: 'rgba(5, 10, 30, 0.95)',
          titleFont: { size: 14, weight: '700', family: 'Outfit' },
          bodyFont: { size: 13, family: 'Inter' },
          padding: 15,
          cornerRadius: 12,
          displayColors: true,
          borderColor: 'rgba(0, 180, 216, 0.3)',
          borderWidth: 1,
          callbacks: {
            label: function(context) {
              const val = context.raw;
              const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
              return ` ${context.label} : ${val} (${pct}%)`;
            }
          }
        }
      }
    }
  });

  // Légende
  const legendEl = document.getElementById('statsLegend');
  legendEl.innerHTML = stats.map((s, idx) => `
    <div class="legend-item" onclick="highlightChart(${idx})">
      <div class="legend-dot" style="background:${colors[idx % colors.length]}"></div>
      <div class="legend-info">
        <div class="legend-type">${esc(s.type)}</div>
        <div class="legend-pct">${s.percent}%</div>
      </div>
      <div class="legend-count">${s.total}</div>
    </div>
  `).join('');
}

function highlightChart(index) {
  if (!window._typeChartObj) return;
  const meta = window._typeChartObj.getDatasetMeta(0);
  // Toggle hover effect manually? Simpler to just trigger tooltip
  const activeElements = [{ datasetIndex: 0, index: index }];
  window._typeChartObj.setActiveElements(activeElements);
  window._typeChartObj.tooltip.setActiveElements(activeElements);
  window._typeChartObj.update();
}

// ── Init ───────────────────────────────────────────────────────────────────────
const defaultPerms = {
  role: 'agent',
  userName: 'Utilisateur',
  canRepondre: true,
  canRejeter: false,
  canModifier: false,
  canSupprimer: false
};

fetch('get_permissions.php')
  .then(function(r) {
    if (!r.ok) throw new Error('Permissions non disponibles');
    return r.json();
  })
  .then(function(data) {
    perms = Object.assign({}, defaultPerms, data || {});
    // The profile UI is updated via get_admin.php below

    // Cacher le bouton stats si pas de permission
    if (!perms.canSeeStatsAgence && !perms.canSeeStatsGlobales) {
      const btnStats = document.querySelector('.btn-stats');
      if (btnStats) btnStats.style.display = 'none';
    }
    return loadReclamations();
  })
  .catch(function() {
    perms = Object.assign({}, defaultPerms);
    return loadReclamations();
  });

</script>
</body>
</html>

