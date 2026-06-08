<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireRoles(['superadmin', 'admin']);

$base = defined('BASE_URL') ? BASE_URL : '/assurance';
$pageTitle = 'Modération du Contenu';

// Styles spécifiques BackOffice Protex
ob_start();
?>
<link rel="stylesheet" href="assets/css/admin-users.css">
<style>
    /* ── Tabs ── */
    .mod-nav {
        display: flex; gap: 8px;
        padding: 16px 24px;
        border-bottom: 1px solid var(--glass-border);
    }
    .mod-nav button {
        background: transparent; border: 1px solid transparent;
        color: var(--text-secondary); padding: 8px 18px;
        border-radius: 8px; font-weight: 600; font-size: 13px;
        cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 6px;
    }
    .mod-nav button:hover { background: rgba(255,255,255,0.05); }
    .mod-nav button.active {
        background: rgba(0,180,216,0.12); color: var(--accent);
        border: 1px solid rgba(0,180,216,0.3);
    }

    /* ── Badges ── */
    .content-preview {
        max-width: 260px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        font-weight: 500; cursor: pointer; color: var(--text-secondary);
    }
    .badge-sig {
        background: rgba(234,179,8,0.12); color: var(--warning);
        padding: 3px 10px; border-radius: 20px; font-weight: 700;
        font-size: 11px; border: 1px solid rgba(234,179,8,0.25);
        display: inline-flex; align-items: center; gap: 4px;
    }
    .badge-sig.high {
        background: rgba(239,68,68,0.12); color: var(--danger);
        border-color: rgba(239,68,68,0.25);
    }
    .badge-vis {
        background: rgba(34,197,94,0.12); color: var(--success);
        padding: 3px 10px; border-radius: 20px; font-weight: 700;
        font-size: 11px; border: 1px solid rgba(34,197,94,0.25);
        display: inline-flex; align-items: center; gap: 4px;
    }
    .badge-hidden {
        background: rgba(255,255,255,0.07); color: var(--text-secondary);
        padding: 3px 10px; border-radius: 20px; font-weight: 700;
        font-size: 11px; border: 1px solid rgba(255,255,255,0.15);
        display: inline-flex; align-items: center; gap: 4px;
    }

    /* ── Action buttons ── */
    .btn-hide   { background: rgba(234,179,8,0.12);  color: var(--warning); border: 1px solid rgba(234,179,8,0.25);  border-radius: 7px; padding: 5px 11px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: .2s; }
    .btn-hide:hover   { background: rgba(234,179,8,0.22); transform: translateY(-1px); }
    .btn-unhide { background: rgba(34,197,94,0.12);  color: var(--success); border: 1px solid rgba(34,197,94,0.25);  border-radius: 7px; padding: 5px 11px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: .2s; }
    .btn-unhide:hover { background: rgba(34,197,94,0.22); transform: translateY(-1px); }
    .btn-del    { background: rgba(239,68,68,0.12);  color: var(--danger);  border: 1px solid rgba(239,68,68,0.25);  border-radius: 7px; padding: 5px 11px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: .2s; }
    .btn-del:hover    { background: rgba(239,68,68,0.22); transform: translateY(-1px); }
    .btn-profile { background: rgba(255,255,255,0.07); color: var(--text-secondary); border: 1px solid rgba(255,255,255,0.15); border-radius: 7px; padding: 5px 9px; font-size: 12px; cursor: pointer; display: inline-inline-flex; align-items: center; gap: 5px; transition: .2s; text-decoration: none; }
    .btn-profile:hover { background: rgba(255,255,255,0.12); color: #fff; }

    /* ── Empty state ── */
    .mod-empty { text-align: center; padding: 48px 20px; color: var(--text-secondary); }
    .mod-empty i { font-size: 36px; display: block; margin-bottom: 10px; opacity: 0.3; }
    .mod-empty p { font-size: 14px; }

    /* ── Author cell ── */
    .author-cell { display: flex; align-items: center; gap: 10px; }
    .author-initial { width: 32px; height: 32px; border-radius: 50%;
        background: linear-gradient(135deg, var(--accent-dark), #0096c7);
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0; }

    /* ── Agence badge in author ── */
    .agence-tag { font-size: 10px; color: var(--accent); background: rgba(0,180,216,0.1);
        border: 1px solid rgba(0,180,216,0.2); border-radius: 5px; padding: 1px 5px; }

    .actions-cell { display: flex; align-items: center; gap: 6px; justify-content: flex-end; flex-wrap: wrap; }

    /* ── Table column widths ── */
    #postsView table      { table-layout: fixed; }
    #postsView thead th:nth-child(1) { width: 180px; }
    #postsView thead th:nth-child(2) { width: auto; }
    #postsView thead th:nth-child(3) { width: 100px; }
    #postsView thead th:nth-child(4) { width: 120px; }
    #postsView thead th:nth-child(5) { width: 130px; }
    #postsView thead th:nth-child(6) { width: 110px; }
    #postsView thead th:nth-child(7) { width: 150px; }

    #commentsView table      { table-layout: fixed; }
    #commentsView thead th:nth-child(1) { width: 180px; }
    #commentsView thead th:nth-child(2) { width: auto; }
    #commentsView thead th:nth-child(3) { width: 100px; }
    #commentsView thead th:nth-child(4) { width: 130px; }
    #commentsView thead th:nth-child(5) { width: 110px; }
    #commentsView thead th:nth-child(6) { width: 150px; }

    /* ── Custom scrollbar ── */
    .table-wrap::-webkit-scrollbar { height: 8px; }
    .table-wrap::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); border-radius: 4px; }
    .table-wrap::-webkit-scrollbar-thumb { background: var(--accent); border-radius: 4px; }
