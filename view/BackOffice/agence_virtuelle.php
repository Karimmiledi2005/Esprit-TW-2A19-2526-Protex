<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
require_once dirname(__DIR__, 2) . '/helpers/CsrfHelper.php';
SessionGuard::requireBackoffice();
$role = SessionGuard::role();
if (!in_array($role, ['admin','superadmin','agent'], true)) {
    http_response_code(403);
    echo '<h1 style="font-family:sans-serif;text-align:center;margin-top:100px">403 — Accès réservé aux administrateurs</h1>';
    exit;
}
$adminNom    = htmlspecialchars($_SESSION['nom'] ?? $_SESSION['user_nom'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$adminPrenom = htmlspecialchars($_SESSION['prenom'] ?? $_SESSION['user_prenom'] ?? '', ENT_QUOTES, 'UTF-8');
$adminAgence = htmlspecialchars($_SESSION['nom_agence'] ?? 'Agence Protex', ENT_QUOTES, 'UTF-8');
$adminId     = (int)($_SESSION['user_id'] ?? $_SESSION['id_user'] ?? 1);

// Charger les vrais agents de l'agence
$dbAgents = config::getConnexion();
$adminAgenceId = (int)($_SESSION['id_agence'] ?? $_SESSION['agence_id'] ?? 0);
if (!$adminAgenceId) {
    $stmtAg = $dbAgents->prepare("SELECT id_agence FROM admin WHERE id_user = ?");
    $stmtAg->execute([$adminId]);
    $adminAgenceId = (int)$stmtAg->fetchColumn();
    if (!$adminAgenceId) {
        $stmtAg2 = $dbAgents->prepare("SELECT id_agence FROM agent WHERE id_user = ?");
        $stmtAg2->execute([$adminId]);
        $adminAgenceId = (int)$stmtAg2->fetchColumn();
    }
}

$stmtAgents = $dbAgents->prepare("
    SELECT u.id_user, u.nom, u.prenom, u.role,
           COALESCE(u.last_seen, '2000-01-01') AS last_seen,
           CASE WHEN u.last_seen >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
                THEN 1 ELSE 0 END AS online
    FROM (
        SELECT a.id_user FROM agent a JOIN user ua ON a.id_user = ua.id_user
         WHERE a.id_agence = :ag1 AND ua.statut = 'actif'
        UNION
        SELECT ad.id_user FROM admin ad JOIN user uad ON ad.id_user = uad.id_user
         WHERE ad.id_agence = :ag2 AND ad.id_user != :self AND uad.statut = 'actif'
    ) AS membres
    JOIN user u ON u.id_user = membres.id_user
    WHERE u.statut = 'actif'
    ORDER BY online DESC, u.nom ASC
");
$stmtAgents->bindValue(':ag1',  $adminAgenceId, PDO::PARAM_INT);
$stmtAgents->bindValue(':ag2',  $adminAgenceId, PDO::PARAM_INT);
$stmtAgents->bindValue(':self', $adminId,       PDO::PARAM_INT);
$stmtAgents->execute();
$realAgents = $stmtAgents->fetchAll(PDO::FETCH_ASSOC);

$agentColors = ['#00b4d8','#2ec46f','#a855f7','#f59e0b','#e63946','#FF6B1A','#06b6d4'];

// Distribution intelligente des agents dans les salles selon leur spécialité
$salleMap = [
    'auto'       => ['Salle Auto', '🚗', 'spécialiste en assurance auto et sinistres véhicules'],
    'sante'      => ['Salle Santé', '🏥', 'spécialiste en assurance santé et remboursements médicaux'],
    'habitation' => ['Salle Habitation', '🏠', 'spécialiste en assurance habitation et multirisques'],
    'sinistres'  => ['Salle Sinistres', '⚠️', 'expert en traitement et analyse des sinistres'],
    'entree'     => ['Entrée', '🚪', 'chargé d\'accueil et d\'orientation des dossiers'],
];
// Vérifier les assignations persistantes (agent_room)
$persistentRooms = [];
$stmtRoom = $dbAgents->query("SELECT id_user, salle FROM agent_room");
foreach ($stmtRoom as $r) {
    $key = array_search($r['salle'], array_column($salleMap, 0));
    if ($key !== false) $persistentRooms[$r['id_user']] = $key;
}

$agentsJs = array_map(function($a, $i) use ($agentColors, $salleMap, $persistentRooms) {
    $roomKeys = ['auto', 'sante', 'habitation', 'sinistres', 'entree'];
    if (isset($persistentRooms[$a['id_user']])) {
        $salleKey = $persistentRooms[$a['id_user']];
    } else {
        $idx = ($a['id_user'] * 7 + 13) % count($roomKeys);
        $salleKey = $roomKeys[$idx];
    }
    $salle = $salleMap[$salleKey][0];
    return [
        'id'     => (int)$a['id_user'],
        'nom'    => $a['nom'] . ' ' . $a['prenom'],
        'prenom' => $a['prenom'],
        'role'   => match($a['role']) { 'agent' => 'Agent', 'admin' => 'Admin', default => ucfirst($a['role']) },
        'salle'  => $salle,
        'color'  => $agentColors[$i % count($agentColors)],
        'online' => (bool)$a['online'],
        'initials' => strtoupper(mb_substr($a['nom'],0,1).mb_substr($a['prenom'],0,1)),
    ];
}, $realAgents, array_keys($realAgents));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="csrf" content="<?= CsrfHelper::getToken() ?>">
<title>Agence Virtuelle — <?= $adminAgence ?></title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

:root {
  --navy:#07111f; --navy2:#0d1c35; --navy3:#122040;
  --orange:#FF6B1A; --orange2:#ff8c00;
  --blue:#00b4d8; --blue2:#0077a8;
  --green:#2ec46f; --red:#e63946;
  --text:#e8edf5; --muted:rgba(232,237,245,0.45);
  --border:rgba(255,255,255,0.08);
  --card:rgba(255,255,255,0.04);
  --glass:rgba(255,255,255,0.06);
  --shadow:0 8px 32px rgba(0,0,0,0.5);
}

*{box-sizing:border-box;margin:0;padding:0}
html,body{width:100%;height:100%;overflow:hidden;font-family:'Sora',sans-serif;background:var(--navy);color:var(--text)}

/* ── TOPBAR ── */
#topbar{
  position:fixed;top:0;left:0;right:0;height:52px;z-index:100;
  background:rgba(7,17,31,0.92);
  border-bottom:1px solid var(--border);
  backdrop-filter:blur(20px);
  display:flex;align-items:center;padding:0 18px;gap:12px;
}
.tb-logo{font-weight:700;font-size:16px;color:var(--orange);letter-spacing:-0.3px;flex-shrink:0}
.tb-sep{width:1px;height:20px;background:var(--border)}
.tb-agence{font-size:13px;color:var(--muted);flex:1}
.tb-badge{
  font-size:11px;font-family:'JetBrains Mono',monospace;
  background:rgba(0,180,216,0.12);border:1px solid rgba(0,180,216,0.25);
  color:var(--blue);border-radius:20px;padding:3px 10px;
}
.tb-back{
  font-size:12px;color:var(--muted);text-decoration:none;
  border:1px solid var(--border);border-radius:7px;padding:5px 12px;
  transition:all .2s;
}
.tb-back:hover{color:var(--text);border-color:rgba(255,255,255,0.2)}

/* ── CANVAS ── */
#cvs{position:fixed;top:52px;left:0;width:100%;height:calc(100% - 52px);display:block;cursor:crosshair}

/* ── HUD SALLE ── */
#hud-room{
  position:fixed;top:64px;left:50%;transform:translateX(-50%);
  background:rgba(7,17,31,0.88);border:1px solid var(--border);
  backdrop-filter:blur(16px);border-radius:12px;
  padding:7px 20px;font-size:13px;font-weight:500;
  color:var(--text);z-index:50;text-align:center;
  transition:opacity .3s;pointer-events:none;
}
#hud-room span{color:var(--orange)}

/* ── CONTROLS HINT ── */
#controls-hint{
  position:fixed;bottom:20px;left:20px;z-index:80;
  background:rgba(7,17,31,0.80);border:1px solid var(--border);
  border-radius:12px;padding:12px 16px;
  font-size:12px;color:var(--muted);line-height:1.8;
  backdrop-filter:blur(10px);
}
#controls-hint kbd{
  display:inline-block;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);
  border-radius:4px;padding:1px 6px;font-family:'JetBrains Mono',monospace;
  font-size:11px;color:var(--text);margin:0 2px;
}

/* ── SIDEBAR CHAT / AGENTS ── */
#sidebar{
  position:fixed;right:0;top:52px;bottom:0;width:300px;
  background:rgba(7,17,31,0.95);border-left:1px solid var(--border);
  backdrop-filter:blur(20px);
  display:flex;flex-direction:column;z-index:70;
  transform:translateX(100%);transition:transform .35s cubic-bezier(.4,0,.2,1);
}
#sidebar.open{transform:translateX(0)}

#sidebar-header{
  padding:14px 16px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:10px;
}
#sidebar-title{flex:1;font-size:14px;font-weight:600}
#sidebar-close{
  width:28px;height:28px;border-radius:7px;border:1px solid var(--border);
  background:none;color:var(--muted);cursor:pointer;font-size:16px;
  display:flex;align-items:center;justify-content:center;transition:all .2s;
}
#sidebar-close:hover{color:var(--text);border-color:rgba(255,255,255,0.2)}

#agents-list{
  padding:12px;border-bottom:1px solid var(--border);
  display:flex;flex-direction:column;gap:6px;
  max-height:180px;overflow-y:auto;
}
.agent-row{
  display:flex;align-items:center;gap:9px;padding:8px 10px;
  border-radius:9px;background:var(--card);border:1px solid var(--border);
  transition:all .2s;
}
.agent-row:hover{border-color:rgba(0,180,216,0.3);background:rgba(0,180,216,0.05)}
.agent-av{
  width:32px;height:32px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:12px;font-weight:700;flex-shrink:0;
}
.agent-info{flex:1;min-width:0}
.agent-name{font-size:13px;font-weight:500;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.agent-status{font-size:11px;color:var(--muted)}
.agent-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}

#chat-msgs{
  flex:1;overflow-y:auto;padding:12px;
  display:flex;flex-direction:column;gap:8px;
}
.msg{display:flex;flex-direction:column;gap:3px;max-width:88%}
.msg.me{align-self:flex-end;align-items:flex-end}
.msg.them{align-self:flex-start;align-items:flex-start}
.msg-row{display:flex;align-items:flex-end;gap:7px;width:100%}
.msg.them .msg-row{flex-direction:row}
.msg.me .msg-row{flex-direction:row-reverse}
.msg-avatar{
  width:26px;height:26px;border-radius:50%;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  font-size:10px;font-weight:700;color:#fff;
}
.msg-bubble{
  padding:8px 12px;border-radius:12px;font-size:13px;line-height:1.45;
}
.msg.me .msg-bubble{background:var(--orange);color:#fff;border-bottom-right-radius:3px}
.msg.them .msg-bubble{background:rgba(255,255,255,0.07);color:var(--text);border-bottom-left-radius:3px}
.msg-meta{font-size:10px;color:var(--muted)}

#chat-input-row{
  padding:10px 12px;border-top:1px solid var(--border);
  display:flex;gap:8px;align-items:flex-end;
}
#chat-input{
  flex:1;background:rgba(255,255,255,0.05);border:1px solid var(--border);
  border-radius:10px;padding:9px 12px;color:var(--text);font-size:13px;
  font-family:'Sora',sans-serif;resize:none;outline:none;max-height:80px;
  transition:border-color .2s;
}
#chat-input:focus{border-color:rgba(0,180,216,0.4)}
#chat-input::placeholder{color:var(--muted)}
#btn-send{
  width:36px;height:36px;border-radius:9px;border:none;
  background:var(--orange);color:#fff;cursor:pointer;font-size:16px;
  display:flex;align-items:center;justify-content:center;
  transition:all .2s;flex-shrink:0;
}
#btn-send:hover{background:var(--orange2);transform:scale(1.05)}

