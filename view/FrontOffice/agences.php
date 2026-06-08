<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Nos agences — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">

    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <style>
        .toast-notif {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #1A3A7A;
            border: 1px solid rgba(26,58,122,0.15);
            border-radius: 12px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #fff;
            z-index: 9999;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(26,58,122,0.25);
        }

        .toast-notif.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast-success i { color: #22c55e; font-size: 18px; }
        .toast-warning i { color: #FF6B1A; font-size: 18px; }
        .toast-danger i  { color: #e63946; font-size: 18px; }

        .hero-panel {
            background: #ffffff;
            border: 1px solid rgba(26,58,122,0.10);
            border-radius: var(--radius-xl);
            padding: 28px;
            box-shadow: 0 8px 30px rgba(26,58,122,0.06);
            margin-bottom: 28px;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 20px;
            align-items: center;
        }

        .hero-title {
            font-family: var(--font-display);
            font-size: 32px;
            color: #15233C;
            line-height: 1.15;
            margin-bottom: 12px;
        }

        .hero-title span {
            color: #FF6B1A;
        }

        .hero-text {
            font-size: 15px;
            color: rgba(21, 35, 60, 0.75);
            line-height: 1.8;
            margin-bottom: 18px;
            max-width: 680px;
        }

        .hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-soft {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 10px;
            border: 1px solid rgba(26,58,122,0.12);
            background: rgba(26,58,122,0.04);
            color: #15233C;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: var(--transition);
            cursor: pointer;
        }

        .btn-soft:hover {
            border-color: #FF6B1A;
            color: #FF6B1A;
        }

        .hero-boxes {
            display: grid;
            gap: 12px;
        }

        .mini-box {
            background: rgba(26,58,122,0.04);
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: var(--radius-lg);
            padding: 18px;
        }

        .mini-value {
            font-family: var(--font-display);
            font-size: 26px;
            color: #15233C;
            line-height: 1;
        }

        .mini-label {
            font-size: 13px;
            color: rgba(21, 35, 60, 0.75);
            margin-top: 6px;
        }

        .chat-card {
            margin-bottom: 28px;
        }

        .chat-card .card-header {
            background: rgba(26,58,122,0.02);
            border-bottom: 1px solid rgba(26,58,122,0.08);
        }

        .chat-messages {
            height: 230px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 14px;
            padding-right: 4px;
        }

        .chat-bubble {
            max-width: 85%;
            padding: 12px 14px;
            border-radius: 14px;
            font-size: 14px;
            line-height: 1.5;
        }

        .chat-bubble.bot {
            align-self: flex-start;
            background: rgba(26,58,122,0.06);
            border: 1px solid rgba(26,58,122,0.08);
            color: #15233C;
        }

        .chat-bubble.user {
            align-self: flex-end;
            background: rgba(255,107,26,0.14);
            border: 1px solid rgba(255,107,26,0.18);
            color: #15233C;
        }

        .chat-input-row {
            display: flex;
            gap: 10px;
        }

        .chat-input {
            flex: 1;
            height: 46px;
            padding: 0 14px;
            border-radius: 10px;
            border: 1px solid rgba(26,58,122,0.10);
            background: rgba(26,58,122,0.04);
            color: #15233C;
            font-size: 14px;
            outline: none;
        }

        .chat-input::placeholder {
            color: var(--text-secondary);
        }

        .hint-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .hint-chip {
            padding: 7px 10px;
            border-radius: 999px;
            font-size: 12px;
            cursor: pointer;
            background: rgba(26,58,122,0.04);
            border: 1px solid rgba(26,58,122,0.08);
            color: var(--text-secondary);
            transition: var(--transition);
        }

        .hint-chip:hover {
            border-color: #FF6B1A;
            color: #FF6B1A;
        }

        .agency-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .agency-card {
            background: #ffffff;
            border: 1px solid rgba(26,58,122,0.10);
            border-radius: var(--radius-lg);
            box-shadow: 0 4px 16px rgba(26,58,122,0.06);
            overflow: hidden;
            transition: all 0.25s ease;
        }

        .agency-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(26,58,122,0.10);
        }

        .agency-head {
            padding: 20px 24px 16px;
            border-bottom: 1px solid rgba(26,58,122,0.08);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }

        .agency-name {
            font-family: var(--font-display);
            font-size: 18px;
            color: #15233C;
            margin-bottom: 4px;
        }

        .agency-country {
            font-size: 13px;
            color: rgba(21, 35, 60, 0.75);
        }

        .agency-body {
            padding: 20px 24px;
        }

        .agency-contact {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .contact-box {
            background: rgba(26,58,122,0.04);
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: var(--radius-md);
            padding: 12px 14px;
        }

        .contact-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        .contact-value {
            font-size: 14px;
            color: #15233C;
            font-weight: 500;
            word-break: break-word;
        }

        .agency-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        .stat-box {
            background: rgba(26,58,122,0.04);
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: var(--radius-md);
            padding: 12px 14px;
        }

        .stat-value {
            font-family: var(--font-display);
            font-size: 24px;
            color: #15233C;
            line-height: 1;
        }

        .stat-label {
            font-size: 12px;
            color: rgba(21, 35, 60, 0.75);
            margin-top: 6px;
        }

        .review-summary {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .review-summary span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: rgba(21, 35, 60, 0.75);
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(26,58,122,0.04);
            border: 1px solid rgba(26,58,122,0.08);
        }

        .block-title {
            font-family: var(--font-display);
            font-size: 15px;
            color: #15233C;
            margin: 18px 0 12px;
        }

        .post-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .post-card {
            background: rgba(26,58,122,0.04);
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: var(--radius-md);
            padding: 14px;
        }

        .post-top {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }

        .post-author {
            font-size: 13px;
            font-weight: 600;
            color: #15233C;
        }

        .post-date {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .post-content {
            font-size: 14px;
            color: #31415f;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .post-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .post-meta span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: rgba(21, 35, 60, 0.75);
            padding: 6px 10px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid rgba(26,58,122,0.08);
        }

        .post-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .review-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .review-item {
            background: rgba(26,58,122,0.04);
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: var(--radius-md);
            padding: 14px;
        }

        .review-top,
        .comment-top {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 6px;
        }

        .review-name,
        .comment-name {
            font-size: 14px;
            font-weight: 600;
            color: #15233C;
        }

        .review-date,
        .comment-date {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .review-stars {
            color: #FF6B1A;
            font-size: 16px;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .review-text,
        .comment-text {
            font-size: 13px;
            color: #31415f;
            line-height: 1.6;
        }

        .review-form-card {
            background: #ffffff;
            border: 1px solid rgba(26,58,122,0.10);
            border-radius: var(--radius-lg);
            box-shadow: 0 4px 16px rgba(26,58,122,0.06);
            margin-bottom: 28px;
            overflow: hidden;
        }

        .review-form-head {
            padding: 18px 22px;
            border-bottom: 1px solid rgba(26,58,122,0.08);
            background: rgba(26,58,122,0.02);
        }

        .review-form-title {
            font-family: var(--font-display);
            font-size: 17px;
            color: #15233C;
            font-weight: 600;
        }

        .review-form-sub {
            font-size: 13px;
            color: rgba(21,35,60,0.75);
            margin-top: 4px;
        }

        .review-form-body {
            padding: 22px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            color: #15233C;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            min-height: 44px;
            border: 1px solid rgba(26,58,122,0.10);
            border-radius: 10px;
            background: rgba(26,58,122,0.03);
            color: #15233C;
            padding: 10px 12px;
            font-size: 14px;
            outline: none;
        }

        textarea.form-control {
            min-height: 110px;
            resize: vertical;
        }

        .field-error {
            min-height: 18px;
            margin-top: 6px;
            font-size: 12px;
            color: #e63946;
        }

        .stars-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 2px;
        }

        .star-btn {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            border: 1px solid rgba(26,58,122,0.10);
            background: rgba(26,58,122,0.04);
            color: #c8c8c8;
            font-size: 20px;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .star-btn.active {
            color: #FF6B1A;
            border-color: rgba(255,107,26,0.22);
            background: rgba(255,107,26,0.08);
        }

        .empty-box-light {
            padding: 10px 0;
            color: rgba(21,35,60,0.75);
            font-size: 13px;
        }

        .comment-input-row,
        .reply-input-row {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .comment-list,
        .reply-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 10px;
        }

        .reply-list {
            margin-left: 18px;
        }

        .reply-item {
            background: #ffffff;
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: 12px;
            padding: 10px 12px;
        }

        .hero-panel,
        .agency-card,
        .card,
        .review-form-card {
            background: #ffffff !important;
            border: 1px solid rgba(26,58,122,0.10) !important;
            box-shadow: 0 8px 28px rgba(26,58,122,0.08) !important;
        }

        .hero-title,
        .agency-name,
        .section-title,
        .review-name,
        .mini-value,
        .card-title,
        h1, h2, h3, h4, h5, h6 {
            color: #15233C !important;
        }

        .hero-text,
        .card-subtitle,
        .section-sub,
        .review-date,
        .review-text,
        .post-date,
        .post-content,
        .contact-label,
        .contact-value,
        .mini-label {
            color: rgba(21, 35, 60, 0.75) !important;
        }

        .mini-box,
        .contact-box,
        .post-card,
        .review-item,
        .stat-box {
            background: rgba(26,58,122,0.04) !important;
            border: 1px solid rgba(26,58,122,0.08) !important;
        }

        .badge-active {
            background: rgba(26,58,122,0.10) !important;
            color: #1A3A7A !important;
            border: 1px solid rgba(26,58,122,0.20) !important;
        }

        .badge-info,
        .badge-pending {
            background: rgba(255,107,26,0.10) !important;
            color: #FF6B1A !important;
            border: 1px solid rgba(255,107,26,0.20) !important;
        }

        .page-title-main {
            color: #15233C !important;
        }

        .page-breadcrumb,
        .page-breadcrumb a {
            color: #ffffff !important;
        }

        .page-breadcrumb span {
            color: #00d2ff !important;
        }

        @media (max-width: 1100px) {
            .hero-grid,
            .agency-grid,
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .agency-contact,
            .agency-stats {
                grid-template-columns: 1fr;
            }

            .chat-input-row,
            .comment-input-row,
            .reply-input-row {
                flex-direction: column;
            }
        }


        /* Premium RDV Modal */
        #rdvModal .modal-content {
            border-radius: 24px;
            border: none;
            box-shadow: 0 25px 80px rgba(26,58,122,0.25);
            background: #ffffff;
            overflow: hidden;
        }
        #rdvModal .modal-header {
            background: linear-gradient(135deg, rgba(26,58,122,0.03) 0%, rgba(255,107,26,0.05) 100%);
            border-bottom: 1px solid rgba(26,58,122,0.08);
            padding: 28px 32px 20px;
            position: relative;
        }
        #rdvModal .modal-title {
            color: #15233C;
            font-size: 22px;
            font-weight: 800;
            font-family: var(--font-display);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        #rdvModal .modal-title i {
            color: #FF6B1A;
            background: rgba(255,107,26,0.15);
            padding: 10px;
            border-radius: 12px;
            font-size: 20px;
        }
        #rdvModal .btn-close {
            background-color: rgba(26,58,122,0.05);
            border-radius: 50%;
            opacity: 1;
            padding: 12px;
            margin: 0;
            position: absolute;
            top: 24px;
            right: 24px;
            transition: all 0.2s;
        }
        #rdvModal .btn-close:hover {
            background-color: rgba(230,57,70,0.1);
            color: #e63946;
            transform: rotate(90deg);
        }
        #rdvModal .modal-body {
            padding: 24px 32px;
        }
        .agency-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(26,58,122,0.06);
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 600;
            color: #15233C;
            margin-bottom: 24px;
            font-size: 14px;
        }
        #rdvModal .form-label {
            font-size: 13px;
            font-weight: 600;
            color: rgba(21,35,60,0.8);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        #rdvModal .form-control {
            border-radius: 14px;
            border: 2px solid rgba(26,58,122,0.08);
            background: #fbfdff;
            padding: 14px 16px;
            color: #15233C;
            font-size: 15px;
            transition: all 0.3s;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.01);
        }
        #rdvModal .form-control:focus {
            border-color: #FF6B1A;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(255,107,26,0.1);
        }
        #rdvModal select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2315233C' class='bi bi-chevron-down' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 12px;
            padding-right: 40px;
        }
        #rdvModal .modal-footer {
            border-top: 1px solid rgba(26,58,122,0.08);
            padding: 24px 32px;
            background: #fbfdff;
            gap: 12px;
        }
        #rdvModal .btn-cancel {
            border-radius: 12px;
            border: 2px solid rgba(26,58,122,0.1);
            color: #15233C;
            font-weight: 600;
            padding: 12px 24px;
            background: transparent;
            transition: all 0.2s;
        }
        #rdvModal .btn-cancel:hover {
            background: rgba(26,58,122,0.05);
            border-color: rgba(26,58,122,0.2);
        }
        #rdvModal .btn-confirm {
            border-radius: 12px;
            background: #FF6B1A;
            color: #ffffff;
            font-weight: 600;
            padding: 12px 32px;
            border: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255,107,26,0.3);
        }
        #rdvModal .btn-confirm:hover {
            background: #e65c12;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255,107,26,0.4);
        }
    </style>
