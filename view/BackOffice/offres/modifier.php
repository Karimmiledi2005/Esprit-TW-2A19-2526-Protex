<?php
$errors   = $errors   ?? [];
$old      = $old      ?? [];
$offre    = $offre    ?? [];
$BASE_URL = (defined('BASE_URL') ? BASE_URL : '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Modifier une offre — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/view/BackOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/view/BackOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/view/BackOffice/assets/css/layout.css">
    <style>
        /* ═══════════════════════════════════════
           ANIMATIONS
        ═══════════════════════════════════════ */
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(14px); }
            to   { opacity:1; transform:translateY(0); }
        }
        @keyframes shimmer {
            0%   { background-position: -200% center; }
            100% { background-position:  200% center; }
        }

        /* ═══════════════════════════════════════
           PAGE SHELL
        ═══════════════════════════════════════ */
        .page-shell {
            display: grid;
            grid-template-columns: minmax(0,1.7fr) 330px;
            gap: 24px;
            align-items: start;
        }

        /* ═══════════════════════════════════════
           FORM PANEL
        ═══════════════════════════════════════ */
        .form-panel {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            backdrop-filter: blur(20px);
            overflow: hidden;
            animation: fadeUp .4s ease both;
        }

        .form-panel-head {
            padding: 22px 24px;
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            background: linear-gradient(135deg, rgba(244,162,97,.06), transparent);
        }

        .form-head-left {
            display: flex;
            gap: 14px;
            align-items: center;
        }

        .form-main-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: grid;
            place-items: center;
            background: rgba(244,162,97,.12);
            border: 1px solid rgba(244,162,97,.25);
            color: var(--gold);
            font-size: 22px;
            flex-shrink: 0;
            box-shadow: 0 4px 14px rgba(244,162,97,.15);
        }

        .form-panel-title {
            color: #fff;
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 3px;
            font-family: var(--font-display);
        }

        .form-panel-sub {
            color: var(--text-secondary);
            font-size: 13px;
        }

        .head-badge {
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid rgba(244,162,97,.2);
            background: rgba(244,162,97,.08);
            color: var(--gold);
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        /* ═══════════════════════════════════════
           FORM BODY
        ═══════════════════════════════════════ */
        .form-panel-body { padding: 24px; }

        .section-card {
            border: 1px solid rgba(255,255,255,.06);
            background: rgba(255,255,255,.03);
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 18px;
            transition: border-color .2s;
        }
        .section-card:hover { border-color: rgba(0,180,216,.15); }
        .section-card:last-child { margin-bottom: 0; }

        .section-title {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 18px;
            font-family: var(--font-display);
        }
        .section-title i { color: var(--accent); }

        /* ═══════════════════════════════════════
           GRID LAYOUTS
        ═══════════════════════════════════════ */
        .form-grid   { display: grid; gap: 18px; }
        .form-row-2  { display: grid; grid-template-columns: repeat(2,1fr); gap: 18px; }
        .form-row-3  { display: grid; grid-template-columns: repeat(3,1fr); gap: 18px; }
        .form-group  { display: grid; gap: 7px; }

        /* ═══════════════════════════════════════
           LABELS & INPUTS
        ═══════════════════════════════════════ */
        .form-label {
            font-size: 11px;
            color: var(--text-secondary);
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .input-wrap { position: relative; }
        .input-wrap > i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 14px;
            pointer-events: none;
        }
        .input-wrap.ta > i { top: 16px; transform: none; }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            border-radius: 13px;
            border: 1px solid var(--glass-border);
            background: rgba(255,255,255,.045);
            color: var(--text-primary);
            font-family: var(--font-body);
            font-size: 14px;
            outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        .form-input,
        .form-select { height: 46px; padding: 0 14px 0 42px; }
        .form-select  { padding-left: 14px; }

        .form-textarea {
            min-height: 110px;
            resize: vertical;
            padding: 14px 14px 14px 42px;
            line-height: 1.55;
        }

        .form-input::placeholder,
        .form-textarea::placeholder { color: var(--text-secondary); }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: rgba(0,180,216,.5);
            box-shadow: 0 0 0 4px rgba(0,180,216,.08);
            background: rgba(255,255,255,.065);
        }

        .form-input.error,
        .form-select.error,
        .form-textarea.error {
            border-color: var(--danger);
            box-shadow: 0 0 0 4px rgba(230,57,70,.08);
        }

        .form-select option { background: var(--navy-mid); }

        .field-error {
            font-size: 11px;
            color: var(--danger);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-hint { font-size: 11px; color: var(--text-secondary); }

        /* ═══════════════════════════════════════
           PRICING BOX
        ═══════════════════════════════════════ */
        .pricing-box {
            background: rgba(0,180,216,.06);
            border: 1px solid rgba(0,180,216,.16);
            border-radius: 13px;
            padding: 13px 15px;
            display: none;
            margin-top: 12px;
        }
        .pricing-box.show { display: block; }
        .pricing-box-title {
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .pricing-line {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }
        .pricing-line:last-child { margin-bottom: 0; }
        .pricing-line strong { color: var(--accent); }

        /* ═══════════════════════════════════════
           ALERT ERROR
        ═══════════════════════════════════════ */
        .alert-error {
            background: rgba(230,57,70,.08);
            border: 1px solid rgba(230,57,70,.2);
            border-radius: 14px;
            padding: 14px 16px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 18px;
        }
        .alert-error > i {
            color: var(--danger);
            font-size: 17px;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .alert-error strong { color: #fff; font-size: 13px; }
        .alert-error ul {
            margin: 6px 0 0;
            padding-left: 16px;
            color: var(--text-secondary);
            font-size: 13px;
            line-height: 1.75;
        }

        /* ═══════════════════════════════════════
           FORM ACTIONS
        ═══════════════════════════════════════ */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid var(--glass-border);
        }

        /* ═══════════════════════════════════════
           SIDE PANEL
        ═══════════════════════════════════════ */
        .side-panel {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            backdrop-filter: blur(20px);
            overflow: hidden;
            animation: fadeUp .4s .1s ease both;
            padding: 20px;
            position: sticky;
            top: 24px;
        }

        .side-block {
            border: 1px solid rgba(255,255,255,.06);
            background: rgba(255,255,255,.03);
            border-radius: 17px;
            padding: 17px;
            margin-bottom: 14px;
        }
        .side-block:last-child { margin-bottom: 0; }

        .side-title {
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 13px;
            font-family: var(--font-display);
        }
        .side-title i { color: var(--accent); }

        /* Summary card */
        .summary-card {
            border-radius: 14px;
            padding: 15px;
            background: linear-gradient(135deg, rgba(0,180,216,.12), rgba(255,255,255,.04));
            border: 1px solid rgba(0,180,216,.16);
        }
        .sum-name {
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 5px;
            font-family: var(--font-display);
        }
        .sum-type {
            color: var(--text-secondary);
            font-size: 12px;
            margin-bottom: 11px;
        }
        .sum-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 7px 0;
            border-bottom: 1px dashed rgba(255,255,255,.08);
            font-size: 12px;
        }
        .sum-row:last-child  { border-bottom: none; }
        .sum-row span:first-child { color: var(--text-secondary); }
        .sum-row span:last-child  { color: #fff; font-weight: 600; }

        /* Status badge dans summary */
        .sum-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }
        .sum-status.active    { background: rgba(16,185,129,.14); color: #86efac; }
        .sum-status.suspendue { background: rgba(245,158,11,.14);  color: #fcd34d; }
        .sum-status.archivee  { background: rgba(148,163,184,.14); color: #cbd5e1; }

        /* Tips */
        .tip-list { display: grid; gap: 10px; }
        .tip-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: var(--text-secondary);
            font-size: 12px;
            line-height: 1.55;
        }
        .tip-item i { color: var(--accent); margin-top: 1px; flex-shrink: 0; }

        /* ═══════════════════════════════════════
           RESPONSIVE
        ═══════════════════════════════════════ */
        @media (max-width:1100px) { .page-shell { grid-template-columns: 1fr; } }
        @media (max-width:768px)  { .form-row-2, .form-row-3 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>
<div class="layout">

    <!-- ═══════════════════════════════════════
         SIDEBAR
    ═══════════════════════════════════════ -->
<?php include __DIR__ . '/../assets/includes/sidebar.php'; ?>
    <!-- ═══════════════════════════════════════
         MAIN
    ═══════════════════════════════════════ -->
    <main class="main">
        <div class="topbar">
            <div>
                <div class="topbar-title">Modifier une offre</div>
                <div class="topbar-sub">
                    Mise à jour de l'offre #<?= (int)($offre['id_offre'] ?? 0) ?>
                </div>
            </div>
            <div class="topbar-actions">
                <a href="<?= $BASE_URL ?>/controller/OffreController.php"
                   class="btn btn-outline btn-sm">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>
        </div>

        <div class="content">

            <div class="page-breadcrumb" style="margin-bottom:20px;">
                <i class="bi bi-house"></i>
                <span>Admin</span>
                <i class="bi bi-chevron-right" style="font-size:10px"></i>
                <a href="<?= $BASE_URL ?>/controller/OffreController.php"
                   style="color:inherit;text-decoration:none;">Offres</a>
                <i class="bi bi-chevron-right" style="font-size:10px"></i>
                <span>Modifier</span>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="alert-error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>
                    <strong>Veuillez corriger les erreurs :</strong>
                    <ul>
                        <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars(is_array($e) ? implode(', ',$e) : $e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <div class="page-shell">

                <!-- ═══ FORMULAIRE ═══ -->
                <div class="form-panel">
                    <div class="form-panel-head">
                        <div class="form-head-left">
                            <div class="form-main-icon">
                                <i class="bi bi-pencil-square"></i>
                            </div>
                            <div>
                                <div class="form-panel-title">
                                    <?= htmlspecialchars($offre['nom_offre'] ?? 'Modifier l\'offre') ?>
                                </div>
                                <div class="form-panel-sub">Modifiez les informations de cette offre</div>
                            </div>
                        </div>
                        <div class="head-badge">
                            ID #<?= (int)($offre['id_offre'] ?? 0) ?>
                            — Créée le <?= !empty($offre['date_creation']) ? date('d/m/Y', strtotime($offre['date_creation'])) : '—' ?>
                        </div>
                    </div>

                    <div class="form-panel-body">
                        <form class="form-grid" method="post"
                              action="<?= $BASE_URL ?>/controller/OffreController.php?action=modifier&id=<?= (int)($offre['id_offre'] ?? 0) ?>"
                              novalidate>

                            <!-- ── Informations générales ── -->
                            <div class="section-card">
                                <div class="section-title">
                                    <i class="bi bi-info-circle"></i> Informations générales
                                </div>

                                <div class="form-row-2" style="margin-bottom:16px;">
                                    <div class="form-group">
                                        <label class="form-label">Nom de l'offre *</label>
                                        <div class="input-wrap">
                                            <i class="bi bi-card-text"></i>
                                            <input class="form-input <?= isset($errors['nom_offre']) ? 'error' : '' ?>"
                                                   type="text" name="nom_offre" id="nomOffre"
                                                   placeholder="Ex : Auto Premium"
                                                   value="<?= htmlspecialchars($old['nom_offre'] ?? $offre['nom_offre'] ?? '') ?>"
                                                   oninput="updatePreview();">
                                        </div>
                                        <?php if (isset($errors['nom_offre'])): ?>
                                        <span class="field-error">
                                            <i class="bi bi-exclamation-circle"></i>
                                            <?= htmlspecialchars($errors['nom_offre']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Type *</label>
                                        <select class="form-select <?= isset($errors['type_offre']) ? 'error' : '' ?>"
                                                name="type_offre" id="typeOffre"
                                                onchange="updatePreview();">
                                            <option value="">-- Sélectionner --</option>
                                            <option value="auto"       <?= (($old['type_offre'] ?? $offre['type_offre'] ?? '') === 'auto')       ? 'selected' : '' ?>>Auto</option>
                                            <option value="sante"      <?= (($old['type_offre'] ?? $offre['type_offre'] ?? '') === 'sante')      ? 'selected' : '' ?>>Santé</option>
                                            <option value="habitation" <?= (($old['type_offre'] ?? $offre['type_offre'] ?? '') === 'habitation') ? 'selected' : '' ?>>Habitation</option>
                                            <option value="vie"        <?= (($old['type_offre'] ?? $offre['type_offre'] ?? '') === 'vie')        ? 'selected' : '' ?>>Vie</option>
                                        </select>
                                        <?php if (isset($errors['type_offre'])): ?>
                                        <span class="field-error">
                                            <i class="bi bi-exclamation-circle"></i>
                                            <?= htmlspecialchars($errors['type_offre']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group" style="margin-bottom:16px;">
                                    <label class="form-label">Description *</label>
                                    <div class="input-wrap ta">
                                        <i class="bi bi-text-paragraph"></i>
                                        <textarea class="form-textarea <?= isset($errors['description']) ? 'error' : '' ?>"
                                                  name="description"
                                                  placeholder="Décrivez l'offre en détail..."><?= htmlspecialchars($old['description'] ?? $offre['description'] ?? '') ?></textarea>
                                    </div>
                                    <?php if (isset($errors['description'])): ?>
                                    <span class="field-error">
                                        <i class="bi bi-exclamation-circle"></i>
                                        <?= htmlspecialchars($errors['description']) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Couverture *</label>
                                    <div class="input-wrap ta">
                                        <i class="bi bi-shield-check"></i>
                                        <textarea class="form-textarea <?= isset($errors['couverture']) ? 'error' : '' ?>"
                                                  name="couverture"
                                                  style="min-height:90px"
                                                  placeholder="Ex : Tous risques, vol, incendie..."><?= htmlspecialchars($old['couverture'] ?? $offre['couverture'] ?? '') ?></textarea>
                                    </div>
                                    <?php if (isset($errors['couverture'])): ?>
                                    <span class="field-error">
                                        <i class="bi bi-exclamation-circle"></i>
                                        <?= htmlspecialchars($errors['couverture']) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- ── Tarification ── -->
                            <div class="section-card">
                                <div class="section-title">
                                    <i class="bi bi-currency-exchange"></i> Tarification
                                </div>
                                <div class="form-row-3">
                                    <div class="form-group">
                                        <label class="form-label">Prix mensuel (TND) *</label>
                                        <div class="input-wrap">
                                            <i class="bi bi-cash-stack"></i>
                                            <input class="form-input <?= isset($errors['prix_mensuel']) ? 'error' : '' ?>"
                                                   type="number" name="prix_mensuel" id="prixMensuel"
                                                   step="0.001" min="0" placeholder="85.000"
                                                   value="<?= htmlspecialchars($old['prix_mensuel'] ?? $offre['prix_mensuel'] ?? '') ?>"
                                                   oninput="calcEco(); updatePreview();">
                                        </div>
                                        <?php if (isset($errors['prix_mensuel'])): ?>
                                        <span class="field-error">
                                            <i class="bi bi-exclamation-circle"></i>
                                            <?= htmlspecialchars($errors['prix_mensuel']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Prix annuel (TND) *</label>
                                        <div class="input-wrap">
                                            <i class="bi bi-wallet2"></i>
                                            <input class="form-input <?= isset($errors['prix_annuel']) ? 'error' : '' ?>"
                                                   type="number" name="prix_annuel" id="prixAnnuel"
                                                   step="0.001" min="0" placeholder="950.000"
                                                   value="<?= htmlspecialchars($old['prix_annuel'] ?? $offre['prix_annuel'] ?? '') ?>"
                                                   oninput="calcEco(); updatePreview();">
                                        </div>
                                        <?php if (isset($errors['prix_annuel'])): ?>
                                        <span class="field-error">
                                            <i class="bi bi-exclamation-circle"></i>
                                            <?= htmlspecialchars($errors['prix_annuel']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Plafond (TND)</label>
                                        <div class="input-wrap">
                                            <i class="bi bi-graph-up-arrow"></i>
                                            <input class="form-input"
                                                   type="number" name="plafond" id="plafondOffre"
                                                   step="0.001" min="0" placeholder="50000.000"
                                                   value="<?= htmlspecialchars($old['plafond'] ?? $offre['plafond'] ?? '') ?>"
                                                   oninput="updatePreview();">
                                        </div>
                                        <span class="form-hint">Optionnel</span>
                                    </div>
                                </div>
                                <div class="pricing-box" id="prixInfo">
                                    <div class="pricing-box-title">Analyse tarifaire</div>
                                    <div class="pricing-line">
                                        <span>Mensuel × 12</span>
                                        <strong id="m12Val">—</strong>
                                    </div>
                                    <div class="pricing-line">
                                        <span>Économie annuelle</span>
                                        <strong id="ecoVal">—</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Conditions ── -->
                            <div class="section-card">
                                <div class="section-title">
                                    <i class="bi bi-sliders"></i> Conditions
                                </div>
                                <div class="form-row-2">
                                    <div class="form-group">
                                        <label class="form-label">Durée minimale (mois) *</label>
                                        <div class="input-wrap">
                                            <i class="bi bi-calendar-range"></i>
                                            <input class="form-input <?= isset($errors['duree_min']) ? 'error' : '' ?>"
                                                   type="number" name="duree_min" id="dureeMin"
                                                   min="1" placeholder="1"
                                                   value="<?= htmlspecialchars($old['duree_min'] ?? $offre['duree_min'] ?? '1') ?>">
                                        </div>
                                        <?php if (isset($errors['duree_min'])): ?>
                                        <span class="field-error">
                                            <i class="bi bi-exclamation-circle"></i>
                                            <?= htmlspecialchars($errors['duree_min']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Statut *</label>
                                        <select class="form-select" name="statut" id="statutOffre"
                                                onchange="updatePreview();">
                                            <option value="active"    <?= (($old['statut'] ?? $offre['statut'] ?? 'active') === 'active')    ? 'selected' : '' ?>>Active</option>
                                            <option value="suspendue" <?= (($old['statut'] ?? $offre['statut'] ?? '')        === 'suspendue') ? 'selected' : '' ?>>Suspendue</option>
                                            <option value="archivee"  <?= (($old['statut'] ?? $offre['statut'] ?? '')        === 'archivee')  ? 'selected' : '' ?>>Archivée</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- ═══ O2: PROMOTIONS FLASH ═══ -->
                            <div class="section-card" style="border-color: rgba(255,107,26,.3); background: rgba(255,107,26,.02);">
                                <div class="section-title" style="color: #ff9b5e;">
                                    <i class="bi bi-lightning-charge-fill"></i> Promotions / Ventes Flash
                                </div>
                                <div class="form-row-3">
                                    <div class="form-group">
                                        <label class="form-label">Début de promo</label>
                                        <div class="input-wrap">
                                            <i class="bi bi-calendar-event"></i>
                                            <input class="form-input" type="datetime-local" name="date_promo_debut" id="datePromoDebut" value="<?= htmlspecialchars($old['date_promo_debut'] ?? $offre['date_promo_debut'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Fin de promo</label>
                                        <div class="input-wrap">
                                            <i class="bi bi-calendar-x"></i>
                                            <input class="form-input" type="datetime-local" name="date_promo_fin" id="datePromoFin" value="<?= htmlspecialchars($old['date_promo_fin'] ?? $offre['date_promo_fin'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Remise (%)</label>
                                        <div class="input-wrap">
                                            <i class="bi bi-percent"></i>
                                            <input class="form-input" type="number" name="remise_promo" id="remisePromo" min="0" max="100" placeholder="Ex: 15" value="<?= htmlspecialchars($old['remise_promo'] ?? $offre['remise_promo'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <a href="<?= $BASE_URL ?>/controller/OffreController.php"
                                   class="btn btn-outline">
                                    <i class="bi bi-x-lg"></i> Annuler
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check2-circle"></i> Enregistrer les modifications
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

                <!-- ═══ SIDE PANEL ═══ -->
                <aside class="side-panel">
                    <div class="side-block">
                        <div class="side-title">
                            <i class="bi bi-eye"></i> Aperçu rapide
                        </div>
                        <div class="summary-card">
                            <div class="sum-name" id="previewNom">
                                <?= htmlspecialchars($offre['nom_offre'] ?? 'Offre') ?>
                            </div>
                            <div class="sum-type" id="previewType">
                                <?= !empty($offre['type_offre']) ? 'Assurance '.ucfirst($offre['type_offre']) : '—' ?>
                            </div>
                            <div class="sum-row">
                                <span>Mensuel</span>
                                <span id="previewMensuel">
                                    <?= !empty($offre['prix_mensuel']) ? number_format($offre['prix_mensuel'],3).' TND' : '—' ?>
                                </span>
                            </div>
                            <div class="sum-row">
                                <span>Annuel</span>
                                <span id="previewAnnuel">
                                    <?= !empty($offre['prix_annuel']) ? number_format($offre['prix_annuel'],3).' TND' : '—' ?>
                                </span>
                            </div>
                            <div class="sum-row">
                                <span>Plafond</span>
                                <span id="previewPlafond">
                                    <?= !empty($offre['plafond']) ? number_format($offre['plafond'],0,'.',' ').' TND' : '—' ?>
                                </span>
                            </div>
                            <div class="sum-row">
                                <span>Durée min.</span>
                                <span id="previewDuree">
                                    <?= !empty($offre['duree_min']) ? $offre['duree_min'].' mois' : '—' ?>
                                </span>
                            </div>
                            <div class="sum-row">
                                <span>Statut</span>
                                <span id="previewStatut">
                                    <?php
                                    $s = $offre['statut'] ?? 'active';
                                    $labels = ['active'=>'Active','suspendue'=>'Suspendue','archivee'=>'Archivée'];
                                    echo '<span class="sum-status '.$s.'">'.($labels[$s]??ucfirst($s)).'</span>';
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="side-block">
                        <div class="side-title">
                            <i class="bi bi-lightbulb"></i> Conseils
                        </div>
                        <div class="tip-list">
                            <div class="tip-item">
                                <i class="bi bi-check2-circle"></i>
                                <span>Vérifiez la cohérence entre prix mensuel et annuel.</span>
                            </div>
                            <div class="tip-item">
                                <i class="bi bi-check2-circle"></i>
                                <span>Le changement de statut impacte immédiatement la visibilité.</span>
                            </div>
                            <div class="tip-item">
                                <i class="bi bi-check2-circle"></i>
                                <span>Évitez une description trop vague côté administration.</span>
                            </div>
                        </div>
                    </div>
                </aside>

            </div>
        </div>
    </main>
</div>

<script src="<?= $BASE_URL ?>/view/BackOffice/assets/js/main.js"></script>
<script>
    function fmt(v) {
        const n = parseFloat(v);
        return (!isNaN(n) && n > 0) ? n.toFixed(3) + ' TND' : '—';
    }

    function calcEco() {
        const m   = parseFloat(document.getElementById('prixMensuel').value);
        const a   = parseFloat(document.getElementById('prixAnnuel').value);
        const box = document.getElementById('prixInfo');
        if (!m || m <= 0) { box.classList.remove('show'); return; }
        document.getElementById('m12Val').textContent  = (m * 12).toFixed(3) + ' TND';
        document.getElementById('ecoVal').textContent  = a ? Math.max(0, (m*12) - a).toFixed(3) + ' TND' : '0.000 TND';
        box.classList.add('show');
    }

    function updatePreview() {
        const nom    = document.getElementById('nomOffre').value.trim();
        const type   = document.getElementById('typeOffre').value;
        const statut = document.getElementById('statutOffre').value;

        document.getElementById('previewNom').textContent     = nom  || 'Offre';
        document.getElementById('previewType').textContent    = type ? 'Assurance ' + type.charAt(0).toUpperCase() + type.slice(1) : '—';
        document.getElementById('previewMensuel').textContent = fmt(document.getElementById('prixMensuel').value);
        document.getElementById('previewAnnuel').textContent  = fmt(document.getElementById('prixAnnuel').value);
        document.getElementById('previewPlafond').textContent = fmt(document.getElementById('plafondOffre').value);

        const dur = document.getElementById('dureeMin').value;
        document.getElementById('previewDuree').textContent   = dur ? dur + ' mois' : '—';

        /* Statut badge */
        const labels = { active:'Active', suspendue:'Suspendue', archivee:'Archivée' };
        document.getElementById('previewStatut').innerHTML =
            `<span class="sum-status ${statut}">${labels[statut] || statut}</span>`;
    }

    updatePreview();
    calcEco();
</script>
</body>
</html>