/* ── VOICE BAR ── */
#voice-bar{
  padding:10px 12px;border-top:1px solid var(--border);
  display:flex;align-items:center;gap:8px;
  background:rgba(0,0,0,0.2);
}
#btn-mic{
  display:flex;align-items:center;gap:7px;
  padding:7px 14px;border-radius:20px;border:1px solid var(--border);
  background:var(--card);color:var(--muted);cursor:pointer;font-size:12px;
  font-family:'Sora',sans-serif;transition:all .25s;flex:1;justify-content:center;
}
#btn-mic.active{
  background:rgba(230,57,70,0.15);border-color:rgba(230,57,70,0.4);
  color:var(--red);animation:pulseRed 1.5s ease-in-out infinite;
}
@keyframes pulseRed{0%,100%{box-shadow:0 0 0 0 rgba(230,57,70,0.4)}50%{box-shadow:0 0 0 6px rgba(230,57,70,0)}}
.mic-icon{font-size:16px}
#voice-indicators{display:flex;gap:3px;align-items:center}
.vi-bar{
  width:3px;border-radius:2px;background:var(--red);
  animation:viBounce .6s ease-in-out infinite;
}
.vi-bar:nth-child(2){animation-delay:.1s;height:8px}
.vi-bar:nth-child(3){animation-delay:.2s;height:14px}
.vi-bar:nth-child(4){animation-delay:.15s;height:10px}
.vi-bar:nth-child(5){animation-delay:.05s;height:6px}
@keyframes viBounce{0%,100%{transform:scaleY(0.4)}50%{transform:scaleY(1)}}

/* ── SIDEBAR TOGGLE ── */
#sidebar-toggle{
  position:fixed;right:20px;top:64px;z-index:90;
  width:42px;height:42px;border-radius:12px;
  background:rgba(7,17,31,0.9);border:1px solid var(--border);
  backdrop-filter:blur(10px);cursor:pointer;
  display:flex;align-items:center;justify-content:center;font-size:18px;
  color:var(--muted);transition:all .2s;
}
#sidebar-toggle:hover{border-color:rgba(255,107,26,0.4);color:var(--orange)}
#sidebar-toggle .notif{
  position:absolute;top:7px;right:7px;width:8px;height:8px;
  background:var(--orange);border-radius:50%;border:2px solid var(--navy);
}

/* ── ENTER ROOM POPUP ── */
#room-popup{
  position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
  background:rgba(7,17,31,0.96);border:1px solid var(--border);
  border-radius:18px;padding:28px;width:320px;z-index:200;
  backdrop-filter:blur(24px);
  box-shadow:0 24px 80px rgba(0,0,0,0.7);
  display:none;text-align:center;
}
#room-popup h3{font-size:16px;font-weight:600;margin-bottom:6px}
#room-popup p{font-size:13px;color:var(--muted);margin-bottom:20px;line-height:1.5}
#room-popup .popup-btns{display:flex;gap:10px}
#room-popup .popup-btns button{
  flex:1;padding:10px;border-radius:10px;border:none;
  font-size:13px;font-weight:600;cursor:pointer;font-family:'Sora',sans-serif;
  transition:all .2s;
}
.btn-enter{background:var(--orange);color:#fff}
.btn-enter:hover{background:var(--orange2)}
.btn-cancel{background:rgba(255,255,255,0.07);color:var(--muted);border:1px solid var(--border) !important}
.btn-cancel:hover{color:var(--text)}

/* ── NOTIFICATION TOAST ── */
#toasts{position:fixed;top:64px;right:20px;z-index:300;display:flex;flex-direction:column;gap:8px;pointer-events:none}
.toast-item{
  background:rgba(7,17,31,0.95);border:1px solid var(--border);
  border-radius:12px;padding:10px 14px;
  font-size:13px;color:var(--text);backdrop-filter:blur(20px);
  animation:toastIn .3s ease;pointer-events:all;
  display:flex;align-items:center;gap:9px;
}
@keyframes toastIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}
.toast-item.out{animation:toastOut .3s ease forwards}
@keyframes toastOut{to{opacity:0;transform:translateX(20px)}}

/* ── COMMAND PALETTE (Ctrl+K) ── */
#cmd-palette{
  position:fixed;top:0;left:0;right:0;bottom:0;z-index:999;
  display:flex;align-items:flex-start;justify-content:center;
  padding-top:12vh;background:rgba(0,0,0,0.55);
  backdrop-filter:blur(4px);opacity:0;pointer-events:none;
  transition:opacity .2s ease;
}
#cmd-palette.open{opacity:1;pointer-events:all}
#cmd-palette-wrap{
  width:420px;max-width:90vw;background:var(--navy);
  border:1px solid var(--border);border-radius:16px;
  box-shadow:0 24px 80px rgba(0,0,0,0.7);overflow:hidden;
}
#cmd-input{
  width:100%;padding:14px 18px;font-size:15px;font-family:'Sora',sans-serif;
  background:rgba(255,255,255,0.04);border:none;border-bottom:1px solid var(--border);
  color:var(--text);outline:none;
}
#cmd-input::placeholder{color:var(--muted)}
#cmd-input:focus{border-bottom-color:var(--orange)}
#cmd-list{max-height:320px;overflow-y:auto;padding:6px}
.cmd-item{
  display:flex;align-items:center;gap:12px;padding:10px 14px;
  border-radius:10px;cursor:pointer;transition:all .12s;
  color:var(--text);font-size:13px;
}
.cmd-item:hover,.cmd-item.focused{background:rgba(255,255,255,0.07)}
.cmd-item .cmd-icon{font-size:18px;width:28px;text-align:center}
.cmd-item .cmd-label{flex:1}
.cmd-item .cmd-desc{font-size:11px;color:var(--muted)}
.cmd-item .cmd-shortcut{
  font-size:10px;font-family:'JetBrains Mono',monospace;
  background:rgba(255,255,255,0.06);border:1px solid var(--border);
  border-radius:5px;padding:2px 6px;color:var(--muted);
}

/* ── GLOW SALLE ACTIVE ── */
@keyframes roomGlow{0%,100%{opacity:0.3}50%{opacity:0.9}}
.room-active-glow{animation:roomGlow 2s ease-in-out infinite}

/* ── COPILOT PANEL ── */
#copilot-panel{
  position:fixed;right:0;top:52px;bottom:0;width:340px;
  background:rgba(7,17,31,0.97);border-left:1px solid var(--border);
  backdrop-filter:blur(24px);
  display:flex;flex-direction:column;z-index:75;
  transform:translateX(100%);transition:transform .35s cubic-bezier(.4,0,.2,1);
}
#copilot-panel.open{transform:translateX(0)}
#copilot-head{
  padding:14px 16px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:10px;
}
#copilot-head .cp-logo{
  width:32px;height:32px;border-radius:8px;
  background:linear-gradient(135deg,var(--orange),var(--orange2));
  display:flex;align-items:center;justify-content:center;
  font-size:14px;font-weight:700;color:#fff;flex-shrink:0;
}
#copilot-title{flex:1;font-size:13px;font-weight:600}
#copilot-status{font-size:10px;color:var(--green);display:flex;align-items:center;gap:4px}
#copilot-status::before{content:'';width:5px;height:5px;border-radius:50%;background:var(--green);animation:onlinePulse 2s ease-in-out infinite}
@keyframes onlinePulse{0%,100%{opacity:1}50%{opacity:.4}}
#copilot-close{
  width:26px;height:26px;border-radius:6px;border:1px solid var(--border);
  background:none;color:var(--muted);cursor:pointer;font-size:14px;
  display:flex;align-items:center;justify-content:center;
}
#copilot-close:hover{color:var(--text);border-color:rgba(255,255,255,0.2)}
#copilot-msgs{
  flex:1;overflow-y:auto;padding:12px;
  display:flex;flex-direction:column;gap:8px;
}
.copilot-msg{font-size:13px;line-height:1.5;padding:8px 12px;border-radius:12px;max-width:92%}
.copilot-msg.user{align-self:flex-end;background:var(--orange);color:#fff;border-bottom-right-radius:3px}
.copilot-msg.bot{align-self:flex-start;background:rgba(255,255,255,0.07);color:var(--text);border-bottom-left-radius:3px;white-space:pre-wrap}
#copilot-loading{align-self:flex-start;display:none;gap:4px;padding:10px 14px;background:rgba(255,255,255,0.07);border-radius:12px}
#copilot-loading span{width:6px;height:6px;border-radius:50%;background:var(--muted);animation:cDot 1.2s ease-in-out infinite}
#copilot-loading span:nth-child(2){animation-delay:.2s}
#copilot-loading span:nth-child(3){animation-delay:.4s}
@keyframes cDot{0%,80%,100%{transform:scale(.75);opacity:.45}40%{transform:scale(1.1);opacity:1}}
#copilot-footer{
  padding:10px 12px;border-top:1px solid var(--border);
  display:flex;gap:8px;align-items:flex-end;
}
#copilot-input{
  flex:1;background:rgba(255,255,255,0.05);border:1px solid var(--border);
  border-radius:10px;padding:9px 12px;color:var(--text);font-size:13px;
  font-family:'Sora',sans-serif;resize:none;outline:none;max-height:60px;
}
#copilot-input:focus{border-color:rgba(255,107,26,0.4)}
#copilot-input::placeholder{color:var(--muted)}
</style>
</head>
<body>

<!-- TOPBAR -->
<div id="topbar">
  <span class="tb-logo">⬡ Protex</span>
  <div class="tb-sep"></div>
  <span class="tb-agence">Agence Virtuelle — <?= $adminAgence ?></span>
  <span class="tb-badge" id="hud-agents-count">0 agents en ligne</span>
  <button onclick="toggleCopilot()" style="background:none;border:1px solid var(--border);border-radius:7px;color:var(--muted);padding:5px 10px;font-size:12px;cursor:pointer;font-family:'Sora',sans-serif;display:flex;align-items:center;gap:5px;transition:all .2s" onmouseover="this.style.borderColor='rgba(255,107,26,0.4)';this.style.color='var(--orange)'" onmouseout="this.style.borderColor='';this.style.color='var(--muted)'">🤖 Copilot</button>
  <a href="dashboard.php" class="tb-back">← Dashboard</a>
</div>

<!-- CANVAS 3D -->
<canvas id="cvs"></canvas>

<!-- HUD SALLE -->
<div id="hud-room">Vous êtes dans : <span id="hud-room-name">Entrée</span></div>

<!-- MINIMAP -->
<!-- SIDEBAR TOGGLE -->
<div id="sidebar-toggle" onclick="toggleSidebar()" title="Chat &amp; Agents">
  💬
  <span class="notif" id="notif-dot" style="display:none"></span>
</div>

