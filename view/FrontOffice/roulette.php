<?php
/**
 * view/FrontOffice/roulette.php
 * Page de la Roulette de Fidélité Protex 🎰
 * Appelée par RouletteController.php
 */

$email = $email ?? '';
$client = $client ?? ['nom' => '', 'prenom' => 'Client', 'email' => $email ?? ''];
$nbPaiements = $nbPaiements ?? 0;
$spinsGagnes = $spinsGagnes ?? 0;
$spinsUtilises = $spinsUtilises ?? 0;
$spinsRestants = $spinsRestants ?? 0;
$eligible = $eligible ?? false;
$paiementsRestants = $paiementsRestants ?? 3;
$progress = $progress ?? 0;
$messageEligibilite = $messageEligibilite ?? '';
$cadeaux = $cadeaux ?? [];
$historique = $historique ?? [];
$nbCadeaux = count($cadeaux);
$anglePerSegment = $nbCadeaux > 0 ? 360 / $nbCadeaux : 45;

$frontBase = (defined('BASE_URL') ? BASE_URL : '') . '/view/FrontOffice';
$BASE_URL  = (defined('BASE_URL') ? BASE_URL : '');
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
    <link rel="stylesheet" href="<?= $frontBase ?>/assets/css/variables.css">
    <link rel="stylesheet" href="<?= $frontBase ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= $frontBase ?>/assets/css/layout.css">
    <link rel="stylesheet" href="<?= $frontBase ?>/assets/css/client.css">
    <style>
        /* ═══ HERO ROULETTE ═══ */
        .roulette-hero {
            background: linear-gradient(135deg, #1A3A7A 0%, #0f2456 100%);
            border-radius: 24px;
            padding: 32px 36px;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }
        .roulette-hero::before {
            content: '';
            position: absolute;
            top: -60px; right: -40px;
            width: 220px; height: 220px;
            background: rgba(255,107,26,0.12);
            border-radius: 50%;
        }
        .roulette-hero h1 {
            font-family: 'Sora', sans-serif;
            font-size: 28px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 6px;
            position: relative;
            z-index: 1;
        }
        .roulette-hero p {
            color: rgba(255,255,255,0.65);
            font-size: 14px;
            position: relative;
            z-index: 1;
        }
        .roulette-hero p strong { color: #fff; }

        /* ═══ SPINS CARD ═══ */
        .spins-card {
            background: #fff;
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: 20px;
            padding: 26px 28px;
            margin-bottom: 28px;
            box-shadow: 0 4px 20px rgba(26,58,122,0.06);
        }
        .spins-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
        }
        .spins-level {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .spins-level-icon {
            font-size: 36px;
        }
        .spins-level-info h3 {
            font-family: 'Sora', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: #15233C;
        }
        .spins-level-info p {
            font-size: 13px;
            color: rgba(21,35,60,0.55);
            margin-top: 2px;
        }
        .spins-pills {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .spins-pill {
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .spins-pill.gagne {
            background: rgba(26,58,122,0.08);
            color: #1A3A7A;
        }
        .spins-pill.utilise {
            background: rgba(16,185,129,0.1);
            color: #059669;
        }
        .spins-pill.restant {
            background: rgba(255,107,26,0.1);
            color: #FF6B1A;
        }

        /* ═══ PROGRESS ═══ */
        .progress-track {
            height: 12px;
            background: rgba(26,58,122,0.06);
            border-radius: 6px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #FF6B1A, #ffa050);
            border-radius: 6px;
            width: 0;
            transition: width 1.4s cubic-bezier(.4,0,.2,1);
        }
        .progress-label {
            font-size: 12px;
            color: rgba(21,35,60,0.45);
            margin-top: 8px;
            text-align: center;
        }
        .progress-label strong { color: #FF6B1A; }

        /* ═══ SPIN STEPS ═══ */
        .spin-steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 20px;
        }
        .spin-step {
            padding: 14px;
            border-radius: 14px;
            background: rgba(26,58,122,0.03);
            border: 1px solid rgba(26,58,122,0.06);
            text-align: center;
            position: relative;
            transition: 0.2s;
        }
        .spin-step.atteint {
            background: rgba(16,185,129,0.06);
            border-color: rgba(16,185,129,0.2);
        }
        .spin-step.actuel {
            background: rgba(255,107,26,0.06);
            border-color: rgba(255,107,26,0.2);
            box-shadow: 0 0 20px rgba(255,107,26,0.08);
        }
        .spin-step .ico { font-size: 26px; margin-bottom: 4px; }
        .spin-step .name { font-weight: 700; font-size: 13px; color: #15233C; }
        .spin-step .req { font-size: 11px; color: rgba(21,35,60,0.45); margin-top: 2px; }
        .spin-step .check {
            position: absolute; top: 8px; right: 8px;
            color: #059669; font-size: 14px;
        }

        /* ═══ WHEEL ═══ */
        .wheel-section {
            text-align: center;
            margin-bottom: 28px;
        }
        .wheel-wrap {
            position: relative;
            width: 380px;
            height: 380px;
            max-width: 100%;
            margin: 0 auto;
        }
        .wheel-svg {
            width: 100%;
            height: 100%;
            transition: transform 5s cubic-bezier(0.17, 0.67, 0.16, 1.0);
            filter: drop-shadow(0 20px 50px rgba(0,0,0,0.3));
        }
        .wheel-pointer {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 0; height: 0;
            border-left: 22px solid transparent;
            border-right: 22px solid transparent;
            border-top: 38px solid #FF6B1A;
            filter: drop-shadow(0 6px 14px rgba(255,107,26,0.5));
            z-index: 10;
        }
        .wheel-pointer::after {
            content: "";
            position: absolute;
            top: -38px; left: 50%;
            transform: translateX(-50%);
            width: 18px; height: 18px;
            background: #fff;
            border-radius: 50%;
            border: 4px solid #FF6B1A;
        }
        .wheel-center {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 100px; height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FF6B1A, #ff8c42);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
            border: 5px solid #fff;
            box-shadow: 0 12px 30px rgba(255,107,26,0.4), inset 0 -4px 12px rgba(0,0,0,0.2);
            z-index: 5;
            user-select: none;
        }
        .wheel-center:hover:not(.disabled) {
            transform: translate(-50%, -50%) scale(1.05);
        }
        .wheel-center.disabled { cursor: not-allowed; opacity: 0.6; }
        .wheel-center.spinning { cursor: progress; }
        .wheel-center .label {
            color: #fff;
            font-family: 'Sora', sans-serif;
            font-weight: 800;
            font-size: 14px;
            letter-spacing: 1px;
        }

        /* ═══ NOT ELIGIBLE ═══ */
        .not-eligible {
            background: rgba(255,107,26,0.06);
            border: 1px solid rgba(255,107,26,0.15);
            border-radius: 20px;
            padding: 32px;
            text-align: center;
        }
        .not-eligible .icon { font-size: 44px; margin-bottom: 12px; }
        .not-eligible h3 {
            font-family: 'Sora', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: #15233C;
            margin-bottom: 8px;
        }
        .not-eligible p { color: rgba(21,35,60,0.55); font-size: 13px; line-height: 1.6; }

        /* ═══ HISTORY ═══ */
        .history-card {
            background: #fff;
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: 20px;
            padding: 24px 28px;
            margin-bottom: 28px;
            box-shadow: 0 4px 20px rgba(26,58,122,0.06);
        }
        .history-card h2 {
            font-family: 'Sora', sans-serif;
            font-size: 17px;
            font-weight: 700;
            color: #15233C;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .history-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 12px;
        }
        .history-item {
            padding: 16px;
            border-radius: 14px;
            background: rgba(26,58,122,0.02);
            border: 1px solid rgba(26,58,122,0.06);
        }
        .history-item .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .history-item .tag {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            background: rgba(255,107,26,0.1);
            color: #FF6B1A;
        }
        .history-item .gain-text {
            font-size: 15px;
            font-weight: 700;
            color: #15233C;
            margin-bottom: 4px;
        }
        .history-item .promo-mini {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: rgba(21,35,60,0.5);
            background: rgba(26,58,122,0.04);
            padding: 5px 8px;
            border-radius: 6px;
            display: inline-block;
        }
        .history-item .date-mini {
            font-size: 11px;
            color: rgba(21,35,60,0.35);
            margin-top: 6px;
        }

        /* ═══ MODAL ═══ */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }
        .modal-overlay.show { display: flex; }
        .modal-card {
            background: #fff;
            color: #1a1a1a;
            padding: 40px 32px;
            border-radius: 24px;
            max-width: 460px;
            width: 100%;
            text-align: center;
            box-shadow: 0 30px 80px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
            animation: modal-in 0.4s ease both;
        }
        @keyframes modal-in {
            from { opacity: 0; transform: scale(0.8) translateY(20px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-card .gain-icon { font-size: 64px; margin-bottom: 12px; }
        .modal-card h2 {
            font-family: 'Sora', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: #1A3A7A;
            margin-bottom: 6px;
        }
        .modal-card .subtitle { color: rgba(21,35,60,0.5); font-size: 14px; margin-bottom: 16px; }
        .modal-card .gain-label {
            font-family: 'Sora', sans-serif;
            font-size: 28px;
            font-weight: 800;
            color: #FF6B1A;
            margin: 14px 0;
        }
        .promo-box {
            background: linear-gradient(135deg, #FF6B1A, #ff8c42);
            color: #fff;
            padding: 18px 20px;
            border-radius: 14px;
            margin: 16px 0;
        }
        .promo-box .promo-label {
            font-size: 11px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .promo-box .promo-code {
            font-family: 'Courier New', monospace;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 1px;
            background: rgba(255,255,255,0.18);
            padding: 10px;
            border-radius: 10px;
            border: 2px dashed rgba(255,255,255,0.4);
        }
        .copy-btn {
            margin-top: 8px;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: #fff;
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }
        .modal-card .info-text {
            color: rgba(21,35,60,0.55);
            font-size: 13px;
            margin: 12px 0 18px;
            line-height: 1.6;
        }
        .modal-actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .btn-primary {
            background: linear-gradient(135deg, #FF6B1A, #ff8c42);
            color: #fff;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255,107,26,0.3); }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            padding: 12px 24px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
        }
        .modal-card.lost h2 { color: rgba(21,35,60,0.4); }
        .modal-card.lost .gain-label { color: rgba(21,35,60,0.3); }

        .confetti {
            position: fixed; top: -10px; width: 10px; height: 10px;
            opacity: 0; pointer-events: none; z-index: 999;
        }

        @media (max-width: 600px) {
            .spin-steps { grid-template-columns: 1fr; }
            .wheel-wrap { width: 300px; height: 300px; }
            .spins-header { flex-direction: column; align-items: flex-start; }
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
        <div class="content">

            <!-- ═══ HERO ═══ -->
            <div class="roulette-hero">
                <h1>🎰 Roulette de Fidélité</h1>
                <p>Bonjour <strong><?= htmlspecialchars(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? '')) ?></strong> !
                Tournez la roulette et découvrez votre récompense.</p>
            </div>

            <!-- ═══ SPINS CARD ═══ -->
            <div class="spins-card">
                <div class="spins-header">
                    <div class="spins-level">
                        <div class="spins-level-icon">🎰</div>
                        <div class="spins-level-info">
                            <h3><?= $spinsRestants ?> tour<?= $spinsRestants > 1 ? 's' : '' ?> disponible<?= $spinsRestants > 1 ? 's' : '' ?></h3>
                            <p><?= $nbPaiements ?> paiement<?= $nbPaiements > 1 ? 's' : '' ?> validé<?= $nbPaiements > 1 ? 's' : '' ?> = <?= $spinsGagnes ?> spin<?= $spinsGagnes > 1 ? 's' : '' ?> gagné<?= $spinsGagnes > 1 ? 's' : '' ?></p>
                        </div>
                    </div>
                    <div class="spins-pills">
                        <span class="spins-pill gagne"><i class="bi bi-award"></i> <?= $spinsGagnes ?> gagnés</span>
                        <span class="spins-pill utilise"><i class="bi bi-check-circle"></i> <?= $spinsUtilises ?> utilisés</span>
                        <span class="spins-pill restant"><i class="bi bi-fire"></i> <?= $spinsRestants ?> restants</span>
                    </div>
                </div>

                <div class="progress-track">
                    <div class="progress-bar-fill" data-pct="<?= $progress ?>"></div>
                </div>
                <p class="progress-label">
                    <?= $paiementsRestants > 0
                        ? "Encore <strong>" . $paiementsRestants . "</strong> paiement" . ($paiementsRestants > 1 ? 's' : '') . " pour un nouveau spin 🎯"
                        : "Tous les spins sont débloqués !" ?>
                </p>

                <div class="spin-steps">
                    <?php for ($i = 1; $i <= 3; $i++):
                        $seuil = $i * Roulette::SEUIL_PAR_SPIN;
                        $dejaAtteint = $nbPaiements >= $seuil;
                        $prochain = $i === 1 ? $nbPaiements < $seuil : ($nbPaiements >= ($seuil - Roulette::SEUIL_PAR_SPIN) && $nbPaiements < $seuil);
                    ?>
                    <div class="spin-step <?= $dejaAtteint ? 'atteint' : '' ?> <?= $prochain ? 'actuel' : '' ?>">
                        <?php if ($dejaAtteint): ?><i class="bi bi-check-circle-fill check"></i><?php endif; ?>
                        <div class="ico"><?= $dejaAtteint ? '🎰' : '⏳' ?></div>
                        <div class="name"><?= $seuil ?> paiements</div>
                        <div class="req"><?= $dejaAtteint ? 'Débloqué ✓' : '1 spin à gagner' ?></div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <?php if ($eligible && $spinsRestants > 0): ?>

            <!-- ═══ WHEEL ═══ -->
            <div class="wheel-section">
                <div class="wheel-wrap">
                    <div class="wheel-pointer"></div>
                    <svg class="wheel-svg" id="wheelSvg" viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
                        <?php
                        $cx = 200; $cy = 200; $r = 195;
                        foreach ($cadeaux as $i => $c):
                            $startAngle = $i * $anglePerSegment - 90;
                            $endAngle   = $startAngle + $anglePerSegment;
                            $startRad   = deg2rad($startAngle);
                            $endRad     = deg2rad($endAngle);
                            $x1 = $cx + $r * cos($startRad);
                            $y1 = $cy + $r * sin($startRad);
                            $x2 = $cx + $r * cos($endRad);
                            $y2 = $cy + $r * sin($endRad);
                            $largeArc = $anglePerSegment > 180 ? 1 : 0;
                            $midAngle = ($startAngle + $endAngle) / 2;
                            $midRad   = deg2rad($midAngle);
                            $tx = $cx + ($r * 0.62) * cos($midRad);
                            $ty = $cy + ($r * 0.62) * sin($midRad);
                        ?>
                            <path d="M<?= $cx ?>,<?= $cy ?> L<?= $x1 ?>,<?= $y1 ?> A<?= $r ?>,<?= $r ?> 0 <?= $largeArc ?>,1 <?= $x2 ?>,<?= $y2 ?> Z"
                                  fill="<?= htmlspecialchars($c['couleur']) ?>"
                                  stroke="#fff" stroke-width="3"/>
                            <text x="<?= $tx ?>" y="<?= $ty ?>"
                                  fill="#fff" font-family="DM Sans, Arial" font-size="16" font-weight="800"
                                  text-anchor="middle" dominant-baseline="middle"
                                  transform="rotate(<?= $midAngle + 90 ?> <?= $tx ?> <?= $ty ?>)">
                                <?= htmlspecialchars($c['icone']) ?>
                            </text>
                        <?php endforeach; ?>
                        <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>" fill="none" stroke="#fff" stroke-width="6" opacity="0.3"/>
                        <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r - 4 ?>" fill="none" stroke="#FF6B1A" stroke-width="3" stroke-dasharray="6 4" opacity="0.5"/>
                    </svg>
                    <div class="wheel-center" id="spinBtn" onclick="spinWheel()">
                        <span class="label">TOURNER</span>
                    </div>
                </div>
                <p style="margin-top:20px;color:rgba(21,35,60,0.45);font-size:13px;">
                    Cliquez sur <strong>"TOURNER"</strong> — 🎯 Tours restants : <strong><?= $spinsRestants ?></strong>
                </p>
            </div>

            <?php else: ?>

            <!-- ═══ NOT ELIGIBLE ═══ -->
            <div class="not-eligible">
                <div class="icon">⏳</div>
                <h3>Pas encore de tour disponible</h3>
                <p><?= htmlspecialchars($messageEligibilite ?: 'Effectuez des paiements pour débloquer vos tours 🎰') ?></p>
            </div>

            <?php endif; ?>

            <!-- ═══ HISTORY ═══ -->
            <?php if (!empty($historique)): ?>
            <div class="history-card">
                <h2><i class="bi bi-trophy-fill" style="color:#f59e0b;"></i> Vos gains précédents</h2>
                <div class="history-grid">
                    <?php foreach ($historique as $h): ?>
                    <div class="history-item">
                        <div class="top">
                            <span class="tag">🎰 <?= htmlspecialchars($h['cadeau_icone'] ?? '🎁') ?></span>
                            <?php if ($h['utilise']): ?>
                                <span style="font-size:11px;color:#059669;font-weight:700;">✓ Utilisé</span>
                            <?php endif; ?>
                        </div>
                        <div class="gain-text"><?= htmlspecialchars($h['cadeau_label']) ?></div>
                        <div class="promo-mini"><?= htmlspecialchars($h['code_promo']) ?></div>
                        <div class="date-mini"><?= date('d/m/Y H:i', strtotime($h['date_jeu'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<!-- ═══ MODAL ═══ -->
<div class="modal-overlay" id="modalWin">
    <div class="modal-card" id="modalCard">
        <div class="gain-icon" id="modalIcon">🎉</div>
        <h2 id="modalTitle">Félicitations !</h2>
        <div class="subtitle" id="modalSubtitle">Vous avez gagné</div>
        <div class="gain-label" id="modalGainLabel">-10%</div>
        <div class="promo-box" id="modalPromoBox">
            <div class="promo-label">🎫 Votre code promo</div>
            <div class="promo-code" id="modalPromoCode">PROTEX-SPIN-XXXXXX</div>
            <button class="copy-btn" onclick="copyPromo()"><i class="bi bi-clipboard"></i> Copier</button>
        </div>
        <p class="info-text" id="modalInfoText">
            📧 Un email avec votre code promo a été envoyé.<br>Utilisez-le lors de votre prochain paiement.
        </p>
        <div class="modal-actions">
            <a href="<?= BASE_URL ?>/controller/RouletteController.php?email=<?= urlencode($email) ?>" class="btn-primary">
                <i class="bi bi-arrow-repeat"></i> Rejouer
            </a>
            <button class="btn-secondary" onclick="location.reload()">Fermer</button>
        </div>
    </div>
</div>

<script src="<?= $frontBase ?>/assets/js/main.js"></script>
<script>
const BASE_URL_JS = <?= json_encode(defined('BASE_URL') ? BASE_URL : '') ?>;
const CADEAUX = <?= json_encode($cadeaux) ?>;
const N = CADEAUX.length;
const ANGLE = 360 / N;
const EMAIL_CLIENT  = <?= json_encode($email) ?>;
const ELIGIBLE      = <?= $eligible ? 'true' : 'false' ?>;
const SPINS_RESTANTS = <?= $spinsRestants ?? 0 ?>;

let isSpinning = false;
let currentRotation = 0;

window.addEventListener('load', () => {
    const bar = document.querySelector('.progress-bar-fill');
    if (bar) setTimeout(() => { bar.style.width = (bar.dataset.pct || 0) + '%'; }, 200);
});

async function spinWheel() {
    if (!ELIGIBLE || isSpinning) return;
    isSpinning = true;
    const btn = document.getElementById('spinBtn');
    btn.classList.add('spinning');
    btn.querySelector('.label').textContent = '...';

    try {
        const formData = new FormData();
        formData.append('email', EMAIL_CLIENT);

        const response = await fetch(BASE_URL_JS + '/controller/RouletteController.php?action=tourner', {
            method: 'POST', body: formData
        });
        const data = await response.json();

        if (!data.success) {
            alert('⚠️ ' + (data.message || 'Erreur'));
            isSpinning = false;
            btn.classList.remove('spinning');
            btn.querySelector('.label').textContent = 'TOURNER';
            return;
        }

        const targetIndex = data.index;
        const segmentMid  = targetIndex * ANGLE + ANGLE / 2;
        currentRotation = 360 * 6 - segmentMid;
        document.getElementById('wheelSvg').style.transform = `rotate(${currentRotation}deg)`;

        setTimeout(() => {
            showWinModal(data);
            launchConfetti();
            const rest = document.querySelector('.spins-pill.restant');
            if (rest && data.spins_restants !== undefined) {
                rest.innerHTML = `<i class="bi bi-fire"></i> ${data.spins_restants} restant${data.spins_restants > 1 ? 's' : ''}`;
            }
        }, 5200);

    } catch (err) {
        alert('Erreur : ' + err.message);
        isSpinning = false;
        btn.classList.remove('spinning');
        btn.querySelector('.label').textContent = 'TOURNER';
    }
}

function showWinModal(data) {
    const card = document.getElementById('modalCard');
    document.getElementById('modalIcon').textContent = data.cadeau.icone || '🎉';
    document.getElementById('modalGainLabel').textContent = data.cadeau.label;

    if (data.cadeau.type === 'aucun') {
        card.classList.add('lost');
        document.getElementById('modalTitle').textContent = 'Pas de chance 😢';
        document.getElementById('modalSubtitle').textContent = 'Retentez au prochain paiement !';
        document.getElementById('modalPromoBox').style.display = 'none';
        document.getElementById('modalInfoText').innerHTML = 'Continuez à payer vos cotisations pour débloquer un nouveau tour.';
    } else {
        card.classList.remove('lost');
        document.getElementById('modalTitle').textContent = 'Félicitations !';
        document.getElementById('modalSubtitle').textContent = 'Vous avez gagné';
        document.getElementById('modalPromoBox').style.display = 'block';
        document.getElementById('modalPromoCode').textContent = data.code_promo;
        document.getElementById('modalInfoText').innerHTML = data.email_envoye
            ? '📧 Un email avec votre code promo a été envoyé.'
            : '⚠️ L\'email n\'a pas pu être envoyé, mais votre code est sauvegardé.';
    }
    document.getElementById('modalWin').classList.add('show');
}

function copyPromo() {
    navigator.clipboard.writeText(document.getElementById('modalPromoCode').textContent).then(() => {
        const btn = event.target.closest('.copy-btn');
        const old = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Copié !';
        setTimeout(() => btn.innerHTML = old, 2000);
    });
}

function launchConfetti() {
    const colors = ['#FF6B1A','#10b981','#3b82f6','#f59e0b','#ec4899','#8b5cf6','#ffd700'];
    for (let i = 0; i < 80; i++) {
        const c = document.createElement('div');
        c.className = 'confetti';
        c.style.left = Math.random() * 100 + 'vw';
        c.style.background = colors[Math.floor(Math.random() * colors.length)];
        c.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
        c.style.animation = `fall ${2 + Math.random() * 3}s linear ${Math.random() * 2}s forwards`;
        document.body.appendChild(c);
        setTimeout(() => c.remove(), 6000);
    }
}
const s = document.createElement('style');
s.textContent = `@keyframes fall { 0% { transform:translateY(0) rotate(0deg); opacity:1; } 100% { transform:translateY(110vh) rotate(720deg); opacity:0; } }`;
document.head.appendChild(s);
</script>
</body>
</html>