</style>
<?php
$extraCss = ob_get_clean();

require_once __DIR__ . '/assets/includes/header.php';
require_once __DIR__ . '/assets/includes/sidebar.php';
?>

<main class="main">
<div class="content">

    <div class="page-header-bar">
        <div>
            <div class="page-title">Modération du Réseau</div>
            <div class="page-breadcrumb">
                <i class="bi bi-shield-check"></i>
                <span>Modération</span>
                <i class="bi bi-chevron-right" style="font-size:10px;"></i>
                <span>Posts &amp; Commentaires</span>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <input class="form-check-input" type="checkbox" id="filterSignaledOnly"
                style="width:36px;height:18px;cursor:pointer;margin:0;">
            <label for="filterSignaledOnly" style="font-size:12px; font-weight:700; color:var(--danger); text-transform:uppercase; letter-spacing:0.5px; cursor:pointer; margin:0;">
                Signalés uniquement
            </label>
        </div>
    </div>

    <!-- Filters -->
    <div class="card" style="margin-bottom:20px;">
        <div class="toolbar-inner">
            <div class="toolbar">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" placeholder="Rechercher un auteur, contenu…" oninput="applyFilters()">
                </div>
                <select id="filterAgence" class="filter-select" style="min-width:180px;" onchange="applyFilters()">
                    <option value="">Toutes les agences</option>
                </select>
                <input type="date" id="filterDateStart" class="filter-select" style="height:38px;padding:5px 10px;" onchange="applyFilters()">
                <span style="color:var(--text-secondary);font-size:12px;">à</span>
                <input type="date" id="filterDateEnd" class="filter-select" style="height:38px;padding:5px 10px;" onchange="applyFilters()">
                <button class="btn btn-outline btn-sm" onclick="clearFilters()">
                    <i class="bi bi-x-circle"></i> Réinitialiser
                </button>
            </div>
        </div>
    </div>

    <!-- Main card with tabs -->
    <div class="card">
        <div class="card-header">
            <div class="mod-nav" style="padding:0; border:none;">
                <button class="active" onclick="switchTab('posts')" id="btnTabPosts">
                    <i class="bi bi-file-post"></i> Publications
                    <span id="postCount" style="background:rgba(0,180,216,0.15);color:var(--accent);padding:1px 7px;border-radius:10px;font-size:11px;"></span>
                </button>
                <button onclick="switchTab('comments')" id="btnTabComments">
                    <i class="bi bi-chat-text"></i> Commentaires
                    <span id="commentCount" style="background:rgba(0,180,216,0.15);color:var(--accent);padding:1px 7px;border-radius:10px;font-size:11px;"></span>
                </button>
            </div>
        </div>

        <!-- POSTS TAB -->
        <div id="postsView">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Auteur</th>
                            <th>Contenu</th>
                            <th>Date</th>
                            <th>Réactions</th>
                            <th>Signalements</th>
                            <th>Statut</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="postsBody">
                        <tr><td colspan="7" class="mod-empty"><i class="bi bi-arrow-repeat spin"></i><p>Chargement…</p></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- COMMENTS TAB -->
        <div id="commentsView" style="display:none;">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Auteur</th>
                            <th>Contenu</th>
                            <th>Date</th>
                            <th>Signalements</th>
                            <th>Statut</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="commentsBody">
                        <tr><td colspan="6" class="mod-empty"><i class="bi bi-arrow-repeat spin"></i><p>Chargement…</p></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</main>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