<!-- VOICE CALL WIDGET -->
<style>
#vw-btn{position:fixed;bottom:20px;right:84px;z-index:80;width:44px;height:44px;border-radius:50%;border:none;background:var(--orange,#FF6B1A);color:#fff;font-size:20px;cursor:pointer;box-shadow:0 4px 16px rgba(255,107,26,0.35);transition:all .3s}
#vw-btn:hover{transform:scale(1.1)}
#vw-btn.active{background:#e63946}
#vw-panel{display:none;position:fixed;bottom:74px;right:20px;z-index:80;width:280px;background:rgba(7,17,31,0.95);border:1px solid rgba(255,255,255,0.1);border-radius:14px;backdrop-filter:blur(16px);overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.5)}
#vw-panel.open{display:block}
.vw-h{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,0.08);font-size:12px;font-weight:600;color:rgba(255,255,255,0.5)}
.vw-b{padding:8px 12px;max-height:180px;overflow-y:auto}
.vw-it{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;cursor:pointer}
.vw-it:hover{background:rgba(255,255,255,0.05)}
.vw-it .vn{flex:1;font-size:12px;color:#fff}
.vw-it .vr{font-size:10px;color:rgba(255,255,255,0.35)}
.vw-it .vc{padding:4px 12px;border-radius:6px;border:none;background:var(--orange,#FF6B1A);color:#fff;font-size:11px;cursor:pointer}
.vw-it .vc.on{background:#e63946}
.vw-e{padding:12px;text-align:center;font-size:11px;color:rgba(255,255,255,0.25)}
</style>
<button id="vw-btn" onclick="vwToggle()" title="Appel vocal">📞</button>
<div id="vw-panel">
  <div class="vw-h">📞 Appel vocal</div>
  <div class="vw-b" id="vw-body"><div class="vw-e">Aucun agent disponible</div></div>
  <div id="vw-mic-row" style="padding:8px 12px;border-top:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:8px">
    <button id="vw-mic-btn" style="width:32px;height:32px;border-radius:50%;border:none;cursor:pointer;font-size:14px;background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.4);transition:all .2s" onclick="vwToggleMic()">🎤</button>
    <span id="vw-mic-label" style="font-size:11px;color:rgba(255,255,255,0.3);flex:1">Micro désactivé</span>
    <span id="vw-status" style="font-size:10px;color:rgba(255,255,255,0.2)"></span>
  </div>
</div>

<!-- SIDEBAR -->
<div id="sidebar">
  <div id="sidebar-header">
    <div id="sidebar-title">🏢 <span id="sb-room-label">Entrée</span></div>
    <button onclick="toggleCopilot()" style="width:28px;height:28px;border-radius:7px;border:1px solid var(--border);background:none;color:var(--muted);cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;transition:all .2s" title="Copilot IA" onmouseover="this.style.borderColor='rgba(255,107,26,0.4)';this.style.color='var(--orange)'" onmouseout="this.style.borderColor='';this.style.color='var(--muted)'">🤖</button>
    <button id="sidebar-close" onclick="toggleSidebar()">✕</button>
  </div>
  <div id="agents-list"></div>
  <div id="room-kpis" style="display:none;border-bottom:1px solid rgba(255,255,255,0.07)"></div>
  <div id="room-desc" style="padding:8px 12px;border-bottom:1px solid rgba(255,255,255,0.07);font-size:11.5px;color:var(--muted);line-height:1.4;display:none"></div>
  <div id="room-actions" style="padding:10px 12px;border-bottom:1px solid rgba(255,255,255,0.07);display:none"></div>
  <div id="chat-msgs"></div>
  <div id="voice-bar">
    <button id="btn-mic" onclick="toggleMic()">
      <span class="mic-icon">🎤</span>
      <span id="mic-label">Parler dans la salle</span>
      <div id="voice-indicators" style="display:none">
        <div class="vi-bar" style="height:6px"></div>
        <div class="vi-bar"></div>
        <div class="vi-bar"></div>
        <div class="vi-bar"></div>
        <div class="vi-bar" style="height:6px"></div>
      </div>
    </button>
  </div>
  <div id="chat-input-row">
    <textarea id="chat-input" rows="1" placeholder="Message à la salle…"></textarea>
    <button id="btn-send" onclick="sendMsg()" title="Envoyer">➤</button>
  </div>
</div>

<!-- ROOM POPUP -->
<div id="room-popup">
  <h3 id="popup-title">Entrer dans la salle</h3>
  <p id="popup-desc">Vous allez rejoindre cette salle et pouvoir discuter avec les agents présents.</p>
  <div class="popup-btns">
    <button class="btn-cancel" onclick="closePopup()">Annuler</button>
    <button class="btn-enter" onclick="enterRoom()">Entrer ↗</button>
  </div>
</div>

<!-- TOASTS -->
<div id="toasts"></div>

<script src="assets/js/vendor/three.min.js"></script>
<script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
<script>
// ═══════════════════════════════════════════════════════════
// CONFIG
// ═══════════════════════════════════════════════════════════
const ADMIN_NAME = "<?= $adminPrenom ?> <?= $adminNom ?>";
const ADMIN_ID   = <?= $adminId ?>;

const AGENTS = <?= json_encode($agentsJs, JSON_UNESCAPED_UNICODE) ?>;
let agents = [...AGENTS];

// ═══════════════════════════════════════════════════════════
// ROOM LAYOUT — plan isométrique
// ═══════════════════════════════════════════════════════════
const ROOMS = [
  {
    id:"entree", label:"Entrée", icon:"🚪",
    // grille logique (col, row, largeur, hauteur) en tiles de 64px
    gx:5, gy:8, gw:4, gh:3,
    color:"#1a3a7a", floor:"#162d5e", accent:"#2255bb",
    agents:["Youssef Chahed"],
    desc:"Hall d'accueil principal de l'agence",
  },
  {
    id:"auto", label:"Salle Auto", icon:"🚗",
    gx:1, gy:2, gw:5, gh:4,
    color:"#0d3349", floor:"#0a2638", accent:"#00b4d8",
    agents:["Sami Trabelsi"],
    desc:"Gestion des contrats auto et sinistres véhicules",
  },
  {
    id:"sante", label:"Salle Santé", icon:"🏥",
    gx:8, gy:2, gw:5, gh:4,
    color:"#0d3320", floor:"#092918", accent:"#2ec46f",
    agents:["Leila Ben Amor"],
    desc:"Contrats santé et remboursements médicaux",
  },
  {
    id:"habitation", label:"Salle Habitation", icon:"🏠",
    gx:1, gy:8, gw:3, gh:3,
    color:"#2d1f4a", floor:"#221740", accent:"#a855f7",
    agents:["Karim Mansouri"],
    desc:"Assurance habitation et multirisques",
  },
  {
    id:"sinistres", label:"Salle Sinistres", icon:"⚠️",
    gx:10, gy:7, gw:4, gh:4,
    color:"#3d1a0d", floor:"#2e140a", accent:"#FF6B1A",
    agents:["Nadia Hamdi"],
    desc:"Traitement et analyse des sinistres déclarés",
  },
  {
    id:"reunion", label:"Salle Réunion", icon:"👥",
    gx:5, gy:2, gw:3, gh:3,
    color:"#1a2040", floor:"#141835", accent:"#f59e0b",
    agents:[],
    desc:"Salle de réunions et conférences",
  },
  {
    id:"archives", label:"Archives", icon:"📁",
    gx:10, gy:2, gw:3, gh:3,
    color:"#1f1a0d", floor:"#181409", accent:"#d4a017",
    agents:[],
    desc:"Stockage et gestion documentaire",
  },
];

// ═══════════════════════════════════════════════════════════
// ENGINE 3D ISOMÉTRIQUE (Three.js via CDN)
// ═══════════════════════════════════════════════════════════
const CVS = document.getElementById('cvs');
const TILE = 64; // taille d'une tuile en px monde

let scene, camera, renderer, clock;
let adminMesh, adminPos = {x:0, z:0};
let targetPos = {x:0, z:0};
let currentRoom = null;
let pendingRoom  = null;
let roomMeshes   = {};
let agentMeshes  = {};
let keys = {};
let sidebarOpen  = false;
let micActive    = false;
let micStream    = null;
let recognition  = null;
let unreadCount  = 0;

function initEngine() {
  // Scene
  scene = new THREE.Scene();
  scene.background = new THREE.Color(0x07111f);
  scene.fog = new THREE.FogExp2(0x07111f, 0.018);

  // Camera isométrique
  const W = CVS.clientWidth, H = CVS.clientHeight;
  const aspect = W / H;
  const d = 22;
  camera = new THREE.OrthographicCamera(-d*aspect, d*aspect, d, -d, -100, 200);
  camera.position.set(30, 30, 30);
  camera.lookAt(0, 0, 0);

  // Renderer
  renderer = new THREE.WebGLRenderer({canvas:CVS, antialias:true});
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.setSize(W, H);
  renderer.shadowMap.enabled = true;
  renderer.shadowMap.type = THREE.PCFSoftShadowMap;

  clock = new THREE.Clock();

  // Lumières
  const ambient = new THREE.AmbientLight(0x334466, 1.2);
  scene.add(ambient);

  const dirLight = new THREE.DirectionalLight(0xffffff, 2.0);
  dirLight.position.set(20, 40, 20);
  dirLight.castShadow = true;
  dirLight.shadow.mapSize.set(2048, 2048);
  dirLight.shadow.camera.near = 0.1;
  dirLight.shadow.camera.far = 200;
  dirLight.shadow.camera.left = -60;
  dirLight.shadow.camera.right = 60;
  dirLight.shadow.camera.top = 60;
  dirLight.shadow.camera.bottom = -60;
  scene.add(dirLight);

  // Point light accent orange
  const ptLight = new THREE.PointLight(0xFF6B1A, 1.5, 30);
  ptLight.position.set(0, 8, 0);
  scene.add(ptLight);

  // Sol global
  const groundGeo = new THREE.PlaneGeometry(80, 80);
  const groundMat = new THREE.MeshStandardMaterial({color:0x050e1a, roughness:1});
  const ground = new THREE.Mesh(groundGeo, groundMat);
  ground.rotation.x = -Math.PI/2;
  ground.receiveShadow = true;
  scene.add(ground);

  buildRooms();
  buildAdmin();
  buildAgents();
  buildDecor();

  window.addEventListener('resize', onResize);
  window.addEventListener('keydown', e => { keys[e.key.toLowerCase()] = true; preventDefaultKeys(e); });
  window.addEventListener('keyup',   e => { keys[e.key.toLowerCase()] = false; });
  CVS.addEventListener('click', onCanvasClick);

  enterRoomById("entree");
  animate();

  setTimeout(() => {
    pollAgentPresence();
    setInterval(pollAgentPresence, 30000);
  }, 2000);

  setTimeout(() => {
    pollSosAlerts();
    setInterval(pollSosAlerts, 15000);
  }, 3000);

  setTimeout(pollNotifications, 4000);
  setInterval(pollNotifications, 20000);

  setTimeout(pollRoomActivity, 1000);
  setInterval(pollRoomActivity, 15000);

  // Heartbeat pour maintenir le statut "en ligne"
  fetch('../../api.php?action=heartbeat');
  setInterval(() => fetch('../../api.php?action=heartbeat'), 60000);
}

function preventDefaultKeys(e) {
  if(['arrowup','arrowdown','arrowleft','arrowright',' '].includes(e.key.toLowerCase())) {
    e.preventDefault();
  }
}

// ── Conversion grille → monde 3D ──
function gridToWorld(gx, gy) {
  return {x: gx * 1.5 - 12, z: gy * 1.5 - 11};
}

// ── Construire les salles ──
function buildRooms() {
  ROOMS.forEach(room => {
    const w3 = room.gw * 1.5;
    const h3 = room.gh * 1.5;
    const cx  = room.gx * 1.5 - 12 + w3/2;
    const cz  = room.gy * 1.5 - 11 + h3/2;

    // Sol de la salle
    const floorGeo = new THREE.BoxGeometry(w3 - 0.1, 0.15, h3 - 0.1);
    const floorMat = new THREE.MeshStandardMaterial({
      color: new THREE.Color(room.floor),
      roughness:0.8, metalness:0.1
    });
    const floor = new THREE.Mesh(floorGeo, floorMat);
    floor.position.set(cx, 0.075, cz);
    floor.receiveShadow = true;
    scene.add(floor);

    // Murs (4 côtés bas, style isométrique)
    const wallH = 2.0;
    const wallMat = new THREE.MeshStandardMaterial({
      color: new THREE.Color(room.color),
      roughness:0.7, metalness:0.15,
      transparent:true, opacity:0.88
    });

    // Mur Nord
    const wallN = new THREE.Mesh(new THREE.BoxGeometry(w3, wallH, 0.2), wallMat.clone());
    wallN.position.set(cx, wallH/2, cz - h3/2);
    wallN.castShadow = wallN.receiveShadow = true;
    scene.add(wallN);

    // Mur Ouest
    const wallW = new THREE.Mesh(new THREE.BoxGeometry(0.2, wallH, h3), wallMat.clone());
    wallW.position.set(cx - w3/2, wallH/2, cz);
    wallW.castShadow = wallW.receiveShadow = true;
    scene.add(wallW);

    // Bordure lumineuse accent
    const edgeGeo = new THREE.BoxGeometry(w3, 0.05, 0.08);
    const edgeMat = new THREE.MeshStandardMaterial({
      color: new THREE.Color(room.accent), emissive: new THREE.Color(room.accent), emissiveIntensity:0.8
    });
    const edge = new THREE.Mesh(edgeGeo, edgeMat);
    edge.position.set(cx, 0.18, cz - h3/2 + 0.04);
    scene.add(edge);

    // Sprite label (simulé via plane + texture canvas)
    const labelTex = makeLabelTexture(room.icon + ' ' + room.label, room.accent);
    const labelMat = new THREE.MeshBasicMaterial({map:labelTex, transparent:true, depthWrite:false});
    const labelMesh = new THREE.Mesh(new THREE.PlaneGeometry(3.5, 0.9), labelMat);
    labelMesh.position.set(cx, 2.8, cz);
    labelMesh.rotation.x = -Math.PI * 0.28;
    scene.add(labelMesh);

    // Mobilier
    const deskGeo = new THREE.BoxGeometry(1.0, 0.3, 0.6);
    const deskMat = new THREE.MeshStandardMaterial({color:0x1a2a45, roughness:0.6});
    const desk = new THREE.Mesh(deskGeo, deskMat);
    desk.position.set(cx, 0.3, cz + 0.3);
    desk.castShadow = true;
    scene.add(desk);

    // Chaise devant le bureau
    const chairGeo = new THREE.BoxGeometry(0.5, 0.5, 0.5);
    const chairMat = new THREE.MeshStandardMaterial({color:0x0d1c35, roughness:0.7});
    const chair = new THREE.Mesh(chairGeo, chairMat);
    chair.position.set(cx + 0.4, 0.25, cz + 0.3);
    chair.castShadow = true;
    scene.add(chair);

    // Écran sur le bureau
    const screenGeo = new THREE.BoxGeometry(0.7, 0.45, 0.05);
    const screenMat = new THREE.MeshStandardMaterial({
      color: new THREE.Color(room.accent), emissive: new THREE.Color(room.accent), emissiveIntensity:0.4
    });
    const screen = new THREE.Mesh(screenGeo, screenMat);
    screen.position.set(cx, 0.6, cz + 0.02);
    scene.add(screen);

    // Plante décorative
    const potGeo = new THREE.CylinderGeometry(0.13, 0.1, 0.25, 6);
    const potMat = new THREE.MeshStandardMaterial({color:0x3d2b0a, roughness:0.8});
    const pot = new THREE.Mesh(potGeo, potMat);
    pot.position.set(cx + w3/2 - 0.4, 0.125, cz - h3/2 + 0.4);
    scene.add(pot);
    const leafGeo = new THREE.SphereGeometry(0.3, 6, 6);
    const leafMat = new THREE.MeshStandardMaterial({color:0x1a4a1a, roughness:0.7});
    const leaf = new THREE.Mesh(leafGeo, leafMat);
    leaf.position.set(cx + w3/2 - 0.4, 0.55, cz - h3/2 + 0.4);
    scene.add(leaf);

    // Zone cliquable (invisible, pour raycasting)
    const hitGeo = new THREE.BoxGeometry(w3, 1.5, h3);
    const hitMat = new THREE.MeshBasicMaterial({transparent:true, opacity:0});
    const hit = new THREE.Mesh(hitGeo, hitMat);
    hit.position.set(cx, 0.75, cz);
    hit.userData = {roomId: room.id, cx, cz};
    scene.add(hit);

    // Lumières de salle
    const rLight = new THREE.PointLight(new THREE.Color(room.accent), 0.6, 8);
    rLight.position.set(cx, 3, cz);
    scene.add(rLight);
    const rLight2 = new THREE.PointLight(new THREE.Color(room.accent), 0, 6);
    rLight2.position.set(cx, 2, cz);
    scene.add(rLight2);
    roomMeshes[room.id] = {hit, floor, edgeMat, cx, cz, room, rLight, rLight2};

    // Grand écran mural 3D (panneau d'affichage)
    const panelGeo  = new THREE.PlaneGeometry(2.5, 1.5);
    const panelCanvas = document.createElement('canvas');
    panelCanvas.width = 512; panelCanvas.height = 300;
    const pCtx = panelCanvas.getContext('2d');
    pCtx.fillStyle = '#040c1a';
    pCtx.fillRect(0,0,512,300);
    pCtx.strokeStyle = room.accent; pCtx.lineWidth = 3;
    pCtx.strokeRect(3,3,506,294);
    pCtx.fillStyle = room.accent;
    pCtx.font = 'bold 28px Sora, sans-serif';
    pCtx.textAlign = 'center';
    pCtx.fillText(room.label, 256, 60);
    pCtx.fillStyle = 'rgba(255,255,255,0.5)';
    pCtx.font = '18px Sora, sans-serif';
    pCtx.fillText('Chargement des données…', 256, 150);

    const panelTex  = new THREE.CanvasTexture(panelCanvas);
    const panelMat  = new THREE.MeshStandardMaterial({map:panelTex, emissive:new THREE.Color(room.accent), emissiveIntensity:0.05});
    const panelMesh = new THREE.Mesh(panelGeo, panelMat);
    panelMesh.position.set(cx, 1.3, cz - h3/2 + 0.15);
    panelMesh.frustumCulled = true;
    scene.add(panelMesh);
    roomMeshes[room.id] = {...(roomMeshes[room.id]||{}), panelCanvas, panelTex, panelMat, pCtx};
  });
}

function makeLabelTexture(text, accentHex) {
  const c = document.createElement('canvas');
  c.width = 512; c.height = 128;
  const ctx = c.getContext('2d');
  ctx.fillStyle = 'rgba(7,17,31,0.8)';
  ctx.roundRect(4, 4, c.width-8, c.height-8, 16);
  ctx.fill();
  ctx.strokeStyle = accentHex;
  ctx.lineWidth = 3;
  ctx.roundRect(4, 4, c.width-8, c.height-8, 16);
  ctx.stroke();
  ctx.fillStyle = '#ffffff';
  ctx.font = 'bold 36px Sora, sans-serif';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText(text, c.width/2, c.height/2);
  const tex = new THREE.CanvasTexture(c);
  return tex;
}

// ── Avatar admin ──
function buildAdmin() {
  const g = new THREE.Group();

  // Corps
  const bodyGeo = new THREE.BoxGeometry(0.5, 0.8, 0.3);
  const bodyMat = new THREE.MeshStandardMaterial({color:0x1A3A7A, roughness:0.5});
  const body = new THREE.Mesh(bodyGeo, bodyMat);
  body.position.y = 0.6;
  body.castShadow = true;
  g.add(body);

  // Tête
  const headGeo = new THREE.SphereGeometry(0.22, 8, 8);
  const headMat = new THREE.MeshStandardMaterial({color:0xf4c09a, roughness:0.7});
  const head = new THREE.Mesh(headGeo, headMat);
  head.position.y = 1.18;
  head.castShadow = true;
  g.add(head);

  // Halo admin (anneau orange)
  const ringGeo = new THREE.TorusGeometry(0.35, 0.04, 8, 24);
  const ringMat = new THREE.MeshStandardMaterial({
    color:0xFF6B1A, emissive:0xFF6B1A, emissiveIntensity:1.2
  });
  const ring = new THREE.Mesh(ringGeo, ringMat);
  ring.rotation.x = Math.PI/2;
  ring.position.y = 1.5;
  g.add(ring);

  // Label flottant
  const adminTex = makeLabelTexture('👑 ' + ADMIN_NAME, '#FF6B1A');
  const labelMat = new THREE.MeshBasicMaterial({map:adminTex, transparent:true, depthWrite:false});
  const label = new THREE.Mesh(new THREE.PlaneGeometry(3, 0.75), labelMat);
  label.position.y = 2.0;
  label.rotation.x = -Math.PI * 0.1;
  g.add(label);

  g.position.set(adminPos.x, 0, adminPos.z);
  g.frustumCulled = true;
  scene.add(g);
  adminMesh = g;
  adminMesh._ring = ring;
  adminMesh._label = label;
}

// ── Agents ──
function buildAgents() {
  const agentColors = {
    "#00b4d8": 0x00b4d8, "#2ec46f": 0x2ec46f,
    "#a855f7": 0xa855f7, "#FF6B1A": 0xFF6B1A, "#f59e0b": 0xf59e0b
  };

  agents.forEach((agent, i) => {
    const room = ROOMS.find(r => r.label === agent.salle);
    if (!room) return;
    const w3 = room.gw * 1.5;
    const h3 = room.gh * 1.5;
    const cx  = room.gx * 1.5 - 12 + w3/2;
    const cz  = room.gy * 1.5 - 11 + h3/2;

    const g = new THREE.Group();
    const col = agentColors[agent.color] || 0x888888;
    const onlineColor = agent.online ? col : 0x444444;

    const bodyGeo = new THREE.BoxGeometry(0.4, 0.7, 0.25);
    const bodyMat = new THREE.MeshStandardMaterial({color: onlineColor, roughness:0.5});
    const body = new THREE.Mesh(bodyGeo, bodyMat); body.position.y = 0.55; body.castShadow = true; g.add(body);

    const headGeo = new THREE.SphereGeometry(0.18, 8, 8);
    const headMat = new THREE.MeshStandardMaterial({color:0xf4c09a, roughness:0.7});
    const head = new THREE.Mesh(headGeo, headMat); head.position.y = 1.05; g.add(head);

    if (agent.online) {
      const dotGeo = new THREE.SphereGeometry(0.07, 6, 6);
      const dotMat = new THREE.MeshStandardMaterial({color:0x2ec46f, emissive:0x2ec46f, emissiveIntensity:2});
      const dot = new THREE.Mesh(dotGeo, dotMat); dot.position.set(0.2, 1.22, 0); g.add(dot);
    }

    // Label
    const agentTex = makeLabelTexture((agent.online ? '🟢 ' : '⚫ ') + agent.nom.split(' ')[0], agent.color);
    const lMat = new THREE.MeshBasicMaterial({map:agentTex, transparent:true, depthWrite:false});
    const lMesh = new THREE.Mesh(new THREE.PlaneGeometry(2.5, 0.65), lMat);
    lMesh.position.y = 1.7; lMesh.rotation.x = -Math.PI * 0.1; g.add(lMesh);

    // Positionner légèrement décalé du centre de la salle
    const offset = [(i%2)*0.6-0.3, 0, Math.floor(i/2)*0.6-0.3];
    g.position.set(cx + offset[0] - 0.5, 0, cz + offset[2] + 0.5);
    g.frustumCulled = true;
    scene.add(g);
    agentMeshes[agent.id] = g;
    g.userData = {agentId: agent.id};
  });
}

// ── Décoration ──
function buildDecor() {
  // Couloir central (dalles)
  for (let i = 0; i < 8; i++) {
    const tileGeo = new THREE.BoxGeometry(1.4, 0.08, 1.4);
    const tileMat = new THREE.MeshStandardMaterial({color: i%2===0 ? 0x0a1525 : 0x0d1c35, roughness:0.9});
    const tile = new THREE.Mesh(tileGeo, tileMat);
    tile.position.set(-12 + i * 2, 0.04, 0);
    tile.receiveShadow = true;
    scene.add(tile);
  }

  // Plantes décoratives
  const plantPos = [{x:-10, z:-8},{x:10, z:-8},{x:-10, z:8},{x:10, z:8}];
  plantPos.forEach(p => {
    const potGeo = new THREE.CylinderGeometry(0.2, 0.15, 0.4, 8);
    const potMat = new THREE.MeshStandardMaterial({color:0x3d2b0a, roughness:0.8});
    const pot = new THREE.Mesh(potGeo, potMat); pot.position.set(p.x, 0.2, p.z); scene.add(pot);
    const leafGeo = new THREE.SphereGeometry(0.45, 8, 8);
    const leafMat = new THREE.MeshStandardMaterial({color:0x1a4a1a, roughness:0.7});
    const leaf = new THREE.Mesh(leafGeo, leafMat); leaf.position.set(p.x, 0.85, p.z); scene.add(leaf);
  });

  // Logo Protex au sol (disque)
  const logoGeo = new THREE.CylinderGeometry(1.2, 1.2, 0.06, 32);
  const logoMat = new THREE.MeshStandardMaterial({color:0xFF6B1A, emissive:0xFF6B1A, emissiveIntensity:0.3});
  const logo = new THREE.Mesh(logoGeo, logoMat);
  logo.position.set(0, 0.03, 0);
  scene.add(logo);
}

// ═══════════════════════════════════════════════════════════
// GAME LOOP
// ═══════════════════════════════════════════════════════════
const SPEED = 6.0;

let walkTime = 0;
let isMoving = false;
function animate() {
  requestAnimationFrame(animate);
  const dt = clock.getDelta();
  handleMovement(dt);
  updateCamera();
  updateAgentBob(dt);
  if (adminMesh) {
    if (isMoving) {
      walkTime += dt * 8;
      adminMesh.position.y = Math.abs(Math.sin(walkTime)) * 0.1 - 0.05;
      adminMesh.rotation.x = Math.sin(walkTime * 2) * 0.03;
      adminMesh.rotation.z = Math.cos(walkTime * 2) * 0.03;
    } else {
      adminMesh.position.y *= 0.9;
      adminMesh.rotation.x *= 0.9;
      adminMesh.rotation.z *= 0.9;
    }
  }
  renderer.render(scene, camera);
}

function handleMovement(dt) {
  let dx = 0, dz = 0;
  const isDown = k => keys[k];

  if (isDown('z') || isDown('arrowup'))    { dx -= 1; dz -= 1; }
  if (isDown('s') || isDown('arrowdown'))  { dx += 1; dz += 1; }
  if (isDown('q') || isDown('arrowleft'))  { dx -= 1; dz += 1; }
  if (isDown('d') || isDown('arrowright')) { dx += 1; dz -= 1; }

  if (window._joyDX && window._joyDZ) {
    const jdx = window._joyDX();
    const jdz = window._joyDZ();
    isMoving = jdx !== 0 || jdz !== 0;
    if (isMoving) {
      adminPos.x += jdx * SPEED * dt;
      adminPos.z += jdz * SPEED * dt;
      adminPos.x = Math.max(-14, Math.min(14, adminPos.x));
      adminPos.z = Math.max(-11, Math.min(12, adminPos.z));
      adminMesh.position.x = adminPos.x;
      adminMesh.position.z = adminPos.z;
      adminMesh.rotation.y = Math.atan2(jdx, jdz);
      checkRoomEntry();
    }
  }

  isMoving = dx !== 0 || dz !== 0;
  if (isMoving) {
    const len = Math.sqrt(dx*dx + dz*dz);
    adminPos.x += (dx/len) * SPEED * dt;
    adminPos.z += (dz/len) * SPEED * dt;
    // Clamp dans les limites
    adminPos.x = Math.max(-14, Math.min(14, adminPos.x));
    adminPos.z = Math.max(-11, Math.min(12, adminPos.z));
    adminMesh.position.x = adminPos.x;
    adminMesh.position.z = adminPos.z;
    // Rotation vers direction
    adminMesh.rotation.y = Math.atan2(dx, dz);

    checkRoomEntry();
  }

  // Oscillation de l'anneau
  if (adminMesh && adminMesh._ring) {
    adminMesh._ring.rotation.z += dt * 1.5;
  }

  // Espace = ouvrir sidebar
  if (keys[' '] && !keys['_spaceConsumed']) {
    keys['_spaceConsumed'] = true;
    toggleSidebar();
  }
  if (!keys[' ']) keys['_spaceConsumed'] = false;
}

function updateCamera() {
  const tx = adminMesh.position.x;
  const tz = adminMesh.position.z;
  camera.position.x += (tx + 30 - camera.position.x) * 0.08;
  camera.position.z += (tz + 30 - camera.position.z) * 0.08;
  camera.lookAt(tx, 0, tz);
}

let agentBobTime = 0;
function updateAgentBob(dt) {
  agentBobTime += dt;
  Object.values(agentMeshes).forEach((g, i) => {
    const t = agentBobTime * 1.2 + i * 0.8;
    g.position.y = Math.sin(t) * 0.05;
    g.rotation.z = Math.sin(t * 0.7) * 0.02;
  });
}

// ═══════════════════════════════════════════════════════════
// DÉTECTION DE SALLE
// ═══════════════════════════════════════════════════════════
function checkRoomEntry() {
  let found = null;
  ROOMS.forEach(room => {
    const w3 = room.gw * 1.5;
    const h3 = room.gh * 1.5;
    const cx  = room.gx * 1.5 - 12 + w3/2;
    const cz  = room.gy * 1.5 - 11 + h3/2;
    const margin = 0.5;
    if (
      adminPos.x >= cx - w3/2 + margin && adminPos.x <= cx + w3/2 - margin &&
      adminPos.z >= cz - h3/2 + margin && adminPos.z <= cz + h3/2 - margin
    ) {
      found = room;
    }
  });

  if (found && found.id !== (currentRoom?.id)) {
    enterRoomById(found.id);
  }
}

const roomActions = {
  sinistres: [
    {label:'📋 Liste des sinistres', url:'sinistre_list.php'},
    {label:'🔍 Analyse fraude',      url:'fraud_analyse.php'},
  ],
  auto: [
    {label:'📄 Contrats Auto',       url:'contrats_back.php?type=Auto'},
    {label:'📊 Stats Auto',          url:'statsType.php?type=auto'},
  ],
  sante: [
    {label:'📄 Contrats Santé',      url:'contrats_back.php?type=Santé'},
  ],
  habitation: [
    {label:'📄 Contrats Habitation', url:'contrats_back.php?type=Habitation'},
  ],
  entree: [
    {label:'👥 Gestion utilisateurs',url:'admin-users.php'},
    {label:'📊 Dashboard',           url:'dashboard.php'},
  ],
  reunion: [
    {label:'📅 Agenda / RDV',        url:'agenda.php'},
    {label:'📢 Réclamations',        url:'listreponse.php'},
  ],
};

const kpiSalleMap = {
  sinistres:'sinistres', auto:'auto',
  sante:'sante', habitation:'habitation', reunion:'reunion'
};

function enterRoomById(id) {
  const room = ROOMS.find(r => r.id === id);
  if (!room) return;
  if (currentRoom) voiceLeaveRoom();
  currentRoom = room;
  document.getElementById('hud-room-name').textContent = room.label;
  document.getElementById('sb-room-label').textContent = room.icon + ' ' + room.label;
  refreshAgentsList();
  refreshChat();
  showToast('📍 ' + room.label, room.desc);
  updateHudCount();

  // Description salle
  const descEl = document.getElementById('room-desc');
  if (descEl) {
    const inRoom = agents.filter(a => a.salle === room.label && a.online);
    const agentNames = inRoom.map(a => a.nom.split(' ')[0]).join(', ');
    descEl.style.display = 'block';
    descEl.innerHTML = `${room.desc}${agentNames ? '<br>👤 <strong style="color:var(--text)">' + escHtml(agentNames) + '</strong> présent(s)' : ''}`;
  }

  // KPIs
  const kpiType = kpiSalleMap[id];
  if (kpiType) fetchRoomKPIs(kpiType);
  else document.getElementById('room-kpis').style.display = 'none';

  // Actions
  const actions = roomActions[id] || [];
  const actionsEl = document.getElementById('room-actions');
  if (actions.length) {
    actionsEl.style.display = 'block';
    actionsEl.innerHTML = actions.map(a => `
      <a href="${escHtml(a.url)}" style="display:flex;align-items:center;gap:8px;
         padding:7px 10px;border-radius:8px;font-size:12.5px;
         color:rgba(255,255,255,0.65);text-decoration:none;
         background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);
         margin-bottom:5px;transition:all .15s"
         onmouseover="this.style.background='rgba(255,107,26,0.12)';this.style.color='#fff'"
         onmouseout="this.style.background='rgba(255,255,255,0.03)';this.style.color='rgba(255,255,255,0.65)'">
        ${escHtml(a.label)} <span style="margin-left:auto;opacity:.4;font-size:11px">→</span>
      </a>
    `).join('');
  } else {
    actionsEl.style.display = 'none';
  }

  // Effet lumière : éteindre toutes, allumer la salle courante
  Object.values(roomMeshes).forEach(m => {
    m.rLight2.intensity = 0;
    m.edgeMat.emissiveIntensity = 0.3;
  });
  if (roomMeshes[id]) {
    roomMeshes[id].rLight2.intensity = 2;
    roomMeshes[id].edgeMat.emissiveIntensity = 1.5;
  }

  // Voice call
  if (room.id !== 'entree') voiceJoinRoom(room.label);

  // Saluer l'agent présent dans la salle
  setTimeout(() => greetAgentInRoom(), 1500);
}

function fetchRoomKPIs(salle) {
  const el = document.getElementById('room-kpis');
  el.style.display = 'block';
  el.innerHTML = '<div style="padding:10px 12px;font-size:11px;color:var(--muted)">Chargement stats…</div>';

  fetch(`../../api.php?action=room_kpis&salle=${salle}`)
    .then(r => r.json())
    .then(data => {
      if (!data.success || !data.kpis.length) { el.style.display='none'; return; }
      el.innerHTML = `
        <div style="padding:10px 14px">
          <div style="font-size:9.5px;letter-spacing:.1em;text-transform:uppercase;
               color:rgba(255,255,255,0.3);margin-bottom:8px;font-weight:500">
            Stats en direct
          </div>
          ${data.kpis.map(k => `
            <div style="display:flex;justify-content:space-between;align-items:center;
                 padding:5px 0;border-bottom:1px solid rgba(255,255,255,0.04)">
              <span style="font-size:12px;color:rgba(255,255,255,0.6)">${escHtml(k.label)}</span>
              <strong style="font-size:14px;color:${escHtml(k.color || '#fff')}">${escHtml(String(k.value))}</strong>
            </div>
          `).join('')}
        </div>
      `;
      // Mettre à jour le panneau mural 3D
      updateWallPanel(salle, data.kpis);
    })
    .catch(() => { el.style.display = 'none'; });
}

// ── Clic sur canvas ──
const raycaster = new THREE.Raycaster();
const mouse = new THREE.Vector2();
function onCanvasClick(e) {
  const rect = CVS.getBoundingClientRect();
  mouse.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
  mouse.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
  raycaster.setFromCamera(mouse, camera);
  const hits = raycaster.intersectObjects(Object.values(roomMeshes).map(r => r.hit));
  if (hits.length > 0) {
    const d = hits[0].object.userData;
    pendingRoom = ROOMS.find(r => r.id === d.roomId);
    if (pendingRoom && pendingRoom.id !== currentRoom?.id) {
      // Téléporter l'avatar au centre de la salle
      adminPos.x = d.cx;
      adminPos.z = d.cz;
      adminMesh.position.x = adminPos.x;
      adminMesh.position.z = adminPos.z;
      enterRoomById(pendingRoom.id);
    }
  }
}

// ═══════════════════════════════════════════════════════════
// SIDEBAR — AGENTS & CHAT
// ═══════════════════════════════════════════════════════════
function refreshAgentsList() {
  const list = document.getElementById('agents-list');
  const inRoom = agents.filter(a => a.salle === currentRoom?.label);
  const onlineTotal = agents.filter(a => a.online).length;
  document.getElementById('hud-agents-count').textContent = onlineTotal + ' agents en ligne';

  // Afficher tous les agents en ligne, pas seulement ceux dans la salle courante
  const allOnline = agents.filter(a => a.online);
  
  if (allOnline.length === 0) {
    list.innerHTML = '<div style="padding:10px 12px;font-size:12px;color:var(--muted)">Aucun agent en ligne</div>';
    return;
  }
  
  // Agents dans la salle courante
  const header = inRoom.length > 0 
    ? `<div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:6px;font-weight:500">Dans cette salle</div>
       ${inRoom.map(a => agentRowHtml(a, true)).join('')}`
    : '';

  // Autres agents en ligne (dans d'autres salles)
  const others = allOnline.filter(a => a.salle !== currentRoom?.label);
  const otherSection = others.length > 0
    ? `<div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin:8px 0 6px;font-weight:500">Autres agents</div>
       ${others.map(a => agentRowHtml(a, false)).join('')}`
    : '';

  list.innerHTML = header + otherSection;
}

function agentRowHtml(a, isHere) {
  const room = ROOMS.find(r => r.label === a.salle);
  const isAdmin = <?= json_encode(in_array($role, ['admin','superadmin'])) ?>;
  const goBtn = !isHere && room
    ? `<button onclick="teleportToRoom('${room.id}')" title="Aller dans ${escHtml(a.salle)}"
         style="background:none;border:1px solid rgba(255,255,255,0.1);border-radius:5px;color:var(--muted);cursor:pointer;font-size:10px;padding:2px 6px;margin-left:4px;transition:all .15s"
         onmouseover="this.style.borderColor='rgba(255,107,26,0.4)';this.style.color='var(--orange)'"
         onmouseout="this.style.borderColor='';this.style.color='var(--muted)'">${room.icon}</button>`
    : '';

  const assignSelect = isAdmin
    ? `<select onchange="assignAgentRoom(${a.id}, this.value)" onclick="event.stopPropagation()"
         style="background:var(--navy3);color:var(--text);border:1px solid var(--border);border-radius:5px;font-size:10px;padding:2px 4px;margin-left:4px;cursor:pointer;font-family:'Sora',sans-serif"
         title="Déplacer l'agent vers une autre salle">
         ${ROOMS.map(r => `<option value="${escHtml(r.label)}" ${r.label === a.salle ? 'selected' : ''}>${r.icon} ${escHtml(r.label.replace('Salle ',''))}</option>`).join('')}
       </select>`
    : '';

  return `
    <div class="agent-row" style="cursor:pointer" onclick="${isHere ? '' : `teleportToRoom('${room?.id || 'entree'}')`}">
      <div class="agent-av" style="background:${a.color}22;color:${a.color}">${escHtml(a.initials)}</div>
      <div class="agent-info">
        <div class="agent-name">${escHtml(a.nom)} <span style="font-size:10px;color:var(--muted);font-weight:400">${escHtml(a.role)}</span></div>
        <div class="agent-status" style="display:flex;align-items:center;gap:4px">
          <span style="font-size:10px">${isHere ? '📍 ' : ''}${escHtml(a.salle)}</span>
          ${assignSelect}
        </div>
      </div>
      ${goBtn}
      <div class="agent-dot" style="background:${a.online ? '#2ec46f' : '#555'}"></div>
    </div>
  `;
}

function assignAgentRoom(agentId, salle) {
  const fd = new FormData();
  fd.append('agent_id', agentId);
  fd.append('salle', salle);
  fetch('../../api.php?action=assign_agent_room', {method:'POST', body:fd})
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const agent = agents.find(a => a.id === agentId);
        if (agent) agent.salle = salle;
        refreshAgentsList();
        showToast(`Agent déplacé vers ${salle}`);
      } else {
        showToast('Erreur', data.error || 'Impossible de déplacer l\'agent');
      }
    })
    .catch(() => showToast('Erreur réseau', 'Vérifiez votre connexion'));
}

function refreshChat() {
  if (!currentRoom) return;
  const msgs = document.getElementById('chat-msgs');
  msgs.innerHTML = '<div style="text-align:center;color:var(--muted);font-size:12px;padding:20px">Chargement…</div>';

  fetch(`../../api.php?action=get_room_messages&salle=${encodeURIComponent(currentRoom.label)}`)
    .then(r => r.json())
    .then(data => {
      if (!data.success || !data.messages.length) { msgs.innerHTML = '<div style="padding:12px;color:var(--muted);font-size:12px">Aucun message</div>'; return; }
      msgs.innerHTML = data.messages.map(m => {
        const senderName = m.sender_nom || 'Agent';
        const initials = senderName.split(' ').map(s=>s.charAt(0)).join('').toUpperCase().slice(0,2);
        const agent = agents.find(a => a.nom === senderName);
        const color = agent ? agent.color : '#888';
        return `
          <div class="msg ${m.is_me ? 'me' : 'them'}">
            <div class="msg-row">
              ${m.is_me ? '' : `<div class="msg-avatar" style="background:${color}">${escHtml(initials)}</div>`}
              <div class="msg-bubble">${escHtml(m.contenu)}</div>
            </div>
            <div class="msg-meta" style="padding-left:33px">${escHtml(senderName)} · ${escHtml(m.heure)}</div>
          </div>
        `;
      }).join('');
      msgs.scrollTop = msgs.scrollHeight;
    })
    .catch(() => { msgs.innerHTML = '<div style="padding:12px;color:var(--muted);font-size:12px">Erreur de chargement</div>'; });
}

function updateHudCount() {
  const inRoom = agents.filter(a => a.salle === currentRoom?.label && a.online).length;
}

function updateWallPanel(roomId, kpis) {
  const rm = roomMeshes[roomId];
  if (!rm || !rm.pCtx) return;
  const ctx = rm.pCtx; const c = rm.panelCanvas;
  const room = ROOMS.find(r => r.id === roomId);
  if (!room) return;

  ctx.fillStyle = '#040c1a'; ctx.fillRect(0,0,512,300);
  ctx.strokeStyle = room.accent; ctx.lineWidth = 3;
  ctx.strokeRect(3,3,506,294);

  ctx.fillStyle = room.accent;
  ctx.font = 'bold 26px Sora, sans-serif';
  ctx.textAlign = 'center';
  ctx.fillText(room.icon + ' ' + room.label, 256, 48);

  ctx.font = '14px Sora, sans-serif';
  ctx.textAlign = 'left';
  let y = 90;
  kpis.forEach(k => {
    ctx.fillStyle = 'rgba(255,255,255,0.5)'; ctx.fillText(k.label, 30, y);
    ctx.fillStyle = k.color || '#fff';
    ctx.font = 'bold 18px Sora, sans-serif';
    ctx.textAlign = 'right';
    ctx.fillText(String(k.value), 490, y);
    ctx.textAlign = 'left';
    ctx.font = '14px Sora, sans-serif';

    const maxVal = 20;
    const barW = Math.min((k.value / maxVal) * 450, 450);
    ctx.fillStyle = k.color + '33';
    ctx.fillRect(30, y + 5, 450, 6);
    ctx.fillStyle = k.color;
    ctx.fillRect(30, y + 5, barW, 6);

    y += 48;
  });

  ctx.fillStyle = 'rgba(255,255,255,0.2)';
  ctx.font = '11px Sora, sans-serif'; ctx.textAlign = 'center';
  ctx.fillText('Mis à jour : ' + new Date().toLocaleTimeString('fr'), 256, 282);

  rm.panelTex.needsUpdate = true;
}

// ── Polling présence agents ──
function pollAgentPresence() {
  fetch('../../api.php?action=agents_online')
    .then(r => r.json())
    .then(onlineIds => {
      agents.forEach(a => {
        const wasOnline = a.online;
        a.online = onlineIds.includes(a.id);
        if (wasOnline !== a.online) {
          const mesh = agentMeshes[a.id];
          if (mesh) {
            mesh.children.forEach(child => {
              if (child.material) {
                child.material.transparent = true;
                child.material.opacity = a.online ? 1.0 : 0.25;
                child.material.needsUpdate = true;
              }
            });
          }
          if (a.online && !wasOnline) {
            showToast('🟢 ' + a.nom, 'Vient de se connecter', 3000);
          }
        }
      });
      const onlineCount = agents.filter(a => a.online).length;
      document.getElementById('hud-agents-count').textContent = onlineCount + ' agent(s) en ligne';
      if (sidebarOpen) refreshAgentsList();
    })
    .catch(() => {});
}

// ── Polling SOS ──
let lastSosCount = 0;
function pollSosAlerts() {
  fetch('get_sos_admin.php')
    .then(r => r.json())
    .then(data => {
      const alerts = data.alerts || [];
      const activeCount = alerts.length;
      if (activeCount > 0 && activeCount > lastSosCount) {
        const rm = roomMeshes['sinistres'];
        if (rm && rm.floor) {
          let flashCount = 0;
          const origColor = ROOMS.find(r=>r.id==='sinistres').floor;
          const flashInterval = setInterval(() => {
            if (flashCount >= 6) { clearInterval(flashInterval); rm.floor.material.color.set(origColor); return; }
            rm.floor.material.color.setHex(flashCount % 2 === 0 ? 0x5c0000 : parseInt(origColor.replace('#',''), 16));
            flashCount++;
          }, 300);
        }
        showToast('🆘 ' + activeCount + ' alerte(s) SOS !', 'Aller à la Salle Sinistres', 8000);
        if (currentRoom?.id === 'sinistres') {
          document.getElementById('hud-room-name').innerHTML =
            'Salle Sinistres <span style="background:#e63946;color:#fff;font-size:10px;padding:2px 6px;border-radius:99px;margin-left:6px">' + activeCount + ' SOS</span>';
        }
      }
      lastSosCount = activeCount;
    })
    .catch(() => {});
}

// ── Polling notifications ──
function pollNotifications() {
  fetch('../../api.php?action=notifs_admin_count')
    .then(r => r.json())
    .then(data => {
      const badge = document.getElementById('hud-agents-count');
      const onlineCount = agents.filter(a => a.online).length;
      if (data.count > 0) {
        badge.innerHTML = onlineCount + ' en ligne <span style="background:#e63946;color:#fff;border-radius:99px;padding:1px 7px;font-size:10px;margin-left:6px">' + data.count + ' notifs</span>';
      } else {
        badge.textContent = onlineCount + ' agent(s) en ligne';
      }
    })
    .catch(() => {});
}

function toggleSidebar() {
  sidebarOpen = !sidebarOpen;
  document.getElementById('sidebar').classList.toggle('open', sidebarOpen);
  document.getElementById('notif-dot').style.display = 'none';
  unreadCount = 0;
  if (sidebarOpen) { refreshAgentsList(); refreshChat(); }
}

function sendMsg() {
  const input = document.getElementById('chat-input');
  const text = input.value.trim();
  if (!text || !currentRoom) return;

  const btn = document.getElementById('btn-send');
  btn.disabled = true;

  const fd = new FormData();
  fd.append('salle', currentRoom.label);
  fd.append('contenu', text);
  fd.append('csrf_token', document.querySelector('meta[name="csrf"]')?.content || '');

  fetch('../../api.php?action=send_room_message', {method:'POST', body:fd})
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        input.value = '';
        input.style.height = 'auto';
        refreshChat();
        // Réponse IA de l'agent dans la salle
        const inRoom = agents.filter(a => a.salle === currentRoom?.label && a.online);
        if (inRoom.length > 0) {
          const responder = inRoom[Math.floor(Math.random() * inRoom.length)];
          setTimeout(() => agentAIReply(responder, text), 1200 + Math.random()*800);
        }
      }
    })
    .finally(() => { btn.disabled = false; });
}

