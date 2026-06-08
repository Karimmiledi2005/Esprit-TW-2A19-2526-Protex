<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Mes Sinistres — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <style>
        /* ===== TOAST ===== */
        .toast-notif {
            position: fixed; bottom: 24px; right: 24px;
            background: #fff; border: 1px solid rgba(26,58,122,0.15);
            border-radius: 12px; padding: 14px 20px;
            display: flex; align-items: center; gap: 10px;
            font-size: 14px; color: #15233C;
            z-index: 9999; opacity: 0; transform: translateY(10px);
            transition: all 0.3s ease; box-shadow: 0 8px 24px rgba(26,58,122,0.12);
        }
        .toast-notif.show { opacity: 1; transform: translateY(0); }
        .toast-notif i { font-size: 18px; }
        .toast-success i { color: #1A3A7A; }
        .toast-warning i { color: #FF6B1A; }
        .toast-danger  i { color: var(--danger); }

        /* ===== MONTANT BANNER ===== */
        .montant-banner {
            background: linear-gradient(135deg, rgba(26,58,122,0.08), rgba(26,58,122,0.04));
            border: 1px solid rgba(26,58,122,0.15); border-radius: 12px;
            padding: 14px 18px; display: flex; align-items: center; gap: 14px; margin-bottom: 14px;
        }
        .montant-banner i { font-size: 26px; color: #1A3A7A; }
        .montant-banner-label { font-size: 11px; color: var(--text-secondary); }
        .montant-banner-amount { font-size: 22px; font-weight: 700; color: #1A3A7A; }

        /* ===== COMMENTS ===== */
        .comment-section { margin-top: 14px; }
        .comment-title { font-size: 13px; font-weight: 500; color: #15233C; margin-bottom: 10px; }
        .comment-item {
            background: rgba(26,58,122,0.04); border: 1px solid rgba(26,58,122,0.10);
            border-radius: 10px; padding: 10px 12px; margin-bottom: 8px;
        }
        .comment-meta { font-size: 11px; color: var(--text-secondary); margin-bottom: 4px; }
        .comment-text { font-size: 13px; color: #15233C; }
        .comment-form { display: flex; gap: 8px; margin-top: 10px; }
        .comment-input {
            flex: 1; padding: 10px 12px; border-radius: 10px;
            background: #f0f4ff; border: 1px solid rgba(26,58,122,0.15);
            color: #15233C; font-size: 13px; outline: none; font-family: inherit; transition: 0.2s;
        }
        .comment-input:focus { border-color: #FF6B1A; box-shadow: 0 0 10px rgba(255,107,26,0.15); }
        .comment-input::placeholder { color: rgba(21,35,60,0.35); }
        .comment-send {
            padding: 10px 14px; background: #FF6B1A; border: none;
            border-radius: 10px; color: #fff; cursor: pointer; font-size: 13px; transition: 0.2s;
        }
        .comment-send:hover { transform: translateY(-1px); background: #e05a0f; }

        /* ===== TRAITEMENT ===== */
        .traitement-item {
            border: 1px solid rgba(26,58,122,0.10); border-radius: 12px;
            padding: 14px; margin-bottom: 12px; background: rgba(26,58,122,0.03);
        }
        .traitement-item.traitement-final { border-color: rgba(26,58,122,0.25); background: rgba(26,58,122,0.06); }
        .traitement-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .traitement-step { display: flex; align-items: center; gap: 10px; }
        .traitement-num {
            width: 28px; height: 28px; border-radius: 8px;
            background: rgba(255,107,26,0.12); color: #FF6B1A;
            font-size: 11px; font-weight: 700;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .traitement-num.success { background: rgba(26,58,122,0.12); color: #1A3A7A; }
        .traitement-title-t { font-size: 13px; font-weight: 500; color: #15233C; line-height: 1.4; }
        .traitement-date { font-size: 11px; color: var(--text-secondary); white-space: nowrap; }
        .traitement-body { display: flex; flex-direction: column; gap: 6px; padding-left: 38px; }
        .traitement-row { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--text-secondary); }
        .traitement-row i { font-size: 13px; color: #FF6B1A; flex-shrink: 0; }
        .traitement-row.success-text { color: #1A3A7A; }
        .traitement-row.success-text i { color: #1A3A7A; }
        .traitement-row strong { color: #15233C; font-weight: 500; }

        /* ===== SELECTED ===== */
        .sinistre-box.selected { border-color: #FF6B1A !important; }

        /* ===== CONFIRM DELETE ===== */
        .confirm-modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(26,58,122,0.35); backdrop-filter: blur(6px);
            z-index: 9998; align-items: center; justify-content: center;
        }
        .confirm-modal-overlay.open { display: flex; }
        .confirm-box {
            background: #fff; border: 1px solid rgba(230,57,70,0.25);
            border-radius: 16px; padding: 28px; width: 360px;
            box-shadow: 0 20px 50px rgba(26,58,122,0.2); text-align: center;
            animation: popIn 0.2s ease;
        }
        @keyframes popIn { from { transform: scale(0.9); opacity:0; } to { transform: scale(1); opacity:1; } }
        .confirm-icon { font-size: 36px; color: var(--danger); margin-bottom: 12px; }
        .confirm-title { font-size: 16px; font-weight: 600; color: #15233C; margin-bottom: 8px; }
        .confirm-sub { font-size: 13px; color: var(--text-secondary); margin-bottom: 20px; }
        .confirm-actions { display: flex; gap: 10px; }
        .btn-confirm-cancel {
            flex: 1; padding: 11px; border-radius: 10px;
            background: rgba(26,58,122,0.06); border: 1px solid rgba(26,58,122,0.12);
            color: var(--text-secondary); cursor: pointer; font-size: 14px; transition: 0.2s;
        }
        .btn-confirm-cancel:hover { background: rgba(26,58,122,0.10); }
        .btn-confirm-delete {
            flex: 1; padding: 11px; border-radius: 10px;
            background: linear-gradient(135deg, var(--danger), #f55); border: none;
            color: #fff; cursor: pointer; font-size: 14px; font-weight: 500; transition: 0.2s;
        }
        .btn-confirm-delete:hover { transform: translateY(-1px); }

        /* ===== MODAL — LIGHT THEME ===== */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(26,58,122,0.30);
            backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
            justify-content: center; align-items: center; z-index: 9999;
        }
        .modal-box {
            width: 460px; max-width: 94vw; padding: 28px; border-radius: 18px;
            background: #ffffff;
            border: 1px solid rgba(26,58,122,0.15);
            box-shadow: 0 20px 60px rgba(26,58,122,0.18);
            color: #15233C; position: relative;
            max-height: 90vh; overflow-y: auto;
            animation: glassPop 0.25s ease;
        }
        @keyframes glassPop { from { transform: scale(0.92); opacity:0; } to { transform: scale(1); opacity:1; } }

        .modal-box h3 { color: #15233C; font-size: 18px; font-weight: 700; margin-bottom: 20px; }

        .modal-box label {
            display: block; font-size: 13px; font-weight: 500;
            color: #15233C; margin-bottom: 6px;
        }

        .form-control {
            width: 100%; padding: 11px 14px; margin-top: 0; margin-bottom: 14px;
            border-radius: 11px;
            background: #f0f4ff;
            border: 1px solid rgba(26,58,122,0.15);
            color: #15233C; outline: none;
            font-family: inherit; font-size: 13px; transition: 0.2s;
            box-sizing: border-box;
        }
        .form-control:hover { border-color: rgba(26,58,122,0.3); }
        .form-control:focus {
            border-color: #FF6B1A;
            box-shadow: 0 0 12px rgba(255,107,26,0.15);
            background: #fff;
        }
        .form-control::placeholder { color: rgba(21,35,60,0.35); }

        select.form-control {
            appearance: none; cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%2315233C' viewBox='0 0 24 24'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 12px center;
            background-size: 14px; padding-right: 38px;
            background-color: #f0f4ff;
        }
        select.form-control option { background: #ffffff; color: #15233C; }

        textarea.form-control { resize: vertical; }

        .btn-submit {
            width: 100%; padding: 12px; border-radius: 11px; border: none;
            background: #FF6B1A; color: #fff; font-weight: 600; font-size: 14px;
            cursor: pointer; transition: 0.25s ease; margin-top: 4px;
        }
        .btn-submit:hover {
            background: #e05a0f;
            box-shadow: 0 6px 20px rgba(255,107,26,0.3);
            transform: translateY(-1px);
        }

        .close-btn {
            position: absolute; top: 14px; right: 14px;
            width: 32px; height: 32px; border-radius: 10px;
            background: rgba(26,58,122,0.08); border: 1px solid rgba(26,58,122,0.12);
            color: #15233C; cursor: pointer; transition: 0.2s;
            display: flex; align-items: center; justify-content: center; font-size: 15px;
        }
        .close-btn:hover { transform: rotate(90deg); background: rgba(26,58,122,0.14); }

        /* ===== ANIMATED FIELDS ===== */
        .animated-field { opacity: 0; transform: translateY(10px); animation: fadeUp 0.5s ease forwards; }
        .animated-field:nth-child(1) { animation-delay: 0.05s; }
        .animated-field:nth-child(2) { animation-delay: 0.12s; }
        .animated-field:nth-child(3) { animation-delay: 0.19s; }
        .animated-field:nth-child(4) { animation-delay: 0.26s; }
        .animated-field:nth-child(5) { animation-delay: 0.33s; }
        @keyframes fadeUp { to { opacity:1; transform:translateY(0); } }

        /* ===== BADGE EXTRA ===== */
        .badge-info   { background: rgba(255,107,26,0.10); color: #FF6B1A; }
        .badge-danger { background: rgba(230,57,70,0.10);  color: var(--danger); }
        .badge-fraud  { 
            background: linear-gradient(135deg, #000, #444); 
            color: #fff; 
            border: 1px solid #FFD700;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .fraud-banner {
            background: rgba(0, 0, 0, 0.05);
            border: 2px dashed #000;
            border-radius: 12px;
            padding: 15px;
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: pulse-border 2s infinite;
        }
        @keyframes pulse-border {
            0% { border-color: #000; }
            50% { border-color: #f00; }
            100% { border-color: #000; }
        }
        .fraud-banner i { font-size: 30px; color: #000; }
        .fraud-title { font-weight: 700; color: #000; font-size: 14px; margin-bottom: 2px; }
        .fraud-desc { font-size: 12px; color: #444; line-height: 1.4; }

        /* ===== STEPPER ===== */
        .stepper {
            display: flex; justify-content: space-between;
            margin-bottom: 24px; position: relative; padding: 0 5px;
        }
        .stepper::before {
            content: ''; position: absolute; top: 15px; left: 15px; right: 15px;
            height: 2px; background: rgba(26,58,122,0.08); z-index: 1;
        }
        .step {
            position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; gap: 6px;
            width: 70px;
        }
        .step-circle {
            width: 30px; height: 30px; border-radius: 50%;
            background: #fff; border: 2px solid rgba(26,58,122,0.15);
            color: var(--text-secondary); font-size: 13px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.3s ease;
        }
        .step.active .step-circle {
            border-color: #FF6B1A; background: #FF6B1A; color: #fff;
            box-shadow: 0 0 12px rgba(255,107,26,0.25);
        }
        .step.completed .step-circle {
            border-color: #FF6B1A; background: #fff; color: #FF6B1A;
        }
        .step-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary); text-align: center; }
        .step.active .step-label { color: #15233C; }

        /* ===== STEP NAVIGATION ===== */
        .step-content { display: none; }
        .step-content.active { display: block; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

        .step-actions {
            display: flex; justify-content: space-between; gap: 10px; margin-top: 24px;
            padding-top: 20px; border-top: 1px solid rgba(26,58,122,0.08);
        }
        .btn-step {
            padding: 11px 20px; border-radius: 10px; font-size: 13px; font-weight: 600;
            cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px;
            font-family: inherit;
        }
        .btn-prev { background: rgba(26,58,122,0.05); color: #1A3A7A; border: 1px solid rgba(26,58,122,0.1); }
        .btn-prev:hover { background: rgba(26,58,122,0.1); }
        .btn-next { background: #1A3A7A; color: #fff; border: none; flex: 1; justify-content: center; }
        .btn-next:hover { background: #15233C; transform: translateY(-1px); }

        /* ===== SUMMARY VIEW ===== */
        .summary-box {
            background: #f8f9ff; border-radius: 12px; padding: 16px; border: 1px solid rgba(26,58,122,0.08);
        }
        .summary-item { margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(26,58,122,0.05); }
        .summary-item:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
        .summary-label { font-size: 9px; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 2px; font-weight: 700; letter-spacing: 0.5px; }
        .summary-val { font-size: 13px; color: #15233C; font-weight: 500; display: flex; align-items: center; gap: 6px; }

        /* ===== LIGHTBOX ===== */
        .lightbox {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 35, 60, 0.95);
            display: none; align-items: center; justify-content: center;
            z-index: 10000; padding: 40px; opacity: 0; transition: opacity 0.3s ease;
        }
        .lightbox.show { display: flex; opacity: 1; }
        .lightbox-content {
            position: relative; max-width: 90%; max-height: 90%;
            border-radius: 12px; box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            overflow: hidden; transform: scale(0.9); transition: transform 0.3s ease;
        }
        .lightbox.show .lightbox-content { transform: scale(1); }
        .lightbox-img { width: 100%; height: 100%; object-fit: contain; }
        .lightbox-close {
            position: absolute; top: 20px; right: 20px;
            width: 44px; height: 44px; border-radius: 50%;
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
            color: #fff; font-size: 24px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s;
        }
        .lightbox-close:hover { background: rgba(255,255,255,0.2); transform: rotate(90deg); }
        
        .preview-wrapper {
            position: relative; cursor: zoom-in;
            border-radius: 10px; overflow: hidden;
            transition: transform 0.2s;
            background: rgba(26,58,122,0.05);
        }
        .preview-wrapper:hover { transform: scale(1.02); }
        .preview-overlay {
            position: absolute; inset: 0;
            background: rgba(26,58,122,0.4);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.2s;
        }
        .preview-wrapper:hover .preview-overlay { opacity: 1; }
        .preview-overlay i { color: #fff; font-size: 20px; }

        .modal-upload-preview {
            display: none; margin-top: 10px;
            border-radius: 10px; overflow: hidden;
            border: 1px solid rgba(26,58,122,0.12);
            position: relative;
        }
        .modal-upload-preview img {
            width: 100%; max-height: 150px;
            object-fit: cover; display: block;
        }
        .modal-preview-remove {
            position: absolute; top: 8px; right: 8px;
            width: 24px; height: 24px; border-radius: 50%;
            background: rgba(230,57,70,0.9); border: none;
            color: #fff; cursor: pointer; display: flex;
            align-items: center; justify-content: center; font-size: 14px;
        }

        /* ── Tracker Timeline ────────────────────────────────────── */
        .tracker-steps {
            display: flex; gap: 0; position: relative; margin-bottom: 28px; margin-top: 10px;
        }
        .tracker-steps::before {
            content: ''; position: absolute; top: 22px; left: 22px; right: 22px;
            height: 4px; background: rgba(26,58,122,0.08); border-radius: 2px; z-index: 0;
        }
        .tracker-step {
            flex: 1; display: flex; flex-direction: column; align-items: center;
            gap: 8px; position: relative; z-index: 1;
        }
        .tracker-step-circle {
            width: 44px; height: 44px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; background: rgba(26,58,122,0.07);
            border: 3px solid rgba(26,58,122,0.12); color: var(--text-secondary);
            transition: all 0.4s ease;
        }
        .tracker-step.done .tracker-step-circle {
            background: var(--success, #2EC4B6); border-color: var(--success, #2EC4B6); color: #fff;
            box-shadow: 0 0 0 4px rgba(46,196,182,0.2);
        }
        .tracker-step.active .tracker-step-circle {
            background: #FF6B1A; border-color: #FF6B1A; color: #fff;
            box-shadow: 0 0 0 6px rgba(255,107,26,0.18);
            animation: activePulse 1.8s ease-in-out infinite;
        }
        .tracker-step.refused .tracker-step-circle {
            background: var(--danger, #e63946); border-color: var(--danger, #e63946); color: #fff;
        }
        @keyframes activePulse {
            0%, 100% { box-shadow: 0 0 0 6px rgba(255,107,26,0.18); }
            50%       { box-shadow: 0 0 0 12px rgba(255,107,26,0.06); }
        }
        .tracker-step-label {
            font-size: 10px; font-weight: 700; text-align: center; color: var(--text-secondary);
            text-transform: uppercase; letter-spacing: 0.05em; line-height: 1.3;
        }
        .tracker-step.done   .tracker-step-label { color: var(--success, #2EC4B6); }
        .tracker-step.active .tracker-step-label { color: #FF6B1A; }
        .tracker-step.refused .tracker-step-label { color: var(--danger, #e63946); }
        .tracker-progress {
            position: absolute; top: 22px; left: 22px; height: 4px;
            background: linear-gradient(90deg, var(--success, #2EC4B6), #FF6B1A);
            border-radius: 2px; z-index: 0; transition: width 0.6s ease;
        }

        .tracker-info-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 20px;
        }
        @media(max-width: 600px) { .tracker-info-grid { grid-template-columns: 1fr; } }
        .tracker-info-box {
            background: rgba(26,58,122,0.03); border: 1px solid rgba(26,58,122,0.15);
            border-radius: 12px; padding: 16px;
        }
        .tracker-info-box-title {
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.07em; color: var(--text-secondary); margin-bottom: 10px;
        }
        .tracker-checklist { list-style: none; padding: 0; margin: 0; }
        .tracker-checklist li {
            display: flex; align-items: center; gap: 9px; font-size: 13px;
            color: #15233C; padding: 5px 0; border-bottom: 1px solid rgba(26,58,122,0.05);
        }
        .tracker-checklist li:last-child { border-bottom: none; }
        .tracker-checklist li i { font-size: 14px; flex-shrink: 0; }
        .chk-ok   { color: var(--success, #2EC4B6); }
        .chk-miss { color: var(--text-secondary); }
    </style>

    <!-- FrontOffice unifie - surcharge thème camarades dark-navy -->
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css"></head>
<body>

<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">

    <!-- ===== NAVBAR ===== -->
    <?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

    <!-- ===== MAIN ===== -->
    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title-main">Mes Sinistres</div>
                <div class="page-breadcrumb">
                    <i class="bi bi-house"></i>
                    <a href="client.php" style="color:inherit;text-decoration:none;">Accueil</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <span>Mes Sinistres</span>
                </div>
            </div>
        </div>

        <div class="content">

            <div class="section-header fade-in">
                <div>
                    <div class="section-title">Mes sinistres</div>
                    <div class="section-sub">Déclarer et suivre vos demandes</div>
                </div>
                <button class="btn btn-primary" onclick="openDeclareModal()">
                    <i class="bi bi-plus-lg"></i> Déclarer
                </button>
            </div>

            <!-- MODAL DECLARE / EDIT -->
            <div id="sinistreModal" class="modal-overlay">
                <div class="modal-box">
                    <h3 id="modalTitle" style="margin-bottom:24px;">Déclarer un sinistre</h3>
                    
                    <!-- STEPPER -->
                    <div class="stepper">
                        <div class="step active" id="mStep1-head">
                            <div class="step-circle">1</div>
                            <div class="step-label">Infos</div>
                        </div>
                        <div class="step" id="mStep2-head">
                            <div class="step-circle">2</div>
                            <div class="step-label">Docs</div>
                        </div>
                        <div class="step" id="mStep3-head">
                            <div class="step-circle">3</div>
                            <div class="step-label">Vérif</div>
                        </div>
                    </div>

                    <form id="sinistreFormElement" onsubmit="handleSinistreSubmit(event)">
                        
                        <!-- STEP 1: INFOS -->
                        <div class="step-content active" id="mStep1">
                            <div class="form-group animated-field">
                                <label>Votre contrat</label>
                                 <select id="contrat_id" class="form-control" onchange="onContratChange()">
                                     <option value="">Chargement de vos contrats...</option>
                                 </select>
                                 <span class="field-error" id="err_contrat"></span>
                            </div>
                            <div class="form-group animated-field">
                                <label>Type de sinistre</label>
                                <select id="type_sinistre" class="form-control">
                                    <option value="">Choisissez d'abord un contrat</option>
                                </select>
                                <span class="field-error" id="err_type"></span>
                            </div>
                            <div class="form-group animated-field">
                                <label>Date de déclaration</label>
                                <input type="date" id="date_declaration" class="form-control" readonly>
                            </div>

                            <div class="step-actions">
                                <button type="button" class="btn-step btn-next" onclick="mNextStep(2)">
                                    Suivant <i class="bi bi-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- STEP 2: DOCUMENTS -->
                        <div class="step-content" id="mStep2">
                            <div class="form-group animated-field">
                                <label>Description précise</label>
                                <textarea id="description" class="form-control" rows="4" placeholder="Décrivez les circonstances..."></textarea>
                                <span class="field-error" id="err_desc"></span>
                            </div>

                            <div class="form-group animated-field">
                                <label>Justificatifs (photos/PDF, max 5)</label>
                                <input type="file" id="photo" class="form-control" multiple accept="image/jpeg,image/png,image/webp,application/pdf" onchange="onModalFileChange(this)">
                                <span class="field-error" id="err_photo"></span>
                                <div class="modal-upload-preview-grid" id="mUploadPreviewGrid" style="display:none; margin-top:10px; display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 8px;">
                                </div>
                                <div style="font-size:11px;color:var(--text-secondary);margin-top:4px">Ajoutez une photo de l'accident (l'IA utilise la description + la photo en contexte)</div>
                            </div>

                            <!-- ══ BLOC ESTIMATION IA ══ -->
                            <div id="ai-estimator-section" style="display:none">
                              <div style="background:rgba(0,180,216,0.06);border:1px solid rgba(0,180,216,0.2);border-radius:12px;padding:1rem;margin-top:1rem">
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:.75rem">
                                  <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,var(--accent),#7c3aed);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">🤖</div>
                                  <div style="font-size:13px;font-weight:600;color:#15233C">Estimation IA réparation auto</div>
                                  <button type="button" id="btn-ai-estimate" onclick="runAiEstimate()" style="margin-left:auto;padding:6px 14px;border-radius:8px;border:none;background:linear-gradient(135deg,var(--accent),#7c3aed);color:#fff;font-size:12px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:5px;white-space:nowrap">
                                    <i class="bi bi-stars"></i> Estimer
                                  </button>
                                </div>
                                <div id="ai-photo-preview" style="display:none;margin-bottom:.75rem;text-align:center">
                                  <img id="ai-photo-img" style="max-width:100%;max-height:180px;border-radius:8px;border:1px solid rgba(26,58,122,0.1)">
                                </div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:.75rem">
                                  <div>
                                    <label style="font-size:11px;color:var(--text-secondary);display:block;margin-bottom:3px">Type d'accident</label>
                                    <select id="ai-accident-type" style="width:100%;background:rgba(26,58,122,0.05);border:1px solid rgba(26,58,122,0.15);border-radius:7px;padding:7px 10px;font-size:12px;color:#15233C;outline:none">
                                      <option value="collision_arriere">Collision arrière</option>
                                      <option value="collision_laterale">Collision latérale</option>
                                      <option value="collision_frontale">Collision frontale</option>
                                      <option value="stationnement">Choc stationnement</option>
                                      <option value="bris_glace">Bris de glace</option>
                                      <option value="vandalisme">Vandalisme</option>
                                      <option value="incendie">Incendie</option>
                                    </select>
                                  </div>
                                  <div>
                                    <label style="font-size:11px;color:var(--text-secondary);display:block;margin-bottom:3px">Gravité</label>
                                    <select id="ai-severity" style="width:100%;background:rgba(26,58,122,0.05);border:1px solid rgba(26,58,122,0.15);border-radius:7px;padding:7px 10px;font-size:12px;color:#15233C;outline:none">
                                      <option value="leger">Légère</option>
                                      <option value="modere" selected>Modérée</option>
                                      <option value="grave">Grave</option>
                                    </select>
                                  </div>
                                </div>
                                <div id="ai-result-area" style="display:none">
                                  <div id="ai-loading" style="text-align:center;padding:.75rem;display:none">
                                    <div style="display:flex;gap:5px;justify-content:center;margin-bottom:6px">
                                      <div style="width:6px;height:6px;border-radius:50%;background:var(--accent);animation:ai-dot 1.2s ease-in-out infinite"></div>
                                      <div style="width:6px;height:6px;border-radius:50%;background:var(--accent);animation:ai-dot 1.2s ease-in-out .2s infinite"></div>
                                      <div style="width:6px;height:6px;border-radius:50%;background:var(--accent);animation:ai-dot 1.2s ease-in-out .4s infinite"></div>
                                    </div>
                                    <div style="font-size:11px;color:var(--text-secondary)">Analyse photo et description…</div>
                                  </div>
                                  <div id="ai-result-main" style="display:none">
                                    <div style="display:flex;justify-content:space-between;align-items:center;background:rgba(26,58,122,0.06);border-radius:8px;padding:10px 14px;margin-bottom:.6rem">
                                      <div>
                                        <div style="font-size:10px;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.06em">Coût estimé</div>
                                        <div style="font-size:22px;font-weight:700;color:var(--accent)" id="ai-cost-display">—</div>
                                        <div style="font-size:10px;color:var(--text-secondary)" id="ai-range-display">—</div>
                                      </div>
                                      <div style="text-align:right">
                                        <div style="font-size:10px;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.06em">Remboursé</div>
                                        <div style="font-size:18px;font-weight:600;color:#22c55e" id="ai-remb-display">—</div>
                                        <div style="font-size:10px;color:var(--text-secondary)" id="ai-charge-display">À votre charge : —</div>
                                      </div>
                                    </div>
                                    <div style="margin-bottom:.6rem">
                                      <div style="display:flex;justify-content:space-between;font-size:10.5px;color:var(--text-secondary);margin-bottom:3px">
                                        <span>Taux de couverture</span>
                                        <span id="ai-coverage-pct">—%</span>
                                      </div>
                                      <div style="height:4px;background:rgba(26,58,122,0.07);border-radius:2px;overflow:hidden">
                                        <div id="ai-coverage-bar" style="height:100%;border-radius:2px;width:0;transition:width 1.2s ease;background:linear-gradient(90deg,#16a34a,#22c55e)"></div>
                                      </div>
                                    </div>
                                    <div style="background:rgba(124,58,237,0.08);border:1px solid rgba(124,58,237,0.2);border-radius:7px;padding:8px 10px;margin-bottom:.6rem">
                                      <div style="font-size:10px;color:#a78bfa;font-weight:600;margin-bottom:3px;display:flex;align-items:center;gap:4px">
                                        <i class="bi bi-robot"></i> Analyse Groq
                                      </div>
                                      <div style="font-size:11.5px;color:rgba(21,35,60,0.6);line-height:1.5" id="ai-analysis-text">—</div>
                                    </div>
                                    <div id="ai-flags" style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:.6rem"></div>
                                    <div id="ai-meta" style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:.6rem"></div>
                                    <button type="button" onclick="acceptAiEstimate(event)" style="width:100%;padding:8px;border-radius:7px;border:none;background:rgba(34,197,94,0.15);border:1px solid rgba(34,197,94,0.25);color:#22c55e;font-size:12px;font-weight:500;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px">
                                      <i class="bi bi-check-circle"></i> Accepter et annexer à ma déclaration
                                    </button>
                                    <input type="hidden" name="ai_cost_estimate"  id="hidden-ai-estimate">
                                    <input type="hidden" name="ai_cost_min"       id="hidden-ai-min">
                                    <input type="hidden" name="ai_cost_max"       id="hidden-ai-max">
                                    <input type="hidden" name="ai_remboursement"  id="hidden-ai-remb">
                                    <input type="hidden" name="ai_analysis"       id="hidden-ai-analysis">
                                  </div>
                                </div>
                              </div>
                            </div>
                            <style>@keyframes ai-dot{0%,100%{transform:translateY(0);opacity:1}50%{transform:translateY(-7px);opacity:.4}}</style>

                            <div class="step-actions">
                                <button type="button" class="btn-step btn-prev" onclick="mPrevStep(1)">
                                    <i class="bi bi-arrow-left"></i> Retour
                                </button>
                                <button type="button" class="btn-step btn-next" onclick="mNextStep(3)">
                                    Suivant <i class="bi bi-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- STEP 3: RÉSUMÉ -->
                        <div class="step-content" id="mStep3">
                            <div class="summary-box" id="mSummaryView" style="margin-bottom:10px;">
                                <!-- Populated by JS -->
                            </div>

                            <div class="step-actions">
                                <button type="button" class="btn-step btn-prev" onclick="mPrevStep(2)">
                                    <i class="bi bi-arrow-left"></i> Modif.
                                </button>
                                <button type="submit" class="btn-step btn-next" id="mBtnSubmit" style="background:#FF6B1A;">
                                    Confirmer l'envoi
                                </button>
                            </div>
                        </div>

                    </form>
                    <button class="close-btn" onclick="closeSinistreModal()">✕</button>
                </div>
            </div>

            <!-- CONFIRM DELETE -->
            <div class="confirm-modal-overlay" id="confirmDeleteModal">
                <div class="confirm-box">
                    <div class="confirm-icon"><i class="bi bi-exclamation-triangle"></i></div>
                    <div class="confirm-title">Supprimer ce sinistre ?</div>
                    <div class="confirm-sub">Cette action est irréversible.</div>
                    <div class="confirm-actions">
                        <button class="btn-confirm-cancel" onclick="closeConfirmDelete()">Annuler</button>
                        <button class="btn-confirm-delete" onclick="confirmDelete()">Supprimer</button>
                    </div>
                </div>
            </div>

            <!-- GRID -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:16px;">
                <div id="sinistreList"></div>
                <div id="detailPanel">
                    <div class="sinistre-box" style="text-align:center;padding:40px 20px;color:var(--text-secondary);">
                        <i class="bi bi-shield" style="font-size:36px;opacity:0.2;"></i>
                        <p style="margin-top:10px;font-size:13px;">Sélectionnez un sinistre</p>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<div id="photoLightbox" class="lightbox" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()"><i class="bi bi-x"></i></button>
    <div class="lightbox-content" onclick="event.stopPropagation()">
        <img id="lightboxImg" class="lightbox-img" src="" alt="Plein écran">
    </div>
</div>

<div class="toast-notif" id="toastNotif">
    <i class="bi bi-check-circle"></i>
    <span id="toastMsg"></span>
</div>

<script>
// ── URL du contrôleur PHP (chemin relatif depuis FrontOffice/) ──
const SINISTRE_API = 'sinistre_list_user.php';

let sinistres   = [];   // chargé depuis la BDD
let currentClient = null; // chargé via session
let editingId   = null;
let deletingId  = null;
let selectedId  = null;
let mCurrentStep = 1;
let selectedModalFiles = [];

// -- Modal Stepper Logic --------------------------------------------------------
function clearErrors() {
    document.querySelectorAll('.field-error').forEach(el => el.textContent = '');
    document.querySelectorAll('.form-control').forEach(el => el.classList.remove('input-error'));
}

// ── Photo Modal Logic ─────────────────────────────────────────────────────────
function onModalFileChange(input) {
    selectedModalFiles = Array.from(input.files).slice(0, 5);
    renderModalPreviews();
}

function renderModalPreviews() {
    const grid = document.getElementById('mUploadPreviewGrid');
    if (selectedModalFiles.length === 0) {
        grid.style.display = 'none';
        grid.innerHTML = '';
        return;
    }
    grid.style.display = 'grid';
    grid.innerHTML = '';
    selectedModalFiles.forEach((file, index) => {
        if (file.size > 5 * 1024 * 1024) {
            showToast('Fichier ' + file.name + ' trop volumineux (max 5 Mo).', 'warning');
            selectedModalFiles.splice(index, 1);
            return;
        }
        
        const wrapper = document.createElement('div');
        wrapper.style.position = 'relative';
        wrapper.style.borderRadius = '8px';
        wrapper.style.overflow = 'hidden';
        wrapper.style.border = '1px solid rgba(26,58,122,0.15)';
        wrapper.style.aspectRatio = '1';
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.innerHTML = '✕';
        removeBtn.style.position = 'absolute';
        removeBtn.style.top = '4px';
        removeBtn.style.right = '4px';
        removeBtn.style.background = 'rgba(230,57,70,0.9)';
        removeBtn.style.color = '#fff';
        removeBtn.style.border = 'none';
        removeBtn.style.borderRadius = '50%';
        removeBtn.style.width = '20px';
        removeBtn.style.height = '20px';
        removeBtn.style.fontSize = '10px';
        removeBtn.style.cursor = 'pointer';
        removeBtn.style.zIndex = '10';
        removeBtn.onclick = () => {
            selectedModalFiles.splice(index, 1);
            renderModalPreviews();
        };
        wrapper.appendChild(removeBtn);

        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'cover';
            
            const reader = new FileReader();
            reader.onload = e => { img.src = e.target.result; };
            reader.readAsDataURL(file);
            wrapper.appendChild(img);
        } else if (file.type === 'application/pdf') {
            const icon = document.createElement('div');
            icon.style.width = '100%';
            icon.style.height = '100%';
            icon.style.display = 'flex';
            icon.style.alignItems = 'center';
            icon.style.justifyContent = 'center';
            icon.style.background = '#f8f9ff';
            icon.style.color = '#e63946';
            icon.style.fontSize = '24px';
            icon.innerHTML = '<i class="bi bi-file-earmark-pdf-fill"></i>';
            wrapper.appendChild(icon);
        }
        grid.appendChild(wrapper);
    });
}

function removeModalPhoto() {
    document.getElementById('photo').value = '';
    selectedModalFiles = [];
    renderModalPreviews();
}

function openLightbox(specificSrc) {
    const src = specificSrc || document.getElementById('mPreviewImg').src;
    if (!src) return;
    document.getElementById('lightboxImg').src = src;
    document.getElementById('photoLightbox').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('photoLightbox').classList.remove('show');
    document.body.style.overflow = '';
}

function mNextStep(n) {
    clearErrors();
    if (n === 2) {
        const contrat = document.getElementById('contrat_id');
        const type = document.getElementById('type_sinistre');
        let ok = true;
        if (!contrat.value) { 
            document.getElementById('err_contrat').textContent = 'Veuillez choisir un contrat.';
            contrat.classList.add('input-error');
            ok = false;
        }
        if (!type.value) {
            document.getElementById('err_type').textContent = 'Veuillez choisir un type de sinistre.';
            type.classList.add('input-error');
            ok = false;
        }
        if (!ok) return;
    }
    if (n === 3) {
        const desc = document.getElementById('description');
        if (desc.value.trim().length < 10) {
            document.getElementById('err_desc').textContent = 'La description doit faire au moins 10 caractères.';
            desc.classList.add('input-error');
            return;
        }
        renderModalSummary();
    }
    mCurrentStep = n;
    updateModalStepperUI();
}

function mPrevStep(n) {
    mCurrentStep = n;
    updateModalStepperUI();
}

function updateModalStepperUI() {
    document.querySelectorAll('.step-content').forEach(s => s.classList.remove('active'));
    document.getElementById('mStep' + mCurrentStep).classList.add('active');

    document.querySelectorAll('.step').forEach((s, idx) => {
        const stepNum = idx + 1;
        s.classList.remove('active', 'completed');
        if (stepNum === mCurrentStep) s.classList.add('active');
        else if (stepNum < mCurrentStep) s.classList.add('completed');
    });
}

function renderModalSummary() {
    const sel = document.getElementById('contrat_id');
    const contratText = sel.options[sel.selectedIndex].text;
    const type = document.getElementById('type_sinistre').value;
    const desc = document.getElementById('description').value;
    const photo = document.getElementById('photo').files[0];

    document.getElementById('mSummaryView').innerHTML = `
        <div class="summary-item">
            <div class="summary-label">Contrat</div>
            <div class="summary-val"><i class="bi bi-file-earmark-text"></i> ${contratText}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Nature du sinistre</div>
            <div class="summary-val"><i class="bi bi-tag"></i> ${type}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Description</div>
            <div class="summary-val" style="display:block; line-height:1.4; color:var(--text-secondary);">${desc}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Pièces jointes</div>
            <div class="summary-val">
                <i class="bi bi-paperclip"></i> ${selectedModalFiles.length > 0 ? selectedModalFiles.map(f => f.name).join(', ') : '<span style="opacity:0.5;">Aucun fichier</span>'}
            </div>
        </div>
    `;
}

const STEP_CONFIG = [
    { key: 'declared', label: 'Déclaré',  icon: 'bi-file-earmark-plus' },
    { key: 'progress', label: 'En cours', icon: 'bi-search'            },
    { key: 'decision', label: 'Décision', icon: 'bi-gavel'             },
    { key: 'closed',   label: 'Clôturé',  icon: 'bi-check-circle'      },
];

const STATUS_TO_STEP = {
    'en_attente':   0,
    'en_analyse':   1,
    'assigne':      1,
    'en_cours':     1,
    'rembourse':    2,
    'refuse':       2,
    'cloture':      3,
};

function buildTrackerStepperHTML(statut) {
    const activeIdx = STATUS_TO_STEP[statut] ?? 0;
    const refused   = statut === 'refuse';
    const pct       = Math.max(0, Math.min(100, (activeIdx / (STEP_CONFIG.length - 1)) * 100));

    let stepsHTML = '';
    STEP_CONFIG.forEach((s, i) => {
        let cls = '';
        if (refused && i === activeIdx) cls = 'refused';
        else if (i < activeIdx)  cls = 'done';
        else if (i === activeIdx) cls = 'active';
        stepsHTML += `<div class="tracker-step ${cls}">
            <div class="tracker-step-circle"><i class="bi ${s.icon}"></i></div>
            <span class="tracker-step-label">${s.label}</span>
        </div>`;
    });

    return `<div class="tracker-steps">
        <div class="tracker-progress" style="width: calc(${pct}% - 44px);"></div>
        ${stepsHTML}
    </div>`;
}

function buildTrackerChecklist(statut, hasPhoto) {
    const items = [
        { label: 'Déclaration soumise',     done: true },
        { label: 'Photo / justificatif',     done: hasPhoto },
        { label: 'Analyse du dossier',       done: ['en_analyse','assigne','en_cours','rembourse','refuse','cloture'].includes(statut) },
        { label: 'Décision rendue',          done: ['rembourse','refuse','cloture'].includes(statut) },
        { label: 'Clôture du dossier',       done: statut === 'cloture' },
    ];
    return '<ul class="tracker-checklist">' + items.map(it =>
        `<li><i class="bi ${it.done ? 'bi-check-circle-fill chk-ok' : 'bi-circle chk-miss'}"></i>${it.label}</li>`
    ).join('') + '</ul>';
}

function buildFraudBadge(score, niveau) {
    if (!score && score !== 0) return '';
    const s = parseInt(score, 10);
    let cls = 'fraud-faible', label = '✅ Risque faible';
    let color = '#1a9e94', bg = 'rgba(46,196,182,0.12)';
    if (s >= 81) { cls = 'fraud-eleve'; label = '🔴 Fraude probable'; color = '#c42532'; bg = 'rgba(230,57,70,0.12)'; }
    else if (s >= 31) { cls = 'fraud-modere'; label = '⚠️ Risque modéré'; color = '#b97a00'; bg = 'rgba(239,159,39,0.12)'; }
    return `<div style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700; margin-top:12px; background:${bg}; color:${color};"><i class="bi bi-shield-check"></i> Score fraude : ${s}/100 — ${label}</div>`;
}

const STATUTS = {
    'en_attente': { label: 'En attente', css: 'badge-pending' },
    'en_analyse': { label: 'En analyse', css: 'badge-warning' },
    'assigne':    { label: 'Assigné',    css: 'badge-info' },
    'en_cours':   { label: 'En cours',   css: 'badge-info' },
    'rembourse':  { label: 'Remboursé',  css: 'badge-success' },
    'refuse':     { label: 'Refusé',     css: 'badge-danger' },
    'cloture':    { label: 'Clôturé',    css: 'badge-secondary' }
};

const TYPE_MAP = {
    'auto':       [
        { val: 'Accident auto',         label: 'Accident auto' },
        { val: 'Vol',                   label: 'Vol de véhicule' },
        { val: 'Bris de glace',         label: 'Bris de glace' },
        { val: 'Incendie',              label: 'Incendie véhicule' },
    ],
    'habitation': [
        { val: 'Incendie',              label: 'Incendie' },
        { val: 'Vol',                   label: 'Cambriolage / Vol' },
        { val: 'Dégât des eaux',        label: 'Dégât des eaux' },
        { val: 'Catastrophe naturelle', label: 'Catastrophe naturelle' },
    ],
    'vie':        [
        { val: 'Décès',                 label: 'Décès' },
        { val: 'Invalidité',            label: 'Invalidité' },
        { val: 'Hospitalisation',       label: 'Hospitalisation' },
    ],
    'sante':      [
        { val: 'Hospitalisation',       label: 'Hospitalisation' },
        { val: 'Accident',              label: 'Accident corporel' },
        { val: 'Maladie',               label: 'Maladie grave' },
    ],
    'protection': [
        { val: 'Vol',                   label: 'Vol / Vandalisme' },
        { val: 'Dégât des eaux',        label: 'Dégât des eaux' },
        { val: 'Incendie',              label: 'Incendie' },
        { val: 'Catastrophe naturelle', label: 'Catastrophe naturelle' },
    ],
    'default':    [
        { val: 'Accident auto',         label: 'Accident auto' },
        { val: 'Incendie',              label: 'Incendie' },
        { val: 'Vol',                   label: 'Vol' },
        { val: 'Dégât des eaux',        label: 'Dégât des eaux' },
    ],
};

function getContratTypeKey(c) {
    const raw = (c.type_contrat || c.nom_categorie || '').toLowerCase();
    if (raw === 'auto' || raw.includes('auto') || raw.includes('voiture') || raw.includes('vehicule')) return 'auto';
    if (raw === 'habitation' || raw.includes('habitation') || raw.includes('maison') || raw.includes('logement')) return 'habitation';
    if (raw === 'sante' || raw.includes('sante') || raw.includes('santé') || raw.includes('medical')) return 'sante';
    if (raw === 'protection' || raw.includes('protection')) return 'protection';
    if (raw.includes('vie') || raw.includes('deces') || raw.includes('décès')) return 'vie';
    return 'default';
}

function updateTypeOptions(contratId) {
    const sel = document.getElementById('type_sinistre');
    sel.innerHTML = '';
    const c = userContrats.find(x => x.id_contrat == contratId);
    const key = c ? getContratTypeKey(c) : 'default';
    const types = TYPE_MAP[key] || TYPE_MAP['default'];

    if (!contratId) {
        sel.innerHTML = '<option value="">Choisissez d\'abord un contrat</option>';
        return;
    }

    types.forEach(t => {
        const opt = document.createElement('option');
        opt.value = t.val;
        opt.textContent = t.label;
        sel.appendChild(opt);
    });
}

function onContratChange() {
    const val = document.getElementById('contrat_id').value;
    updateTypeOptions(val);
}

let userContrats = [];

// ── Charge les infos de session ──────────────────────────────────────────────
async function checkAuth() {
    try {
        const res = await fetch('get_user.php');
        if (res.status === 401) {
            window.location.href = 'login.html';
            return;
        }
        let user = await res.json();
        if (user.success && user.user) user = user.user;
        currentClient = user;

        loadSinistres();
        loadContracts(); // Charger les contrats pour le formulaire
    } catch (e) {
        console.error('Auth error:', e);
    }
}

// ── Charge les sinistres depuis la BDD ──────────────────────────────────────
async function loadSinistres() {
    if (!currentClient) return;
    try {
        const res = await fetch(SINISTRE_API + '?_=' + Date.now());
        const json = await res.json();
        // Le backend utilise maintenant la session, donc plus besoin de passer id_user
        if (Array.isArray(json)) {
            sinistres = json.map(s => ({
                id:          s.id_sinistre,
                id_contrat:  s.id_contrat,
                contrat:     s.id_contrat, // On utilisera l'ID si le numéro n'est pas dispo
                type:        s.type,
                description: s.description,
                date:        s.date_declaration,
                statut:      s.statut,
                photo:       s.photo_url ? '../../' + s.photo_url : '',
                fraud_score:  s.fraud_score,
                fraud_niveau: s.fraud_niveau,
                fraud_suggestion: s.fraud_suggestion,
                commentaires: [],
                traitements:  [],
            }));
            renderList();
        } else {
            showToast(json.message || 'Erreur chargement.', 'danger');
        }
    } catch (e) {
        showToast('Impossible de contacter le serveur PHP.', 'danger');
    }
}

// ── Charge les contrats du client ──────────────────────────────────────────
async function loadContracts() {
    try {
        const res = await fetch('contrat_list_client.php');
        const json = await res.json();
        const select = document.getElementById('contrat_id');
        if (json.success && Array.isArray(json.data)) {
            userContrats = json.data;
            if (json.data.length === 0) {
                select.innerHTML = '<option value="">Aucun contrat actif</option>';
            } else {
                select.innerHTML = '<option value="">— Sélectionnez un contrat —</option>' + json.data.map(c => 
                    `<option value="${c.id_contrat}">${c.type_contrat} — ${c.numero_contrat}</option>`
                ).join('');
            }
        } else {
            select.innerHTML = '<option value="">Erreur de chargement</option>';
        }
    } catch (e) {
        console.error('Load contracts error:', e);
        document.getElementById('contrat_id').innerHTML = '<option value="">Erreur de chargement</option>';
    }
}

function fmt(d) { if(!d) return '—'; const [y,m,day]=d.split('-'); return `${day}/${m}/${y}`; }

function renderList() {
    const el = document.getElementById('sinistreList');
    if (!sinistres.length) { el.innerHTML = `<div style="text-align:center;padding:40px;color:var(--text-secondary);font-size:13px;">Aucun sinistre déclaré.</div>`; return; }
    el.innerHTML = sinistres.map(s => {
        const st = STATUTS[s.statut] || STATUTS.en_attente;
        const isFraud = s.statut === 'refuse' && s.fraud_niveau === 'fraude';
        const badgeLabel = isFraud ? 'Refusé (Fraude)' : st.label;
        const badgeClass = isFraud ? 'badge-fraud' : st.css;

        const canEdit = s.statut === 'en_attente';
        return `<div class="sinistre-box ${selectedId===s.id?'selected':''}" style="cursor:pointer;margin-bottom:12px;" onclick="selectSinistre(${s.id})">
            <div class="sinistre-header">
                <div class="sinistre-title">${s.type}</div>
                <span class="badge ${badgeClass}">${badgeLabel}</span>
            </div>
            <div class="sinistre-meta">Déclaré le ${fmt(s.date)} — Contrat ${s.contrat}</div>
            ${s.statut==='rembourse'&&s.montant?`<div class="montant-banner" style="margin-top:10px;margin-bottom:4px;"><i class="bi bi-cash-stack"></i><div><div class="montant-banner-label">Montant remboursé</div><div class="montant-banner-amount">${s.montant} DT</div></div></div>`:''}
            <div class="contrat-actions">
                <button class="btn btn-outline" onclick="event.stopPropagation();selectSinistre(${s.id})"><i class="bi bi-eye"></i> Voir</button>
                <a href="sinistre_tracker.php" class="btn btn-outline" style="text-decoration:none;color:var(--accent);"><i class="bi bi-clock-history"></i> Suivre</a>
                ${canEdit?`<button class="btn btn-outline" onclick="event.stopPropagation();openEditModal(${s.id})"><i class="bi bi-pencil"></i> Modifier</button><button class="btn btn-outline danger" onclick="event.stopPropagation();openConfirmDelete(${s.id})"><i class="bi bi-trash"></i></button>`:''}
            </div>
        </div>`;
    }).join('');
}

async function selectSinistre(id) {
    selectedId = id;
    renderList();

    // Fetch fresh sinistre data directly (bypasses cache)
    try {
        const res  = await fetch(SINISTRE_API + '?_=' + Date.now(), {
            method: 'GET',
            headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache' }
        });
        const json = await res.json();
        if (Array.isArray(json)) {
            sinistres = json.map(row => ({
                id:          row.id_sinistre,
                id_contrat:  row.id_contrat,
                contrat:     row.id_contrat,
                type:        row.type,
                description: row.description,
                date:        row.date_declaration,
                statut:      row.statut,
                photo:       row.photo_url ? '../../' + row.photo_url : '',
                fraud_score:  row.fraud_score,
                fraud_niveau: row.fraud_niveau,
                fraud_suggestion: row.fraud_suggestion,
                commentaires: [],
                traitements:  [],
            }));
            renderList();
        }
    } catch(e) {}

    const s = sinistres.find(x => x.id === id);
    if (!s) return;

    const STATUTS_MAP = {
        en_attente: { label:'En attente', css:'badge-warning' },
        rembourse:  { label:'Remboursé',  css:'badge-success' },
        refuse:     { label:'Refusé',     css:'badge-danger'  },
    };
    const st = STATUTS_MAP[s.statut] || { label: s.statut, css: 'badge-warning' };

    // Load traitement history
    let traitHTML = `<p style="font-size:12px;color:var(--text-secondary);">Aucun traitement enregistré.</p>`;
    try {
        const tr   = await fetch('../BackOffice/traitement_list_sinistre.php?id=' + id + '&_=' + Date.now());
        const trj  = await tr.json();
        const DL   = { en_attente:'En attente', refuse:'Refusé', rembourse:'Remboursé' };
        if (trj.success && trj.data.length) {
            traitHTML = trj.data.map((t, i) => {
                let bodyContent = '';
                if (t.montant_indemnise) {
                    bodyContent += `<div class="traitement-row success-text"><i class="bi bi-currency-exchange"></i><span>Montant : <strong>${parseFloat(t.montant_indemnise).toLocaleString('fr-FR')} DT</strong></span></div>`;
                }
                if (t.message_agent) {
                    bodyContent += `<div class="traitement-row" style="margin-top:4px;"><i class="bi bi-chat-left-text" style="color:var(--text-secondary);"></i><span style="font-style:italic;">"${t.message_agent}"</span></div>`;
                }
                let bodyWrap = bodyContent ? `<div class="traitement-body">${bodyContent}</div>` : '';

                return `
                <div class="traitement-item ${t.montant_indemnise ? 'traitement-final' : ''}">
                    <div class="traitement-header">
                        <div class="traitement-step">
                            <span class="traitement-num ${t.montant_indemnise ? 'success' : ''}">${i+1}</span>
                            <span class="traitement-title-t">${DL[t.decision] || t.decision}</span>
                        </div>
                        <span class="traitement-date">${fmt(t.date_traitement)}</span>
                    </div>
                    ${bodyWrap}
                </div>`;
            }).join('');
        }
    } catch(e) {}

    document.getElementById('detailPanel').innerHTML = `
        <div class="sinistre-box">
            <div class="sinistre-header">
                <div class="sinistre-title">${s.type}</div>
                <span class="badge ${st.css}">${st.label}</span>
                <button onclick="selectSinistre(${s.id})" title="Actualiser le statut"
                    style="margin-left:auto;background:none;border:none;cursor:pointer;color:var(--text-secondary);font-size:16px;padding:4px;">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
            <div class="sinistre-meta">Contrat ${s.contrat} — ${fmt(s.date)}</div>
            
            ${s.statut === 'refuse' && s.fraud_niveau === 'fraude' ? `
            <div class="fraud-banner" style="margin-top:14px;">
                <i class="bi bi-shield-lock-fill"></i>
                <div>
                    <div class="fraud-title">Dossier de Fraude Identifié</div>
                    <div class="fraud-desc">Votre dossier a été refusé suite à la détection d'incohérences majeures. Votre compte a été signalé à notre service de conformité.</div>
                </div>
            </div>
            ` : ''}

            <div class="sinistre-title" style="margin-top:20px; margin-bottom:10px;">Suivi du statut</div>
            ${buildTrackerStepperHTML(s.statut)}

            <div class="tracker-info-grid">
                <div class="tracker-info-box">
                    <div class="tracker-info-box-title"><i class="bi bi-chat-left-text"></i> Description</div>
                    <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.6;">${s.description ? s.description : 'Aucune description fournie.'}</p>
                    ${buildFraudBadge(s.fraud_score, s.fraud_niveau)}
                </div>
                <div class="tracker-info-box">
                    <div class="tracker-info-box-title"><i class="bi bi-list-check"></i> Pièces du dossier</div>
                    ${buildTrackerChecklist(s.statut, !!s.photo)}
                    ${s.photo ? '<div class="preview-wrapper" onclick="openLightbox(\'' + s.photo + '\')" style="margin-top:14px; border-radius:8px; overflow:hidden; max-height:120px; width:fit-content; border:1px solid rgba(26,58,122,0.1);"><img src="' + s.photo + '" style="max-height:120px; width:auto; max-width:100%; display:block;" alt="photo" onerror="this.closest(\'.preview-wrapper\').style.display=\'none\'"><div class="preview-overlay"><i class="bi bi-zoom-in"></i></div></div>' : ''}
                </div>
            </div>

            ${s.fraud_suggestion ? `
            <div style="margin-top:14px; padding:12px 16px; background:rgba(255,107,26,0.08); border:1px solid rgba(255,107,26,0.20); border-radius:10px; font-size:12px; color:#FF6B1A;">
                <i class="bi bi-lightbulb"></i> <strong>Suggestion :</strong> ${s.fraud_suggestion}
            </div>` : ''}

            <div class="sinistre-title" style="margin-top:24px; margin-bottom:10px;">
                <i class="bi bi-journal-text" style="margin-right:5px;"></i>Historique des traitements
            </div>
            ${traitHTML}
        </div>`;
}

function openDeclareModal() {
    editingId = null;
    document.getElementById('modalTitle').textContent = 'Déclarer un sinistre';
    document.getElementById('contrat_id').value = '';
    document.getElementById('type_sinistre').value = '';
    document.getElementById('description').value = '';
    document.getElementById('date_declaration').value = new Date().toISOString().split('T')[0];
    updateTypeOptions(null);
    mCurrentStep = 1;
    selectedModalFiles = [];
    document.getElementById('photo').value = '';
    renderModalPreviews();
    updateModalStepperUI();
    // Reset AI estimator
    document.getElementById('ai-result-area').style.display = 'none';
    document.getElementById('ai-estimator-section').style.display = 'none';
    document.getElementById('ai-photo-preview').style.display = 'none';
    ['hidden-ai-estimate','hidden-ai-min','hidden-ai-max','hidden-ai-remb','hidden-ai-analysis'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.value = '';
    });
    document.getElementById('sinistreModal').style.display = 'flex';
}


function openEditModal(id) {
    editingId = id;
    const s = sinistres.find(x => x.id === id);
    document.getElementById('modalTitle').textContent = 'Modifier le sinistre';
    document.getElementById('contrat_id').value = s.id_contrat;
    updateTypeOptions(s.id_contrat);
    document.getElementById('type_sinistre').value = s.type;
    document.getElementById('description').value = s.description;
    document.getElementById('date_declaration').value = s.date;
    mCurrentStep = 1;
    if (s.photo) {
        selectedModalFiles = [];
    } else {
        removeModalPhoto();
    }
    updateModalStepperUI();
    document.getElementById('sinistreModal').style.display = 'flex';
}

async function handleSinistreSubmit(e) {
    e.preventDefault();
    const contrat = document.getElementById('contrat_id').value.trim();
    const type    = document.getElementById('type_sinistre').value;
    const desc    = document.getElementById('description').value.trim();
    if (!contrat || !desc) { showToast('Remplissez tous les champs.', 'warning'); return; }

    const btn = e.target.querySelector('button[type="submit"]');
    if (btn) { btn.disabled = true; btn.textContent = 'Envoi…'; }

    try {
        let res, json;

        const formData = new FormData();
        formData.append('id_contrat', contrat);
        formData.append('type', type);
        formData.append('description', desc);
        selectedModalFiles.forEach(f => {
            formData.append('documents[]', f);
        });

        // Ajouter les champs estimation IA
        const aiEst  = document.getElementById('hidden-ai-estimate');
        const aiMin  = document.getElementById('hidden-ai-min');
        const aiMax  = document.getElementById('hidden-ai-max');
        const aiRemb = document.getElementById('hidden-ai-remb');
        const aiAna  = document.getElementById('hidden-ai-analysis');
        if (aiEst && aiEst.value) {
            formData.append('ai_cost_estimate', aiEst.value);
            formData.append('ai_cost_min', aiMin ? aiMin.value : 0);
            formData.append('ai_cost_max', aiMax ? aiMax.value : 0);
            formData.append('ai_remboursement', aiRemb ? aiRemb.value : 0);
            formData.append('ai_analysis', aiAna ? aiAna.value : '');
        }

        if (editingId) {
            formData.append('id', editingId);
            res = await fetch('sinistre_update.php', { method: 'POST', body: formData });
        } else {
            res = await fetch('sinistre_create.php', { method: 'POST', body: formData });
        }

        json = await res.json();
        if (json.success) {
            showToast(editingId ? 'Sinistre modifié avec succès.' : 'Sinistre déclaré avec succès.', 'success');
            closeSinistreModal();
            await loadSinistres();
            if (editingId && selectedId === editingId) selectSinistre(editingId);
        } else {
            showToast(json.message || 'Erreur.', 'danger');
        }
    } catch (err) {
        showToast('Erreur réseau. Vérifiez que XAMPP est démarré.', 'danger');
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = 'Confirmer l\'envoi'; }
    }
}

function closeSinistreModal() { document.getElementById('sinistreModal').style.display = 'none'; }
function openConfirmDelete(id) { deletingId=id; document.getElementById('confirmDeleteModal').classList.add('open'); }
function closeConfirmDelete()  { document.getElementById('confirmDeleteModal').classList.remove('open'); deletingId=null; }
async function confirmDelete() {
    try {
        const res  = await fetch('sinistre_delete.php?id=' + deletingId, { method:'GET' });
        const json = await res.json();
        if (json.success) {
            if (selectedId === deletingId) {
                selectedId = null;
                document.getElementById('detailPanel').innerHTML = `<div class="sinistre-box" style="text-align:center;padding:40px 20px;color:var(--text-secondary);"><i class="bi bi-shield" style="font-size:36px;opacity:0.2;"></i><p style="margin-top:10px;font-size:13px;">Sélectionnez un sinistre</p></div>`;
            }
            closeConfirmDelete();
            await loadSinistres();
            showToast('Sinistre supprimé.', 'danger');
        } else {
            closeConfirmDelete();
            showToast(json.message || 'Erreur suppression.', 'danger');
        }
    } catch(err) {
        closeConfirmDelete();
        showToast('Erreur réseau.', 'danger');
    }
}

function showToast(msg, type='success') {
    const icons = { success:'bi-check-circle', warning:'bi-exclamation-circle', danger:'bi-x-circle', info:'bi-info-circle' };
    const el = document.getElementById('toastNotif');
    el.querySelector('i').className=`bi ${icons[type]||icons.success}`;
    document.getElementById('toastMsg').textContent=msg;
    el.className=`toast-notif toast-${type} show`;
    setTimeout(()=>el.classList.remove('show'), 3000);
}

document.addEventListener('DOMContentLoaded', () => {
    checkAuth();
    
    // Listen for ESC key to close lightbox
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeLightbox();
    });
});

// ── AI COST ESTIMATOR ─────────────────────────────────────

function updateAiEstimatorVisibility() {
    const contratId = document.getElementById('contrat_id')?.value;
    const section = document.getElementById('ai-estimator-section');
    if (!section) return;
    const c = userContrats.find(x => x.id_contrat == contratId);
    if (!c) { section.style.display = 'none'; return; }
    const key = getContratTypeKey(c);
    section.style.display = key === 'auto' ? 'block' : 'none';
}

// Hook into existing contrat change logic
const origOnContratChange = onContratChange;
onContratChange = function() {
    if (origOnContratChange) origOnContratChange();
    setTimeout(updateAiEstimatorVisibility, 50);
};

async function runAiEstimate() {
    const btn = document.getElementById('btn-ai-estimate');
    const resultArea = document.getElementById('ai-result-area');
    const loading    = document.getElementById('ai-loading');
    const mainResult = document.getElementById('ai-result-main');

    const idContrat    = document.getElementById('contrat_id')?.value || 0;
    const description  = document.getElementById('description')?.value || '';
    const accidentType = document.getElementById('ai-accident-type')?.value || 'collision_arriere';
    const severity     = document.getElementById('ai-severity')?.value || 'modere';
    const photoFile    = selectedModalFiles.length > 0 ? selectedModalFiles[0] : null;

    btn.disabled = true;
    btn.innerHTML = '<div style="width:12px;height:12px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:ai-spin .7s linear infinite"></div> Analyse…';
    resultArea.style.display = 'block';
    loading.style.display    = 'block';
    mainResult.style.display = 'none';

    var imageBase64 = '';
    if (photoFile && photoFile.type && photoFile.type.startsWith('image/')) {
        try {
            imageBase64 = await new Promise(function(resolve, reject) {
                var reader = new FileReader();
                reader.onload = function(e) { resolve(e.target.result.split(',')[1]); };
                reader.onerror = function() { reject(''); };
                reader.readAsDataURL(photoFile);
            });
            var previewEl = document.getElementById('ai-photo-preview');
            var imgEl     = document.getElementById('ai-photo-img');
            if (previewEl && imgEl) {
                imgEl.src = 'data:' + photoFile.type + ';base64,' + imageBase64;
                previewEl.style.display = 'block';
            }
        } catch (e) { imageBase64 = ''; }
    }

    try {
        const bodyData = {
            id_contrat:    idContrat,
            accident_type: accidentType,
            severity:      severity,
            description:   description,
            has_photo:     !!photoFile,
            car_brand:     '',
            car_year:      new Date().getFullYear() - 3,
        };
        if (imageBase64) {
            bodyData.image_base64 = imageBase64;
            bodyData.image_mime   = photoFile.type || 'image/jpeg';
        }

        const response = await fetch('../../api.php?action=ai_cost_estimate', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(bodyData)
        });

        const data = await response.json();
        loading.style.display = 'none';

        if (!data.success) {
            mainResult.innerHTML = '<div style="text-align:center;color:#e63946;padding:10px;font-size:12px">❌ ' + (data.message || 'Erreur estimation IA.') + '</div>';
            mainResult.style.display = 'block';
            return;
        }

        const fmtDT = function(v) { return Math.round(v).toLocaleString('fr-FR') + ' DT'; };

        document.getElementById('ai-cost-display').textContent  = fmtDT(data.cost_estimate);
        document.getElementById('ai-range-display').textContent = 'Fourchette : ' + fmtDT(data.cost_min) + ' — ' + fmtDT(data.cost_max);
        document.getElementById('ai-remb-display').textContent  = fmtDT(data.remboursement || 0);
        document.getElementById('ai-charge-display').textContent= 'À votre charge : ' + fmtDT(data.a_charge || 0);
        document.getElementById('ai-coverage-pct').textContent  = (data.coverage_pct || 0) + '%';
        document.getElementById('ai-analysis-text').textContent = data.analysis || '—';

        const bar = document.getElementById('ai-coverage-bar');
        const pct = data.coverage_pct || 0;
        bar.style.width      = pct + '%';
        bar.style.background = pct >= 70 ? 'linear-gradient(90deg,#16a34a,#22c55e)'
                             : pct >= 40 ? 'linear-gradient(90deg,#d97706,#f59e0b)'
                             :             'linear-gradient(90deg,#b91c1c,#e63946)';

        const flagsEl = document.getElementById('ai-flags');
        flagsEl.innerHTML = (data.flags || []).map(function(f) {
            return '<span style="font-size:10px;padding:2px 8px;border-radius:99px;background:rgba(245,158,11,0.12);color:#f59e0b;border:1px solid rgba(245,158,11,0.2)">' + f + '</span>';
        }).join('');

        document.getElementById('ai-meta').innerHTML = [
            {icon:'bi-clock', label:'Garage: ~' + (data.garage_days || '?') + 'j'},
            {icon:'bi-shield-check', label:'Formule: ' + (data.formule || '?')},
            {icon:'bi-graph-up', label:'Confiance: ' + (data.confidence || '?')},
            data.needs_expertise ? {icon:'bi-exclamation-triangle', label:'Expertise recommandée', warn:true} : null,
        ].filter(Boolean).map(function(m) {
            return '<span style="font-size:10px;padding:3px 8px;border-radius:99px;display:flex;align-items:center;gap:4px;background:' + (m.warn ? 'rgba(230,57,70,0.1)' : 'rgba(255,255,255,0.05)') + ';color:' + (m.warn ? '#e63946' : 'rgba(21,35,60,0.6)') + ';border:1px solid ' + (m.warn ? 'rgba(230,57,70,0.2)' : 'rgba(26,58,122,0.08)') + '"><i class="bi ' + m.icon + '"></i>' + m.label + '</span>';
        }).join('');

        document.getElementById('hidden-ai-estimate').value  = data.cost_estimate;
        document.getElementById('hidden-ai-min').value       = data.cost_min;
        document.getElementById('hidden-ai-max').value       = data.cost_max;
        document.getElementById('hidden-ai-remb').value      = data.remboursement || 0;
        document.getElementById('hidden-ai-analysis').value  = data.analysis;

        if (data.accident_type) {
            const atSelect = document.getElementById('ai-accident-type');
            if ([...atSelect.options].some(o => o.value === data.accident_type)) {
                atSelect.value = data.accident_type;
            }
        }
        if (data.severity) {
            const svSelect = document.getElementById('ai-severity');
            if ([...svSelect.options].some(o => o.value === data.severity)) {
                svSelect.value = data.severity;
            }
        }

        mainResult.style.display = 'block';

    } catch (err) {
        loading.style.display = 'none';
        mainResult.innerHTML = '<div style="text-align:center;color:#e63946;padding:10px;font-size:12px">❌ Erreur réseau.</div>';
        mainResult.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-stars"></i> Recalculer';
    }
}

function acceptAiEstimate(event) {
    const est = document.getElementById('hidden-ai-estimate').value;
    if (!est) return;
    event.target.innerHTML = '✅ Estimation acceptée';
    event.target.style.background = 'rgba(34,197,94,0.2)';
    event.target.disabled = true;
}

// Add spin animation for button loading
(function() {
    var s = document.createElement('style');
    s.textContent = '@keyframes ai-spin{to{transform:rotate(360deg)}}';
    document.head.appendChild(s);
})();
</script>
<script src="assets/js/main.js"></script>
</body>
</html>




