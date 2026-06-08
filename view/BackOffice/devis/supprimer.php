<?php
/**
 * view/BackOffice/devis/supprimer.php
 * Page de confirmation de suppression d’un devis
 */

$base = (defined('BASE_URL') ? BASE_URL : '');

$devis = $devis ?? null;

if (!$devis) {
    header('Location: ' . $base . '/controller/DevisController.php?action=index&erreur=Devis introuvable');
    exit;
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatDateDevis($date): string
{
    if (empty($date)) {
        return '—';
    }

    try {
        $dt = new DateTime($date);
        return $dt->format('d/m/Y H:i');
    } catch (Exception $e) {
        return h($date);
    }
}

function formatMontantDevis($montant): string
{
    if ($montant === null || $montant === '') {
        return '—';
    }

    return number_format((float)$montant, 3, '.', ' ') . ' DT';
}

function referenceDevis($id): string
{
    return 'DEV-2026-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
}

$idDevis   = $devis['id_devis'] ?? 0;
$reference = referenceDevis($idDevis);
$client    = trim(($devis['prenom'] ?? '') . ' ' . ($devis['nom'] ?? ''));
$email     = $devis['email'] ?? '—';
$telephone = $devis['telephone'] ?? '—';
$type      = $devis['type_assurance'] ?? '—';
$offre     = $devis['nom_offre'] ?? '—';
$statut    = $devis['statut'] ?? '—';
$montant   = $devis['montant_estime'] ?? null;
$date      = $devis['date_demande'] ?? null;
$reponse   = $devis['reponse_admin'] ?? '—';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Supprimer Devis — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="<?= h($base) ?>/view/BackOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= h($base) ?>/view/BackOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= h($base) ?>/view/BackOffice/assets/css/layout.css">
    <link rel="stylesheet" href="<?= h($base) ?>/view/BackOffice/assets/css/admin-users.css">

    <style>
        .delete-page {
            max-width: 1050px;
            margin: 0 auto;
        }

        .delete-hero {
            border-radius: 30px;
            border: 1px solid rgba(220, 53, 69, .28);
            background:
                radial-gradient(circle at top left, rgba(220, 53, 69, .22), transparent 38%),
                linear-gradient(135deg, rgba(255, 255, 255, .05), rgba(255, 255, 255, .02));
            box-shadow: 0 30px 80px rgba(0, 0, 0, .25);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .delete-hero-top {
            padding: 32px;
            display: flex;
            align-items: center;
            gap: 22px;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
        }

        .delete-icon {
            width: 86px;
            height: 86px;
            border-radius: 28px;
            display: grid;
            place-items: center;
            font-size: 38px;
            color: #fff;
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            box-shadow: 0 20px 45px rgba(220, 53, 69, .30);
            flex-shrink: 0;
        }

        .delete-title {
            color: #fff;
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .delete-subtitle {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.7;
            max-width: 720px;
        }

        .delete-ref {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            padding: 8px 14px;
            border-radius: 999px;
            color: #fff;
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .10);
            font-size: 13px;
            font-weight: 800;
        }

        .delete-content {
            padding: 28px 32px 32px;
        }

        .danger-box {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            padding: 18px 20px;
            border-radius: 22px;
            background: rgba(255, 193, 7, .10);
            border: 1px solid rgba(255, 193, 7, .25);
            color: #ffd66e;
            margin-bottom: 24px;
            line-height: 1.7;
            font-size: 13px;
            font-weight: 600;
        }

        .danger-box i {
            font-size: 22px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }

        .detail-card {
            border-radius: 20px;
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .08);
            padding: 18px;
            min-height: 92px;
        }

        .detail-label {
            display: flex;
            align-items: center;
            gap: 7px;
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .detail-value {
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            word-break: break-word;
            line-height: 1.5;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 13px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            background: rgba(255, 193, 7, .12);
            color: #ffd66e;
            border: 1px solid rgba(255, 193, 7, .24);
        }

        .delete-question {
            border-radius: 24px;
            padding: 24px;
            background: linear-gradient(135deg, rgba(220, 53, 69, .12), rgba(255, 255, 255, .03));
            border: 1px solid rgba(220, 53, 69, .22);
            margin-bottom: 24px;
        }

        .delete-question h3 {
            color: #fff;
            font-size: 18px;
            font-weight: 800;
            margin: 0 0 8px;
        }

        .delete-question p {
            color: var(--text-secondary);
            margin: 0;
            line-height: 1.7;
            font-size: 14px;
        }

        .confirm-check {
            margin-top: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
        }

        .confirm-check input {
            width: 18px;
            height: 18px;
            accent-color: #dc3545;
        }

        .actions-zone {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            padding-top: 22px;
            border-top: 1px solid rgba(255, 255, 255, .08);
        }

        .actions-left,
        .actions-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-delete-final {
            background: linear-gradient(135deg, #dc3545, #ff5c5c);
            color: #fff;
            border: none;
            opacity: .55;
            pointer-events: none;
        }

        .btn-delete-final.enabled {
            opacity: 1;
            pointer-events: auto;
        }

        .btn-delete-final:hover {
            filter: brightness(1.08);
        }

        .soft-card {
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, .08);
            background: rgba(255, 255, 255, .035);
            padding: 22px;
            margin-bottom: 20px;
        }

        .soft-card-title {
            color: #fff;
            font-weight: 800;
            font-size: 15px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .soft-card-text {
            color: var(--text-secondary);
            font-size: 13px;
            line-height: 1.8;
        }

        @media(max-width: 1000px) {
            .details-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width: 700px) {
            .delete-hero-top {
                flex-direction: column;
                align-items: flex-start;
                padding: 24px;
            }

            .delete-content {
                padding: 24px;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .actions-zone {
                align-items: stretch;
            }

            .actions-left,
            .actions-right {
                width: 100%;
            }

            .actions-left a,
            .actions-right button {
                width: 100%;
                justify-content: center;
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
                <div class="topbar-title">Suppression du devis</div>
                <div class="topbar-sub"><?= h(date('d/m/Y')) ?></div>
            </div>

            <div class="topbar-actions">
                <a href="#" class="topbar-btn" title="Notifications">
                    <i class="bi bi-bell"></i>
                    <span class="notif-dot"></span>
                </a>
                <a href="#" class="topbar-btn" title="Aide">
                    <i class="bi bi-question-circle"></i>
                </a>
            </div>
        </div>

        <div class="content delete-page">

            <div class="page-header-flex">
                <div>
                    <div class="page-title">Confirmation de suppression</div>
                    <div class="page-breadcrumb">
                        <i class="bi bi-house"></i>
                        <a href="<?= h($base) ?>/view/BackOffice/admin.php">Accueil</a>
                        <i class="bi bi-chevron-right" style="font-size:10px"></i>
                        <a href="<?= h($base) ?>/controller/DevisController.php?action=index">Devis</a>
                        <i class="bi bi-chevron-right" style="font-size:10px"></i>
                        <span>Supprimer</span>
                    </div>
                </div>

                <div class="header-actions">
                    <a href="<?= h($base) ?>/controller/DevisController.php?action=index" class="btn btn-outline">
                        <i class="bi bi-arrow-left"></i>
                        Retour liste
                    </a>
                </div>
            </div>

            <div class="delete-hero">

                <div class="delete-hero-top">
                    <div class="delete-icon">
                        <i class="bi bi-trash3"></i>
                    </div>

                    <div>
                        <div class="delete-title">Supprimer ce devis ?</div>

                        <div class="delete-subtitle">
                            Vous êtes sur une page de confirmation. Le devis ne sera supprimé
                            que lorsque vous cliquez sur le bouton rouge de confirmation.
                        </div>

                        <div class="delete-ref">
                            <i class="bi bi-upc-scan"></i>
                            <?= h($reference) ?>
                        </div>
                    </div>
                </div>

                <div class="delete-content">

                    <div class="danger-box">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>
                            Cette action est définitive. Après confirmation, ce devis sera supprimé
                            de la base de données. Si vous voulez garder une trace, il vaut mieux
                            changer son statut en <strong>refusé</strong> ou <strong>expiré</strong>.
                        </div>
                    </div>

                    <div class="details-grid">

                        <div class="detail-card">
                            <div class="detail-label">
                                <i class="bi bi-person"></i>
                                Client
                            </div>
                            <div class="detail-value"><?= h($client ?: '—') ?></div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-label">
                                <i class="bi bi-envelope"></i>
                                Email
                            </div>
                            <div class="detail-value"><?= h($email) ?></div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-label">
                                <i class="bi bi-telephone"></i>
                                Téléphone
                            </div>
                            <div class="detail-value"><?= h($telephone) ?></div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-label">
                                <i class="bi bi-shield-check"></i>
                                Type assurance
                            </div>
                            <div class="detail-value"><?= h($type) ?></div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-label">
                                <i class="bi bi-box-seam"></i>
                                Offre
                            </div>
                            <div class="detail-value"><?= h($offre) ?></div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-label">
                                <i class="bi bi-activity"></i>
                                Statut
                            </div>
                            <div class="detail-value">
                                <span class="status-pill">
                                    <i class="bi bi-hourglass-split"></i>
                                    <?= h($statut) ?>
                                </span>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-label">
                                <i class="bi bi-cash-stack"></i>
                                Montant estimé
                            </div>
                            <div class="detail-value"><?= h(formatMontantDevis($montant)) ?></div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-label">
                                <i class="bi bi-calendar-event"></i>
                                Date demande
                            </div>
                            <div class="detail-value"><?= h(formatDateDevis($date)) ?></div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-label">
                                <i class="bi bi-hash"></i>
                                ID devis
                            </div>
                            <div class="detail-value">#<?= h($idDevis) ?></div>
                        </div>

                    </div>

                    <div class="soft-card">
                        <div class="soft-card-title">
                            <i class="bi bi-chat-left-text"></i>
                            Réponse admin
                        </div>
                        <div class="soft-card-text">
                            <?= nl2br(h($reponse ?: 'Aucune réponse admin enregistrée.')) ?>
                        </div>
                    </div>

                    <div class="delete-question">
                        <h3>Validation finale</h3>
                        <p>
                            Pour éviter toute suppression accidentelle, cochez la case ci-dessous
                            avant de confirmer la suppression du devis.
                        </p>

                        <label class="confirm-check">
                            <input type="checkbox" id="confirmDelete">
                            Je confirme vouloir supprimer ce devis définitivement.
                        </label>
                    </div>

                    <form method="POST"
                          action="<?= h($base) ?>/controller/DevisController.php?action=supprimer&id=<?= h($idDevis) ?>"
                          onsubmit="return confirm('Confirmer la suppression définitive du devis <?= h($reference) ?> ?');">

                        <div class="actions-zone">

                            <div class="actions-left">
                                <a href="<?= h($base) ?>/controller/DevisController.php?action=index"
                                   class="btn btn-outline">
                                    <i class="bi bi-x-circle"></i>
                                    Annuler
                                </a>

                                <a href="<?= h($base) ?>/controller/DevisController.php?action=modifier&id=<?= h($idDevis) ?>"
                                   class="btn btn-outline">
                                    <i class="bi bi-pencil"></i>
                                    Modifier au lieu de supprimer
                                </a>
                            </div>

                            <div class="actions-right">
                                <button type="submit"
                                        id="deleteButton"
                                        class="btn btn-delete-final">
                                    <i class="bi bi-trash3"></i>
                                    Confirmer la suppression
                                </button>
                            </div>

                        </div>

                    </form>

                </div>
            </div>

        </div>
    </main>
</div>

<script src="<?= h($base) ?>/view/BackOffice/assets/js/main.js"></script>

<script>
const checkbox = document.getElementById('confirmDelete');
const deleteButton = document.getElementById('deleteButton');

checkbox.addEventListener('change', function () {
    if (this.checked) {
        deleteButton.classList.add('enabled');
    } else {
        deleteButton.classList.remove('enabled');
    }
});
</script>

</body>
</html>