// ── Réponse IA d'un agent (Groq) au lieu de réponses simulées ──
function agentAIReply(agent, adminMessage) {
  const key = currentRoom?.label;
  if (!key || !agent) return;
  const history = getChatHistoryForAgent(agent.nom);

  const fd = new FormData();
  fd.append('agent_name', agent.nom);
  fd.append('agent_role', agent.role);
  fd.append('salle', key);
  fd.append('message', adminMessage);
  fd.append('history', JSON.stringify(history));

  fetch('../../api.php?action=agent_ai_reply', {method:'POST', body:fd})
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        // fallback: message simple
        addAgentMessage(agent, "Bien reçu, je m'en occupe !");
        return;
      }
      addAgentMessage(agent, data.reply);
    })
    .catch(() => {
      addAgentMessage(agent, "Message reçu, merci directeur !");
    });
}

// Ajouter un message d'agent dans le chat
function addAgentMessage(agent, text) {
  const fd = new FormData();
  fd.append('salle', currentRoom.label);
  fd.append('contenu', text);
  fd.append('sender_name', agent.nom);
  fd.append('csrf_token', document.querySelector('meta[name="csrf"]')?.content || '');
  fetch('../../api.php?action=send_room_message', {method:'POST', body:fd})
    .then(r => r.json())
    .then(data => {
      if (sidebarOpen) refreshChat();
      else {
        unreadCount++;
        document.getElementById('notif-dot').style.display = 'block';
      }
      playNotifSound();
    })
    .catch(() => {});
}

