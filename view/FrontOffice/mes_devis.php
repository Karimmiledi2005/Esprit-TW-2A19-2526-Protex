<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireClient();

require_once __DIR__ . '/../../config.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', '');
}

$frontBase = BASE_URL . '/view/FrontOffice';

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatMontant($montant): string {
    if ($montant === null || $montant === '') {
        return 'Pas encore estimé';
    }
    return number_format((float)$montant, 3, ',', ' ') . ' DT';
}

function badgeStatut(string $statut): string {
    return match ($statut) {
        'accepte'    => 'badge-success',
        'refuse'     => 'badge-danger',
        'en_cours'   => 'badge-warning',
        'expire'     => 'badge-muted',
        default      => 'badge-info',
    };
}

$db = config::getConnexion();

// ── SECURITY FIX ──────────────────────────────────────────────────────────────
// Never trust user input for data lookup. Always resolve the email from the
// server-side session so a client cannot view another client's devis.
// ──────────────────────────────────────────────────────────────────────────────
$uid = (int)($_SESSION['user_id'] ?? 0);
$devis   = [];
$message = '';
$email   = '';

if ($uid > 0) {
    try {
        $userStmt = $db->prepare("SELECT email FROM user WHERE id_user = ?");
        $userStmt->execute([$uid]);
        $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
        $email = $userRow['email'] ?? '';

        if ($email !== '') {
            $stmt = $db->prepare("
                SELECT d.*, o.nom_offre
                FROM devis d
                LEFT JOIN offre o ON d.id_offre = o.id_offre
                WHERE d.email = :email
                ORDER BY d.date_demande DESC, d.id_devis DESC
            ");
            $stmt->execute([':email' => $email]);
            $devis = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($devis)) {
                $message = "Vous n'avez pas encore de devis.";
            }
        }
    } catch (Exception $e) {
        $message = "Erreur chargement devis : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes devis - Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="<?= $frontBase ?>/assets/css/variables.css">
    <link rel="stylesheet" href="<?= $frontBase ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= $frontBase ?>/assets/css/layout.css">
    <link rel="stylesheet" href="<?= $frontBase ?>/assets/css/client.css">
    <link rel="stylesheet" href="<?= $frontBase ?>/assets/css/light-theme.css">

    <style>
        .devis-page {
            padding: 32px;
            min-height: calc(100vh - 80px);
            background:
                radial-gradient(circle at top left, rgba(255,107,26,0.10), transparent 35%),
                radial-gradient(circle at bottom right, rgba(26,58,122,0.12), transparent 35%),
                #f5f7fb;
        }

        .devis-hero {
            background: linear-gradient(135deg, #1A3A7A, #10224d);
            color: white;
            border-radius: 26px;
            padding: 34px;
            margin-bottom: 26px;
            box-shadow: 0 18px 45px rgba(26,58,122,0.22);
            position: relative;
            overflow: hidden;
        }

        .devis-hero::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(255,107,26,0.22);
            top: -90px;
            right: -70px;
        }

        .devis-hero h1 {
            margin: 0;
            font-size: 34px;
            position: relative;
            z-index: 1;
        }

        .devis-hero p {
            margin-top: 10px;
            color: rgba(255,255,255,0.82);
            max-width: 700px;
            position: relative;
            z-index: 1;
        }

        .message-box {
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fed7aa;
            padding: 16px;
            border-radius: 16px;
            margin-bottom: 20px;
        }

        .devis-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
        }

        .devis-card {
            background: white;
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 14px 35px rgba(0,0,0,0.08);
            border: 1px solid rgba(26,58,122,0.08);
            position: relative;
            overflow: hidden;
        }

        .devis-card::before {
            content: "";
            position: absolute;
            width: 7px;
            height: 100%;
            background: #FF6B1A;
            left: 0;
            top: 0;
        }

        .devis-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 20px;
        }

        .devis-ref {
            font-size: 20px;
            font-weight: 900;
            color: #1A3A7A;
        }

        .devis-type {
            margin-top: 5px;
            color: #6b7280;
            font-size: 14px;
        }

        .status-badge {
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 800;
            text-transform: capitalize;
            white-space: nowrap;
        }

        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger  { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-muted   { background: #e5e7eb; color: #374151; }
        .badge-info    { background: #dbeafe; color: #1e40af; }

        .details-list {
            display: grid;
            gap: 12px;
            margin-bottom: 20px;
        }

        .detail-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            background: #f8f9ff;
            padding: 13px 14px;
            border-radius: 14px;
        }

        .detail-label { color: #6b7280; font-size: 14px; }
        .detail-value { color: #111827; font-weight: 700; text-align: right; }

        .admin-response {
            background: linear-gradient(135deg, #fff7ed, #ffffff);
            border: 1px solid #fed7aa;
            border-radius: 18px;
            padding: 18px;
        }

        .admin-response h3 {
            margin: 0 0 10px;
            color: #1A3A7A;
            font-size: 17px;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .admin-response p { margin: 0; color: #374151; line-height: 1.6; }
        .empty-response   { color: #9ca3af !important; font-style: italic; }

        .quick-actions {
            margin-top: 20px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .action-link {
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 12px;
            background: #f8f9ff;
            color: #1A3A7A;
            font-weight: 700;
            border: 1px solid rgba(26,58,122,0.10);
        }

        .empty-state {
            background: white;
            border-radius: 24px;
            padding: 45px;
            text-align: center;
            box-shadow: 0 12px 35px rgba(0,0,0,0.07);
        }

        .empty-state i  { font-size: 50px; color: #FF6B1A; }
        .empty-state h2 { color: #1A3A7A; margin-bottom: 8px; }
        .empty-state p  { color: #6b7280; }

        .btn-protex {
            padding: 14px 22px;
            border-radius: 14px;
            border: none;
            background: #FF6B1A;
            color: white;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            gap: 8px;
            align-items: center;
            justify-content: center;
        }
        .btn-protex:hover { background: #e95f13; }

        @media (max-width: 900px) {
            .devis-grid   { grid-template-columns: 1fr; }
            .devis-page   { padding: 18px; }
            .devis-hero h1{ font-size: 26px; }
        }
    </style>
</head>

<body>

<?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

<main class="devis-page">

    <section class="devis-hero">
        <h1>Mes devis</h1>
        <p>
            Consultez l'état de vos demandes de devis, le montant estimé proposé par l'administrateur
            et la réponse envoyée depuis le BackOffice.
        </p>
    </section>

    <?php if ($message !== ''): ?>
        <div class="message-box">
            <i class="bi bi-info-circle"></i>
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($devis)): ?>

        <section class="devis-grid">
            <?php foreach ($devis as $d): ?>
                <?php
                    $id = (int)$d['id_devis'];
                    $statut = (string)($d['statut'] ?? 'en_attente');
                ?>

                <article class="devis-card">

                    <div class="devis-card-header">
                        <div>
                            <div class="devis-ref">
                                DEV-2026-<?= str_pad((string)$id, 4, '0', STR_PAD_LEFT) ?>
                            </div>
                            <div class="devis-type">
                                Assurance <?= e($d['type_assurance'] ?? '') ?>
                            </div>
                        </div>

                        <span class="status-badge <?= badgeStatut($statut) ?>">
                            <?= e(str_replace('_', ' ', $statut)) ?>
                        </span>
                    </div>

                    <div class="details-list">
                        <div class="detail-row">
                            <span class="detail-label">Client</span>
                            <span class="detail-value">
                                <?= e(($d['prenom'] ?? '') . ' ' . ($d['nom'] ?? '')) ?>
                            </span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label">Offre</span>
                            <span class="detail-value">
                                <?= e($d['nom_offre'] ?? 'Non sélectionnée') ?>
                            </span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label">Téléphone</span>
                            <span class="detail-value">
                                <?= e($d['telephone'] ?? '') ?>
                            </span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label">Date demande</span>
                            <span class="detail-value">
                                <?= e($d['date_demande'] ?? '') ?>
                            </span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label">Montant estimé</span>
                            <span class="detail-value">
                                <?= e(formatMontant($d['montant_estime'] ?? null)) ?>
                            </span>
                        </div>
                    </div>

                    <div class="admin-response">
                        <h3>
                            <i class="bi bi-chat-left-text"></i>
                            Réponse admin
                        </h3>

                        <?php if (!empty($d['reponse_admin'])): ?>
                            <p><?= nl2br(e($d['reponse_admin'])) ?></p>
                        <?php else: ?>
                            <p class="empty-response">
                                Votre demande est en cours de traitement. Une réponse sera affichée ici.
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="quick-actions">
                        <a class="action-link" href="<?= $frontBase ?>/ajoutdevis.php">
                            <i class="bi bi-plus-circle"></i>
                            Nouvelle demande
                        </a>

                        <a class="action-link" href="mailto:support@protex.tn">
                            <i class="bi bi-envelope"></i>
                            Contacter support
                        </a>
                    </div>

                </article>
            <?php endforeach; ?>
        </section>

    <?php else: ?>

        <section class="empty-state">
            <i class="bi bi-file-earmark-x"></i>
            <h2>Aucun devis trouvé</h2>
            <p>
                Vous n'avez pas encore effectué de demande de devis.
            </p>

            <br>

            <a href="<?= $frontBase ?>/ajoutdevis.php" class="btn-protex">
                <i class="bi bi-plus-circle"></i>
                Créer une demande
            </a>
        </section>

    <?php endif; ?>

</main>

<script src="<?= $frontBase ?>/assets/js/main.js"></script>

</body>
</html>
