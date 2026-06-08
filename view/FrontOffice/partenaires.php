<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();
$userId = (int)$_SESSION['user_id'];
$pageTitle = 'Partenaires';
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Partenaires — Protex</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">
<meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
<link rel="stylesheet" href="assets/css/variables.css">
<link rel="stylesheet" href="assets/css/base.css">
<link rel="stylesheet" href="assets/css/layout.css">
<link rel="stylesheet" href="assets/css/light-theme.css">
<link rel="stylesheet" href="assets/css/client.css">
<link rel="stylesheet" href="assets/css/validation.css">
<link rel="stylesheet" href="assets/css/animations.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
.content-layout{display:grid;grid-template-columns:1fr 380px;gap:1.25rem;align-items:start}
@media(max-width:900px){.content-layout{grid-template-columns:1fr}}
.partners-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:.75rem;align-content:start}
.partner-card{background:#fff;border:1px solid rgba(21,35,60,.08);border-radius:14px;padding:1rem;transition:all .25s;position:relative;overflow:hidden;cursor:pointer;box-shadow:0 2px 12px rgba(0,0,0,.04)}
.partner-card:hover{border-color:rgba(0,180,216,.3);transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.06)}
.pc-header{display:flex;align-items:center;gap:10px;margin-bottom:.75rem}
.pc-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.pc-icon.garage{background:rgba(0,180,216,.1);color:var(--accent,#00b4d8)}
.pc-icon.clinique{background:rgba(230,57,70,.1);color:#e63946}
.pc-icon.pharmacie{background:rgba(34,197,94,.1);color:#22c55e}
.pc-icon.hotel{background:rgba(245,158,11,.1);color:#f59e0b}
.pc-icon.avocat{background:rgba(124,58,237,.1);color:#7c3aed}
.pc-icon.serrurier{background:rgba(255,107,26,.1);color:#FF6B1A}
.pc-icon.location_voiture{background:rgba(0,180,216,.1);color:var(--accent,#00b4d8)}
.pc-name{font-size:13.5px;font-weight:600;color:#15233C}
.pc-location{font-size:12px;color:rgba(21,35,60,.5);display:flex;align-items:center;gap:4px}
.pc-avantage{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.15);border-radius:99px;padding:3px 10px;font-size:11.5px;color:var(--success,#22c55e);display:inline-flex;align-items:center;gap:4px;margin:.5rem 0}
.pc-note{display:flex;align-items:center;gap:3px;font-size:12px;color:var(--gold,#f4a261);margin-top:.3rem}
.pc-horaires{font-size:11.5px;color:rgba(21,35,60,.45);display:flex;align-items:center;gap:5px;margin-top:.4rem}
.pc-actions{display:flex;gap:6px;margin-top:.75rem}
.pc-btn{flex:1;padding:7px;border-radius:8px;border:none;font-size:12px;font-weight:500;cursor:pointer;font-family:var(--font-body,'DM Sans',sans-serif);transition:all .15s;background:#f0f4f8;color:#15233C;display:flex;align-items:center;justify-content:center;gap:5px}
.pc-btn:hover{background:#e2e8f0}
.pc-btn.primary{background:rgba(0,180,216,.12);color:var(--accent,#00b4d8);border:1px solid rgba(0,180,216,.2)}
.pc-btn.primary:hover{background:rgba(0,180,216,.2)}
.map-panel{position:sticky;top:100px;height:calc(100vh - 160px);background:#fff;border-radius:14px;border:1px solid rgba(21,35,60,.08);overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.04)}
#ptx-map{width:100%;height:100%}
.type-pills{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:1rem}
.type-pill{padding:5px 14px;border-radius:99px;border:1px solid rgba(21,35,60,.1);background:#f8fafd;color:rgba(21,35,60,.6);font-size:12.5px;cursor:pointer;transition:all .18s;font-family:var(--font-body,'DM Sans',sans-serif)}
.type-pill:hover,.type-pill.active{background:rgba(0,180,216,.1);color:var(--accent,#00b4d8);border-color:rgba(0,180,216,.25)}
.filters-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:1rem;align-items:center}
.filter-group{display:flex;align-items:center;gap:6px;background:#f8fafd;border:1px solid rgba(21,35,60,.08);border-radius:99px;padding:5px 14px}
.filter-group i{color:rgba(21,35,60,.4);font-size:13px}
.filter-group select,.filter-group input{background:transparent;border:none;color:#15233C;font-size:13px;outline:none;font-family:var(--font-body,'DM Sans',sans-serif)}
.filter-group select option{background:#fff;color:#15233C}
.filter-group input::placeholder{color:rgba(21,35,60,.35)}
.btn-reset{padding:6px 16px;border-radius:99px;border:1px solid rgba(21,35,60,.1);font-size:12.5px;cursor:pointer;background:var(--accent,#00b4d8);color:#fff;font-family:var(--font-body,'DM Sans',sans-serif);font-weight:500;display:flex;align-items:center;gap:5px;transition:all .15s}
.btn-reset:hover{opacity:.85}
.loading-grid{display:flex;align-items:center;justify-content:center;height:200px;flex-direction:column;gap:10px;color:rgba(21,35,60,.45);font-size:13px}
.empty-state{text-align:center;padding:3rem 1rem;color:rgba(21,35,60,.45)}
.empty-icon{font-size:40px;opacity:.3;margin-bottom:.75rem}
.ptx-toast-c{position:fixed;top:1rem;right:1rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem}
.ptx-toast{background:#fff;border:1px solid rgba(21,35,60,.1);border-radius:10px;padding:.65rem 1rem;font-size:13px;color:#15233C;display:flex;align-items:center;gap:8px;box-shadow:0 8px 24px rgba(0,0,0,.08);animation:toast-in .3s ease both;min-width:220px;border-left:3px solid var(--success,#22c55e)}
@keyframes toast-in{from{opacity:0;transform:translateX(10px)}to{opacity:1;transform:none}}
</style>
</head>
<body>

<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">

<?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

<main class="main">
<div class="page-header">
    <div>
        <div class="page-title-main">Partenaires</div>
        <div class="page-breadcrumb">
            <i class="bi bi-house"></i>
            <a href="client.php">Accueil</a>
            <i class="bi bi-chevron-right"></i>
            <span>Partenaires</span>
        </div>
    </div>
</div>

<div class="content">

<div class="filters-row">
  <div class="filter-group">
    <i class="bi bi-search"></i>
    <input type="text" id="searchInput" placeholder="Rechercher un partenaire…" oninput="debounceFilter()">
  </div>
  <div class="filter-group">
    <i class="bi bi-geo-alt"></i>
    <select id="villeSelect" onchange="filterPartners()">
      <option value="">Toutes les villes</option>
    </select>
  </div>
  <button class="btn-reset" onclick="resetFilters()">
    <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
  </button>
</div>

<div class="type-pills">
  <button class="type-pill active" data-type="" onclick="setType(this,'')">Tous</button>
  <button class="type-pill" data-type="garage" onclick="setType(this,'garage')">Garages</button>
  <button class="type-pill" data-type="clinique" onclick="setType(this,'clinique')">Cliniques</button>
  <button class="type-pill" data-type="pharmacie" onclick="setType(this,'pharmacie')">Pharmacies</button>
  <button class="type-pill" data-type="hotel" onclick="setType(this,'hotel')">Hôtels</button>
  <button class="type-pill" data-type="avocat" onclick="setType(this,'avocat')">Avocats</button>
  <button class="type-pill" data-type="location_voiture" onclick="setType(this,'location_voiture')">Location</button>
</div>

<div class="content-layout">
  <div>
    <div id="partnersGrid" class="partners-grid">
      <div class="loading-grid"><i class="bi bi-arrow-repeat spin"></i>Chargement des partenaires…</div>
    </div>
  </div>
  <div class="map-panel">
    <div id="ptx-map"></div>
  </div>
</div>

</div>
</main>
</div>

<div class="ptx-toast-c" id="toastContainer"></div>

<script>
let allPartners = [];
let currentType = '';
let mapInstance = null;
let markers     = [];
let debounceTimer;

const TYPE_ICONS = {
  garage:'', clinique:'', pharmacie:'',
  hotel:'', avocat:'', serrurier:'',
  location_voiture:'', telemedicine:'', autre:''
};

document.addEventListener('DOMContentLoaded', () => {
  initMap();
  loadPartners();
});

function initMap() {
  mapInstance = L.map('ptx-map', {zoomControl:true}).setView([34.0, 9.5], 6);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution:'© OpenStreetMap', maxZoom:18
  }).addTo(mapInstance);
}

async function loadPartners() {
  document.getElementById('partnersGrid').innerHTML =
    '<div class="loading-grid"><i class="bi bi-arrow-repeat spin"></i>Chargement…</div>';
  try {
    const r = await fetch('../../api.php?action=partenaires_list');
    const d = await r.json();
    if (!d.success) throw new Error();
    allPartners = d.partenaires;
    populateVilles(d.villes);
    renderPartners(allPartners);
    renderMapMarkers(allPartners);
  } catch {
    document.getElementById('partnersGrid').innerHTML =
      '<div class="loading-grid"><div class="empty-icon"><i class="bi bi-exclamation-circle"></i></div>Erreur de chargement.</div>';
  }
}

function populateVilles(villes) {
  const sel = document.getElementById('villeSelect');
  villes.forEach(v => {
    const opt = document.createElement('option');
    opt.value = opt.textContent = v;
    sel.appendChild(opt);
  });
}

function renderPartners(list) {
  const grid = document.getElementById('partnersGrid');
  if (!list.length) {
    grid.innerHTML = '<div class="empty-state"><div class="empty-icon"><i class="bi bi-building-slash"></i></div><p>Aucun partenaire trouvé</p></div>';
    return;
  }
  const iconMap = {garage:'wrench',clinique:'heart-pulse',pharmacie:'capsule',hotel:'building',avocat:'briefcase',serrurier:'key',location_voiture:'car-front',telemedicine:'phone',autre:'geo-alt'};
  grid.innerHTML = list.map(p => {
    const stars = '★'.repeat(Math.round(p.note_calculee||0)) + '☆'.repeat(5-Math.round(p.note_calculee||0));
    const ic = iconMap[p.type] || 'geo-alt';
    return `
      <div class="partner-card" onclick="focusOnMap(${p.id_partenaire})" id="pc-${p.id_partenaire}">
        <div class="pc-header">
          <div class="pc-icon ${p.type}"><i class="bi bi-${ic}"></i></div>
          <div>
            <div class="pc-name">${escHtml(p.nom)}</div>
            <div class="pc-location"><i class="bi bi-geo-alt"></i>${escHtml(p.ville)}</div>
          </div>
        </div>
        ${p.avantage ? `<div class="pc-avantage"><i class="bi bi-tag"></i>${escHtml(p.avantage)}</div>` : ''}
        <div class="pc-horaires"><i class="bi bi-clock"></i>${escHtml(p.horaires||'')}</div>
        ${p.note_calculee > 0 ? `<div class="pc-note"><span>${stars}</span> <span style="color:rgba(21,35,60,.45)">(${p.nb_avis_count} avis)</span></div>` : ''}
        <div class="pc-actions">
          ${p.telephone ? `<button class="pc-btn primary" onclick="callPartner(event,'${escHtml(p.telephone)}')"><i class="bi bi-telephone"></i>Appeler</button>` : ''}
          <button class="pc-btn" onclick="showDetails(event,${p.id_partenaire})"><i class="bi bi-info-circle"></i>Détails</button>
          <button class="pc-btn" onclick="logUsage(event,${p.id_partenaire},'${escHtml(p.nom)}')"><i class="bi bi-arrow-right-circle"></i>Utiliser</button>
        </div>
      </div>
    `;
  }).join('');
}

function renderMapMarkers(list) {
  markers.forEach(m => m.remove());
  markers = [];
  const typeColors = {
    garage:'#00b4d8', clinique:'#e63946', pharmacie:'#22c55e',
    hotel:'#f59e0b', avocat:'#7c3aed', serrurier:'#FF6B1A',
    location_voiture:'#00b4d8', default:'#94a3b8'
  };
  const iconMap = {garage:'wrench',clinique:'heart-pulse',pharmacie:'capsule',hotel:'building',avocat:'briefcase',serrurier:'key',location_voiture:'car-front',telemedicine:'phone',autre:'geo-alt'};
  list.forEach(p => {
    if (!p.latitude || !p.longitude) return;
    const color = typeColors[p.type] || typeColors.default;
    const ic = iconMap[p.type] || 'geo-alt';
    const marker = L.marker([p.latitude, p.longitude], {
      icon: L.divIcon({
        className: '',
        html: `<div style="width:36px;height:36px;border-radius:50%;background:${color};border:2.5px solid #fff;display:flex;align-items:center;justify-content:center;font-size:16px;color:#fff;box-shadow:0 2px 8px rgba(0,0,0,.3);cursor:pointer"><i class="bi bi-${ic}" style="font-size:14px"></i></div>`,
        iconSize:[36,36], iconAnchor:[18,18],
      })
    })
    .addTo(mapInstance)
    .bindPopup(`
      <div style="font-family:DM Sans,sans-serif;min-width:180px">
        <strong style="font-size:13px;color:#15233C">${p.nom}</strong><br>
        <span style="font-size:11px;color:rgba(21,35,60,.55)">${p.adresse}, ${p.ville}</span><br>
        ${p.avantage ? `<span style="color:#22c55e;font-size:11.5px;font-weight:500">${p.avantage}</span><br>` : ''}
        ${p.telephone ? `<a href="tel:${p.telephone}" style="font-size:12px;color:#00b4d8">${p.telephone}</a>` : ''}
      </div>
    `);
    marker._partnerId = p.id_partenaire;
    markers.push(marker);
  });
  if (list.length) {
    const bounds = L.latLngBounds(list.filter(p => p.latitude).map(p => [p.latitude, p.longitude]));
    if (bounds.isValid()) mapInstance.fitBounds(bounds, {padding:[20,20]});
  }
}

function focusOnMap(id) {
  const marker = markers.find(m => m._partnerId === id);
  if (marker) { mapInstance.setView(marker.getLatLng(), 14); marker.openPopup(); }
}

function setType(btn, type) {
  currentType = type;
  document.querySelectorAll('.type-pill').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  filterPartners();
}

function filterPartners() {
  const type   = currentType;
  const ville  = document.getElementById('villeSelect').value;
  const search = document.getElementById('searchInput').value.toLowerCase();
  const filtered = allPartners.filter(p =>
    (!type   || p.type  === type) &&
    (!ville  || p.ville === ville) &&
    (!search || p.nom.toLowerCase().includes(search)
             || (p.avantage||'').toLowerCase().includes(search)
             || (p.ville||'').toLowerCase().includes(search))
  );
  renderPartners(filtered);
  renderMapMarkers(filtered);
}

function debounceFilter() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(filterPartners, 300);
}

function resetFilters() {
  currentType = '';
  document.getElementById('searchInput').value = '';
  document.getElementById('villeSelect').value = '';
  document.querySelectorAll('.type-pill').forEach(b => b.classList.remove('active'));
  document.querySelector('.type-pill[data-type=""]').classList.add('active');
  filterPartners();
}

function callPartner(e, tel) {
  e.stopPropagation();
  window.location.href = 'tel:' + tel;
}

function showDetails(e, id) {
  e.stopPropagation();
  const p = allPartners.find(x => x.id_partenaire == id);
  if (!p) return;
  const d = [
    p.nom,
    '',
    p.adresse + ', ' + p.ville,
    p.telephone || '—',
    p.horaires || '—',
    '',
    p.avantage_detail || p.avantage || '—'
  ].join('\n');
  alert(d);
}

function logUsage(e, id, nom) {
  e.stopPropagation();
  fetch('../../api.php?action=partenaire_log', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'id_partenaire='+id+'&contexte=profil_client'
  });
  toast(nom + ' — Utilisation enregistrée');
}

function toast(msg) {
  const c  = document.getElementById('toastContainer');
  const el = document.createElement('div');
  el.className = 'ptx-toast';
  el.innerHTML = '<i class="bi bi-check-circle" style="color:var(--success,#22c55e)"></i>' + escHtml(msg);
  c.appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

function escHtml(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
<script src="assets/js/main.js"></script>
</body>
</html>
