<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Déclarer un sinistre — Protex</title>
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

        /* ===== PAGE LAYOUT ===== */
        .page-content {
            max-width: 1100px;
            margin: 0 auto;
            padding: 32px 24px;
        }

        .page-header {
            margin-bottom: 28px;
        }
        .page-title {
            font-family: var(--font-display);
            font-size: 24px; font-weight: 700;
            color: #15233C; line-height: 1.2;
        }
        .page-breadcrumb {
            display: flex; align-items: center; gap: 6px;
            font-size: 12px; color: var(--text-secondary);
            margin-top: 6px;
        }
        .page-breadcrumb a { color: var(--accent); text-decoration: none; }
        .page-breadcrumb a:hover { text-decoration: underline; }

        /* ===== TWO-COL GRID ===== */
        .two-col {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 24px;
            align-items: start;
        }
        @media (max-width: 900px) {
            .two-col { grid-template-columns: 1fr; }
        }

        /* ===== CARD ===== */
        .card {
            background: #fff;
            border: 1px solid rgba(26,58,122,0.10);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(26,58,122,0.06);
            overflow: hidden;
        }
        .card-header {
            display: flex; align-items: center; gap: 12px;
            padding: 20px 24px;
            border-bottom: 1px solid rgba(26,58,122,0.08);
        }
        .card-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
        }
        .card-icon.orange { background: rgba(255,107,26,0.12); color: #FF6B1A; }
        .card-icon.navy   { background: rgba(26,58,122,0.10);  color: #1A3A7A; }
        .card-title { font-family: var(--font-display); font-size: 16px; font-weight: 600; color: #15233C; }
        .card-sub   { font-size: 12px; color: var(--text-secondary); margin-top: 2px; }
        .card-body  { padding: 24px; }

        /* ===== FORM ===== */
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block; font-size: 13px; font-weight: 500;
            color: #15233C; margin-bottom: 7px;
        }
        .form-group label span.req { color: var(--danger); margin-left: 2px; }
        .form-group label span.opt { color: var(--text-secondary); font-weight: 400; font-size: 11px; margin-left: 4px; }
        .form-control {
            width: 100%; padding: 11px 14px;
            border: 1px solid rgba(26,58,122,0.18);
            border-radius: 10px; background: #f8f9ff;
            color: #15233C; font-size: 14px;
            font-family: var(--font-body);
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }
        .form-control:focus {
            outline: none;
            border-color: #FF6B1A;
            box-shadow: 0 0 0 3px rgba(255,107,26,0.10);
            background: #fff;
        }
        .form-control.error {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(230,57,70,0.10);
        }
        textarea.form-control {
            resize: vertical; min-height: 110px; line-height: 1.5;
        }
        select.form-control { cursor: pointer; }

        .form-error {
            font-size: 12px; color: var(--danger);
            margin-top: 5px; display: none;
        }
        .form-error.show { display: block; }

        .fraud-indicator {
            margin-top: 12px;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid rgba(26,58,122,0.12);
            background: linear-gradient(135deg, rgba(26,58,122,0.05), rgba(255,255,255,0.8));
            transition: all 0.4s ease;
        }
        .fraud-indicator-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .fraud-indicator-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #15233C;
        }
        .fraud-indicator-score {
            font-size: 13px;
            font-weight: 800;
            color: #1A3A7A;
        }
        .fraud-progress {
            height: 12px;
            border-radius: 999px;
            overflow: hidden;
            background: rgba(26,58,122,0.08);
            border: 1px solid rgba(26,58,122,0.08);
        }
        .fraud-progress-bar {
            width: 0;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #2EC4B6, #1A3A7A);
            transition: width 0.4s ease, background 0.4s ease;
        }
        .fraud-progress-label {
            margin-top: 10px;
            font-size: 12px;
            font-weight: 700;
            color: #2EC4B6;
        }
        .fraud-progress-label.modere { color: #EF9F27; }
        .fraud-progress-label.suspect { color: #e63946; }
        .fraud-progress-label.probable { color: #e63946; animation: fraudPulseTxt 1.2s ease-in-out infinite; }
        @keyframes fraudPulseTxt {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.75; }
        }
        @keyframes fraudPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(230,57,70,0.5); }
            50% { box-shadow: 0 0 0 10px rgba(230,57,70,0); }
        }

        /* ===== TYPE SELECTOR GRID ===== */
        .type-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .type-btn {
            display: flex; flex-direction: column; align-items: center;
            gap: 8px; padding: 16px 12px;
            border: 2px solid rgba(26,58,122,0.12);
            border-radius: 12px; background: #f8f9ff;
            cursor: pointer; transition: all 0.2s;
            font-family: var(--font-body);
        }
        .type-btn:hover {
            border-color: rgba(255,107,26,0.4);
            background: rgba(255,107,26,0.04);
        }
        .type-btn.selected {
            border-color: #FF6B1A;
            background: rgba(255,107,26,0.07);
            box-shadow: 0 0 0 3px rgba(255,107,26,0.10);
        }
        .type-btn i {
            font-size: 22px;
            color: var(--text-secondary);
            transition: color 0.2s;
        }
        .type-btn.selected i { color: #FF6B1A; }
        .type-btn span {
            font-size: 12px; font-weight: 500;
            color: var(--text-secondary); text-align: center;
            transition: color 0.2s;
        }
        .type-btn.selected span { color: #15233C; }
        #typeHidden { display: none; }

        /* ===== PHOTO UPLOAD ===== */
        .upload-zone {
            border: 2px dashed rgba(26,58,122,0.20);
            border-radius: 12px; padding: 24px;
            text-align: center; cursor: pointer;
            transition: all 0.2s; background: #f8f9ff;
            position: relative;
        }
        .upload-zone:hover, .upload-zone.drag {
            border-color: #FF6B1A;
            background: rgba(255,107,26,0.04);
        }
        .upload-zone input[type=file] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer;
        }
        .upload-icon { font-size: 28px; color: rgba(26,58,122,0.30); margin-bottom: 8px; }
        .upload-label { font-size: 13px; color: var(--text-secondary); }
        .upload-label strong { color: #FF6B1A; }
        .upload-hint  { font-size: 11px; color: var(--text-secondary); margin-top: 4px; }
        .upload-preview {
            display: none; margin-top: 14px;
            border-radius: 10px; overflow: hidden;
            border: 1px solid rgba(26,58,122,0.12);
        }
        .preview-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid rgba(26,58,122,0.15);
            background: #fff;
            aspect-ratio: 1;
        }
        .preview-item img {
            width: 100%; height: 100%; object-fit: cover;
        }
        .preview-item .pdf-icon {
            width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
            font-size: 32px; color: #e63946; background: #f8f9ff;
        }
        .preview-item .remove-btn {
            position: absolute; top: 6px; right: 6px;
            background: rgba(255,255,255,0.9); border: none; border-radius: 50%;
            width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;
            color: var(--danger); cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: 0.2s;
        }
        .preview-item .remove-btn:hover { background: var(--danger); color: #fff; }
        .preview-item .file-name {
            position: absolute; bottom: 0; left: 0; right: 0;
            background: rgba(0,0,0,0.6); color: #fff; font-size: 10px;
            padding: 4px 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        /* ===== SUBMIT BUTTON ===== */
        .btn-submit {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #FF6B1A, #e05a0f);
            color: #fff; border: none; border-radius: 12px;
            font-size: 15px; font-weight: 600;
            font-family: var(--font-display);
            cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 4px 16px rgba(255,107,26,0.30);
        }
        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255,107,26,0.40);
        }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .spin { animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ===== RIGHT PANEL: INFO + HISTORY ===== */
        .info-card {
            background: rgba(26,58,122,0.04);
            border: 1px solid rgba(26,58,122,0.10);
            border-radius: 12px; padding: 16px;
            margin-bottom: 16px;
        }
        .info-card-title {
            font-size: 12px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.8px;
            color: var(--text-secondary); margin-bottom: 12px;
            display: flex; align-items: center; gap: 6px;
        }
        .info-card-title i { font-size: 14px; color: #1A3A7A; }

        .step-list { display: flex; flex-direction: column; gap: 12px; }
        .step-item { display: flex; gap: 12px; align-items: flex-start; }
        .step-num {
            width: 24px; height: 24px; border-radius: 50%;
            background: rgba(255,107,26,0.12); color: #FF6B1A;
            font-size: 11px; font-weight: 700;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .step-text { font-size: 12px; color: var(--text-secondary); line-height: 1.5; }
        .step-text strong { color: #15233C; }

        /* ===== HISTORY LIST ===== */
        .history-item {
            padding: 14px 0;
            border-bottom: 1px solid rgba(26,58,122,0.07);
        }
        .history-item:last-child { border-bottom: none; padding-bottom: 0; }
        .history-top {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 4px;
        }
        .history-type {
            font-size: 13px; font-weight: 500; color: #15233C;
            display: flex; align-items: center; gap: 6px;
        }
        .history-type i { font-size: 14px; color: #FF6B1A; }
        .history-date { font-size: 11px; color: var(--text-secondary); }
        .history-contrat { font-size: 11px; color: var(--text-secondary); margin-bottom: 6px; }

        .badge {
            display: inline-flex; align-items: center;
            padding: 3px 9px; border-radius: 20px;
            font-size: 11px; font-weight: 600;
        }
        .badge-attente  { background: rgba(255,107,26,0.12); color: #e05a0f; }
        .badge-rembourse{ background: rgba(26,58,122,0.12);  color: #1A3A7A; }
        .badge-refuse   { background: rgba(230,57,70,0.12);  color: var(--danger); }

        .history-empty {
            text-align: center; padding: 20px 10px;
            color: var(--text-secondary); font-size: 13px;
        }
        .history-empty i { font-size: 28px; display: block; margin-bottom: 8px; opacity: 0.3; }

        /* ===== SUCCESS STATE ===== */
        .success-state {
            display: none; text-align: center; padding: 32px 24px;
        }
        .success-icon {
            width: 68px; height: 68px; border-radius: 50%;
            background: rgba(26,58,122,0.10); color: #1A3A7A;
            font-size: 30px; display: flex; align-items: center;
            justify-content: center; margin: 0 auto 16px;
        }
        .success-title {
            font-family: var(--font-display); font-size: 18px;
            font-weight: 700; color: #15233C; margin-bottom: 8px;
        }
        .success-msg { font-size: 13px; color: var(--text-secondary); line-height: 1.6; }
        .success-id {
            display: inline-block; margin-top: 14px;
            background: rgba(26,58,122,0.08); border-radius: 8px;
            padding: 6px 16px; font-size: 13px; font-weight: 600; color: #1A3A7A;
        }
        .btn-new {
            margin-top: 20px; padding: 10px 24px;
            background: #FF6B1A; color: #fff; border: none;
            border-radius: 10px; font-size: 13px; font-weight: 600;
            cursor: pointer; font-family: var(--font-body);
            transition: 0.2s;
        }
        .btn-new:hover { background: #e05a0f; }

        /* ===== CONTRAT SELECT with preview ===== */
        .contrat-preview {
            display: none;
            background: rgba(26,58,122,0.05);
            border: 1px solid rgba(26,58,122,0.15);
            border-radius: 10px; padding: 12px 14px;
            margin-top: 8px; font-size: 12px;
        }
        .contrat-preview-row {
            display: flex; gap: 16px; flex-wrap: wrap;
        }
        .contrat-preview-item { display: flex; flex-direction: column; gap: 2px; }
        .contrat-preview-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.7px; color: var(--text-secondary); }
        .contrat-preview-val   { font-size: 13px; font-weight: 500; color: #15233C; }
        .contrat-preview-val.danger { color: var(--danger); }
        .contrat-preview-val.warning { color: #FF6B1A; }

        /* ===== STEPPER ===== */
        .stepper {
            display: flex; justify-content: space-between;
            margin-bottom: 32px; position: relative; padding: 0 10px;
        }
        .stepper::before {
            content: ''; position: absolute; top: 16px; left: 20px; right: 20px;
            height: 2px; background: rgba(26,58,122,0.08); z-index: 1;
        }
        .step {
            position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; gap: 8px;
            width: 80px;
        }
        .step-circle {
            width: 34px; height: 34px; border-radius: 50%;
            background: #fff; border: 2px solid rgba(26,58,122,0.15);
            color: var(--text-secondary); font-size: 14px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.3s ease;
        }
        .step.active .step-circle {
            border-color: #FF6B1A; background: #FF6B1A; color: #fff;
            box-shadow: 0 0 15px rgba(255,107,26,0.25);
        }
        .step.completed .step-circle {
            border-color: #FF6B1A; background: #fff; color: #FF6B1A;
        }
        .step-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary); text-align: center; }
        .step.active .step-label { color: #15233C; }

        /* ===== STEP NAVIGATION ===== */
        .step-content { display: none; }
        .step-content.active { display: block; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .step-actions {
            display: flex; justify-content: space-between; gap: 12px; margin-top: 30px;
            padding-top: 24px; border-top: 1px solid rgba(26,58,122,0.08);
        }
        .btn-step {
            padding: 12px 24px; border-radius: 12px; font-size: 14px; font-weight: 600;
            cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px;
            font-family: var(--font-display);
        }
        .btn-prev { background: rgba(26,58,122,0.05); color: #1A3A7A; border: 1px solid rgba(26,58,122,0.1); }
        .btn-prev:hover { background: rgba(26,58,122,0.1); }
        .btn-next { background: #1A3A7A; color: #fff; border: none; flex: 1; justify-content: center; }
        .btn-next:hover { background: #15233C; transform: translateY(-1px); }
        .btn-next:disabled { opacity: 0.5; cursor: not-allowed; }

        /* ===== SUMMARY VIEW ===== */
        .summary-box {
            background: #f8f9ff; border-radius: 12px; padding: 20px; border: 1px solid rgba(26,58,122,0.08);
        }
        .summary-item { margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid rgba(26,58,122,0.05); }
        .summary-item:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
        .summary-label { font-size: 10px; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 4px; font-weight: 700; letter-spacing: 0.5px; }
        .summary-val { font-size: 14px; color: #15233C; font-weight: 500; display: flex; align-items: center; gap: 8px; }
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
        }
        .preview-wrapper:hover { transform: scale(1.02); }
        .preview-overlay {
            position: absolute; inset: 0;
            background: rgba(26,58,122,0.4);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.2s;
        }
        .preview-wrapper:hover .preview-overlay { opacity: 1; }
        .preview-overlay i { color: #fff; font-size: 24px; }
    </style>
    <script src="assets/js/validation.js"></script>

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

    <!-- ===== MAIN CONTENT ===== -->
    <div class="page-content">

        <!-- Page header -->
        <div class="page-header">
            <div class="page-title">
                <i class="bi bi-shield-plus" style="color:#FF6B1A;margin-right:8px;"></i>
                Déclarer un sinistre
            </div>
            <div class="page-breadcrumb">
                <i class="bi bi-house"></i>
                <a href="client.php">Accueil</a>
                <i class="bi bi-chevron-right" style="font-size:10px;"></i>
                <a href="mes-sinistres.php">Sinistres</a>
                <i class="bi bi-chevron-right" style="font-size:10px;"></i>
                <span>Déclarer</span>
            </div>
        </div>

        <div class="two-col">

            <!-- ===== LEFT: FORM ===== -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon orange"><i class="bi bi-clipboard-plus"></i></div>
                        <div>
                            <div class="card-title">Nouvelle déclaration</div>
                            <div class="card-sub">Remplissez tous les champs obligatoires</div>
                        </div>
                    </div>

                    <!-- FORM -->
                    <div class="card-body" id="formBody">
                        
                        <!-- STEPPER -->
                        <div class="stepper">
                            <div class="step active" id="step1-head">
                                <div class="step-circle">1</div>
                                <div class="step-label">Infos</div>
                            </div>
                            <div class="step" id="step2-head">
                                <div class="step-circle">2</div>
                                <div class="step-label">Documents</div>
                            </div>
                            <div class="step" id="step3-head">
                                <div class="step-circle">3</div>
                                <div class="step-label">Validation</div>
                            </div>
                        </div>

                        <form id="sinistreForm" onsubmit="return false;">

                            <!-- STEP 1: INFOS DE BASE -->
                            <div class="step-content active" id="step1">
                                <!-- Contrat -->
                                <div class="form-group">
                                    <label>Contrat concerné <span class="req">*</span></label>
                                    <select class="form-control" id="fContrat" onchange="onContratChange()">
                                        <option value="">— Choisissez votre contrat —</option>
                                    </select>
                                    <div class="form-error" id="errContrat">Veuillez sélectionner un contrat.</div>
                                    <div class="contrat-preview" id="contratPreview"></div>
                                </div>

                                <!-- Type de sinistre -->
                                <div class="form-group">
                                    <label>Type de sinistre <span class="req">*</span></label>
                                    <input type="hidden" id="typeHidden">
                                    <div class="type-grid">
                                        <!-- Will be populated by JS -->
                                    </div>
                                    <div class="form-error" id="errType">Veuillez choisir un type de sinistre.</div>
                                </div>

                                <div class="step-actions">
                                    <button type="button" class="btn-step btn-next" onclick="nextStep(2)">
                                        Continuer <i class="bi bi-arrow-right"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- STEP 2: DOCUMENTS & DESCRIPTION -->
                            <div class="step-content" id="step2">
                                <!-- Description -->
                                <div class="form-group">
                                    <label>Description des faits <span class="req">*</span></label>
                                    <textarea class="form-control" id="fDescription"
                                        placeholder="Décrivez les circonstances du sinistre : lieu, date et heure des faits, dommages constatés…"
                                        maxlength="1000"></textarea>
                                    <div id="fraud-indicator" class="fraud-indicator" style="display:flex; align-items:center; gap: 20px;">
                                        <div class="gauge-container" style="position:relative; width: 70px; height: 70px; flex-shrink: 0;">
                                            <svg viewBox="0 0 36 36" style="width:100%; height:100%; transform: rotate(-90deg);">
                                                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                                    fill="none" stroke="rgba(26,58,122,0.08)" stroke-width="3" />
                                                <path id="fraudGaugePath" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                                    fill="none" stroke="#2EC4B6" stroke-width="3"
                                                    stroke-dasharray="0, 100" style="transition: stroke-dasharray 0.5s ease, stroke 0.5s ease; stroke-linecap: round;" />
                                            </svg>
                                            <div class="fraud-indicator-score" id="fraudScoreValue" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); font-size:14px; margin:0; font-weight:800;">0</div>
                                        </div>
                                        <div style="flex:1;">
                                            <div class="fraud-indicator-title" style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#15233C; margin-bottom:4px;">Analyse du risque en direct</div>
                                            <div class="fraud-progress-label" id="fraudLabel" style="font-size:12px; font-weight:700; color:#2EC4B6;">✅ Faible risque</div>
                                        </div>
                                    </div>
                                    <div style="text-align:right;font-size:11px;color:var(--text-secondary);margin-top:4px;">
                                        <span id="charCount">0</span>/1000 caractères
                                    </div>
                                    <div class="form-error" id="errDescription">La description est obligatoire (minimum 20 caractères).</div>
                                </div>

                                <!-- ══ BLOC ESTIMATION IA ══ -->
                                <div id="ai-estimator-section" style="display:none;margin-top:1.5rem">
                                  <div style="background:rgba(0,180,216,0.06);border:1px solid rgba(0,180,216,0.2);border-radius:14px;padding:1.25rem">
                                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:1rem">
                                      <div style="width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,var(--accent),#7c3aed);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">🤖</div>
                                      <div>
                                        <div style="font-size:14px;font-weight:600;color:#15233C">Estimation IA du coût de réparation</div>
                                        <div style="font-size:11.5px;color:var(--text-secondary)">Alimenté par Groq LLaMA · Basé sur votre contrat</div>
                                      </div>
                                      <button type="button" id="btn-ai-estimate" onclick="runAiEstimate()" style="margin-left:auto;padding:7px 16px;border-radius:8px;border:none;background:linear-gradient(135deg,var(--accent),#7c3aed);color:#fff;font-size:12.5px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap">
                                        <i class="bi bi-stars"></i> Estimer
                                      </button>
                                    </div>
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:1rem" id="ai-extra-fields">
                                      <div>
                                        <label style="font-size:12px;color:var(--text-secondary);display:block;margin-bottom:4px">Type d'accident</label>
                                        <select id="ai-accident-type" style="width:100%;background:rgba(26,58,122,0.05);border:1px solid rgba(26,58,122,0.15);border-radius:8px;padding:8px 12px;font-size:13px;color:#15233C;outline:none">
                                          <option value="collision_arriere">Collision arrière</option>
                                          <option value="collision_laterale">Collision latérale</option>
                                          <option value="collision_frontale">Collision frontale</option>
                                          <option value="stationnement">Choc en stationnement</option>
                                          <option value="bris_glace">Bris de glace</option>
                                          <option value="vandalisme">Vandalisme</option>
                                          <option value="incendie">Incendie</option>
                                        </select>
                                      </div>
                                      <div>
                                        <label style="font-size:12px;color:var(--text-secondary);display:block;margin-bottom:4px">Gravité estimée</label>
                                        <select id="ai-severity" style="width:100%;background:rgba(26,58,122,0.05);border:1px solid rgba(26,58,122,0.15);border-radius:8px;padding:8px 12px;font-size:13px;color:#15233C;outline:none">
                                          <option value="leger">Légère (rayures, bosses mineures)</option>
                                          <option value="modere" selected>Modérée (pièces à remplacer)</option>
                                          <option value="grave">Grave (dommages structurels)</option>
                                        </select>
                                      </div>
                                    </div>
                                    <div id="ai-result-area" style="display:none">
                                      <div id="ai-loading" style="text-align:center;padding:1rem;display:none">
                                        <div style="display:flex;gap:6px;justify-content:center;margin-bottom:8px">
                                          <div style="width:7px;height:7px;border-radius:50%;background:var(--accent);animation:ai-dot 1.2s ease-in-out infinite"></div>
                                          <div style="width:7px;height:7px;border-radius:50%;background:var(--accent);animation:ai-dot 1.2s ease-in-out .2s infinite"></div>
                                          <div style="width:7px;height:7px;border-radius:50%;background:var(--accent);animation:ai-dot 1.2s ease-in-out .4s infinite"></div>
                                        </div>
                                        <div style="font-size:12px;color:var(--text-secondary)">Analyse IA en cours…</div>
                                      </div>
                                      <div id="ai-result-main" style="display:none">
                                        <div style="display:flex;justify-content:space-between;align-items:center;background:rgba(26,58,122,0.06);border-radius:10px;padding:12px 16px;margin-bottom:.75rem">
                                          <div>
                                            <div style="font-size:11px;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.06em">Coût estimé</div>
                                            <div style="font-family:'Sora',sans-serif;font-size:26px;font-weight:700;color:var(--accent)" id="ai-cost-display">—</div>
                                            <div style="font-size:11px;color:var(--text-secondary)" id="ai-range-display">—</div>
                                          </div>
                                          <div style="text-align:right">
                                            <div style="font-size:11px;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.06em">Remboursé</div>
                                            <div style="font-family:'Sora',sans-serif;font-size:22px;font-weight:600;color:#22c55e" id="ai-remb-display">—</div>
                                            <div style="font-size:11px;color:var(--text-secondary)" id="ai-charge-display">À votre charge : —</div>
                                          </div>
                                        </div>
                                        <div style="margin-bottom:.75rem">
                                          <div style="display:flex;justify-content:space-between;font-size:11.5px;color:var(--text-secondary);margin-bottom:4px">
                                            <span>Taux de couverture estimé</span>
                                            <span id="ai-coverage-pct">—%</span>
                                          </div>
                                          <div style="height:5px;background:rgba(26,58,122,0.07);border-radius:3px;overflow:hidden">
                                            <div id="ai-coverage-bar" style="height:100%;border-radius:3px;width:0;transition:width 1.2s ease;background:linear-gradient(90deg,#16a34a,#22c55e)"></div>
                                          </div>
                                        </div>
                                        <div style="background:rgba(124,58,237,0.08);border:1px solid rgba(124,58,237,0.2);border-radius:9px;padding:10px 12px;margin-bottom:.75rem">
                                          <div style="font-size:10.5px;color:#a78bfa;font-weight:600;margin-bottom:5px;display:flex;align-items:center;gap:5px">
                                            <i class="bi bi-robot"></i> Analyse Groq LLaMA
                                          </div>
                                          <div style="font-size:12.5px;color:rgba(21,35,60,0.6);line-height:1.6" id="ai-analysis-text">—</div>
                                        </div>
                                        <div id="ai-flags" style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:.75rem"></div>
                                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:1rem" id="ai-meta"></div>
                                        <button type="button" onclick="acceptAiEstimate(event)" style="width:100%;padding:10px;border-radius:9px;border:none;background:rgba(34,197,94,0.15);border:1px solid rgba(34,197,94,0.25);color:#22c55e;font-size:13px;font-weight:500;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;transition:all .2s">
                                          <i class="bi bi-check-circle"></i> Accepter cette estimation et l'annexer à ma déclaration
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
                                <style>
                                @keyframes ai-dot { 0%,100%{transform:translateY(0);opacity:1} 50%{transform:translateY(-7px);opacity:.4} }
                                </style>

                                <!-- Documents multiples (optional) -->
                                <div class="form-group">
                                    <label>Photos / Justificatifs <span class="opt">(facultatif, max 5 fichiers)</span></label>
                                    <div class="upload-zone" id="uploadZone">
                                        <input type="file" id="fPhoto" multiple accept="image/jpeg,image/png,image/webp,application/pdf"
                                               onchange="onFileChange(this)">
                                        <div class="upload-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                                        <div class="upload-label">Glissez vos fichiers ici ou <strong>cliquez pour choisir</strong></div>
                                        <div class="upload-hint">JPG, PNG, WEBP, PDF — max 5 Mo / fichier</div>
                                    </div>
                                    <div class="upload-preview-grid" id="uploadPreviewGrid" style="display:none; margin-top:14px; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px;">
                                        <!-- Previews will be injected here by JS -->
                                    </div>
                                </div>

                                <div class="step-actions">
                                    <button type="button" class="btn-step btn-prev" onclick="prevStep(1)">
                                        <i class="bi bi-arrow-left"></i> Retour
                                    </button>
                                    <button type="button" class="btn-step btn-next" onclick="nextStep(3)">
                                        Continuer <i class="bi bi-arrow-right"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- STEP 3: CONFIRMATION & RÉSUMÉ -->
                            <div class="step-content" id="step3">
                                <div style="margin-bottom:20px;">
                                    <div style="font-size:14px; font-weight:600; color:#15233C; margin-bottom:12px;">Vérifiez vos informations avant l'envoi :</div>
                                    <div class="summary-box" id="summaryView">
                                        <!-- Populated by JS -->
                                    </div>
                                </div>

                                <div class="step-actions">
                                    <button type="button" class="btn-step btn-prev" onclick="prevStep(2)">
                                        <i class="bi bi-arrow-left"></i> Modifier
                                    </button>
                                    <button type="button" class="btn-step btn-next" id="btnSubmit" onclick="submitSinistre()" style="background:#FF6B1A;">
                                        <i class="bi bi-send"></i> Confirmer l'envoi
                                    </button>
                                </div>
                            </div>

                        </form>
                    </div>

                    <!-- SUCCESS STATE -->
                    <div class="success-state" id="successState">
                        <div class="success-icon"><i class="bi bi-check2-circle"></i></div>
                        <div class="success-title">Déclaration envoyée !</div>
                        <div class="success-msg">
                            Votre sinistre a été enregistré avec succès.<br>
                            Notre équipe va l'examiner dans les meilleurs délais.
                        </div>
                        <div class="success-id" id="successId"></div>
                        <br>
                        <button class="btn-new" onclick="resetForm()">
                            <i class="bi bi-plus-circle"></i> Nouvelle déclaration
                        </button>
                        &nbsp;
                        <button class="btn-new" style="background:#1A3A7A;" onclick="location.href='mes-sinistres.html'">
                            <i class="bi bi-list-ul"></i> Voir mes sinistres
                        </button>
                    </div>
                </div>
            </div>

            <!-- ===== RIGHT PANEL ===== -->
            <div>
                <!-- How it works -->
                <div class="card" style="margin-bottom:20px;">
                    <div class="card-header">
                        <div class="card-icon navy"><i class="bi bi-info-circle"></i></div>
                        <div>
                            <div class="card-title">Comment ça marche ?</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="step-list">
                            <div class="step-item">
                                <div class="step-num">1</div>
                                <div class="step-text"><strong>Remplissez</strong> le formulaire en sélectionnant votre contrat et le type de sinistre.</div>
                            </div>
                            <div class="step-item">
                                <div class="step-num">2</div>
                                <div class="step-text"><strong>Décrivez</strong> les circonstances avec le plus de détails possible.</div>
                            </div>
                            <div class="step-item">
                                <div class="step-num">3</div>
                                <div class="step-text"><strong>Joignez</strong> une photo si disponible pour accélérer le traitement.</div>
                            </div>
                            <div class="step-item">
                                <div class="step-num">4</div>
                                <div class="step-text"><strong>Soumettez</strong> — notre équipe vous contactera sous 48h ouvrables.</div>
                            </div>
                        </div>

                        <div style="margin-top:16px;padding:12px;background:rgba(255,107,26,0.06);border:1px solid rgba(255,107,26,0.15);border-radius:10px;">
                            <div style="font-size:12px;color:#15233C;display:flex;gap:8px;align-items:flex-start;">
                                <i class="bi bi-exclamation-triangle" style="color:#FF6B1A;flex-shrink:0;margin-top:1px;"></i>
                                <span>En cas d'urgence (accident, incendie), appelez le <strong>71 000 000</strong> avant de soumettre ce formulaire.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent history -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon navy"><i class="bi bi-clock-history"></i></div>
                        <div>
                            <div class="card-title">Vos derniers sinistres</div>
                            <div class="card-sub" id="historyCount">Chargement…</div>
                        </div>
                    </div>
                    <div class="card-body" style="padding:16px 20px;" id="historyList">
                        <div class="history-empty">
                            <i class="bi bi-hourglass-split"></i>
                            Chargement de l'historique…
                        </div>
                    </div>
                </div>

                <!-- Partenaires à proximité -->
                <div class="card" style="margin-top:20px;">
                    <div class="card-header">
                        <div class="card-icon navy"><i class="bi bi-building-check"></i></div>
                        <div>
                            <div class="card-title">Partenaires agréés</div>
                            <div class="card-sub">Garages, cliniques, pharmacie…</div>
                        </div>
                    </div>
                    <div class="card-body" style="padding:12px 16px;" id="partenairesSidebar">
                        <div style="font-size:12px;color:rgba(21,35,60,.6);text-align:center;padding:12px"><i class="bi bi-hourglass-split"></i> Chargement…</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div id="photoLightbox" class="lightbox" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()"><i class="bi bi-x"></i></button>
    <div class="lightbox-content" onclick="event.stopPropagation()">
        <img id="lightboxImg" class="lightbox-img" src="" alt="Plein écran">
    </div>
</div>

<script src="assets_sinistre_traitement/js/declarer-sinistre.js"></script>

<script>
// Load partners sidebar
fetch('../../api.php?action=partenaires_list&limit=5&actif=1')
    .then(r => r.json())
    .then(d => {
        const el = document.getElementById('partenairesSidebar');
        if (!el) return;
        if (!d.success || !d.partenaires?.length) {
            el.innerHTML = '<div style="font-size:12px;color:rgba(21,35,60,.5);text-align:center;padding:12px">Aucun partenaire disponible</div>';
            return;
        }
        el.innerHTML = d.partenaires.map(p => `
            <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid rgba(21,35,60,.06)">
                <div style="width:32px;height:32px;border-radius:8px;background:rgba(0,180,216,.1);display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0">
                    <i class="bi bi-${p.type==='garage'?'wrench':p.type==='clinique'?'heart-pulse':p.type==='pharmacie'?'capsule':p.type==='hotel'?'building':'geo-alt'}"></i>
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:12px;font-weight:600;color:#15233C">${p.nom||''}</div>
                    <div style="font-size:10.5px;color:rgba(21,35,60,.5)">${p.ville||'—'} · ${p.telephone||''}</div>
                </div>
                ${p.avantage ? '<span style="font-size:10px;color:#22c55e;font-weight:500;white-space:nowrap">'+p.avantage+'</span>' : ''}
            </div>
        `).join('');
        el.innerHTML += '<a href="partenaires.php" style="display:block;text-align:center;font-size:11px;color:var(--accent);padding:8px 0 2px;text-decoration:none">Voir tous les partenaires <i class="bi bi-arrow-right"></i></a>';
    })
    .catch(() => { const el = document.getElementById('partenairesSidebar'); if (el) el.innerHTML = ''; });
</script>

<script src="assets/js/main.js"></script>
</body>
</html>




