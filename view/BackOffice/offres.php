<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
$__base = defined('BASE_URL') ? BASE_URL : '';
$base = $__base;
?>
<script>const BASE_URL_PHP = '<?= $__base ?>';</script>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offres — BackOffice Protex</title>

    <style>
        :root {
            --bg-1: #07111f;
            --bg-2: #0b1d33;
            --card: rgba(255,255,255,0.08);
            --card-border: rgba(255,255,255,0.14);
            --text: #ffffff;
            --muted: #b8c2d1;
            --accent: #00b4d8;
            --accent-2: #0096c7;
            --shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top right, rgba(0,180,216,0.18), transparent 25%),
                radial-gradient(circle at bottom left, rgba(72,149,239,0.15), transparent 30%),
                linear-gradient(135deg, var(--bg-1), var(--bg-2));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow: hidden;
        }

        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(20px);
            opacity: 0.25;
            pointer-events: none;
        }

        .orb-1 {
            width: 220px;
            height: 220px;
            top: 8%;
            left: 8%;
            background: #00b4d8;
        }

        .orb-2 {
            width: 300px;
            height: 300px;
            right: -40px;
            bottom: 10%;
            background: #3a86ff;
        }

        .wrapper {
            width: 100%;
            max-width: 720px;
            position: relative;
            z-index: 2;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(12px);
            border-radius: 24px;
            box-shadow: var(--shadow);
            padding: 34px 30px;
            text-align: center;
        }

        .logo {
            width: 78px;
            height: 78px;
            margin: 0 auto 18px;
            border-radius: 22px;
            display: grid;
            place-items: center;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            font-size: 30px;
        }

        h1 {
            margin: 0 0 10px;
            font-size: 30px;
            font-weight: 800;
            letter-spacing: 0.3px;
        }

        .subtitle {
            margin: 0 auto 26px;
            max-width: 560px;
            color: var(--muted);
            line-height: 1.6;
            font-size: 15px;
        }

        .status-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            margin: 0 auto 28px;
            padding: 18px 20px;
            border-radius: 18px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            max-width: 520px;
        }

        .spinner {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.18);
            border-top-color: var(--accent);
            animation: spin 0.8s linear infinite;
            flex-shrink: 0;
        }

        .status-text {
            text-align: left;
        }

        .status-text strong {
            display: block;
            font-size: 15px;
            margin-bottom: 4px;
        }

        .status-text span {
            color: var(--muted);
            font-size: 13px;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .btn {
            border: none;
            cursor: pointer;
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 700;
            transition: 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: white;
            box-shadow: 0 12px 24px rgba(0,180,216,0.25);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: rgba(255,255,255,0.05);
            color: white;
            border: 1px solid rgba(255,255,255,0.12);
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.09);
        }

        .infos {
            margin-top: 28px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .info-card {
            padding: 16px 14px;
            border-radius: 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
        }

        .info-card strong {
            display: block;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .info-card span {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .footer-note {
            margin-top: 24px;
            color: var(--muted);
            font-size: 12px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 680px) {
            .card {
                padding: 28px 20px;
            }

            h1 {
                font-size: 24px;
            }

            .infos {
                grid-template-columns: 1fr;
            }

            .status-box {
                align-items: flex-start;
            }

            .status-text {
                text-align: left;
            }
        }
    </style>

    <script>
        const TARGET_URL = "<?= defined('BASE_URL') ? BASE_URL : '' ?>/controller/OffreController.php";

        function goToOffres() {
            window.location.href = TARGET_URL;
        }

        window.addEventListener("load", function () {
            setTimeout(goToOffres, 1200);
        });
    </script>
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/layout.css">
</head>
<body>
<div class="layout">
    <?php require_once __DIR__.'/assets/includes/sidebar.php'; ?>
    <main class="main">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="wrapper">
        <div class="card">
            <div class="logo">🏷️</div>

            <h1>Gestion des offres</h1>
            <p class="subtitle">
                Vous êtes dans l’interface BackOffice du module Offres.
                Cette page HTML sert d’accès visuel propre vers le module dynamique PHP de votre projet.
            </p>

            <div class="status-box">
                <div class="spinner"></div>
                <div class="status-text">
                    <strong>Redirection en cours...</strong>
                    <span>Chargement du catalogue des offres, de la gestion CRUD et des contrôles de saisie.</span>
                </div>
            </div>

            <div class="actions">
                <a class="btn btn-primary" href="<?= BASE_URL ?>/controller/OffreController.php">Ouvrir le module Offres</a>
                <a class="btn btn-secondary" href="<?= $__base ?>/view/BackOffice/admin-users.php">Retour au tableau de bord</a>
            </div>

            <div class="infos">
                <div class="info-card">
                    <strong>Catalogue</strong>
                    <span>Consulter la liste complète des offres d’assurance.</span>
                </div>
                <div class="info-card">
                    <strong>Gestion</strong>
                    <span>Ajouter, modifier, suspendre ou supprimer une offre.</span>
                </div>
                <div class="info-card">
                    <strong>Statistiques</strong>
                    <span>Suivre les offres actives, suspendues et archivées.</span>
                </div>
            </div>

            <div class="footer-note">
                Si la redirection automatique ne fonctionne pas, utilisez le bouton
                <strong>“Ouvrir le module Offres”</strong>.
            </div>

            <noscript>
                <div style="margin-top:18px; padding:14px; border-radius:12px; background:rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.25); color:#fff;">
                    JavaScript est désactivé. Cliquez ici :
                    <a href="<?= BASE_URL ?>/controller/OffreController.php" style="color:#7dd3fc; font-weight:bold;">Accéder aux offres</a>
                </div>
            </noscript>
        </div>
    </div>
    </main>
</div>
</body>
</html>
