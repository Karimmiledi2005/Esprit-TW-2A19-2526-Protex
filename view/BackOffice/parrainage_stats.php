<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
if (!in_array(SessionGuard::role(), ['superadmin', 'admin', 'assureur'], true)) {
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
    <title>Parrainage — Protex Admin</title>
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
    .page-tabs{display:flex;gap:0;padding:0 2rem;border-bottom:1px solid rgba(255,255,255,.06);margin-bottom:1.25rem}
    .page-tabs button{background:none;border:none;padding:.6rem 1.1rem;font-size:13px;color:rgba(255,255,255,.5);cursor:pointer;border-bottom:2px solid transparent;font-family:inherit;transition:all .15s}
    .page-tabs button.active{color:#fff;border-bottom-color:var(--accent,#00b4d8)}
    .tab-content{display:none}.tab-content.active{display:block}

    .stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.75rem;padding:0 2rem 1.25rem}
    .stat-card{background:var(--card-bg,rgba(255,255,255,.04));border:1px solid var(--border-color,rgba(255,255,255,.08));border-radius:12px;padding:1rem;text-align:center}
    .stat-card .num{font-size:24px;font-weight:700;color:#fff}
    .stat-card .lbl{font-size:11.5px;opacity:.5;margin-top:2px}

    .filters-row{display:flex;gap:10px;padding:0 2rem 1rem;flex-wrap:wrap}
    .filters-row input,.filters-row select{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:7px 12px;color:#fff;font-size:13px;outline:none}
    .filters-row select option{background:#0a1628}

    .table-wrap{padding:0 2rem 2rem}
    table{width:100%;border-collapse:collapse;background:rgba(255,255,255,.03);border-radius:12px;overflow:hidden;margin-bottom:1rem}
    th{text-align:left;padding:12px 14px;font-size:12px;opacity:.5;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid rgba(255,255,255,.06)}
    td{padding:11px 14px;font-size:13px;border-bottom:1px solid rgba(255,255,255,.04)}
    tr:hover td{background:rgba(255,255,255,.03)}
    .empty-state{text-align:center;padding:3rem;opacity:.5}

    .badge-stat{display:inline-block;padding:2px 9px;border-radius:99px;font-size:11px;font-weight:500}
    .badge-stat.en_attente{background:rgba(245,158,11,.12);color:#f59e0b}
    .badge-stat.valide{background:rgba(34,197,94,.12);color:#22c55e}
    .badge-stat.rejete{background:rgba(230,57,70,.12);color:#e63946}
    .badge-stat.converti{background:rgba(0,180,216,.12);color:#00b4d8}

    .btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:8px;border:none;font-size:12px;font-weight:500;cursor:pointer;font-family:inherit;transition:all .15s}
    .btn-primary{background:var(--accent,#00b4d8);color:#fff}.btn-primary:hover{opacity:.8}
    .btn-sm{padding:5px 10px;font-size:11px}
    .btn-icon{background:transparent;color:rgba(255,255,255,.5);padding:5px;border:none;cursor:pointer;font-size:16px}
    .btn-icon:hover{color:#fff}

    .config-card{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:1.5rem;margin:0 2rem 1.5rem}
    .config-card h3{font-size:15px;font-weight:600;margin:0 0 .75rem}
    .config-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px}
    .config-grid label{display:block;font-size:11.5px;opacity:.6;margin-bottom:3px}
    .config-grid input{width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:8px 10px;color:#fff;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box}
    .config-actions{margin-top:1rem;display:flex;gap:8px;justify-content:flex-end}
    .config-actions .btn-success{background:#22c55e;color:#fff}
    .config-actions .btn-success:hover{opacity:.8}

    .toast-c{position:fixed;top:1rem;right:1rem;z-index:99999}
    .toast{background:#0d1b2a;border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:.65rem 1rem;font-size:13px;color:#fff;display:flex;align-items:center;gap:8px;box-shadow:0 8px 32px rgba(0,0,0,.5);margin-bottom:.5rem;border-left:3px solid var(--accent)}

    .points{color:#f4a261;font-weight:600}
    .user-pill{display:inline-flex;align-items:center;gap:5px}
    .user-pill img{width:22px;height:22px;border-radius:50%;object-fit:cover;background:rgba(255,255,255,.1)}
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
        <h1>Parrainage</h1>
        <div class="sub">Statistiques et gestion des parrainages</div>
    </div>
    <a href="../FrontOffice/parrainage.php" class="btn btn-primary" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Voir FO</a>
</div>

<div class="page-tabs">
    <button class="active" data-tab="tab-stats" onclick="switchTab('tab-stats')">Statistiques</button>
    <button data-tab="tab-list" onclick="switchTab('tab-list')">Parrainages</button>
    <button data-tab="tab-config" onclick="switchTab('tab-config')">Configuration</button>
    <button data-tab="tab-top" onclick="switchTab('tab-top')">Top parrains</button>
</div>

<div id="tab-stats" class="tab-content active">
    <div class="stats-row">
        <div class="stat-card"><div class="num" id="sTotal">—</div><div class="lbl">Total parrainages</div></div>
        <div class="stat-card"><div class="num" id="sEnAttente">—</div><div class="lbl">En attente</div></div>
        <div class="stat-card"><div class="num" id="sValides">—</div><div class="lbl">Validés</div></div>
        <div class="stat-card"><div class="num" id="sConverti">—</div><div class="lbl">Converti</div></div>
        <div class="stat-card"><div class="num" id="sPointsDistribues">—</div><div class="lbl">Points distribués</div></div>
    </div>
    <div style="padding:0 2rem">
        <canvas id="parrainageChart" height="200" style="background:rgba(255,255,255,.02);border-radius:12px;padding:1rem;max-width:100%"></canvas>
    </div>
</div>

<div id="tab-list" class="tab-content">
    <div class="filters-row">
        <input type="date" id="fDateDebut">
        <input type="date" id="fDateFin">
        <select id="fStatut" onchange="loadParrainages()">
            <option value="">Tous statuts</option>
            <option value="en_attente">En attente</option>
            <option value="valide">Validé</option>
            <option value="converti">Converti</option>
            <option value="rejete">Rejeté</option>
        </select>
        <input type="text" id="fSearch" placeholder="Email ou nom…" oninput="debounceParrainages()">
        <button class="btn btn-primary btn-sm" onclick="loadParrainages()"><i class="bi bi-search"></i></button>
    </div>
    <div class="table-wrap">
        <table><thead><tr>
            <th>Parrain</th><th>Filleul</th><th>Code</th><th>Statut</th><th>Points</th><th>Date</th><th>Actions</th>
        </tr></thead>
            <tbody id="parrainageTbody"><tr><td colspan="7" style="text-align:center;padding:3rem;opacity:.5">Chargement…</td></tr></tbody>
        </table>
    </div>
</div>

<div id="tab-config" class="tab-content">
    <div class="config-card">
        <h3>Paramètres des points de parrainage</h3>
        <div class="config-grid">
            <div><label>Points pour le parrain (inscription filleul)</label><input type="number" id="cfg_points_parrain" min="0" step="1"></div>
            <div><label>Points pour le filleul (inscription)</label><input type="number" id="cfg_points_filleul" min="0" step="1"></div>
            <div><label>Points bonus (souscription contrat)</label><input type="number" id="cfg_points_bonus" min="0" step="1"></div>
            <div><label>Points nécessaires pour 1 DT de remise</label><input type="number" id="cfg_points_per_dt" min="1" step="1"></div>
            <div><label>Validité parrainage (jours)</label><input type="number" id="cfg_validite_jours" min="1" step="1"></div>
            <div><label>Conversion auto (min. contrats souscrits)</label><input type="number" id="cfg_min_contrats" min="0" step="1"></div>
        </div>
        <div class="config-actions">
            <button class="btn btn-success" onclick="saveConfig()"><i class="bi bi-check-lg"></i> Sauvegarder</button>
        </div>
    </div>

    <div class="config-card">
        <h3>Actions manuelles</h3>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <input type="number" id="manualUserId" placeholder="ID utilisateur" style="width:120px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:7px 12px;color:#fff;font-size:13px;outline:none">
            <input type="number" id="manualPoints" placeholder="Points" style="width:100px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:7px 12px;color:#fff;font-size:13px;outline:none">
            <input type="text" id="manualRaison" placeholder="Raison" style="width:200px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:7px 12px;color:#fff;font-size:13px;outline:none">
            <button class="btn btn-primary" onclick="ajusterPoints()"><i class="bi bi-plus-minus"></i> Ajuster points</button>
        </div>
    </div>
</div>

<div id="tab-top" class="tab-content">
    <div class="table-wrap">
        <table><thead><tr><th>#</th><th>Parrain</th><th>Filleuls</th><th>Points gagnés</th><th>Codes actifs</th></tr></thead>
            <tbody id="topTbody"><tr><td colspan="5" style="text-align:center;padding:3rem;opacity:.5">Chargement…</td></tr></tbody>
        </table>
    </div>
</div>

    </main>
</div>

<div class="toast-c" id="toastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const CSRF = document.querySelector('meta[name="csrf"]')?.getAttribute('content') || '';
let myChart = null;

function switchTab(id) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.page-tabs button').forEach(b => b.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    document.querySelector(`.page-tabs button[data-tab="${id}"]`).classList.add('active');
}

function debounceParrainages() {
    clearTimeout(window._dp); window._dp = setTimeout(loadParrainages, 300);
}

async function loadStats() {
    try {
        const r = await fetch('../../api.php?action=parrainage_stats');
        const d = await r.json();
        if (!d.success) return;
        document.getElementById('sTotal').textContent = d.stats?.total || 0;
        document.getElementById('sEnAttente').textContent = d.stats?.en_attente || 0;
        document.getElementById('sValides').textContent = d.stats?.valide || 0;
        document.getElementById('sConverti').textContent = d.stats?.converti || 0;
        document.getElementById('sPointsDistribues').textContent = d.stats?.points_distribues || 0;
        if (d.stats?.chart_labels && d.stats?.chart_data && window.Chart) {
            if (myChart) myChart.destroy();
            const ctx = document.getElementById('parrainageChart').getContext('2d');
            myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: d.stats.chart_labels,
                    datasets: [{
                        label: 'Parrainages',
                        data: d.stats.chart_data,
                        borderColor: '#00b4d8',
                        backgroundColor: 'rgba(0,180,216,.1)',
                        fill: true,
                        tension: .3,
                        pointRadius: 3
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { labels: { color: '#fff' } } },
                    scales: {
                        x: { ticks: { color: 'rgba(255,255,255,.5)' }, grid: { color: 'rgba(255,255,255,.05)' } },
                        y: { ticks: { color: 'rgba(255,255,255,.5)' }, grid: { color: 'rgba(255,255,255,.05)' }, beginAtZero: true }
                    }
                }
            });
        }
    } catch(e) { console.error(e); }
}

async function loadParrainages() {
    const params = new URLSearchParams({
        date_debut: document.getElementById('fDateDebut').value,
        date_fin: document.getElementById('fDateFin').value,
        statut: document.getElementById('fStatut').value,
        search: document.getElementById('fSearch').value,
        limit: 100
    });
    try {
        const r = await fetch(`../../api.php?action=parrainage_list&${params}`);
        const d = await r.json();
        const tbody = document.getElementById('parrainageTbody');
        if (!d.success || !d.parrainages?.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:3rem;opacity:.5">Aucun parrainage</td></tr>';
            return;
        }
        tbody.innerHTML = d.parrainages.map(p => {
            const badge = p.statut || 'en_attente';
            const ds = p.date_creation ? p.date_creation.split(' ')[0] : '—';
            return `<tr>
                <td class="user-pill"><img src="https://ui-avatars.com/api/?name=${encodeURIComponent(p.parrain_nom||'?')}&background=00b4d8&color=fff&size=22" alt=""> ${escHtml(p.parrain_nom)||'N°'+p.id_parrain}</td>
                <td>${escHtml(p.filleul_nom)||escHtml(p.filleul_email)||'—'}</td>
                <td style="font-family:monospace;font-size:12px">${escHtml(p.code_parrainage)||'—'}</td>
                <td><span class="badge-stat ${badge}">${badge}</span></td>
                <td class="points">+${p.points_attribues||0}</td>
                <td style="font-size:12px;opacity:.6">${ds}</td>
                <td>
                    ${badge === 'en_attente' ? `<button class="btn-icon bi bi-check-circle" style="color:#22c55e" onclick="validerParrainage(${p.id_parrainage})" title="Valider"></button>` : ''}
                    ${badge === 'en_attente' ? `<button class="btn-icon bi bi-x-circle" style="color:#e63946" onclick="rejeterParrainage(${p.id_parrainage})" title="Rejeter"></button>` : ''}
                    <button class="btn-icon bi bi-eye" onclick="detailParrainage(${p.id_parrainage})" title="Détail"></button>
                </td>
            </tr>`;
        }).join('');
    } catch(e) { console.error(e); tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:3rem;opacity:.5">Erreur de chargement</td></tr>'; }
}

async function loadTopParrains() {
    try {
        const r = await fetch('../../api.php?action=parrainage_top');
        const d = await r.json();
        const tbody = document.getElementById('topTbody');
        if (!d.success || !d.top?.length) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:3rem;opacity:.5">Aucun parrain</td></tr>';
            return;
        }
        tbody.innerHTML = d.top.map((p, i) => `<tr>
            <td style="opacity:.4">#${i+1}</td>
            <td class="user-pill"><img src="https://ui-avatars.com/api/?name=${encodeURIComponent(p.nom||'?')}&background=00b4d8&color=fff&size=22" alt=""> ${escHtml(p.nom)}</td>
            <td>${p.total_filleuls||0}</td>
            <td class="points">+${p.total_points||0}</td>
            <td>${p.codes_actifs||0}</td>
        </tr>`).join('');
    } catch(e) { console.error(e); }
}

async function loadConfig() {
    try {
        const r = await fetch('../../api.php?action=parrainage_config');
        const d = await r.json();
        if (!d.success) return;
        const c = d.config;
        document.getElementById('cfg_points_parrain').value = c?.points_parrain || 50;
        document.getElementById('cfg_points_filleul').value = c?.points_filleul || 30;
        document.getElementById('cfg_points_bonus').value = c?.points_bonus || 100;
        document.getElementById('cfg_points_per_dt').value = c?.points_per_dt || 200;
        document.getElementById('cfg_validite_jours').value = c?.validite_jours || 30;
        document.getElementById('cfg_min_contrats').value = c?.min_contrats || 1;
    } catch(e) { console.error(e); }
}

async function saveConfig() {
    const btn = event.target;
    btn.disabled = true; btn.textContent = 'Sauvegarde…';
    const fd = new FormData();
    fd.append('points_parrain', document.getElementById('cfg_points_parrain').value);
    fd.append('points_filleul', document.getElementById('cfg_points_filleul').value);
    fd.append('points_bonus', document.getElementById('cfg_points_bonus').value);
    fd.append('points_per_dt', document.getElementById('cfg_points_per_dt').value);
    fd.append('validite_jours', document.getElementById('cfg_validite_jours').value);
    fd.append('min_contrats', document.getElementById('cfg_min_contrats').value);
    try {
        const r = await fetch('../../api.php?action=parrainage_save_config', { method:'POST', body: fd });
        const d = await r.json();
        if (d.success) toast('Configuration sauvegardée');
        else toast('Erreur de sauvegarde', 'danger');
    } catch(e) { toast('Erreur réseau', 'danger'); }
    btn.disabled = false; btn.textContent = 'Sauvegarder';
}

async function validerParrainage(id) {
    const fd = new FormData(); fd.append('id', id);
    const r = await fetch('../../api.php?action=parrainage_valider', { method:'POST', body: fd });
    const d = await r.json();
    if (d.success) { toast('Parrainage validé'); loadParrainages(); loadStats(); loadTopParrains(); }
    else toast(d.error || 'Erreur', 'danger');
}

async function rejeterParrainage(id) {
    const fd = new FormData(); fd.append('id', id);
    const r = await fetch('../../api.php?action=parrainage_rejeter', { method:'POST', body: fd });
    const d = await r.json();
    if (d.success) { toast('Parrainage rejeté'); loadParrainages(); loadStats(); }
    else toast(d.error || 'Erreur', 'danger');
}

async function detailParrainage(id) {
    try {
        const r = await fetch(`../../api.php?action=parrainage_detail&id=${id}`);
        const d = await r.json();
        if (!d.success) { toast('Erreur', 'danger'); return; }
        alert(JSON.stringify(d.parrainage, null, 2));
    } catch(e) { toast('Erreur', 'danger'); }
}

async function ajusterPoints() {
    const uid = document.getElementById('manualUserId').value;
    const pts = document.getElementById('manualPoints').value;
    const raison = document.getElementById('manualRaison').value;
    if (!uid) { toast('ID utilisateur requis', 'danger'); return; }
    if (!pts) { toast('Points requis', 'danger'); return; }
    const fd = new FormData(); fd.append('user_id', uid); fd.append('points', pts); fd.append('raison', raison);
    try {
        const r = await fetch('../../api.php?action=parrainage_ajuster_points', { method:'POST', body: fd });
        const d = await r.json();
        if (d.success) { toast('Points ajustés: '+d.nouveau_solde); loadStats(); }
        else toast(d.error || 'Erreur', 'danger');
    } catch(e) { toast('Erreur réseau', 'danger'); }
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

document.addEventListener('DOMContentLoaded', () => { loadStats(); loadParrainages(); loadConfig(); loadTopParrains(); });
</script>
<script src="assets/js/main.js"></script>
<script src="assets/js/admin.js"></script>
</body>
</html>
