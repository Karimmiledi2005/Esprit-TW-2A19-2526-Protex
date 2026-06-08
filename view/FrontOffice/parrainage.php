<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();
$userId = (int)$_SESSION['user_id'];
$pageTitle = 'Parrainage';
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Parrainage — Protex</title>
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
<style>
.referral-card{background:#fff;border-radius:20px;padding:2rem 2.5rem;box-shadow:0 4px 24px rgba(0,0,0,.06);max-width:720px;margin:0 auto}
.referral-hero{text-align:center;padding:1rem 0 1.25rem}
.referral-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(244,162,97,0.12);border:1px solid rgba(244,162,97,0.25);border-radius:99px;padding:4px 14px;font-size:12px;color:var(--gold,#f4a261);margin-bottom:1rem}
.referral-title{font-family:var(--font-display,'Sora',sans-serif);font-size:28px;font-weight:700;color:#15233C;margin-bottom:.5rem}
.referral-sub{font-size:14px;color:rgba(21,35,60,.55);max-width:440px;margin:0 auto;line-height:1.6}
.code-box{background:#f8fafd;border:1px solid rgba(21,35,60,.08);border-radius:16px;padding:1.5rem;text-align:center;margin:1.5rem 0}
.code-label{font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:rgba(21,35,60,.45);margin-bottom:.75rem}
.code-display{font-family:var(--font-display,'Sora',sans-serif);font-size:36px;font-weight:700;color:var(--accent,#00b4d8);letter-spacing:.12em;margin-bottom:.5rem;background:rgba(0,180,216,0.08);border-radius:10px;padding:.5rem 1.5rem;display:inline-block;border:1px solid rgba(0,180,216,0.2)}
.code-sub{font-size:12px;color:rgba(21,35,60,.45);margin-bottom:1.25rem}
.share-btns{display:flex;gap:8px;justify-content:center;flex-wrap:wrap}
.share-btn{padding:9px 18px;border-radius:9px;border:none;font-size:13px;font-weight:500;cursor:pointer;font-family:var(--font-body,'DM Sans',sans-serif);transition:all .18s;display:flex;align-items:center;gap:7px}
.share-btn.whatsapp{background:#25D366;color:#fff}.share-btn.whatsapp:hover{background:#128C7E}
.share-btn.copy{background:#f0f4f8;color:#15233C;border:1px solid rgba(21,35,60,.1)}.share-btn.copy:hover{background:#e2e8f0}
.share-btn.email{background:rgba(0,180,216,.12);color:var(--accent,#00b4d8);border:1px solid rgba(0,180,216,.25)}
.rewards-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin:1.25rem 0}
.reward-card{background:#f8fafd;border:1px solid rgba(21,35,60,.08);border-radius:12px;padding:1rem;text-align:center}
.reward-icon{font-size:24px;margin-bottom:6px}
.reward-pts{font-family:var(--font-display,'Sora',sans-serif);font-size:22px;font-weight:700;margin-bottom:3px}
.reward-label{font-size:12px;color:rgba(21,35,60,.5);line-height:1.5}
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin:1.25rem 0}
.stat-box{background:#f8fafd;border:1px solid rgba(21,35,60,.08);border-radius:12px;padding:.9rem;text-align:center}
.stat-num{font-family:var(--font-display,'Sora',sans-serif);font-size:24px;font-weight:700;color:#15233C}
.stat-lbl{font-size:11.5px;color:rgba(21,35,60,.5);margin-top:2px}
.filleuls-list{background:#f8fafd;border:1px solid rgba(21,35,60,.08);border-radius:14px;overflow:hidden;margin-top:1.25rem}
.filleuls-header{padding:.85rem 1rem;border-bottom:1px solid rgba(21,35,60,.08);font-size:13px;font-weight:600;color:#15233C}
.filleul-row{display:flex;align-items:center;gap:10px;padding:.8rem 1rem;border-bottom:1px solid rgba(21,35,60,.04)}
.filleul-row:last-child{border:none}
.filleul-av{width:34px;height:34px;border-radius:8px;flex-shrink:0;background:linear-gradient(135deg,var(--accent,#00b4d8),#7c3aed);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;color:#fff}
.filleul-name{font-size:13px;font-weight:500;color:#15233C}
.filleul-date{font-size:11.5px;color:rgba(21,35,60,.5)}
.filleul-status{margin-left:auto;font-size:11px;padding:2px 9px;border-radius:99px;font-weight:500}
.status-recompense{background:rgba(34,197,94,.12);color:var(--success,#22c55e)}
.status-en_attente{background:rgba(245,158,11,.12);color:#f59e0b}
.empty-filleuls{text-align:center;padding:2rem;color:rgba(21,35,60,.5);font-size:13px}
.how-steps{display:flex;gap:0;margin:1.25rem 0}
.how-step{flex:1;text-align:center;position:relative;padding:0 .5rem}
.how-step:not(:last-child)::after{content:'';position:absolute;top:20px;left:50%;width:100%;height:2px;background:rgba(21,35,60,.08)}
.how-dot{width:40px;height:40px;border-radius:50%;margin:0 auto .75rem;background:rgba(0,180,216,.12);border:1px solid rgba(0,180,216,.25);display:flex;align-items:center;justify-content:center;font-size:18px;position:relative;z-index:1}
.how-label{font-size:12px;color:rgba(21,35,60,.5);line-height:1.4}
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
        <div class="page-title-main">Parrainage</div>
        <div class="page-breadcrumb">
            <i class="bi bi-house"></i>
            <a href="client.php">Accueil</a>
            <i class="bi bi-chevron-right"></i>
            <span>Parrainage</span>
        </div>
    </div>
</div>

<div class="content" style="display:flex;justify-content:center">

  <div class="referral-card">

  <div class="referral-hero">
    <div class="referral-badge"><i class="bi bi-gift-fill"></i> Programme de Parrainage</div>
    <h1 class="referral-title">Invitez vos amis,<br>gagnez des récompenses</h1>
    <p class="referral-sub">Parrainez un proche et recevez tous les deux des avantages exclusifs Protex</p>
  </div>

  <div class="how-steps">
    <div class="how-step"><div class="how-dot"><i class="bi bi-share"></i></div><div class="how-label">Partagez votre<br>code unique</div></div>
    <div class="how-step"><div class="how-dot"><i class="bi bi-person-plus"></i></div><div class="how-label">Votre ami<br>s'inscrit</div></div>
    <div class="how-step"><div class="how-dot"><i class="bi bi-file-earmark-check"></i></div><div class="how-label">Il souscrit son<br>premier contrat</div></div>
    <div class="how-step"><div class="how-dot"><i class="bi bi-award"></i></div><div class="how-label">Vous êtes<br>tous les deux récompensés</div></div>
  </div>

  <div class="rewards-grid">
    <div class="reward-card">
      <div class="reward-icon"><i class="bi bi-star-fill" style="color:var(--gold,#f4a261)"></i></div>
      <div class="reward-pts" style="color:var(--gold,#f4a261)">+150 pts</div>
      <div class="reward-label">Pour vous (le parrain)<br>+ -5% sur votre renouvellement</div>
    </div>
    <div class="reward-card">
      <div class="reward-icon"><i class="bi bi-gift-fill" style="color:var(--success,#22c55e)"></i></div>
      <div class="reward-pts" style="color:var(--success,#22c55e)">-5% + 50 pts</div>
      <div class="reward-label">Pour votre ami (le filleul)<br>sur sa première prime</div>
    </div>
  </div>

  <div class="code-box">
    <div class="code-label">Votre code de parrainage</div>
    <div class="code-display" id="codeDisplay">…</div>
    <div class="code-sub">Partagez ce code ou le lien ci-dessous</div>
    <div class="share-btns">
      <button class="share-btn whatsapp" onclick="shareWhatsapp()"><i class="bi bi-whatsapp"></i> Partager WhatsApp</button>
      <button class="share-btn copy" id="btnCopy" onclick="copyLink()"><i class="bi bi-clipboard"></i> Copier le lien</button>
      <button class="share-btn email" onclick="shareEmail()"><i class="bi bi-envelope"></i> Email</button>
    </div>
  </div>

  <div class="stats-row" id="statsRow">
    <div class="stat-box"><div class="stat-num" id="statTotal">—</div><div class="stat-lbl">Amis parrainés</div></div>
    <div class="stat-box"><div class="stat-num" id="statConvertis">—</div><div class="stat-lbl">Convertis</div></div>
    <div class="stat-box"><div class="stat-num" id="statPts" style="color:var(--gold,#f4a261)">—</div><div class="stat-lbl">Points gagnés</div></div>
  </div>

  <div class="filleuls-list">
    <div class="filleuls-header"><i class="bi bi-people"></i> Mes filleuls</div>
    <div id="filleulsList"><div class="empty-filleuls"><i class="bi bi-hourglass-split"></i> Chargement…</div></div>
  </div>
</div>
</main>
</div>

<script>
let myCode = '';
const BASE_URL = window.location.origin + '/assurance/view/FrontOffice';

document.addEventListener('DOMContentLoaded', loadData);

async function loadData() {
  try {
    const r = await fetch('../../api.php?action=get_mon_code_parrain');
    const d = await r.json();
    if (!d.success) return;

    myCode = d.code;
    document.getElementById('codeDisplay').textContent = myCode;

    const s = d.stats || {};
    document.getElementById('statTotal').textContent    = s.total_parrainages || 0;
    document.getElementById('statConvertis').textContent= s.convertis || 0;
    document.getElementById('statPts').textContent      = (s.pts_gagnes || 0) + ' pts';

    const list = d.filleuls || [];
    const el   = document.getElementById('filleulsList');
    if (!list.length) {
      el.innerHTML = '<div class="empty-filleuls"><div style="font-size:32px;opacity:.3;margin-bottom:.5rem"><i class="bi bi-people"></i></div>Vous n\'avez pas encore de filleuls.<br><span style="color:var(--accent,#00b4d8)">Partagez votre code pour commencer !</span></div>';
    } else {
      el.innerHTML = list.map(f => {
        const initials = ((f.prenom||'')[0]||'') + ((f.nom||'')[0]||'');
        const date     = new Date(f.created_at).toLocaleDateString('fr-FR');
        const sc       = f.statut === 'recompense' ? 'status-recompense' : 'status-en_attente';
        const sl       = f.statut === 'recompense' ? 'Récompensé' : 'En attente';
        return '<div class="filleul-row"><div class="filleul-av">'+(initials||'?').toUpperCase()+'</div><div><div class="filleul-name">'+escHtml(f.prenom)+' '+escHtml(f.nom)+'</div><div class="filleul-date">Inscrit le '+date+'</div></div><span class="filleul-status '+sc+'">'+sl+'</span></div>';
      }).join('');
    }
  } catch(e) { console.error(e); }
}

function getReferralLink() {
  return BASE_URL + '/login.html?ref=' + myCode;
}

function shareWhatsapp() {
  const msg = encodeURIComponent('Salut ! Assure-toi avec Protex et profite de -5% sur ta première prime grâce à mon code de parrainage : '+myCode+'\n\nInscris-toi ici : '+getReferralLink());
  window.open('https://wa.me/?text='+msg, '_blank');
}

function copyLink() {
  navigator.clipboard.writeText(getReferralLink()).then(() => {
    const btn = document.getElementById('btnCopy');
    btn.innerHTML = ' Copié !';
    btn.style.color = 'var(--success,#22c55e)';
    setTimeout(() => { btn.innerHTML = ' Copier le lien'; btn.style.color=''; }, 2500);
  });
}

function shareEmail() {
  const subject = encodeURIComponent('Rejoins Protex avec mon code parrain !');
  const body    = encodeURIComponent('Bonjour,\n\nJe t\'invite à rejoindre Protex, ma plateforme d\'assurance digitale.\n\nUtilise mon code parrain '+myCode+' pour profiter de -5% sur ta première prime.\n\nLien d\'inscription : '+getReferralLink()+'\n\nÀ bientôt sur Protex !');
  window.location.href = 'mailto:?subject='+subject+'&body='+body;
}

function escHtml(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')}
</script>
<script src="assets/js/main.js"></script>
</body>
</html>
