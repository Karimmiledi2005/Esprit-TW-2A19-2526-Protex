<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Réseau — Protex</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- CSS SYSTEM -->
    <link rel="stylesheet" href="assets_agences_postes/css/variables.css">
    <link rel="stylesheet" href="assets_agences_postes/css/base.css">
    <link rel="stylesheet" href="assets_agences_postes/css/layout.css">
    <link rel="stylesheet" href="assets_agences_postes/css/animations.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            color: #15233C;
            background: #f0f4ff;
            min-height: 100vh;
        }


        /* ── PAGE LAYOUT ── */
        .page-body {
            max-width: 1100px;
            margin: 0 auto;
            padding: 32px 24px 60px;
        }

        .page-header {
            margin-bottom: 28px;
        }

        .page-title {
            font-family: 'Sora', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: #15233C;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title i {
            color: #FF6B1A;
            font-size: 28px;
        }

        .page-sub {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }

        .agence-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #FF6B1A;
            color: #fff;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 700;
            margin-top: 8px;
            box-shadow: 0 4px 12px rgba(255, 107, 26, 0.2);
        }

        /* ── GRID ── */
        .net-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
            align-items: start;
        }

        @media (max-width: 820px) {
            .net-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ── CARD ── */
        .card {
            background: #fff;
            border: 1px solid rgba(26, 58, 122, 0.08);
            border-radius: 24px;
            box-shadow: 0 2px 12px rgba(26, 58, 122, 0.06);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .card-header {
            padding: 18px 24px 14px;
            border-bottom: 1px solid rgba(26, 58, 122, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .card-title {
            font-family: 'Sora', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: #15233C;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title i {
            color: #FF6B1A;
        }

        .card-body {
            padding: 16px 24px;
        }

        /* ── SEARCH BAR ── */
        .search-wrap {
            position: relative;
        }

        .search-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 15px;
        }

        .search-input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            border: 1.5px solid rgba(26, 58, 122, 0.08);
            border-radius: 12px;
            background: #f8f9ff;
            font-size: 13.5px;
            color: #15233C;
            font-family: inherit;
            transition: all 0.3s;
            outline: none;
        }

        .search-input:focus {
            border-color: #FF6B1A;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(255, 107, 26, 0.10);
        }

        /* ── TABS ── */
        .tabs {
            display: flex;
            gap: 4px;
            padding: 12px 16px 0;
            background: rgba(26, 58, 122, 0.03);
            border-bottom: 1px solid rgba(26, 58, 122, 0.08);
        }

        .tab-btn {
            flex: 1;
            padding: 10px 6px;
            border: none;
            border-radius: 8px 8px 0 0;
            background: transparent;
            color: #64748b;
            font-size: 11.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            font-family: inherit;
        }

        .tab-btn.active {
            background: #FF6B1A;
            color: #fff;
        }

        .tab-btn:hover:not(.active) {
            background: rgba(255, 107, 26, 0.08);
            color: #FF6B1A;
        }

        .tab-badge {
            position: absolute;
            top: -4px;
            right: 2px;
            background: #ef4444;
            color: #fff;
            border-radius: 50%;
            width: 17px;
            height: 17px;
            font-size: 9px;
            display: none;
            align-items: center;
            justify-content: center;
        }

        /* ── USER LIST ── */
        .user-list {
            max-height: 480px;
            overflow-y: auto;
        }

        .user-list::-webkit-scrollbar {
            width: 4px;
        }

        .user-list::-webkit-scrollbar-thumb {
            background: rgba(26, 58, 122, 0.15);
            border-radius: 2px;
        }

        .user-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 16px;
            border-bottom: 1px solid rgba(26, 58, 122, 0.06);
            transition: background 0.2s;
            animation: slideIn 0.25s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .user-item:last-child {
            border-bottom: none;
        }

        .user-item:hover {
            background: rgba(255, 107, 26, 0.03);
        }

        .avatar-wrap-net {
            position: relative;
            flex-shrink: 0;
        }

        .avatar-wrap-net img {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .online-dot {
            position: absolute;
            bottom: 1px;
            right: 1px;
            width: 11px;
            height: 11px;
            background: #2ed573;
            border: 2px solid #fff;
            border-radius: 50%;
        }
        .online-dot.offline { background: #cbd5e1; }

        .user-info {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-size: 14px;
            font-weight: 700;
            color: #15233C;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 3px;
        }

        .role-pill {
            font-size: 9px;
            background: #1A3A7A;
            color: #fff;
            padding: 1px 7px;
            border-radius: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-txt {
            font-size: 10px;
            color: #64748b;
        }

        .status-txt.online {
            color: #2ed573;
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .action-btn {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 1.5px solid rgba(26, 58, 122, 0.08);
            background: #f8f9ff;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .action-btn:hover {
            border-color: #FF6B1A;
            color: #FF6B1A;
            background: rgba(255, 107, 26, 0.06);
        }

        .action-btn.accent {
            background: #FF6B1A;
            color: #fff;
            border-color: #FF6B1A;
        }

        .action-btn.accent:hover {
            background: #e65a10;
        }

        .action-btn.danger {
            border-color: #ef4444;
            color: #ef4444;
        }

        .action-btn.danger:hover {
            background: rgba(239, 68, 68, 0.08);
        }

        .action-btn.gold {
            color: #f59e0b;
            border-color: #f59e0b;
            background: rgba(245, 158, 11, 0.07);
        }

        .action-btn.gold.active {
            background: #f59e0b;
            color: #fff;
            border-color: #f59e0b;
        }

        .action-btn.chat-active {
            background: #1A3A7A;
            color: #fff;
            border-color: #1A3A7A;
            position: relative;
        }

        /* ── CHAT PANEL ── */
        .chat-panel {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 320px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 12px 40px rgba(26, 58, 122, 0.18);
            border: 1px solid rgba(26, 58, 122, 0.08);
            display: flex;
            flex-direction: column;
            z-index: 500;
            transform: translateY(20px);
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
            max-height: 480px;
        }

        .chat-panel.open {
            transform: translateY(0);
            opacity: 1;
            pointer-events: all;
        }

        .chat-header {
            background: #1A3A7A;
            padding: 12px 16px;
            border-radius: 20px 20px 0 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chat-header img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .chat-name {
            flex: 1;
            font-size: 13px;
            font-weight: 700;
            color: #fff;
        }

        .chat-status {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.65);
        }

        .chat-close {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            font-size: 18px;
            cursor: pointer;
            padding: 2px;
        }

        .chat-close:hover {
            color: #fff;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-height: 200px;
            max-height: 300px;
            background: #f8f9ff;
        }

        .msg-bubble {
            max-width: 80%;
            padding: 8px 12px;
            border-radius: 14px;
            font-size: 12.5px;
            line-height: 1.45;
            word-wrap: break-word;
        }

        .msg-bubble.mine {
            background: #1A3A7A;
            color: #fff;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }

        .msg-bubble.theirs {
            background: #fff;
            color: #15233C;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
            border: 1px solid rgba(26, 58, 122, 0.08);
        }

        .chat-footer {
            padding: 10px 12px;
            border-top: 1px solid rgba(26, 58, 122, 0.08);
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            padding: 8px 12px;
            border: 1.5px solid rgba(26, 58, 122, 0.08);
            border-radius: 20px;
            font-size: 12.5px;
            outline: none;
            transition: all 0.3s;
            background: #f8f9ff;
        }

        .chat-input:focus {
            border-color: #FF6B1A;
            background: #fff;
        }

        .send-btn {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #FF6B1A;
            color: #fff;
            border: none;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        /* ── SOS CARD ── */
        .sos-card {
            background: linear-gradient(135deg, #1A3A7A, #0f2556);
            border-radius: 24px;
            padding: 24px;
            color: #fff;
            margin-bottom: 20px;
        }

        .sos-title {
            font-family: 'Sora', sans-serif;
            font-size: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .sos-sub {
            font-size: 12px;
            opacity: 0.7;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .trusted-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 20px;
            min-height: 40px;
        }

        .trusted-item {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.07);
            border-radius: 12px;
            padding: 10px 12px;
        }

        .trusted-item img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .trusted-name {
            font-size: 13px;
            font-weight: 600;
            flex: 1;
        }

        .sos-trigger-btn {
            width: 100%;
            padding: 14px;
            background: #ef4444;
            color: #fff;
            border: none;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Sora', sans-serif;
            transition: all 0.3s;
            animation: sos-glow 2s infinite;
        }

        @keyframes sos-glow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            50% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
        }

        .sos-trigger-btn:hover { background: #dc2626; }

        /* ── STATS ── */
        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .stat-label { font-size: 13px; color: #64748b; }
        .stat-val { font-size: 16px; font-weight: 800; color: #FF6B1A; }
        .stat-val.green { color: #2ed573; }

        /* ── SOS MODAL ── */
        .sos-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 26, 46, 0.85);
            backdrop-filter: blur(8px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .sos-modal-overlay.open { display: flex; }

        .sos-modal {
            background: #fff;
            border-radius: 28px;
            padding: 40px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 30px 70px rgba(0,0,0,0.3);
        }

        .empty-state { padding: 48px; text-align: center; color: #64748b; font-size: 13px; }
        .empty-state i { font-size: 40px; display: block; margin-bottom: 12px; opacity: 0.2; }

        /* ─── STORIES ─── */
        .stories-bar {
            display: flex;
            gap: 14px;
            overflow-x: auto;
            padding: 18px 0 12px;
            margin-bottom: 20px;
            scrollbar-width: none;
        }
        .stories-bar::-webkit-scrollbar { display: none; }
        .story-bubble {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            flex-shrink: 0;
        }
        .story-ring {
            width: 62px;
            height: 62px;
            border-radius: 50%;
            padding: 3px;
            background: linear-gradient(135deg, #FF6B1A, #e11d48, #a855f7);
            transition: transform 0.2s;
        }
        .story-ring:hover { transform: scale(1.08); }
        .story-ring.no-story { background: #e2e8f0; }
        .story-ring img, .story-ring .story-init {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 800;
            color: #FF6B1A;
        }
        .story-label {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            text-align: center;
            white-space: nowrap;
            max-width: 64px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .story-add-btn {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: 2px dashed #FF6B1A;
            background: rgba(255,107,26,0.06);
            color: #FF6B1A;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .story-add-btn:hover { background: rgba(255,107,26,0.12); transform: scale(1.1); }

        /* ─── STORY VIEWER ─── */
        .story-viewer-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.95);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        .story-viewer-overlay.open { display: flex; }
        .story-viewer-content {
            position: relative;
            max-width: 420px;
            width: 90%;
            border-radius: 20px;
            overflow: hidden;
            background: #000;
        }
        .story-viewer-img {
            width: 100%;
            max-height: 70vh;
            object-fit: contain;
            display: block;
        }
        .story-viewer-text {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.85));
            color: #fff;
            padding: 24px 20px 20px;
            font-size: 15px;
            font-weight: 600;
        }
        .story-viewer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: absolute;
            top: 16px;
            left: 16px;
            right: 16px;
            z-index: 10;
        }
        .story-progress-bar {
            height: 3px;
            background: rgba(255,255,255,0.3);
            border-radius: 2px;
            overflow: hidden;
            flex: 1;
            margin: 0 4px;
        }
        .story-progress-fill {
            height: 100%;
            background: #fff;
            width: 0;
            transition: width 5s linear;
        }
        .story-close-btn {
            background: none;
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            padding: 4px;
        }
        .story-viewer-overlay .tap-left,
        .story-viewer-overlay .tap-right {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 40%;
            cursor: pointer;
            z-index: 5;
        }
        .tap-left { left: 0; }
        .tap-right { right: 0; }

        /* ─── FEED ─── */
        .post-card {
            background: #fff;
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: 20px;
            margin-bottom: 18px;
            box-shadow: 0 2px 12px rgba(26,58,122,0.06);
            overflow: hidden;
        }
        .post-head {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px 12px;
        }
        .post-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg,#FF6B1A,#e11d48);
            color: #fff;
            font-size: 16px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }
        .post-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .post-author-name { font-weight: 800; font-size: 14px; color: #15233C; }
        .post-date { font-size: 11px; color: #94a3b8; margin-top: 2px; }
        .post-body { padding: 0 20px 12px; font-size: 14px; color: #334155; line-height: 1.6; }
        .post-body .hashtag {
            color: #FF6B1A;
            font-weight: 700;
            cursor: pointer;
            transition: color 0.2s;
        }
        .post-body .hashtag:hover { text-decoration: underline; }
        .post-body .mention { color: #1A3A7A; font-weight: 700; }
        .post-image {
            width: 100%;
            max-height: 350px;
            object-fit: cover;
            display: block;
            margin-bottom: 4px;
        }
        .reactions-bar {
            display: flex;
            gap: 8px;
            padding: 10px 20px;
            border-top: 1px solid rgba(26,58,122,0.06);
            align-items: center;
        }
        .react-btn {
            background: none;
            border: 1.5px solid rgba(26,58,122,0.1);
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
            font-family: inherit;
            font-weight: 700;
            color: #64748b;
        }
        .react-btn.active { background: rgba(255,107,26,0.1); border-color: #FF6B1A; color: #FF6B1A; }
        .react-btn:hover { background: rgba(255,107,26,0.07); }
        .react-summary { margin-left: auto; font-size: 12px; color: #94a3b8; }

        /* ─── COMPOSER ─── */
        .composer-card {
            background: #fff;
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(26,58,122,0.06);
        }
        .composer-top {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .composer-textarea {
            flex: 1;
            border: 1.5px solid rgba(26,58,122,0.1);
            border-radius: 14px;
            padding: 12px 16px;
            font-size: 14px;
            color: #15233C;
            font-family: 'DM Sans', sans-serif;
            resize: none;
            min-height: 80px;
            outline: none;
            background: #f8f9ff;
            transition: all 0.3s;
        }
        .composer-textarea:focus { border-color: #FF6B1A; background: #fff; }
        .composer-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
        }
        .img-drop-zone {
            border: 2px dashed rgba(26,58,122,0.15);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            color: #94a3b8;
            font-size: 13px;
            margin-bottom: 12px;
            transition: all 0.3s;
            display: none;
        }
        .img-drop-zone.show { display: block; }
        .img-drop-zone:hover, .img-drop-zone.drag-over { border-color: #FF6B1A; background: rgba(255,107,26,0.03); }
        .img-preview-wrap {
            position: relative;
            display: inline-block;
            margin-top: 8px;
        }
        .img-preview-wrap img { max-height: 120px; border-radius: 8px; }
        .img-preview-remove {
            position: absolute;
            top: -6px;
            right: -6px;
            width: 20px;
            height: 20px;
            background: #ef4444;
            color: #fff;
            border: none;
            border-radius: 50%;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .post-btn {
            background: #FF6B1A;
            color: #fff;
            border: none;
            padding: 10px 22px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .post-btn:hover { background: #e05a14; }
        .add-img-btn {
            background: none;
            border: 1.5px solid rgba(26,58,122,0.1);
            border-radius: 20px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .add-img-btn:hover { border-color: #FF6B1A; color: #FF6B1A; }

        /* ─── SUGGESTIONS ─── */
        .suggest-card {
            background: #fff;
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: 20px;
            padding: 18px;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(26,58,122,0.06);
        }
        .suggest-title {
            font-family: 'Sora',sans-serif;
            font-size: 14px;
            font-weight: 800;
            color: #15233C;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .suggest-title i { color: #FF6B1A; }
        .suggest-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(26,58,122,0.06);
        }
        .suggest-user:last-child { border-bottom: none; }
        .suggest-init {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1A3A7A, #3b82f6);
            color: #fff;
            font-size: 15px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .suggest-name { font-size: 13px; font-weight: 700; color: #15233C; }
        .suggest-meta { font-size: 11px; color: #94a3b8; margin-top: 2px; }
        .suggest-invite-btn {
            margin-left: auto;
            background: rgba(255,107,26,0.1);
            color: #FF6B1A;
            border: none;
            border-radius: 20px;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
        }
        .suggest-invite-btn:hover { background: #FF6B1A; color: #fff; }

       
    </style>
</head>

<body>
    <div class="layout">
        <?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

        <main class="page-body">
            <div class="page-header">
                <div class="page-title">
                    <i class="bi bi-people-fill"></i>
                    Mon Réseau Social Protex
                </div>
                <p class="page-sub">Connectez-vous avec les clients de votre agence, échangez des messages et activez votre réseau SOS.</p>
                <div class="agence-badge" id="agenceBadge">
                    <i class="bi bi-geo-alt-fill"></i> <span id="agenceName">Chargement...</span>
                </div>
            </div>

            <!-- R6: STORIES BAR -->
            <div class="stories-bar" id="storiesBar">
                <div class="story-bubble" id="myStoryBubble">
                    <div class="story-add-btn" onclick="openStoryComposer()" title="Ajouter une story">
                        <i class="bi bi-plus-lg"></i>
                    </div>
                    <span class="story-label">Ma story</span>
                </div>
            </div>

            <!-- R3: SOCIAL FEED + Friends Grid -->
            <div class="net-grid">
                <!-- COLONNE GAUCHE -->
                <div class="left-col">
                    <!-- R3: POST COMPOSER -->
                    <div class="composer-card" id="postComposer">
                        <div class="composer-top">
                            <div class="post-avatar" id="myAvatar">?</div>
                            <textarea class="composer-textarea" id="postContent" placeholder="Partagez une actualité, un conseil... Utilisez #hashtag et @mention"></textarea>
                        </div>
                        <div class="img-drop-zone" id="imgDropZone" ondragover="event.preventDefault(); this.classList.add('drag-over')" ondragleave="this.classList.remove('drag-over')" ondrop="handleImgDrop(event)">
                            <i class="bi bi-image"></i> Glissez une image ici ou <label for="imgFile" style="color:#FF6B1A; cursor:pointer; font-weight:700;">choisissez un fichier</label>
                            <input type="file" id="imgFile" accept="image/*" style="display:none" onchange="handleImgSelect(this)">
                            <div class="img-preview-wrap" id="imgPreviewWrap" style="display:none;">
                                <img id="imgPreview" src="" alt="">
                                <button class="img-preview-remove" onclick="removeImg()">×</button>
                            </div>
                        </div>
                        <div class="composer-actions">
                            <button class="add-img-btn" onclick="toggleImgZone()"><i class="bi bi-image"></i> Image</button>
                            <button class="post-btn" onclick="submitPost()"><i class="bi bi-send-fill"></i> Publier</button>
                        </div>
                    </div>

                    <!-- R3: POSTS FEED -->
                    <div id="hashtagFilterBanner" style="display:none; padding: 10px 16px; background: rgba(255,107,26,0.08); border-radius: 12px; margin-bottom: 14px; font-size:13px; font-weight:700; color:#FF6B1A;">
                        <i class="bi bi-hash"></i> Fil filtré par hashtag : <span id="activeHashtag"></span>
                        <button onclick="clearHashtag()" style="background:none; border:none; color:#FF6B1A; cursor:pointer; margin-left:10px; font-weight:800;">✕ Tout voir</button>
                    </div>

                    <div id="postsFeed">
                        <div class="empty-state"><i class="bi bi-hourglass-split"></i>Chargement du fil...</div>
                    </div>

                    <!-- RECHERCHE -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title"><i class="bi bi-search"></i> Chercher des clients</div>
                        </div>
                        <div class="card-body">
                            <div class="search-wrap">
                                <i class="bi bi-search"></i>
                                <input type="text" class="search-input" id="searchInput" placeholder="Nom, prénom ou agence...">
                            </div>
                            <div id="searchResults" class="user-list" style="margin-top:12px; max-height: 300px;"></div>
                        </div>
                    </div>

                    <!-- CONTACTS -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="bi bi-person-check"></i> Mes contacts 
                                <span id="contactCount" style="font-size:12px;opacity:0.6;font-weight:400;">(0)</span>
                            </div>
                        </div>
                        <div class="tabs">
                            <button class="tab-btn active" id="tab-friends" onclick="switchTab('friends')"><i class="bi bi-people"></i> Amis</button>
                            <button class="tab-btn" id="tab-pending" onclick="switchTab('pending')"><i class="bi bi-clock"></i> Invitations <span id="pendingBadge" class="tab-badge">0</span></button>
                            <button class="tab-btn" id="tab-trusted" onclick="switchTab('trusted')">⭐ Confiance</button>
                        </div>
                        <div id="contactsList" class="user-list">
                            <div class="empty-state">
                                <i class="bi bi-hourglass-split"></i>
                                Chargement...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLONNE DROITE -->
                <div class="right-col">
                    <!-- R4: FRIEND SUGGESTIONS -->
                    <div class="suggest-card" id="suggestCard">
                        <div class="suggest-title"><i class="bi bi-person-hearts"></i> Personnes que vous connaissez peut-être</div>
                        <div id="suggestionsList"><div style="text-align:center;font-size:12px;color:#94a3b8;">Chargement...</div></div>
                    </div>

                    <!-- CARTE SOS -->
                    <div class="sos-card">
                        <div class="sos-title"><i class="bi bi-shield-fill-exclamation"></i> Centre de Sécurité SOS</div>
                        <p class="sos-sub">En cas d'urgence, vos contacts de confiance (⭐) recevront immédiatement votre position GPS et une alerte email.</p>
                        
                        <div style="font-size:11px; text-transform:uppercase; font-weight:700; opacity:0.6; margin-bottom:12px;">Mes contacts de confiance</div>
                        <div class="trusted-list" id="trustedList"></div>

                        <button class="sos-trigger-btn" onclick="openSOSModal()">
                            <i class="bi bi-exclamation-octagon-fill"></i> DÉCLENCHER UNE ALERTE SOS
                        </button>
                    </div>

                    <!-- STATS -->
                    <div class="card">
                        <div class="card-header"><div class="card-title"><i class="bi bi-bar-chart-fill"></i> Statistiques</div></div>
                        <div class="card-body">
                            <div class="stat-row">
                                <span class="stat-label">Amis totaux</span>
                                <span class="stat-val" id="statFriends">0</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">En ligne maintenant</span>
                                <span class="stat-val green" id="statOnline">0</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Contacts de confiance</span>
                                <span class="stat-val" id="statTrusted">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- R6: STORY VIEWER OVERLAY -->
    <div class="story-viewer-overlay" id="storyViewer">
        <div style="display:flex; gap:4px; width: 420px; max-width:90%; margin-bottom: 10px;" id="storyProgressBars"></div>
        <div class="story-viewer-content">
            <div class="story-viewer-header">
                <div style="display:flex; align-items:center; gap:8px;">
                    <div style="width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.2);overflow:hidden;">
                        <img id="svAuthorAvatar" src="" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                    <span id="svAuthorName" style="color:#fff; font-weight:700; font-size:13px;"></span>
                </div>
                <button class="story-close-btn" onclick="closeStoryViewer()"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="tap-left" onclick="prevStory()"></div>
            <div class="tap-right" onclick="nextStory()"></div>
            <img class="story-viewer-img" id="svImg" src="" alt="">
            <div class="story-viewer-text" id="svText"></div>
        </div>
    </div>

    <!-- R6: STORY COMPOSER MODAL -->
    <div id="storyComposerModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9998; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:20px; padding:30px; max-width:400px; width:90%;">
            <h3 style="margin-bottom:16px; font-family:'Sora',sans-serif;">Ajouter une story</h3>
            <input type="file" id="storyFile" accept="image/*" style="margin-bottom:12px; width:100%;">
            <textarea id="storyText" placeholder="Texte optionnel (overlay sur l'image)..." style="width:100%; padding:10px; border:1.5px solid rgba(26,58,122,0.1); border-radius:10px; font-family:inherit; resize:none; min-height:70px; margin-bottom:12px;"></textarea>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button onclick="closeStoryComposer()" style="background:#f1f5f9; border:none; padding:10px 20px; border-radius:10px; font-weight:700; cursor:pointer;">Annuler</button>
                <button onclick="submitStory()" style="background:#FF6B1A; color:#fff; border:none; padding:10px 20px; border-radius:10px; font-weight:700; cursor:pointer;">Publier la story</button>
            </div>
        </div>
    </div>

    <!-- CHAT PANEL -->
    <div class="chat-panel" id="chatPanel">
        <div class="chat-header">
            <img src="" alt="" id="chatAvatar">
            <div>
                <div class="chat-name" id="chatName">—</div>
                <div class="chat-status" id="chatStatusTxt">—</div>
            </div>
            <button class="chat-close" onclick="closeChat()"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="chat-messages" id="chatMessages"></div>
        <div class="chat-footer">
            <input type="text" class="chat-input" id="chatInput" placeholder="Écrire un message...">
            <button class="send-btn" onclick="sendMessage()"><i class="bi bi-send-fill"></i></button>
        </div>
    </div>

    <!-- SOS MODAL -->
    <div class="sos-modal-overlay" id="sosModalOverlay">
        <div class="sos-modal">
            <div class="bi bi-exclamation-octagon-fill" style="font-size:64px; color:#ef4444; margin-bottom:16px;"></div>
            <h2 style="font-family:'Sora',sans-serif; font-size:24px; font-weight:800; color:#15233C; margin-bottom:8px;">ALERTE SOS</h2>
            <p style="font-size:14px; color:#64748b; margin-bottom:20px;">Vos contacts de confiance vont recevoir votre position et une alerte immédiate.</p>
            
            <div id="sosCountdownNum" style="font-size:56px; font-weight:900; color:#ef4444; margin-bottom:12px;">5</div>
            <div id="sosGPSStatus" style="font-size:12px; color:#15233C; font-weight:600; margin-bottom:24px;">📍 Récupération GPS...</div>

            <div style="display:flex; gap:12px;">
                <button class="action-btn" style="flex:1; background:#f1f5f9; border:none; padding:12px; border-radius:12px; font-weight:700;" onclick="cancelSOS()">ANNULER</button>
                <button id="sosConfirmBtn" class="action-btn accent" style="flex:1; padding:12px; border-radius:12px; font-weight:700;" onclick="confirmSOS()" disabled>CONFIRMER</button>
            </div>
        </div>
    </div>

    <script>
        let networkData = { friends: [], pending: [], suggestions: [] };
        let currentTab = 'friends';
        let activeChatId = null;
        let chatPollTimer = null;
        let sosLocation = { lat: null, lng: null };
        let sosCountdownTimer = null;

        async function init() {
            // Load agence
            try {
                const r = await fetch('search_agency_users.php?action=my_agence');
                const d = await r.json();
                if (d.success) document.getElementById('agenceName').textContent = d.nom_agence;
            } catch (e) { }

            await loadNetwork();
            searchUsers('');
        }

        // SEARCH
        let searchTimer = null;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => searchUsers(this.value.trim()), 300);
        });

        async function searchUsers(q) {
            const box = document.getElementById('searchResults');
            box.innerHTML = '<div style="text-align:center;padding:20px;"><i class="bi bi-arrow-repeat spin"></i> Recherche...</div>';
            try {
                const r = await fetch(`search_agency_users.php?action=search&q=${encodeURIComponent(q)}`);
                const d = await r.json();
                if (!d.success || !d.users || d.users.length === 0) {
                    box.innerHTML = '<div class="empty-state">Aucun client trouvé.</div>';
                    return;
                }
                box.innerHTML = d.users.map(u => renderUserItem(u, 'search')).join('');
            } catch (e) { box.innerHTML = '<div class="empty-state">Erreur réseau.</div>'; }
        }

        // NETWORK
        async function loadNetwork() {
            try {
                const r = await fetch('friends.php?action=list');
                const d = await r.json();
                if (d.success) {
                    networkData = d;
                    updateStats();
                    renderTab(currentTab);
                    updateTrustedCard();
                }
            } catch (e) { }
        }

        function updateStats() {
            const friends = networkData.friends || [];
            const pending = networkData.pending || [];
            const trusted = friends.filter(f => f.is_trusted == 1);
            const online = friends.filter(f => f.is_online == 1);

            document.getElementById('statFriends').textContent = friends.length;
            document.getElementById('statOnline').textContent = online.length;
            document.getElementById('statTrusted').textContent = trusted.length;
            document.getElementById('contactCount').textContent = `(${friends.length})`;

            const b = document.getElementById('pendingBadge');
            b.textContent = pending.length;
            b.style.display = pending.length > 0 ? 'flex' : 'none';
        }

        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.toggle('active', btn.id === `tab-${tab}`);
            });
            renderTab(tab);
        }

        function renderTab(tab) {
            const box = document.getElementById('contactsList');
            let users = [];
            if (tab === 'friends') users = networkData.friends || [];
            if (tab === 'pending') users = networkData.pending || [];
            if (tab === 'trusted') users = (networkData.friends || []).filter(f => f.is_trusted == 1);

            if (users.length === 0) {
                box.innerHTML = '<div class="empty-state"><i class="bi bi-inbox"></i>Liste vide.</div>';
                return;
            }
            box.innerHTML = users.map(u => renderUserItem(u, tab)).join('');
        }

        function renderUserItem(u, context) {
            const online = u.is_online == 1;
            const trusted = u.is_trusted == 1;
            let actions = '';

            if (context === 'pending') {
                actions = `
                    <button class="action-btn accent" onclick="handleFriend(${u.id_user}, 'accept')"><i class="bi bi-check-lg"></i></button>
                    <button class="action-btn danger" onclick="handleFriend(${u.id_user}, 'reject')"><i class="bi bi-x-lg"></i></button>
                `;
            } else if (u.rel_status === 'accepted' || context === 'friends' || context === 'trusted') {
                actions = `
                    <button class="action-btn gold ${trusted ? 'active' : ''}" onclick="toggleTrust(${u.id_user})"><i class="bi bi-star-fill"></i></button>
                    <button class="action-btn chat-active" onclick="openChat(${u.id_user}, '${escHtml(u.prenom)}', '${getAvatarUrl(u.avatar_url)}', ${online})"><i class="bi bi-chat-dots-fill"></i></button>
                    <button class="action-btn danger" onclick="handleFriend(${u.id_user}, 'remove')"><i class="bi bi-person-x"></i></button>
                `;
            } else if (u.rel_status === 'pending_sent') {
                actions = '<span class="status-txt">Attente...</span>';
            } else if (u.rel_status === 'pending_recv') {
                actions = `
                    <button class="action-btn accent" onclick="handleFriend(${u.id_user}, 'accept')"><i class="bi bi-check-lg"></i></button>
                    <button class="action-btn danger" onclick="handleFriend(${u.id_user}, 'reject')"><i class="bi bi-x-lg"></i></button>
                `;
            } else {
                actions = `<button class="action-btn accent" onclick="handleFriend(${u.id_user}, 'add')"><i class="bi bi-person-plus-fill"></i></button>`;
            }

            return `
                <div class="user-item">
                    <div class="avatar-wrap-net">
                        <img src="${getAvatarUrl(u.avatar_url)}" alt="">
                        <div class="online-dot ${online ? '' : 'offline'}"></div>
                    </div>
                    <div class="user-info">
                        <div class="user-name">${escHtml(u.prenom)} ${escHtml(u.nom)}</div>
                        <div class="user-meta">
                            <span class="role-pill">${u.role || 'Client'}</span>
                            <span class="status-txt ${online ? 'online' : ''}">${online ? 'En ligne' : 'Hors ligne'}</span>
                        </div>
                    </div>
                    <div class="user-actions">${actions}</div>
                </div>
            `;
        }

        function updateTrustedCard() {
            const box = document.getElementById('trustedList');
            const trusted = (networkData.friends || []).filter(f => f.is_trusted == 1);
            if (trusted.length === 0) {
                box.innerHTML = '<div style="font-size:11px; opacity:0.5; text-align:center;">Aucun contact de confiance.</div>';
                return;
            }
            box.innerHTML = trusted.map(u => `
                <div class="trusted-item">
                    <img src="${getAvatarUrl(u.avatar_url)}" alt="">
                    <span class="trusted-name">${escHtml(u.prenom)} ${escHtml(u.nom)}</span>
                    <i class="bi bi-shield-fill-check" style="color:#2ed573"></i>
                </div>
            `).join('');
        }

        async function handleFriend(id, action) {
            try {
                const r = await fetch('friends.php', { method: 'POST', body: JSON.stringify({ action, friend_id: id }) });
                const d = await r.json();
                if (d.success) loadNetwork();
                if (typeof showToast === 'function') showToast(d.message, d.success ? 'success' : 'warning');
            } catch (e) { }
        }

        async function toggleTrust(id) {
            try {
                const r = await fetch('sos.php', { method: 'POST', body: JSON.stringify({ action: 'toggle_trust', friend_id: id }) });
                const d = await r.json();
                if (d.success) loadNetwork();
            } catch (e) { }
        }

        // CHAT
        function openChat(userId, name, avatar, online) {
            activeChatId = userId;
            document.getElementById('chatName').textContent = name;
            document.getElementById('chatAvatar').src = avatar;
            document.getElementById('chatStatusTxt').textContent = online ? '🟢 En ligne' : '⚪ Hors ligne';
            document.getElementById('chatPanel').classList.add('open');
            loadMessages();
            clearInterval(chatPollTimer);
            chatPollTimer = setInterval(loadMessages, 4000);
        }
        function closeChat() { document.getElementById('chatPanel').classList.remove('open'); clearInterval(chatPollTimer); activeChatId = null; }

        async function loadMessages() {
            if (!activeChatId) return;
            const box = document.getElementById('chatMessages');
            try {
                const r = await fetch(`chat.php?action=fetch&friend_id=${activeChatId}`);
                const d = await r.json();
                if (!d.success) return;
                const atBottom = box.scrollTop + box.clientHeight >= box.scrollHeight - 20;
                box.innerHTML = d.messages.length === 0 ? '<div class="empty-state">Dites bonjour !</div>' : d.messages.map(m => `
                    <div class="msg-bubble ${m.is_mine == 1 ? 'mine' : 'theirs'}">
                        ${escHtml(m.content)}
                        <div style="font-size:9px; opacity:0.5; margin-top:4px; text-align:right;">${fmtTime(m.sent_at)}</div>
                    </div>
                `).join('');
                if (atBottom) box.scrollTop = box.scrollHeight;
            } catch (e) { }
        }

        async function sendMessage() {
            const input = document.getElementById('chatInput');
            const content = input.value.trim();
            if (!activeChatId || !content) return;
            input.value = '';
            try {
                await fetch('chat.php', { method: 'POST', body: JSON.stringify({ action: 'send', friend_id: activeChatId, content }) });
                loadMessages();
            } catch (e) { }
        }

        document.getElementById('chatInput').addEventListener('keydown', e => { if(e.key==='Enter') sendMessage(); });

        // SOS
        function openSOSModal() {
            const trusted = (networkData.friends || []).filter(f => f.is_trusted == 1);
            if (trusted.length === 0) return;
            document.getElementById('sosModalOverlay').classList.add('open');
            document.getElementById('sosConfirmBtn').disabled = true;
            let count = 5;
            document.getElementById('sosCountdownNum').textContent = count;
            clearInterval(sosCountdownTimer);
            sosCountdownTimer = setInterval(() => {
                count--;
                document.getElementById('sosCountdownNum').textContent = count;
                if (count <= 0) { clearInterval(sosCountdownTimer); document.getElementById('sosConfirmBtn').disabled = false; }
            }, 1000);

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => {
                    sosLocation = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                    document.getElementById('sosGPSStatus').textContent = '📍 Position GPS acquise.';
                }, () => { document.getElementById('sosGPSStatus').textContent = '⚠️ GPS non disponible.'; });
            }
        }
        function cancelSOS() { clearInterval(sosCountdownTimer); document.getElementById('sosModalOverlay').classList.remove('open'); }
        async function confirmSOS() {
            try {
                const r = await fetch('sos.php', { method: 'POST', body: JSON.stringify({ action: 'trigger', lat: sosLocation.lat, lng: sosLocation.lng }) });
                const d = await r.json();
                cancelSOS();
                if (typeof showToast === 'function') showToast(d.message, d.success ? 'success' : 'danger');
            } catch (e) { cancelSOS(); }
        }

        function getAvatarUrl(url) {
            if (!url || url === 'default.png') return 'default.png';
            if (url.startsWith('http') || url.includes('/')) return url;
            return '../../uploads/avatars/' + url;
        }
        function escHtml(s) { return String(s||'').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
        function fmtTime(s) { if(!s) return ''; const d = new Date(s); return d.toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'}); }

        init();
        setInterval(loadNetwork, 30000);

        // ══════════════════════════════════════════
        // R5: HEARTBEAT — keeps last_seen updated
        // ══════════════════════════════════════════
        function heartbeat() {
            fetch('../../api.php?action=heartbeat', { method: 'GET' }).catch(() => {});
        }
        heartbeat();
        setInterval(heartbeat, 30000);

        // ══════════════════════════════════════════
        // R3: POSTS FEED
        // ══════════════════════════════════════════
        let postImageFile = null;
        let activeHashtagFilter = '';

        async function loadFeed(hashtag = '') {
            const feed = document.getElementById('postsFeed');
            feed.innerHTML = '<div class="empty-state"><i class="bi bi-hourglass-split"></i>Chargement...</div>';
            const url = 'get_posts_client.php' + (hashtag ? '?hashtag=' + encodeURIComponent(hashtag) : '');
            try {
                const r = await fetch(url);
                const d = await r.json();
                if (!d.success || !d.data.length) {
                    feed.innerHTML = '<div class="empty-state"><i class="bi bi-newspaper"></i>Aucune publication pour le moment.</div>';
                    return;
                }
                feed.innerHTML = d.data.map(renderPost).join('');
            } catch (e) {
                feed.innerHTML = '<div class="empty-state"><i class="bi bi-exclamation-circle"></i>Erreur de chargement.</div>';
            }
        }

        function renderPost(p) {
            const initials = (p.auteur_nom || '?').split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
            const avatarHtml = p.avatar_url ? `<img src="${p.avatar_url}" alt="">` : `<img src="../../uploads/avatars/${p.avatar}" alt="" onerror="this.style.display='none'">`;
            const imageHtml = p.media_url ? `<img class="post-image" src="../../${p.media_url}" alt="Image du post">` : '';
            const reactions = [
                { type: 'like', emoji: '👍', count: p.react_like || 0 },
                { type: 'love', emoji: '❤️', count: p.react_love || 0 },
                { type: 'wow',  emoji: '😮', count: p.react_wow  || 0 },
                { type: 'sad',  emoji: '😢', count: p.react_sad  || 0 },
            ];

            const reactionBtns = reactions.map(r => `
                <button class="react-btn ${p.my_reaction === r.type ? 'active' : ''}" onclick="toggleReaction(${p.id_poste}, '${r.type}', this)">
                    ${r.emoji} <span class="react-count-${r.type}-${p.id_poste}">${r.count > 0 ? r.count : ''}</span>
                </button>
            `).join('');

            const totalReactions = reactions.reduce((s, r) => s + parseInt(r.count), 0);
            const summaryStr = reactions.filter(r => parseInt(r.count) > 0).map(r => `${r.emoji} ${r.count}`).join('  ');

            const dateStr = p.date_publication ? new Date(p.date_publication).toLocaleDateString('fr-FR', { day:'numeric', month:'short', hour:'2-digit', minute:'2-digit' }) : '';

            return `
            <div class="post-card" id="post-${p.id_poste}">
                <div class="post-head">
                    <div class="post-avatar">${avatarHtml}<span>${initials}</span></div>
                    <div>
                        <div class="post-author-name">${escHtml(p.auteur_nom || 'Utilisateur')}</div>
                        <div class="post-date">${dateStr}</div>
                    </div>
                </div>
                ${imageHtml}
                <div class="post-body">${p.contenu_html || escHtml(p.contenu)}</div>
                <div class="reactions-bar">
                    ${reactionBtns}
                    ${totalReactions > 0 ? `<span class="react-summary">${summaryStr}</span>` : ''}
                </div>
            </div>`;
        }

        async function toggleReaction(idPost, type, btn) {
            const isActive = btn.classList.contains('active');
            const fd = new FormData();
            fd.append('id_post', idPost);

            if (isActive) {
                await fetch('../../api.php?action=remove_reaction', { method: 'POST', body: fd });
            } else {
                fd.append('type', type);
                await fetch('../../api.php?action=add_reaction', { method: 'POST', body: fd });
            }
            loadFeed(activeHashtagFilter);
        }

        function filterByHashtag(word) {
            activeHashtagFilter = word;
            document.getElementById('hashtagFilterBanner').style.display = 'block';
            document.getElementById('activeHashtag').textContent = '#' + word;
            loadFeed(word);
        }

        function clearHashtag() {
            activeHashtagFilter = '';
            document.getElementById('hashtagFilterBanner').style.display = 'none';
            loadFeed('');
        }

        // Image Composer
        function toggleImgZone() {
            const zone = document.getElementById('imgDropZone');
            zone.classList.toggle('show');
        }

        function handleImgSelect(input) {
            if (input.files && input.files[0]) {
                postImageFile = input.files[0];
                const reader = new FileReader();
                reader.onload = e => {
                    document.getElementById('imgPreview').src = e.target.result;
                    document.getElementById('imgPreviewWrap').style.display = 'inline-block';
                };
                reader.readAsDataURL(postImageFile);
            }
        }

        function handleImgDrop(event) {
            event.preventDefault();
            document.getElementById('imgDropZone').classList.remove('drag-over');
            const file = event.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                postImageFile = file;
                const reader = new FileReader();
                reader.onload = e => {
                    document.getElementById('imgPreview').src = e.target.result;
                    document.getElementById('imgPreviewWrap').style.display = 'inline-block';
                };
                reader.readAsDataURL(file);
            }
        }

        function removeImg() {
            postImageFile = null;
            document.getElementById('imgPreview').src = '';
            document.getElementById('imgPreviewWrap').style.display = 'none';
        }

        async function submitPost() {
            const content = document.getElementById('postContent').value.trim();
            if (!content) { alert('Veuillez écrire quelque chose.'); return; }

            const fd = new FormData();
            fd.append('contenu', content);
            if (postImageFile) fd.append('image', postImageFile);

            try {
                const r = await fetch('save_post_client.php', { method: 'POST', body: fd });
                const d = await r.json();
                if (d.success) {
                    document.getElementById('postContent').value = '';
                    removeImg();
                    document.getElementById('imgDropZone').classList.remove('show');
                    loadFeed(activeHashtagFilter);
                } else {
                    alert(d.error || 'Erreur lors de la publication.');
                }
            } catch (e) {
                alert('Erreur réseau.');
            }
        }

        // ══════════════════════════════════════════
        // R4: FRIEND SUGGESTIONS
        // ══════════════════════════════════════════
        async function loadSuggestions() {
            try {
                const r = await fetch('../../api.php?action=suggestions_amis&limit=5');
                const d = await r.json();
                const list = document.getElementById('suggestionsList');
                if (!d.success || !d.data || !d.data.length) {
                    document.getElementById('suggestCard').style.display = 'none';
                    return;
                }
                list.innerHTML = d.data.map(u => {
                    const initials = ((u.prenom||'?')[0] + (u.nom||'?')[0]).toUpperCase();
                    return `
                    <div class="suggest-user">
                        <div class="suggest-init">${initials}</div>
                        <div>
                            <div class="suggest-name">${escHtml(u.prenom)} ${escHtml(u.nom)}</div>
                            <div class="suggest-meta">Même agence</div>
                        </div>
                        <button class="suggest-invite-btn" onclick="handleFriend(${u.id_user}, 'add')">Inviter</button>
                    </div>`;
                }).join('');
            } catch (e) {}
        }

        // ══════════════════════════════════════════
        // R6: STORIES
        // ══════════════════════════════════════════
        let allStories = [];
        let currentStoryIndex = 0;
        let storyTimer = null;

        async function loadStories() {
            try {
                const r = await fetch('../../api.php?action=get_stories');
                const d = await r.json();
                if (!d.success) return;
                allStories = d.data;
                renderStoriesBar(d.data);
            } catch (e) {}
        }

        function renderStoriesBar(stories) {
            const bar = document.getElementById('storiesBar');
            // Keep the "my story" bubble
            const myBubble = document.getElementById('myStoryBubble');
            bar.innerHTML = '';
            bar.appendChild(myBubble);

            // Group by user
            const byUser = {};
            stories.forEach(s => {
                if (!byUser[s.id_user]) byUser[s.id_user] = [];
                byUser[s.id_user].push(s);
            });

            Object.entries(byUser).forEach(([uid, userStories]) => {
                const s = userStories[0];
                const initials = ((s.prenom||'?')[0] + (s.nom||'?')[0]).toUpperCase();
                const bubble = document.createElement('div');
                bubble.className = 'story-bubble';
                bubble.onclick = () => openStoryViewer(userStories);
                bubble.innerHTML = `
                    <div class="story-ring">
                        <div class="story-init">${initials}</div>
                    </div>
                    <span class="story-label">${escHtml(s.prenom)}</span>
                `;
                bar.appendChild(bubble);
            });
        }

        function openStoryViewer(stories) {
            allStories = stories;
            currentStoryIndex = 0;
            document.getElementById('storyViewer').classList.add('open');
            showStory(0);
        }

        function showStory(idx) {
            if (idx < 0 || idx >= allStories.length) { closeStoryViewer(); return; }
            currentStoryIndex = idx;
            const s = allStories[idx];
            document.getElementById('svImg').src = s.media_url ? '../../' + s.media_url : '';
            document.getElementById('svImg').style.display = s.media_url ? 'block' : 'none';
            document.getElementById('svText').textContent = s.contenu || '';
            document.getElementById('svAuthorName').textContent = (s.prenom || '') + ' ' + (s.nom || '');
            document.getElementById('svAuthorAvatar').src = s.avatar_url || '';

            // Marquer comme vue
            if (s.id && s.vu == 0) {
                const fd = new FormData();
                fd.append('id_story', s.id);
                fetch('../../api.php?action=mark_story_seen', { method: 'POST', body: fd }).catch(() => {});
            }

            // Progress bars
            const pbContainer = document.getElementById('storyProgressBars');
            pbContainer.innerHTML = allStories.map((_, i) =>
                `<div class="story-progress-bar"><div class="story-progress-fill ${i < idx ? 'done' : ''}" id="spf-${i}"></div></div>`
            ).join('');
            clearTimeout(storyTimer);
            setTimeout(() => {
                const fill = document.getElementById('spf-' + idx);
                if (fill) fill.style.width = '100%';
            }, 50);
            storyTimer = setTimeout(() => nextStory(), 5000);
        }

        function nextStory() { showStory(currentStoryIndex + 1); }
        function prevStory() { showStory(currentStoryIndex - 1); }
        function closeStoryViewer() {
            clearTimeout(storyTimer);
            document.getElementById('storyViewer').classList.remove('open');
        }

        function openStoryComposer() {
            const m = document.getElementById('storyComposerModal');
            m.style.display = 'flex';
        }
        function closeStoryComposer() {
            document.getElementById('storyComposerModal').style.display = 'none';
        }

        async function submitStory() {
            const file = document.getElementById('storyFile').files[0];
            const text = document.getElementById('storyText').value.trim();
            if (!file && !text) { alert('Ajoutez une image ou du texte.'); return; }

            const fd = new FormData();
            if (file) fd.append('media', file);
            if (text) fd.append('contenu', text);

            try {
                const r = await fetch('../../api.php?action=add_story', { method: 'POST', body: fd });
                const d = await r.json();
                if (d.success) {
                    closeStoryComposer();
                    loadStories();
                } else {
                    alert(d.error || 'Erreur lors de l\'ajout de la story.');
                }
            } catch (e) { alert('Erreur réseau.'); }
        }

        // Init R3-R6
        loadFeed();
        loadSuggestions();
        loadStories();
    </script>
    <script src="assets_sinistre_traitement/js/main.js"></script>
</body>
</html>
