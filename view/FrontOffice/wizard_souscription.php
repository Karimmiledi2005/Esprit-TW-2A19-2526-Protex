<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();

require_once dirname(__DIR__, 2) . '/config.php';

$id_offre = (int)($_GET['id_offre'] ?? 0);
$db = config::getConnexion();

$offre = null;
if ($id_offre > 0) {
    $stmt = $db->prepare("SELECT * FROM offre WHERE id_offre = ? AND statut = 'active'");
    $stmt->execute([$id_offre]);
    $offre = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Load all active offers for step 1 selection
$toutes_offres = $db->query("SELECT * FROM offre WHERE statut='active' ORDER BY prix_mensuel ASC")->fetchAll(PDO::FETCH_ASSOC);

$client_id = $_SESSION['user_id'] ?? $_SESSION['id_client'] ?? 0;
$client_stmt = $db->prepare("SELECT nom, prenom, email, telephone FROM user WHERE id_user = ?");
$client_stmt->execute([$client_id]);
$client = $client_stmt->fetch(PDO::FETCH_ASSOC) ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Souscrire — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Souscrivez à votre formule d'assurance Protex en 4 étapes simples.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --primary: #1A3A7A;
            --accent: #FF6B1A;
            --success: #10b981;
            --danger: #ef4444;
            --bg: #f4f6fb;
            --card: #fff;
            --border: rgba(26,58,122,0.10);
            --text: #15233C;
            --muted: #6B7A90;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--bg);
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            min-height: 100vh;
        }

        /* ── Top Bar ── */
        .top-bar {
            background: linear-gradient(135deg, #0f2557 0%, #1A3A7A 100%);
            padding: 14px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(15,37,87,0.3);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .logo {
            font-family: 'Sora', sans-serif;
            font-size: 22px;
            font-weight: 900;
            color: #fff;
            letter-spacing: -0.5px;
        }
        .logo span { color: var(--accent); }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50px;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .back-btn:hover { background: rgba(255,255,255,0.22); }

        /* ── Wizard Shell ── */
        .wizard-wrap {
            max-width: 800px;
            margin: 48px auto 100px;
            padding: 0 20px;
        }
        .wizard-intro {
            text-align: center;
            margin-bottom: 40px;
        }
        .wizard-title {
            font-family: 'Sora', sans-serif;
            font-size: 32px;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 8px;
        }
        .wizard-sub { font-size: 15px; color: var(--muted); }

        /* ── Progress Steps ── */
        .wizard-steps {
            display: flex;
            gap: 0;
            margin-bottom: 40px;
            position: relative;
        }
        .wizard-steps::before {
            content: '';
            position: absolute;
            top: 21px;
            left: 10%;
            right: 10%;
            height: 3px;
            background: rgba(26,58,122,0.10);
            z-index: 0;
        }
        .wz-step {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            position: relative;
            z-index: 1;
        }
        .wz-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #fff;
            border: 3px solid rgba(26,58,122,0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: var(--muted);
            font-weight: 700;
            transition: all 0.35s cubic-bezier(0.34,1.56,0.64,1);
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .wz-step.active .wz-circle {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
            box-shadow: 0 0 0 8px rgba(255,107,26,0.15), 0 4px 14px rgba(255,107,26,0.3);
        }
        .wz-step.done .wz-circle {
            background: var(--success);
            border-color: var(--success);
            color: #fff;
            box-shadow: 0 4px 12px rgba(16,185,129,0.3);
        }
        .wz-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            text-align: center;
        }
        .wz-step.active .wz-label { color: var(--accent); }
        .wz-step.done .wz-label   { color: var(--success); }

        /* ── Card ── */
        .wz-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 40px 36px;
            box-shadow: 0 10px 40px rgba(26,58,122,0.08);
        }
        .wz-card-title {
            font-family: 'Sora', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 6px;
        }
        .wz-card-sub {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 32px;
            line-height: 1.6;
        }

        /* Step panels */
        .wz-panel { display: none; animation: fadeIn 0.35s ease; }
        .wz-panel.active { display: block; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Step 1: Offer Grid ── */
        .offer-select-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }
        @media (max-width: 540px) { .offer-select-grid { grid-template-columns: 1fr; } }

        .offer-select-card {
            border: 2px solid var(--border);
            border-radius: 18px;
            padding: 20px 18px;
            cursor: pointer;
            transition: all 0.25s ease;
            background: #f8f9ff;
            position: relative;
            overflow: hidden;
        }
        .offer-select-card:hover {
            border-color: rgba(255,107,26,0.35);
            background: rgba(255,107,26,0.02);
            transform: translateY(-2px);
        }
        .offer-select-card.selected {
            border-color: var(--accent);
            background: rgba(255,107,26,0.04);
            box-shadow: 0 0 0 4px rgba(255,107,26,0.10);
        }
        .offer-select-card .selected-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: var(--accent);
            color: #fff;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .offer-select-card.selected .selected-badge { display: flex; }
        .offer-card-type {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--accent);
            margin-bottom: 6px;
        }
        .offer-card-name {
            font-family: 'Sora', sans-serif;
            font-size: 16px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 4px;
        }
        .offer-card-price {
            font-size: 22px;
            font-weight: 900;
            color: var(--primary);
            margin-bottom: 4px;
        }
        .offer-card-price span {
            font-size: 13px;
            font-weight: 400;
            color: var(--muted);
        }
        .offer-card-features {
            font-size: 12px;
            color: var(--muted);
            margin-top: 8px;
            line-height: 1.7;
        }

        /* ── Step 2: Coverage Picker ── */
        .coverage-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 28px;
        }
        @media (max-width: 560px) { .coverage-grid { grid-template-columns: 1fr; } }

        .coverage-card {
            border: 2px solid var(--border);
            border-radius: 16px;
            padding: 20px 16px;
            cursor: pointer;
            text-align: center;
            transition: all 0.25s ease;
            background: #f8f9ff;
        }
        .coverage-card:hover { border-color: rgba(26,58,122,0.3); }
        .coverage-card.selected {
            border-color: var(--primary);
            background: rgba(26,58,122,0.05);
            box-shadow: 0 0 0 4px rgba(26,58,122,0.08);
        }
        .coverage-emoji { font-size: 28px; margin-bottom: 8px; display: block; }
        .coverage-name  { font-weight: 800; font-size: 15px; color: var(--text); margin-bottom: 4px; }
        .coverage-mult  { font-size: 12px; color: var(--accent); font-weight: 600; margin-bottom: 8px; }
        .coverage-desc  { font-size: 11px; color: var(--muted); line-height: 1.7; }

        /* Price display */
        .price-banner {
            background: linear-gradient(135deg, #0f2557, var(--primary));
            border-radius: 18px;
            padding: 24px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #fff;
            flex-wrap: wrap;
            gap: 12px;
        }
        .price-banner-label { font-size: 12px; opacity: 0.7; text-transform: uppercase; letter-spacing: 0.07em; }
        .price-banner-amount {
            font-family: 'Sora', sans-serif;
            font-size: 42px;
            font-weight: 900;
            line-height: 1;
        }
        .price-banner-amount span { font-size: 16px; font-weight: 400; opacity: 0.7; }
        .price-banner-sub { font-size: 12px; opacity: 0.6; margin-top: 4px; }

        /* ── Step 3: Info personnelles ── */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        @media (max-width: 540px) { .form-grid { grid-template-columns: 1fr; } }
        .form-full { grid-column: 1 / -1; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
        }
        .form-control {
            padding: 12px 16px;
            border: 1.5px solid rgba(26,58,122,0.15);
            border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--text);
            background: #fff;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(255,107,26,0.10);
        }

        /* ── Step 4: Summary ── */
        .summary-block {
            background: #f8f9ff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
        }
        .summary-block-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
            margin-bottom: 16px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dashed rgba(26,58,122,0.08);
            font-size: 14px;
        }
        .summary-row:last-child { border-bottom: none; padding-bottom: 0; }
        .summary-key { color: var(--muted); }
        .summary-val { font-weight: 700; color: var(--text); }

        .final-total {
            background: linear-gradient(135deg, var(--accent), #e05a0f);
            color: #fff;
            border-radius: 18px;
            padding: 24px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }
        .final-total-label { font-size: 14px; opacity: 0.9; }
        .final-total-amount {
            font-family: 'Sora', sans-serif;
            font-size: 32px;
            font-weight: 900;
        }

        /* ── Nav Buttons ── */
        .wz-nav {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }
        .btn-wz {
            flex: 1;
            padding: 15px 20px;
            border-radius: 14px;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-wz-next {
            background: linear-gradient(135deg, var(--accent), #e05a0f);
            color: #fff;
            box-shadow: 0 6px 20px rgba(255,107,26,0.3);
        }
        .btn-wz-next:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(255,107,26,0.4); }
        .btn-wz-prev {
            background: rgba(26,58,122,0.07);
            color: var(--primary);
            border: 1.5px solid rgba(26,58,122,0.15);
        }
        .btn-wz-prev:hover { background: rgba(26,58,122,0.12); }
        .btn-wz-submit {
            background: linear-gradient(135deg, var(--success), #059669);
            color: #fff;
            box-shadow: 0 6px 18px rgba(16,185,129,0.3);
        }
        .btn-wz-submit:hover { transform: translateY(-2px); }
        .btn-wz:disabled { opacity: 0.5; cursor: not-allowed; transform: none !important; }

        /* ── Success Screen ── */
        .success-screen {
            display: none;
            text-align: center;
            padding: 60px 20px;
        }
        .success-screen.show { display: block; }
        .success-icon {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--success), #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 40px;
            color: #fff;
            box-shadow: 0 12px 30px rgba(16,185,129,0.3);
            animation: popIn 0.5s cubic-bezier(0.34,1.56,0.64,1);
        }
        @keyframes popIn {
            from { transform: scale(0); opacity: 0; }
            to   { transform: scale(1); opacity: 1; }
        }
        .success-title {
            font-family: 'Sora', sans-serif;
            font-size: 28px;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 12px;
        }
        .success-sub { font-size: 15px; color: var(--muted); margin-bottom: 32px; line-height: 1.7; }
        .success-actions { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }

        /* Misc */
        .alert-box {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        @media (max-width: 640px) {
            .wz-card { padding: 28px 20px; }
            .wizard-wrap { margin: 24px auto 80px; }
            .wizard-title { font-size: 24px; }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="logo">Prot<span>ex</span></div>
    <a href="offres.php" class="back-btn"><i class="bi bi-arrow-left"></i> Voir les offres</a>
</div>

<div class="wizard-wrap">

    <div class="wizard-intro">
        <h1 class="wizard-title"><i class="bi bi-shield-check" style="color:var(--accent)"></i> Souscription</h1>
        <p class="wizard-sub">Finalisez votre souscription en 4 étapes simples et sécurisées.</p>
    </div>

    <!-- Step Indicators -->
    <div class="wizard-steps" id="wizardSteps">
        <div class="wz-step active" id="stp-1">
            <div class="wz-circle"><i class="bi bi-tag"></i></div>
            <span class="wz-label">Offre</span>
        </div>
        <div class="wz-step" id="stp-2">
            <div class="wz-circle"><i class="bi bi-layers"></i></div>
            <span class="wz-label">Couverture</span>
        </div>
        <div class="wz-step" id="stp-3">
            <div class="wz-circle"><i class="bi bi-person"></i></div>
            <span class="wz-label">Vos infos</span>
        </div>
        <div class="wz-step" id="stp-4">
            <div class="wz-circle"><i class="bi bi-check2-circle"></i></div>
            <span class="wz-label">Confirmation</span>
        </div>
    </div>

    <!-- Main wizard card -->
    <div class="wz-card" id="wizardCard">

        <!-- ── STEP 1: Offer Selection ── -->
        <div class="wz-panel active" id="panel-1">
            <div class="wz-card-title">Choisissez votre offre</div>
            <div class="wz-card-sub">Sélectionnez la formule d'assurance qui correspond le mieux à vos besoins.</div>

            <div class="offer-select-grid" id="offerGrid">
                <?php foreach ($toutes_offres as $of):
                    $t = strtolower($of['type_offre'] ?? '');
                    $icons = ['auto'=>'bi-car-front','sante'=>'bi-heart-pulse','habitation'=>'bi-house-check','vie'=>'bi-shield-heart'];
                    $icon = $icons[$t] ?? 'bi-tags';
                    $isSelected = $offre && $offre['id_offre'] == $of['id_offre'];
                ?>
                <div class="offer-select-card <?= $isSelected ? 'selected' : '' ?>"
                     data-id="<?= $of['id_offre'] ?>"
                     data-price="<?= $of['prix_mensuel'] ?>"
                     data-name="<?= htmlspecialchars($of['nom_offre']) ?>"
                     data-type="<?= htmlspecialchars($of['type_offre'] ?? '') ?>"
                     onclick="selectOffer(this)">
                    <div class="selected-badge"><i class="bi bi-check"></i></div>
                    <div class="offer-card-type"><i class="bi <?= $icon ?>"></i> <?= ucfirst($t) ?></div>
                    <div class="offer-card-name"><?= htmlspecialchars($of['nom_offre']) ?></div>
                    <div class="offer-card-price"><?= number_format((float)$of['prix_mensuel'], 0) ?> <span>TND / mois</span></div>
                    <?php if (!empty($of['couverture'])): ?>
                    <div class="offer-card-features">
                        <?php $feats = array_slice(array_map('trim', explode(',', $of['couverture'])), 0, 3);
                        foreach ($feats as $f): ?>
                        <div><i class="bi bi-check-circle-fill" style="color:#10b981; font-size:11px; margin-right:4px;"></i><?= htmlspecialchars($f) ?></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <div id="step1-error" class="alert-box alert-danger" style="display:none;">
                <i class="bi bi-exclamation-circle"></i> Veuillez sélectionner une offre.
            </div>

            <div class="wz-nav">
                <button class="btn-wz btn-wz-next" onclick="goStep1()">
                    Continuer <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- ── STEP 2: Coverage Level ── -->
        <div class="wz-panel" id="panel-2">
            <div class="wz-card-title">Niveau de couverture</div>
            <div class="wz-card-sub">Ajustez votre niveau de protection. Le prix s'adapte en temps réel.</div>

            <div class="coverage-grid">
                <div class="coverage-card" onclick="selectCoverage('essentiel', this, 1.0)">
                    <span class="coverage-emoji">🛡️</span>
                    <div class="coverage-name">Essentiel</div>
                    <div class="coverage-mult">Prix de base</div>
                    <div class="coverage-desc">
                        ✓ Responsabilité civile<br>
                        ✓ Assistance 24/7<br>
                        ✗ Tous risques<br>
                        ✗ Garanties premium
                    </div>
                </div>
                <div class="coverage-card selected" onclick="selectCoverage('premium', this, 1.4)">
                    <span class="coverage-emoji">⭐</span>
                    <div class="coverage-name">Premium</div>
                    <div class="coverage-mult">+40% · Recommandé</div>
                    <div class="coverage-desc">
                        ✓ Responsabilité civile<br>
                        ✓ Assistance 24/7<br>
                        ✓ Tous risques<br>
                        ✗ Garanties platinum
                    </div>
                </div>
                <div class="coverage-card" onclick="selectCoverage('platinum', this, 2.0)">
                    <span class="coverage-emoji">💎</span>
                    <div class="coverage-name">Platine</div>
                    <div class="coverage-mult">+100% · Complet</div>
                    <div class="coverage-desc">
                        ✓ Responsabilité civile<br>
                        ✓ Assistance 24/7<br>
                        ✓ Tous risques<br>
                        ✓ Capital décès + Invalidité
                    </div>
                </div>
            </div>

            <div class="price-banner">
                <div>
                    <div class="price-banner-label">Prime mensuelle estimée</div>
                    <div class="price-banner-amount" id="priceDisplay">—<span> TND</span></div>
                    <div class="price-banner-sub">Pour la formule sélectionnée · Hors taxes</div>
                </div>
                <div style="text-align:right;">
                    <div class="price-banner-label">Couverture</div>
                    <div style="font-size:18px; font-weight:700; margin-top:4px;" id="coverageLabel">Premium ⭐</div>
                </div>
            </div>

            <div class="wz-nav">
                <button class="btn-wz btn-wz-prev" onclick="goTo(1)"><i class="bi bi-arrow-left"></i> Retour</button>
                <button class="btn-wz btn-wz-next" onclick="goTo(3)">Continuer <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>

        <!-- ── STEP 3: Personal Info ── -->
        <div class="wz-panel" id="panel-3">
            <div class="wz-card-title">Vos informations</div>
            <div class="wz-card-sub">Vérifiez et complétez vos informations personnelles pour la souscription.</div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="f_nom">Nom</label>
                    <input type="text" id="f_nom" class="form-control" value="<?= htmlspecialchars($client['nom'] ?? '') ?>" placeholder="Votre nom">
                </div>
                <div class="form-group">
                    <label class="form-label" for="f_prenom">Prénom</label>
                    <input type="text" id="f_prenom" class="form-control" value="<?= htmlspecialchars($client['prenom'] ?? '') ?>" placeholder="Votre prénom">
                </div>
                <div class="form-group">
                    <label class="form-label" for="f_email">Email</label>
                    <input type="email" id="f_email" class="form-control" value="<?= htmlspecialchars($client['email'] ?? '') ?>" placeholder="email@exemple.com">
                </div>
                <div class="form-group">
                    <label class="form-label" for="f_tel">Téléphone</label>
                    <input type="tel" id="f_tel" class="form-control" value="<?= htmlspecialchars($client['telephone'] ?? '') ?>" placeholder="+216 XX XXX XXX">
                </div>
                <div class="form-group form-full">
                    <label class="form-label" for="f_adresse">Adresse</label>
                    <input type="text" id="f_adresse" class="form-control" placeholder="Votre adresse complète">
                </div>
                <div class="form-group">
                    <label class="form-label" for="f_debut">Date de début souhaitée</label>
                    <input type="date" id="f_debut" class="form-control" min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="f_duree">Durée (mois)</label>
                    <select id="f_duree" class="form-control">
                        <option value="12">12 mois (recommandé)</option>
                        <option value="24">24 mois</option>
                        <option value="36">36 mois</option>
                        <option value="6">6 mois</option>
                    </select>
                </div>
                <div class="form-group form-full">
                    <label class="form-label" for="f_mode_paiement">Mode de paiement</label>
                    <select id="f_mode_paiement" class="form-control" onchange="updateModePaiement()">
                        <option value="mensuel">Mensuel &mdash; 1 paiement/mois</option>
                        <option value="trimestriel">Trimestriel &mdash; 1 paiement tous les 3 mois</option>
                        <option value="annuel" selected>Annuel &mdash; 1 paiement par an (-5%)</option>
                    </select>
                    <div id="mode-paiement-info" style="margin-top:8px; padding:10px 14px; border-radius:10px; background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; font-size:13px;">
                        <i class="bi bi-info-circle"></i> Paiement annuel : <strong>-5%</strong> sur la prime
                    </div>
                </div>
            </div>

            <div id="step3-error" class="alert-box alert-danger" style="display:none;">
                <i class="bi bi-exclamation-circle"></i> Veuillez remplir tous les champs obligatoires.
            </div>

            <div class="wz-nav">
                <button class="btn-wz btn-wz-prev" onclick="goTo(2)"><i class="bi bi-arrow-left"></i> Retour</button>
                <button class="btn-wz btn-wz-next" onclick="goStep3()">Continuer <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>

        <!-- ── STEP 4: Summary & Confirm ── -->
        <div class="wz-panel" id="panel-4">
            <div class="wz-card-title">Récapitulatif</div>
            <div class="wz-card-sub">Vérifiez les informations avant de confirmer votre souscription.</div>

            <div class="summary-block">
                <div class="summary-block-title"><i class="bi bi-tag" style="margin-right:6px;"></i>Offre sélectionnée</div>
                <div class="summary-row">
                    <span class="summary-key">Formule</span>
                    <span class="summary-val" id="sum-offre">—</span>
                </div>
                <div class="summary-row">
                    <span class="summary-key">Type d'assurance</span>
                    <span class="summary-val" id="sum-type">—</span>
                </div>
                <div class="summary-row">
                    <span class="summary-key">Niveau de couverture</span>
                    <span class="summary-val" id="sum-coverage">—</span>
                </div>
            </div>

            <div class="summary-block">
                <div class="summary-block-title"><i class="bi bi-person" style="margin-right:6px;"></i>Informations souscripteur</div>
                <div class="summary-row">
                    <span class="summary-key">Nom complet</span>
                    <span class="summary-val" id="sum-nom">—</span>
                </div>
                <div class="summary-row">
                    <span class="summary-key">Email</span>
                    <span class="summary-val" id="sum-email">—</span>
                </div>
                <div class="summary-row">
                    <span class="summary-key">Téléphone</span>
                    <span class="summary-val" id="sum-tel">—</span>
                </div>
                <div class="summary-row">
                    <span class="summary-key">Date de début</span>
                    <span class="summary-val" id="sum-debut">—</span>
                </div>
                <div class="summary-row">
                    <span class="summary-key">Durée</span>
                    <span class="summary-val" id="sum-duree">—</span>
                </div>
                <div class="summary-row">
                    <span class="summary-key">Mode de paiement</span>
                    <span class="summary-val" id="sum-mode">—</span>
                </div>
            </div>

            <div class="final-total">
                <div>
                    <div class="final-total-label">Prime mensuelle totale</div>
                    <div style="font-size:12px; opacity:0.8; margin-top:2px;">Toutes couvertures incluses</div>
                </div>
                <div class="final-total-amount" id="sum-price">—</div>
            </div>

            <div id="step4-error" class="alert-box alert-danger" style="display:none;">
                <i class="bi bi-exclamation-circle"></i> Une erreur est survenue. Veuillez réessayer.
            </div>

            <div class="wz-nav">
                <button class="btn-wz btn-wz-prev" onclick="goTo(3)"><i class="bi bi-arrow-left"></i> Modifier</button>
                <button class="btn-wz btn-wz-submit" id="submitBtn" onclick="submitSouscription()">
                    <i class="bi bi-shield-check"></i> Confirmer la souscription
                </button>
            </div>
        </div>

    </div>

    <!-- Success Screen -->
    <div class="wz-card success-screen" id="successScreen">
        <div class="success-icon"><i class="bi bi-check-lg"></i></div>
        <div class="success-title">Souscription confirmée !</div>
        <p class="success-sub">
            Votre demande de souscription a bien été enregistrée.<br>
            Notre équipe va traiter votre dossier et vous contactera sous 24h.
        </p>
        <div class="success-actions">
            <a href="client.php" class="btn-wz btn-wz-next" style="text-decoration:none; flex:none; padding:14px 28px;">
                <i class="bi bi-house"></i> Tableau de bord
            </a>
            <a href="mes_devis.php" class="btn-wz btn-wz-prev" style="text-decoration:none; flex:none; padding:14px 28px;">
                <i class="bi bi-file-earmark-text"></i> Mes devis
            </a>
        </div>
    </div>

</div>

<script>
function saveState() {
    try { sessionStorage.setItem('wizard_state', JSON.stringify(state)); } catch (e) {}
}

const savedRaw = (function(){ try { return sessionStorage.getItem('wizard_state'); } catch(e) { return null; } })();
const saved = savedRaw ? (function(){ try { return JSON.parse(savedRaw); } catch(e) { return null; } })() : null;

const state = saved || {
    step: 1,
    offre: {
        id: <?= $offre ? $offre['id_offre'] : 'null' ?>,
        name: '<?= $offre ? addslashes($offre['nom_offre']) : '' ?>',
        type: '<?= $offre ? addslashes($offre['type_offre'] ?? '') : '' ?>',
        price: <?= $offre ? (float)$offre['prix_mensuel'] : 0 ?>
    },
    coverage: { id: 'premium', label: 'Premium ⭐', mult: 1.4 },
    modePaiement: 'annuel',
    info: {}
};
saveState();

// Pre-select offer if passed via URL
document.querySelectorAll('.offer-select-card').forEach(function(card) {
    card.addEventListener('click', function() { selectOffer(this); });
});

function selectOffer(el) {
    document.querySelectorAll('.offer-select-card').forEach(function(c) { c.classList.remove('selected'); });
    el.classList.add('selected');
    state.offre.id    = el.getAttribute('data-id');
    state.offre.name  = el.getAttribute('data-name');
    state.offre.type  = el.getAttribute('data-type');
    state.offre.price = parseFloat(el.getAttribute('data-price')) || 0;
    saveState();
    updatePriceDisplay();
}

function selectCoverage(id, el, mult) {
    document.querySelectorAll('.coverage-card').forEach(function(c) { c.classList.remove('selected'); });
    el.classList.add('selected');
    const names = { essentiel: 'Essentiel 🛡️', premium: 'Premium ⭐', platinum: 'Platine 💎' };
    state.coverage = { id: id, label: names[id], mult: mult };
    saveState();
    document.getElementById('coverageLabel').textContent = names[id];
    updatePriceDisplay();
}

function updatePriceDisplay() {
    const modeDiscount = state.modePaiement === 'annuel' ? 0.95 : 1.0;
    const price = Math.round(state.offre.price * state.coverage.mult * modeDiscount);
    const el = document.getElementById('priceDisplay');
    if (el) el.innerHTML = price.toLocaleString('fr-FR') + '<span> TND</span>';
}

function updateModePaiement() {
    const mode = document.getElementById('f_mode_paiement').value;
    state.modePaiement = mode;
    saveState();
    const infoEl = document.getElementById('mode-paiement-info');
    const msgs = {
        mensuel: 'Paiement mensuel : prime standard, débit chaque mois.',
        trimestriel: 'Paiement trimestriel : regroupement de 3 mois, facilités de gestion.',
        annuel: 'Paiement annuel : <strong>-5%</strong> sur la prime. Un seul débit par an.'
    };
    const colors = { mensuel: '#eff6ff|#bfdbfe|#1e40af', trimestriel: '#fefce8|#fde68a|#92400e', annuel: '#f0fdf4|#bbf7d0|#166534' };
    const [bg, border, color] = colors[mode].split('|');
    infoEl.style.background = bg;
    infoEl.style.borderColor = border;
    infoEl.style.color = color;
    infoEl.innerHTML = '<i class="bi bi-info-circle"></i> ' + msgs[mode];
    updatePriceDisplay();
}

function goStep1() {
    if (!state.offre.id) {
        document.getElementById('step1-error').style.display = 'flex';
        return;
    }
    document.getElementById('step1-error').style.display = 'none';
    updatePriceDisplay();
    goTo(2);
}

function goStep3() {
    const nom    = document.getElementById('f_nom').value.trim();
    const prenom = document.getElementById('f_prenom').value.trim();
    const email  = document.getElementById('f_email').value.trim();
    const debut  = document.getElementById('f_debut').value;
    if (!nom || !prenom || !email || !debut) {
        document.getElementById('step3-error').style.display = 'flex';
        return;
    }
    document.getElementById('step3-error').style.display = 'none';
    state.info = {
        nom:     nom,
        prenom:  prenom,
        email:   email,
        tel:     document.getElementById('f_tel').value.trim(),
        adresse: document.getElementById('f_adresse').value.trim(),
        debut:   debut,
        duree:   document.getElementById('f_duree').value,
        mode:    document.getElementById('f_mode_paiement').value
    };
    saveState();
    renderSummary();
    goTo(4);
}

function renderSummary() {
    document.getElementById('sum-offre').textContent     = state.offre.name;
    document.getElementById('sum-type').textContent      = state.offre.type || '—';
    document.getElementById('sum-coverage').textContent  = state.coverage.label;
    document.getElementById('sum-nom').textContent       = state.info.prenom + ' ' + state.info.nom;
    document.getElementById('sum-email').textContent     = state.info.email;
    document.getElementById('sum-tel').textContent       = state.info.tel || '—';
    document.getElementById('sum-debut').textContent     = state.info.debut;
    document.getElementById('sum-duree').textContent     = state.info.duree + ' mois';
    const modeLabels = { mensuel: 'Mensuel', trimestriel: 'Trimestriel', annuel: 'Annuel (-5%)' };
    document.getElementById('sum-mode').textContent  = modeLabels[state.info.mode] || state.info.mode;
    const modeDiscount = state.info.mode === 'annuel' ? 0.95 : 1.0;
    const finalPrice = Math.round(state.offre.price * state.coverage.mult * modeDiscount);
    document.getElementById('sum-price').textContent     = finalPrice.toLocaleString('fr-FR') + ' TND / mois';
}

function goTo(n) {
    document.querySelectorAll('.wz-panel').forEach(function(p) { p.classList.remove('active'); });
    document.getElementById('panel-' + n).classList.add('active');
    for (let i = 1; i <= 4; i++) {
        const s = document.getElementById('stp-' + i);
        s.classList.remove('active', 'done');
        if (i < n)  s.classList.add('done');
        if (i === n) s.classList.add('active');
    }
    state.step = n;
    saveState();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function submitSouscription() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Traitement en cours...';

    const price = Math.round(state.offre.price * state.coverage.mult);
    const form = new FormData();
    form.append('id_offre',     state.offre.id);
    form.append('couverture',   state.coverage.id);
    form.append('prime_mensuelle', price);
    form.append('nom',          state.info.nom);
    form.append('prenom',       state.info.prenom);
    form.append('email',        state.info.email);
    form.append('telephone',    state.info.tel || '');
    form.append('adresse',      state.info.adresse || '');
    form.append('date_debut',   state.info.debut);
    form.append('duree_mois',   state.info.duree);
    form.append('mode_paiement', state.info.mode || 'annuel');

    try {
        const res  = await fetch('wizard_souscription_submit.php', { method: 'POST', body: form });
        const data = await res.json();
        if (data.success) {
            try { sessionStorage.removeItem('wizard_state'); } catch (e) {}
            // Redirect to payment page with offre and devis IDs
            const payUrl = 'paiement.php?id_offre=' + data.id_offre + '&id_devis=' + data.id_devis
                         + '&mode=' + (state.info.mode || 'annuel')
                         + '&montant=' + Math.round(state.offre.price * state.coverage.mult);
            window.location.href = payUrl;
        } else {
            document.getElementById('step4-error').style.display = 'flex';
            document.getElementById('step4-error').innerHTML = '<i class="bi bi-exclamation-circle"></i> ' + (data.message || 'Erreur inconnue.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-shield-check"></i> Confirmer la souscription';
        }
    } catch (e) {
        document.getElementById('step4-error').style.display = 'flex';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-shield-check"></i> Confirmer la souscription';
    }
}

// Restore wizard state from sessionStorage
(function restoreState() {
    if (!saved) return;
    // Coverage
    const covCards = document.querySelectorAll('.coverage-card');
    covCards.forEach(function(c) {
        const onclick = c.getAttribute('onclick') || '';
        const m = onclick.match(/selectCoverage\('(\w+)'/);
        if (m && m[1] === state.coverage.id) {
            covCards.forEach(function(x) { x.classList.remove('selected'); });
            c.classList.add('selected');
        }
    });
    document.getElementById('coverageLabel').textContent = state.coverage.label;
    // Form info
    if (state.info.nom)     document.getElementById('f_nom').value        = state.info.nom;
    if (state.info.prenom)  document.getElementById('f_prenom').value     = state.info.prenom;
    if (state.info.email)   document.getElementById('f_email').value      = state.info.email;
    if (state.info.tel)     document.getElementById('f_tel').value        = state.info.tel;
    if (state.info.adresse) document.getElementById('f_adresse').value    = state.info.adresse;
    if (state.info.debut)   document.getElementById('f_debut').value      = state.info.debut;
    if (state.info.duree)   document.getElementById('f_duree').value      = state.info.duree;
    if (state.info.mode)    document.getElementById('f_mode_paiement').value = state.info.mode;
    updateModePaiement();
    // Step
    if (state.step > 1) {
        if (state.step === 4) renderSummary();
        goTo(state.step);
    }
})();

// Init
updatePriceDisplay();
</script>

</body>
</html>
