```php
<?php
$offre    = $offre    ?? [];
$BASE_URL = (defined('BASE_URL') ? BASE_URL : '');

if (empty($offre)) {
    header('Location: ' . $BASE_URL . '/controller/OffreController.php?erreur=Offre introuvable');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Supprimer une offre — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/view/BackOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/view/BackOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/view/BackOffice/assets/css/layout.css">
    
    <style>
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(16px); }
            to   { opacity:1; transform:translateY(0); }
        }

        .confirm-wrap {
            max-width: 560px;
            margin: 0 auto;
            animation: fadeUp .4s ease both;
        }

        /* Card danger */
        .danger-card {
            background: var(--glass-bg);
            border: 1px solid rgba(239,68,68,.3);
            border-radius: 22px;
            overflow: hidden;
            backdrop-filter: blur(20px);
        }

        .danger-card-head {
            background: rgba(239,68,68,.07);
            border-bottom: 1px solid rgba(239,68,68,.2);
            padding: 22px 26px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .danger-icon-wrap {
            width: 52px; height: 52px;
            border-radius: 16px;
            background: rgba(239,68,68,.15);
            border: 1px solid rgba(239,68,68,.25);
            display: grid; place-items: center;
            font-size: 22px;
            color: #fca5a5;
            flex-shrink: 0;
        }

        .danger-card-title {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
        }

        .danger-card-sub {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .danger-card-body { padding: 26px; }

        /* Aperçu offre */
        .offre-preview {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 16px;
            padding: 18px 20px;
            margin-bottom: 20px;
        }

        .offre-preview-top {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }

        .offre-preview-icon {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: grid; place-items: center;
            font-size: 20px; color: #fff;
            flex-shrink: 0;
        }

        .offre-preview-icon.auto       { background: rgba(59,130,246,.2);  }
        .offre-preview-icon.sante      { background: rgba(16,185,129,.2);  }
        .offre-preview-icon.habitation { background: rgba(245,158,11,.2);  }
        .offre-preview-icon.vie        { background: rgba(236,72,153,.2);  }

        .offre-preview-name {
            font-family: var(--font-display);
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 5px;
        }

        .offre-preview-type {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .offre-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .offre-info-item {
            padding: 10px 12px;
            border-radius: 12px;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.06);
        }

        .offre-info-label {
            font-size: 10px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 5px;
        }

        .offre-info-value {
            font-size: 13px;
            font-weight: 600;
            color: #fff;
        }

        /* Warning box */
        .warning-box {
            background: rgba(245,158,11,.07);
            border: 1px solid rgba(245,158,11,.2);
            border-radius: 14px;
            padding: 14px 16px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 22px;
            font-size: 13px;
            color: var(--text-primary);
            line-height: 1.6;
        }

        .warning-box i {
            color: #fbbf24;
            font-size: 16px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* Actions */
        .confirm-actions {
            display: flex;
            gap: 12px;
        }

        .confirm-actions .btn {
            flex: 1;
            justify-content: center;
        }
    </style>
</head>
<body>
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">

    <!-- SIDEBAR -->
 <?php include __DIR__ . '/../assets/includes/sidebar.php'; ?>

    <!-- MAIN -->
    <main class="main">
        <div class="topbar">
            <div>
                <div class="topbar-title">Supprimer une offre</div>
                <div class="topbar-sub">Confirmation requise avant suppression</div>
            </div>
            <div class="topbar-actions">
                <a href="<?= $BASE_URL ?>/controller/OffreController.php"
                   class="btn btn-outline btn-sm">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>
        </div>

        <div class="content">

            <div class="page-breadcrumb" style="margin-bottom:24px;">
                <i class="bi bi-house"></i>
                <span>Admin</span>
                <i class="bi bi-chevron-right" style="font-size:10px"></i>
                <a href="<?= $BASE_URL ?>/controller/OffreController.php"
                   style="color:inherit;text-decoration:none;">Offres</a>
                <i class="bi bi-chevron-right" style="font-size:10px"></i>
                <span>Supprimer</span>
            </div>

            <div class="confirm-wrap">
                <div class="danger-card">

                    <!-- Head -->
                    <div class="danger-card-head">
                        <div class="danger-icon-wrap">
                            <i class="bi bi-trash3"></i>
                        </div>
                        <div>
                            <div class="danger-card-title">Confirmer la suppression</div>
                            <div class="danger-card-sub">Cette action est irréversible</div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="danger-card-body">

                        <?php
                        $type    = strtolower($offre['type_offre'] ?? '');
                        $statut  = strtolower($offre['statut']     ?? '');
                        $icons   = ['auto'=>'bi-car-front','sante'=>'bi-heart-pulse','habitation'=>'bi-house-door','vie'=>'bi-shield-check'];
                        $icon    = $icons[$type] ?? 'bi-tags';
                        ?>

                        <!-- Aperçu offre -->
                        <div class="offre-preview">
                            <div class="offre-preview-top">
                                <div class="offre-preview-icon <?= $type ?>">
                                    <i class="bi <?= $icon ?>"></i>
                                </div>
                                <div>
                                    <div class="offre-preview-name">
                                        <?= htmlspecialchars($offre['nom_offre'] ?? '—') ?>
                                    </div>
                                    <div class="offre-preview-type">
                                        Assurance <?= ucfirst($type) ?>
                                        &nbsp;·&nbsp; ID #<?= (int)($offre['id_offre'] ?? 0) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="offre-info-grid">
                                <div class="offre-info-item">
                                    <div class="offre-info-label">Prix mensuel</div>
                                    <div class="offre-info-value">
                                        <?= number_format((float)($offre['prix_mensuel']??0),3) ?> TND
                                    </div>
                                </div>
                                <div class="offre-info-item">
                                    <div class="offre-info-label">Prix annuel</div>
                                    <div class="offre-info-value">
                                        <?= number_format((float)($offre['prix_annuel']??0),3) ?> TND
                                    </div>
                                </div>
                                <div class="offre-info-item">
                                    <div class="offre-info-label">Statut</div>
                                    <div class="offre-info-value"><?= ucfirst($statut) ?></div>
                                </div>
                                <div class="offre-info-item">
                                    <div class="offre-info-label">Créée le</div>
                                    <div class="offre-info-value">
                                        <?= !empty($offre['date_creation'])
                                            ? date('d/m/Y', strtotime($offre['date_creation']))
                                            : '—' ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Warning -->
                        <div class="warning-box">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <div>
                                <strong style="color:#fbbf24;">Attention :</strong>
                                Si des paiements sont liés à cette offre, elle sera
                                <strong>archivée</strong> au lieu d'être supprimée
                                afin de conserver l'historique des transactions.
                            </div>
                        </div>

                        <!-- Boutons confirmation -->
                        <form method="post"
                              action="<?= $BASE_URL ?>/controller/OffreController.php?action=supprimer&id=<?= (int)($offre['id_offre']??0) ?>">
                            <div class="confirm-actions">
                                <a href="<?= $BASE_URL ?>/controller/OffreController.php"
                                   class="btn btn-outline">
                                    <i class="bi bi-x-lg"></i> Annuler
                                </a>
                                <button type="submit" class="btn btn-danger">
                                    <i class="bi bi-trash3"></i> Supprimer définitivement
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script src="<?= $BASE_URL ?>/view/BackOffice/assets/js/main.js"></script>
</body>
</html>
```