// Récupérer l'historique des messages avec un agent spécifique
function getChatHistoryForAgent(agentNom) {
  return Array.from(document.querySelectorAll('#chat-msgs .msg')).map(el => {
    const bubble = el.querySelector('.msg-bubble');
    const meta = el.querySelector('.msg-meta');
    const isMe = el.classList.contains('me');
    return {
      role: isMe ? 'user' : 'assistant',
      content: bubble?.textContent || '',
      sender: meta?.textContent?.split('·')[0]?.trim() || ''
    };
  }).slice(-10);
}

// ── Salutation IA quand l'admin entre dans une salle avec agent ──
function greetAgentInRoom() {
  if (!currentRoom) return;
  const inRoom = agents.filter(a => a.salle === currentRoom.label && a.online);
  if (inRoom.length === 0) return;

  // Animation de wave des agents dans la salle
  inRoom.forEach((a, idx) => {
    const mesh = agentMeshes[a.id];
    if (!mesh) return;
    const origY = mesh.position.y;
    let waveT = 0;
    const wave = () => {
      waveT += 0.05;
      mesh.rotation.z = Math.sin(waveT * 8) * 0.15;
      mesh.position.y = origY + Math.sin(waveT * 6) * 0.08;
      if (waveT < Math.PI * 2) requestAnimationFrame(wave);
      else { mesh.rotation.z = 0; mesh.position.y = origY; }
    };
    setTimeout(() => wave(), idx * 200);
  });

  // L'agent principal (le premier) salue l'admin
  const mainAgent = inRoom[0];
  const greetings = [
    `Bonjour directeur ! Bienvenue dans ${currentRoom.label}. Comment puis-je vous aider ?`,
    `Directeur, ravi de vous voir dans ${currentRoom.label}. Tout est sous contrôle, avez-vous besoin de quelque chose ?`,
    `Salut chef ! ${currentRoom.label} est opérationnelle. Des instructions particulières ?`,
    `Bonjour monsieur ! Je suis à votre disposition dans ${currentRoom.label}.`,
  ];
  const greeting = greetings[Math.floor(Math.random() * greetings.length)];

  setTimeout(() => {
    agentAIReply(mainAgent, greeting);
  }, 800);
}

