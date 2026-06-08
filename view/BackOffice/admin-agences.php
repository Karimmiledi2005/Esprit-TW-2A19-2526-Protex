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
  <title>Agences — Protex Admin</title>
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/admin-users.css">
    <link rel="stylesheet" href="assets/css/animations.css">
</head>
  <style>
    .badge-posts   { background:rgba(46,196,182,0.15);color:var(--success);border:1px solid rgba(46,196,182,0.3);font-size:11px;font-weight:600;padding:4px 10px;border-radius:20px; }
    .badge-reviews { background:rgba(244,162,97,0.15);color:var(--gold);border:1px solid rgba(244,162,97,0.3);font-size:11px;font-weight:600;padding:4px 10px;border-radius:20px; }
    .badge-note    { background:rgba(0,180,216,0.15);color:var(--accent);border:1px solid rgba(0,180,216,0.3);font-size:11px;font-weight:600;padding:4px 10px;border-radius:20px; }

    .review-item {
      background:var(--glass-bg);border:1px solid var(--glass-border);
      border-radius:var(--radius-sm);padding:10px 12px;margin-top:6px;
    }
    .review-head { display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:4px; }
    .review-author { font-size:13px;font-weight:600;color:#fff; }
    .review-date { font-size:11px;color:var(--text-secondary); }
    .review-stars { color:#f4a261;font-size:14px;margin-bottom:4px; }
    .review-text { font-size:13px;color:var(--text-primary); }

    .toolbar-inner {
      padding:16px 24px;
      border-bottom:1px solid var(--glass-border);
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
        <div class="topbar-title">Gestion des agences</div>
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
          <div class="page-title">Agences</div>
          <div class="page-breadcrumb">
            <i class="bi bi-house"></i>
            <a href="#">Accueil</a>
            <i class="bi bi-chevron-right" style="font-size:10px;"></i>
            <span>Agences</span>
          </div>
        </div>
      </div>

      <!-- STATS -->
      <div class="stats-grid">
        <div class="stat-card blue">
          <div class="stat-icon"><i class="bi bi-building"></i></div>
          <div class="stat-value" id="statTotal">0</div>
          <div class="stat-label">Total agences</div>
          <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> Actives</div>
        </div>
        <div class="stat-card gold">
          <div class="stat-icon"><i class="bi bi-star"></i></div>
          <div class="stat-value" id="statNote">0.0</div>
          <div class="stat-label">Note moyenne</div>
          <div class="stat-trend trend-warn"><i class="bi bi-star"></i> Sur 5</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon"><i class="bi bi-chat-left-text"></i></div>
          <div class="stat-value" id="statReviews">0</div>
          <div class="stat-label">Total avis</div>
          <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> Commentaires</div>
        </div>
        <div class="stat-card red">
          <div class="stat-icon"><i class="bi bi-megaphone"></i></div>
          <div class="stat-value" id="statPosts">0</div>
          <div class="stat-label">Posts liés</div>
          <div class="stat-trend trend-down"><i class="bi bi-file-text"></i> Publications</div>
        </div>
      </div>

      <!-- TABLE CARD -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <i class="bi bi-table"></i> Liste des agences
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
              <input type="text" id="searchInput" placeholder="Rechercher par nom, pays, email, téléphone...">
            </div>
            <button class="btn btn-outline btn-sm" onclick="resetFilters()">
              <i class="bi bi-x-circle"></i> Réinitialiser
            </button>
          </div>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Pays</th>
                <th>Téléphone</th>
                <th>Email</th>
                <th>Statut</th>
                <th>Posts</th>
                <th>Avis</th>
                <th>Note</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="agenciesBody"></tbody>
          </table>
          <div id="emptyState" style="display:none;text-align:center;padding:48px 20px;color:var(--text-secondary);">
            <i class="bi bi-building-slash" style="font-size:36px;display:block;margin-bottom:10px;opacity:0.3;"></i>
            <p style="font-size:14px;">Aucune agence trouvée</p>
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
      <div class="modal-title"><i class="bi bi-geo-alt"></i> <span id="modalCrudTitle">Ajouter une agence</span></div>
      <button class="modal-close" onclick="closeModal('modalCrud')"><i class="bi bi-x"></i></button>
    </div>
    <div style="padding:0 32px 32px;">
      <div class="form-group">
        <label>Nom de l'agence</label>
        <input type="text" id="fNom" class="form-control" placeholder="Nom de l'agence">
        <div class="form-error" id="errNom"></div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Pays</label>
          <input type="text" id="fPays" class="form-control" placeholder="Ex: Tunisie">
          <div class="form-error" id="errPays"></div>
        </div>
        <div class="form-group">
          <label>Téléphone</label>
          <input type="text" id="fTel" class="form-control" placeholder="Ex: 71111222">
          <div class="form-error" id="errTel"></div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Email</label>
          <input type="email" id="fEmail" class="form-control" placeholder="agence@protex.tn">
          <div class="form-error" id="errEmail"></div>
        </div>
        <div class="form-group">
          <label>Statut</label>
          <select id="fStatut" class="form-control">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Adresse</label>
        <textarea id="fAdresse" class="form-control" placeholder="Adresse complète..."></textarea>
      </div>
      <!-- A3: Opening Hours -->
      <div style="margin-top:12px;">
        <div onclick="toggleHoraires()" style="cursor:pointer;display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:var(--accent);">
          <i class="bi bi-clock" id="horairesIcon"></i> <span id="horairesToggle">Définir les horaires d'ouverture</span>
        </div>
        <div id="horairesEditor" style="display:none;margin-top:8px;padding:12px;background:rgba(255,255,255,.03);border-radius:8px;border:1px solid var(--glass-border);">
          <?php $dayNames = ['', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche']; ?>
          <?php for ($d = 1; $d <= 7; $d++): ?>
          <div style="display:flex;align-items:center;gap:8px;padding:4px 0;">
            <span style="width:90px;font-size:13px;font-weight:500;"><?php echo $dayNames[$d]; ?></span>
            <input type="checkbox" id="hFerme<?php echo $d; ?>" onchange="toggleDay(<?php echo $d; ?>)"> <label for="hFerme<?php echo $d; ?>" style="font-size:12px;">Fermé</label>
            <input type="time" id="hOuverture<?php echo $d; ?>" value="09:00" style="background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:6px;color:var(--text-primary);padding:4px 8px;font-size:13px;">
            <span style="font-size:12px;color:var(--text-secondary);">—</span>
            <input type="time" id="hFermeture<?php echo $d; ?>" value="17:00" style="background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:6px;color:var(--text-primary);padding:4px 8px;font-size:13px;">
          </div>
          <?php endfor; ?>
          <div style="margin-top:8px;">
            <button type="button" class="btn btn-sm btn-outline" onclick="saveHoraires()"><i class="bi bi-save"></i> Sauvegarder les horaires</button>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="margin-top:8px;">
        <button class="btn btn-outline" onclick="closeModal('modalCrud')">Annuler</button>
        <button class="btn btn-primary" id="btnSaveAgency"><i class="bi bi-save"></i> Enregistrer</button>
      </div>
    </div>
  </div>
</div>

<!-- ===== MODAL DÉTAIL ===== -->
<div class="modal-overlay" id="modalDetail">
  <div class="modal" style="max-width:620px;">
    <div class="modal-header">
      <div class="modal-title"><i class="bi bi-geo-alt"></i> Détails de l'agence</div>
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
    <div class="delete-title">Supprimer cette agence ?</div>
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
const API_GET    = 'get_agences.php';
const API_SAVE   = 'save_agence.php';
const API_DELETE = 'delete_agence.php';
const API_POSTS  = 'get_postes_agence.php';

let agencies = [], allPosts = [], allReviews = [], editingId = null, deletingId = null;
let currentPage = 1, perPage = 10, currentDetailAgencyId = null;

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
    const r=await fetch(API_GET+'?_='+Date.now()); const j=await r.json();
    if(j.success){agencies=j.data||j.agences||[];} else showToast('Erreur chargement agences.','danger');
  } catch(e){showToast('Impossible de charger les agences.','danger');}
}
async function loadPosts(){
  try{const r=await fetch(API_POSTS);const j=await r.json();if(j.success)allPosts=j.data||j.posts||[];}
  catch(e){console.error('Erreur posts',e);}
}
async function loadAll(){await Promise.all([loadAgencies(),loadPosts()]);currentPage=1;render();}

function getFiltered(){
  const q=document.getElementById('searchInput').value.toLowerCase();
  return agencies.filter(a=>{const txt=(a.nom_agence+' '+(a.pays||'')+' '+(a.email||'')+' '+(a.tel||'')).toLowerCase();return!q||txt.includes(q);});
}
function resetFilters(){document.getElementById('searchInput').value='';currentPage=1;render();}

function render(){
  const filtered=getFiltered(),total=filtered.length;
  const pages=Math.ceil(total/perPage)||1;
  if(currentPage>pages)currentPage=pages;
  const slice=filtered.slice((currentPage-1)*perPage,currentPage*perPage);
  const tbody=document.getElementById('agenciesBody');
  const empty=document.getElementById('emptyState');
  if(!slice.length){tbody.innerHTML='';empty.style.display='block';}
  else {
    empty.style.display='none';
    tbody.innerHTML=slice.map(a=>{
      const aPosts=(allPosts||[]).filter(p=>Number(p.id_agence)===Number(a.id_agence));
      return '<tr>'+
        '<td><span style="font-family:monospace;font-size:12px;color:var(--accent);">#'+a.id_agence+'</span></td>'+
        '<td style="color:#fff;font-weight:500;">'+escapeHtml(a.nom_agence)+'</td>'+
        '<td style="color:var(--text-secondary);">'+escapeHtml(a.pays||'—')+'</td>'+
        '<td style="color:var(--text-primary);">'+escapeHtml(a.tel||'—')+'</td>'+
        '<td style="color:var(--gold);">'+escapeHtml(a.email||'—')+'</td>'+
        '<td><span class="badge-'+(a.statut==='active'?'success':'danger')+'">'+(a.statut==='active'?'Active':'Inactive')+'</span></td>'+
        '<td><span class="badge-posts">'+aPosts.length+'</span></td>' +
        '<td><span class="badge-reviews">0</span></td>'+
        '<td><span class="badge-note">—</span></td>'+
        '<td><div class="actions">'+
          '<div class="toggle-switch-wrapper" title="'+(a.statut==='active'?'Désactiver':'Activer')+'">'+
            '<input type="checkbox" id="toggle-'+a.id_agence+'" class="toggle-switch-input" '+(a.statut==='active'?'checked':'')+' onchange="toggleStatutAgency('+a.id_agence+')">'+
            '<label for="toggle-'+a.id_agence+'" class="toggle-switch-label"></label>'+
          '</div>'+
          '<a href="agence_detail.php?id='+a.id_agence+'" class="btn btn-outline btn-sm" title="Statistiques"><i class="bi bi-bar-chart"></i></a>'+
          '<button class="btn btn-outline btn-sm" onclick="viewAgency('+a.id_agence+')" title="Voir"><i class="bi bi-eye"></i></button>'+
          '<button class="btn btn-outline btn-sm" onclick="editAgency('+a.id_agence+')" title="Modifier"><i class="bi bi-pencil"></i></button>'+
          '<button class="btn btn-danger btn-sm" onclick="openDeleteModal('+a.id_agence+')" title="Supprimer"><i class="bi bi-trash3"></i></button>'+


function updateStats(){
  document.getElementById('statTotal').textContent=agencies.length;
  document.getElementById('statNote').textContent='0.0';
  document.getElementById('statReviews').textContent='0';
  document.getElementById('statPosts').textContent=(allPosts||[]).length;
}

// TOGGLE
async function toggleStatutAgency(id) {
  const toggleInput = document.getElementById(`toggle-${id}`);
  const originalState = !toggleInput.checked;
  try {
    const res = await apiPost('toggle_agence.php', { id_agence: id });
    if (res.success) {
      const a = agencies.find(x => Number(x.id_agence) === Number(id));
      if (a) {
        a.statut = res.statut;
        // Update badge in the row
        const row = toggleInput.closest('tr');
        if (row) {
          const badgeCell = row.querySelector('td:nth-child(6)');
          if (badgeCell) {
            badgeCell.innerHTML = `<span class="badge-${a.statut==='active'?'success':'danger'}">${a.statut==='active'?'Active':'Inactive'}</span>`;
          }
        }
      }
      showToast(res.statut === 'active' ? 'Agence activée' : 'Agence désactivée', 'success');
    } else {
      showToast('Erreur : ' + res.message, 'danger');
      toggleInput.checked = originalState;
    }
  } catch (err) {
    showToast('Erreur de connexion', 'danger');
    toggleInput.checked = originalState;
  }
}

// CRUD
function openCreateModal(){
  editingId=null;
  document.getElementById('modalCrudTitle').textContent='Ajouter une agence';
  document.getElementById('fNom').value='';document.getElementById('fPays').value='';
  document.getElementById('fTel').value='';document.getElementById('fEmail').value='';
  document.getElementById('fStatut').value='active';document.getElementById('fAdresse').value='';
  document.querySelectorAll('#modalCrud .form-error').forEach(e=>e.textContent='');
  openModal('modalCrud');
}
function editAgency(id){
  const a=agencies.find(x=>Number(x.id_agence)===Number(id));if(!a)return;
  editingId=id;
  document.getElementById('modalCrudTitle').textContent='Modifier l\'agence';
  document.getElementById('fNom').value=a.nom_agence||'';document.getElementById('fPays').value=a.pays||'';
  document.getElementById('fTel').value=a.tel||'';document.getElementById('fEmail').value=a.email||'';
  document.getElementById('fStatut').value=a.statut||'active';document.getElementById('fAdresse').value=a.adresse||'';
  document.querySelectorAll('#modalCrud .form-error').forEach(e=>e.textContent='');
  openModal('modalCrud');
}
async function saveAgency(){
  const nom=document.getElementById('fNom').value.trim();
  const pays=document.getElementById('fPays').value.trim();
  const tel=document.getElementById('fTel').value.trim();
  const email=document.getElementById('fEmail').value.trim();
  const statut=document.getElementById('fStatut').value;
  const adresse=document.getElementById('fAdresse').value.trim();
  let ok=true;
  if(!nom){document.getElementById('errNom').textContent='Veuillez remplir le nom.';ok=false;}else document.getElementById('errNom').textContent='';
  if(!pays){document.getElementById('errPays').textContent='Veuillez remplir le pays.';ok=false;}else document.getElementById('errPays').textContent='';
  if(tel&&!/^\d{8}$/.test(tel)){document.getElementById('errTel').textContent='Le téléphone doit contenir 8 chiffres.';ok=false;}else document.getElementById('errTel').textContent='';
  if(!email){document.getElementById('errEmail').textContent='Veuillez remplir l\'email.';ok=false;}
  else if(email.indexOf('@protex.tn')===-1){document.getElementById('errEmail').textContent='L\'email doit contenir "@protex.tn".';ok=false;}
  else document.getElementById('errEmail').textContent='';
  if(!ok)return;
  const btn=document.getElementById('btnSaveAgency');
  btn.innerHTML='<i class="bi bi-arrow-repeat spin"></i> Enregistrement...';btn.disabled=true;
  try {
    await apiPost(API_SAVE,{id_agence:editingId,nom_agence:nom,pays,tel,email,statut,adresse});
    closeModal('modalCrud');await loadAll();showToast('Agence enregistrée.','success');
  } catch(err){
    if(err.errors){
      if(err.errors.nom_agence)document.getElementById('errNom').textContent=err.errors.nom_agence;
      if(err.errors.pays)document.getElementById('errPays').textContent=err.errors.pays;
      if(err.errors.tel)document.getElementById('errTel').textContent=err.errors.tel;
      if(err.errors.email)document.getElementById('errEmail').textContent=err.errors.email;
    }
    showToast(err.message||'Erreur enregistrement.','danger');
  }
  btn.innerHTML='<i class="bi bi-save"></i> Enregistrer';btn.disabled=false;
}

// DETAIL
function viewAgency(id){
  currentDetailAgencyId=id;
  const a=agencies.find(x=>Number(x.id_agence)===Number(id));if(!a)return;
  const aPosts=(allPosts||[]).filter(p=>Number(p.id_agence)===Number(a.id_agence));
  let html='<div class="sinistre-modal-header">'+
    '<div class="sinistre-modal-icon"><i class="bi bi-geo-alt"></i></div>'+
    '<div style="flex:1;"><div class="sinistre-modal-type">'+escapeHtml(a.nom_agence)+'</div><div class="sinistre-modal-id">Agence #'+a.id_agence+' · '+escapeHtml(a.pays||'')+'</div></div>'+
    '</div>'+
  '<div class="detail-grid">'+
    '<div class="detail-field"><div class="detail-field-label"><i class="bi bi-hash"></i> ID</div><div class="detail-field-value" style="font-family:monospace;color:var(--accent);">#'+a.id_agence+'</div></div>'+
    '<div class="detail-field"><div class="detail-field-label"><i class="bi bi-building"></i> Nom</div><div class="detail-field-value" style="color:#fff;">'+escapeHtml(a.nom_agence)+'</div></div>'+
    '<div class="detail-field"><div class="detail-field-label"><i class="bi bi-geo-alt"></i> Pays</div><div class="detail-field-value">'+escapeHtml(a.pays||'—')+'</div></div>'+
    '<div class="detail-field"><div class="detail-field-label"><i class="bi bi-telephone"></i> Téléphone</div><div class="detail-field-value">'+escapeHtml(a.tel||'—')+'</div></div>'+
    '<div class="detail-field full"><div class="detail-field-label"><i class="bi bi-envelope"></i> Email</div><div class="detail-field-value" style="color:var(--gold);">'+escapeHtml(a.email||'—')+'</div></div>'+
    '<div class="detail-field"><div class="detail-field-label"><i class="bi bi-toggle-on"></i> Statut</div><div class="detail-field-value">'+(a.statut==='active'?'Active':'Inactive')+'</div></div>'+
    '<div class="detail-field"><div class="detail-field-label"><i class="bi bi-megaphone"></i> Posts</div><div class="detail-field-value" style="color:var(--success);">'+aPosts.length+'</div></div>'+
    '<div class="detail-field full"><div class="detail-field-label"><i class="bi bi-geo"></i> Adresse</div><div class="detail-field-value">'+escapeHtml(a.adresse||'—')+'</div></div>'+
  '</div>';
  document.getElementById('modalDetailBody').innerHTML=html;
  openModal('modalDetail');
}

// DELETE
// A3: Horaires editor
function toggleHoraires() {
    var el = document.getElementById('horairesEditor');
    var toggle = document.getElementById('horairesToggle');
    var icon = document.getElementById('horairesIcon');
    if (el.style.display === 'none') {
        el.style.display = 'block';
        toggle.textContent = 'Masquer les horaires';
        icon.className = 'bi bi-clock-fill';
    } else {
        el.style.display = 'none';
        toggle.textContent = 'Définir les horaires d\'ouverture';
        icon.className = 'bi bi-clock';
    }
}
function toggleDay(d) {
    var ferme = document.getElementById('hFerme'+d).checked;
    document.getElementById('hOuverture'+d).disabled = ferme;
    document.getElementById('hFermeture'+d).disabled = ferme;
}
async function saveHoraires() {
    var id = editingId;
    if (!id) { showToast('Enregistrez d\'abord l\'agence.','warning'); return; }
    var fd = new FormData();
    fd.append('action', 'save_agence_horaires');
    fd.append('id_agence', id);
    for (var d = 1; d <= 7; d++) {
        fd.append('jour_'+d+'_ferme', document.getElementById('hFerme'+d).checked ? '1' : '0');
        fd.append('jour_'+d+'_ouverture', document.getElementById('hOuverture'+d).value);
        fd.append('jour_'+d+'_fermeture', document.getElementById('hFermeture'+d).value);
    }
    try {
        var res = await fetch('/api.php', { method:'POST', body:fd });
        var data = await res.json();
        if (data.success) { showToast('Horaires enregistrés.','success'); }
        else { showToast('Erreur','danger'); }
    } catch(e) { showToast('Erreur réseau','danger'); }
}
function openDeleteModal(id){
  deletingId=id;
  document.getElementById('deleteMsg').textContent='Vous êtes sur le point de supprimer l\'agence #'+id+'. Cette action est irréversible.';
  openModal('modalDelete');
}
async function confirmDelete(){
  try {
    await apiPost(API_DELETE,{id_agence:deletingId});
    closeModal('modalDelete');
    agencies=agencies.filter(x=>Number(x.id_agence)!==Number(deletingId));
    render(); showToast('Agence supprimée.','danger');
  } catch(err){showToast(err.message||'Erreur suppression.','danger');}
}

// EVENTS
document.getElementById('searchInput').addEventListener('input',()=>{currentPage=1;render();});
document.getElementById('btnSaveAgency').addEventListener('click',saveAgency);

document.addEventListener('DOMContentLoaded',async function(){await loadAll();});
</script>

</body>
</html>
