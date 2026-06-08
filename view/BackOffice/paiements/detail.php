<?php
declare(strict_types=1);

$paiement = $paiement ?? null;
$message  = $message  ?? ($_GET['message'] ?? '');
$erreur   = $erreur   ?? ($_GET['erreur']  ?? '');
$BASE_URL = (defined('BASE_URL') ? BASE_URL : '');

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatMoney($value): string
{
    return number_format((float)$value, 3, '.', ' ') . ' TND';
}

function formatDateFr(?string $date): string
{
    if (empty($date)) {
        return '—';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '—';
    }

    return date('d/m/Y', $timestamp);
}

function formatDateTimeFr(?string $date): string
{
    if (empty($date)) {
        return '—';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '—';
    }

    return date('d/m/Y à H:i', $timestamp);
}

function ucfirstSafe(?string $value): string
{
    $value = (string)$value;
    if ($value === '') {
        return '—';
    }
    return ucfirst($value);
}

$statut = strtolower((string)($paiement['statut'] ?? ''));
$type   = strtolower((string)($paiement['type_offre'] ?? ''));
$methode = strtolower((string)($paiement['methode'] ?? ''));

$typeIcons = [
    'auto' => 'bi-car-front',
    'sante' => 'bi-heart-pulse',
    'habitation' => 'bi-house-door',
    'vie' => 'bi-shield-check'
];
$typeIcon = $typeIcons[$type] ?? 'bi-tags';

$methodeIcons = [
    'carte' => 'bi-credit-card-2-front',
    'virement' => 'bi-bank',
    'mobile' => 'bi-phone'
];
$methodeIcon = $methodeIcons[$methode] ?? 'bi-credit-card';