.spin { display: inline-block; animation: spin .8s linear infinite; }
</style>

<script>
let postsData = [];
let commentsData = [];

document.addEventListener('DOMContentLoaded', () => {
    loadData();

    document.getElementById('filterSignaledOnly').addEventListener('change', applyFilters);

    fetch(`${window.BASE_URL}/api.php?action=get_agences`)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const sel = document.getElementById('filterAgence');
                res.data.forEach(a => {
                    sel.innerHTML += `<option value="${a.id_agence}">${a.nom_agence}</option>`;
                });
            }
        }).catch(() => {});
});

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterAgence').value = '';
    document.getElementById('filterDateStart').value = '';
    document.getElementById('filterDateEnd').value = '';
    applyFilters();
}

async function loadData() {
    try {
        const res = await fetch(`${window.BASE_URL}/api.php?action=get_all_posts_admin`);
        const data = await res.json();
        if (data.success) {
            postsData = data.posts;
            commentsData = data.commentaires;
            applyFilters();
        }
    } catch(e) {
        console.error(e);
    }
}

function applyFilters() {
    const onlySignaled = document.getElementById('filterSignaledOnly').checked;
    const agenceFilter = document.getElementById('filterAgence').value;
    const dateStart    = document.getElementById('filterDateStart').value;
    const dateEnd      = document.getElementById('filterDateEnd').value;
    const search       = (document.getElementById('searchInput').value || '').toLowerCase();

    const filteredPosts = postsData.filter(p => {
        if (onlySignaled && (!p.signalements || p.signalements == 0)) return false;
        if (agenceFilter && String(p.id_agence) !== agenceFilter) return false;
        if (dateStart && p.date_publication < dateStart) return false;
        if (dateEnd   && p.date_publication > dateEnd)   return false;
        if (search) {
            const text = `${p.prenom || ''} ${p.nom || ''} ${p.contenu || ''}`.toLowerCase();
            if (!text.includes(search)) return false;
        }
        return true;
    });

    const filteredComments = commentsData.filter(c => {
        if (onlySignaled && (!c.signalements || c.signalements == 0)) return false;
        if (search) {
            const text = `${c.prenom || ''} ${c.nom || ''} ${c.contenu || ''}`.toLowerCase();
            if (!text.includes(search)) return false;
        }
        return true;
    });

    document.getElementById('postCount').textContent    = filteredPosts.length;
    document.getElementById('commentCount').textContent = filteredComments.length;

    renderPosts(filteredPosts);
    renderComments(filteredComments);
}

