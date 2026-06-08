<?php
/**
 * view/FrontOffice/roulette_email.php
 * Page d'accès à la roulette — Saisie email
 * Utilise le navbar unique du front office
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>🎰 Roulette de Fidélité — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="<?= BASE_URL ?>/view/FrontOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/view/FrontOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/view/FrontOffice/assets/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/view/FrontOffice/assets/css/client.css">
    <style>
        @keyframes float-in {
            from { opacity: 0; transform: translateY(30px) scale(0.96); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255,107,26,0.35); }
            50%      { box-shadow: 0 0 0 18px rgba(255,107,26,0); }
        }

        .email-page-content {
            min-height: calc(100vh - 68px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 32px;
        }

        .email-card {
            max-width: 520px;
            width: 100%;
            background: #fff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            animation: float-in 0.6s ease both;
        }

        .email-card-header {
            background: linear-gradient(135deg, #1A3A7A 0%, #0f2456 100%);
            padding: 44px 40px 38px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .email-card-header::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 200px; height: 200px;
            background: rgba(255,107,26,0.12);
            border-radius: 50%;
        }

        .email-card-header::after {
            content: '';
            position: absolute;
            bottom: -40px; left: -40px;
            width: 140px; height: 140px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
        }

        .email-card-icon {
            position: relative;
            z-index: 1;
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FF6B1A, #ff8c42);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 12px 36px rgba(255,107,26,0.4);
            animation: pulse-glow 2.5s infinite;
        }

        .email-card-icon i {
            font-size: 40px;
            color: #fff;
        }

        .email-card-header h1 {
            position: relative;
            z-index: 1;
            font-family: 'Sora', sans-serif;
            font-size: 28px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 8px;
        }

        .email-card-header p {
            position: relative;
            z-index: 1;
            color: rgba(255,255,255,0.65);
            font-size: 14px;
            line-height: 1.6;
            max-width: 380px;
            margin: 0 auto;
        }

        .email-card-body {
            padding: 36px 40px 40px;
        }

        .email-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .email-input-wrap {
            position: relative;
        }

        .email-input-wrap .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: rgba(21,35,60,0.3);
            pointer-events: none;
            transition: color 0.2s;
        }

        .email-input {
            width: 100%;
            padding: 16px 18px 16px 50px;
            border-radius: 14px;
            border: 2px solid rgba(26,58,122,0.10);
            background: #fafbff;
            color: #15233C;
            font-size: 15px;
            font-family: 'DM Sans', sans-serif;
            outline: none;
            transition: all 0.2s;
        }

        .email-input::placeholder {
            color: rgba(21,35,60,0.3);
        }

        .email-input:focus {
            border-color: #FF6B1A;
            box-shadow: 0 0 0 4px rgba(255,107,26,0.08);
            background: #fff;
        }

        .email-input:focus ~ .input-icon {
            color: #FF6B1A;
        }

        .email-btn {
            padding: 16px;
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, #FF6B1A, #e05a0f);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Sora', sans-serif;
            cursor: pointer;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 16px rgba(255,107,26,0.3);
        }

        .email-btn:hover {
            background: linear-gradient(135deg, #e05a0f, #cc4f00);
            box-shadow: 0 6px 24px rgba(255,107,26,0.4);
            transform: translateY(-2px);
        }

        .email-btn:active {
            transform: translateY(0);
        }

        .email-back {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 22px;
            color: rgba(21,35,60,0.45);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: color 0.2s;
        }

        .email-back:hover {
            color: #FF6B1A;
        }

        .email-steps {
            display: flex;
            gap: 16px;
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid rgba(26,58,122,0.06);
        }

        .email-step {
            flex: 1;
            text-align: center;
        }

        .email-step-num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(26,58,122,0.06);
            color: #1A3A7A;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            margin: 0 auto 8px;
        }

        .email-step-label {
            font-size: 11px;
            color: rgba(21,35,60,0.45);
            line-height: 1.4;
        }

        @media (max-width: 600px) {
            .email-card-header { padding: 32px 24px 28px; }
            .email-card-body { padding: 28px 24px 32px; }
            .email-steps { gap: 10px; }
            .email-card-header h1 { font-size: 22px; }
        }
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
        <div class="email-page-content">
            <div class="email-card">
                <div class="email-card-header">
                    <div class="email-card-icon">
                        <i class="bi bi-dice-5"></i>
                    </div>
                    <h1>Roulette de Fidélité</h1>
                    <p>Entrez votre email pour accéder à la roulette et vérifier vos tours disponibles 🎰</p>
                </div>

                <div class="email-card-body">
                    <form class="email-form" method="get" action="<?= BASE_URL ?>/controller/RouletteController.php">
                        <div class="email-input-wrap">
                            <input class="email-input" type="email" name="email" placeholder="votre@email.com" required autofocus>
                            <i class="bi bi-envelope input-icon"></i>
                        </div>
                        <button type="submit" class="email-btn">
                            <i class="bi bi-arrow-right"></i>
                            Accéder à la roulette
                        </button>
                    </form>

                    <a href="<?= BASE_URL ?>/view/FrontOffice/client.php" class="email-back">
                        <i class="bi bi-arrow-left"></i>
                        Retour à l'espace client
                    </a>

                    <div class="email-steps">
                        <div class="email-step">
                            <div class="email-step-num">1</div>
                            <div class="email-step-label">Entrez votre email</div>
                        </div>
                        <div class="email-step">
                            <div class="email-step-num">2</div>
                            <div class="email-step-label">Vérifiez vos spins</div>
                        </div>
                        <div class="email-step">
                            <div class="email-step-num">3</div>
                            <div class="email-step-label">Tournez & gagnez</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="<?= BASE_URL ?>/view/FrontOffice/assets/js/main.js"></script>
</body>
</html>


