<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
// FIX 2 — Réservé au superadmin uniquement
if (SessionGuard::role() !== 'superadmin') {
    http_response_code(403);
    $errMsg = 'Cette section est réservée au Super Administrateur.';
    include __DIR__ . '/403.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Postes — Protex Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/admin-users.css">
    <link rel="stylesheet" href="assets/css/animations.css">
</head>
  <style>
    .badge-like  { background:rgba(230,57,70,0.15);color:var(--danger);border:1px solid rgba(230,57,70,0.3);font-size:11px;font-weight:600;padding:4px 10px;border-radius:20px; }
    .badge-comm  { background:rgba(0,180,216,0.15);color:var(--accent);border:1px solid rgba(0,180,216,0.3);font-size:11px;font-weight:600;padding:4px 10px;border-radius:20px;cursor:pointer;transition:var(--transition); }
    .badge-comm:hover { background:rgba(0,180,216,0.25); }

    .comment-tree { margin-top:8px; }
    .c-item {
      background:var(--glass-bg);border:1px solid var(--glass-border);
      border-radius:var(--radius-sm);padding:10px 12px;margin-top:6px;
    }
    .c-item.hidden { opacity:0.55; }
    .c-item.hidden .c-text { text-decoration:line-through; }
    .c-item.hidden .c-author { text-decoration:line-through; }
    .c-head { display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:4px; }
    .c-author { font-size:13px;font-weight:600;color:#fff; }
    .c-badge { font-size:10px;color:var(--danger);font-weight:600; }
    .c-text { font-size:13px;color:var(--text-primary); }
    .c-date { font-size:11px;color:var(--text-secondary); }

    .toolbar-inner {
      padding:16px 24px;
      border-bottom:1px solid var(--glass-border);
    }

    /* ===== DETAIL MODAL — PREMIUM ===== */
    .detail-hero {
      background: linear-gradient(135deg, rgba(0,180,216,0.12) 0%, rgba(0,150,199,0.06) 100%);
      border: 1px solid rgba(0,180,216,0.18);
      border-radius: var(--radius-md);
      padding: 20px;
      margin-bottom: 20px;
      position: relative;
      overflow: hidden;
    }
    .detail-hero::before {
      content: '';
      position: absolute;
      top: -30px; right: -30px;
      width: 100px; height: 100px;
      background: radial-gradient(circle, rgba(0,180,216,0.15) 0%, transparent 70%);
      border-radius: 50%;
    }
    .detail-hero-top {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 14px;
    }
    .detail-hero-icon {
      width: 48px; height: 48px;
      background: linear-gradient(135deg, var(--accent), var(--accent-dark));
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 20px; color: #fff;
      box-shadow: 0 4px 16px rgba(0,180,216,0.35);
      flex-shrink: 0;
    }
    .detail-hero-title {
      font-family: var(--font-display);
      font-size: 17px; font-weight: 700; color: #fff;
      line-height: 1.3;
    }
    .detail-hero-sub {
      font-size: 12px; color: var(--text-secondary);
      margin-top: 2px;
      display: flex; align-items: center; gap: 6px;
    }
    .detail-hero-sub i { font-size: 11px; color: var(--accent); }

    .detail-stats-row {
      display: flex; gap: 10px;
    }
    .detail-stat-chip {
      flex: 1;
      background: rgba(255,255,255,0.04);
      border: 1px solid var(--glass-border);
      border-radius: var(--radius-sm);
      padding: 10px 12px;
      text-align: center;
      transition: var(--transition);
    }
    .detail-stat-chip:hover {
      border-color: rgba(0,180,216,0.3);
      background: rgba(0,180,216,0.06);
    }
    .detail-stat-chip .chip-value {
      font-family: var(--font-display);
      font-size: 18px; font-weight: 700; color: #fff;
      line-height: 1;
    }
    .detail-stat-chip .chip-label {
      font-size: 10px; color: var(--text-secondary);
      text-transform: uppercase; letter-spacing: 0.5px;
      margin-top: 4px;
    }
    .detail-stat-chip.likes .chip-value { color: var(--danger); }
    .detail-stat-chip.comments .chip-value { color: var(--accent); }
    .detail-stat-chip.note .chip-value { color: var(--gold); }

    .detail-section {
      margin-top: 18px;
    }
    .detail-section-title {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 1.2px;
      color: var(--text-secondary);
      font-weight: 500;
      margin-bottom: 10px;
      display: flex; align-items: center; gap: 6px;
    }
    .detail-section-title i { color: var(--accent); font-size: 13px; }

    .detail-content-box {
      background: var(--glass-bg);
      border: 1px solid var(--glass-border);
      border-radius: var(--radius-sm);
      padding: 16px;
      font-size: 14px;
      color: var(--text-primary);
      line-height: 1.7;
      white-space: pre-wrap;
      word-wrap: break-word;
    }

    .detail-info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }
    .detail-info-item {
      background: var(--glass-bg);
      border: 1px solid var(--glass-border);
      border-radius: var(--radius-sm);
      padding: 12px 14px;
      transition: var(--transition);
    }
    .detail-info-item:hover {
      border-color: rgba(0,180,216,0.25);
    }
    .detail-info-item .info-label {
      font-size: 11px; color: var(--text-secondary);
      display: flex; align-items: center; gap: 5px;
      margin-bottom: 4px;
    }
    .detail-info-item .info-label i { font-size: 12px; color: var(--accent); }
    .detail-info-item .info-value {
      font-size: 14px; font-weight: 600; color: #fff;
    }
    .detail-info-item.full { grid-column: 1 / -1; }

    .detail-divider {
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--glass-border), transparent);
      margin: 18px 0;
    }

    /* ===== COMMENT CARDS ===== */
    .cmnt-card {
      background: rgba(255,255,255,0.03);
      border: 1px solid var(--glass-border);
      border-radius: var(--radius-sm);
      padding: 14px;
      margin-top: 10px;
      transition: var(--transition);
      position: relative;
    }
    .cmnt-card:hover { border-color: rgba(0,180,216,0.2); }
    .cmnt-card.cmnt-hidden { opacity: 0.6; background: rgba(0,0,0,0.2); }
    
    .cmnt-top {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 10px;
    }
    .cmnt-avatar {
      width: 32px; height: 32px;
      background: var(--accent-glow);
      color: var(--accent);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 14px;
      flex-shrink: 0;
      border: 1px solid rgba(0,180,216,0.2);
    }
    .cmnt-author { font-size: 13px; font-weight: 600; color: #fff; }
    .cmnt-date { font-size: 11px; color: var(--text-secondary); }
    
    .cmnt-body {
      font-size: 13px;
      color: var(--text-primary);
      line-height: 1.5;
      padding-left: 42px;
    }
    
    .cmnt-toggle-btn {
      width: 28px; height: 28px;
      border-radius: 6px;
      border: 1px solid var(--glass-border);
      background: var(--glass-bg);
      color: var(--btn-color, var(--text-secondary));
      display: flex; align-items: center; justify-content: center;
      cursor: pointer;
      transition: var(--transition);
      flex-shrink: 0;
    }
    .cmnt-toggle-btn:hover:not(:disabled) { border-color: var(--btn-color); background: rgba(255,255,255,0.05); }
    .cmnt-toggle-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    
    .cmnt-hidden-badge {
      font-size: 10px;
      background: rgba(230,57,70,0.1);
      color: var(--danger);
      padding: 2px 6px;
      border-radius: 4px;
      margin-right: 6px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
  </style>
<body>

<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">

  <!-- ===== SIDEBAR (PHP include) ===== -->
  <?php require_once __DIR__ . '/assets/includes/sidebar.php'; ?>

  <!-- ===== MAIN ===== -->
  <main class="main">

    <div class="topbar">
      <div>
        <div class="topbar-title">Gestion des publications</div>
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
          <div class="page-title">Postes</div>
          <div class="page-breadcrumb">
            <i class="bi bi-house"></i>
            <a href="#">Accueil</a>
            <i class="bi bi-chevron-right" style="font-size:10px;"></i>
            <span>Postes</span>
          </div>
        </div>
      </div>

      <!-- STATS -->
      <div class="stats-grid">
        <div class="stat-card blue">
          <div class="stat-icon"><i class="bi bi-megaphone"></i></div>
          <div class="stat-value" id="statTotal">0</div>
          <div class="stat-label">Total postes</div>
          <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> Publications</div>
        </div>
        <div class="stat-card red">
          <div class="stat-icon"><i class="bi bi-heart"></i></div>
          <div class="stat-value" id="statLikes">0</div>
          <div class="stat-label">Total j'aime</div>
          <div class="stat-trend trend-up"><i class="bi bi-heart"></i> Interactions</div>
        </div>
        <div class="stat-card gold">
          <div class="stat-icon"><i class="bi bi-chat-left-text"></i></div>
          <div class="stat-value" id="statComments">0</div>
          <div class="stat-label">Commentaires</div>
          <div class="stat-trend trend-warn"><i class="bi bi-chat-dots"></i> Total</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-building"></i></div>
          <div class="stat-value" id="statAgencies">0</div>
          <div class="stat-label">Agences</div>
          <div class="stat-trend trend-up"><i class="bi bi-geo-alt"></i> Actives</div>
        </div>
      </div>

      <!-- TABLE CARD -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <i class="bi bi-table"></i> Liste des postes
          </div>
          <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
            <i class="bi bi-plus-lg"></i> Ajouter
          </button>
        </div>

        <!-- TOOLBAR -->
        <div class="toolbar-inner">
          <div class="toolbar" style="margin-bottom:0;">
            <div class="search-box">
              <i class="bi bi-search"></i>
              <input type="text" id="searchInput" placeholder="Rechercher par contenu, auteur, agence...">
            </div>
            <select class="filter-select" id="filterAgency">
              <option value="">Toutes les agences</option>
            </select>
            <select class="filter-select" id="filterSort">
              <option value="date_desc">Date &darr; (r&eacute;cent)</option>
              <option value="date_asc">Date &uarr; (ancien)</option>
              <option value="author_asc">Auteur A-Z</option>
              <option value="author_desc">Auteur Z-A</option>
              <option value="likes_desc">J'aime &darr;</option>
              <option value="likes_asc">J'aime &uarr;</option>
              <option value="comments_desc">Commentaires &darr;</option>
              <option value="comments_asc">Commentaires &uarr;</option>
            </select>
            <button class="btn btn-outline btn-sm" onclick="resetFilters()">
              <i class="bi bi-x-circle"></i> R&eacute;initialiser
            </button>
          </div>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Contenu</th>
                <th>Auteur</th>
                <th>Agence</th>
                <th>Date</th>
                <th>J'aime</th>
                <th>Commentaires</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="postsBody"></tbody>
          </table>
          <div id="emptyState" style="display:none;text-align:center;padding:48px 20px;color:var(--text-secondary);">
            <i class="bi bi-inbox" style="font-size:36px;display:block;margin-bottom:10px;opacity:0.3;"></i>
            <p style="font-size:14px;">Aucun poste trouvé</p>
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

<!-- ===== MODAL CRUD ===== -->
<div class="modal-overlay" id="modalCrud">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><i class="bi bi-megaphone"></i> <span id="modalCrudTitle">Ajouter un poste</span></div>
      <button class="modal-close" onclick="closeModal('modalCrud')"><i class="bi bi-x"></i></button>
    </div>
    <div style="padding:0 32px 32px;">
      <div class="form-group">
        <label>Contenu</label>
        <textarea id="fContenu" class="form-control" placeholder="Écrire le contenu du poste..." style="min-height:110px;resize:vertical;"></textarea>
        <div class="form-error" id="errContenu"></div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Auteur</label>
          <input type="text" id="fAuteur" class="form-control" placeholder="Nom de l'auteur">
          <div class="form-error" id="errAuteur"></div>
        </div>
        <div class="form-group">
          <label>Agence</label>
          <select id="fAgence" class="form-control"><option value="">Choisir</option></select>
          <div class="form-error" id="errAgence"></div>
        </div>
      </div>
      <div class="form-group">
        <label>Date de publication</label>
        <input type="date" id="fDate" class="form-control">
        <div class="form-error" id="errDate"></div>
      </div>
      <div class="modal-footer" style="margin-top:8px;">
        <button class="btn btn-outline" onclick="closeModal('modalCrud')">Annuler</button>
        <button class="btn btn-primary" id="btnSavePost"><i class="bi bi-save"></i> Enregistrer</button>
      </div>
    </div>
  </div>
</div>

<!-- ===== MODAL DÉTAIL ===== -->
<div class="modal-overlay" id="modalDetail">
  <div class="modal" style="max-width:620px;">
    <div class="modal-header">
      <div class="modal-title"><i class="bi bi-megaphone"></i> Détails du poste</div>
      <button class="modal-close" onclick="closeModal('modalDetail')"><i class="bi bi-x"></i></button>
    </div>
    <div id="modalDetailBody" style="padding:0 32px 8px;"></div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modalDetail')">Fermer</button>
    </div>
  </div>
</div>

<!-- ===== MODAL SUPPRIMER ===== -->
<div class="modal-overlay delete-modal" id="modalDelete">
  <div class="modal">
    <div class="delete-icon"><i class="bi bi-trash3"></i></div>
    <div class="delete-title">Supprimer ce poste ?</div>
    <div class="delete-msg" id="deleteMsg">Cette action est irréversible.</div>
    <div class="modal-footer" style="justify-content:center;margin-top:28px;">
      <button class="btn btn-outline" onclick="closeModal('modalDelete')">Annuler</button>
      <button class="btn btn-danger" onclick="confirmDelete()">
        <i class="bi bi-trash3"></i> Supprimer définitivement
      </button>
    </div>
  </div>
</div>

<script src="assets/js/validation.js"></script>
<script>
const API_GET = 'get_postes_agence.php';
const API_SAVE = 'save_poste.php';
const API_DELETE = 'delete_poste.php';
const API_AGENCES = 'get_agences.php';

let posts = [], agencies = [], editingId = null, deletingId = null;
let currentPage = 1, perPage = 10, currentDetailPostId = null;

document.getElementById('topbarDate').textContent =
  new Date().toLocaleDateString('fr-FR',{weekday:'long',day:'numeric',month:'long',year:'numeric'});

async function apiPost(url, data) {
  const r = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) });
  const j = await r.json();
  if (!r.ok || !j.success) throw j;
  return j;
}
function escapeHtml(t){ return String(t??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }
function formatDate(d){ if(!d)return'—'; return new Date(d).toLocaleDateString('fr-FR',{day:'2-digit',month:'short',year:'numeric'}); }
function showToast(msg,type){
  if(typeof type==='undefined')type='success';
  const icons={success:'check-circle',warning:'exclamation-triangle',danger:'x-circle'};
  const t=document.createElement('div');
  t.className='toast-notif toast-'+type;
  t.innerHTML='<i class="bi bi-'+icons[type]+'"></i><span>'+escapeHtml(msg)+'</span>';
  document.body.appendChild(t);
  setTimeout(()=>t.classList.add('show'),50);
  setTimeout(()=>{t.classList.remove('show');setTimeout(()=>t.remove(),300);},3000);
}
function openModal(id){document.getElementById(id).classList.add('open');document.body.style.overflow='hidden';}
function closeModal(id){document.getElementById(id).classList.remove('open');document.body.style.overflow='';}

document.addEventListener('keydown',e=>{if(e.key==='Escape')document.querySelectorAll('.modal-overlay.open').forEach(m=>{m.classList.remove('open');document.body.style.overflow='';});});
document.querySelectorAll('.modal-overlay').forEach(o=>{o.addEventListener('click',e=>{if(e.target===o){o.classList.remove('open');document.body.style.overflow='';}});});

async function loadAgencies(){
  try {
    const r=await fetch(API_AGENCES); const j=await r.json();
    if(j.success){
      agencies=j.data||j.agences||[];
      const fs=document.getElementById('filterAgency');
      const fm=document.getElementById('fAgence');
      fs.innerHTML='<option value="">Toutes les agences</option>';
      fm.innerHTML='<option value="">Choisir</option>';
      agencies.forEach(a=>{
        fs.innerHTML+='<option value="'+a.id_agence+'">'+escapeHtml(a.nom_agence)+'</option>';
        fm.innerHTML+='<option value="'+a.id_agence+'">'+escapeHtml(a.nom_agence)+'</option>';
      });
    }
  } catch(e){ console.error('Erreur agences',e); }
}

async function loadPosts(){
  try {
    const r=await fetch(API_GET+'?_='+Date.now()); const j=await r.json();
    if(j.success){ posts=j.data||j.posts||[]; currentPage=1; render(); }
    else showToast('Erreur chargement posts.','danger');
  } catch(e){ showToast('Impossible de charger les posts.','danger'); }
}

function getFiltered(){
  const q=document.getElementById('searchInput').value.toLowerCase();
  const ag=document.getElementById('filterAgency').value;
  const sort=document.getElementById('filterSort').value;
  let f=posts.filter(p=>(!q||(p.contenu+' '+p.auteur+' '+(p.nom_agence||'')).toLowerCase().includes(q))&&(!ag||Number(p.id_agence)===Number(ag)));
  switch(sort){
    case 'date_asc': f.sort((a,b)=>new Date(a.date_publication)-new Date(b.date_publication)); break;
    case 'date_desc': f.sort((a,b)=>new Date(b.date_publication)-new Date(a.date_publication)); break;
    case 'author_asc': f.sort((a,b)=>(a.auteur||'').localeCompare(b.auteur||'')); break;
    case 'author_desc': f.sort((a,b)=>(b.auteur||'').localeCompare(a.auteur||'')); break;
    case 'likes_desc': f.sort((a,b)=>(b.nb_likes||0)-(a.nb_likes||0)); break;
    case 'likes_asc': f.sort((a,b)=>(a.nb_likes||0)-(b.nb_likes||0)); break;
    case 'comments_desc': f.sort((a,b)=>(b.nb_commentaires||0)-(a.nb_commentaires||0)); break;
    case 'comments_asc': f.sort((a,b)=>(a.nb_commentaires||0)-(b.nb_commentaires||0)); break;
    default: f.sort((a,b)=>new Date(b.date_publication)-new Date(a.date_publication));
  }
  return f;
}
function resetFilters(){
  document.getElementById('searchInput').value='';
  document.getElementById('filterAgency').value='';
  document.getElementById('filterSort').value='date_desc';
  currentPage=1; render();
}

function render(){
  const filtered=getFiltered(), total=filtered.length;
  const pages=Math.ceil(total/perPage)||1;
  if(currentPage>pages)currentPage=pages;
  const slice=filtered.slice((currentPage-1)*perPage,currentPage*perPage);
  const tbody=document.getElementById('postsBody');
  const empty=document.getElementById('emptyState');
  if(!slice.length){tbody.innerHTML='';empty.style.display='block';}
  else {
    empty.style.display='none';
    tbody.innerHTML=slice.map(p=>{
      return '<tr>'+
        '<td><span style="font-family:monospace;font-size:12px;color:var(--accent);">#'+p.id_poste+'</span></td>'+
        '<td style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-primary);">'+escapeHtml(p.contenu||'')+'</td>'+
        '<td style="color:#fff;font-weight:500;">'+escapeHtml(p.auteur||'')+'</td>'+
        '<td style="color:var(--text-secondary);">'+escapeHtml(p.nom_agence||p.agence||'—')+'</td>'+
        '<td style="color:var(--text-secondary);">'+formatDate(p.date_publication)+'</td>'+
        '<td><span class="badge-like">'+(p.nb_likes||0)+'</span></td>'+
        '<td><span class="badge-comm" onclick="viewPost('+p.id_poste+')">'+(p.nb_commentaires||0)+'</span></td>'+
        '<td><div class="actions">'+
          '<button class="btn btn-outline btn-sm" onclick="viewPost('+p.id_poste+')" title="Voir"><i class="bi bi-eye"></i></button>'+
          '<button class="btn btn-outline btn-sm" onclick="editPost('+p.id_poste+')" title="Modifier"><i class="bi bi-pencil"></i></button>'+
          '<button class="btn btn-danger btn-sm" onclick="openDeleteModal('+p.id_poste+')" title="Supprimer"><i class="bi bi-trash3"></i></button>'+
        '</div></td>'+
      '</tr>';
    }).join('');
  }
  const start=total===0?0:(currentPage-1)*perPage+1;
  const end=Math.min(currentPage*perPage,total);
  document.getElementById('paginationInfo').textContent='Affichage '+start+'–'+end+' sur '+total+' poste'+(total>1?'s':'');
  document.getElementById('paginationBtns').innerHTML=
    '<button class="page-btn" onclick="goPage('+(currentPage-1)+')"'+(currentPage<=1?'disabled':'')+'><i class="bi bi-chevron-left"></i></button>'+
    Array.from({length:pages},(_,i)=>'<button class="page-btn'+(i+1===currentPage?' active':'')+'" onclick="goPage('+(i+1)+')">'+(i+1)+'</button>').join('')+
    '<button class="page-btn" onclick="goPage('+(currentPage+1)+')"'+(currentPage>=pages?'disabled':'')+'><i class="bi bi-chevron-right"></i></button>';
  updateStats();
}
function goPage(p){currentPage=p;render();}

function updateStats(){
  document.getElementById('statTotal').textContent=posts.length;
  document.getElementById('statLikes').textContent=posts.reduce((s,p)=>s+(p.nb_likes||0),0);
  document.getElementById('statComments').textContent=posts.reduce((s,p)=>s+(p.nb_commentaires||0),0);
  document.getElementById('statAgencies').textContent=agencies.length;
}

// CRUD
function openCreateModal(){
  editingId=null;
  document.getElementById('modalCrudTitle').textContent='Ajouter un poste';
  document.getElementById('fContenu').value='';
  document.getElementById('fAuteur').value='';
  document.getElementById('fAgence').value='';
  document.getElementById('fDate').value=new Date().toISOString().split('T')[0];
  document.querySelectorAll('#modalCrud .form-error').forEach(e=>e.textContent='');
  openModal('modalCrud');
}
function editPost(id){
  const p=posts.find(x=>Number(x.id_poste)===Number(id)); if(!p)return;
  editingId=id;
  document.getElementById('modalCrudTitle').textContent='Modifier le poste';
  document.getElementById('fContenu').value=p.contenu||'';
  document.getElementById('fAuteur').value=p.auteur||'';
  document.getElementById('fAgence').value=p.id_agence||'';
  document.getElementById('fDate').value=p.date_publication?String(p.date_publication).substring(0,10):'';
  document.querySelectorAll('#modalCrud .form-error').forEach(e=>e.textContent='');
  openModal('modalCrud');
}
async function savePost(){
  const contenu=document.getElementById('fContenu').value.trim();
  const auteur=document.getElementById('fAuteur').value.trim();
  const agence=document.getElementById('fAgence').value;
  const date=document.getElementById('fDate').value;
  let ok=true;
  if(!contenu){document.getElementById('errContenu').textContent='Veuillez remplir le contenu.';ok=false;}else document.getElementById('errContenu').textContent='';
  if(!auteur){document.getElementById('errAuteur').textContent='Veuillez remplir l\'auteur.';ok=false;}
  else if(/\d/.test(auteur)){document.getElementById('errAuteur').textContent='L\'auteur ne doit pas contenir de chiffres.';ok=false;}
  else document.getElementById('errAuteur').textContent='';
  if(!agence){document.getElementById('errAgence').textContent='Veuillez choisir une agence.';ok=false;}else document.getElementById('errAgence').textContent='';
  if(!date){document.getElementById('errDate').textContent='Veuillez choisir une date.';ok=false;}else document.getElementById('errDate').textContent='';
  if(!ok)return;
  const btn=document.getElementById('btnSavePost');
  btn.innerHTML='<i class="bi bi-arrow-repeat spin"></i> Enregistrement...';btn.disabled=true;
  try {
    await apiPost(API_SAVE,{id_poste:editingId,contenu,auteur,id_agence:parseInt(agence,10),date_publication:date});
    closeModal('modalCrud'); await loadPosts(); showToast('Poste enregistré.','success');
  } catch(err){
    if(err.errors){
      if(err.errors.contenu)document.getElementById('errContenu').textContent=err.errors.contenu;
      if(err.errors.auteur)document.getElementById('errAuteur').textContent=err.errors.auteur;
      if(err.errors.id_agence)document.getElementById('errAgence').textContent=err.errors.id_agence;
      if(err.errors.date_publication)document.getElementById('errDate').textContent=err.errors.date_publication;
    }
    showToast(err.message||'Erreur enregistrement.','danger');
  }
  btn.innerHTML='<i class="bi bi-save"></i> Enregistrer';btn.disabled=false;
}

// DETAIL
function viewPost(id){
  currentDetailPostId=id;
  const p=posts.find(x=>Number(x.id_poste)===Number(id)); if(!p)return;
  const agName = escapeHtml(p.nom_agence||p.agence||'—');
  const noteStr = (p.note && p.note >= 1 && p.note <= 5) ? p.note+'/5' : '—';
  const starsHtml = (p.note && p.note >= 1 && p.note <= 5)
    ? Array.from({length:5},(_,i)=> '<i class="bi bi-star'+(i<p.note?'-fill':'')+'"></i>').join('')
    : '<span style="font-size:12px;color:var(--text-secondary);">Non noté</span>';

  let html =
    /* ── Hero header ── */
    '<div class="detail-hero">'+
      '<div class="detail-hero-top">'+
        '<div class="detail-hero-icon"><i class="bi bi-megaphone"></i></div>'+
        '<div>'+
          '<div class="detail-hero-title">'+escapeHtml(p.auteur||'Auteur inconnu')+'</div>'+
          '<div class="detail-hero-sub">'+
            '<i class="bi bi-hash"></i> Poste #'+p.id_poste+
            ' &nbsp;·&nbsp; <i class="bi bi-building"></i> '+agName+
            ' &nbsp;·&nbsp; <i class="bi bi-calendar3"></i> '+formatDate(p.date_publication)+
          '</div>'+
        '</div>'+
      '</div>'+
      /* Stats chips */
      '<div class="detail-stats-row">'+
        '<div class="detail-stat-chip likes">'+
          '<div class="chip-value"><i class="bi bi-heart-fill" style="font-size:13px;"></i> '+(p.nb_likes||0)+'</div>'+
          '<div class="chip-label">J\'aime</div>'+
        '</div>'+
        '<div class="detail-stat-chip comments">'+
          '<div class="chip-value"><i class="bi bi-chat-dots-fill" style="font-size:13px;"></i> '+(p.nb_commentaires||0)+'</div>'+
          '<div class="chip-label">Commentaires</div>'+
        '</div>'+
        '<div class="detail-stat-chip note">'+
          '<div class="chip-value" style="font-size:14px;">'+starsHtml+'</div>'+
          '<div class="chip-label">Note</div>'+
        '</div>'+
      '</div>'+
    '</div>'+

    /* ── Contenu ── */
    '<div class="detail-section">'+
      '<div class="detail-section-title"><i class="bi bi-chat-left-text"></i> Contenu de la publication</div>'+
      '<div class="detail-content-box">'+escapeHtml(p.contenu||'Aucun contenu.')+'</div>'+
    '</div>'+

    '<div class="detail-divider"></div>'+

    /* ── Infos ── */
    '<div class="detail-section">'+
      '<div class="detail-section-title"><i class="bi bi-info-circle"></i> Informations</div>'+
      '<div class="detail-info-grid">'+
        '<div class="detail-info-item">'+
          '<div class="info-label"><i class="bi bi-person"></i> Auteur</div>'+
          '<div class="info-value">'+escapeHtml(p.auteur||'—')+'</div>'+
        '</div>'+
        '<div class="detail-info-item">'+
          '<div class="info-label"><i class="bi bi-building"></i> Agence</div>'+
          '<div class="info-value" style="color:var(--accent);">'+agName+'</div>'+
        '</div>'+
        '<div class="detail-info-item">'+
          '<div class="info-label"><i class="bi bi-calendar3"></i> Date de publication</div>'+
          '<div class="info-value">'+formatDate(p.date_publication)+'</div>'+
        '</div>'+
        '<div class="detail-info-item">'+
          '<div class="info-label"><i class="bi bi-hash"></i> ID du poste</div>'+
          '<div class="info-value" style="font-family:monospace;color:var(--accent);">#'+p.id_poste+'</div>'+
        '</div>'+
      '</div>'+
    '</div>' +

    /* ── Commentaires section ── */
    '<div class="detail-divider"></div>'+
    '<div class="detail-section">'+
      '<div class="detail-section-title" style="cursor:pointer;" onclick="loadComments('+p.id_poste+')">'+
        '<i class="bi bi-chat-dots"></i> Commentaires <span style="color:var(--accent);font-size:12px;text-transform:none;letter-spacing:0;">('+(p.nb_commentaires||0)+')</span>'+
        ' <i class="bi bi-chevron-down" id="commentsChevron" style="margin-left:auto;font-size:12px;transition:transform 0.3s;"></i>'+
      '</div>'+
      '<div id="commentsContainer" style="display:none;"></div>'+
    '</div>';

  document.getElementById('modalDetailBody').innerHTML=html;
  openModal('modalDetail');

  // Make the Commentaires stat chip clickable too
  const commChip = document.querySelector('.detail-stat-chip.comments');
  if(commChip) commChip.style.cursor='pointer';
  if(commChip) commChip.onclick = ()=> loadComments(p.id_poste);
}

// COMMENTS
async function loadComments(idPoste){
  const container = document.getElementById('commentsContainer');
  const chevron = document.getElementById('commentsChevron');
  if(!container) return;

  // Toggle visibility if already loaded
  if(container.dataset.loaded === 'true'){
    const isHidden = container.style.display === 'none';
    container.style.display = isHidden ? 'block' : 'none';
    if(chevron) chevron.style.transform = isHidden ? 'rotate(180deg)' : '';
    return;
  }

  // Show loading
  container.style.display = 'block';
  if(chevron) chevron.style.transform = 'rotate(180deg)';
  container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-secondary);"><i class="bi bi-arrow-repeat spin" style="font-size:18px;"></i><div style="margin-top:6px;font-size:13px;">Chargement des commentaires...</div></div>';

  try {
    const r = await fetch('get_commentaires.php?id_poste='+idPoste+'&_='+Date.now());
    const j = await r.json();
    if(j.success){
      const cmts = j.data || [];
      if(cmts.length === 0){
        container.innerHTML = '<div style="text-align:center;padding:24px;color:var(--text-secondary);"><i class="bi bi-chat" style="font-size:28px;display:block;margin-bottom:8px;opacity:0.3;"></i><span style="font-size:13px;">Aucun commentaire pour ce poste.</span></div>';
      } else {
        container.innerHTML = cmts.map(c => renderComment(c)).join('');
      }
      container.dataset.loaded = 'true';
    } else {
      container.innerHTML = '<div style="padding:16px;color:var(--danger);font-size:13px;"><i class="bi bi-exclamation-triangle"></i> '+escapeHtml(j.message||'Erreur')+'</div>';
    }
  } catch(e){
    container.innerHTML = '<div style="padding:16px;color:var(--danger);font-size:13px;"><i class="bi bi-exclamation-triangle"></i> Impossible de charger les commentaires.</div>';
  }
}

