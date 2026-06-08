<?php
if (session_status() === PHP_SESSION_NONE) session_start();
/**
 * view/BackOffice/assets/includes/sidebar.php
 * Sidebar BackOffice — structure camarades + mes 2 tâches (Diagnostique + Messagerie)
 */

if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 4) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
if (class_exists('RoleHelper')) {
    // already loaded
} elseif (file_exists(dirname(__DIR__, 4) . '/helpers/RoleHelper.php')) {
    require_once dirname(__DIR__, 4) . '/helpers/RoleHelper.php';
}
require_once dirname(__DIR__, 4) . '/helpers/SidebarUser.php';

$uri = strtolower($_SERVER['REQUEST_URI'] ?? '');
function sidebarActive(string ...$keywords): string {
    global $uri;
    foreach ($keywords as $kw) {
        if (str_contains($uri, strtolower($kw))) return 'active';
    }
    return '';
}

$_back  = BASE_URL . '/view/BackOffice';
$_ctrl  = BASE_URL . '/controller';
$_front = BASE_URL . '/view/FrontOffice';

// Lecture session
$sidebarUser    = null;
$unreadCount    = 0;
$activeMentions = 0;
$sidebarUid     = (int)($_SESSION['user_id'] ?? $_SESSION['id_user'] ?? 0);