function esc(str) {
    return String(str || '').replace(/[&<>"']/g, c =>
        ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
}

function renderPosts(data) {
    const tbody = document.getElementById('postsBody');
    if (!data || !data.length) {
        tbody.innerHTML = `<tr><td colspan="7">
            <div class="mod-empty"><i class="bi bi-file-post"></i><p>Aucun post à afficher</p></div>
        </td></tr>`;
        return;
    }

    tbody.innerHTML = data.map(p => {
        const auteur     = `${p.prenom || ''} ${p.nom || ''}`.trim() || 'Anonyme';
        const initial    = auteur.charAt(0).toUpperCase();
        const signalements = parseInt(p.signalements || 0);
        const hidden     = parseInt(p.hidden || 0) === 1;
        const date       = (p.date_publication || '').slice(0,10);

        const sigBadge   = signalements === 0
            ? `<span style="color:var(--text-secondary);">-</span>`
            : `<span class="badge-sig ${signalements > 3 ? 'high' : ''}"><i class="bi bi-flag"></i> ${signalements}</span>`;

        const statBadge  = hidden
            ? `<span class="badge-hidden"><i class="bi bi-eye-slash"></i> Masqué</span>`
            : `<span class="badge-vis"><i class="bi bi-eye"></i> Visible</span>`;

        const btnHide = hidden
            ? `<button class="btn-unhide" onclick="moderate(${p.id_poste},'post','hide')"><i class="bi bi-eye"></i> Rétablir</button>`
            : `<button class="btn-hide"   onclick="moderate(${p.id_poste},'post','hide')"><i class="bi bi-eye-slash"></i> Masquer</button>`;
        const btnDel   = `<button class="btn-del" onclick="moderate(${p.id_poste},'post','delete')"><i class="bi bi-trash"></i></button>`;

        return `<tr>
            <td>
                <div class="author-cell">
                    <div class="author-initial">${initial}</div>
                    <div>
                        <div style="font-weight:600;color:var(--text-primary);font-size:13px;">${esc(auteur)}</div>
                        ${p.nom_agence ? `<div class="agence-tag">${esc(p.nom_agence)}</div>` : ''}
                    </div>
                </div>
            </td>
            <td><div class="content-preview" title="${esc(p.contenu)}">${esc(p.contenu) || '-'}</div></td>
            <td style="color:var(--text-secondary);font-size:12px;white-space:nowrap;">${date}</td>
            <td style="color:var(--text-secondary);font-size:12px;">
                <span><i class="bi bi-heart" style="color:var(--danger);"></i> ${p.nb_likes||0}</span>
                &nbsp;
                <span><i class="bi bi-chat" style="color:var(--accent);"></i> ${p.nb_commentaires||0}</span>
            </td>
            <td>${sigBadge}</td>
            <td>${statBadge}</td>
            <td><div class="actions-cell">${btnHide}${btnDel}</div></td>
        </tr>`;
    }).join('');
}

function renderComments(data) {
    const tbody = document.getElementById('commentsBody');
    if (!data || !data.length) {
        tbody.innerHTML = `<tr><td colspan="6">
            <div class="mod-empty"><i class="bi bi-chat-text"></i><p>Aucun commentaire à afficher</p></div>
        </td></tr>`;
        return;
    }

    tbody.innerHTML = data.map(c => {
        const auteur     = `${c.prenom || ''} ${c.nom || ''}`.trim() || 'Anonyme';
        const initial    = auteur.charAt(0).toUpperCase();
        const signalements = parseInt(c.signalements || 0);
        const hidden     = parseInt(c.hidden || 0) === 1;
        const date       = (c.date_commentaire || '').slice(0,10);

        const sigBadge   = signalements === 0
            ? `<span style="color:var(--text-secondary);">-</span>`
            : `<span class="badge-sig ${signalements > 3 ? 'high' : ''}"><i class="bi bi-flag"></i> ${signalements}</span>`;

        const statBadge  = hidden
            ? `<span class="badge-hidden"><i class="bi bi-eye-slash"></i> Masqué</span>`
            : `<span class="badge-vis"><i class="bi bi-eye"></i> Visible</span>`;

        const btnHide = hidden
            ? `<button class="btn-unhide" onclick="moderate(${c.id_commentaire},'comment','hide')"><i class="bi bi-eye"></i> Rétablir</button>`
            : `<button class="btn-hide"   onclick="moderate(${c.id_commentaire},'comment','hide')"><i class="bi bi-eye-slash"></i> Masquer</button>`;
        const btnDel   = `<button class="btn-del" onclick="moderate(${c.id_commentaire},'comment','delete')"><i class="bi bi-trash"></i></button>`;

        return `<tr>
            <td>
                <div class="author-cell">
                    <div class="author-initial">${initial}</div>
                    <div style="font-weight:600;color:var(--text-primary);font-size:13px;">${esc(auteur)}</div>
                </div>
            </td>
            <td><div class="content-preview" title="${esc(c.contenu)}">${esc(c.contenu) || '-'}</div></td>
            <td style="color:var(--text-secondary);font-size:12px;white-space:nowrap;">${date}</td>
            <td>${sigBadge}</td>
            <td>${statBadge}</td>
            <td><div class="actions-cell">${btnHide}${btnDel}</div></td>
        </tr>`;
    }).join('');
}

async function moderate(id, type, actionStr) {
    if (actionStr === 'delete') {
        if (!confirm(`Supprimer ce ${type === 'post' ? 'post' : 'commentaire'} ? Cette action est irréversible.`)) return;
    }
    const fd = new FormData();
    fd.append('id', id);
    fd.append('type', type);
    fd.append('mod_action', actionStr);
    try {
        const res  = await fetch(`${window.BASE_URL}/api.php?action=moderate_post`, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            loadData();
        } else {
            alert('Erreur : ' + (data.error || 'Inconnue'));
        }
    } catch(e) { console.error(e); }
}

function switchTab(tab) {
    document.getElementById('btnTabPosts').classList.toggle('active', tab === 'posts');
    document.getElementById('btnTabComments').classList.toggle('active', tab === 'comments');
    document.getElementById('postsView').style.display    = tab === 'posts'    ? '' : 'none';
    document.getElementById('commentsView').style.display = tab === 'comments' ? '' : 'none';
}
</script>

<?php require_once __DIR__ . '/assets/includes/footer.php'; ?>