function renderComment(c){
  const isHidden = Number(c.hidden) === 1;
  const hiddenClass = isHidden ? ' cmnt-hidden' : '';
  const btnIcon = isHidden ? 'eye' : 'eye-slash';
  const btnLabel = isHidden ? 'Afficher' : 'Masquer';
  const btnColor = isHidden ? 'var(--success)' : 'var(--warning)';
  const dateStr = c.date_commentaire ? new Date(c.date_commentaire).toLocaleDateString('fr-FR',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '—';

  return '<div class="cmnt-card'+hiddenClass+'" id="cmnt-'+c.id_commentaire+'">'+
    '<div class="cmnt-top">'+
      '<div class="cmnt-avatar">'+escapeHtml((c.auteur_nom||'?').charAt(0).toUpperCase())+'</div>'+
      '<div style="flex:1;min-width:0;">'+
        '<div class="cmnt-author">'+escapeHtml(c.auteur_nom||'Client #'+c.id_client)+'</div>'+
        '<div class="cmnt-date"><i class="bi bi-clock"></i> '+dateStr+'</div>'+
      '</div>'+
      (isHidden ? '<span class="cmnt-hidden-badge"><i class="bi bi-eye-slash"></i> Masqué</span>' : '')+
      '<button class="cmnt-toggle-btn" onclick="toggleComment('+c.id_commentaire+')" style="--btn-color:'+btnColor+';" title="'+btnLabel+' dans le Front-Office">'+
        '<i class="bi bi-'+btnIcon+'"></i>'+
      '</button>'+
    '</div>'+
    '<div class="cmnt-body">'+escapeHtml(c.contenu)+'</div>'+
  '</div>';
}

async function toggleComment(idCmnt){
  const card = document.getElementById('cmnt-'+idCmnt);
  const btn = card ? card.querySelector('.cmnt-toggle-btn') : null;
  if(btn){ btn.innerHTML='<i class="bi bi-arrow-repeat spin"></i>'; btn.disabled=true; }
  try {
    const r = await fetch('toggle_commentaire.php',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({id_commentaire:idCmnt})
    });
    const j = await r.json();
    if(j.success){
      const isNowHidden = Number(j.hidden) === 1;
      if(card){
        if(isNowHidden) card.classList.add('cmnt-hidden');
        else card.classList.remove('cmnt-hidden');
      }
      if(btn){
        btn.style.setProperty('--btn-color', isNowHidden ? 'var(--success)' : 'var(--warning)');
        btn.innerHTML = '<i class="bi bi-'+(isNowHidden?'eye':'eye-slash')+'"></i>';
        btn.title = isNowHidden ? 'Afficher dans le Front-Office' : 'Masquer dans le Front-Office';
      }
      // Update hidden badge
      const existingBadge = card ? card.querySelector('.cmnt-hidden-badge') : null;
      if(isNowHidden && !existingBadge){
        const badgeSpan = document.createElement('span');
        badgeSpan.className = 'cmnt-hidden-badge';
        badgeSpan.innerHTML = '<i class="bi bi-eye-slash"></i> Masqué';
        btn.parentElement.insertBefore(badgeSpan, btn);
      } else if(!isNowHidden && existingBadge){
        existingBadge.remove();
      }
      showToast(j.message, isNowHidden ? 'warning' : 'success');
    } else {
      showToast(j.message||'Erreur toggle.','danger');
    }
  } catch(e){
    showToast('Erreur réseau.','danger');
  }
  if(btn) btn.disabled=false;
}

// DELETE
function openDeleteModal(id){
  deletingId=id;
  document.getElementById('deleteMsg').textContent='Vous êtes sur le point de supprimer le poste #'+id+'. Cette action est irréversible.';
  openModal('modalDelete');
}
async function confirmDelete(){
  try {
    await apiPost(API_DELETE,{id_poste:deletingId});
    closeModal('modalDelete');
    posts=posts.filter(x=>Number(x.id_poste)!==Number(deletingId));
    render(); showToast('Poste supprimé.','danger');
  } catch(err){showToast(err.message||'Erreur suppression.','danger');}
}

// EVENTS
document.getElementById('searchInput').addEventListener('input',()=>{currentPage=1;render();});
document.getElementById('filterAgency').addEventListener('change',()=>{currentPage=1;render();});
document.getElementById('filterSort').addEventListener('change',()=>{currentPage=1;render();});
document.getElementById('btnSavePost').addEventListener('click',savePost);

document.addEventListener('DOMContentLoaded',async function(){await loadAgencies();await loadPosts();});
</script>

</body>
</html>
