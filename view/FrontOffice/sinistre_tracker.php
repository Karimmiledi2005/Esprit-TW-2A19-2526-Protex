<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();

$base     = defined('BASE_URL') ? BASE_URL : '/assurance';
$userId   = (int)($_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 0);
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Suivi de mes sinistres — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Suivez l'avancement de chacun de vos sinistres déclarés sur Protex, étape par étape.">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=Inter:wght@300;400;500;600&display=swap">
    <style>
        :root {
            --primary: #1A3A7A;
            --accent:  #FF6B1A;
            --success: #2EC4B6;
            --danger:  #e63946;
            --warning: #EF9F27;
            --bg:      #F4F6FB;
            --card:    #FFFFFF;
            --border:  rgba(26,58,122,0.10);
            --text:    #15233C;
            --muted:   #6B7A90;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            color: var(--text);
            min-height: 100vh;
        }

        /* ── Top Bar ─────────────────────────────────────── */
        .top-bar {
            background: linear-gradient(135deg, #0f2557, #1A3A7A);
            padding: 16px 32px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 4px 20px rgba(15,37,87,0.3);
        }
        .top-bar .logo {
            font-family: 'Outfit', sans-serif;
            font-size: 22px; font-weight: 900;
            color: #fff; letter-spacing: -0.5px;
        }
        .top-bar .logo span { color: var(--accent); }
        .back-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 9px 18px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50px; color: #fff;
            font-size: 13px; font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .back-btn:hover {
            background: rgba(255,255,255,0.22);
            color: #fff; transform: translateX(-3px);
        }

        /* ── Page ────────────────────────────────────────── */
        .page { max-width: 900px; margin: 40px auto; padding: 0 20px 60px; }

        .page-title {
            font-family: 'Outfit', sans-serif;
            font-size: 28px; font-weight: 800;
            color: var(--text); margin-bottom: 6px;
        }
        .page-sub { font-size: 14px; color: var(--muted); margin-bottom: 32px; }

        /* ── Sinistre Card ───────────────────────────────── */
        .sinistre-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: 0 4px 24px rgba(26,58,122,0.07);
            margin-bottom: 28px;
            overflow: hidden;
            transition: box-shadow 0.3s ease, transform 0.3s ease;
        }
        .sinistre-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(26,58,122,0.12);
        }
        .sinistre-card-head {
            display: flex; align-items: center; gap: 16px;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
        }
        .sinistre-type-icon {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; flex-shrink: 0;
        }
        .sinistre-ref {
            font-family: 'Outfit', sans-serif;
            font-size: 15px; font-weight: 700;
            color: var(--text);
        }
        .sinistre-type-label {
            font-size: 13px; color: var(--muted); margin-top: 2px;
        }
        .sinistre-meta {
            margin-left: auto; display: flex; align-items: center; gap: 12px;
        }
        .badge-statut {
            padding: 5px 12px; border-radius: 20px;
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.05em;
        }
        .badge-en_attente  { background: rgba(239,159,39,0.15); color: #b97a00; }
        .badge-en_traitement { background: rgba(26,58,122,0.12);  color: #1A3A7A; }
        .badge-en_analyse    { background: rgba(239,159,39,0.15); color: #b97a00; }
        .badge-assigne       { background: rgba(26,58,122,0.12);  color: #1A3A7A; }
        .badge-en_cours      { background: rgba(46,196,182,0.15); color: #1a9e94; }
        .badge-cloture       { background: rgba(108,117,125,0.15);color: #6c757d; }
        .badge-rembourse   { background: rgba(46,196,182,0.15); color: #1a9e94; }
        .badge-refuse      { background: rgba(230,57,70,0.12);  color: #c42532; }

        .toggle-arrow {
            font-size: 18px; color: var(--muted);
            transition: transform 0.3s ease;
        }
        .sinistre-card.expanded .toggle-arrow {
            transform: rotate(180deg);
        }

        /* ── Timeline ────────────────────────────────────── */
        .sinistre-body {
            display: none;
            padding: 28px 24px;
        }
        .sinistre-card.expanded .sinistre-body {
            display: block;
            animation: fadeSlideIn 0.3s ease;
        }
        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .tracker-steps {
            display: flex;
            gap: 0;
            position: relative;
            margin-bottom: 28px;
        }
        .tracker-steps::before {
            content: '';
            position: absolute;
            top: 22px; left: 22px; right: 22px;
            height: 4px;
            background: rgba(26,58,122,0.08);
            border-radius: 2px;
            z-index: 0;
        }
        .tracker-step {
            flex: 1;
            display: flex; flex-direction: column; align-items: center;
            gap: 8px; position: relative; z-index: 1;
        }
        .step-circle {
            width: 44px; height: 44px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            background: rgba(26,58,122,0.07);
            border: 3px solid rgba(26,58,122,0.12);
            color: var(--muted);
            transition: all 0.4s ease;
        }
        .tracker-step.done .step-circle {
            background: var(--success);
            border-color: var(--success);
            color: #fff;
            box-shadow: 0 0 0 4px rgba(46,196,182,0.2);
        }
        .tracker-step.active .step-circle {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
            box-shadow: 0 0 0 6px rgba(255,107,26,0.18);
            animation: activePulse 1.8s ease-in-out infinite;
        }
        .tracker-step.refused .step-circle {
            background: var(--danger);
            border-color: var(--danger);
            color: #fff;
        }
        @keyframes activePulse {
            0%, 100% { box-shadow: 0 0 0 6px rgba(255,107,26,0.18); }
            50%       { box-shadow: 0 0 0 12px rgba(255,107,26,0.06); }
        }
        .step-label {
            font-size: 10px; font-weight: 700;
            text-align: center; color: var(--muted);
            text-transform: uppercase; letter-spacing: 0.05em;
            line-height: 1.3;
        }
        .tracker-step.done   .step-label { color: var(--success); }
        .tracker-step.active .step-label { color: var(--accent); }
        .tracker-step.refused .step-label { color: var(--danger); }

        /* Progress bar fill */
        .tracker-progress {
            position: absolute;
            top: 22px; left: 22px;
            height: 4px;
            background: linear-gradient(90deg, var(--success), var(--accent));
            border-radius: 2px;
            z-index: 0;
            transition: width 0.6s ease;
        }

        /* ── Description & Checklist ─────────────────────── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 20px;
        }
        @media(max-width: 600px) { .info-grid { grid-template-columns: 1fr; } }

        .info-box {
            background: rgba(26,58,122,0.03);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
        }
        .info-box-title {
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.07em;
            color: var(--muted); margin-bottom: 10px;
        }
        .info-box p { font-size: 13px; color: var(--text); line-height: 1.6; }

        .checklist { list-style: none; }
        .checklist li {
            display: flex; align-items: center; gap: 9px;
            font-size: 13px; color: var(--text);
            padding: 5px 0;
            border-bottom: 1px solid rgba(26,58,122,0.05);
        }
        .checklist li:last-child { border-bottom: none; }
        .checklist li i { font-size: 14px; flex-shrink: 0; }
        .chk-ok   { color: var(--success); }
        .chk-miss { color: var(--muted); }

        .fraud-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px; border-radius: 20px;
            font-size: 12px; font-weight: 700;
            margin-top: 12px;
        }
        .fraud-faible   { background: rgba(46,196,182,0.12);  color: #1a9e94; }
        .fraud-modere   { background: rgba(239,159,39,0.12);  color: #b97a00; }
        .fraud-eleve    { background: rgba(230,57,70,0.12);   color: #c42532; }

        /* ── Empty state ─────────────────────────────────── */
        .empty-state {
            text-align: center; padding: 80px 20px;
        }
        .empty-icon {
            font-size: 64px; color: rgba(26,58,122,0.15);
            display: block; margin-bottom: 20px;
        }
        .empty-title {
            font-family: 'Outfit', sans-serif;
            font-size: 20px; font-weight: 700;
            color: var(--text); margin-bottom: 10px;
        }
        .empty-sub { font-size: 14px; color: var(--muted); }

        /* ── Loading ─────────────────────────────────────── */
        .loading-wrap {
            display: flex; align-items: center; justify-content: center;
            padding: 60px; gap: 12px; color: var(--muted);
        }
        .spinner {
            width: 28px; height: 28px;
            border: 3px solid rgba(26,58,122,0.12);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<!-- ── Top Bar ─────────────────────────────────────────────── -->
<div class="top-bar">
    <div class="logo">Prot<span>ex</span></div>
    <a href="client.php" class="back-btn">
        <i class="bi bi-arrow-left"></i> Tableau de bord
    </a>
</div>

<!-- ── Page Content ───────────────────────────────────────── -->
<div class="page">
    <h1 class="page-title"><i class="bi bi-clipboard2-pulse" style="color:var(--accent)"></i> Suivi de mes sinistres</h1>
    <p class="page-sub">Consultez l'avancement de chacun de vos sinistres déclarés en temps réel.</p>

    <!-- Container filled by JS -->
    <div id="trackerContainer">
        <div class="loading-wrap">
            <div class="spinner"></div>
            <span>Chargement de vos sinistres…</span>
        </div>
    </div>
</div>

<script>
const SINISTRE_API = 'sinistre_list_user.php';

const STEP_CONFIG = [
    { key: 'declared', label: 'Déclaré',  icon: 'bi-file-earmark-plus' },
    { key: 'progress', label: 'En cours', icon: 'bi-search'            },
    { key: 'decision', label: 'Décision', icon: 'bi-gavel'             },
    { key: 'closed',   label: 'Clôturé',  icon: 'bi-check-circle'      },
];

const TYPE_ICONS = {
    'Accident auto':         { icon: 'bi-car-front',            bg: 'rgba(26,58,122,0.10)',  color: '#1A3A7A' },
    'Incendie':              { icon: 'bi-fire',                  bg: 'rgba(239,68,68,0.10)',  color: '#e63946' },
    'Vol':                   { icon: 'bi-shield-x',              bg: 'rgba(239,159,39,0.10)', color: '#EF9F27' },
    'Degat des eaux':        { icon: 'bi-droplet-fill',          bg: 'rgba(46,196,182,0.10)', color: '#2EC4B6' },
    'Bris de glace':         { icon: 'bi-columns-gap',           bg: 'rgba(26,58,122,0.08)', color: '#1A3A7A' },
    'Catastrophe naturelle': { icon: 'bi-cloud-lightning-rain',  bg: 'rgba(106,90,205,0.10)',color: '#6a5acd' },
    'Deces':                 { icon: 'bi-heart-pulse',           bg: 'rgba(230,57,70,0.10)', color: '#e63946' },
    'Invalidite':            { icon: 'bi-person-wheelchair',     bg: 'rgba(26,58,122,0.10)', color: '#1A3A7A' },
    'Hospitalisation':       { icon: 'bi-hospital',              bg: 'rgba(46,196,182,0.10)',color: '#2EC4B6' },
    'Accident':              { icon: 'bi-bandaid',               bg: 'rgba(239,159,39,0.10)',color: '#EF9F27' },
    'Maladie':               { icon: 'bi-thermometer-half',      bg: 'rgba(230,57,70,0.10)', color: '#e63946' },
};

const STATUS_TO_STEP = {
    'en_attente':   0,
    'en_analyse':   1,
    'assigne':      1,
    'en_cours':     1,
    'rembourse':    2,
    'refuse':       2,
    'cloture':      3,
};

const STATUS_LABELS = {
    'en_attente':   'En attente',
    'en_analyse':   'En analyse',
    'assigne':      'Assigné',
    'en_cours':     'En cours',
    'rembourse':    'Remboursé',
    'refuse':       'Refusé',
    'cloture':      'Clôturé',
};

function getStepIndex(statut) {
    return STATUS_TO_STEP[statut] ?? 0;
}

function buildStepperHTML(statut) {
    const activeIdx = getStepIndex(statut);
    const refused   = statut === 'refuse';
    const pct       = Math.max(0, Math.min(100, (activeIdx / (STEP_CONFIG.length - 1)) * 100));

    let stepsHTML = '';
    STEP_CONFIG.forEach((s, i) => {
        let cls = '';
        if (refused && i === activeIdx) cls = 'refused';
        else if (i < activeIdx)  cls = 'done';
        else if (i === activeIdx) cls = 'active';
        stepsHTML += `<div class="tracker-step ${cls}">
            <div class="step-circle"><i class="bi ${s.icon}"></i></div>
            <span class="step-label">${s.label}</span>
        </div>`;
    });

    return `<div class="tracker-steps" style="position:relative;">
        <div class="tracker-progress" style="width: calc(${pct}% - 44px);"></div>
        ${stepsHTML}
    </div>`;
}

function buildChecklist(statut, hasPhoto) {
    const items = [
        { label: 'Déclaration soumise',     done: true },
        { label: 'Photo / justificatif',     done: hasPhoto },
        { label: 'Analyse du dossier',       done: ['en_analyse','assigne','en_cours','rembourse','refuse','cloture'].includes(statut) },
        { label: 'Décision rendue',          done: ['rembourse','refuse','cloture'].includes(statut) },
        { label: 'Clôture du dossier',       done: statut === 'cloture' },
    ];
    return '<ul class="checklist">' + items.map(it =>
        `<li><i class="bi ${it.done ? 'bi-check-circle-fill chk-ok' : 'bi-circle chk-miss'}"></i>${it.label}</li>`
    ).join('') + '</ul>';
}

function fraudBadge(score, niveau) {
    if (!score && score !== 0) return '';
    const s = parseInt(score, 10);
    let cls = 'fraud-faible', label = '✅ Risque faible';
    if (s >= 81) { cls = 'fraud-eleve'; label = '🔴 Fraude probable'; }
    else if (s >= 31) { cls = 'fraud-modere'; label = '⚠️ Risque modéré'; }
    return `<div class="fraud-badge ${cls}"><i class="bi bi-shield-check"></i> Score fraude : ${s}/100 — ${label}</div>`;
}

function buildCard(s, idx) {
    const statut   = s.statut || 'en_attente';
    const ref      = 'SIN-' + String(s.id_sinistre).padStart(4, '0');
    const typeInfo = TYPE_ICONS[s.type] || { icon: 'bi-exclamation-circle', bg: 'rgba(26,58,122,0.10)', color: '#1A3A7A' };
    const badgeCls = 'badge-' + statut;
    const badgeLbl = STATUS_LABELS[statut] || statut;
    const dateStr  = s.date_declaration ? new Date(s.date_declaration).toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric' }) : '—';
    const hasPhoto = !!s.photo_url;

    return `
    <div class="sinistre-card" id="card-${idx}">
        <div class="sinistre-card-head" onclick="toggleCard(${idx})">
            <div class="sinistre-type-icon" style="background:${typeInfo.bg}; color:${typeInfo.color};">
                <i class="bi ${typeInfo.icon}"></i>
            </div>
            <div>
                <div class="sinistre-ref">${ref}</div>
                <div class="sinistre-type-label"><i class="bi bi-tag" style="margin-right:4px;"></i>${s.type || 'Sinistre'} — ${dateStr}</div>
            </div>
            <div class="sinistre-meta">
                <span class="badge-statut ${badgeCls}">${badgeLbl}</span>
                <i class="bi bi-chevron-down toggle-arrow"></i>
            </div>
        </div>
        <div class="sinistre-body">
            ${buildStepperHTML(statut)}
            <div class="info-grid">
                <div class="info-box">
                    <div class="info-box-title"><i class="bi bi-chat-left-text"></i> Description</div>
                    <p>${s.description ? s.description.substring(0, 200) + (s.description.length > 200 ? '…' : '') : 'Aucune description fournie.'}</p>
                    ${fraudBadge(s.fraud_score, s.fraud_niveau)}
                </div>
                <div class="info-box">
                    <div class="info-box-title"><i class="bi bi-list-check"></i> Pièces du dossier</div>
                    ${buildChecklist(statut, hasPhoto)}
                </div>
            </div>
            ${s.fraud_suggestion ? `<div style="margin-top:14px; padding:12px 16px; background:rgba(239,159,39,0.08); border:1px solid rgba(239,159,39,0.20); border-radius:10px; font-size:12px; color:#b97a00;">
                <i class="bi bi-lightbulb"></i> <strong>Suggestion :</strong> ${s.fraud_suggestion}
            </div>` : ''}
            <div style="margin-top:20px; text-align:right;">
                <button class="btn btn-outline-primary btn-sm" onclick="window.open('sinistre_message.php?id_sinistre=${s.id_sinistre}', 'chatWindow', 'width=450,height=600')">
                    <i class="bi bi-chat-dots"></i> Contacter l'assistance
                </button>
            </div>
        </div>
    </div>`;
}

function toggleCard(idx) {
    const card = document.getElementById('card-' + idx);
    card.classList.toggle('expanded');
}

async function loadSinistres() {
    const container = document.getElementById('trackerContainer');
    try {
        const res  = await fetch(SINISTRE_API);
        const data = await res.json();
        const list = Array.isArray(data) ? data : (data.data || []);

        if (!list.length) {
            container.innerHTML = `<div class="empty-state">
                <i class="bi bi-inbox empty-icon"></i>
                <div class="empty-title">Aucun sinistre déclaré</div>
                <p class="empty-sub">Vous n'avez pas encore déclaré de sinistre.<br>
                    <a href="declarer-sinistre.php" style="color:var(--accent); font-weight:600;">Déclarer un sinistre</a>
                </p>
            </div>`;
            return;
        }

        container.innerHTML = list.map((s, i) => buildCard(s, i)).join('');
        // Auto-expand first card
        const first = document.getElementById('card-0');
        if (first) first.classList.add('expanded');

    } catch (e) {
        container.innerHTML = `<div class="empty-state">
            <i class="bi bi-wifi-off empty-icon"></i>
            <div class="empty-title">Erreur de connexion</div>
            <p class="empty-sub">Impossible de charger vos sinistres. Vérifiez votre connexion.</p>
        </div>`;
    }
}

// Auto-refresh every 30s
loadSinistres();
setInterval(loadSinistres, 30000);
</script>
</body>
</html>
