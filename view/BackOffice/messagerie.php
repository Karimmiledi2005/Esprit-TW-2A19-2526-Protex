<?php
/**
 * view/BackOffice/messagerie.php
 * Messagerie interne BackOffice — Protex 2026
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();

if (!defined('BASE_URL')) define('BASE_URL', (defined('BASE_URL') ? BASE_URL : ''));
$base = (defined('BASE_URL') ? BASE_URL : '');

// Si la vue est appelée directement (sans passer par le contrôleur), rediriger
if (!isset($conversations) && !isset($user)) {
    header('Location: ' . $base . '/controller/MessagerieController.php');
    exit;
}

// Defaults pour éviter les warnings (si chargé partiellement)
$conversations = $conversations ?? [];
$users         = $users         ?? [];
$messages      = $messages      ?? [];
$mentions      = $mentions      ?? [];
$currentConv   = $currentConv   ?? null;
$convId        = $convId        ?? 0;
$user          = $user          ?? ['id_user'=>0, 'prenom'=>'', 'nom'=>'', 'role'=>''];

function mE($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function mDate($d): string { if (!$d) return ''; try { return (new DateTime($d))->format('H:i'); } catch (Exception $e) { return ''; } }
function mDateFull($d): string { if (!$d) return ''; try { return (new DateTime($d))->format('d/m/Y H:i'); } catch (Exception $e) { return ''; } }
function mInitiales($u): string { return strtoupper(mb_substr($u['prenom'] ?? 'A', 0, 1) . mb_substr($u['nom'] ?? 'U', 0, 1)); }

$roleColors = ['admin' => '#FF6B1A', 'agent' => '#0dcaf0', 'client' => '#64748b'];
$roleLabels = ['admin' => 'Admin', 'agent' => 'Agent', 'client' => 'Client'];

function isOnline(array $u): bool {
    if (empty($u['last_login'])) return false;
    try {
        $last = new DateTime($u['last_login']);
        $now = new DateTime();
        return $now->getTimestamp() - $last->getTimestamp() < 900;
    } catch (Exception $e) { return false; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Messagerie — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/layout.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/admin-users.css">
    <style>
        .msg-layout { display: grid; grid-template-columns: 320px 1fr; height: calc(100vh - 100px); background: rgba(255,255,255,.02); border-radius: 22px; border: 1px solid rgba(255,255,255,.08); overflow: hidden; }
        @media (max-width: 900px) { .msg-layout { grid-template-columns: 1fr; } .msg-sidebar { display: none; } }
        @media (max-width: 900px) { .msg-layout { grid-template-columns: 1fr; } .msg-sidebar { display: none; } }

        .msg-sidebar { border-right: 1px solid rgba(255,255,255,.08); display: flex; flex-direction: column; background: rgba(255,255,255,.02); }
        .msg-sidebar-header { padding: 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,.06); }
        .msg-sidebar-header h2 { font-size: 18px; font-weight: 800; color: #fff; margin: 0; }
        .btn-new-conv { padding: 8px 14px; border-radius: 10px; background: #FF6B1A; color: #fff; border: none; cursor: pointer; font-size: 13px; font-weight: 700; }
        .btn-new-conv:hover { background: #e55d16; }
        .msg-search { padding: 12px 20px; }
        .msg-search input {
            width: 100%; padding: 10px 14px; border-radius: 12px;
            border: 1px solid rgba(255,255,255,.1); background: rgba(255,255,255,.04);
            color: #fff; font-size: 13px; outline: none;
        }
        .msg-search input:focus { border-color: #FF6B1A; }

        .conv-list { flex: 1; overflow-y: auto; }
        .conv-item {
            padding: 14px 20px; cursor: pointer; display: flex; align-items: center; gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,.04); transition: background .15s;
        }
        .conv-item:hover { background: rgba(255,255,255,.04); }
        .conv-item.active { background: rgba(255,107,26,.08); border-left: 3px solid #FF6B1A; }
        .conv-avatar { width: 42px; height: 42px; border-radius: 50%; display: grid; place-items: center; font-size: 14px; font-weight: 800; color: #fff; flex-shrink: 0; position: relative; }
        .conv-avatar.online::after { content: ''; position: absolute; bottom: 1px; right: 1px; width: 10px; height: 10px; border-radius: 50%; background: #00d68f; border: 2px solid rgba(15,20,30,.9); }
        .conv-info { flex: 1; min-width: 0; }
        .conv-name { font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .conv-last { font-size: 12px; color: rgba(255,255,255,.5); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .conv-meta { text-align: right; flex-shrink: 0; }
        .conv-time { font-size: 11px; color: rgba(255,255,255,.4); margin-bottom: 4px; }
        .conv-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 20px; height: 20px; border-radius: 999px; background: #FF6B1A; color: #fff; font-size: 11px; font-weight: 700; padding: 0 6px; }

        .msg-chat { display: flex; flex-direction: column; height: calc(100vh - 100px); overflow: hidden; }
        .msg-chat-header { padding: 18px 24px; border-bottom: 1px solid rgba(255,255,255,.06); display: flex; align-items: center; gap: 14px; background: rgba(255,255,255,.02); }
        .chat-header-info h3 { font-size: 16px; font-weight: 800; color: #fff; margin: 0; }
        .chat-header-info span { font-size: 12px; color: rgba(255,255,255,.5); }
        .chat-header-actions { margin-left: auto; display: flex; gap: 8px; }
        .chat-header-btn { padding: 8px 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,.1); background: rgba(255,255,255,.04); color: rgba(255,255,255,.5); cursor: pointer; font-size: 14px; }
        .chat-header-btn:hover { color: #fff; border-color: rgba(255,255,255,.2); }

        .msg-empty { flex: 1; display: flex; align-items: center; justify-content: center; flex-direction: column; color: rgba(255,255,255,.5); }
        .msg-empty i { font-size: 64px; margin-bottom: 16px; opacity: .3; }
        .msg-empty p { font-size: 16px; }

        .msg-messages { flex: 1; overflow-y: auto; padding: 24px; display: flex; flex-direction: column; gap: 8px; min-height: 0; }

        .msg-bubble { max-width: 70%; padding: 12px 16px; border-radius: 18px; font-size: 14px; line-height: 1.5; position: relative; word-break: break-word; }
        .msg-bubble.sent { align-self: flex-end; background: linear-gradient(135deg, #FF6B1A, #e55d16); color: #fff; border-bottom-right-radius: 6px; }
        .msg-bubble.received { align-self: flex-start; background: rgba(255,255,255,.08); color: #fff; border-bottom-left-radius: 6px; }
        .msg-bubble.system { align-self: center; background: transparent; color: rgba(255,255,255,.5); font-size: 12px; text-align: center; max-width: 100%; padding: 8px; border-radius: 8px; }
        .msg-bubble .sender { font-size: 11px; font-weight: 700; margin-bottom: 4px; opacity: .8; }
        .msg-bubble .time { font-size: 10px; opacity: .6; margin-top: 4px; text-align: right; }

        .msg-bubble .msg-image { max-width: 280px; border-radius: 12px; margin-top: 6px; cursor: pointer; display: block; }
        .msg-bubble .msg-image:hover { opacity: .9; }

        .msg-bubble .msg-audio { display: flex; align-items: center; gap: 8px; margin-top: 6px; }
        .msg-bubble .msg-audio audio { height: 32px; max-width: 240px; border-radius: 16px; }
        .msg-bubble .audio-badge { background: rgba(255,255,255,.15); padding: 4px 10px; border-radius: 8px; font-size: 12px; display: flex; align-items: center; gap: 6px; }

        .msg-input-wrap { border-top: 1px solid rgba(255,255,255,.08); background: rgba(255,255,255,.02); flex-shrink: 0; }
        .msg-input-toolbar { display: flex; align-items: center; gap: 4px; padding: 8px 16px 0; }
        .msg-input-toolbar button { width: 36px; height: 36px; border-radius: 10px; border: none; background: transparent; color: rgba(255,255,255,.5); cursor: pointer; font-size: 18px; display: grid; place-items: center; transition: all .15s; }
        .msg-input-toolbar button:hover { background: rgba(255,255,255,.08); color: #fff; }
        .msg-input-toolbar button.recording { background: rgba(230,57,70,.2); color: #e63946; animation: pulse-rec 1s infinite; }
        @keyframes pulse-rec { 0%,100% { transform: scale(1); } 50% { transform: scale(1.1); } }

        .msg-input-row { display: flex; gap: 10px; align-items: flex-end; padding: 8px 16px 12px; }
        .msg-input-row textarea {
            flex: 1; padding: 12px 16px; border-radius: 14px;
            border: 1px solid rgba(255,255,255,.1); background: rgba(255,255,255,.06);
            color: #f5f5f4; font-size: 14px; font-family: 'DM Sans', sans-serif;
            outline: none; resize: none; min-height: 44px; line-height: 1.5;
        }
        .msg-input-row textarea:focus { border-color: #FF6B1A; box-shadow: 0 0 0 3px rgba(255,107,26,.15); }
        .msg-input-row textarea::placeholder { color: rgba(255,255,255,.35); }
        .btn-send {
            width: 44px; height: 44px; border-radius: 14px; flex-shrink: 0;
            background: linear-gradient(135deg, #FF6B1A, #e55d16);
            color: #fff; border: none; cursor: pointer; font-size: 18px;
            display: grid; place-items: center;
            transition: transform .2s, box-shadow .2s;
        }
        .btn-send:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255,107,26,.35); }

        .emoji-picker {
            position: absolute; bottom: 80px; left: 16px; width: 320px; max-height: 300px;
            background: #1a1f2e; border: 1px solid rgba(255,255,255,.1); border-radius: 16px;
            padding: 12px; display: none; z-index: 200; box-shadow: 0 12px 40px rgba(0,0,0,.5);
            overflow-y: auto;
        }
        .emoji-picker.show { display: block; }
        .emoji-picker .emoji-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 4px; }
        .emoji-picker .emoji-btn { width: 34px; height: 34px; border-radius: 8px; border: none; background: transparent; font-size: 20px; cursor: pointer; display: grid; place-items: center; transition: background .15s; }
        .emoji-picker .emoji-btn:hover { background: rgba(255,255,255,.1); }

        .mention-list { position: absolute; bottom: 80px; left: 16px; background: #1a1f2e; border: 1px solid rgba(255,255,255,.1); border-radius: 14px; padding: 8px; display: none; z-index: 200; width: 240px; box-shadow: 0 12px 40px rgba(0,0,0,.4); }
        .mention-list.show { display: block; }
        .mention-item { padding: 8px 12px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 10px; font-size: 13px; color: #fff; }
        .mention-item:hover { background: rgba(255,107,26,.12); }
        .mention-item .m-avatar { width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center; font-size: 11px; font-weight: 800; color: #fff; background: rgba(255,255,255,.1); }

        .btn-call-user { width: 28px; height: 28px; border-radius: 8px; background: rgba(46,196,182,.12); border: 1px solid rgba(46,196,182,.25); font-size: 14px; cursor: pointer; display: grid; place-items: center; flex-shrink: 0; color: #2ec4b6; }
        .btn-call-user:hover { background: rgba(46,196,182,.25); }

        .btn-call-header { padding: 8px 14px; border-radius: 10px; border: 1px solid rgba(46,196,182,.3); background: rgba(46,196,182,.1); color: #2ec4b6; cursor: pointer; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
        .btn-call-header:hover { background: rgba(46,196,182,.2); }

        .call-banner { background: linear-gradient(135deg, rgba(46,196,182,.12), rgba(46,196,182,.04)); border: 1px solid rgba(46,196,182,.2); border-radius: 12px; padding: 10px 16px; display: none; align-items: center; gap: 10px; margin: 0 24px 8px; }
        .call-banner.show { display: flex; }
        .call-banner .ring { animation: ring 1s ease-in-out infinite; }
        @keyframes ring { 0%,100% { transform: rotate(0); } 10%,30% { transform: rotate(-15deg); } 20%,40% { transform: rotate(15deg); } 50% { transform: rotate(0); } }

        .mentions-panel { width: 280px; border-left: 1px solid rgba(255,255,255,.08); background: rgba(255,255,255,.02); overflow-y: auto; }
        .mentions-panel h3 { padding: 18px 20px; font-size: 15px; font-weight: 800; color: #fff; border-bottom: 1px solid rgba(255,255,255,.06); margin: 0; }
        .mention-item-full { padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,.04); }
        .mention-item-full .from { font-size: 12px; color: rgba(255,255,255,.5); margin-bottom: 4px; }
        .mention-item-full .from strong { color: #fff; }
        .mention-item-full .content { font-size: 13px; color: #fff; margin-bottom: 6px; line-height: 1.4; }
        .mention-item-full .time { font-size: 11px; color: rgba(255,255,255,.4); }
        .mention-item-full .btn-resolve { margin-top: 6px; padding: 4px 10px; border-radius: 6px; background: rgba(0,214,143,.15); border: 1px solid rgba(0,214,143,.3); color: #00d68f; font-size: 11px; font-weight: 700; cursor: pointer; }

        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.6); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-overlay.show { display: flex; }
        .modal-card { background: #1a1f2e; border: 1px solid rgba(255,255,255,.1); border-radius: 20px; padding: 28px; max-width: 440px; width: 100%; }
        .modal-card h3 { font-size: 18px; font-weight: 800; color: #fff; margin-bottom: 16px; }
        .modal-card label { display: block; font-size: 12px; color: rgba(255,255,255,.5); font-weight: 700; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .06em; }
        .modal-card input, .modal-card select {
            width: 100%; padding: 10px 14px; border-radius: 10px;
            border: 1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.04); color: #fff; font-size: 14px; margin-bottom: 14px; outline: none;
        }
        .modal-card input:focus, .modal-card select:focus { border-color: #FF6B1A; }
        .user-checkboxes { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; max-height: 200px; overflow-y: auto; }
        .user-checkbox { padding: 8px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,.08); background: rgba(255,255,255,.03); cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 13px; color: #fff; }
        .user-checkbox:hover { border-color: #FF6B1A; }
        .user-checkbox input { margin: 0; width: auto; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 8px; }
        .btn-cancel { padding: 10px 20px; border-radius: 10px; border: 1px solid rgba(255,255,255,.15); background: transparent; color: #fff; cursor: pointer; font-size: 13px; font-weight: 700; }
        .btn-create { padding: 10px 20px; border-radius: 10px; background: #FF6B1A; color: #fff; border: none; cursor: pointer; font-size: 13px; font-weight: 700; }

        .img-preview-modal { position: fixed; inset: 0; background: rgba(0,0,0,.85); z-index: 2000; display: none; align-items: center; justify-content: center; cursor: zoom-out; }
        .img-preview-modal.show { display: flex; }
        .img-preview-modal img { max-width: 90vw; max-height: 90vh; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,.5); }

        .conv-list::-webkit-scrollbar, .msg-messages::-webkit-scrollbar, .emoji-picker::-webkit-scrollbar { width: 5px; }
        .conv-list::-webkit-scrollbar-thumb, .msg-messages::-webkit-scrollbar-thumb, .emoji-picker::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 3px; }

        .image-preview-bar { display: none; padding: 8px 16px; border-top: 1px solid rgba(255,255,255,.06); background: rgba(255,255,255,.02); }
        .image-preview-bar.show { display: flex; align-items: center; gap: 10px; }
        .image-preview-bar img { height: 60px; border-radius: 8px; border: 1px solid rgba(255,255,255,.1); }
        .image-preview-bar .remove-preview { padding: 6px 10px; border-radius: 8px; background: rgba(230,57,70,.15); border: 1px solid rgba(230,57,70,.3); color: #e63946; font-size: 11px; font-weight: 700; cursor: pointer; }
        .image-preview-bar .img-name { font-size: 12px; color: rgba(255,255,255,.5); }
    </style>
</head>
<body>
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="layout">
    <?php include __DIR__ . '/assets/includes/sidebar.php'; ?>

    <main class="main">
        <div class="topbar">
            <h1 class="topbar-title">💬 Messagerie Interne</h1>
        </div>
        <div class="content" style="padding:0;">
            <div class="msg-layout">
                <!-- SIDEBAR -->
                <div class="msg-sidebar">
                    <div class="msg-sidebar-header">
                        <h2>Conversations</h2>
                        <button class="btn-new-conv" onclick="openNewConvModal()"><i class="bi bi-plus"></i></button>
                    </div>
                    <div class="msg-search">
                        <input type="text" id="convSearch" placeholder="🔍 Chercher..." oninput="filterConvs()">
                    </div>
                    <div class="conv-list" id="convList">
                        <?php if (empty($conversations)): ?>
                            <div style="padding:30px 20px;text-align:center;color:rgba(255,255,255,.5);font-size:13px;">
                                <i class="bi bi-chat-dots" style="font-size:32px;display:block;margin-bottom:10px;opacity:.4;"></i>
                                Aucune conversation.<br>Cliquez sur un utilisateur pour commencer.
                            </div>
                        <?php endif; ?>
                        <?php foreach ($conversations as $c): ?>
                            <?php
                            $avatarColor = 'rgba(255,107,26,.2)';
                            $nom = $c['type'] === 'prive' ? ($c['autre_nom'] ?? 'Utilisateur') : ($c['nom'] ?? 'Groupe');
                            $init = strtoupper(mb_substr($nom, 0, 1));
                            $nonLus = (int)($c['non_lus'] ?? 0);
                            $isActive = ($convId && (int)$c['id_conversation'] === (int)$convId);
                            $lastMsg = $c['dernier_message'] ?? '';
                            $lastMsgDate = $c['dernier_message_date'] ?? '';
                            ?>
                            <div class="conv-item <?= $isActive ? 'active' : '' ?>" onclick="openConv(<?= (int)$c['id_conversation'] ?>)" data-name="<?= strtolower(mE($nom)) ?>">
                                <div class="conv-avatar" style="background:<?= $avatarColor ?>"><?= mE($init) ?></div>
                                <div class="conv-info">
                                    <div class="conv-name"><?= mE($nom) ?></div>
                                    <div class="conv-last"><?= mE(mb_substr($lastMsg, 0, 40)) ?></div>
                                </div>
                                <div class="conv-meta">
                                    <div class="conv-time"><?= mDate($lastMsgDate) ?></div>
                                    <?php if ($nonLus > 0): ?><div class="conv-badge"><?= $nonLus ?></div><?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div style="padding: 14px 20px; border-top: 1px solid rgba(255,255,255,.06); margin-top: 8px;">
                            <div style="font-size:11px;color:rgba(255,255,255,.5);font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">Admins & Agents en ligne</div>
                            <?php foreach ($users as $u): if ((int)$u['id_user'] === (int)$user['id_user']) continue; $online = isOnline($u); ?>
                                <div class="conv-item" style="padding:8px 0;" onclick="startPrivateConv(<?= (int)$u['id_user'] ?>)">
                                    <div class="conv-avatar <?= $online ? 'online' : '' ?>" style="width:32px;height:32px;font-size:11px;background:rgba(13,202,240,.15);"><?= mInitiales($u) ?></div>
                                    <div class="conv-info">
                                        <div class="conv-name" style="font-size:12px;"><?= mE($u['prenom'] . ' ' . $u['nom']) ?></div>
                                        <div class="conv-last" style="font-size:11px;"><span style="color:<?= $roleColors[$u['role']] ?? '#fff' ?>;"><?= mE($roleLabels[$u['role']] ?? '') ?></span> · <?= $online ? '🟢 En ligne' : '⚫ Hors ligne' ?></div>
                                    </div>
                                    <button class="btn-call-user" onclick="event.stopPropagation(); callUser(<?= (int)$u['id_user'] ?>)" title="Appeler <?= mE($u['prenom']) ?>">📞</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- CHAT -->
                <div class="msg-chat">
                    <?php if ($currentConv): ?>
                        <div class="msg-chat-header">
                            <div class="conv-avatar" style="width:40px;height:40px;font-size:14px;background:rgba(255,107,26,.2);"><?= mE(strtoupper(mb_substr($currentConv['autre_nom'] ?? 'G', 0, 1))) ?></div>
                            <div class="chat-header-info">
                                <h3><?= mE($currentConv['nom'] ?? $currentConv['autre_nom'] ?? 'Conversation') ?></h3>
                                <span><?= (int)$currentConv['total_messages'] ?> messages</span>
                            </div>
                            <div class="chat-header-actions">
                                <button class="btn-call-header" onclick="callFromChat()"><i class="bi bi-telephone"></i> Appeler</button>
                                <button class="chat-header-btn" title="Mentions @" onclick="toggleMentionsPanel()"><i class="bi bi-at"></i> <?php if (count($mentions) > 0): ?><span style="background:#FF6B1A;color:#fff;font-size:10px;padding:1px 6px;border-radius:999px;"><?= count($mentions) ?></span><?php endif; ?></button>
                            </div>
                        </div>
                        <div class="call-banner" id="callBanner">
                            <span class="ring" style="font-size:20px;">📞</span>
                            <span style="flex:1;font-size:13px;color:#2ec4b6;font-weight:700;" id="callBannerText">Appel en cours...</span>
                            <button style="padding:6px 14px;border-radius:8px;background:rgba(230,57,70,.15);border:1px solid rgba(230,57,70,.3);color:#e63946;font-size:12px;font-weight:700;cursor:pointer;" onclick="cancelCall()">Raccrocher</button>
                        </div>
                        <div class="msg-messages" id="msgContainer" data-conv="<?= (int)$convId ?>">
                            <?php foreach ($messages as $msg): ?>
                                <?php if ((string)$msg['type_message'] === 'systeme'): ?>
                                    <div class="msg-bubble system">📌 <?= mE($msg['contenu']) ?></div>
                                <?php else:
                                    $isSent = (int)$msg['id_expediteur'] === (int)$user['id_user'];
                                ?>
                                    <div class="msg-bubble <?= $isSent ? 'sent' : 'received' ?>">
                                        <?php if (!$isSent): ?>
                                            <div class="sender" style="color:<?= $roleColors[$msg['role']] ?? '#fff' ?>"><?= mE($msg['prenom'] . ' ' . $msg['nom']) ?></div>
                                        <?php endif; ?>
                                        <?php if ((string)$msg['type_message'] === 'image' && !empty($msg['fichier_url'])): ?>
                                            <img class="msg-image" src="<?= mE($msg['fichier_url']) ?>" alt="Image" onclick="previewImage(this.src)">
                                            <?php if (!empty($msg['contenu'])): ?><div style="margin-top:6px;"><?= mE(nl2br($msg['contenu'])) ?></div><?php endif; ?>
                                        <?php elseif ((string)$msg['type_message'] === 'audio' && !empty($msg['fichier_url'])): ?>
                                            <div class="msg-audio">
                                                <audio controls preload="metadata">
                                                    <source src="<?= mE($msg['fichier_url']) ?>">
                                                </audio>
                                            </div>
                                            <div class="audio-badge">🎤 <?= mE($msg['duree_audio'] ?? '0') ?>s</div>
                                            <?php if (!empty($msg['contenu'])): ?><div style="margin-top:6px;"><?= mE(nl2br($msg['contenu'])) ?></div><?php endif; ?>
                                        <?php else: ?>
                                            <div><?= mE(nl2br($msg['contenu'])) ?></div>
                                        <?php endif; ?>
                                        <div class="time"><?= mDate($msg['date_envoi']) ?></div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <div class="msg-input-wrap">
                            <div class="msg-input-toolbar">
                                <button onclick="document.getElementById('imageFileInput').click()" title="Envoyer une image"><i class="bi bi-image"></i></button>
                                <input type="file" id="imageFileInput" accept="image/*" style="display:none" onchange="handleImageSelect(this)">
                                <button id="recBtn" onclick="toggleRecording()" title="Message vocal"><i class="bi bi-mic"></i></button>
                                <button onclick="toggleEmojiPicker()" title="Emoji"><i class="bi bi-emoji-smile"></i></button>
                            </div>
                            <div class="image-preview-bar" id="imagePreviewBar">
                                <img id="imagePreviewThumb" src="">
                                <span class="img-name" id="imagePreviewName"></span>
                                <button class="remove-preview" onclick="removeImagePreview()">✕ Retirer</button>
                            </div>
                            <div class="msg-input-row" style="position:relative;">
                                <textarea id="msgInput" placeholder="💬 Message..." rows="1" onkeydown="handleKey(event)" oninput="handleInput()"></textarea>
                                <button class="btn-send" onclick="sendMsg()"><i class="bi bi-send-fill"></i></button>
                                <div class="emoji-picker" id="emojiPicker"></div>
                                <div class="mention-list" id="mentionList"></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="msg-empty">
                            <i class="bi bi-chat-dots"></i>
                            <p>Sélectionnez une conversation à gauche pour commencer</p>
                            <div style="margin-top:24px;padding:20px;border-radius:16px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);max-width:320px;text-align:left;">
                                <div style="font-size:13px;font-weight:700;color:#fff;margin-bottom:12px;">📖 Comment utiliser :</div>
                                <div style="font-size:12px;color:rgba(255,255,255,.5);line-height:2;">
                                    <div>💬 <strong style="color:#fff;">Cliquez</strong> sur un utilisateur pour discuter</div>
                                    <div>🖼️ <strong style="color:#fff;">Image</strong> — envoyez des photos</div>
                                    <div>🎤 <strong style="color:#fff;">Vocal</strong> — enregistrez un message audio</div>
                                    <div>😊 <strong style="color:#fff;">Emoji</strong> — ajoutez des émoticônes</div>
                                    <div>📞 <strong style="color:#fff;">Appel</strong> — notifiez un collègue</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- MENTIONS PANEL -->
                <?php if (!empty($mentions)): ?>
                <div class="mentions-panel" id="mentionsPanel">
                    <h3>📢 Appels (<?= count($mentions) ?>)</h3>
                    <?php foreach ($mentions as $m): ?>
                        <div class="mention-item-full">
                            <div class="from"><strong><?= mE($m['expediteur_prenom'] . ' ' . $m['expediteur_nom']) ?></strong> vous appelle</div>
                            <div class="content"><?= mE(mb_substr($m['contenu'], 0, 80)) ?>...</div>
                            <div class="time"><?= mDateFull($m['date_mention']) ?></div>
                            <button class="btn-resolve" onclick="resolveMention(<?= (int)$m['id_mention'] ?>)">✓ Résolu</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- MODAL NEW CONVERSATION -->
<div class="modal-overlay" id="newConvModal">
    <div class="modal-card">
        <h3>Nouvelle conversation</h3>
        <label>Type</label>
        <select id="convType" onchange="toggleGroupFields()">
            <option value="prive">💬 Privée (1-on-1)</option>
            <option value="groupe">👥 Groupe</option>
        </select>
        <label>Destinataire</label>
        <select id="convDest">
            <?php foreach ($users as $u): if ((int)$u['id_user'] === (int)$user['id_user']) continue; ?>
                <option value="<?= (int)$u['id_user'] ?>"><?= mE($u['prenom'] . ' ' . $u['nom']) ?> (<?= mE($roleLabels[$u['role']] ?? '') ?>)</option>
            <?php endforeach; ?>
        </select>
        <div id="groupFields" style="display:none;">
            <label>Nom du groupe</label>
            <input type="text" id="groupName" placeholder="Ex: Team Assurance">
            <label>Participants</label>
            <div class="user-checkboxes">
                <?php foreach ($users as $u): if ((int)$u['id_user'] === (int)$user['id_user']) continue; ?>
                    <label class="user-checkbox">
                        <input type="checkbox" value="<?= (int)$u['id_user'] ?>">
                        <?= mE($u['prenom'] . ' ' . $u['nom']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeNewConvModal()">Annuler</button>
            <button class="btn-create" onclick="createConv()">Créer</button>
        </div>
    </div>
</div>

<!-- IMAGE PREVIEW MODAL -->
<div class="img-preview-modal" id="imgPreviewModal" onclick="this.classList.remove('show')">
    <img id="imgPreviewFull" src="">
</div>

<script src="<?= $base ?>/view/BackOffice/assets/js/main.js"></script>
<script>
const BASE = window.BASE_URL || '';
const CURRENT_USER_ID = <?= (int)($user['id_user']) ?>;
const CURRENT_CONV_ID = <?= (int)($convId ?? 0) ?>;
const ALL_USERS = <?= json_encode(array_values(array_map(function($u){return ['id'=>$u['id_user'],'prenom'=>$u['prenom'],'nom'=>$u['nom']];}, $users ?? []))) ?>;
let lastMsgDate = <?= $messages ? json_encode(end($messages)['date_envoi'] ?? null) : 'null' ?>;

// ═══ EMOJI PICKER ═══
const EMOJIS = ['😀','😂','🤣','😊','😍','🥰','😎','🤩','😇','🤗','🤔','😏','😅','😬','🤪','😜','🥳','😢','😤','😡','🤯','😱','🥺','😴','🤮','🤢','💀','👻','❤️','🔥','⭐','🎉','🎊','💪','👍','👏','🙌','🤝','✌️','👋','📞','📧','💬','📌','✅','❌','⚠️','💡','🏆','🎯','💎','🚀','⚡','🌟','💯','🙏','👀','🤝','💼','📎','🔔','💻','📱','🎵','🎶','☕','🍕','🌈','🌍','🕐','📅','🔒','🔑'];

function buildEmojiPicker() {
    const picker = document.getElementById('emojiPicker');
    if (picker) {
        picker.innerHTML = '<div class="emoji-grid">' + EMOJIS.map(e => '<button class="emoji-btn" onclick="insertEmoji(\''+e+'\')">'+e+'</button>').join('') + '</div>';
    }
}
buildEmojiPicker();

function toggleEmojiPicker() {
    document.getElementById('emojiPicker').classList.toggle('show');
    document.getElementById('mentionList').classList.remove('show');
}

function insertEmoji(emoji) {
    const input = document.getElementById('msgInput');
    input.value += emoji;
    input.focus();
}

document.addEventListener('click', function(e) {
    const picker = document.getElementById('emojiPicker');
    if (picker.classList.contains('show') && !picker.contains(e.target) && !e.target.closest('.msg-input-toolbar button')) {
        picker.classList.remove('show');
    }
});

// ═══ IMAGE UPLOAD ═══
let selectedImageFile = null;

function handleImageSelect(input) {
    if (input.files && input.files[0]) {
        selectedImageFile = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreviewThumb').src = e.target.result;
            document.getElementById('imagePreviewName').textContent = selectedImageFile.name;
            document.getElementById('imagePreviewBar').classList.add('show');
        };
        reader.readAsDataURL(selectedImageFile);
    }
}

function removeImagePreview() {
    selectedImageFile = null;
    document.getElementById('imagePreviewBar').classList.remove('show');
    document.getElementById('imageFileInput').value = '';
}

function previewImage(src) {
    document.getElementById('imgPreviewFull').src = src;
    document.getElementById('imgPreviewModal').classList.add('show');
}

// ═══ VOICE RECORDING ═══
let mediaRecorder = null;
let audioChunks = [];
let isRecording = false;
let recStartTime = 0;
let recTimerInterval = null;

async function toggleRecording() {
    if (!isRecording) {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];

            mediaRecorder.ondataavailable = function(e) {
                if (e.data.size > 0) audioChunks.push(e.data);
            };

            mediaRecorder.onstop = function() {
                const blob = new Blob(audioChunks, { type: 'audio/webm' });
                const file = new File([blob], 'voice_' + Date.now() + '.webm', { type: 'audio/webm' });
                const duration = Math.round((Date.now() - recStartTime) / 1000);
                sendVoiceMessage(file, duration);
                stream.getTracks().forEach(t => t.stop());
            };

            mediaRecorder.start();
            isRecording = true;
            recStartTime = Date.now();
            const btn = document.getElementById('recBtn');
            btn.classList.add('recording');
            btn.innerHTML = '<i class="bi bi-stop-circle-fill"></i>';
        } catch(err) {
            alert('Accès micro refusé. Vérifiez les permissions.');
        }
    } else {
        mediaRecorder.stop();
        isRecording = false;
        const btn = document.getElementById('recBtn');
        btn.classList.remove('recording');
        btn.innerHTML = '<i class="bi bi-mic"></i>';
    }
}

function sendVoiceMessage(file, duration) {
    if (!CURRENT_CONV_ID) return;
    const fd = new FormData();
    fd.append('id_conversation', CURRENT_CONV_ID);
    fd.append('contenu', '🎤 Message vocal');
    fd.append('fichier', file);
    fd.append('duree_audio', duration);

    fetch(BASE + '/controller/MessagerieController.php?action=envoyer', { method:'POST', body:fd, credentials:'same-origin' })
    .then(r => r.json())
    .then(d => { if (d.success) fetchNewMessages(); })
    .catch(() => {});
}

// ═══ SEND MESSAGE ═══
function openConv(id) { window.location.href = '?conv=' + id; }

function startPrivateConv(userId) {
    const fd = new FormData();
    fd.append('type', 'prive');
    fd.append('destinataire', userId);
    fetch(BASE + '/controller/MessagerieController.php?action=nouvelle_conversation', {
        method: 'POST', body: fd, credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(d => { if (d.success) openConv(d.id_conversation); });
}

function sendMsg() {
    const input = document.getElementById('msgInput');
    const content = input.value.trim();
    if ((!content && !selectedImageFile) || !CURRENT_CONV_ID) return;

    const fd = new FormData();
    fd.append('id_conversation', CURRENT_CONV_ID);
    fd.append('contenu', content);

    if (selectedImageFile) {
        fd.append('fichier', selectedImageFile);
    }

    fetch(BASE + '/controller/MessagerieController.php?action=envoyer', { method:'POST', body:fd, credentials:'same-origin' })
    .then(r => r.text())
    .then(raw => {
        try {
            const d = JSON.parse(raw);
            if (d.success) {
                input.value = '';
                input.style.height = 'auto';
                removeImagePreview();
                fetchNewMessages();
            } else {
                alert('Erreur: ' + (d.error || 'Inconnue'));
            }
        } catch(e) {
            alert('Server error: ' + raw.substring(0, 150));
        }
    })
    .catch(err => { alert('Erreur réseau.'); });
}

function fetchNewMessages() {
    if (!CURRENT_CONV_ID) return;
    const params = new URLSearchParams({conv: CURRENT_CONV_ID});
    if (lastMsgDate) params.set('after', lastMsgDate);

    fetch(BASE + '/controller/MessagerieController.php?action=nouveaux&' + params, {credentials:'same-origin'})
    .then(r => r.json())
    .then(d => {
        if (d.messages && d.messages.length) {
            const container = document.getElementById('msgContainer');
            d.messages.forEach(msg => {
                if (msg.type_message === 'systeme') {
                    container.innerHTML += '<div class="msg-bubble system">📌 ' + escHtml(msg.contenu) + '</div>';
                } else {
                    const isSent = parseInt(msg.id_expediteur) === CURRENT_USER_ID;
                    let contentHtml = '';

                    if (msg.type_message === 'image' && msg.fichier_url) {
                        contentHtml = '<img class="msg-image" src="' + BASE + msg.fichier_url + '" alt="Image" onclick="previewImage(this.src)">';
                        if (msg.contenu) contentHtml += '<div style="margin-top:6px;">' + escHtml(msg.contenu).replace(/\n/g, '<br>') + '</div>';
                    } else if (msg.type_message === 'audio' && msg.fichier_url) {
                        contentHtml = '<div class="msg-audio"><audio controls preload="metadata"><source src="' + BASE + msg.fichier_url + '"></audio></div>';
                        contentHtml += '<div class="audio-badge">🎤 ' + (msg.duree_audio || '0') + 's</div>';
                    } else {
                        contentHtml = escHtml(msg.contenu).replace(/\n/g, '<br>');
                    }

                    container.innerHTML += '<div class="msg-bubble ' + (isSent ? 'sent' : 'received') + '">' +
                        (!isSent ? '<div class="sender">' + escHtml(msg.prenom + ' ' + msg.nom) + '</div>' : '') +
                        contentHtml +
                        '<div class="time">' + msg.date_envoi.substring(11, 16) + '</div></div>';
                }
                lastMsgDate = msg.date_envoi;
            });
            container.scrollTop = container.scrollHeight;
        }
    })
    .catch(() => {});
}

if (CURRENT_CONV_ID) {
    setInterval(fetchNewMessages, 3000);
    setTimeout(() => { const c = document.getElementById('msgContainer'); if(c) c.scrollTop = c.scrollHeight; }, 200);
}

function handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMsg(); }
}

function handleInput() {
    const autoResize = document.getElementById('msgInput');
    autoResize.style.height = 'auto';
    autoResize.style.height = Math.min(autoResize.scrollHeight, 120) + 'px';

    const val = autoResize.value;
    const cursorPos = autoResize.selectionStart;
    const beforeCursor = val.substring(0, cursorPos);
    const match = beforeCursor.match(/@(\w*)$/);
    if (match) {
        showMentionSuggestions(match[1]);
        document.getElementById('emojiPicker').classList.remove('show');
    } else {
        document.getElementById('mentionList').classList.remove('show');
    }
}

function showMentionSuggestions(query) {
    const list = document.getElementById('mentionList');
    const filtered = ALL_USERS.filter(u =>
        u.id !== CURRENT_USER_ID &&
        (u.prenom.toLowerCase().startsWith(query.toLowerCase()) || u.nom.toLowerCase().startsWith(query.toLowerCase()))
    );
    if (filtered.length === 0) { list.classList.remove('show'); return; }
    list.innerHTML = filtered.map(u =>
        '<div class="mention-item" onclick="insertMention(\'' + u.prenom + '\')">' +
        '<div class="m-avatar">' + u.prenom[0].toUpperCase() + '</div>' +
        u.prenom + ' ' + u.nom + '</div>'
    ).join('');
    list.classList.add('show');
}

function insertMention(prenom) {
    const input = document.getElementById('msgInput');
    const val = input.value;
    const cursorPos = input.selectionStart;
    const beforeCursor = val.substring(0, cursorPos);
    const newBefore = beforeCursor.replace(/@\w*$/, '@' + prenom + ' ');
    input.value = newBefore + val.substring(cursorPos);
    input.focus();
    input.selectionStart = input.selectionEnd = newBefore.length;
    document.getElementById('mentionList').classList.remove('show');
}

function openNewConvModal() { document.getElementById('newConvModal').classList.add('show'); }
function closeNewConvModal() { document.getElementById('newConvModal').classList.remove('show'); }
function toggleGroupFields() { document.getElementById('groupFields').style.display = document.getElementById('convType').value === 'groupe' ? 'block' : 'none'; }

function createConv() {
    const type = document.getElementById('convType').value;
    const fd = new FormData();
    fd.append('type', type);
    if (type === 'prive') {
        fd.append('destinataire', document.getElementById('convDest').value);
    } else {
        fd.append('nom', document.getElementById('groupName').value);
        document.querySelectorAll('.user-checkboxes input:checked').forEach(cb => fd.append('participants[]', cb.value));
    }
    fetch(BASE + '/controller/MessagerieController.php?action=nouvelle_conversation', {method:'POST', body:fd, credentials:'same-origin'})
    .then(r => r.json())
    .then(d => { if (d.success) { closeNewConvModal(); openConv(d.id_conversation); } });
}

function resolveMention(id) {
    fetch(BASE + '/controller/MessagerieController.php?action=resoudre_mention', {
        method: 'POST', body: new URLSearchParams({id_mention: id})
    }).then(() => location.reload());
}

function toggleMentionsPanel() {
    const p = document.getElementById('mentionsPanel');
    if (p) p.style.display = p.style.display === 'none' ? 'block' : 'none';
}

function filterConvs() {
    const q = document.getElementById('convSearch').value.toLowerCase();
    document.querySelectorAll('.conv-item[data-name]').forEach(el => {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function callUser(userId) {
    const user = ALL_USERS.find(u => u.id === userId);
    if (!user) return;
    const banner = document.getElementById('callBanner');
    document.getElementById('callBannerText').textContent = '📞 Appel à ' + user.prenom + ' ' + user.nom + '...';
    banner.classList.add('show');

    const fd = new FormData();
    fd.append('type', 'prive');
    fd.append('destinataire', userId);

    fetch(BASE + '/controller/MessagerieController.php?action=nouvelle_conversation', {
        method: 'POST', body: fd, credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            window.location.href = '?conv=' + d.id_conversation + '&call=' + userId;
        }
    });
}

function callFromChat() {
    if (!CURRENT_CONV_ID) return;
    const banner = document.getElementById('callBanner');
    const chatName = document.querySelector('.chat-header-info h3')?.textContent || 'équipe';
    document.getElementById('callBannerText').textContent = '📞 Appel à ' + chatName + '...';
    banner.classList.add('show');

    const msg = '@admin 🚨 APPEL URGENT — Répondez s\'il vous plaît!';
    const fd = new FormData();
    fd.append('id_conversation', CURRENT_CONV_ID);
    fd.append('contenu', msg);

    fetch(BASE + '/controller/MessagerieController.php?action=envoyer', {method:'POST', body:fd, credentials:'same-origin'})
    .then(r => r.json())
    .then(d => { if (d.success) fetchNewMessages(); })
    .catch(() => {});

    setTimeout(() => banner.classList.remove('show'), 5000);
}

function cancelCall() { document.getElementById('callBanner').classList.remove('show'); }

<?php if (!empty($_GET['call'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    const banner = document.getElementById('callBanner');
    if (banner) {
        banner.classList.add('show');
        setTimeout(() => {
            const input = document.getElementById('msgInput');
            if (input) { input.value = '📞 Bonjour, j\'ai besoin d\'aide!'; input.focus(); }
        }, 1500);
        setTimeout(() => banner.classList.remove('show'), 6000);
    }
});
<?php endif; ?>
</script>
</body>
</html>