if ($sidebarUid > 0) {
    try {
        if (class_exists('config')) {
            $uDb   = config::getConnexion();
            $uStmt = $uDb->prepare(
                "SELECT u.nom, u.prenom, u.role, u.avatar, u.avatar_url, anc.nom_agence
                 FROM user u
                 LEFT JOIN admin a ON u.id_user = a.id_user
                 LEFT JOIN agent ag ON u.id_user = ag.id_user
                 LEFT JOIN agence anc ON (a.id_agence = anc.id_agence OR ag.id_agence = anc.id_agence)
                 WHERE u.id_user = ?"
            );
            $uStmt->execute([$sidebarUid]);
            $sidebarUser = $uStmt->fetch(PDO::FETCH_ASSOC) ?: null;

            try {
                $msgStmt = $uDb->prepare("
                    SELECT COALESCE(SUM(
                        (SELECT COUNT(*) FROM messages_admin m
                         WHERE m.id_conversation = cp.id_conversation
                           AND m.id_expediteur != ?
                           AND (m.date_envoi > cp.dernier_message_lu OR cp.dernier_message_lu IS NULL))
                    ), 0) AS unread
                    FROM conversation_participants cp WHERE cp.id_user = ?
                ");
                $msgStmt->execute([$sidebarUid, $sidebarUid]);
                $unreadCount = (int)($msgStmt->fetch(PDO::FETCH_ASSOC)['unread'] ?? 0);

                $amStmt = $uDb->prepare("SELECT COUNT(*) FROM message_mentions WHERE id_user_mentionne = ? AND est_resolu = 0");
                $amStmt->execute([$sidebarUid]);
                $activeMentions = (int)($amStmt->fetchColumn() ?? 0);

                // Prio 3 : Badges sidebar
                $qS = $uDb->query("SELECT COUNT(*) FROM sinistre WHERE statut = 'en_attente'");
                $pendingSinistres = (int)$qS->fetchColumn();

                $qD = $uDb->query("SELECT COUNT(*) FROM devis WHERE statut = 'en_attente'");
                $pendingDevis = (int)$qD->fetchColumn();

                $qR = $uDb->query("SELECT COUNT(*) FROM reclamation r
                    LEFT JOIN reponse rep ON rep.reclamation_id = r.id
                    WHERE rep.reclamation_id IS NULL");
                $pendingReclamations = (int)$qR->fetchColumn();

                $qMod = $uDb->query("SELECT (SELECT COUNT(*) FROM poste WHERE signalements > 0 AND hidden = 0) + (SELECT COUNT(*) FROM commentaire WHERE signalements > 0 AND hidden = 0)");
                $pendingModeration = (int)$qMod->fetchColumn();

            } catch (Exception $ign2) {}
        }
    } catch (Exception $ign) {}
}

$sidebarNom = $sidebarUser
    ? trim(($sidebarUser['prenom'] ?? '') . ' ' . ($sidebarUser['nom'] ?? ''))
    : trim(($_SESSION['user_prenom'] ?? $_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? $_SESSION['nom'] ?? ''));
if ($sidebarNom === '') $sidebarNom = 'Admin';

$sidebarRoleRaw = $sidebarUser['role'] ?? RoleHelper::getRole();
$sidebarNomAgence = $sidebarUser['nom_agence'] ?? null;
$sidebarRole = SidebarUser::roleLabel($sidebarRoleRaw, $sidebarNomAgence);
$sideIsSuperAdmin = ($sidebarRoleRaw === 'superadmin');
$sideIsAdmin = in_array($sidebarRoleRaw, ['superadmin', 'admin'], true);
$sideIsAgent = in_array($sidebarRoleRaw, ['superadmin', 'admin', 'agent'], true);

$sidebarPrenom = $sidebarUser['prenom'] ?? $_SESSION['prenom'] ?? $_SESSION['user_prenom'] ?? '';
$sidebarNomOnly = $sidebarUser['nom'] ?? $_SESSION['nom'] ?? $_SESSION['user_nom'] ?? '';
$sidebarInit = SidebarUser::initials($sidebarPrenom, $sidebarNomOnly);
$sidebarAvatarUrl = SidebarUser::resolveAvatarUrl(
    $sidebarUser['avatar'] ?? $_SESSION['user_avatar'] ?? null,
    $sidebarUser['avatar_url'] ?? null
);
?>
<script>
    window.BASE_URL = '<?= BASE_URL ?>';
</script>
<style>
/* Sidebar scrollable SANS barre visible — défilement smooth */
.sidebar { display:flex; flex-direction:column; max-height:100vh; }
.sidebar-nav {
    flex:1;
    overflow-y:auto;
    overflow-x:hidden;
    scroll-behavior:smooth;
    scrollbar-width:none;       /* Firefox */
    -ms-overflow-style:none;    /* IE/Edge */
}
.sidebar-nav::-webkit-scrollbar { display:none; width:0; height:0; }  /* Chrome/Safari */
.sidebar-footer { flex-shrink:0; }
.user-avatar img { display:block; }
.user-avatar:has(img) { font-size:0; line-height:0; }
</style>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <img src="<?= $_front ?>/logo.png" alt="logo" width="40" height="40" style="border-radius:10px;object-fit:cover;">
        <div>
            <div class="logo-text">Protex</div>
            <div class="logo-sub">Back-Office</div>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar" id="sidebarUserAvatar" data-sidebar-avatar>
            <?= SidebarUser::renderAvatarInner($sidebarInit, $sidebarAvatarUrl) ?>
        </div>
        <div>
            <div class="user-name"><?= htmlspecialchars($sidebarNom, ENT_QUOTES, 'UTF-8') ?></div>
            <span class="user-role"><?= htmlspecialchars($sidebarRole, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Principal</div>
        <a class="nav-item <?= sidebarActive('admin.php','dashboard') ?>"
           href="<?= $_back ?>/admin.php">
            <i class="bi bi-grid-1x2"></i> Tableau de bord
        </a>

        <?php if ($sideIsAgent): ?>
        <a class="nav-item <?= sidebarActive('sinistre','sinsiter') ?>" href="<?= $_back ?>/sinsiter.php">
            <i class="bi bi-shield-exclamation"></i> Sinistres
            <?php if (($pendingSinistres ?? 0) > 0): ?>
                <span class="nav-badge"><?= $pendingSinistres ?></span>
            <?php endif; ?>
        </a>

        <a class="nav-item <?= sidebarActive('traitement') ?>" href="<?= $_back ?>/traitement.php">
            <i class="bi bi-file-earmark-text"></i> Traitements
        </a>

        <a class="nav-item <?= sidebarActive('reclamation','reponse') ?>" href="<?= $_back ?>/reponse.php">
            <i class="bi bi-chat-dots"></i> Réclamations
            <?php if (($pendingReclamations ?? 0) > 0): ?>
                <span class="nav-badge"><?= $pendingReclamations ?></span>
            <?php endif; ?>
        </a>

        <a class="nav-item <?= sidebarActive('messagerie') ?>" href="<?= $_ctrl ?>/MessagerieController.php">
            <i class="bi bi-chat-left-text"></i> Messagerie
            <?php if ($unreadCount + $activeMentions > 0): ?>
                <span class="nav-badge accent"><?= $unreadCount + $activeMentions ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>

        <?php if ($sideIsAgent): ?>
        <div class="nav-section">Gestion</div>
        <a class="nav-item <?= sidebarActive('contrats_back','contrat') ?>" href="<?= $_back ?>/contrats_back.php">
            <i class="bi bi-file-earmark-check"></i> Contrats
        </a>
        <a class="nav-item <?= sidebarActive('devis') ?>" href="<?= $_ctrl ?>/DevisController.php">
            <i class="bi bi-file-earmark-medical"></i> Devis
            <?php if (($pendingDevis ?? 0) > 0): ?>
                <span class="nav-badge accent"><?= $pendingDevis ?></span>
            <?php endif; ?>
        </a>
        <a class="nav-item <?= sidebarActive('offres') ?>" href="<?= $_ctrl ?>/OffreController.php">
            <i class="bi bi-tags"></i> Offres
        </a>
        <a class="nav-item <?= sidebarActive('paiement') ?>" href="<?= $_ctrl ?>/PaiementController.php">
            <i class="bi bi-credit-card"></i> Paiements
        </a>
        <a class="nav-item <?= sidebarActive('dashboard','DashboardController') ?>"
           href="<?= $_ctrl ?>/DashboardController.php">
            <i class="bi bi-bar-chart-line"></i> Diagnostique
        </a>
        <a class="nav-item <?= sidebarActive('admin-partenaires','partenaire') ?>" href="<?= $_back ?>/admin-partenaires.php">
            <i class="bi bi-building-check"></i> Partenaires
        </a>
        <a class="nav-item <?= sidebarActive('parrainage_stats','parrainage') ?>" href="<?= $_back ?>/parrainage_stats.php">
            <i class="bi bi-gift"></i> Parrainage
        </a>
        <?php endif; ?>

        <?php if ($sideIsAgent): ?>
        <div class="nav-section">Administration</div>
        <a class="nav-item <?= sidebarActive('admin-users') ?>" href="<?= $_back ?>/admin-users.php">
            <i class="bi bi-people"></i> Utilisateurs
        </a>
        <a class="nav-item <?= sidebarActive('categories_back') ?>" href="<?= $_back ?>/categories_back.php">
            <i class="bi bi-grid-3x3-gap"></i> Catégories
        </a>
        <a class="nav-item <?= sidebarActive('garanties_back') ?>" href="<?= $_back ?>/garanties_back.php">
            <i class="bi bi-shield-check"></i> Garanties
        </a>
        <a class="nav-item <?= sidebarActive('garanties_matrix') ?>" href="<?= $_back ?>/garanties_matrix.php">
            <i class="bi bi-table"></i> Matrice Garanties
        </a>
        <a class="nav-item <?= sidebarActive('formules_back') ?>" href="<?= $_back ?>/formules_back.php">
            <i class="bi bi-layers"></i> Formules
        </a>
        <?php if ($sideIsAdmin): ?>
        <a class="nav-item <?= sidebarActive('moderation') ?>" href="<?= $_back ?>/moderation.php">
            <i class="bi bi-shield-lock"></i> Modération
            <?php if (($pendingModeration ?? 0) > 0): ?>
                <span class="nav-badge accent" style="background:#e11d48; color:white; border:none;"><?= $pendingModeration ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
        <a class="nav-item <?= sidebarActive('agence_virtuelle') ?>" href="<?= $_back ?>/agence_virtuelle.php">
            <i class="bi bi-building"></i> Agence Virtuelle
            <span class="nav-badge accent" style="background:linear-gradient(135deg,#FF6B1A,#f59e0b);color:#fff;border:none;font-size:9px">3D</span>
        </a>
        <?php if ($sideIsSuperAdmin): ?>
        <div class="nav-section" style="color:#f4a261;border-top:1px solid rgba(244,162,97,0.2);margin-top:8px;padding-top:8px;">Super Admin</div>
        <a class="nav-item <?= sidebarActive('admin-agences','agence') ?>" href="<?= $_back ?>/admin-agences.php">
            <i class="bi bi-geo-alt"></i> Agences
        </a>
        <a class="nav-item <?= sidebarActive('leaderboard_agences') ?>" href="<?= $_back ?>/leaderboard_agences.php">
            <i class="bi bi-bar-chart-line"></i> Classement agences
        </a>
        <a class="nav-item <?= sidebarActive('agenda') ?>" href="<?= $_back ?>/agenda.php">
            <i class="bi bi-calendar-event"></i> Agenda RDV
        </a>
        <a class="nav-item <?= sidebarActive('admin-postes','poste') ?>" href="<?= $_back ?>/admin-postes.php">
            <i class="bi bi-megaphone"></i> Postes
        </a>
        <a class="nav-item <?= sidebarActive('audit_log') ?>" href="<?= $_back ?>/audit_log.php">
            <i class="bi bi-journal-text"></i> Journal d'Audit
        </a>
        <?php endif; ?>

        <div class="nav-section">Compte</div>
        <a class="nav-item <?= sidebarActive('adminprofile') ?>" href="<?= $_back ?>/adminprofile.php">
            <i class="bi bi-person-gear"></i> Mon profil
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= $_ctrl ?>/AuthController.php?action=logout" class="logout-btn">
            <i class="bi bi-box-arrow-left"></i> Se déconnecter
        </a>
    </div>
</aside>
<script src="<?= $_back ?>/assets/js/sidebar-user.js"></script>
<script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
<script>
// CSRF token global pour les appels AJAX
window.CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
// Patch fetch pour inclure le token CSRF sur les requêtes POST
const origFetch = window.fetch;
window.fetch = function(url, opts) {
  opts = opts || {};
  if ((!opts.method || opts.method === 'POST') && CSRF_TOKEN) {
    opts.headers = opts.headers || {};
    if (typeof opts.headers.append === 'function') {
      opts.headers.append('X-CSRF-Token', CSRF_TOKEN);
    } else if (typeof opts.headers === 'object' && !Array.isArray(opts.headers)) {
      opts.headers['X-CSRF-Token'] = CSRF_TOKEN;
    }
  }
  return origFetch.call(window, url, opts);
};
</script>

<!-- VOICE CALL WIDGET -->
<style>
#voice-widget{position:fixed;bottom:80px;right:20px;z-index:9999;font-family:sans-serif}
#voice-widget-btn{width:56px;height:56px;border-radius:50%;border:none;background:var(--orange,#FF6B1A);color:#fff;font-size:24px;cursor:pointer;box-shadow:0 4px 20px rgba(255,107,26,0.4);transition:all .3s}
#voice-widget-btn:hover{transform:scale(1.1)}
#voice-widget-btn.active{background:#e63946;animation:pulse-ring 1.5s infinite}
#voice-panel{display:none;position:absolute;bottom:66px;right:0;width:300px;background:rgba(7,17,31,0.95);border:1px solid rgba(255,255,255,0.1);border-radius:16px;backdrop-filter:blur(16px);overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.5)}
#voice-panel.open{display:block}
.vp-header{padding:14px 16px;border-bottom:1px solid rgba(255,255,255,0.08);font-size:13px;font-weight:600;color:rgba(255,255,255,0.6);display:flex;align-items:center;gap:8px}
.vp-header span{flex:1}
.vp-body{padding:10px 14px;max-height:200px;overflow-y:auto}
.vp-peer{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;margin-bottom:4px;transition:all .2s;cursor:pointer}
.vp-peer:hover{background:rgba(255,255,255,0.05)}
.vp-peer .vp-name{flex:1;font-size:13px;color:#fff;font-weight:500}
.vp-peer .vp-room{font-size:11px;color:rgba(255,255,255,0.4)}
.vp-peer .vp-call{padding:6px 14px;border-radius:8px;border:none;background:var(--orange,#FF6B1A);color:#fff;font-size:12px;cursor:pointer;transition:all .2s;font-weight:500}
.vp-peer .vp-call:hover{opacity:.85}
.vp-peer .vp-call.active{background:#e63946}
.vp-empty{padding:16px;text-align:center;font-size:12px;color:rgba(255,255,255,0.3)}
.vp-mic-row{padding:10px 14px;border-top:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:10px}
.vp-mic-row button{width:36px;height:36px;border-radius:50%;border:none;cursor:pointer;font-size:16px;transition:all .3s}
.vp-mic-row .vp-mic-on{background:var(--orange,#FF6B1A);color:#fff}
.vp-mic-row .vp-mic-off{background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.4)}
.vp-mic-row .vp-label{font-size:11px;color:rgba(255,255,255,0.4);flex:1}
#voice-toast{position:fixed;bottom:150px;right:20px;z-index:10000;padding:12px 20px;border-radius:12px;font-size:13px;display:none;animation:fadeInUp .3s}
#voice-toast.show{display:block}
#voice-toast.incoming{background:rgba(255,107,26,0.2);border:1px solid rgba(255,107,26,0.3);color:#fff}
#voice-toast .vt-actions{margin-top:8px;display:flex;gap:8px}
#voice-toast .vt-actions button{padding:6px 16px;border-radius:8px;border:none;cursor:pointer;font-size:12px;font-weight:500}
#voice-toast .vt-accept{background:#2ec46f;color:#fff}
#voice-toast .vt-decline{background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.6)}
@keyframes fadeInUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
@keyframes pulse-ring{0%{box-shadow:0 0 0 0 rgba(255,107,26,0.7)}50%{box-shadow:0 0 0 12px rgba(255,107,26,0)}to{box-shadow:0 0 0 0 rgba(255,107,26,0)}}
</style>

<div id="voice-widget">
  <div id="voice-panel">
    <div class="vp-header">
      📞 <span>Appel vocal</span>
      <span id="vp-room-name" style="font-weight:400;font-size:12px"></span>
    </div>
    <div class="vp-body" id="vp-body">
      <div class="vp-empty" id="vp-empty">Aucun agent disponible</div>
    </div>
    <div class="vp-mic-row" id="vp-mic-row" style="display:none">
      <button id="vp-mic-btn" class="vp-mic-off" onclick="vpToggleMic()">🎤</button>
      <span class="vp-label" id="vp-mic-label">Micro désactivé</span>
      <span id="vp-call-status" style="font-size:11px;color:rgba(255,255,255,0.3)"></span>
    </div>
  </div>
  <button id="voice-widget-btn" onclick="vpToggle()" title="Appel vocal">📞</button>
</div>

<div id="voice-toast"></div>

<script>
// ═══════════════════════════════════════════════════════════
// VOICE CALL — Version globale (toutes les pages backoffice)
// ═══════════════════════════════════════════════════════════
let vpPeer = null;
let vpCalls = {};
let vpMicStream = null;
let vpMicActive = false;
let vpInCall = false;
let vpOpen = false;
let vpPollTimer = null;
let vpPendingCall = null;

function vpInit() {
  if (typeof Peer === 'undefined') return;
  try {
    vpPeer = new Peer('vp_' + <?= json_encode($_SESSION['user_id'] ?? 0) ?> + '_' + Math.random().toString(36).slice(2,6));
    vpPeer.on('call', call => {
      call.answer(vpMicActive && vpMicStream ? vpMicStream : null);
      call.on('stream', stream => vpPlayStream(stream, call.peer));
      call.on('close', () => vpCleanup(call.peer));
      vpCalls[call.peer] = call;
      vpInCall = true;
      vpUpdateUI();
    });
    vpPeer.on('error', () => {});
    vpPoll();
    vpPollTimer = setInterval(vpPoll, 5000);
  } catch(e) { console.error('Voice init error:', e); }
}

function vpToggle() {
  vpOpen = !vpOpen;
  document.getElementById('voice-panel').classList.toggle('open', vpOpen);
  if (vpOpen) {
    vpUpdateUI();
    vpRegister();
  } else {
    vpUnregister();
  }
}

function vpRegister() {
  if (!vpPeer) return;
  const fd = new FormData();
  fd.append('salle', '__widget__');
  fd.append('peer_id', vpPeer.id);
  fetch(BASE_URL + '/api.php?action=voice_join', {method:'POST', body:fd});
}

function vpUnregister() {
  const fd = new FormData();
  fd.append('salle', '__widget__');
  fetch(BASE_URL + '/api.php?action=voice_leave', {method:'POST', body:fd});
}

function vpUpdateUI() {
  const panel = document.getElementById('vp-body');
  const empty = document.getElementById('vp-empty');
  const peers = document.querySelectorAll('.vp-peer');
  const micRow = document.getElementById('vp-mic-row');
  const callStatus = document.getElementById('vp-call-status');
  
  micRow.style.display = vpInCall ? 'flex' : 'none';
  callStatus.textContent = vpInCall ? '🔊 En appel' : '';
}

function vpPoll() {
  fetch(BASE_URL + '/api.php?action=voice_list&salle=__all__')
    .then(r => r.json()).then(data => {
      if (!data.success) return;
      const container = document.getElementById('vp-body');
      const empty = document.getElementById('vp-empty');
      const peers = data.peers || [];
      if (!peers.length) {
        container.innerHTML = '<div class="vp-empty">Aucun agent disponible</div>';
        return;
      }
      container.innerHTML = peers.map(p => {
        const roomLabel = p.salle === '__widget__' ? '📱 Disponible' : '🏢 ' + p.salle;
        return `
          <div class="vp-peer" data-peer-id="${p.peer_id}">
            <div style="width:32px;height:32px;border-radius:50%;background:rgba(255,107,26,0.15);display:flex;align-items:center;justify-content:center;font-size:14px">👤</div>
            <div class="vp-name">${p.prenom} ${p.nom}</div>
            <div class="vp-room">${roomLabel}</div>
            <button class="vp-call ${vpCalls[p.peer_id] ? 'active' : ''}" onclick="vpCallPeer('${p.peer_id}')">
              ${vpCalls[p.peer_id] ? '🔊' : '📞'}
            </button>
          </div>
        `;
      }).join('');
    });
}

function vpCallPeer(peerId) {
  if (!vpMicActive || !vpMicStream) {
    vpToast('Active d\'abord le micro 🎤', '');
    return;
  }
  if (vpCalls[peerId]) {
    // Déjà en appel → raccrocher
    try { vpCalls[peerId].close(); } catch(e) {}
    vpCleanup(peerId);
    return;
  }
  const call = vpPeer.call(peerId, vpMicStream);
  call.on('stream', stream => vpPlayStream(stream, call.peer));
  call.on('close', () => vpCleanup(call.peer));
  vpCalls[call.peer] = call;
  vpInCall = true;
  vpUpdateUI();
  vpToast('📞 Appel en cours...', '');
}

function vpPlayStream(stream, peerId) {
  let audio = document.getElementById('vp-audio-' + peerId);
  if (!audio) {
    audio = document.createElement('audio');
    audio.id = 'vp-audio-' + peerId;
    audio.autoplay = true;
    audio.playsInline = true;
    document.body.appendChild(audio);
  }
  audio.srcObject = stream;
  audio.play().catch(() => {});
  vpInCall = true;
  vpUpdateUI();
}

function vpCleanup(peerId) {
  const el = document.getElementById('vp-audio-' + peerId);
  if (el) el.remove();
  delete vpCalls[peerId];
  if (!Object.keys(vpCalls).length) {
    vpInCall = false;
    document.getElementById('vp-call-status').textContent = '';
  }
  vpUpdateUI();
}

async function vpToggleMic() {
  const btn = document.getElementById('vp-mic-btn');
  const label = document.getElementById('vp-mic-label');
  if (!vpMicActive) {
    try {
      vpMicStream = await navigator.mediaDevices.getUserMedia({audio:true});
      vpMicActive = true;
      btn.className = 'vp-mic-on';
      label.textContent = 'Micro actif';
      vpToast('🎤 Micro activé', '');
    } catch(e) {
      vpToast('⚠️ Accès micro refusé', '');
    }
  } else {
    if (vpMicStream) vpMicStream.getTracks().forEach(t => t.stop());
    vpMicActive = false;
    vpMicStream = null;
    btn.className = 'vp-mic-off';
    label.textContent = 'Micro désactivé';
    Object.keys(vpCalls).forEach(k => { try { vpCalls[k].close(); } catch(e) {} });
    vpCalls = {};
    vpInCall = false;
    vpUpdateUI();
  }
}

function vpToast(msg, sub) {
  const el = document.getElementById('voice-toast');
  el.innerHTML = msg + (sub ? '<br><small>' + sub + '</small>' : '') + '<div class="vt-actions"><button class="vt-accept" onclick="this.parentElement.parentElement.classList.remove(\'show\')">OK</button></div>';
  el.className = 'show incoming';
  setTimeout(() => el.classList.remove('show'), 5000);
}

// Notification quand l'admin entre dans la salle de l'agent
let vpLastAdminNotif = '';
setInterval(() => {
  fetch(BASE_URL + '/api.php?action=voice_admin_presence').then(r=>r.json()).then(d => {
    if (d.success && d.admin) {
      const key = d.admin.salle + d.admin.nom;
      if (key !== vpLastAdminNotif) {
        vpLastAdminNotif = key;
        vpToast('👑 ' + d.admin.prenom + ' ' + d.admin.nom + ' est dans ' + d.admin.salle, 'Cliquez pour répondre');
      }
    } else {
      vpLastAdminNotif = '';
    }
  });
}, 5000);

window.addEventListener('beforeunload', () => vpUnregister());
vpInit();
</script>