// ── Son de notification ──
function playNotifSound() {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.frequency.value = 880;
    osc.type = 'sine';
    gain.gain.setValueAtTime(0.08, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);
    osc.start(ctx.currentTime);
    osc.stop(ctx.currentTime + 0.15);
  } catch(e) {}
}

// ── Copilot IA — Assistant virtuel intelligent ──
let copilotOpen = false;
const copilotHistory = [];
function toggleCopilot() {
  copilotOpen = !copilotOpen;
  document.getElementById('copilot-panel').classList.toggle('open', copilotOpen);
  if (copilotOpen) document.getElementById('copilot-input').focus();
}

function sendCopilotMsg() {
  const input = document.getElementById('copilot-input');
  const text = input.value.trim();
  if (!text) return;
  input.value = '';

  const msgs = document.getElementById('copilot-msgs');
  msgs.innerHTML += `<div class="copilot-msg user">${escHtml(text)}</div>`;
  msgs.scrollTop = msgs.scrollHeight;

  copilotHistory.push({role:'user', content:text});

  const loading = document.getElementById('copilot-loading');
  loading.style.display = 'flex';

  fetch('../../api.php?action=agent_ai_reply', {
    method:'POST',
    body: (() => {
      const fd = new FormData();
      fd.append('agent_name', 'Copilot Protex');
      fd.append('agent_role', 'Assistant IA');
      fd.append('salle', currentRoom?.label || 'Agence Virtuelle');
      fd.append('message', text);
      fd.append('history', JSON.stringify(copilotHistory));
      return fd;
    })()
  })
  .then(r => r.json())
  .then(data => {
    loading.style.display = 'none';
    const reply = data.success ? data.reply : 'Je n\'ai pas pu traiter votre demande. Veuillez réessayer.';
    copilotHistory.push({role:'assistant', content:reply});
    msgs.innerHTML += `<div class="copilot-msg bot">${escHtml(reply)}</div>`;
    msgs.scrollTop = msgs.scrollHeight;
  })
  .catch(() => {
    loading.style.display = 'none';
    msgs.innerHTML += `<div class="copilot-msg bot">Service temporairement indisponible.</div>`;
  });
}

