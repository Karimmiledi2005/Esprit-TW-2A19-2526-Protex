<?php
/**
 * view/BackOffice/devis/details.php
 * Détail complet d'un devis — Back-office Protex
 * Variables : $devis (array), $details (array|null)
 */

// ── Chargement direct si pas passé par le contrôleur ──────────
if (!isset($devis)) {
    if (!defined('BASE_URL')) define('BASE_URL', (defined('BASE_URL') ? BASE_URL : ''));
    include_once __DIR__ . '/../../../config.php';

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/controller/DevisController.php?action=index');
        exit;
    }

    $db   = config::getConnexion();
    $stmt = $db->prepare("SELECT d.*, o.nom_offre FROM devis d LEFT JOIN offre o ON d.id_offre = o.id_offre WHERE d.id_devis = ?");
    $stmt->execute([$id]);
    $devis = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$devis) {
        header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/controller/DevisController.php?action=index&erreur=Devis+introuvable');
        exit;
    }

    $tables  = ['auto'=>'devis_auto','habitation'=>'devis_habitation','sante'=>'devis_sante'];
    $details = null;
    if (isset($tables[$devis['type_assurance']])) {
        $s = $db->prepare("SELECT * FROM " . $tables[$devis['type_assurance']] . " WHERE id_devis = ?");
        $s->execute([$id]);
        $details = $s->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

if (!defined('BASE_URL')) define('BASE_URL', (defined('BASE_URL') ? BASE_URL : ''));
$base = (defined('BASE_URL') ? BASE_URL : '');

// ── Helpers ───────────────────────────────────────────────────
function dE($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

function dFmt($v): string {
    if ($v === null || $v === '') return '—';
    return number_format((float)$v, 3, '.', ' ') . ' DT';
}

function dFmtDate($d): string {
    if (!$d) return '—';
    try { return (new DateTime($d))->format('d/m/Y'); } catch (Exception $e) { return dE($d); }
}

function dFmtDateTime($d): string {
    if (!$d) return '—';
    try { return (new DateTime($d))->format('d/m/Y \à H:i'); } catch (Exception $e) { return dE($d); }
}

function dRef($id): string {
    return 'DEV-2026-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
}

function dInitiales(array $d): string {
    return strtoupper(mb_substr((string)($d['prenom'] ?? ''), 0, 1, 'UTF-8') . mb_substr((string)($d['nom'] ?? ''), 0, 1, 'UTF-8'));
}

// ── Données ───────────────────────────────────────────────────
$type   = (string)($devis['type_assurance'] ?? '');
$statut = (string)($devis['statut'] ?? '');

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
    <title>Détail Devis <?= dE(dRef($devis['id_devis'] ?? 0)) ?> — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= dE($base) ?>/view/BackOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= dE($base) ?>/view/BackOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= dE($base) ?>/view/BackOffice/assets/css/layout.css">
    <link rel="stylesheet" href="<?= dE($base) ?>/view/BackOffice/assets/css/admin-users.css">
    <style>
        /* ── BADGES TYPE ── */
        .devis-type-badge {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 14px; border-radius: 999px;
            font-size: 13px; font-weight: 700; white-space: nowrap;
        }
        .type-auto       { background: rgba(0,194,255,.12); color: #8fe9ff; border: 1px solid rgba(0,194,255,.25); }
        .type-habitation { background: rgba(255,153,0,.12);  color: #ffd28a; border: 1px solid rgba(255,153,0,.25); }
        .type-sante      { background: rgba(0,214,143,.12);  color: #94ffd8; border: 1px solid rgba(0,214,143,.25); }

        /* ── BADGES STATUT ── */
        .devis-status {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 14px; border-radius: 999px;
            font-size: 13px; font-weight: 700; white-space: nowrap;
        }
        .status-en_attente { background: rgba(255,193,7,.12);   border: 1px solid rgba(255,193,7,.24);   color: #ffd66e; }
        .status-en_cours   { background: rgba(13,202,240,.12);  border: 1px solid rgba(13,202,240,.24);  color: #8eeaff; }
        .status-accepte    { background: rgba(25,135,84,.12);   border: 1px solid rgba(25,135,84,.24);   color: #90f1bc; }
        .status-refuse     { background: rgba(220,53,69,.12);   border: 1px solid rgba(220,53,69,.24);   color: #ff9cab; }
        .status-expire     { background: rgba(108,117,125,.16); border: 1px solid rgba(108,117,125,.24); color: #d0d5dd; }

        /* ── HERO DEVIS ── */
        .detail-hero {
            position: relative;
            overflow: hidden;
            padding: 30px;
            border-radius: 26px;
            background:
                radial-gradient(circle at 80% 20%, rgba(255,107,26,.14), transparent 30%),
                radial-gradient(circle at 20% 80%, rgba(0,194,255,.08), transparent 30%),
                linear-gradient(135deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
            border: 1px solid rgba(255,255,255,.08);
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 24px;
            flex-wrap: wrap;
        }
        .detail-hero::before {
            content: "";
            position: absolute;
            width: 200px; height: 200px;
            right: -50px; top: -50px;
            border-radius: 50%;
            background: rgba(255,107,26,.06);
            pointer-events: none;
        }
        .detail-hero-avatar {
            width: 80px; height: 80px;
            border-radius: 24px;
            display: grid; place-items: center;
            font-size: 26px; font-weight: 800; color: #fff;
            background: linear-gradient(135deg, rgba(255,107,26,.95), rgba(255,140,66,.85));
            box-shadow: 0 16px 32px rgba(255,107,26,.25);
            flex-shrink: 0;
            position: relative; z-index: 1;
        }
        .detail-hero-body { flex: 1; position: relative; z-index: 1; }
        .detail-hero-name {
            font-size: 26px; font-weight: 800; color: #fff;
            margin-bottom: 8px; letter-spacing: -.3px;
        }
        .detail-hero-contact {
            display: flex; gap: 20px; flex-wrap: wrap;
            color: var(--text-secondary); font-size: 14px; margin-bottom: 16px;
        }
        .detail-hero-contact span { display: flex; align-items: center; gap: 7px; }
        .detail-hero-badges { display: flex; gap: 10px; flex-wrap: wrap; }
        .detail-ref {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 9px 16px; border-radius: 999px;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            color: #fff; font-size: 13px; font-weight: 700;
        }
        .detail-hero-meta {
            display: flex; flex-direction: column; gap: 12px;
            position: relative; z-index: 1;
        }
        .detail-meta-card {
            padding: 16px 20px; border-radius: 18px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.08);
            min-width: 160px;
        }
        .detail-meta-label { color: var(--text-secondary); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 6px; }
        .detail-meta-value { color: #fff; font-size: 18px; font-weight: 800; }
        .detail-meta-sub   { color: var(--text-secondary); font-size: 11px; margin-top: 3px; }

        /* ── BARRE D'ACTIONS ── */
        .action-bar {
            display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px;
            padding: 16px 20px; border-radius: 18px;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.07);
            align-items: center; justify-content: space-between;
        }
        .action-bar-left  { display: flex; gap: 10px; flex-wrap: wrap; }
        .action-bar-right { display: flex; gap: 10px; flex-wrap: wrap; }

        /* ── GRILLE DE DÉTAILS ── */
        .detail-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .detail-card {
            border-radius: 22px; padding: 24px;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.03);
        }
        .detail-card-title {
            font-size: 15px; font-weight: 800; color: #fff;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
            padding-bottom: 14px;
            border-bottom: 1px solid rgba(255,255,255,.06);
        }
        .detail-card-title i { color: var(--accent); font-size: 17px; }

        .detail-pairs { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .detail-pair  { padding: 14px; border-radius: 14px; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.06); }
        .detail-pair-full { grid-column: 1 / -1; }
        .detail-pair-label { color: var(--text-secondary); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 7px; }
        .detail-pair-value { color: #fff; font-size: 14px; font-weight: 700; line-height: 1.4; }

        /* ── RÉPONSE ADMIN ── */
        .reponse-block {
            margin-top: 16px; padding: 18px;
            border-radius: 16px;
            background: rgba(255,107,26,.06);
            border: 1px solid rgba(255,107,26,.18);
        }
        .reponse-block-title {
            color: var(--accent); font-size: 13px; font-weight: 700;
            margin-bottom: 10px; display: flex; align-items: center; gap: 7px;
        }
        .reponse-block-text {
            color: var(--text-secondary); font-size: 14px; line-height: 1.75;
        }

        /* ── TIMELINE STATUTS ── */
        .timeline-statut {
            display: flex; gap: 0; align-items: center;
            padding: 20px 24px;
            border-radius: 20px;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.07);
            margin-bottom: 20px;
            overflow-x: auto;
        }
        .tl-step {
            display: flex; flex-direction: column; align-items: center;
            gap: 8px; flex: 1; min-width: 80px; position: relative;
        }
        .tl-step:not(:last-child)::after {
            content: "";
            position: absolute;
            top: 16px; left: calc(50% + 18px);
            width: calc(100% - 36px); height: 2px;
            background: rgba(255,255,255,.1);
        }
        .tl-step.done:not(:last-child)::after  { background: rgba(0,214,143,.4); }
        .tl-step.current:not(:last-child)::after { background: rgba(255,193,7,.3); }
        .tl-dot {
            width: 36px; height: 36px; border-radius: 50%;
            display: grid; place-items: center; font-size: 14px;
            border: 2px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.05); color: var(--text-secondary);
            position: relative; z-index: 1;
        }
        .tl-step.done .tl-dot    { background: rgba(0,214,143,.2); border-color: rgba(0,214,143,.5); color: #90f1bc; }
        .tl-step.current .tl-dot { background: rgba(255,193,7,.2); border-color: rgba(255,193,7,.5); color: #ffd66e; box-shadow: 0 0 0 4px rgba(255,193,7,.08); }
        .tl-label { font-size: 11px; font-weight: 700; color: var(--text-secondary); text-align: center; }
        .tl-step.done .tl-label    { color: #90f1bc; }
        .tl-step.current .tl-label { color: #ffd66e; }

        /* ── CARTE PLEINE LARGEUR ── */
        .detail-card-full {
            border-radius: 22px; padding: 24px;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.03);
            margin-bottom: 20px;
        }

        /* ── AUCUN DETAIL ── */
        .no-detail {
            text-align: center; padding: 32px;
            color: var(--text-secondary); font-size: 14px;
        }
        .no-detail i { display: block; font-size: 28px; margin-bottom: 10px; opacity: .5; }

        /* ── BREADCRUMB ── */
        .page-header-flex {
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; flex-wrap: wrap; margin-bottom: 22px;
        }

        /* ── ALERT ── */
        .alert-bar {
            display: flex; align-items: center; gap: 12px;
            padding: 14px 20px; border-radius: 16px;
            font-size: 14px; font-weight: 600; margin-bottom: 18px;
        }
        .alert-success { background: rgba(0,214,143,.10); border: 1px solid rgba(0,214,143,.24); color: #90f1bc; }
        .alert-danger  { background: rgba(220,53,69,.10);  border: 1px solid rgba(220,53,69,.24);  color: #ff9cab; }

        /* ── RESPONSIVE ── */
        @media(max-width:1100px) { .detail-section { grid-template-columns: 1fr; } }
        @media(max-width:900px)  { .detail-pairs { grid-template-columns: 1fr; } .detail-hero { flex-direction: column; } }
        @media(max-width:640px)  { .action-bar { flex-direction: column; align-items: stretch; } .action-bar-left,.action-bar-right { flex-direction: column; } }
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
            <div class="topbar-title">Détail du devis</div>
            <div class="topbar-sub"><?= dE(date('l d F Y', time())) ?></div>
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
        <div class="page-header-flex">
            <div>
                <div class="page-title">Détail du devis</div>
                <div class="page-breadcrumb">
                    <i class="bi bi-house"></i>
                    <a href="<?= dE($base) ?>/view/BackOffice/admin.php">Accueil</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/controller/DevisController.php?action=index">Devis</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <span><?= dE(dRef($devis['id_devis'] ?? 0)) ?></span>
                </div>
            </div>
        </div>

        <!-- Action bar -->
        <div class="action-bar">
            <div class="action-bar-left">
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/controller/DevisController.php?action=index" class="btn btn-outline">
                    <i class="bi bi-arrow-left"></i> Retour à la liste
                </a>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/controller/DevisController.php?action=modifier&id=<?= $devis['id_devis'] ?>" class="btn btn-primary">
                    Modifier ce devis
                </a>
                <?php if ($statut === 'accepte'): ?>
                    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/controller/DevisController.php?action=convertir&id=<?= $devis['id_devis'] ?>" class="btn btn-success">
                        <i class="bi bi-file-earmark-check"></i> Convertir en contrat
                    </a>
                <?php endif; ?>
            </div>
            <div class="action-bar-right">
                <form method="POST"
                      action="<?= dE($base) ?>/controller/DevisController.php?action=supprimer&id=<?= dE($devis['id_devis'] ?? '') ?>"
                      style="display:inline;"
                      onsubmit="return confirm('Supprimer définitivement ce devis ? Cette action est irréversible.')">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash3"></i> Supprimer
                    </button>
                </form>
            </div>
        </div>

        <!-- Timeline statuts -->
        <?php
        $steps = [
            ['key'=>'en_attente', 'label'=>'En attente', 'icon'=>'hourglass-split'],
            ['key'=>'en_cours',   'label'=>'En cours',   'icon'=>'arrow-repeat'],
            ['key'=>'accepte',    'label'=>'Accepté',    'icon'=>'check-circle'],
        ];
        $statutOrder = ['en_attente'=>0,'en_cours'=>1,'accepte'=>2,'refuse'=>2,'expire'=>2];
        $currentOrder = $statutOrder[$statut] ?? 0;
        ?>
        <div class="timeline-statut">
            <?php foreach ($steps as $i => $step): ?>
                <?php
                $stepOrder = $statutOrder[$step['key']] ?? $i;
                $isDone    = $currentOrder > $stepOrder;
                $isCurrent = $statut === $step['key'] || ($statut === 'refuse' && $step['key'] === 'accepte') || ($statut === 'expire' && $step['key'] === 'accepte');
                $cls = $isDone ? 'done' : ($isCurrent ? 'current' : '');
                ?>
                <div class="tl-step <?= $cls ?>">
                    <div class="tl-dot">
                        <?php if ($isDone): ?>
                            <i class="bi bi-check2"></i>
                        <?php elseif ($isCurrent): ?>
                            <i class="bi bi-<?= dE($step['icon']) ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-<?= dE($step['icon']) ?>"></i>
                        <?php endif; ?>
                    </div>
                    <div class="tl-label"><?= dE($step['label']) ?></div>
                </div>
            <?php endforeach; ?>

            <?php if ($statut === 'refuse' || $statut === 'expire'): ?>
            <div class="tl-step current">
                <div class="tl-dot" style="background:rgba(220,53,69,.2);border-color:rgba(220,53,69,.5);color:#ff9cab;">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="tl-label" style="color:#ff9cab;">
                    <?= $statut === 'refuse' ? 'Refusé' : 'Expiré' ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Hero client -->
        <div class="detail-hero">
            <div class="detail-hero-avatar">
                <?= dE(dInitiales($devis)) ?>
            </div>
            <div class="detail-hero-body">
                <div class="detail-hero-name">
                    <?= dE(trim(($devis['prenom'] ?? '') . ' ' . ($devis['nom'] ?? ''))) ?>
                </div>
                <div class="detail-hero-contact">
                    <span><i class="bi bi-envelope"></i> <?= dE($devis['email'] ?? '—') ?></span>
                    <span><i class="bi bi-telephone"></i> <?= dE($devis['telephone'] ?? '—') ?></span>
                    <span><i class="bi bi-calendar3"></i> Soumis le <?= dE(dFmtDate($devis['date_demande'] ?? null)) ?></span>
                </div>
                <div class="detail-hero-badges">
                    <span class="devis-type-badge <?= dE($typeClasses[$type] ?? 'type-auto') ?>">
                        <i class="bi bi-<?= dE($typeIcons[$type] ?? 'file-earmark') ?>"></i>
                        <?= dE($typeLabels[$type] ?? $type) ?>
                    </span>
                    <span class="devis-status <?= dE($statutClasses[$statut] ?? 'status-en_attente') ?>">
                        <i class="bi bi-<?= dE($statutIcons[$statut] ?? 'circle') ?>"></i>
                        <?= dE($statutLabels[$statut] ?? $statut) ?>
                    </span>
                    <span class="detail-ref">
                        <i class="bi bi-upc-scan"></i>
                        <?= dE(dRef($devis['id_devis'] ?? 0)) ?>
                    </span>
                </div>
            </div>
            <div class="detail-hero-meta">
                <div class="detail-meta-card">
                    <div class="detail-meta-label">Offre souscrite</div>
                    <div class="detail-meta-value" style="font-size:14px;"><?= dE($devis['nom_offre'] ?? '—') ?></div>
                    <div class="detail-meta-sub">#<?= dE($devis['id_offre'] ?? '—') ?></div>
                </div>
                <div class="detail-meta-card">
                    <div class="detail-meta-label">Montant estimé</div>
                    <div class="detail-meta-value"><?= dE(dFmt($devis['montant_estime'] ?? null)) ?></div>
                    <div class="detail-meta-sub">Estimation admin</div>
                </div>
            </div>
        </div>

        <!-- Grille principale -->
        <div class="detail-section">

            <!-- Informations générales -->
            <div class="detail-card">
                <div class="detail-card-title">
                    <i class="bi bi-file-earmark-text"></i> Informations générales
                </div>
                <div class="detail-pairs">
                    <div class="detail-pair">
                        <div class="detail-pair-label">Référence</div>
                        <div class="detail-pair-value"><?= dE(dRef($devis['id_devis'] ?? 0)) ?></div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Date de demande</div>
                        <div class="detail-pair-value"><?= dE(dFmtDateTime($devis['date_demande'] ?? null)) ?></div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Type d'assurance</div>
                        <div class="detail-pair-value"><?= dE($typeLabels[$type] ?? $type) ?></div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Statut actuel</div>
                        <div class="detail-pair-value"><?= dE($statutLabels[$statut] ?? $statut) ?></div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Offre choisie</div>
                        <div class="detail-pair-value"><?= dE($devis['nom_offre'] ?? '—') ?></div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Montant estimé</div>
                        <div class="detail-pair-value"><?= dE(dFmt($devis['montant_estime'] ?? null)) ?></div>
                    </div>
                </div>

                <?php if (!empty($devis['reponse_admin'])): ?>
                <div class="reponse-block">
                    <div class="reponse-block-title">
                        <i class="bi bi-chat-left-text"></i> Réponse de l'administrateur
                    </div>
                    <div class="reponse-block-text"><?= dE($devis['reponse_admin']) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Coordonnées client -->
            <div class="detail-card">
                <div class="detail-card-title">
                    <i class="bi bi-person-vcard"></i> Coordonnées du client
                </div>
                <div class="detail-pairs">
                    <div class="detail-pair">
                        <div class="detail-pair-label">Nom</div>
                        <div class="detail-pair-value"><?= dE($devis['nom'] ?? '—') ?></div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Prénom</div>
                        <div class="detail-pair-value"><?= dE($devis['prenom'] ?? '—') ?></div>
                    </div>
                    <div class="detail-pair detail-pair-full">
                        <div class="detail-pair-label">Adresse email</div>
                        <div class="detail-pair-value"><?= dE($devis['email'] ?? '—') ?></div>
                    </div>
                    <div class="detail-pair detail-pair-full">
                        <div class="detail-pair-label">Numéro de téléphone</div>
                        <div class="detail-pair-value"><?= dE($devis['telephone'] ?? '—') ?></div>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div style="margin-top:20px;padding-top:16px;border-top:1px solid rgba(255,255,255,.06);">
                    <div style="color:var(--text-secondary);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px;">Actions rapides</div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <a href="mailto:<?= dE($devis['email'] ?? '') ?>" class="btn btn-outline btn-sm">
                            <i class="bi bi-envelope"></i> Envoyer un email
                        </a>
                        <a href="<?= dE($base) ?>/controller/DevisController.php?action=modifier&id=<?= dE($devis['id_devis'] ?? '') ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-pencil"></i> Modifier le devis
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Détails spécifiques au type -->
        <?php if ($details): ?>
        <div class="detail-card-full">
            <?php if ($type === 'auto'): ?>
                <div class="detail-card-title">
                    <i class="bi bi-car-front"></i> Informations véhicule
                </div>
                <div class="detail-pairs">
                    <div class="detail-pair">
                        <div class="detail-pair-label">Marque</div>
                        <div class="detail-pair-value"><?= dE($details['marque'] ?? '—') ?></div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Modèle</div>
                        <div class="detail-pair-value"><?= dE($details['modele'] ?? '—') ?></div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Année</div>
                        <div class="detail-pair-value"><?= dE($details['annee'] ?? '—') ?></div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Immatriculation</div>
                        <div class="detail-pair-value"><?= dE($details['immatriculation'] ?? '—') ?></div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Puissance fiscale</div>
                        <div class="detail-pair-value"><?= dE($details['puissance'] ?? '—') ?> CV</div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Type de carburant</div>
                        <div class="detail-pair-value"><?= dE($details['carburant'] ?? '—') ?></div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Valeur estimée du véhicule</div>
                        <div class="detail-pair-value"><?= dE(dFmt($details['valeur_vehicule'] ?? null)) ?></div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Usage du véhicule</div>
                        <div class="detail-pair-value"><?= dE($details['usage_vehicule'] ?? '—') ?></div>
                    </div>
                </div>

            <?php elseif ($type === 'habitation'): ?>
                <div class="detail-card-title">
                    <i class="bi bi-house-door"></i> Informations habitation
                </div>
                <div class="detail-pairs">
                    <div class="detail-pair">
                        <div class="detail-pair-label">Type d'habitation</div>
                        <div class="detail-pair-value"><?= dE($details['type_habitation'] ?? '—') ?></div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Statut d'occupation</div>
                        <div class="detail-pair-value"><?= dE($details['statut_occupation'] ?? '—') ?></div>
                    </div>
                    <div class="detail-pair detail-pair-full">
                        <div class="detail-pair-label">Adresse du bien</div>
                        <div class="detail-pair-value"><?= dE($details['adresse'] ?? '—') ?></div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Superficie</div>
                        <div class="detail-pair-value">
                            <?= ($details['superficie'] ?? null) ? dE($details['superficie']) . ' m²' : '—' ?>
                        </div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Nombre de pièces</div>
                        <div class="detail-pair-value"><?= dE($details['nombre_pieces'] ?? '—') ?></div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Valeur estimée du bien</div>
                        <div class="detail-pair-value"><?= dE(dFmt($details['valeur_bien'] ?? null)) ?></div>
                    </div>
                </div>

            <?php elseif ($type === 'sante'): ?>
                <div class="detail-card-title">
                    <i class="bi bi-heart-pulse"></i> Informations santé
                </div>
                <div class="detail-pairs">
                    <div class="detail-pair">
                        <div class="detail-pair-label">Âge du demandeur</div>
                        <div class="detail-pair-value">
                            <?= ($details['age'] ?? null) ? dE($details['age']) . ' ans' : '—' ?>
                        </div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Situation familiale</div>
                        <div class="detail-pair-value"><?= dE($details['situation_familiale'] ?? '—') ?></div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Nombre de bénéficiaires</div>
                        <div class="detail-pair-value"><?= dE($details['nombre_beneficiaires'] ?? '—') ?></div>
                    </div>
                    <div class="detail-pair">
                        <div class="detail-pair-label">Profession</div>
                        <div class="detail-pair-value"><?= dE($details['profession'] ?? '—') ?></div>
                    </div>
                    <div class="detail-pair detail-pair-full">
                        <div class="detail-pair-label">Couverture souhaitée</div>
                        <div class="detail-pair-value"><?= dE($details['couverture_souhaitee'] ?? '—') ?></div>
                    </div>
                    <?php if (!empty($details['antecedents_medicaux'])): ?>
                    <div class="detail-pair detail-pair-full">
                        <div class="detail-pair-label">Antécédents médicaux déclarés</div>
                        <div class="detail-pair-value" style="line-height:1.6;"><?= dE($details['antecedents_medicaux']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="detail-card-full">
            <div class="no-detail">
                <i class="bi bi-info-circle"></i>
                Aucun détail spécifique disponible pour ce devis.
            </div>
        </div>
        <?php endif; ?>

    </div>
</main>
</div>

<script src="<?= dE($base) ?>/view/BackOffice/assets/js/main.js"></script>
<script>
    // Topbar date
    const topbarSub = document.querySelector('.topbar-sub');
    if (topbarSub) {
        topbarSub.textContent = new Date().toLocaleDateString('fr-FR', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
        });
    }
</script>
</body>
</html>