$echeanceSoon = false;
if (!empty($paiement['date_echeance'])) {
    $timestamp = strtotime((string)$paiement['date_echeance']);
    if ($timestamp !== false) {
        $diff = ($timestamp - time()) / 86400;
        $echeanceSoon = ($diff >= 0 && $diff <= 3);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Détail du paiement — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e($BASE_URL) ?>/view/BackOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= e($BASE_URL) ?>/view/BackOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= e($BASE_URL) ?>/view/BackOffice/assets/css/layout.css">
    <link rel="stylesheet" href="<?= e($BASE_URL) ?>/view/BackOffice/assets/css/client.css">

    <style>
        .page-hero {
            position: relative;
            margin-bottom: 24px;
            padding: 26px 28px 22px;
            border-radius: 22px;
            background:
                radial-gradient(circle at top right, rgba(0,180,216,.14), transparent 35%),
                radial-gradient(circle at bottom left, rgba(16,185,129,.08), transparent 40%),
                linear-gradient(135deg, rgba(255,255,255,.05), rgba(255,255,255,.03));
            border: 1px solid rgba(255,255,255,.08);
            overflow: hidden;
        }

        .page-hero-head {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            flex-wrap: wrap;
        }

        .page-hero-title {
            margin: 0;
            font-size: 26px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.3px;
        }

        .page-hero-sub {
            margin: 8px 0 0;
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.6;
            max-width: 700px;
        }

        .hero-side {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .hero-mini-card {
            min-width: 130px;
            padding: 14px 16px;
            border-radius: 16px;
            text-align: center;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.08);
        }

        .hero-mini-card strong {
            display: block;
            color: #fff;
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .hero-mini-card span {
            color: var(--text-secondary);
            font-size: 12px;
        }

        .alert-ok {
            background: rgba(16,185,129,.08);
            border: 1px solid rgba(16,185,129,.25);
            border-radius: 14px;
            padding: 13px 18px;
            color: #86efac;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .alert-err {
            background: rgba(239,68,68,.08);
            border: 1px solid rgba(239,68,68,.25);
            border-radius: 14px;
            padding: 13px 18px;
            color: #fca5a5;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .detail-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            backdrop-filter: blur(20px);
            overflow: hidden;
        }

        .detail-card-head {
            padding: 18px 22px;
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .detail-card-title {
            font-family: var(--font-display);
            font-size: 16px;
            font-weight: 700;
            color: #fff;
        }

        .payment-main {
            padding: 22px;
        }

        .payment-banner {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            border-radius: 18px;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            margin-bottom: 20px;
        }

        .payment-icon {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            font-size: 24px;
            color: #fff;
            flex-shrink: 0;
        }

        .payment-icon.auto       { background: rgba(59,130,246,.22); }
        .payment-icon.sante      { background: rgba(16,185,129,.22); }
        .payment-icon.habitation { background: rgba(245,158,11,.22); }
        .payment-icon.vie        { background: rgba(236,72,153,.22); }

        .payment-name {
            font-size: 18px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 4px;
        }

        .payment-sub {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .ref-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border-radius: 8px;
            background: rgba(0,180,216,.1);
            border: 1px solid rgba(0,180,216,.2);
            color: var(--accent);
            font-family: monospace;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .5px;
            margin-top: 8px;
        }

        .fields-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .field-box {
            padding: 14px;
            border-radius: 16px;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
        }

        .field-box.full {
            grid-column: 1 / -1;
        }

        .field-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .7px;
            color: var(--text-secondary);
            margin-bottom: 7px;
            font-weight: 700;
        }

        .field-value {
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.5;
        }

        .field-sub {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        .badge-methode {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 11px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            color: #fff;
        }

        .badge-statut {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-statut.en_attente { background: rgba(245,158,11,.14); color: #fcd34d; }
        .badge-statut.valide     { background: rgba(16,185,129,.14); color: #86efac; }
        .badge-statut.refuse     { background: rgba(239,68,68,.14); color: #fca5a5; }
        .badge-statut.rembourse  { background: rgba(0,180,216,.14); color: #7dd3fc; }

        .side-panel {
            padding: 22px;
        }

        .side-stack {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .mini-info {
            padding: 16px;
            border-radius: 16px;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
        }

        .mini-info-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .7px;
            color: var(--text-secondary);
            margin-bottom: 8px;
            font-weight: 700;
        }

        .mini-info-value {
            font-size: 16px;
            color: #fff;
            font-weight: 800;
        }

        .mini-info-sub {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 5px;
        }

        .echeance-soon {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            color: #fbbf24;
            margin-top: 6px;
        }

        .action-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .btnx {
            height: 40px;
            padding: 0 16px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.05);
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            transition: .2s;
        }

        .btnx:hover {
            background: rgba(255,255,255,.1);
            transform: translateY(-1px);
        }

        .btnx.validate {
            background: rgba(16,185,129,.12);
            color: #86efac;
            border-color: rgba(16,185,129,.25);
        }

        .btnx.refuse {
            background: rgba(239,68,68,.12);
            color: #fca5a5;
            border-color: rgba(239,68,68,.25);
        }

        .btnx.refund {
            background: rgba(0,180,216,.12);
            color: #7dd3fc;
            border-color: rgba(0,180,216,.25);
        }

        .empty-box {
            text-align: center;
            padding: 70px 20px;
            color: var(--text-secondary);
        }

        .empty-box i {
            font-size: 44px;
            display: block;
            margin-bottom: 12px;
            opacity: .65;
        }

        .empty-box strong {
            display: block;
            color: #fff;
            margin-bottom: 8px;
            font-size: 18px;
        }

        @media (max-width: 1050px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .fields-grid {
                grid-template-columns: 1fr;
            }

            .hero-side {
                display: none;
            }

            .payment-banner {
                align-items: flex-start;
            }
        }
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
                <div class="topbar-title">Détail du paiement</div>
                <div class="topbar-sub" id="topbarDate"></div>
            </div>
            <div class="topbar-actions">
                <a href="<?= e($BASE_URL) ?>/controller/PaiementController.php" class="topbar-btn" title="Retour à la liste">
                    <i class="bi bi-arrow-left"></i>
                </a>
            </div>
        </div>

        <div class="content">
            <div class="page-breadcrumb" style="margin-bottom:24px;">
                <i class="bi bi-house"></i>
                <span>Admin</span>
                <i class="bi bi-chevron-right" style="font-size:10px"></i>
                <a href="<?= e($BASE_URL) ?>/controller/PaiementController.php" style="color:inherit;text-decoration:none;">Paiements</a>
                <i class="bi bi-chevron-right" style="font-size:10px"></i>
                <span>Détail</span>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert-ok">
                    <i class="bi bi-check-circle-fill"></i>
                    <strong><?= e($message) ?></strong>
                </div>
            <?php endif; ?>

            <?php if ($erreur !== ''): ?>
                <div class="alert-err">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong><?= e($erreur) ?></strong>
                </div>
            <?php endif; ?>

            <?php if (empty($paiement)): ?>
                <div class="detail-card">
                    <div class="empty-box">
                        <i class="bi bi-credit-card-2-front"></i>
                        <strong>Paiement introuvable</strong>
                        <p>Le paiement demandé n’existe pas ou n’est plus disponible.</p>
                        <div class="action-bar" style="justify-content:center;margin-top:16px;">
                            <a href="<?= e($BASE_URL) ?>/controller/PaiementController.php" class="btnx">
                                <i class="bi bi-arrow-left"></i> Retour à la liste
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>

                <section class="page-hero">
                    <div class="page-hero-head">
                        <div>
                            <h1 class="page-hero-title">Détail du paiement</h1>
                            <p class="page-hero-sub">
                                Consultez l’ensemble des informations liées à cette transaction :
                                offre souscrite, méthode de paiement, statut, échéance et motif éventuel.
                            </p>
                        </div>

                        <div class="hero-side">
                            <div class="hero-mini-card">
                                <strong><?= formatMoney($paiement['montant'] ?? 0) ?></strong>
                                <span>Montant</span>
                            </div>
                            <div class="hero-mini-card">
                                <strong><?= e(ucfirstSafe($paiement['periodicite'] ?? '')) ?></strong>
                                <span>Périodicité</span>
                            </div>
                            <div class="hero-mini-card">
                                <strong><?= e(ucfirstSafe($paiement['methode'] ?? '')) ?></strong>
                                <span>Méthode</span>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="detail-grid">
                    <div class="detail-card">
                        <div class="detail-card-head">
                            <div class="detail-card-title">Informations principales</div>
                            <span class="badge-statut <?= e($statut) ?>">
                                <?php if ($statut === 'en_attente'): ?>
                                    <i class="bi bi-hourglass-split"></i> En attente
                                <?php elseif ($statut === 'valide'): ?>
                                    <i class="bi bi-check-circle-fill"></i> Validé
                                <?php elseif ($statut === 'refuse'): ?>
                                    <i class="bi bi-x-circle-fill"></i> Refusé
                                <?php else: ?>
                                    <i class="bi bi-arrow-counterclockwise"></i> Remboursé
                                <?php endif; ?>
                            </span>
                        </div>

                        <div class="payment-main">
                            <div class="payment-banner">
                                <div class="payment-icon <?= e($type) ?>">
                                    <i class="bi <?= e($typeIcon) ?>"></i>
                                </div>
                                <div>
                                    <div class="payment-name"><?= e($paiement['nom_offre'] ?? '—') ?></div>
                                    <div class="payment-sub">Offre <?= e(ucfirstSafe($type)) ?></div>
                                    <div class="ref-badge">
                                        <i class="bi bi-hash"></i>
                                        <?= e($paiement['reference'] ?? '—') ?>
                                    </div>
                                </div>
                            </div>

                            <div class="fields-grid">
                                <div class="field-box">
                                    <div class="field-label">Montant payé</div>
                                    <div class="field-value"><?= formatMoney($paiement['montant'] ?? 0) ?></div>
                                </div>

                                <div class="field-box">
                                    <div class="field-label">Périodicité</div>
                                    <div class="field-value"><?= e(ucfirstSafe($paiement['periodicite'] ?? '')) ?></div>
                                </div>

                                <div class="field-box">
                                    <div class="field-label">Méthode de paiement</div>
                                    <div class="field-value">
                                        <span class="badge-methode">
                                            <i class="bi <?= e($methodeIcon) ?>"></i>
                                            <?= e(ucfirstSafe($paiement['methode'] ?? '')) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="field-box">
                                    <div class="field-label">Carte masquée</div>
                                    <div class="field-value"><?= e($paiement['num_carte_masque'] ?? '—') ?></div>
                                </div>

                                <div class="field-box">
                                    <div class="field-label">Date du paiement</div>
                                    <div class="field-value"><?= e(formatDateTimeFr($paiement['date_paiement'] ?? null)) ?></div>
                                </div>

                                <div class="field-box">
                                    <div class="field-label">Date d’échéance</div>
                                    <div class="field-value"><?= e(formatDateFr($paiement['date_echeance'] ?? null)) ?></div>
                                    <?php if ($echeanceSoon): ?>
                                        <div class="echeance-soon">
                                            <i class="bi bi-alarm-fill"></i> Échéance proche
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($paiement['motif_refus'])): ?>
                                    <div class="field-box full">
                                        <div class="field-label">Motif</div>
                                        <div class="field-value"><?= e($paiement['motif_refus']) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="detail-card">
                        <div class="detail-card-head">
                            <div class="detail-card-title">Actions administrateur</div>
                        </div>

                        <div class="side-panel">
                            <div class="side-stack">
                                <div class="mini-info">
                                    <div class="mini-info-title">Référence</div>
                                    <div class="mini-info-value"><?= e($paiement['reference'] ?? '—') ?></div>
                                    <div class="mini-info-sub">Identifiant unique du paiement</div>
                                </div>

                                <div class="mini-info">
                                    <div class="mini-info-title">Statut actuel</div>
                                    <div class="mini-info-value"><?= e(ucfirstSafe($paiement['statut'] ?? '')) ?></div>
                                    <div class="mini-info-sub">État métier de la transaction</div>
                                </div>

                                <div class="mini-info">
                                    <div class="mini-info-title">Actions disponibles</div>
                                    <div class="action-bar">
                                        <a href="<?= e($BASE_URL) ?>/controller/PaiementController.php" class="btnx">
                                            <i class="bi bi-arrow-left"></i> Retour
                                        </a>

                                        <a href="<?= e($BASE_URL) ?>/view/FrontOffice/recu_paiement.php?id=<?= (int)($paiement['id_paiement'] ?? 0) ?>"
                                           class="btnx" target="_blank">
                                            <i class="bi bi-receipt"></i> Reçu
                                        </a>

                                        <?php if ($statut === 'en_attente'): ?>
                                            <a href="<?= e($BASE_URL) ?>/controller/PaiementController.php?action=valider&id=<?= (int)($paiement['id_paiement'] ?? 0) ?>"
                                               class="btnx validate"
                                               onclick="return confirm('Valider ce paiement ?')">
                                                <i class="bi bi-check2"></i> Valider
                                            </a>

                                            <a href="<?= e($BASE_URL) ?>/controller/PaiementController.php?action=refuser&id=<?= (int)($paiement['id_paiement'] ?? 0) ?>"
                                               class="btnx refuse">
                                                <i class="bi bi-x-lg"></i> Refuser
                                            </a>
                                        <?php elseif ($statut === 'valide'): ?>
                                            <a href="<?= e($BASE_URL) ?>/controller/PaiementController.php?action=rembourser&id=<?= (int)($paiement['id_paiement'] ?? 0) ?>"
                                               class="btnx refund">
                                                <i class="bi bi-arrow-counterclockwise"></i> Rembourser
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mini-info">
                                    <div class="mini-info-title">Conseil</div>
                                    <div class="mini-info-sub">
                                        Un paiement ne doit pas être supprimé dans une logique métier normale.
                                        Il doit être validé, refusé ou remboursé pour conserver l’historique financier.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </main>
</div>

<script src="<?= e($BASE_URL) ?>/view/BackOffice/assets/js/main.js"></script>
<script>
document.getElementById('topbarDate').textContent =
    new Date().toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
</script>
</body>
</html>