document.getElementById('copilot-input')?.addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendCopilotMsg(); }
});

// Auto-resize textarea
document.getElementById('chat-input').addEventListener('input', function() {
  this.style.height = 'auto';
  this.style.height = Math.min(this.scrollHeight, 80) + 'px';
});
document.getElementById('chat-input').addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMsg(); }
});

// ═══════════════════════════════════════════════════════════
// VOIX WebRTC
// ═══════════════════════════════════════════════════════════
async function toggleMic() {
  const btn = document.getElementById('btn-mic');
  const label = document.getElementById('mic-label');
  const indicators = document.getElementById('voice-indicators');

  if (!micActive) {
    try {
      micStream = await navigator.mediaDevices.getUserMedia({audio:true, video:false});
      micActive = true;
      btn.classList.add('active');
      label.textContent = 'Micro actif — parlez';
      indicators.style.display = 'flex';
      showToast('🎤 Micro activé', 'Vous parlez dans ' + (currentRoom?.label || 'la salle'));

      // Analyse audio (visualisation)
      const ctx = new AudioContext();
      const source = ctx.createMediaStreamSource(micStream);
      const analyser = ctx.createAnalyser();
      analyser.fftSize = 256;
      source.connect(analyser);
      const data = new Uint8Array(analyser.frequencyBinCount);
      const bars = document.querySelectorAll('.vi-bar');
      function updateViz() {
        if (!micActive) return;
        analyser.getByteFrequencyData(data);
        bars.forEach((b, i) => {
          const v = data[i * 6 + 10] / 255;
          b.style.height = (4 + v * 16) + 'px';
        });
        requestAnimationFrame(updateViz);
      }
      updateViz();
      voiceShareStream(micStream);

    } catch(e) {
      showToast('⚠️ Micro', 'Accès refusé ou non disponible');
    }
  } else {
    voiceStopStream();
    if (recognition) { try { recognition.stop(); } catch(e) {} recognition = null; }
    if (micStream) micStream.getTracks().forEach(t => t.stop());
    micActive = false;
    micStream = null;
    btn.classList.remove('active');
    label.textContent = 'Parler dans la salle';
    indicators.style.display = 'none';
    showToast('🔇 Micro désactivé', '');
  }
}

// ── Joystick tactile ──
(function setupJoystick() {
  if (!('ontouchstart' in window)) return;
  document.getElementById('joy-zone').style.display    = 'block';
  document.getElementById('btn-space-mobile').style.display = 'block';
  document.getElementById('controls-hint').style.display   = 'none';

  let joyActive = false, joyStartX = 0, joyStartY = 0;
  let joyDX = 0, joyDZ = 0;
  const zone  = document.getElementById('joy-zone');
  const knob  = document.getElementById('joy-knob');
  const MAX_R = 33;

  zone.addEventListener('touchstart', e => {
    e.preventDefault();
    joyActive = true;
    const t = e.touches[0];
    const r = zone.getBoundingClientRect();
    joyStartX = r.left + r.width/2;
    joyStartY = r.top  + r.height/2;
  }, {passive:false});

  zone.addEventListener('touchmove', e => {
    e.preventDefault();
    if (!joyActive) return;
    const t = e.touches[0];
    let dx = t.clientX - joyStartX;
    let dz = t.clientY - joyStartY;
    const dist = Math.sqrt(dx*dx + dz*dz);
    if (dist > MAX_R) { dx = dx/dist*MAX_R; dz = dz/dist*MAX_R; }
    knob.style.transform = `translate(calc(-50% + ${dx}px), calc(-50% + ${dz}px))`;
    joyDX = dx / MAX_R;
    joyDZ = dz / MAX_R;
  }, {passive:false});

  zone.addEventListener('touchend', () => {
    joyActive = false; joyDX = 0; joyDZ = 0;
    knob.style.transform = 'translate(-50%,-50%)';
  });

  window._joyDX = () => joyDX;
  window._joyDZ = () => joyDZ;
})();

// ═══════════════════════════════════════════════════════════
// COMMAND PALETTE (Ctrl+K)
// ═══════════════════════════════════════════════════════════
function showPalette() {
  document.getElementById('cmd-palette').classList.add('open');
  const input = document.getElementById('cmd-input');
  input.value = '';
  input.focus();
  renderPalette('');
}

function hidePalette() {
  document.getElementById('cmd-palette').classList.remove('open');
}

function renderPalette(query) {
  const q = query.toLowerCase().trim();
  const list = document.getElementById('cmd-list');

  let items = ROOMS.filter(r => !q || r.label.toLowerCase().includes(q) || r.id.includes(q))
    .map(r => ({
      icon: r.icon,
      label: r.label,
      desc: r.desc,
      action: () => { hidePalette(); teleportToRoom(r.id); }
    }));

  // Ajouter Copilot dans la palette
  if (!q || 'copilot'.includes(q) || 'ia'.includes(q) || 'assistant'.includes(q)) {
    items.push({
      icon: '🤖',
      label: 'Copilot IA',
      desc: 'Assistant virtuel intelligent',
      shortcut: 'Ctrl+Shift+K',
      action: () => { hidePalette(); toggleCopilot(); }
    });
  }

  if (!items.length) {
    list.innerHTML = '<div style="padding:20px;text-align:center;color:var(--muted);font-size:13px">Aucun résultat trouvé</div>';
    return;
  }

  list.innerHTML = items.map((item, i) => `
    <div class="cmd-item" data-idx="${i}" onclick="(function(){ hidePalette(); ${item.action.toString()} })()">
      <span class="cmd-icon">${item.icon}</span>
      <span class="cmd-label">${escHtml(item.label)}</span>
      <span class="cmd-desc">${escHtml(item.desc)}</span>
      ${item.shortcut ? `<span class="cmd-shortcut">${item.shortcut}</span>` : ''}
    </div>
  `).join('');

  list.querySelector('.cmd-item')?.classList.add('focused');
}

function teleportToRoom(roomId) {
  const room = ROOMS.find(r => r.id === roomId);
  if (!room) return;
  const w3 = room.gw * 1.5, h3 = room.gh * 1.5;
  adminPos.x = room.gx * 1.5 - 12 + w3/2;
  adminPos.z = room.gy * 1.5 - 11 + h3/2;
  adminMesh.position.x = adminPos.x;
  adminMesh.position.z = adminPos.z;
  enterRoomById(roomId);
}

// Ctrl+K → Palette, Ctrl+Shift+K → Copilot
document.addEventListener('keydown', e => {
  if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'k') {
    e.preventDefault();
    toggleCopilot();
    return;
  }
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault();
    showPalette();
  }
  if (e.key === 'Escape') {
    if (document.getElementById('cmd-palette').classList.contains('open')) hidePalette();
    else if (copilotOpen) toggleCopilot();
  }
});

// Filtre palette en temps réel
document.getElementById('cmd-input')?.addEventListener('input', function() {
  renderPalette(this.value);
});

// Navigation clavier palette
document.getElementById('cmd-input')?.addEventListener('keydown', function(e) {
  const items = document.querySelectorAll('.cmd-item');
  const focused = document.querySelector('.cmd-item.focused');
  let idx = focused ? parseInt(focused.dataset.idx) : -1;

  if (e.key === 'ArrowDown') { e.preventDefault(); idx = Math.min(idx + 1, items.length - 1); }
  else if (e.key === 'ArrowUp') { e.preventDefault(); idx = Math.max(idx - 1, 0); }
  else if (e.key === 'Enter' && focused) { e.preventDefault(); focused.click(); return; }
  else return;

  items.forEach(el => el.classList.remove('focused'));
  items[idx]?.classList.add('focused');
  items[idx]?.scrollIntoView({block:'nearest'});
});

// ═══════════════════════════════════════════════════════════
// ACTIVITÉ SALLES (glow sur les salles avec dossiers)
// ═══════════════════════════════════════════════════════════
const roomActivity = {};

function pollRoomActivity() {
  fetch('../../api.php?action=room_kpis&salle=all')
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;
      (data.kpis || []).forEach(k => {
        const id = k.salle_id;
        if (!id) return;
        const was = roomActivity[id];
        roomActivity[id] = k.active > 0;
        const rm = roomMeshes[id];
        if (rm && rm.edgeMat && was !== roomActivity[id]) {
          rm.edgeMat.emissiveIntensity = roomActivity[id] ? 1.8 : 0.8;
          if (roomActivity[id]) {
            rm.floor.material.emissive = new THREE.Color(rm.room.accent);
            rm.floor.material.emissiveIntensity = 0.15;
          } else {
            rm.floor.material.emissive = null;
            rm.floor.material.emissiveIntensity = 0;
          }
          rm.floor.material.needsUpdate = true;
        }
      });
    })
    .catch(() => {});
}

// ═══════════════════════════════════════════════════════════
// UTILS
// ═══════════════════════════════════════════════════════════
function closePopup() { document.getElementById('room-popup').style.display='none'; }
function enterRoom() { closePopup(); if(pendingRoom) enterRoomById(pendingRoom.id); }

