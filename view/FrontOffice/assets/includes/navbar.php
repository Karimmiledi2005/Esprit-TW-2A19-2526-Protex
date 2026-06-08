<?php
require_once dirname(__DIR__, 4) . '/helpers/SessionGuard.php';
require_once dirname(__DIR__, 4) . '/helpers/SidebarUser.php';
/**
 * view/FrontOffice/assets/includes/navbar.php
 * Avatar centralisé via SidebarUser + protex-user.js (sidebar-user.js)
 */

if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 4) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}

$frontBase = BASE_URL . '/view/FrontOffice';
$ctrlBase  = BASE_URL . '/controller';
$uri = strtolower($_SERVER['REQUEST_URI'] ?? '');

function frontActive(string ...$keys): string {
    global $uri;
    foreach ($keys as $key) {
        if (str_contains($uri, strtolower($key))) return 'active';
    }
    return '';
}

$navUid = SessionGuard::userId();
$navUser = $navUid > 0 ? SidebarUser::fetchUserById($navUid) : null;

$sessionPrenom = $navUser['prenom'] ?? $_SESSION['user_prenom'] ?? $_SESSION['prenom'] ?? '';
$sessionNom    = $navUser['nom']    ?? $_SESSION['user_nom']    ?? $_SESSION['nom']    ?? '';
$sessionEmail  = $navUser['email']  ?? $_SESSION['user_email']  ?? '';
$sessionRole   = $navUser['role']   ?? $_SESSION['user_role']   ?? $_SESSION['role']   ?? 'client';

$displayName = trim($sessionPrenom . ' ' . $sessionNom);
if ($displayName === '') {
    $displayName = 'Mon compte';
}

$initiales = SidebarUser::initials($sessionPrenom, $sessionNom);
if ($initiales === 'AD') {
    $initiales = 'CL';
}

$navAvatarUrl = SidebarUser::resolveAvatarUrl(
    $navUser['avatar'] ?? null,
    $navUser['avatar_url'] ?? null
);
$navAvatarHtml = SidebarUser::renderAvatarInner($initiales, $navAvatarUrl);

$roleLabel = SidebarUser::clientRoleLabel($sessionRole);
?>

<style>
/* Scroll horizontal SANS barre visible — défilement smooth */
.navbar { position:sticky; top:0; z-index:1000; overflow:visible; }

/* WRAPPER : contient les boutons + navbar-nav */
.navbar-nav-wrap {
    display:flex; align-items:center; flex:1; min-width:0;
    position:relative; overflow:hidden;
}
.navbar-nav {
    display:flex; align-items:center; flex:1; min-width:0;
    overflow:hidden;
}
.navbar-nav-track {
    display:flex; align-items:center; gap:2px; flex-shrink:0;
    justify-content:center;
    transition:transform 0.3s ease;
    will-change:transform;
}
.navbar-nav .nav-link { flex-shrink:0; white-space:nowrap; }

/* Boutons de défilement */
.nav-scroll-btn {
    position:absolute; top:50%; transform:translateY(-50%);
    z-index:5; width:28px; height:28px; border-radius:50%;
    border:none; cursor:pointer;
    background:rgba(255,255,255,0.12);
    color:#fff; font-size:18px; line-height:1;
    display:flex; align-items:center; justify-content:center;
    opacity:0; transition:opacity 0.25s, background 0.15s;
    pointer-events:none;
    backdrop-filter:blur(8px);
}
.nav-scroll-btn.visible { opacity:0.7; pointer-events:auto; }
.navbar-nav-wrap:hover .nav-scroll-btn.visible,
.navbar-nav-wrap:focus-within .nav-scroll-btn.visible { opacity:1; }
.nav-scroll-btn:active { background:rgba(255,255,255,0.22); }
.nav-scroll-left  { left:0; }
.nav-scroll-right { right:0; }

