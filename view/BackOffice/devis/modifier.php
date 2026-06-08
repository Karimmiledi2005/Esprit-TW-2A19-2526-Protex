<?php
/**
 * view/BackOffice/devis/modifier.php
 * Modification d'un devis — Back-office Protex
 * Variables : $devis (array), $errors (array), $old (array)
 */

if (!defined('BASE_URL')) define('BASE_URL', (defined('BASE_URL') ? BASE_URL : ''));
$base = (defined('BASE_URL') ? BASE_URL : '');

$devis  = $devis  ?? [];
$errors = $errors ?? [];
$old    = $old    ?? [];

function mE($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function mFmt($v): string {
    if ($v === null || $v === '') return '—';
    return number_format((float)$v, 3, '.', ' ') . ' DT';
}
function mFmtDate($d): string {
    if (!$d) return '—';
    try { return (new DateTime($d))->format('d/m/Y'); } catch (Exception $e) { return mE($d); }
}
function mRef($id): string {
    return 'DEV-2026-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
}
function mInitiales(array $d): string {
    return strtoupper(
        mb_substr((string)($d['prenom'] ?? ''), 0, 1, 'UTF-8') .
        mb_substr((string)($d['nom']    ?? ''), 0, 1, 'UTF-8')
    );
}

$type   = (string)($devis['type_assurance'] ?? '');
$statut = (string)($old['statut'] ?? $devis['statut'] ?? 'en_attente');

$typeLabels  = ['auto' => 'Auto', 'habitation' => 'Habitation', 'sante' => 'Santé'];
$typeIcons   = ['auto' => 'car-front', 'habitation' => 'house-door', 'sante' => 'heart-pulse'];
$typeClasses = ['auto' => 'type-auto', 'habitation' => 'type-habitation', 'sante' => 'type-sante'];

$statutLabels  = ['en_attente' => 'En attente', 'en_cours' => 'En cours', 'accepte' => 'Accepté', 'refuse' => 'Refusé', 'expire' => 'Expiré'];
$statutIcons   = ['en_attente' => 'hourglass-split', 'en_cours' => 'arrow-repeat', 'accepte' => 'check-circle', 'refuse' => 'x-circle', 'expire' => 'clock-history'];
$statutClasses = ['en_attente' => 'status-en_attente', 'en_cours' => 'status-en_cours', 'accepte' => 'status-accepte', 'refuse' => 'status-refuse', 'expire' => 'status-expire'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Devis <?= mE(mRef($devis['id_devis'] ?? 0)) ?> — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/view/BackOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/view/BackOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/view/BackOffice/assets/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/view/BackOffice/assets/css/admin-users.css">
    <style>
        /* ── BADGES ── */
        .devis-type-badge,.devis-status{display:inline-flex;align-items:center;gap:7px;padding:7px 13px;border-radius:999px;font-size:12px;font-weight:700;white-space:nowrap;}
        .type-auto{background:rgba(0,194,255,.12);color:#8fe9ff;border:1px solid rgba(0,194,255,.25);}
        .type-habitation{background:rgba(255,153,0,.12);color:#ffd28a;border:1px solid rgba(255,153,0,.25);}
        .type-sante{background:rgba(0,214,143,.12);color:#94ffd8;border:1px solid rgba(0,214,143,.25);}
        .status-en_attente{background:rgba(255,193,7,.12);border:1px solid rgba(255,193,7,.24);color:#ffd66e;}
        .status-en_cours{background:rgba(13,202,240,.12);border:1px solid rgba(13,202,240,.24);color:#8eeaff;}
        .status-accepte{background:rgba(25,135,84,.12);border:1px solid rgba(25,135,84,.24);color:#90f1bc;}
        .status-refuse{background:rgba(220,53,69,.12);border:1px solid rgba(220,53,69,.24);color:#ff9cab;}
        .status-expire{background:rgba(108,117,125,.16);border:1px solid rgba(108,117,125,.24);color:#d0d5dd;}

        /* ── HERO ── */
        .mod-hero {
            position: relative; overflow: hidden;
            padding: 26px 28px; border-radius: 24px;
            background:
                radial-gradient(circle at 80% 20%, rgba(255,107,26,.12), transparent 30%),
                linear-gradient(135deg, rgba(255,255,255,.05), rgba(255,255,255,.02));
            border: 1px solid rgba(255,255,255,.08);
            margin-bottom: 24px;
            display: flex; align-items: center; gap: 20px; flex-wrap: wrap;
        }
        .mod-hero::before {
            content:""; position:absolute; width:160px; height:160px;
            right:-30px; top:-30px; border-radius:50%;
            background:rgba(255,107,26,.06); pointer-events:none;
        }
        .mod-avatar {
            width: 66px; height: 66px; border-radius: 20px;
            display: grid; place-items: center;
            font-size: 22px; font-weight: 800; color: #fff;
            background: linear-gradient(135deg, rgba(255,107,26,.95), rgba(255,140,66,.85));
            box-shadow: 0 14px 28px rgba(255,107,26,.22);
            flex-shrink: 0; position: relative; z-index: 1;
        }
        .mod-hero-body { flex: 1; position: relative; z-index: 1; }
        .mod-hero-name { font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 6px; }
        .mod-hero-contact { color: var(--text-secondary); font-size: 13px; margin-bottom: 12px; display:flex;gap:16px;flex-wrap:wrap; }
        .mod-hero-contact span { display:flex;align-items:center;gap:6px; }
        .mod-hero-badges { display: flex; gap: 8px; flex-wrap: wrap; }
        .mod-ref {
            display:inline-flex;align-items:center;gap:7px;padding:7px 13px;
            border-radius:999px;background:rgba(255,255,255,.06);
            border:1px solid rgba(255,255,255,.10);color:#fff;font-size:12px;font-weight:700;
        }

        /* ── LAYOUT FORMULAIRE ── */
        .mod-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 20px;
            align-items: start;
        }
        .mod-main   { display: flex; flex-direction: column; gap: 20px; }
        .mod-aside  { position: sticky; top: 20px; display: flex; flex-direction: column; gap: 16px; }

        /* ── CARDS ── */
        .mod-card {
            border-radius: 22px; padding: 24px;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.03);
        }
        .mod-card-title {
            font-size: 15px; font-weight: 800; color: #fff;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
            padding-bottom: 14px;
            border-bottom: 1px solid rgba(255,255,255,.06);
        }
        .mod-card-title i { color: var(--accent); font-size: 17px; }

        /* ── CHAMPS ── */
        .form-row   { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .form-row-1 { display: grid; grid-template-columns: 1fr;     gap: 16px; margin-bottom: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 7px; }
        .form-label {
            font-size: 12px; font-weight: 700; color: var(--text-secondary);
            text-transform: uppercase; letter-spacing: .06em;
            display: flex; align-items: center; gap: 6px;
        }
        .form-label i { color: var(--accent); }
        .form-label .required { color: #ff9cab; margin-left: 3px; }

        .form-control {
            width: 100%; padding: 13px 16px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.10);
            background: rgba(255,255,255,.05);
            color: #fff; font-size: 14px;
            font-family: var(--font-body);
            outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .form-control:focus {
            border-color: rgba(255,107,26,.45);
            box-shadow: 0 0 0 3px rgba(255,107,26,.10);
            background: rgba(255,255,255,.07);
        }
        .form-control.error {
            border-color: rgba(220,53,69,.5);
            box-shadow: 0 0 0 3px rgba(220,53,69,.10);
        }
        .form-control option { background: #1a2035; color: #fff; }
        select.form-control { cursor: pointer; }
        textarea.form-control { resize: vertical; min-height: 110px; line-height: 1.6; }

        .form-error {
            font-size: 12px; color: #ff9cab; font-weight: 600;
            display: flex; align-items: center; gap: 5px;
        }
        .form-hint {
            font-size: 11px; color: var(--text-secondary); line-height: 1.5;
        }

        /* ── READONLY FIELDS ── */
        .readonly-field {
            padding: 13px 16px; border-radius: 14px;
            border: 1px solid rgba(255,255,255,.06);
            background: rgba(255,255,255,.03);
            color: var(--text-secondary); font-size: 14px;
            cursor: not-allowed;
        }

        /* ── STATUT SELECTOR ── */
        .statut-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 6px;
        }
        .statut-option { position: relative; }
        .statut-option input[type="radio"] { position: absolute; opacity: 0; inset: 0; pointer-events: none; }
        .statut-label {
            display: flex; flex-direction: column; align-items: center; gap: 8px;
            padding: 14px 10px; border-radius: 14px; cursor: pointer;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.04);
            transition: all .2s ease; text-align: center;
        }
        .statut-label:hover { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.16); }
        .statut-label i { font-size: 18px; color: var(--text-secondary); }
        .statut-label span { font-size: 11px; font-weight: 700; color: var(--text-secondary); }
        .statut-option input:checked + .statut-label {
            border-color: rgba(255,107,26,.35);
            background: rgba(255,107,26,.10);
        }
        .statut-option input:checked + .statut-label i,
        .statut-option input:checked + .statut-label span { color: #fff; }

        /* Couleurs statuts */
        .statut-option.s-en_attente input:checked + .statut-label { border-color:rgba(255,193,7,.4);  background:rgba(255,193,7,.10); }
        .statut-option.s-en_attente input:checked + .statut-label i,
        .statut-option.s-en_attente input:checked + .statut-label span { color:#ffd66e; }
        .statut-option.s-en_cours   input:checked + .statut-label { border-color:rgba(13,202,240,.4);  background:rgba(13,202,240,.10); }
        .statut-option.s-en_cours   input:checked + .statut-label i,
        .statut-option.s-en_cours   input:checked + .statut-label span { color:#8eeaff; }
        .statut-option.s-accepte    input:checked + .statut-label { border-color:rgba(25,135,84,.4);   background:rgba(25,135,84,.10); }
        .statut-option.s-accepte    input:checked + .statut-label i,
        .statut-option.s-accepte    input:checked + .statut-label span { color:#90f1bc; }
        .statut-option.s-refuse     input:checked + .statut-label { border-color:rgba(220,53,69,.4);   background:rgba(220,53,69,.10); }
        .statut-option.s-refuse     input:checked + .statut-label i,
        .statut-option.s-refuse     input:checked + .statut-label span { color:#ff9cab; }
        .statut-option.s-expire     input:checked + .statut-label { border-color:rgba(108,117,125,.4); background:rgba(108,117,125,.10); }
        .statut-option.s-expire     input:checked + .statut-label i,
        .statut-option.s-expire     input:checked + .statut-label span { color:#d0d5dd; }

        /* ── ASIDE RÉSUMÉ ── */
        .aside-info-card {
            border-radius: 18px; padding: 18px;
            border: 1px solid rgba(255,255,255,.07);
            background: rgba(255,255,255,.03);
        }
        .aside-info-title {
            font-size: 13px; font-weight: 800; color: #fff;
            margin-bottom: 14px; display: flex; align-items: center; gap: 8px;
        }
        .aside-info-title i { color: var(--accent); }
        .aside-row {
            display: flex; justify-content: space-between; gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,.05);
            font-size: 13px;
        }
        .aside-row:last-child { border-bottom: none; padding-bottom: 0; }
        .aside-row-label { color: var(--text-secondary); font-weight: 600; }
        .aside-row-value { color: #fff; font-weight: 700; text-align: right; }

        .aside-warn {
            padding: 14px 16px; border-radius: 14px;
            background: rgba(255,193,7,.07);
            border: 1px solid rgba(255,193,7,.20);
            font-size: 13px; color: #ffd66e; line-height: 1.65;
            display: flex; gap: 10px;
        }
        .aside-warn i { flex-shrink: 0; margin-top: 2px; }

        .aside-tip {
            padding: 14px 16px; border-radius: 14px;
            background: rgba(0,194,255,.07);
            border: 1px solid rgba(0,194,255,.18);
            font-size: 13px; color: #8fe9ff; line-height: 1.65;
        }

        /* ── BOUTONS ── */
        .action-bar {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; flex-wrap: wrap; margin-bottom: 24px;
            padding: 14px 18px; border-radius: 18px;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.07);
        }
        .action-bar-left  { display: flex; gap: 10px; flex-wrap: wrap; }
        .action-bar-right { display: flex; gap: 10px; flex-wrap: wrap; }

        /* ── ALERT ── */
        .alert-bar {
            display: flex; align-items: center; gap: 12px;
            padding: 14px 20px; border-radius: 16px;
            font-size: 14px; font-weight: 600; margin-bottom: 18px;
        }
        .alert-danger { background: rgba(220,53,69,.10); border: 1px solid rgba(220,53,69,.24); color: #ff9cab; }

        /* ── INFOS CLIENT READONLY ── */
        .client-info-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
        }
        .client-info-item {
            padding: 12px 14px; border-radius: 12px;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.06);
        }
        .client-info-label { color: var(--text-secondary); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 5px; }
        .client-info-value { color: #fff; font-size: 14px; font-weight: 600; }

        /* ── RESPONSIVE ── */
        @media(max-width:1100px) { .mod-layout { grid-template-columns: 1fr; } .mod-aside { position: static; } }
        @media(max-width:768px)  { .form-row { grid-template-columns: 1fr; } .statut-grid { grid-template-columns: 1fr 1fr; } }
        @media(max-width:500px)  { .statut-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>
<div class="layout">

<?php include __DIR__ . '/../assets/includes/sidebar.php'; ?>

<main class="main">
    <div class="topbar">
        <div>
            <div class="topbar-title">Modifier le devis</div>
            <div class="topbar-sub" id="topbarDate"></div>
        </div>
        <div class="topbar-actions">
            <a href="#" class="topbar-btn" title="Notifications">
                <i class="bi bi-bell"></i><span class="notif-dot"></span>
            </a>
            <a href="#" class="topbar-btn" title="Aide"><i class="bi bi-question-circle"></i></a>
        </div>
    </div>

    <div class="content">

        <!-- Breadcrumb -->
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:22px;">
            <div>
                <div class="page-title">Modifier le devis</div>
                <div class="page-breadcrumb">
                    <i class="bi bi-house"></i>
                    <a href="<?= BASE_URL ?>/view/BackOffice/admin.php">Accueil</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <a href="<?= BASE_URL ?>/controller/DevisController.php?action=index">Devis</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <a href="<?= BASE_URL ?>/controller/DevisController.php?action=details&id=<?= mE($devis['id_devis'] ?? '') ?>"><?= mE(mRef($devis['id_devis'] ?? 0)) ?></a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <span>Modifier</span>
                </div>
            </div>
        </div>

        <!-- Action bar -->
        <div class="action-bar">
            <div class="action-bar-left">
                <a href="<?= BASE_URL ?>/controller/DevisController.php?action=index" class="btn btn-outline">
                    <i class="bi bi-arrow-left"></i> Retour à la liste
                </a>
                <a href="<?= BASE_URL ?>/controller/DevisController.php?action=details&id=<?= mE($devis['id_devis'] ?? '') ?>" class="btn btn-outline">
                    <i class="bi bi-eye"></i> Voir les détails
                </a>
            </div>
            <div class="action-bar-right">
                <form method="POST"
                      action="<?= BASE_URL ?>/controller/DevisController.php?action=supprimer&id=<?= mE($devis['id_devis'] ?? '') ?>"
                      style="display:inline;"
                      onsubmit="return confirm('Supprimer définitivement ce devis ?')">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash3"></i> Supprimer
                    </button>
                </form>
            </div>
        </div>

        <!-- Erreurs globales -->
        <?php if (!empty($errors['general'])): ?>
        <div class="alert-bar alert-danger">
            <i class="bi bi-exclamation-circle-fill"></i>
            <?= mE($errors['general']) ?>
        </div>
        <?php endif; ?>

        <!-- Hero client -->
        <div class="mod-hero">
            <div class="mod-avatar"><?= mE(mInitiales($devis)) ?></div>
            <div class="mod-hero-body">
                <div class="mod-hero-name"><?= mE(trim(($devis['prenom'] ?? '') . ' ' . ($devis['nom'] ?? ''))) ?></div>
                <div class="mod-hero-contact">
                    <span><i class="bi bi-envelope"></i> <?= mE($devis['email'] ?? '—') ?></span>
                    <span><i class="bi bi-telephone"></i> <?= mE($devis['telephone'] ?? '—') ?></span>
                    <span><i class="bi bi-calendar3"></i> Soumis le <?= mE(mFmtDate($devis['date_demande'] ?? null)) ?></span>
                </div>
                <div class="mod-hero-badges">
                    <span class="devis-type-badge <?= mE($typeClasses[$type] ?? 'type-auto') ?>">
                        <i class="bi bi-<?= mE($typeIcons[$type] ?? 'file-earmark') ?>"></i>
                        <?= mE($typeLabels[$type] ?? $type) ?>
                    </span>
                    <span class="devis-status <?= mE($statutClasses[$statut] ?? 'status-en_attente') ?>" id="statutBadge">
                        <i class="bi bi-<?= mE($statutIcons[$statut] ?? 'circle') ?>" id="statutBadgeIcon"></i>
                        <span id="statutBadgeLabel"><?= mE($statutLabels[$statut] ?? $statut) ?></span>
                    </span>
                    <span class="mod-ref"><i class="bi bi-upc-scan"></i><?= mE(mRef($devis['id_devis'] ?? 0)) ?></span>
                </div>
            </div>
        </div>

        <!-- Formulaire -->
        <form method="POST"
              action="<?= BASE_URL ?>/controller/DevisController.php?action=modifier&id=<?= mE($devis['id_devis'] ?? '') ?>"
              id="modForm">

            <div class="mod-layout">

                <!-- ══ COLONNE PRINCIPALE ══ -->
                <div class="mod-main">

                    <!-- Informations client (lecture seule) -->
                    <div class="mod-card">
                        <div class="mod-card-title">
                            <i class="bi bi-person-vcard"></i> Informations client
                            <span style="margin-left:auto;font-size:11px;font-weight:600;color:var(--text-secondary);padding:4px 10px;border-radius:999px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);">
                                <i class="bi bi-lock"></i> Non modifiable
                            </span>
                        </div>
                        <div class="client-info-grid">
                            <div class="client-info-item">
                                <div class="client-info-label">Nom</div>
                                <div class="client-info-value"><?= mE($devis['nom'] ?? '—') ?></div>
                            </div>
                            <div class="client-info-item">
                                <div class="client-info-label">Prénom</div>
                                <div class="client-info-value"><?= mE($devis['prenom'] ?? '—') ?></div>
                            </div>
                            <div class="client-info-item">
                                <div class="client-info-label">Email</div>
                                <div class="client-info-value"><?= mE($devis['email'] ?? '—') ?></div>
                            </div>
                            <div class="client-info-item">
                                <div class="client-info-label">Téléphone</div>
                                <div class="client-info-value"><?= mE($devis['telephone'] ?? '—') ?></div>
                            </div>
                            <div class="client-info-item">
                                <div class="client-info-label">Type d'assurance</div>
                                <div class="client-info-value"><?= mE($typeLabels[$type] ?? $type) ?></div>
                            </div>
                            <div class="client-info-item">
                                <div class="client-info-label">Offre souscrite</div>
                                <div class="client-info-value"><?= mE($devis['nom_offre'] ?? '—') ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Statut -->
                    <div class="mod-card">
                        <div class="mod-card-title">
                            <i class="bi bi-arrow-repeat"></i> Statut du devis
                            <span class="required" style="color:#ff9cab;font-size:12px;margin-left:4px;">*</span>
                        </div>

                        <div class="statut-grid">
                            <?php
                            $statutOptions = [
                                'en_attente' => ['label' => 'En attente',  'icon' => 'hourglass-split'],
                                'en_cours'   => ['label' => 'En cours',    'icon' => 'arrow-repeat'],
                                'accepte'    => ['label' => 'Accepté',     'icon' => 'check-circle'],
                                'refuse'     => ['label' => 'Refusé',      'icon' => 'x-circle'],
                                'expire'     => ['label' => 'Expiré',      'icon' => 'clock-history'],
                            ];
                            foreach ($statutOptions as $val => $opt):
                                $checked = ($statut === $val) ? 'checked' : '';
                            ?>
                            <div class="statut-option s-<?= mE($val) ?>">
                                <input type="radio" name="statut" value="<?= mE($val) ?>" id="statut_<?= mE($val) ?>" <?= $checked ?> onchange="updateStatutBadge(this)">
                                <label class="statut-label" for="statut_<?= mE($val) ?>">
                                    <i class="bi bi-<?= mE($opt['icon']) ?>"></i>
                                    <span><?= mE($opt['label']) ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (!empty($errors['statut'])): ?>
                            <div class="form-error" style="margin-top:8px;">
                                <i class="bi bi-exclamation-circle"></i> <?= mE($errors['statut']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Montant estimé -->
                    <div class="mod-card">
                        <div class="mod-card-title">
                            <i class="bi bi-cash-stack"></i> Estimation financière
                        </div>

                        <div class="form-row-1">
                            <div class="form-group">
                                <label class="form-label" for="montant_estime">
                                    <i class="bi bi-coin"></i> Montant estimé (DT)
                                </label>
                                <div style="position:relative;">
                                    <input type="number"
                                           step="0.001"
                                           min="0"
                                           name="montant_estime"
                                           id="montant_estime"
                                           class="form-control <?= !empty($errors['montant_estime']) ? 'error' : '' ?>"
                                           placeholder="0.000"
                                           value="<?= mE($old['montant_estime'] ?? $devis['montant_estime'] ?? '') ?>"
                                    >
                                    <span style="position:absolute;right:14px;top:50%;transform:translateY(-50%);color:var(--text-secondary);font-weight:700;pointer-events:none;font-size:13px;">DT</span>
                                </div>
                                <?php if (!empty($errors['montant_estime'])): ?>
                                    <div class="form-error"><i class="bi bi-exclamation-circle"></i> <?= mE($errors['montant_estime']) ?></div>
                                <?php else: ?>
                                    <div class="form-hint">Laissez vide si le montant n'est pas encore calculé. Entrez le prix que vous proposez au client.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Réponse admin -->
                    <div class="mod-card">
                        <div class="mod-card-title">
                            <i class="bi bi-chat-left-text"></i> Réponse à envoyer au client
                        </div>

                        <div class="form-row-1">
                            <div class="form-group">
                                <label class="form-label" for="reponse_admin">
                                    <i class="bi bi-pencil"></i> Message de réponse
                                </label>
                                <textarea
                                    name="reponse_admin"
                                    id="reponse_admin"
                                    class="form-control <?= !empty($errors['reponse_admin']) ? 'error' : '' ?>"
                                    placeholder="Ex : Votre devis a été étudié. Nous vous proposons une couverture complète pour un montant de X DT par an..."
                                    rows="6"
                                ><?= mE($old['reponse_admin'] ?? $devis['reponse_admin'] ?? '') ?></textarea>
                                <?php if (!empty($errors['reponse_admin'])): ?>
                                    <div class="form-error"><i class="bi bi-exclamation-circle"></i> <?= mE($errors['reponse_admin']) ?></div>
                                <?php else: ?>
                                    <div class="form-hint">Maximum 1000 caractères. <span id="charCount" style="color:var(--accent);">0</span>/1000</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ══ COLONNE ASIDE ══ -->
                <div class="mod-aside">

                    <!-- Bouton soumettre -->
                    <div class="mod-card">
                        <div class="mod-card-title"><i class="bi bi-floppy2"></i> Enregistrer</div>
                        <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;" id="btnSave">
                            <i class="bi bi-check2-circle"></i> Enregistrer les modifications
                        </button>
                        <a href="<?= BASE_URL ?>/controller/DevisController.php?action=details&id=<?= mE($devis['id_devis'] ?? '') ?>"
                           class="btn btn-outline"
                           style="width:100%;margin-top:10px;">
                            <i class="bi bi-x"></i> Annuler
                        </a>
                    </div>

                    <!-- Résumé du devis -->
                    <div class="aside-info-card">
                        <div class="aside-info-title"><i class="bi bi-file-earmark-text"></i> Résumé du devis</div>
                        <div class="aside-row">
                            <span class="aside-row-label">Référence</span>
                            <span class="aside-row-value"><?= mE(mRef($devis['id_devis'] ?? 0)) ?></span>
                        </div>
                        <div class="aside-row">
                            <span class="aside-row-label">Client</span>
                            <span class="aside-row-value"><?= mE(trim(($devis['prenom'] ?? '') . ' ' . ($devis['nom'] ?? ''))) ?></span>
                        </div>
                        <div class="aside-row">
                            <span class="aside-row-label">Type</span>
                            <span class="aside-row-value"><?= mE($typeLabels[$type] ?? $type) ?></span>
                        </div>
                        <div class="aside-row">
                            <span class="aside-row-label">Offre</span>
                            <span class="aside-row-value"><?= mE($devis['nom_offre'] ?? '—') ?></span>
                        </div>
                        <div class="aside-row">
                            <span class="aside-row-label">Date demande</span>
                            <span class="aside-row-value"><?= mE(mFmtDate($devis['date_demande'] ?? null)) ?></span>
                        </div>
                        <div class="aside-row">
                            <span class="aside-row-label">Montant actuel</span>
                            <span class="aside-row-value"><?= mE(mFmt($devis['montant_estime'] ?? null)) ?></span>
                        </div>
                    </div>

                    <!-- Avertissement -->
                    <div class="aside-warn">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>
                            Seuls le <strong>statut</strong>, le <strong>montant estimé</strong> et la <strong>réponse admin</strong> sont modifiables. Les informations du client ne peuvent pas être changées ici.
                        </div>
                    </div>

                    <!-- Conseil -->
                    <div class="aside-tip">
                        <i class="bi bi-lightbulb" style="margin-right:6px;"></i>
                        <strong>Conseil :</strong> Passez le statut à <em>En cours</em> pendant l'étude, puis à <em>Accepté</em> ou <em>Refusé</em> une fois la décision prise. Ajoutez toujours un message de réponse pour informer le client.
                    </div>

                </div>
            </div>
        </form>

    </div>
</main>
</div>

<script src="<?= BASE_URL ?>/view/BackOffice/assets/js/main.js"></script>
<script>
// Date topbar
document.getElementById('topbarDate').textContent = new Date().toLocaleDateString('fr-FR', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
});

// Compteur caractères réponse admin
const textarea  = document.getElementById('reponse_admin');
const charCount = document.getElementById('charCount');
if (textarea && charCount) {
    charCount.textContent = textarea.value.length;
    textarea.addEventListener('input', function () {
        const len = this.value.length;
        charCount.textContent = len;
        charCount.style.color = len > 900 ? '#ff9cab' : 'var(--accent)';
    });
}

// Mise à jour du badge statut en temps réel
const statutData = {
    en_attente: { label: 'En attente', icon: 'hourglass-split', cls: 'status-en_attente' },
    en_cours:   { label: 'En cours',   icon: 'arrow-repeat',    cls: 'status-en_cours'   },
    accepte:    { label: 'Accepté',    icon: 'check-circle',    cls: 'status-accepte'    },
    refuse:     { label: 'Refusé',     icon: 'x-circle',        cls: 'status-refuse'     },
    expire:     { label: 'Expiré',     icon: 'clock-history',   cls: 'status-expire'     },
};

function updateStatutBadge(radio) {
    const badge     = document.getElementById('statutBadge');
    const badgeIcon = document.getElementById('statutBadgeIcon');
    const badgeLabel= document.getElementById('statutBadgeLabel');
    if (!badge || !radio) return;

    const data = statutData[radio.value];
    if (!data) return;

    // Retirer toutes les classes statut
    Object.values(statutData).forEach(d => badge.classList.remove(d.cls));
    badge.classList.add(data.cls);
    badgeIcon.className  = 'bi bi-' + data.icon;
    badgeLabel.textContent = data.label;
}

// Confirmation avant soumission
document.getElementById('modForm')?.addEventListener('submit', function (e) {
    const btn = document.getElementById('btnSave');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat" style="animation:spin .8s linear infinite"></i> Enregistrement...';
    }
});

// Animation spin
const style = document.createElement('style');
style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
document.head.appendChild(style);
</script>
</body>
</html>