</head>
<body>

<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">

    <!-- ===== NAVBAR ===== -->
    <?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title-main">Nos agences</div>
                <div class="page-breadcrumb">
                    <i class="bi bi-house"></i>
                    <a href="client.php" style="color:inherit;text-decoration:none;">Accueil</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <span>Agences</span>
                </div>
            </div>
        </div>

        <div class="content">

            <div class="hero-panel">
                <div class="hero-grid">
                    <div>
                        <div class="hero-title">Une assurance <span>nationale</span>, simple et digitale</div>
                        <div class="hero-text">
                            Retrouvez les coordonnées de nos agences, leurs dernières publications
                            et utilisez notre assistant pour obtenir une estimation rapide.
                        </div>

                        <div class="hero-actions">
                            <a href="#chatbot" class="btn-nav-primary">
                                <i class="bi bi-robot"></i> Estimer avec le chatbot
                            </a>
                            <a href="#agences-list" class="btn-soft">
                                <i class="bi bi-geo-alt"></i> Voir les agences
                            </a>
                        </div>
                    </div>

                    <div class="hero-boxes">
                        <div class="mini-box">
                            <div class="mini-value" id="heroAgencyCount">4</div>
                            <div class="mini-label">Agences visibles</div>
                        </div>
                        <div class="mini-box">
                            <div class="mini-value" id="heroReviewCount">0</div>
                            <div class="mini-label">Avis clients</div>
                        </div>
                        <div class="mini-box">
                            <div class="mini-value" id="heroAverage">0.0/5</div>
                            <div class="mini-label">Satisfaction moyenne</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom:28px;overflow:hidden;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;min-height:220px;">
                    <div style="padding:28px;display:flex;flex-direction:column;justify-content:center;">
                        <div style="font-family:var(--font-display);font-size:22px;color:#15233C;margin-bottom:16px;">
                            <i class="bi bi-globe" style="color:#FF6B1A;"></i> Couverture Nationale
                        </div>
                        <p style="font-size:14px;color:rgba(21,35,60,0.75);line-height:1.7;margin-bottom:16px;">
                            Protex est présent dans toute la Tunisie avec un réseau d'agences couvrant l'ensemble du territoire.
                        </p>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                            <div style="background:rgba(26,58,122,0.04);border:1px solid rgba(26,58,122,0.08);border-radius:12px;padding:14px;text-align:center;">
                                <div style="font-family:var(--font-display);font-size:22px;color:#15233C;" id="mapAgencyCount">4</div>
                                <div style="font-size:12px;color:rgba(21,35,60,0.75);margin-top:4px;">Agences</div>
                            </div>
                            <div style="background:rgba(26,58,122,0.04);border:1px solid rgba(26,58,122,0.08);border-radius:12px;padding:14px;text-align:center;">
                                <div style="font-family:var(--font-display);font-size:22px;color:#15233C;">24/7</div>
                                <div style="font-size:12px;color:rgba(21,35,60,0.75);margin-top:4px;">Disponible</div>
                            </div>
                            <div style="background:rgba(26,58,122,0.04);border:1px solid rgba(26,58,122,0.08);border-radius:12px;padding:14px;text-align:center;">
                                <div style="font-family:var(--font-display);font-size:22px;color:#15233C;">100%</div>
                                <div style="font-size:12px;color:rgba(21,35,60,0.75);margin-top:4px;">National</div>
                            </div>
                        </div>
                    </div>
                    <div style="background:rgba(26,58,122,0.04);display:flex;align-items:center;justify-content:center;padding:0;border-left:1px solid rgba(26,58,122,0.08);min-height:220px;">
                        <div id="leafletMiniMap" style="width:100%;height:100%;min-height:220px;"></div>
                    </div>
                </div>
            </div>

            <div class="card chat-card" id="chatbot">
                <div class="card-header">
                    <div>
                        <div class="card-title">Assistant d’estimation</div>
                        <div class="card-subtitle">Estimation rapide par chatbot</div>
                    </div>
                    <span class="badge badge-info">En ligne</span>
                </div>

                <div class="card-body">
                    <div class="chat-messages" id="chatMessages">
                        <div class="chat-bubble bot">
                            Bonjour 👋 Je peux vous aider à estimer une assurance auto, santé ou habitation.
                        </div>
                    </div>

                    <div class="chat-input-row">
                        <input type="text" id="chatInput" class="chat-input" placeholder="Ex : je veux une assurance auto">
                        <button class="btn-nav-primary" onclick="sendEstimateMessage()">
                            <i class="bi bi-send"></i> Envoyer
                        </button>
                    </div>

                    <div class="hint-row">
                        <span class="hint-chip" onclick="fillSuggestion('Je veux une assurance auto')">Assurance auto</span>
                        <span class="hint-chip" onclick="fillSuggestion('Je veux une assurance santé')">Assurance santé</span>
                        <span class="hint-chip" onclick="fillSuggestion('Je veux une assurance habitation')">Assurance habitation</span>
                        <span class="hint-chip" onclick="fillSuggestion('Donne moi les statistiques')">Statistiques</span>
                        <span class="hint-chip" onclick="fillSuggestion('Quelle est la meilleure agence')">Top agences</span>
                        <span class="hint-chip" onclick="fillSuggestion('Taux de satisfaction')">Satisfaction</span>
                    </div>
                </div>
            </div>

            <div class="review-form-card">
                <div class="review-form-head">
                    <div class="review-form-title">Donner votre avis sur une agence</div>
                    <div class="review-form-sub">Choisissez l’agence, attribuez une note sur 5 étoiles et ajoutez un commentaire.</div>
                </div>

                <div class="review-form-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="reviewAgency">Agence</label>
                            <select id="reviewAgency" class="form-control"></select>
                            <div class="field-error" id="reviewAgencyError"></div>
                        </div>

                        <div class="form-group">
                            <label>Note</label>
                            <div class="stars-row">
                                <button type="button" class="star-btn" data-value="1">★</button>
                                <button type="button" class="star-btn" data-value="2">★</button>
                                <button type="button" class="star-btn" data-value="3">★</button>
                                <button type="button" class="star-btn" data-value="4">★</button>
                                <button type="button" class="star-btn" data-value="5">★</button>
                            </div>
                            <div class="field-error" id="reviewStarsError"></div>
                        </div>

                        <div class="form-group full">
                            <label for="reviewComment">Commentaire</label>
                            <textarea id="reviewComment" class="form-control" placeholder="Écrire votre avis..."></textarea>
                            <div class="field-error" id="reviewCommentError"></div>
                        </div>
                    </div>

                    <button class="btn-nav-primary" type="button" onclick="submitAgencyReview()">
                        <i class="bi bi-send-check"></i> Submit
                    </button>
                </div>
            </div>

            <div class="section-header" id="agences-list">
                <div>
                    <div class="section-title">Réseau d’agences</div>
                    <div class="section-sub">Coordonnées, publications récentes et avis des clients</div>
                </div>
            </div>

            <div class="agency-grid" id="agencyGrid"></div>

        </div>
    </main>
