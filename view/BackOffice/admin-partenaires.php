<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
if (!in_array(SessionGuard::role(), ['superadmin', 'admin'], true)) {
    http_response_code(403); include __DIR__.'/403.php'; exit;
}
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Partenaires — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <style>
    .page-header{padding:1.5rem 2rem 1rem;display:flex;align-items:center;justify-content:space-between}
    .page-header h1{font-size:22px;font-weight:700;margin:0}
    .page-header .sub{font-size:13px;opacity:.5;margin-top:2px}
    .stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.75rem;padding:0 2rem 1.25rem}
    .stat-card{background:var(--card-bg,rgba(255,255,255,.04));border:1px solid var(--border-color,rgba(255,255,255,.08));border-radius:12px;padding:1rem;text-align:center}
    .stat-card .num{font-size:24px;font-weight:700;color:#fff}
    .stat-card .lbl{font-size:11.5px;opacity:.5;margin-top:2px}
    .filters-row{display:flex;gap:10px;padding:0 2rem 1rem;flex-wrap:wrap}
    .filters-row input,.filters-row select{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:7px 12px;color:#fff;font-size:13px;outline:none}
    .filters-row select option{background:#0a1628}
    .table-wrap{padding:0 2rem 2rem}
    table{width:100%;border-collapse:collapse;background:rgba(255,255,255,.03);border-radius:12px;overflow:hidden}
    th{text-align:left;padding:12px 14px;font-size:12px;opacity:.5;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid rgba(255,255,255,.06)}
    td{padding:11px 14px;font-size:13px;border-bottom:1px solid rgba(255,255,255,.04)}
    tr:hover td{background:rgba(255,255,255,.03)}
    .badge-type{display:inline-block;padding:2px 9px;border-radius:99px;font-size:11px;background:rgba(0,180,216,.12);color:var(--accent,#00b4d8)}
    .badge-type.clinique{background:rgba(230,57,70,.12);color:#e63946}
    .badge-type.pharmacie{background:rgba(34,197,94,.12);color:#22c55e}
    .badge-type.hotel{background:rgba(245,158,11,.12);color:#f59e0b}
    .badge-type.avocat{background:rgba(124,58,237,.12);color:#7c3aed}
    .toggle-switch{width:36px;height:20px;border-radius:99px;background:rgba(255,255,255,.15);cursor:pointer;position:relative;transition:all .2s;display:inline-block}
    .toggle-switch.active{background:#22c55e}.toggle-switch::after{content:'';position:absolute;top:2px;left:2px;width:16px;height:16px;border-radius:50%;background:#fff;transition:all .2s}
    .toggle-switch.active::after{left:18px}
    .btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:8px;border:none;font-size:12px;font-weight:500;cursor:pointer;font-family:inherit;transition:all .15s}
    .btn-primary{background:var(--accent,#00b4d8);color:#fff}.btn-primary:hover{opacity:.8}
    .btn-sm{padding:5px 10px;font-size:11px}
    .btn-icon{background:transparent;color:rgba(255,255,255,.5);padding:5px;border:none;cursor:pointer;font-size:16px}
    .btn-icon:hover{color:#fff}
    .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;display:none;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
    .modal-overlay.open{display:flex}
    .modal-box{background:#0d1b2a;border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:1.5rem;max-width:600px;width:90%;max-height:85vh;overflow-y:auto}
    .modal-box h2{font-size:17px;font-weight:600;margin:0 0 1rem}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    .form-grid .full{grid-column:1/-1}
    .form-grid label{display:block;font-size:11.5px;opacity:.6;margin-bottom:3px}
    .form-grid input,.form-grid select,.form-grid textarea{width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:8px 10px;color:#fff;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box}
    .form-grid textarea{resize:vertical;min-height:60px}
    .modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:1.25rem}
    .stars{color:#f4a261;letter-spacing:2px;font-size:14px}
    .empty-state{text-align:center;padding:3rem;opacity:.5}
    .toast-c{position:fixed;top:1rem;right:1rem;z-index:99999}
    .toast{background:#0d1b2a;border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:.65rem 1rem;font-size:13px;color:#fff;display:flex;align-items:center;gap:8px;box-shadow:0 8px 32px rgba(0,0,0,.5);margin-bottom:.5rem;border-left:3px solid var(--accent)}
    </style>
</head>
<body>
<div class="background"></div>
<div class="orb orb-1"></div><div class="orb orb-2"></div><div class="orb orb-3"></div>
<div class="layout">
    <?php require_once __DIR__.'/assets/includes/sidebar.php'; ?>
    <main class="main">

<div class="page-header">
    <div>
        <h1>Partenaires</h1>
        <div class="sub">Gérez votre réseau de partenaires agréés</div>
    </div>
    <button class="btn btn-primary" onclick="openModal()"><i class="bi bi-plus-lg"></i> Ajouter</button>
</div>

<div id="statsRow" class="stats-row">
    <div class="stat-card"><div class="num" id="statTotal">—</div><div class="lbl">Total</div></div>
    <div class="stat-card"><div class="num" id="statActifs">—</div><div class="lbl">Actifs</div></div>
    <div class="stat-card"><div class="num" id="statGarages">—</div><div class="lbl">Garages</div></div>
    <div class="stat-card"><div class="num" id="statCliniques">—</div><div class="lbl">Cliniques</div></div>
    <div class="stat-card"><div class="num" id="statNote">—</div><div class="lbl">Note moyenne</div></div>
</div>

<div class="filters-row">
    <input type="text" id="searchInput" placeholder="Rechercher..." oninput="debounceLoad()">
    <select id="filterType" onchange="loadTable()">
        <option value="">Tous types</option>
        <option value="garage">Garage</option>
        <option value="clinique">Clinique</option>
        <option value="pharmacie">Pharmacie</option>
        <option value="hotel">Hôtel</option>
        <option value="avocat">Avocat</option>
        <option value="location_voiture">Location</option>
        <option value="serrurier">Serrurier</option>
        <option value="telemedicine">Télémédecine</option>
        <option value="autre">Autre</option>
    </select>
    <select id="filterActif" onchange="loadTable()">
        <option value="">Tous statuts</option>
        <option value="1">Actif</option>
        <option value="0">Inactif</option>
    </select>
</div>

<div class="table-wrap">
    <table><thead><tr>
        <th>Nom</th><th>Type</th><th>Ville</th><th>Note</th><th>Avantage</th><th>Actif</th><th>Actions</th>
    </tr></thead>
        <tbody id="tableBody"><tr><td colspan="7" style="text-align:center;padding:3rem;opacity:.5">Chargement…</td></tr></tbody>
    </table>
</div>

    </main>
</div>

<div class="modal-overlay" id="partenaireModal">
    <div class="modal-box">
        <h2 id="modalTitle">Ajouter un partenaire</h2>
        <form onsubmit="savePartenaire(event)" id="partenaireForm">
            <input type="hidden" name="id_partenaire" id="fid" value="0">
            <div class="form-grid">
                <div><label>Nom *</label><input name="nom" id="fnom" required></div>
                <div><label>Type *</label>
                    <select name="type" id="ftype" required>
                        <option value="garage">Garage</option>
                        <option value="clinique">Clinique</option>
                        <option value="pharmacie">Pharmacie</option>
                        <option value="hotel">Hôtel</option>
                        <option value="avocat">Avocat</option>
                        <option value="location_voiture">Location</option>
                        <option value="serrurier">Serrurier</option>
                        <option value="telemedicine">Télémédecine</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div class="full"><label>Description</label><textarea name="description" id="fdesc" rows="2"></textarea></div>
                <div><label>Adresse</label><input name="adresse" id="fadr"></div>
                <div><label>Ville</label><input name="ville" id="fville"></div>
                <div><label>Gouvernorat</label><input name="gouvernorat" id="fgouv"></div>
                <div><label>Téléphone</label><input name="telephone" id="ftel"></div>
                <div><label>Email</label><input name="email" id="femail" type="email"></div>
                <div><label>Site web</label><input name="site_web" id="fweb"></div>
                <div><label>Latitude</label><input name="latitude" id="flat" type="number" step="any"></div>
                <div><label>Longitude</label><input name="longitude" id="flng" type="number" step="any"></div>
                <div><label>Avantage (court)</label><input name="avantage" id="fav"></div>
                <div><label>Horaires</label><input name="horaires" id="fhor" placeholder="Lun-Ven 8h-18h"></div>
                <div class="full"><label>Avantage détail</label><textarea name="avantage_detail" id="favd" rows="2"></textarea></div>
                <div><label>Actif</label><label style="display:flex;align-items:center;gap:8px;margin-top:6px"><input name="actif" id="factif" type="checkbox" checked> Oui</label></div>
                <div><label>Ordre</label><input name="ordre" id="fordre" type="number" value="0"></div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn" onclick="closeModal()" style="background:rgba(255,255,255,.06)">Annuler</button>
                <button type="submit" class="btn btn-primary" id="saveBtn">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<div class="toast-c" id="toastContainer"></div>

<script>
let partenaires = [];
const CSRF = document.querySelector('meta[name="csrf"]')?.getAttribute('content') || '';

async function loadStats() {
    try {
        const r = await fetch('../../api.php?action=partenaires_list');
        const d = await r.json();
        if (!d.success) return;
        partenaires = d.partenaires;
        const total = partenaires.length;
        const actifs = partenaires.filter(p => p.actif == 1).length;
        const garages = partenaires.filter(p => p.type === 'garage').length;
        const cliniques = partenaires.filter(p => p.type === 'clinique').length;
        const note = partenaires.length ? (partenaires.reduce((a,p) => a + parseFloat(p.note_calculee||0), 0) / partenaires.length) : 0;
        document.getElementById('statTotal').textContent = total;
        document.getElementById('statActifs').textContent = actifs;
        document.getElementById('statGarages').textContent = garages;
        document.getElementById('statCliniques').textContent = cliniques;
        document.getElementById('statNote').textContent = note.toFixed(1) + '/5';
        loadTable();
    } catch(e) { console.error(e); }
}

function loadTable() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const type   = document.getElementById('filterType').value;
    const actif  = document.getElementById('filterActif').value;
    const filtered = partenaires.filter(p =>
        (!search || p.nom.toLowerCase().includes(search) || p.ville?.toLowerCase().includes(search)) &&
        (!type   || p.type === type) &&
        (actif === '' || p.actif == actif)
    );
    const tbody = document.getElementById('tableBody');
    if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:3rem;opacity:.5">Aucun partenaire trouvé</td></tr>';
        return;
    }
    tbody.innerHTML = filtered.map(p => {
        const stars = '★'.repeat(Math.round(p.note_calculee||0)) + '☆'.repeat(5-Math.round(p.note_calculee||0));
        return `<tr>
            <td><strong>${escHtml(p.nom)}</strong></td>
            <td><span class="badge-type ${p.type}">${p.type}</span></td>
            <td>${escHtml(p.ville||'—')}</td>
            <td><span class="stars">${stars}</span></td>
            <td style="font-size:12px;color:#22c55e">${escHtml(p.avantage||'')}</td>
            <td><span class="toggle-switch ${p.actif==1?'active':''}" onclick="toggleActif(${p.id_partenaire},this)"></span></td>
            <td>
                <button class="btn-icon bi bi-pencil" onclick="editPartenaire(${p.id_partenaire})" title="Modifier"></button>
                <button class="btn-icon bi bi-trash" onclick="confirmDelete(${p.id_partenaire},'${escHtml(p.nom)}')" title="Supprimer"></button>
            </td>
        </tr>`;
    }).join('');
}

function debounceLoad() { clearTimeout(window._dl); window._dl = setTimeout(loadTable, 300); }

async function toggleActif(id, el) {
    const fd = new FormData(); fd.append('id', id);
    const r = await fetch('../../api.php?action=partenaire_toggle', { method:'POST', body: fd });
    const d = await r.json();
    if (d.success) { el.classList.toggle('active'); toast('Statut modifié'); loadStats(); }
    else toast('Erreur', 'danger');
}

function openModal(data) {
    document.getElementById('partenaireModal').classList.add('open');
    document.getElementById('modalTitle').textContent = data ? 'Modifier le partenaire' : 'Ajouter un partenaire';
    document.getElementById('fid').value = data?.id_partenaire || 0;
    document.getElementById('fnom').value = data?.nom || '';
    document.getElementById('ftype').value = data?.type || 'garage';
    document.getElementById('fdesc').value = data?.description || '';
    document.getElementById('fadr').value = data?.adresse || '';
    document.getElementById('fville').value = data?.ville || '';
    document.getElementById('fgouv').value = data?.gouvernorat || '';
    document.getElementById('ftel').value = data?.telephone || '';
    document.getElementById('femail').value = data?.email || '';
    document.getElementById('fweb').value = data?.site_web || '';
    document.getElementById('flat').value = data?.latitude || '';
    document.getElementById('flng').value = data?.longitude || '';
    document.getElementById('fav').value = data?.avantage || '';
    document.getElementById('favd').value = data?.avantage_detail || '';
    document.getElementById('fhor').value = data?.horaires || '';
    document.getElementById('factif').checked = data?.actif != 0;
    document.getElementById('fordre').value = data?.ordre || 0;
}

function closeModal() { document.getElementById('partenaireModal').classList.remove('open'); }

function editPartenaire(id) {
    const p = partenaires.find(x => x.id_partenaire == id);
    if (p) openModal(p);
}

async function savePartenaire(e) {
    e.preventDefault();
    const btn = document.getElementById('saveBtn');
    btn.disabled = true; btn.textContent = 'Enregistrement…';
    const fd = new FormData(document.getElementById('partenaireForm'));
    const r = await fetch('../../api.php?action=partenaire_save', { method:'POST', body: fd });
    const d = await r.json();
    btn.disabled = false; btn.textContent = 'Enregistrer';
    if (d.success) { toast('Partenaire enregistré'); closeModal(); loadStats(); }
    else toast('Erreur lors de l\'enregistrement', 'danger');
}

function confirmDelete(id, nom) {
    if (!confirm('Supprimer définitivement « '+nom+' » ?')) return;
    const fd = new FormData(); fd.append('id', id);
    fetch('../../api.php?action=partenaire_delete', { method:'POST', body: fd })
        .then(r => r.json()).then(d => {
            if (d.success) { toast('Partenaire supprimé'); loadStats(); }
            else toast('Erreur', 'danger');
        });
}

function toast(msg, type) {
    const c = document.getElementById('toastContainer');
    const el = document.createElement('div');
    el.className = 'toast';
    el.style.borderLeftColor = type === 'danger' ? '#e63946' : 'var(--accent)';
    el.innerHTML = '<i class="bi bi-'+(type==='danger'?'exclamation-circle':'check-circle')+'" style="color:'+(type==='danger'?'#e63946':'#22c55e')+'"></i>'+msg;
    c.appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

document.addEventListener('DOMContentLoaded', loadStats);
</script>
<script src="assets/js/main.js"></script>
<script src="assets/js/admin.js"></script>
</body>
</html>