/* --- NAV DROPDOWN --- */
.nav-dropdown { position:relative; display:inline-flex; flex-shrink:0; }
.nav-dropdown > .nav-link { white-space:nowrap; }
.nav-dropdown-menu {
    display:none;
    position:absolute;
    top:100%;
    left:50%;
    transform:translateX(-50%);
    min-width:170px;
    padding-top:6px;
    background:transparent;
    border:none;
    z-index:999;
}
.nav-dropdown-menu-inner {
    background:#1a2744;
    border:1px solid rgba(255,255,255,0.10);
    border-radius:12px;
    box-shadow:0 16px 40px rgba(0,0,0,0.45);
    backdrop-filter:blur(20px);
    padding:6px;
}
.nav-dropdown:hover .nav-dropdown-menu,
.nav-dropdown:focus-within .nav-dropdown-menu { display:block; }
.nav-dropdown-menu a {
    display:flex; align-items:center; gap:9px;
    padding:10px 14px; border-radius:8px;
    font-size:13px; font-weight:500; color:rgba(255,255,255,0.80);
    text-decoration:none; transition:background 0.15s, color 0.15s;
    white-space:nowrap;
}
.nav-dropdown-menu a:hover, .nav-dropdown-menu a.active {
    background:rgba(255,255,255,0.07); color:#fff;
}
.nav-dropdown-menu a i { font-size:15px; opacity:0.75; }
.nav-dropdown-arrow {
    font-size:10px; margin-left:3px; opacity:0.55;
    display:inline-block; transition:transform 0.2s;
}
.nav-dropdown:hover .nav-dropdown-arrow { transform:rotate(180deg); }
.nav-dropdown-sep {
    height:1px; background:rgba(255,255,255,0.07); margin:4px 0;
}

