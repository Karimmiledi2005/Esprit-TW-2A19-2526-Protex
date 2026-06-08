<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
require_once __DIR__ . '/../../controller/FormuleController.php';
require_once __DIR__ . '/../../connexion.php';
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
$base = defined('BASE_URL') ? BASE_URL : '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID formule manquant.");
}

$id = (int)$_GET['id'];

$formuleC = new FormuleController();
$formule = $formuleC->showFormule($id);

if (!$formule) {
    die("Formule introuvable.");
}

$db = config::getConnexion();

$sql = "
    SELECT
        g.*,
        fg.niveau_couvert_garantie AS niveau_couvert_garantie
    FROM formule_garantie fg
    INNER JOIN garantie g ON g.id_garantie = fg.id_garantie
    WHERE fg.id_formule = :id_formule
    ORDER BY g.nom_garantie ASC
";
$stmt = $db->prepare($sql);
$stmt->execute(['id_formule' => $id]);
$garanties = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function niveauClass(?string $niveau): string
{
    $niveau = strtolower(trim((string)$niveau));
    return match ($niveau) {
        'basique' => 'garantie-basic',
        'option' => 'garantie-option',
        default => 'garantie-disabled',
    };
}

function niveauIcon(?string $niveau): string
{
    $niveau = strtolower(trim((string)$niveau));
    return match ($niveau) {
        'basique' => 'bi-check2',
        'option' => 'bi-plus-lg',
        default => 'bi-x-lg',
    };
}

function niveauLabel(?string $niveau): string
{
    $niveau = strtolower(trim((string)$niveau));
    return match ($niveau) {
        'basique' => 'Basique',
        'option' => 'Option',
        default => 'Non disponible',
    };
}