function showToast(title, desc, duration=3000) {
  const el = document.createElement('div');
  el.className = 'toast-item';
  el.innerHTML = `<div><div style="font-weight:600;font-size:13px">${escHtml(title)}</div>${desc?`<div style="font-size:12px;color:var(--muted);margin-top:2px">${escHtml(desc)}</div>`:''}</div>`;
  document.getElementById('toasts').prepend(el);
  setTimeout(()=>{ el.classList.add('out'); setTimeout(()=>el.remove(), 300); }, duration);
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function onResize() {
  if (!renderer) return; // initEngine pas encore appelé
  const W = CVS.parentElement?.clientWidth  || window.innerWidth;
  const H = window.innerHeight - 52;
  CVS.style.width  = W + 'px';
  CVS.style.height = H + 'px';
  renderer.setSize(W, H, false);
  const aspect = W/H, d = 22;
  camera.left   = -d * aspect;
  camera.right  =  d * aspect;
  camera.top    =  d;
  camera.bottom = -d;
  camera.updateProjectionMatrix();
}
window.addEventListener('load', onResize);

// Focus sur le canvas pour les touches
CVS.setAttribute('tabindex','0');
CVS.focus();
window.addEventListener('click', () => CVS.focus());

// ═══════════════════════════════════════════════════════════
// VOICE CALL (PeerJS) — Appel vocal temps réel entre users
// ═══════════════════════════════════════════════════════════
let voicePeer = null;
let voiceCalls = {};
let voicePeersInRoom = {};
let voicePollTimer = null;

function initVoice() {
  const uid = <?= json_encode($adminId) ?>;
  if (!uid || typeof Peer === 'undefined') return;
  try {
    voicePeer = new Peer('u' + uid + '_' + Math.random().toString(36).slice(2,6), {
      config: {
        iceServers: [
          { urls: 'stun:stun.l.google.com:19302' },
          { urls: 'stun:stun1.l.google.com:19302' }
        ]
      }
    });
    voicePeer.on('call', call => {
      call.answer(micActive && micStream ? micStream : null);
      call.on('stream', stream => playRemoteStream(stream, call.peer));
      call.on('close', () => cleanupCall(call.peer));
      voiceCalls[call.peer] = call;
    });
    voicePeer.on('error', () => {});
  } catch(e) {}
}

function voiceJoinRoom(salle) {
  if (!voicePeer || !salle) return;
  const fd = new FormData(); fd.append('salle', salle); fd.append('peer_id', voicePeer.id);
  fetch('../../api.php?action=voice_join', {method:'POST', body:fd});
  voicePollRoom(salle);
  if (voicePollTimer) clearInterval(voicePollTimer);
  voicePollTimer = setInterval(() => voicePollRoom(salle), 5000);
}

function voicePollRoom(salle) {
  fetch('../../api.php?action=voice_list&salle=' + encodeURIComponent(salle))
    .then(r => r.json()).then(data => {
      if (!data.success) return;
      data.peers.forEach(p => {
        if (voiceCalls[p.peer_id] || voicePeersInRoom[p.peer_id]) return;
        voicePeersInRoom[p.peer_id] = p;
        if (micActive && micStream) {
          const call = voicePeer.call(p.peer_id, micStream);
          setupCall(call);
        }
      });
    });
}

function setupCall(call) {
  call.on('stream', stream => playRemoteStream(stream, call.peer));
  call.on('close', () => cleanupCall(call.peer));
  call.on('error', () => cleanupCall(call.peer));
  voiceCalls[call.peer] = call;
}

function playRemoteStream(stream, peerId) {
  let audio = document.getElementById('voice-' + peerId);
  if (!audio) {
    audio = document.createElement('audio');
    audio.id = 'voice-' + peerId;
    audio.autoplay = true;
    audio.playsInline = true;
    audio.style.display = 'none';
    document.body.appendChild(audio);
  }
  audio.srcObject = stream;
  audio.volume = 1;
  audio.play().catch(() => {
    // Autoplay bloqué : on débloque au prochain clic
    const unlock = () => { audio.play(); document.removeEventListener('click', unlock); };
    document.addEventListener('click', unlock);
  });
}

function cleanupCall(peerId) {
  const el = document.getElementById('voice-' + peerId);
  if (el) el.remove();
  delete voiceCalls[peerId];
  delete voicePeersInRoom[peerId];
}

function voiceLeaveRoom() {
  if (voicePollTimer) { clearInterval(voicePollTimer); voicePollTimer = null; }
  Object.keys(voiceCalls).forEach(k => { try { voiceCalls[k].close(); } catch(e) {} });
  voiceCalls = {}; voicePeersInRoom = {};
  const fd = new FormData();
  if (currentRoom) fd.append('salle', currentRoom.label);
  fetch('../../api.php?action=voice_leave', {method:'POST', body: fd});
}

function voiceShareStream(stream) {
  Object.keys(voicePeersInRoom).forEach(pid => {
    if (voiceCalls[pid]) {
      // Remplacer le track audio sur la connexion existante
      try {
        const pc = voiceCalls[pid].peerConnection;
        if (pc) {
          const sender = pc.getSenders().find(s => s.track && s.track.kind === 'audio');
          if (sender) sender.replaceTrack(stream.getAudioTracks()[0]);
        }
      } catch(e) {}
    } else {
      try { const call = voicePeer.call(pid, stream); setupCall(call); } catch(e) {}
    }
  });
}

function voiceStopStream() {
  // On garde les connexions mais on ne partage plus l'audio
}

initVoice();
// Widget vocal (complément au voice call 3D)
let vwPeer = null;
let vwCalls = {};
let vwOpen = false;
let vwTimer = null;
function vwInit() {
  if (typeof Peer === 'undefined') return;
  try {
    vwPeer = new Peer('vw_' + <?= json_encode($adminId) ?> + '_' + Math.random().toString(36).slice(2,6));
    vwPeer.on('call', call => {
      call.answer(micActive && micStream ? micStream : null);
      call.on('stream', stream => {
        let a = document.getElementById('vw-a-' + call.peer);
        if (!a) { a = document.createElement('audio'); a.id = 'vw-a-' + call.peer; a.autoplay = true; a.playsInline = true; document.body.appendChild(a); }
        a.srcObject = stream; a.play().catch(() => {});
      });
      call.on('close', () => { const e = document.getElementById('vw-a-' + call.peer); if(e) e.remove(); delete vwCalls[call.peer]; });
      vwCalls[call.peer] = call;
    });
    vwPeer.on('error', () => {});
    vwPoll();
    vwTimer = setInterval(vwPoll, 5000);
  } catch(e) {}
}
function vwToggle() { vwOpen = !vwOpen; document.getElementById('vw-panel').classList.toggle('open', vwOpen); }
function vwPoll() {
  const salle = currentRoom && currentRoom.id !== 'entree' ? currentRoom.label : '__all__';
  fetch('../../api.php?action=voice_list&salle=' + encodeURIComponent(salle)).then(r=>r.json()).then(d => {
    if (!d.success) return;
    const b = document.getElementById('vw-body');
    if (!d.peers || !d.peers.length) { b.innerHTML = '<div class="vw-e">Aucun agent disponible</div>'; return; }
    b.innerHTML = d.peers.map(p => {
      const rl = p.salle === '__widget__' ? '📱' : '🏢 ' + p.salle;
      return '<div class="vw-it"><div class="vn">' + p.prenom + ' ' + p.nom + '</div><div class="vr">' + rl + '</div>' +
        '<button class="vc' + (vwCalls[p.peer_id]?' on':'') + '" onclick="vwCall(\'' + p.peer_id + '\')">' + (vwCalls[p.peer_id]?'🔊':'📞') + '</button></div>';
    }).join('');
  });
}
async function vwCall(pid) {
  if (vwCalls[pid]) { try { vwCalls[pid].close(); } catch(e) {} delete vwCalls[pid]; return; }
  if (!micActive || !micStream) {
    try {
      micStream = await navigator.mediaDevices.getUserMedia({audio:true});
      micActive = true;
      document.getElementById('vw-mic-btn').style.background = 'var(--orange)';
      document.getElementById('vw-mic-btn').style.color = '#fff';
      document.getElementById('vw-mic-label').textContent = 'Micro actif';
      document.getElementById('vw-status').textContent = '🎤';
    } catch(e) { showToast('⚠️ Accès micro refusé', ''); return; }
  }
  const call = vwPeer.call(pid, micStream);
  call.on('stream', stream => {
    let a = document.getElementById('vw-a-' + call.peer);
    if (!a) { a = document.createElement('audio'); a.id = 'vw-a-' + call.peer; a.autoplay = true; a.playsInline = true; document.body.appendChild(a); }
    a.srcObject = stream; a.play().catch(() => {});
  });
  call.on('close', () => { const e = document.getElementById('vw-a-' + call.peer); if(e) e.remove(); delete vwCalls[call.peer]; });
  vwCalls[call.peer] = call;
  showToast('📞 Appel en cours', '');
}
async function vwToggleMic() {
  const btn = document.getElementById('vw-mic-btn');
  const lbl = document.getElementById('vw-mic-label');
  if (!micActive || !micStream) {
    try {
      micStream = await navigator.mediaDevices.getUserMedia({audio:true});
      micActive = true;
      btn.style.background = 'var(--orange)'; btn.style.color = '#fff';
      lbl.textContent = 'Micro actif';
      document.getElementById('vw-status').textContent = '🎤';
    } catch(e) { showToast('⚠️ Accès micro refusé', ''); }
    return;
  }
  if (micStream) micStream.getTracks().forEach(t => t.stop());
  micActive = false; micStream = null;
  btn.style.background = 'rgba(255,255,255,0.08)'; btn.style.color = 'rgba(255,255,255,0.4)';
  lbl.textContent = 'Micro désactivé';
  document.getElementById('vw-status').textContent = '';
}
vwInit();
window.addEventListener('beforeunload', () => { voiceLeaveRoom(); });
initEngine();
</script>

<!-- Joystick mobile -->
<div id="joy-zone" style="display:none;position:fixed;bottom:90px;left:20px;width:110px;height:110px;border-radius:50%;z-index:85;background:rgba(255,255,255,0.06);border:1.5px solid rgba(255,107,26,0.3)">
  <div id="joy-knob" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:44px;height:44px;border-radius:50%;background:rgba(255,107,26,0.5);border:2px solid #FF6B1A;pointer-events:none;transition:transform .05s"></div>
</div>
<button id="btn-space-mobile" style="display:none;position:fixed;bottom:90px;right:90px;width:56px;height:56px;border-radius:50%;background:rgba(0,180,216,0.2);border:1.5px solid rgba(0,180,216,0.4);color:#00b4d8;font-size:20px;z-index:85;cursor:pointer" onclick="toggleSidebar()">💬</button>

<!-- COMMAND PALETTE -->
<div id="cmd-palette" onclick="if(event.target===this)hidePalette()">
  <div id="cmd-palette-wrap">
    <input id="cmd-input" type="text" placeholder="Chercher une salle ou une action…" autofocus>
    <div id="cmd-list"></div>
  </div>
</div>

<!-- COPILOT PANEL -->
<div id="copilot-panel">
  <div id="copilot-head">
    <div class="cp-logo">P</div>
    <div id="copilot-title">Copilot Protex</div>
    <div id="copilot-status">En ligne</div>
    <button id="copilot-close" onclick="toggleCopilot()">✕</button>
  </div>
  <div id="copilot-msgs">
    <div class="copilot-msg bot">Bonjour ! Je suis le Copilot Protex, votre assistant IA. Posez-moi des questions sur l'agence, les salles, les statistiques, ou demandez-moi de l'aide.</div>
  </div>
  <div id="copilot-loading"><span></span><span></span><span></span></div>
  <div id="copilot-footer">
    <textarea id="copilot-input" rows="1" placeholder="Demander au Copilot…"></textarea>
    <button onclick="sendCopilotMsg()" style="width:36px;height:36px;border-radius:9px;border:none;background:var(--orange);color:#fff;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s" onmouseover="this.style.background='var(--orange2)'" onmouseout="this.style.background='var(--orange)'">➤</button>
  </div>
</div>
</body>
</html>