</div>

<div class="modal fade" id="rdvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar-heart"></i> Prendre rendez-vous</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="agency-badge">
                    <i class="bi bi-building"></i> Agence <span id="rdvAgencyName"></span>
                </div>
                <input type="hidden" id="rdvAgencyId">
                
                <div class="mb-4">
                    <label class="form-label">Motif de votre visite</label>
                    <select id="rdvMotif" class="form-control">
                        <option value="Souscription">Souscription - Nouveau contrat</option>
                        <option value="Sinestre">Déclaration de sinistre</option>
                        <option value="Information">Demande d'information</option>
                        <option value="Réclamation">Réclamation</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Date souhaitée</label>
                        <input type="date" id="rdvDate" class="form-control" min="">
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Créneau horaire</label>
                        <select id="rdvCreneau" class="form-control"></select>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label">Message (optionnel)</label>
                    <textarea id="rdvMessage" class="form-control" rows="3" placeholder="Précisez votre demande pour que nous puissions préparer votre visite..."></textarea>
                </div>
                <div id="rdvError" class="alert alert-danger d-none mt-3" style="border-radius:12px; font-size:14px;"></div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" data-bs-dismiss="modal">Annuler</button>
                <button class="btn-confirm" onclick="submitRdv()">
                    Confirmer <i class="bi bi-arrow-right-short" style="font-size:20px; margin-right:-4px;"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