$retourUrl = !empty($formule['id_categorie']) ? 'showCategorie.php?id=' . (int)$formule['id_categorie'] : 'formules_back.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail formule — Protex Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/admin-users.css">
    <link rel="stylesheet" href="assets/css/validation.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/layout.css">
    <style>
        body {
            min-height: 100vh;
            overflow-x: hidden;
        }

        .show-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 42px 18px;
            position: relative;
        }

        .show-backdrop {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 15% 10%, rgba(0, 198, 255, 0.18), transparent 30%),
                radial-gradient(circle at 90% 80%, rgba(255, 107, 26, 0.22), transparent 34%),
                rgba(2, 8, 23, 0.72);
            backdrop-filter: blur(13px);
            z-index: 1;
        }

        .show-modal {
            position: relative;
            z-index: 2;
            width: min(920px, 96vw);
            max-height: 92vh;
            overflow: auto;
            background: linear-gradient(180deg, rgba(8, 22, 52, 0.98), rgba(5, 17, 42, 0.98));
            border: 1px solid rgba(80, 132, 255, 0.24);
            border-radius: 24px;
            box-shadow: 0 32px 90px rgba(0, 0, 0, 0.42);
            padding: 0;
            color: #fff;
        }

        .show-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            padding: 28px 32px 22px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .show-title-wrap {
            display: flex;
            gap: 14px;
            align-items: center;
        }

        .show-icon {
            width: 48px;
            height: 48px;
            border-radius: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ff6b1a, #ff3d3d);
            box-shadow: 0 16px 34px rgba(255, 107, 26, 0.25);
            font-size: 24px;
            flex: 0 0 auto;
        }

        .show-title {
            margin: 0;
            font-size: 26px;
            line-height: 1.15;
            font-weight: 800;
            color: #fff;
        }

        .show-subtitle {
            margin-top: 6px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.65);
            font-weight: 700;
        }

        .show-close {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.06);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 18px;
            transition: .2s ease;
            flex: 0 0 auto;
        }

        .show-close:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-1px);
        }

        .show-modal-body {
            padding: 26px 32px 8px;
        }

        .show-section {
            margin-bottom: 24px;
        }

        .show-section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 17px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 16px;
        }

        .show-section-title i {
            color: #00c6ff;
            font-size: 20px;
        }

        .show-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .show-field {
            min-height: 72px;
            background: rgba(255, 255, 255, 0.055);
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 16px;
            padding: 14px 16px;
        }

        .show-field.full {
            grid-column: 1 / -1;
        }

        .show-label {
            display: block;
            margin-bottom: 8px;
            font-size: 11px;
            letter-spacing: .5px;
            color: rgba(255, 255, 255, 0.56);
            text-transform: uppercase;
            font-weight: 800;
        }

        .show-value {
            color: #fff;
            font-size: 15px;
            font-weight: 750;
            line-height: 1.35;
            word-break: break-word;
        }

        .show-empty {
            background: rgba(255, 255, 255, 0.045);
            border: 1px dashed rgba(255, 255, 255, 0.14);
            border-radius: 16px;
            padding: 18px;
            color: rgba(255, 255, 255, 0.62);
            font-size: 13px;
        }

        .garantie-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .garantie-item {
            min-height: 58px;
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.055);
            border: 1px solid rgba(0, 198, 255, 0.18);
            color: #fff;
            font-weight: 800;
            line-height: 1.25;
        }

        .garantie-item i {
            width: 26px;
            height: 26px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            font-size: 14px;
        }

        .garantie-main {
            flex: 1;
            min-width: 0;
        }

        .garantie-name {
            color: #fff;
            font-size: 14px;
            font-weight: 850;
            word-break: break-word;
        }

        .garantie-level {
            margin-top: 4px;
            font-size: 11px;
            font-weight: 850;
            letter-spacing: .2px;
        }

        .garantie-basic i { color: #2ed573; background: rgba(46, 213, 115, 0.12); border: 1px solid rgba(46, 213, 115, 0.25); }
        .garantie-basic .garantie-level { color: #2ed573; }
        .garantie-option i { color: #ffb020; background: rgba(255, 176, 32, 0.12); border: 1px solid rgba(255, 176, 32, 0.25); }
        .garantie-option .garantie-level { color: #ffb020; }
        .garantie-disabled { opacity: .72; }
        .garantie-disabled i { color: #cbd5e1; background: rgba(148, 163, 184, 0.10); border: 1px solid rgba(148, 163, 184, 0.20); }
        .garantie-disabled .garantie-level { color: #cbd5e1; }

        .show-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 22px 32px 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            flex-wrap: wrap;
        }

        .show-btn {
            height: 44px;
            padding: 0 18px;
            border-radius: 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            line-height: 1;
            text-decoration: none;
            font-weight: 800;
            font-size: 14px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            transition: .2s ease;
            cursor: pointer;
            white-space: nowrap;
        }

        .show-btn:hover {
            transform: translateY(-1px);
        }

        .btn-return {
            color: rgba(255, 255, 255, .78);
            background: rgba(255, 255, 255, 0.05);
        }

        .btn-edit {
            color: #fff;
            background: linear-gradient(135deg, #00c6ff, #0891b2);
            border-color: transparent;
        }

        @media (max-width: 720px) {
            .show-modal-header,
            .show-modal-body,
            .show-footer {
                padding-left: 20px;
                padding-right: 20px;
            }

            .show-grid,
            .garantie-list {
                grid-template-columns: 1fr;
            }

            .show-footer .show-btn {
                flex: 1 1 100%;
            }
        }
    </style>
</head>
<body>
<div class="layout">
    <?php require_once __DIR__.'/assets/includes/sidebar.php'; ?>
    <main class="main">
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="show-page">
    <div class="show-backdrop"></div>

    <div class="show-modal" role="dialog" aria-modal="true" aria-labelledby="showFormuleTitle">
        <div class="show-modal-header">
            <div class="show-title-wrap">
                <div class="show-icon"><i class="bi bi-layers"></i></div>
                <div>
                    <h1 class="show-title" id="showFormuleTitle">Détail de la formule</h1>
                    <div class="show-subtitle"><?= h($formule['nom_formule'] ?? '—') ?></div>
                </div>
            </div>

            <a class="show-close" href="<?= h($retourUrl) ?>" title="Fermer"><i class="bi bi-x"></i></a>
        </div>

        <div class="show-modal-body">
            <div class="show-section">
                <div class="show-section-title"><i class="bi bi-shield-check"></i> Informations formule</div>

                <div class="show-grid">
                    <div class="show-field">
                        <span class="show-label">Nom formule</span>
                        <div class="show-value"><?= h($formule['nom_formule'] ?? '—') ?></div>
                    </div>
                    <div class="show-field">
                        <span class="show-label">Niveau</span>
                        <div class="show-value"><?= h($formule['niveau_formule'] ?? '—') ?></div>
                    </div>
                    <div class="show-field">
                        <span class="show-label">Prix</span>
                        <div class="show-value"><?= number_format((float)($formule['prix_formule'] ?? 0), 2) ?> DT</div>
                    </div>
                    <div class="show-field">
                        <span class="show-label">Franchise</span>
                        <div class="show-value"><?= number_format((float)($formule['franchise_formule'] ?? 0), 2) ?> DT</div>
                    </div>
                    <div class="show-field full">
                        <span class="show-label">Description</span>
                        <div class="show-value"><?= h($formule['description_formule'] ?? '—') ?></div>
                    </div>
                </div>
            </div>

            <div class="show-section">
                <div class="show-section-title"><i class="bi bi-list-check"></i> Garanties de la formule</div>

                <?php if (empty($garanties)): ?>
                    <div class="show-empty">Aucune garantie associée à cette formule.</div>
                <?php else: ?>
                    <div class="garantie-list">
                        <?php foreach ($garanties as $g): ?>
                            <?php
                            $niveau = $g['niveau_couvert_garantie'] ?? '';
                            $class = niveauClass($niveau);
                            $icon = niveauIcon($niveau);
                            ?>
                            <div class="garantie-item <?= h($class) ?>">
                                <i class="bi <?= h($icon) ?>"></i>
                                <div class="garantie-main">
                                    <div class="garantie-name"><?= h($g['nom_garantie'] ?? 'Garantie') ?></div>
                                    <div class="garantie-level"><?= h(niveauLabel($niveau)) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="show-footer">
            <a href="<?= h($retourUrl) ?>" class="show-btn btn-return"><i class="bi bi-arrow-left"></i> Retour</a>
            <a href="updateFormule.php?id=<?= (int)$id ?>" class="show-btn btn-edit"><i class="bi bi-pencil"></i> Modifier</a>
        </div>
    </div>
</div>
    </main>
</div>
</body>
</html>