/* --- NOTIFICATIONS STYLES --- */
.notif-wrap { position:relative; display:inline-flex; }
.notif-badge {
    position:absolute; top:-3px; right:-6px;
    background:#e63946; color:#fff; font-size:10px; font-weight:700;
    min-width:17px; height:17px; border-radius:999px;
    display:flex; align-items:center; justify-content:center;
    padding:0 4px; z-index:5; border:2px solid #0f1a2e;
    pointer-events:none;
}
.notif-dropdown {
    display:none; position:absolute; top:calc(100% + 8px); right:0;
    width:380px; max-height:440px;
    background:#1a2744; border:1px solid rgba(255,255,255,0.08);
    border-radius:14px; box-shadow:0 20px 50px rgba(0,0,0,0.5);
    z-index:1000; overflow:hidden; flex-direction:column;
    backdrop-filter: blur(20px);
}
.notif-dropdown.open { display:flex; }
.notif-header {
    display:flex; justify-content:space-between; align-items:center;
    padding:14px 16px; border-bottom:1px solid rgba(255,255,255,0.06);
    font-size:14px; font-weight:600; color:#fff; flex-shrink:0;
}
.notif-mark-all {
    background:none; border:none; color:#00d2ff;
    font-size:12px; cursor:pointer; padding:0;
}
.notif-mark-all:hover { text-decoration:underline; }
.notif-list {
    overflow-y:auto; flex:1; display:flex; flex-direction:column;
}
.notif-empty {
    padding:28px 16px; text-align:center; color:rgba(255,255,255,0.35);
    font-size:13px;
}
.notif-item {
    display:flex; gap:12px; padding:12px 16px;
    border-bottom:1px solid rgba(255,255,255,0.04);
    cursor:default; transition:background 0.15s;
}
.notif-item:hover { background:rgba(255,255,255,0.03); }
.notif-item.unread { background:rgba(0,210,255,0.05); }
.notif-icon {
    flex-shrink:0; width:34px; height:34px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:16px; margin-top:2px;
}
.notif-item.unread .notif-icon { background:rgba(0,210,255,0.12); color:#00d2ff; }
.notif-item:not(.unread) .notif-icon { background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.35); }
.notif-content { flex:1; min-width:0; }
.notif-text { font-size:13px; color:rgba(255,255,255,0.85); line-height:1.4; }
.notif-item.unread .notif-text { color:#fff; font-weight:500; }
.notif-time { font-size:11px; color:rgba(255,255,255,0.35); margin-top:4px; }
.avatar-btn img, .dropdown-avatar img { display:block; width:100%; height:100%; object-fit:cover; border-radius:50%; }
.avatar-btn:has(img), .dropdown-avatar:has(img) { font-size:0; line-height:0; overflow:hidden; }
</style>
<script>
window.BASE_URL = '<?= BASE_URL ?>';
document.addEventListener('DOMContentLoaded',function(){
/* --- scroll --- */
var tr=document.getElementById('navbarNavTrack'),n=tr?tr.parentElement:null,l=document.getElementById('navScrollLeft'),r=document.getElementById('navScrollRight');
if(tr&&n&&l&&r){
var pos=0,max=0;
function upd(){
max=Math.max(0,tr.scrollWidth-n.clientWidth);
tr.style.justifyContent=max>0?'flex-start':'center';
l.classList.toggle('visible',pos>2);
r.classList.toggle('visible',pos<max-2);
}
function go(v){pos=Math.max(0,Math.min(max,pos+v));tr.style.transform='translateX('+(-pos)+'px)';upd();}
l.addEventListener('click',function(){go(-260);});
r.addEventListener('click',function(){go(260);});
window.addEventListener('resize',function(){max=Math.max(0,tr.scrollWidth-n.clientWidth);pos=Math.min(pos,max);upd();});
setTimeout(upd,100);
}
/* --- dropdown overflow fix: detach menu to body so parent overflow:hidden cannot clip it --- */
(function(){
document.querySelectorAll('.nav-dropdown').forEach(function(dd){
var link=dd.querySelector('.nav-link'),orig=dd.querySelector('.nav-dropdown-menu');
if(!link||!orig)return;
/* clone menu to body */
var menu=orig.cloneNode(true);
orig.style.display='none';
document.body.appendChild(menu);
var timer;
function show(){
clearTimeout(timer);
var r=dd.getBoundingClientRect();
menu.style.position='fixed'; menu.style.zIndex='9999';
menu.style.top=(r.bottom+6)+'px'; menu.style.left=r.left+r.width/2+'px';
menu.style.transform='translateX(-50%)';
menu.style.display='block';
}
function hide(){
menu.style.display='';menu.style.position='';menu.style.zIndex='';
menu.style.top='';menu.style.left='';menu.style.transform='';
}
dd.addEventListener('mouseenter',show);
dd.addEventListener('mouseleave',function(){timer=setTimeout(hide,200);});
menu.addEventListener('mouseenter',function(){clearTimeout(timer);});
menu.addEventListener('mouseleave',hide);
link.addEventListener('click',function(e){e.preventDefault();location.href=link.getAttribute('href');});
});
})();
});
</script>

<nav class="navbar">
    <a href="<?= $frontBase ?>/client.php" class="navbar-brand">
        <img src="<?= $frontBase ?>/logo.png" alt="logo" width="40" height="40" style="border-radius:10px;">
        <div>
            <div class="logo-text">Protex</div>
            <div class="logo-sub">Assurance Digitale</div>
        </div>
    </a>

    <div class="navbar-nav-wrap" id="navbarNavWrap">
        <button class="nav-scroll-btn nav-scroll-left" id="navScrollLeft" aria-label="Défiler à gauche">‹</button>
        <div class="navbar-nav" id="navbarNav">
        <div class="navbar-nav-track" id="navbarNavTrack">
        <!-- GROUPE 1 : Mon espace -->
        <a class="nav-link <?= frontActive('client.php','client.html') ?>" href="<?= $frontBase ?>/client.php">
            <i class="bi bi-grid-1x2"></i><span class="nav-label">Dashboard</span>
        </a>
        <a class="nav-link <?= frontActive('contrat') ?>" href="<?= $frontBase ?>/contrat.php">
            <i class="bi bi-file-earmark-text"></i><span class="nav-label">Contrats</span>
        </a>
        <!-- DEVIS DROPDOWN -->
        <div class="nav-dropdown">
            <a class="nav-link <?= frontActive('ajoutdevis','mesdevis','mes_devis') ?>" href="<?= $frontBase ?>/ajoutdevis.php">
                <i class="bi bi-file-earmark-plus"></i>
                <span class="nav-label">Devis <span class="nav-dropdown-arrow">▾</span></span>
            </a>
            <div class="nav-dropdown-menu">
                <div class="nav-dropdown-menu-inner">
                    <a href="<?= $frontBase ?>/ajoutdevis.php" class="<?= frontActive('ajoutdevis') ?>">
                        <i class="bi bi-file-earmark-plus"></i> Nouveau devis
                    </a>
                    <div class="nav-dropdown-sep"></div>
                    <a href="<?= $frontBase ?>/mesdevis.php" class="<?= frontActive('mesdevis') ?>">
                        <i class="bi bi-files"></i> Mes devis
                    </a>
                </div>
            </div>
        </div>
        <a class="nav-link <?= frontActive('sinistre','mes-sinistres') ?>" href="<?= $frontBase ?>/mes-sinistres.php">
            <i class="bi bi-shield-exclamation"></i><span class="nav-label">Sinistres</span>
        </a>
        <a class="nav-link <?= frontActive('paiement') ?>" href="<?= $frontBase ?>/paiement.php">
            <i class="bi bi-credit-card"></i><span class="nav-label">Paiements</span>
        </a>
        <a class="nav-link <?= frontActive('parrainage') ?>" href="<?= $frontBase ?>/parrainage.php">
            <i class="bi bi-gift"></i><span class="nav-label">Parrainage</span>
        </a>
        <a class="nav-link <?= frontActive('reclamation') ?>" href="<?= $frontBase ?>/reclamationList.php">
            <i class="bi bi-chat-dots"></i><span class="nav-label">Réclamations</span>
        </a>

        <!-- SÉPARATEUR VISUEL -->
        <div class="nav-divider"></div>

        <!-- GROUPE 2 : Découvrir -->
        <a class="nav-link <?= frontActive('offres') ?>" href="<?= $frontBase ?>/offres.php">
            <i class="bi bi-stars"></i><span class="nav-label">Offres</span>
        </a>
        <a class="nav-link <?= frontActive('postes') ?>" href="<?= $frontBase ?>/postes.php">
            <i class="bi bi-megaphone"></i><span class="nav-label">Postes</span>
        </a>
        <a class="nav-link <?= frontActive('agences') ?>" href="<?= $frontBase ?>/agences.php">
            <i class="bi bi-geo-alt"></i><span class="nav-label">Agences</span>
        </a>
        <a class="nav-link <?= frontActive('partenaires') ?>" href="<?= $frontBase ?>/partenaires.php">
            <i class="bi bi-building-check"></i><span class="nav-label">Partenaires</span>
        </a>
        <a class="nav-link <?= frontActive('reseau','chat','friend') ?>" href="<?= $frontBase ?>/reseau.php">
            <i class="bi bi-people"></i><span class="nav-label">Réseau</span>
        </a>

        <!-- GROUPE 3 : Loisirs (pill spéciale) -->
        <a class="nav-link nav-pill-fun <?= frontActive('jeux','snake','memory','roulette','dice') ?>" href="<?= $frontBase ?>/jeux.php">
            <i class="bi bi-controller"></i><span class="nav-label">Jeux 🎮</span>
        </a>
    </div>
    </div>
        <button class="nav-scroll-btn nav-scroll-right" id="navScrollRight" aria-label="Défiler à droite">›</button>
    </div>

    <div class="navbar-right">
        <div class="notif-wrap">
            <button class="nav-btn" id="notifBtn" title="Notifications">
                <i class="bi bi-bell"></i>
                <span class="notif-dot" id="notifDot" style="display:none;"></span>
                <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
            </button>
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-header">
                    <span>Notifications</span>
                    <button class="notif-mark-all" onclick="markAllNotifRead()">Tout marquer comme lu</button>
                </div>
                <div class="notif-list" id="notifList">
                    <div class="notif-empty">Chargement...</div>
                </div>
            </div>
        </div>
        <a href="#" class="nav-btn" title="Aide"><i class="bi bi-question-circle"></i></a>

        <div class="avatar-wrap">
            <div class="avatar-btn" id="avatarBtn" data-navbar-avatar onclick="toggleAvatarDropdown(event)">
                <?= $navAvatarHtml ?>
            </div>
            <div class="avatar-dropdown" id="avatarDropdown">
                <div class="dropdown-header">
                    <div class="dropdown-avatar" data-navbar-avatar><?= $navAvatarHtml ?></div>
                    <div class="dropdown-info">
                        <div class="dropdown-name"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="dropdown-email"><?= htmlspecialchars($sessionEmail, ENT_QUOTES, 'UTF-8') ?></div>
                        <span class="dropdown-role"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
                <a href="<?= $frontBase ?>/monprofile.php" class="dropdown-item">
                    <i class="bi bi-person-circle"></i> Mon profil
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?= $ctrlBase ?>/AuthController.php?action=logout" class="dropdown-item logout">
                    <i class="bi bi-box-arrow-right"></i> Se déconnecter
                </a>
            </div>
        </div>
    </div>
</nav>

<style>
.avatar-dropdown { z-index: 9999 !important; }
.avatar-dropdown.open { display: block !important; opacity: 1 !important; visibility: visible !important; transform: translateY(0) scale(1) !important; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Premium Global Enhancements */
.card, .stat-card, .contrat-card {
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1) !important;
}
.card:hover, .stat-card:hover, .contrat-card:hover {
    transform: translateY(-6px) scale(1.01) !important;
    box-shadow: 0 20px 40px rgba(26, 58, 122, 0.12) !important;
    border-color: rgba(255, 107, 26, 0.3) !important;
}

/* Premium animated borders for active elements */
.btn-primary, .btn-nav-primary {
    position: relative;
    overflow: hidden;
}
.btn-primary::after, .btn-nav-primary::after {
    content: '';
    position: absolute;
    top: -50%; left: -50%;
    width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 60%);
    opacity: 0;
    transform: scale(0.5);
    transition: opacity 0.4s, transform 0.4s;
    pointer-events: none;
}
.btn-primary:hover::after, .btn-nav-primary:hover::after {
    opacity: 1;
    transform: scale(1);
}
/* --- TOAST NOTIFICATIONS --- */
.toast-notif {
    position: fixed; bottom: 30px; right: 30px; background: #1e293b;
    border: 1px solid var(--ptx-accent, #00d2ff); border-radius: 12px; padding: 16px 24px;
    display: flex; align-items: center; gap: 12px; color: #fff; z-index: 10000;
    box-shadow: 0 20px 40px rgba(0,0,0,0.4); transform: translateY(100px); opacity: 0; transition: 0.4s;
    font-size: 14px;
}
.toast-notif.show { transform: translateY(0); opacity: 1; }
.toast-success i { color: #22c55e; }
.toast-warning i { color: #FF6B1A; }
.toast-danger i  { color: #e63946; }

</style>

<script>
function toggleAvatarDropdown(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const dd = document.getElementById('avatarDropdown');
    if (dd) {
        dd.classList.toggle('open');
        console.log('Dropdown toggled. Current class:', dd.className);
    }
}

/* --- TOAST NOTIFICATIONS --- */
function showToast(message, type = 'success') {
    const icons = {
        success: 'check-circle',
        warning: 'exclamation-triangle',
        danger: 'x-circle',
        info: 'info-circle'
    };

    const toast = document.createElement('div');
    toast.className = 'toast-notif toast-' + type;
    toast.innerHTML = '<i class="bi bi-' + (icons[type] || 'info-circle') + '"></i><span>' + message + '</span>';
    document.body.appendChild(toast);

    setTimeout(() => toast.classList.add('show'), 50);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

document.addEventListener('click', function(e) {
    const dd = document.getElementById('avatarDropdown');
    const btn = document.getElementById('avatarBtn');
    if (dd && btn && !dd.contains(e.target) && !btn.contains(e.target)) {
        dd.classList.remove('open');
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const dd = document.getElementById('avatarDropdown');
        if (dd) dd.classList.remove('open');
    }
});

// Auto-inject premium animations for layout
document.addEventListener('DOMContentLoaded', function() {
    // Add premium class to main layout for smooth fade-in
    const layout = document.querySelector('.layout');
    if (layout && !layout.classList.contains('premium-fade')) {
        layout.style.animation = 'fadeUp 0.5s ease-out forwards';
    }
});
</script>

<script src="<?= BASE_URL ?>/view/BackOffice/assets/js/sidebar-user.js"></script>
<!-- NOTIFICATIONS SYSTEM (Centralized) -->
<script src="<?= $frontBase ?>/js/notifications.js"></script>
<script>
    window.CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
    window._notifUserId = <?= json_encode($_SESSION['user_id'] ?? $_SESSION['id_user'] ?? 0) ?>;
    // Patch fetch pour CSRF
    const origFetch = window.fetch;
    window.fetch = function(url, opts) {
      opts = opts || {};
      if ((!opts.method || opts.method === 'POST') && window.CSRF_TOKEN) {
        opts.headers = opts.headers || {};
        if (typeof opts.headers.append === 'function') {
          opts.headers.append('X-CSRF-Token', window.CSRF_TOKEN);
        } else if (typeof opts.headers === 'object' && !Array.isArray(opts.headers)) {
          opts.headers['X-CSRF-Token'] = window.CSRF_TOKEN;
        }
      }
      return origFetch.call(window, url, opts);
    };
    if (typeof initNotifications === 'function') {
        initNotifications(window._notifUserId);
    }
</script>