let agences = [];
let posts = [];
let reviews = [];
let selectedStars = 0;
/* Configuration Session Client */
const CURRENT_CLIENT_ID = <?= json_encode($_SESSION['user_id'] ?? 0) ?>;
const clientAgencyId = <?= json_encode(RoleHelper::getAgenceId()) ?>;

function escapeHtml(text) {
    return String(text || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function showToast(message, type = 'success') {
    const icons = { success: 'check-circle', warning: 'exclamation-triangle', danger: 'x-circle' };
    const toast = document.createElement('div');
    toast.className = `toast-notif toast-${type}`;
    toast.innerHTML = `<i class="bi bi-${icons[type] || 'info-circle'}"></i><span>${escapeHtml(message)}</span>`;
    document.body.appendChild(toast);

    setTimeout(() => toast.classList.add('show'), 50);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

async function apiPost(url, data) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    const text = await response.text();
    let result;

    try {
        result = JSON.parse(text);
    } catch (e) {
        console.error('Réponse brute API :', text);
        throw { message: 'Réponse non JSON du serveur.' };
    }

    if (!response.ok || !result.success) {
        throw result;
    }

    return result;
}

async function loadAgences() {
    const response = await fetch('get_agences_public.php');
    const text = await response.text();
    console.log('get_agences =>', text);

    const result = JSON.parse(text);
    if (!result.success) throw new Error(result.message || 'Erreur chargement agences');

    agences = result.data || result.agences || [];

    const select = document.getElementById('reviewAgency');
    if (select) {
        let filteredAgences = agences;
        if (clientAgencyId) {
            filteredAgences = agences.filter(a => parseInt(a.id_agence) === parseInt(clientAgencyId));
        }

        if (filteredAgences.length === 0 && agences.length > 0) {
            select.innerHTML = '<option value="">Aucune agence correspondante</option>';
        } else {
            select.innerHTML = filteredAgences.map(a => {
                const isSelected = (clientAgencyId && parseInt(a.id_agence) === parseInt(clientAgencyId)) ? 'selected' : '';
                return `<option value="${a.id_agence}" ${isSelected}>${escapeHtml(a.nom_agence)}</option>`;
            }).join('');
            
            // Si c'est l'agence du client, on verrouille le choix
            if (clientAgencyId) {
                select.disabled = true;
                select.style.background = 'rgba(255,255,255,0.05)';
                select.style.cursor = 'not-allowed';
            }
        }
    }
}

async function loadPosts() {
    const response = await fetch('get_posts.php');
    const text = await response.text();
    console.log('get_posts =>', text);

    const result = JSON.parse(text);
    if (!result.success) throw new Error(result.message || 'Erreur chargement posts');

    posts = result.posts || [];
}

async function loadReviews() {
    const response = await fetch('get_reviews.php');
    const text = await response.text();
    console.log('get_reviews =>', text);

    const result = JSON.parse(text);
    if (!result.success) throw new Error(result.message || 'Erreur chargement avis');

    reviews = result.reviews || [];
}

function getAgencyPosts(idAgence) {
    return posts
        .filter(post => Number(post.id_agence) === Number(idAgence))
        .sort((a, b) => new Date(b.date_publication) - new Date(a.date_publication));
}

function getAgencyReviews(idAgence) {
    return reviews
        .filter(review => Number(review.id_agence) === Number(idAgence) && !isHidden(review))
        .sort((a, b) => new Date(b.date_avis) - new Date(a.date_avis));
}

function getAgencyStats(idAgence) {
    const agencyReviews = getAgencyReviews(idAgence);
    const total = agencyReviews.length;
    const sum = agencyReviews.reduce((acc, item) => acc + Number(item.note || 0), 0);
    const average = total ? (sum / total) : 0;

    return {
        total,
        average: total ? average.toFixed(1) : '0.0',
        five: agencyReviews.filter(r => Number(r.note) === 5).length,
        four: agencyReviews.filter(r => Number(r.note) === 4).length,
        three: agencyReviews.filter(r => Number(r.note) === 3).length,
        two: agencyReviews.filter(r => Number(r.note) === 2).length,
        one: agencyReviews.filter(r => Number(r.note) === 1).length
    };
}

function getGlobalStats() {
    const totalReviews = reviews.length;
    const sum = reviews.reduce((acc, item) => acc + Number(item.note || 0), 0);
    return {
        totalReviews,
        average: totalReviews ? (sum / totalReviews).toFixed(1) : '0.0'
    };
}

function renderHeroStats() {
    const globalStats = getGlobalStats();
    document.getElementById('heroAgencyCount').textContent = agences.length;
    var mapCount = document.getElementById('mapAgencyCount');
    if (mapCount) mapCount.textContent = agences.length;
    document.getElementById('heroReviewCount').textContent = globalStats.totalReviews;
    document.getElementById('heroAverage').textContent = globalStats.average + '/5';
}

function renderStars(note) {
    const n = Number(note || 0);
    return '★'.repeat(n) + '☆'.repeat(5 - n);
}

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

function formatDateTime(date) {
    if (!date) return '';
    return new Date(date).toLocaleString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function isHidden(c) {
    return Number(c.hidden) === 1;
}

function renderComments(post) {
    var visible = (post.comments || []).filter(function (c) { return !isHidden(c); });

    if (visible.length === 0) {
        return '<div class="empty-box-light">Aucun commentaire pour le moment.</div>';
    }

    return visible.map(function (comment) {
        var visibleReplies = (comment.reponses || []).filter(function (r) { return !isHidden(r); });
        return '<div class="review-item">' +
            '<div class="comment-top">' +
                '<div class="comment-name">' + escapeHtml(comment.auteur) + '</div>' +
                '<div class="comment-date">' + formatDateTime(comment.date_commentaire) + '</div>' +
            '</div>' +
            '<div class="comment-text">' + escapeHtml(comment.contenu) + '</div>' +
            '<div class="reply-input-row">' +
                '<input type="text" id="replyInput-' + post.id_poste + '-' + comment.id_commentaire + '" class="form-control" placeholder="Répondre à ce commentaire...">' +
                '<button class="btn-soft" type="button" onclick="addReply(' + post.id_poste + ', ' + comment.id_commentaire + ')">' +
                    '<i class="bi bi-reply"></i> Répondre' +
                '</button>' +
            '</div>' +
            (visibleReplies.length ? '<div class="reply-list">' +
                visibleReplies.map(function (reply) {
                    return '<div class="reply-item">' +
                        '<div class="comment-top">' +
                            '<div class="comment-name">' + escapeHtml(reply.auteur) + '</div>' +
                            '<div class="comment-date">' + formatDateTime(reply.date_commentaire) + '</div>' +
                        '</div>' +
                        '<div class="comment-text">' + escapeHtml(reply.contenu) + '</div>' +
                    '</div>';
                }).join('') +
            '</div>' : '') +
        '</div>';
    }).join('');
}

function renderAgencies() {
    const grid = document.getElementById('agencyGrid');
    if (!grid) return;

    grid.innerHTML = agences.map(agency => {
        const agencyPosts = getAgencyPosts(agency.id_agence).slice(0, 3);
        const agencyReviews = getAgencyReviews(agency.id_agence).slice(0, 3);
        const stats = getAgencyStats(agency.id_agence);

        return `
            <div class="agency-card">
                <div class="agency-head">
                    <div>
                        <div class="agency-name">${escapeHtml(agency.nom_agence)}</div>
                        <div class="agency-country"><i class="bi bi-geo-alt"></i> ${escapeHtml(agency.pays || 'Tunisie')}</div>
                    </div>
                    <span class="badge badge-active">${agencyPosts.length} publication(s)</span>
                </div>

                <div class="agency-body">
                    <div class="agency-contact">
                        <div class="contact-box">
                            <div class="contact-label">Téléphone</div>
                            <div class="contact-value">${escapeHtml(agency.tel || 'Non disponible')}</div>
                        </div>
                        <div class="contact-box">
                            <div class="contact-label">E-mail</div>
                            <div class="contact-value">${escapeHtml(agency.email || 'Non disponible')}</div>
                        </div>
                    </div>

                    <div class="agency-stats">
                        <div class="stat-box">
                            <div class="stat-value">${stats.average}/5</div>
                            <div class="stat-label">Note moyenne</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value">${stats.total}</div>
                            <div class="stat-label">Nombre d’avis</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value">${agencyPosts.length}</div>
                            <div class="stat-label">Posts visibles</div>
                        </div>
                    </div>
                    <div class="agency-ouverture" style="font-size:13px;padding:8px 12px;background:rgba(255,107,26,0.06);border-radius:8px;margin:8px 0;">
                        ${agency.ouverture ? agency.ouverture.statut : '🟢 Ouvert'}
                    </div>

                    <div class="review-summary">
                        <span><i class="bi bi-star-fill"></i> 5★ ${stats.five}</span>
                        <span><i class="bi bi-star-fill"></i> 4★ ${stats.four}</span>
                        <span><i class="bi bi-star-fill"></i> 3★ ${stats.three}</span>
                        <span><i class="bi bi-star-fill"></i> 2★ ${stats.two}</span>
                        <span><i class="bi bi-star-fill"></i> 1★ ${stats.one}</span>
                    </div>

                    <div class="agency-actions" style="display:flex;gap:8px;margin:8px 0;flex-wrap:wrap;">
                        <button class="btn-soft" type="button" onclick="openRdvModal(${agency.id_agence}, '${escapeHtml(agency.nom_agence)}')">
                            <i class="bi bi-calendar-check"></i> Prendre RDV
                        </button>
                    </div>

                    <div class="block-title">Publications récentes</div>
                    <div class="post-list">
                        ${agencyPosts.length ? agencyPosts.map(post => `
                            <div class="post-card">
                                <div class="post-top">
                                    <div class="post-author">${escapeHtml(post.auteur)}</div>
                                    <div class="post-date">${formatDate(post.date_publication)}</div>
                                </div>
                                <div class="post-content">${escapeHtml(post.contenu)}</div>

                                <div class="post-meta">
                                    <span><i class="bi bi-heart"></i> ${post.nb_likes || 0}</span>
                                    <span><i class="bi bi-chat-left-text"></i> ${post.nb_commentaires || 0}</span>
                                </div>

                                <div class="post-actions">
                                    <button class="btn-soft" type="button" onclick="likePost(${post.id_poste})">
                                        <i class="bi bi-heart-fill"></i> J'aime
                                    </button>
                                </div>

                                <div class="comment-input-row">
                                    <input type="text" id="commentInput-${post.id_poste}" class="form-control" placeholder="Écrire un commentaire...">
                                    <button class="btn-soft" type="button" onclick="addComment(${post.id_poste})">
                                        <i class="bi bi-send"></i> Commenter
                                    </button>
                                </div>

                                <div class="comment-list">
                                    ${renderComments(post)}
                                </div>
                            </div>
                        `).join('') : `
                            <div class="empty-box-light">Aucune publication pour le moment.</div>
                        `}
                    </div>

                    <div class="block-title">Avis clients</div>
                    <div class="review-list">
                        ${agencyReviews.length ? agencyReviews.map(review => `
                            <div class="review-item">
                                <div class="review-top">
                                    <div class="review-name">${escapeHtml(review.auteur || 'Client')}</div>
                                    <div class="review-date">${formatDate(review.date_avis)}</div>
                                </div>
                                <div class="review-stars">${renderStars(review.note)}</div>
                                <div class="review-text">${escapeHtml(review.commentaire)}</div>
                            </div>
                        `).join('') : `
                            <div class="empty-box-light">Aucun avis pour le moment.</div>
                        `}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

let rdvMap = null;

function initMiniMap() {
    const el = document.getElementById('leafletMiniMap');
    if (!el) return;
    if (rdvMap) { rdvMap.invalidateSize(); return; }
    const hasCoords = agences.some(a => a.latitude && a.longitude);
    const center = [34.0, 9.5];
    const zoom = hasCoords ? 7 : 6;
    rdvMap = L.map('leafletMiniMap', { zoomControl: false, attributionControl: false }).setView(center, zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18
    }).addTo(rdvMap);
    if (hasCoords) {
        agences.forEach(function (a) {
            if (!a.latitude || !a.longitude) return;
            L.circleMarker([a.latitude, a.longitude], {
                radius: 8, fillColor: '#FF6B1A', color: '#fff', weight: 2, opacity: 1, fillOpacity: 0.9
            }).addTo(rdvMap).bindTooltip(a.nom_agence, { direction: 'top', offset: [0, -10] });
        });
    }
}

function openRdvModal(idAgence, nomAgence) {
    document.getElementById('rdvAgencyId').value = idAgence;
    document.getElementById('rdvAgencyName').textContent = nomAgence;
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('rdvDate').setAttribute('min', today);
    document.getElementById('rdvDate').value = today;
    document.getElementById('rdvError').classList.add('d-none');
    loadCreneaux(idAgence, today);
    new bootstrap.Modal(document.getElementById('rdvModal')).show();
}

async function loadCreneaux(idAgence, date) {
    const sel = document.getElementById('rdvCreneau');
    sel.innerHTML = '<option value="">Chargement...</option>';
    try {
        const resp = await fetch(`api.php?action=disponibilites_agence&id=${idAgence}&date=${date}`);
        const json = await resp.json();
        if (json.success && json.slots && json.slots.length) {
            sel.innerHTML = json.slots.map(c => `<option value="${c}">${c}</option>`).join('');
        } else {
            sel.innerHTML = '<option value="">Aucun créneau disponible</option>';
        }
    } catch (_) {
        sel.innerHTML = '<option value="">Erreur chargement</option>';
    }
}

async function submitRdv() {
    const idAgence = document.getElementById('rdvAgencyId').value;
    const motif = document.getElementById('rdvMotif').value;
    const date = document.getElementById('rdvDate').value;
    const creneau = document.getElementById('rdvCreneau').value;
    const message = document.getElementById('rdvMessage').value.trim();
    const err = document.getElementById('rdvError');
    err.classList.add('d-none');
    if (!date || !creneau) {
        err.textContent = 'Veuillez choisir une date et un créneau.';
        err.classList.remove('d-none');
        return;
    }
    try {
        const resp = await fetch('api.php?action=creer_rdv', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id_agence: idAgence, motif, date_rdv: date + ' ' + creneau })
        });
        const json = await resp.json();
        if (json.success) {
            bootstrap.Modal.getInstance(document.getElementById('rdvModal')).hide();
            showToast('Rendez-vous confirmé ! Un agent vous contactera.', 'success');
        } else {
            err.textContent = json.message || 'Erreur lors de la réservation.';
            err.classList.remove('d-none');
        }
    } catch (_) {
        err.textContent = 'Erreur réseau.';
        err.classList.remove('d-none');
    }
}

function clearReviewErrors() {
    document.getElementById('reviewAgencyError').textContent = '';
    document.getElementById('reviewStarsError').textContent = '';
    document.getElementById('reviewCommentError').textContent = '';
}

function setSelectedStars(value) {
    selectedStars = value;
    document.querySelectorAll('.star-btn').forEach(btn => {
        const btnValue = Number(btn.getAttribute('data-value'));
        btn.classList.toggle('active', btnValue <= value);
    });
}

function validateReviewForm() {
    clearReviewErrors();
    let valid = true;

    const agency = document.getElementById('reviewAgency').value;
    const comment = document.getElementById('reviewComment').value.trim();

    if (!agency) {
        document.getElementById('reviewAgencyError').textContent = 'Veuillez choisir une agence.';
        valid = false;
    }

    if (!selectedStars) {
        document.getElementById('reviewStarsError').textContent = 'Veuillez choisir une note.';
        valid = false;
    }

    if (!comment) {
        document.getElementById('reviewCommentError').textContent = 'Veuillez écrire un commentaire.';
        valid = false;
    }

    return valid;
}

function resetReviewForm() {
    const select = document.getElementById('reviewAgency');
    if (select && !select.disabled) {
        select.value = '';
    }
    document.getElementById('reviewComment').value = '';
    setSelectedStars(0);
    clearReviewErrors();
}

async function submitAgencyReview() {
    if (!validateReviewForm()) {
        showToast('Veuillez corriger les champs en rouge.', 'warning');
        return;
    }

    const id_agence = parseInt(document.getElementById('reviewAgency').value, 10);
    const commentaire = document.getElementById('reviewComment').value.trim();

    try {
        const result = await apiPost('add_review.php', {
            id_client: CURRENT_CLIENT_ID,
            id_agence,
            note: selectedStars,
            commentaire
        });

        resetReviewForm();
        showToast(result.message || 'Avis envoyé avec succès.', 'success');
        await refreshAgencesData();
    } catch (err) {
        showToast(err.message || 'Erreur envoi avis.', 'danger');
    }
}

async function likePost(idPoste) {
    try {
        const result = await apiPost('like_post.php', {
            id_poste: idPoste,
            id_client: CURRENT_CLIENT_ID
        });

        showToast(result.message || 'Like ajouté.', 'success');
        await refreshAgencesData();
    } catch (err) {
        showToast(err.message || 'Erreur like.', 'warning');
    }
}

async function addComment(idPoste) {
    const input = document.getElementById(`commentInput-${idPoste}`);
    const contenu = input.value.trim();

    if (!contenu) {
        showToast('Veuillez écrire un commentaire.', 'warning');
        return;
    }

    try {
        const result = await apiPost('add_comment.php', {
            id_poste: idPoste,
            id_client: CURRENT_CLIENT_ID,
            contenu
        });

        input.value = '';
        showToast(result.message || 'Commentaire ajouté.', 'success');
        await refreshAgencesData();
    } catch (err) {
        showToast(err.message || 'Erreur commentaire.', 'danger');
    }
}

async function addReply(idPoste, idCommentaireParent) {
    const input = document.getElementById(`replyInput-${idPoste}-${idCommentaireParent}`);
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
            contenu
        });

        input.value = '';
        showToast(result.message || 'Réponse ajoutée.', 'success');
        await refreshAgencesData();
    } catch (err) {
        showToast(err.message || 'Erreur réponse.', 'danger');
    }
}

async function refreshAgencesData() {
    const errors = [];

    try {
        await loadAgences();
    } catch (e) {
        console.error(e);
        errors.push('agences');
        agences = [];
    }

    try {
        await loadPosts();
    } catch (e) {
        console.error(e);
        errors.push('posts');
        posts = [];
    }

    try {
        await loadReviews();
    } catch (e) {
        console.error(e);
        errors.push('avis');
        reviews = [];
    }

    renderHeroStats();
    renderAgencies();
    initMiniMap();

    if (errors.length) {
        showToast('Chargement partiel : ' + errors.join(', '), 'warning');
    }
}

function fillSuggestion(text) {
    const input = document.getElementById('chatInput');
    if (!input) return;
    input.value = text;
    input.focus();
}

function sendEstimateMessage() {
    const input = document.getElementById('chatInput');
    const messages = document.getElementById('chatMessages');
    if (!input || !messages) return;

    const userText = input.value.trim();
    if (!userText) return;

    const userBubble = document.createElement('div');
    userBubble.className = 'chat-bubble user';
    userBubble.textContent = userText;
    messages.appendChild(userBubble);

    const text = userText.toLowerCase();
    let response = "";

    if (text.includes('statistique') || text.includes('stat') || text.includes('aperçu') || text.includes('rapport')) {
        var gStats = getGlobalStats();
        var bestAgency = null, bestAvg = 0, worstAgency = null, worstAvg = 5;
        agences.forEach(function (a) {
            var s = getAgencyStats(a.id_agence);
            if (s.total > 0) {
                var avg = parseFloat(s.average);
                if (avg > bestAvg) { bestAvg = avg; bestAgency = a; }
                if (avg < worstAvg) { worstAvg = avg; worstAgency = a; }
            }
        });
        response = "📊 <b>Aperçu des avis clients</b><br>" +
            "• Total avis : <b>" + gStats.totalReviews + "</b><br>" +
            "• Note moyenne : <b>" + gStats.average + "/5</b><br>" +
            (bestAgency ? "• Meilleure agence : <b>" + escapeHtml(bestAgency.nom_agence) + "</b> (" + bestAvg.toFixed(1) + "/5)<br>" : "") +
            (worstAgency ? "• Agence à améliorer : <b>" + escapeHtml(worstAgency.nom_agence) + "</b> (" + worstAvg.toFixed(1) + "/5)" : "") +
            "<br><br> Tapez <b>\"avis [nom agence]\"</b> pour voir les détails d'une agence.";
    } else if (text.includes('avis') || text.includes('note') || text.includes('évaluation') || text.includes('evaluation')) {
        var foundAgency = null;
        agences.forEach(function (a) {
            if (text.includes(a.nom_agence.toLowerCase())) { foundAgency = a; }
        });
        if (foundAgency) {
            var s = getAgencyStats(foundAgency.id_agence);
            response = "⭐ <b>" + escapeHtml(foundAgency.nom_agence) + "</b><br>" +
                "• Note moyenne : <b>" + s.average + "/5</b><br>" +
                "• Total avis : <b>" + s.total + "</b><br>" +
                "• Répartition : 5★ (" + s.five + ") 4★ (" + s.four + ") 3★ (" + s.three + ") 2★ (" + s.two + ") 1★ (" + s.one + ")<br>" +
                (s.total > 0 ? "• Satisfaction : <b>" + ((s.five + s.four) / s.total * 100).toFixed(0) + "%</b> d'avis positifs (4★ et 5★)" : "");
        } else {
            response = "⭐ <b>Statistiques globales</b><br>" +
                "• Note moyenne globale : <b>" + getGlobalStats().average + "/5</b><br>" +
                "• Total des avis : <b>" + getGlobalStats().totalReviews + "</b><br><br>" +
                "Pour voir les détails d'une agence spécifique, tapez :<br><b>\"avis [nom de l'agence]\"</b> (ex: avis Tunis Centre)";
        }
    } else if (text.includes('meilleur') || text.includes('top') || text.includes('mieux')) {
        var ranked = agences.filter(function (a) { return getAgencyStats(a.id_agence).total > 0; });
        ranked.sort(function (a, b) { return parseFloat(getAgencyStats(b.id_agence).average) - parseFloat(getAgencyStats(a.id_agence).average); });
        var top3 = ranked.slice(0, 3);
        if (top3.length) {
            response = "🏆 <b>Top des agences les mieux notées</b><br>";
            top3.forEach(function (a, i) {
                var s = getAgencyStats(a.id_agence);
                response += (i + 1) + ". <b>" + escapeHtml(a.nom_agence) + "</b> — " + s.average + "/5 (" + s.total + " avis)<br>";
            });
        } else {
            response = "Aucun avis disponible pour le moment.";
        }
    } else if (text.includes('pire') || text.includes('moins') || text.includes('faible') || text.includes('mauvais')) {
        var ranked = agences.filter(function (a) { return getAgencyStats(a.id_agence).total > 0; });
        ranked.sort(function (a, b) { return parseFloat(getAgencyStats(a.id_agence).average) - parseFloat(getAgencyStats(b.id_agence).average); });
        var bottom3 = ranked.slice(0, 3);
        if (bottom3.length) {
            response = "📉 <b>Agences avec les notes les plus faibles</b><br>";
            bottom3.forEach(function (a, i) {
                var s = getAgencyStats(a.id_agence);
                response += (i + 1) + ". <b>" + escapeHtml(a.nom_agence) + "</b> — " + s.average + "/5 (" + s.total + " avis)<br>";
            });
        } else {
            response = "Aucun avis disponible pour le moment.";
        }
    } else if (text.includes('satisfaction') || text.includes('positif') || text.includes('content')) {
        var total = reviews.length;
        var positif = reviews.filter(function (r) { return Number(r.note) >= 4; }).length;
        var neutre = reviews.filter(function (r) { return Number(r.note) === 3; }).length;
        var negatif = reviews.filter(function (r) { return Number(r.note) <= 2; }).length;
        response = "😊 <b>Taux de satisfaction client</b><br>" +
            "• Avis positifs (4-5★) : <b>" + (total ? (positif / total * 100).toFixed(0) : 0) + "%</b> (" + positif + " avis)<br>" +
            "• Avis neutres (3★) : <b>" + (total ? (neutre / total * 100).toFixed(0) : 0) + "%</b> (" + neutre + " avis)<br>" +
            "• Avis négatifs (1-2★) : <b>" + (total ? (negatif / total * 100).toFixed(0) : 0) + "%</b> (" + negatif + " avis)<br><br>" +
            "Note moyenne globale : <b>" + getGlobalStats().average + "/5</b>";
    } else if (text.includes('auto') || text.includes('voiture') || text.includes('vehicule') || text.includes('véhicule')) {
        response = "🚗 <b>Assurance Auto</b><br>Prix estimé : <b>250 TND/mois</b><br>Formules disponibles : Base (200 TND), Confort (350 TND), Tous Risques (500 TND).<br>Souhaitez-vous plus de détails sur une formule ?";
    } else if (text.includes('santé') || text.includes('sante') || text.includes('medical') || text.includes('médical')) {
        response = "❤️ <b>Assurance Santé</b><br>Prix estimé : <b>400 TND/mois</b><br>Formules : Essentielle (350 TND), Premium (550 TND), VIP (800 TND).<br>Souhaitez-vous plus de détails ?";
    } else if (text.includes('habitation') || text.includes('maison') || text.includes('appartement')) {
        response = "🏠 <b>Assurance Habitation</b><br>Prix estimé : <b>320 TND/mois</b><br>Formules : Base (280 TND), Confort (400 TND), Premium (600 TND).<br>Souhaitez-vous plus de détails ?";
    } else if (text.includes('prix') || text.includes('tarif') || text.includes('cout') || text.includes('coût') || text.includes('combien')) {
        response = "💰 Les prix varient selon le type d'assurance :<br>- Auto : à partir de 250 TND/mois<br>- Santé : à partir de 400 TND/mois<br>- Habitation : à partir de 320 TND/mois<br>Quel type vous intéresse ?";
    } else if (text.includes('contact') || text.includes('telephone') || text.includes('téléphone') || text.includes('numero') || text.includes('numéro')) {
        var telList = agences.map(function (a) { return '<b>' + escapeHtml(a.nom_agence) + '</b> : ' + escapeHtml(a.tel || 'N/A'); }).join('<br>');
        response = "📞 <b>Nos agences :</b><br>" + telList + "<br><br>Horaires : Lun-Ven 8h-18h, Sam 9h-13h.";
    } else if (text.includes('assistance') || text.includes('urgence') || text.includes('accident')) {
        response = "🆘 <b>Assistance 24/7</b><br>Numéro d'urgence : <b>+216 71 111 222</b><br>Service disponible 24h/24 et 7j/7.<br>Un agent vous répondra immédiatement.";
    } else if (text.includes('franchise')) {
        response = "📋 <b>Franchise</b><br>La franchise est la partie qui reste à votre charge en cas de sinistre. Elle varie selon le contrat :<br>- Auto : 500 TND à 2000 TND<br>- Santé : 300 TND à 1500 TND<br>- Habitation : 400 TND à 2000 TND";
    } else if (text.includes('formule') || text.includes('couverture')) {
        response = "📑 Nos formules couvrent :<br>- <b>Base</b> : Responsabilité civile + garanties essentielles<br>- <b>Confort</b> : Base + vol + incendie<br>- <b>Premium</b> : Confort + bris de glace + assistance<br>- <b>Tous Risques</b> : Premium + dommages tous accidents + véhicule de remplacement";
    } else if (text.includes('réclamation') || text.includes('reclamation') || text.includes('plainte')) {
        response = "📝 Pour déposer une réclamation, veuillez nous contacter au <b>+216 71 111 333</b> ou vous rendre dans l'agence la plus proche. Vous pouvez aussi envoyer un email à <b>reclamation@protex.tn</b>.";
    } else if (text.includes('bonjour') || text.includes('salut') || text.includes('hello')) {
        response = "👋 Bonjour ! Je suis l'assistant Protex. Je peux vous aider à :<br>- Estimer une assurance (auto, santé, habitation)<br>- Consulter les <b>statistiques</b> des avis clients<br>- Voir le <b>top</b> des agences<br>- Connaître le taux de <b>satisfaction</b><br>- Contacter une agence<br>Que souhaitez-vous savoir ?";
    } else if (text.includes('merci') || text.includes('bye')) {
        response = "🙏 Merci de votre confiance ! N'hésitez pas à revenir si vous avez d'autres questions. À bientôt !";
    } else {
        response = "🤖 Je n'ai pas bien compris votre demande. Voici ce que je peux faire :<br>• Estimer une <b>assurance auto</b><br>• Estimer une <b>assurance santé</b><br>• Estimer une <b>assurance habitation</b><br>• Consulter les <b>statistiques</b> des avis<br>• Voir le <b>top</b> des agences<br>• <b>Contacter</b> une agence<br>• <b>Assistance</b> d'urgence<br>Posez-moi votre question !";
    }

    setTimeout(function () {
        const botBubble = document.createElement('div');
        botBubble.className = 'chat-bubble bot';
        botBubble.innerHTML = response;
        messages.appendChild(botBubble);
        messages.scrollTop = messages.scrollHeight;
    }, 500);

    input.value = '';
    messages.scrollTop = messages.scrollHeight;
}

document.addEventListener('DOMContentLoaded', async function () {
    await refreshAgencesData();

    const input = document.getElementById('chatInput');
    if (input) {
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                sendEstimateMessage();
            }
        });
    }

    document.querySelectorAll('.star-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            setSelectedStars(Number(this.getAttribute('data-value')));
        });
    });

    document.getElementById('rdvDate')?.addEventListener('change', function () {
        const id = document.getElementById('rdvAgencyId').value;
        if (id) loadCreneaux(id, this.value);
    });
});
</script>

</body>
</html>

