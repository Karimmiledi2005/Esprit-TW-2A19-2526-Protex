<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/SessionGuard.php';

$ids_str = $_GET['offres'] ?? '';
$ids_array = explode(',', $ids_str);
$ids_clean = [];
foreach ($ids_array as $id) {
    if (is_numeric($id)) {
        $ids_clean[] = (int)$id;
    }
}
$ids_clean = array_slice($ids_clean, 0, 3); // Max 3

if (empty($ids_clean)) {
    header("Location: offres.php");
    exit;
}

$db = config::getConnexion();
$placeholders = implode(',', array_fill(0, count($ids_clean), '?'));
$stmt = $db->prepare("SELECT * FROM offre WHERE id_offre IN ($placeholders) AND statut='active'");
$stmt->execute($ids_clean);
$offres = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($offres)) {
    header("Location: offres.php");
    exit;
}

// Find cheapest monthly price
$cheapest_id = null;
$min_price = PHP_FLOAT_MAX;
foreach ($offres as $o) {
    $p = (float)$o['prix_mensuel'];
    if ($p < $min_price) {
        $min_price = $p;
        $cheapest_id = $o['id_offre'];
    }
}

// Map features
$all_features = [];
foreach ($offres as $o) {
    if (!empty($o['couverture'])) {
        $feats = array_map('trim', explode(',', $o['couverture']));
        foreach ($feats as $f) {
            $all_features[$f] = true;
        }
    }
}
$all_features = array_keys($all_features);

$typeIcons  = [
    'auto'       => 'bi-car-front',
    'sante'      => 'bi-heart-pulse',
    'habitation' => 'bi-house-check',
    'vie'        => 'bi-shield-heart',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Comparateur — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">

    <style>
        .compare-page-header {
            text-align: center;
            margin-top: 40px;
            margin-bottom: 40px;
        }
        .compare-page-title {
            font-family: 'Sora', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: #15233C;
            margin-bottom: 12px;
        }
        .compare-page-sub {
            color: rgba(21,35,60,0.6);
            font-size: 16px;
        }

        .compare-grid {
            display: grid;
            grid-template-columns: repeat(<?= count($offres) ?>, 1fr);
            gap: 24px;
            margin-bottom: 40px;
        }

        .compare-card {
            background: #fff;
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: 22px;
            padding: 32px 24px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            position: relative;
        }

        .best-price-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: #10b981;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
        }

        .c-icon {
            font-size: 32px;
            color: #FF6B1A;
            margin-bottom: 16px;
        }

        .c-name {
            font-family: 'Sora', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: #15233C;
            margin-bottom: 8px;
        }

        .c-price {
            font-size: 36px;
            font-weight: 900;
            color: #15233C;
            margin-bottom: 4px;
        }
        .c-price-note {
            font-size: 13px;
            color: rgba(21,35,60,0.5);
            margin-bottom: 24px;
        }

        .c-row {
            padding: 12px 0;
            border-top: 1px dashed rgba(26,58,122,0.1);
            font-size: 14px;
            color: #15233C;
            display: flex;
            justify-content: space-between;
        }
        .c-row-label {
            color: rgba(21,35,60,0.5);
            font-size: 13px;
        }

        .c-features {
            margin-top: 24px;
            text-align: left;
        }
        .c-feature-item {
            font-size: 13px;
            padding: 8px 0;
            border-bottom: 1px solid rgba(26,58,122,0.05);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .c-feature-item i {
            color: #10b981;
        }
        .c-feature-item.missing i {
            color: #ef4444;
        }
        .c-feature-item.missing {
            color: rgba(21,35,60,0.4);
            text-decoration: line-through;
        }

        .c-action {
            margin-top: 32px;
        }
        
        @media (max-width: 900px) {
            .compare-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="theme-light">
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">
    <?php include __DIR__ . '/assets/includes/navbar.php'; ?>

    <main class="main" style="min-height: 100vh;">
        <div class="container">
            
            <div class="compare-page-header">
                <a href="offres.php" class="btn btn-outline btn-sm" style="margin-bottom: 20px;"><i class="bi bi-arrow-left"></i> Retour aux offres</a>
                <h1 class="compare-page-title">Comparatif de formules</h1>
                <p class="compare-page-sub">Analysez et choisissez la formule la plus adaptée à vos besoins.</p>
            </div>

            <div class="compare-grid">
                <?php foreach ($offres as $o):
                    $type = strtolower($o['type_offre'] ?? '');
                    $icon = $typeIcons[$type] ?? 'bi-tags';
                    $feats = array_map('trim', explode(',', $o['couverture'] ?? ''));
                ?>
                <div class="compare-card">
                    <?php if ($o['id_offre'] === $cheapest_id): ?>
                        <div class="best-price-badge"><i class="bi bi-star-fill"></i> Meilleur Prix</div>
                    <?php endif; ?>

                    <div class="c-icon"><i class="bi <?= $icon ?>"></i></div>
                    <div class="c-name"><?= htmlspecialchars($o['nom_offre']) ?></div>
                    <div class="c-price"><?= number_format((float)$o['prix_mensuel'], 0) ?> <span style="font-size:16px;">TND</span></div>
                    <div class="c-price-note">par mois</div>

                    <div class="c-row">
                        <span class="c-row-label">Prix annuel</span>
                        <strong><?= number_format((float)$o['prix_annuel'], 0) ?> TND</strong>
                    </div>
                    <div class="c-row">
                        <span class="c-row-label">Plafond</span>
                        <strong><?= !empty($o['plafond']) ? number_format((float)$o['plafond'],0,'.',' ') . ' TND' : '—' ?></strong>
                    </div>
                    <div class="c-row">
                        <span class="c-row-label">Engagement min.</span>
                        <strong><?= (int)($o['duree_min'] ?? 1) ?> mois</strong>
                    </div>

                    <div class="c-features">
                        <strong style="display:block; margin-bottom:12px; font-size:12px; text-transform:uppercase; color:rgba(21,35,60,0.5);">Garanties incluses</strong>
                        <?php foreach ($all_features as $f): 
                            $has = in_array($f, $feats);
                        ?>
                        <div class="c-feature-item <?= $has ? '' : 'missing' ?>">
                            <i class="bi <?= $has ? 'bi-check-circle-fill' : 'bi-x-circle' ?>"></i>
                            <?= htmlspecialchars($f) ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="c-action">
                        <a href="wizard_souscription.php?id_offre=<?= $o['id_offre'] ?>" class="btn btn-primary" style="width:100%; justify-content:center;">Souscrire</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </main>
</div>
    <script src="assets/js/main.js"></script>
</body>
</html>
