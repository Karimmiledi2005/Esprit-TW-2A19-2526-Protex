<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Postes — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="assets_agences_postes/css/variables.css">
    <link rel="stylesheet" href="assets_agences_postes/css/base.css">
    <link rel="stylesheet" href="assets_agences_postes/css/layout.css">
    <link rel="stylesheet" href="assets_agences_postes/css/client.css">

    <style>
        :root {
            --font-display: 'Sora', sans-serif;
            --font-body: 'DM Sans', sans-serif;
            --ptx-primary: #15233C;
            --ptx-secondary: #31415f;
            --ptx-accent: #00d2ff;
            --ptx-orange: #FF6B1A;
            --ptx-glass: rgba(255, 255, 255, 0.8);
            --ptx-border: rgba(21, 35, 60, 0.08);
            --ptx-text: #15233C;
            --ptx-text-muted: rgba(21, 35, 60, 0.6);
            --radius-lg: 18px;
            --radius-md: 12px;
        }

        body {
            font-family: var(--font-body);
            color: var(--ptx-text);
            background: #f0f4ff;
            min-height: 100vh;
        }

        /* --- ORBS BACKGROUND --- */
        .background-orbs {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, #f0f4ff 0%, #e0e8ff 100%);
            z-index: -2;
        }
        .orb {
            position: fixed; border-radius: 50%; filter: blur(100px); z-index: -1; opacity: 0.25;
            animation: orbMove 25s infinite alternate ease-in-out;
        }
        .orb-1 { width: 600px; height: 600px; background: #1A3A7A; top: -100px; right: -100px; }
        .orb-2 { width: 500px; height: 500px; background: #00d2ff; bottom: -100px; left: -100px; animation-delay: -5s; }
        .orb-3 { width: 400px; height: 400px; background: #FF6B1A; top: 40%; left: 30%; opacity: 0.1; animation-delay: -10s; }

        @keyframes orbMove {
            0% { transform: translate(0, 0) rotate(0deg) scale(1); }
            100% { transform: translate(60px, 120px) rotate(15deg) scale(1.1); }
        }

        .page-header { background: transparent; padding: 30px 0; border: none; }
        .page-title-main { color: #15233C !important; font-family: var(--font-display); font-weight: 800; font-size: 32px; }
        .page-breadcrumb, .page-breadcrumb a { color: rgba(21, 35, 60, 0.5) !important; font-size: 13px; }
        .page-breadcrumb span { color: var(--ptx-orange) !important; font-weight: 600; }

        .section-header { margin-bottom: 25px; }
        .section-title { font-family: var(--font-display); font-size: 22px; font-weight: 700; color: #15233C !important; }
        .section-sub { font-size: 14px; color: var(--ptx-text-muted); }

        /* --- TOOLBAR --- */
        .posts-toolbar {
            display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 35px;
            padding: 24px; background: var(--ptx-glass); border: 1px solid var(--ptx-border);
            border-radius: var(--radius-lg); backdrop-filter: blur(20px);
            box-shadow: 0 10px 30px rgba(21, 35, 60, 0.04);
        }

        .search-input, .filter-select {
            height: 52px; border-radius: var(--radius-md); border: 1px solid var(--ptx-border);
            background: #fff; color: var(--ptx-text); padding: 0 18px;
            font-size: 14px; outline: none; transition: all 0.3s ease;
        }
        .search-input { flex: 1; min-width: 280px; }
        .filter-select { min-width: 220px; cursor: pointer; }
        .search-input:focus, .filter-select:focus { border-color: var(--ptx-orange); box-shadow: 0 0 0 4px rgba(255, 107, 26, 0.08); }

        /* --- GRID --- */
        .posts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(500px, 1fr)); gap: 30px; }
        @media (max-width: 1200px) { .posts-grid { grid-template-columns: 1fr; } }

        /* --- POST CARD --- */
        .post-card {
            background: #fff; border: 1px solid var(--ptx-border);
            border-radius: 24px; overflow: hidden; transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            display: flex; flex-direction: column;
            box-shadow: 0 8px 30px rgba(21, 35, 60, 0.05);
        }
        .post-card:hover {
            transform: translateY(-8px); border-color: rgba(255, 107, 26, 0.2);
            box-shadow: 0 25px 50px rgba(21, 35, 60, 0.1);
        }

        .post-card-head {
            padding: 28px 28px 20px; border-bottom: 1px solid rgba(21, 35, 60, 0.05);
            display: flex; justify-content: space-between; align-items: flex-start;
            background: rgba(21, 35, 60, 0.01);
        }
        .post-card-title { font-family: var(--font-display); font-size: 18px; font-weight: 700; color: #15233C; margin-bottom: 6px; }
        .post-card-sub { font-size: 13px; color: var(--ptx-text-muted); display: flex; align-items: center; gap: 8px; }
        .badge-info { background: rgba(0, 210, 255, 0.1); color: #0099cc; border: 1px solid rgba(0, 210, 255, 0.15); font-weight: 600; padding: 6px 12px; border-radius: 8px; font-size: 12px; }

        .post-card-body { padding: 28px; flex: 1; display: flex; flex-direction: column; }
        .post-content { font-size: 15.5px; line-height: 1.8; color: #31415f; margin-bottom: 25px; }

        .post-meta { display: flex; gap: 15px; margin-bottom: 25px; }
        .post-meta span {
            display: inline-flex; align-items: center; gap: 8px; font-size: 13px;
            color: #15233C; padding: 8px 15px; border-radius: 12px;
            background: rgba(21, 35, 60, 0.03); border: 1px solid rgba(21, 35, 60, 0.05);
            font-weight: 600;
        }

        /* --- ACTIONS --- */
        .btn-soft {
            display: inline-flex; align-items: center; gap: 10px; padding: 12px 22px;
            border-radius: 14px; border: 1px solid var(--ptx-border);
            background: rgba(21, 35, 60, 0.03); color: #15233C; font-size: 14px;
            font-weight: 600; transition: 0.3s; cursor: pointer;
        }
        .btn-soft:hover { background: #fff; border-color: var(--ptx-orange); color: var(--ptx-orange); box-shadow: 0 8px 20px rgba(255, 107, 26, 0.1); }
        .btn-like.liked { color: var(--ptx-orange); border-color: var(--ptx-orange); background: rgba(255,107,26,0.05); }

        .btn-nav-primary {
            background: linear-gradient(135deg, #FF6B1A 0%, #FF8D4D 100%);
            color: #fff; border: none; padding: 12px 24px; border-radius: 14px;
            font-weight: 600; cursor: pointer; transition: 0.3s;
            box-shadow: 0 10px 20px rgba(255, 107, 26, 0.2);
        }
        .btn-nav-primary:hover { transform: translateY(-2px); box-shadow: 0 15px 25px rgba(255, 107, 26, 0.3); }

        /* --- COMMENTS --- */
        .comments-wrap { margin-top: 30px; padding-top: 25px; border-top: 1px solid rgba(21, 35, 60, 0.05); }
        .comment-input-row { display: flex; gap: 12px; margin-bottom: 25px; }
        .comment-input-row .form-control {
            flex: 1; background: #f8fafc; border: 1px solid var(--ptx-border);
            border-radius: 14px; padding: 12px 18px; color: #15233C; outline: none;
            transition: 0.3s;
        }
        .comment-input-row .form-control:focus { background: #fff; border-color: var(--ptx-orange); }

        .comment-list { display: flex; flex-direction: column; gap: 20px; }
        .comment-item {
            background: #f8fafc; border: 1px solid rgba(21, 35, 60, 0.05);
            border-radius: 20px; padding: 20px; transition: 0.3s;
        }
        .comment-top { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .comment-name { font-size: 14.5px; font-weight: 700; color: #15233C; }
        .comment-date { font-size: 12px; color: var(--ptx-text-muted); }
        .comment-text { font-size: 14px; line-height: 1.6; color: #334155; }

        .reply-list { margin-left: 25px; margin-top: 18px; border-left: 3px solid rgba(255, 107, 26, 0.1); padding-left: 20px; display: flex; flex-direction: column; gap: 12px; }
        .reply-item { background: #fff; border-radius: 16px; padding: 14px 16px; border: 1px solid rgba(21, 35, 60, 0.03); }

        .reply-input-row { display: flex; gap: 10px; margin-top: 15px; }
        .reply-input-row input {
            flex: 1; height: 42px; background: #fff; border: 1px solid var(--ptx-border);
            border-radius: 12px; padding: 0 15px; color: #15233C; font-size: 13px;
            outline: none;
        }
        .reply-input-row input:focus { border-color: var(--ptx-orange); }

        .empty-comments { font-size: 14px; color: var(--ptx-text-muted); font-style: italic; padding: 10px 0; }

        .empty-box { text-align: center; padding: 60px 20px; color: var(--ptx-text-muted); }

    </style>
</head>
<body>

<div class="background-orbs"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">

    <!-- ===== NAVBAR ===== -->
    <?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title-main">Publications des agences</div>
                <div class="page-breadcrumb">
                    <i class="bi bi-house"></i>
                    <a href="client.php" style="color:inherit;text-decoration:none;">Accueil</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <span>Postes</span>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="section-header">
                <div>
                    <div class="section-title">Fil des publications</div>
                    <div class="section-sub">Consultez les posts des agences et interagissez avec eux</div>
                </div>
            </div>

            <div class="posts-toolbar">
                <input type="text" id="searchInput" class="search-input" placeholder="Rechercher par contenu, auteur ou agence...">

                <select id="agencyFilter" class="filter-select">
                    <option value="">Toutes les agences</option>
                </select>

                <select id="sortSelect" class="filter-select">
                    <option value="date_desc">Date &#8595; (r&eacute;cent)</option>
                    <option value="date_asc">Date &#8593; (ancien)</option>
                    <option value="author_asc">Auteur A-Z</option>
                    <option value="author_desc">Auteur Z-A</option>
                    <option value="likes_desc">J'aime &#8595;</option>
                    <option value="likes_asc">J'aime &#8593;</option>
                    <option value="comments_desc">Commentaires &#8595;</option>
                    <option value="comments_asc">Commentaires &#8593;</option>
                </select>
            </div>

            <div id="postsContainer" class="posts-grid"></div>

            <div id="emptyState" class="empty-box" style="display:none;">
                <i class="bi bi-inbox" style="font-size:28px; display:block; margin-bottom:10px;"></i>
                Aucun poste trouvé.
            </div>
        </div>
    </main>
</div>

<script src="assets_agences_postes/js/main.js"></script>
<script>
const CURRENT_CLIENT_ID = <?= json_encode($_SESSION['user_id'] ?? 0) ?>;
let posts = [];
let agencies = [];

function $(id) {
    return document.getElementById(id);
}

function escapeHtml(text) {
    return String(text ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}


async function apiPost(url, data) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    const result = await response.json();

    if (!response.ok || !result.success) {
        throw result;
    }

    return result;
}

function populateAgencyFilter() {
    const sel = $('agencyFilter');
    sel.innerHTML = '<option value="">Toutes les agences</option>';
    agencies.forEach(function (a) {
        const opt = document.createElement('option');
        opt.value = a.id_agence;
        opt.textContent = a.nom_agence;
        sel.appendChild(opt);
    });
}

async function loadAgencies() {
    try {
        const response = await fetch('get_agences_public.php');
        const result = await response.json();
        if (result.success) {
            agencies = result.data || result.agences || [];
            populateAgencyFilter();
        }
    } catch (e) {
        console.error('Erreur chargement agences', e);
    }
}

async function loadPosts() {
    try {
        const response = await fetch('get_posts.php');
        const result = await response.json();

        if (!result.success) {
            showToast('Erreur lors du chargement des posts.', 'danger');
            return;
        }

        posts = result.posts || [];
        renderPosts();
    } catch (e) {
        showToast('Impossible de charger les posts.', 'danger');
    }
}

function isHidden(c) {
    return Number(c.hidden) === 1;
}

function renderComments(post) {
    var visible = (post.comments || []).filter(function (c) { return !isHidden(c); });

    if (visible.length === 0) {
        return '<div class="empty-comments">Aucun commentaire pour le moment.</div>';
    }

    return visible.map(function (comment) {
        var visibleReplies = (comment.reponses || []).filter(function (r) { return !isHidden(r); });
        return '<div class="comment-item">' +
            '<div class="comment-top">' +
                '<div class="comment-name">' + escapeHtml(comment.auteur) + '</div>' +
                '<div class="comment-date"><i class="bi bi-clock"></i> ' + escapeHtml(comment.date_commentaire) + '</div>' +
            '</div>' +
            '<div class="comment-text">' + escapeHtml(comment.contenu) + '</div>' +
            '<div class="reply-input-row">' +
                '<input type="text" id="replyInput-' + post.id_poste + '-' + comment.id_commentaire + '" class="form-control" placeholder="Répondre...">' +
                '<button class="btn-soft" type="button" onclick="addReply(' + post.id_poste + ', ' + comment.id_commentaire + ')" style="padding: 5px 12px; height: 34px;">' +
                    '<i class="bi bi-reply"></i>' +
                '</button>' +
            '</div>' +
            (visibleReplies.length ? '<div class="reply-list">' +
                visibleReplies.map(function (reply) {
                    return '<div class="reply-item">' +
                        '<div class="comment-top">' +
                            '<div class="comment-name" style="font-size: 12px;">' + escapeHtml(reply.auteur) + '</div>' +
                            '<div class="comment-date" style="font-size: 10px;">' + escapeHtml(reply.date_commentaire) + '</div>' +
                        '</div>' +
                        '<div class="comment-text" style="font-size: 12.5px;">' + escapeHtml(reply.contenu) + '</div>' +
                    '</div>';
                }).join('') +
            '</div>' : '') +
        '</div>';
    }).join('');
}

function renderPosts() {
    const container = $('postsContainer');
    const emptyState = $('emptyState');
    const search = $('searchInput').value.toLowerCase().trim();
    const agency = $('agencyFilter').value;
    const sort = $('sortSelect').value;

    let filtered = posts.filter(post => {
        const text = `${post.contenu} ${post.auteur} ${post.nom_agence || ''}`.toLowerCase();
        return (!search || text.includes(search)) && (!agency || Number(post.id_agence) === Number(agency));
    });

    switch (sort) {
        case 'date_asc': filtered.sort((a, b) => new Date(a.date_publication) - new Date(b.date_publication)); break;
        case 'author_asc': filtered.sort((a, b) => (a.auteur || '').localeCompare(b.auteur || '')); break;
        case 'author_desc': filtered.sort((a, b) => (b.auteur || '').localeCompare(a.auteur || '')); break;
        case 'likes_desc': filtered.sort((a, b) => (b.nb_likes || 0) - (a.nb_likes || 0)); break;
        case 'likes_asc': filtered.sort((a, b) => (a.nb_likes || 0) - (b.nb_likes || 0)); break;
        case 'comments_desc': filtered.sort((a, b) => (b.nb_commentaires || 0) - (a.nb_commentaires || 0)); break;
        case 'comments_asc': filtered.sort((a, b) => (a.nb_commentaires || 0) - (b.nb_commentaires || 0)); break;
        default: filtered.sort((a, b) => new Date(b.date_publication) - new Date(a.date_publication));
    }

    if (filtered.length === 0) {
        container.innerHTML = '';
        emptyState.style.display = 'block';
        return;
    }

    emptyState.style.display = 'none';

    container.innerHTML = filtered.map(post => `
        <div class="post-card">
            <div class="post-card-head">
                <div>
                    <div class="post-card-title">${escapeHtml(post.auteur || 'Agence Protex')}</div>
                    <div class="post-card-sub">
                        <i class="bi bi-building"></i> ${escapeHtml(post.nom_agence || 'Siège')} 
                        <span style="opacity: 0.5; margin: 0 5px;">•</span>
                        <i class="bi bi-calendar3"></i> ${formatDate(post.date_publication)}
                    </div>
                </div>
                <span class="badge badge-info"><i class="bi bi-megaphone"></i> Publication</span>
            </div>

            <div class="post-card-body">
                <div class="post-content">${escapeHtml(post.contenu)}</div>

                <div class="post-meta">
                    <span><i class="bi bi-heart-fill" style="color: var(--ptx-orange)"></i> ${post.nb_likes || 0}</span>
                    <span><i class="bi bi-chat-dots-fill" style="color: var(--ptx-accent)"></i> ${post.nb_commentaires || 0}</span>
                </div>

                <div class="post-actions">
                    <button class="btn-soft btn-like" type="button" onclick="likePost(${post.id_poste})">
                        <i class="bi bi-heart"></i> J'aime
                    </button>
                </div>

                <div class="comments-wrap">
                    <div class="comment-input-row">
                        <input type="text" id="commentInput-${post.id_poste}" class="form-control" placeholder="Votre commentaire...">
                        <button class="btn-nav-primary" type="button" onclick="addComment(${post.id_poste})">
                            <i class="bi bi-send"></i>
                        </button>
                    </div>

                    <div class="comment-list">
                        ${renderComments(post)}
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

async function likePost(idPoste) {
    try {
        const result = await apiPost('like_post.php', {
            id_poste: idPoste,
            id_client: CURRENT_CLIENT_ID
        });

        showToast(result.message || 'Like ajouté.', 'success');
        await loadPosts();
    } catch (err) {
        showToast(err.message || 'Erreur like.', 'warning');
    }
}

async function addComment(idPoste) {
    const input = $('commentInput-' + idPoste);
    const contenu = input.value.trim();

    if (!contenu) {
        showToast('Veuillez écrire un commentaire.', 'warning');
        return;
    }

    try {
        const result = await apiPost('add_comment.php', {
            id_poste: idPoste,
            id_client: CURRENT_CLIENT_ID,
            contenu: contenu
        });

        input.value = '';
        showToast(result.message || 'Commentaire ajouté.', 'success');
        await loadPosts();
    } catch (err) {
        showToast(err.message || 'Erreur commentaire.', 'danger');
    }
}

async function addReply(idPoste, idCommentaireParent) {
    const input = $('replyInput-' + idPoste + '-' + idCommentaireParent);
    const contenu = input.value.trim();

    if (!contenu) {
        showToast('Veuillez écrire une réponse.', 'warning');
        return;
    }

    try {
        const result = await apiPost('add_reply.php', {
            id_poste: idPoste,
            id_client: CURRENT_CLIENT_ID,
            id_commentaire_parent: idCommentaireParent,
            contenu: contenu
        });

        input.value = '';
        showToast(result.message || 'Réponse ajoutée.', 'success');
        await loadPosts();
    } catch (err) {
        showToast(err.message || 'Erreur réponse.', 'danger');
    }
}

document.addEventListener('DOMContentLoaded', async function () {
    $('searchInput').addEventListener('input', renderPosts);
    $('agencyFilter').addEventListener('change', renderPosts);
    $('sortSelect').addEventListener('change', renderPosts);
    await loadAgencies();
    await loadPosts();
});
</script>

</body>
</html>

