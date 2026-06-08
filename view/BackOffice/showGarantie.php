<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
require_once __DIR__ . '/../../controller/GarantieController.php';
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
$base = defined('BASE_URL') ? BASE_URL : '';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    error_log('showGarantie.php called without id');
    header('Location: garanties_back.php?error=missing_id');
    exit;
}

$controller = new GarantieController();
$garantie = $controller->showGarantie($id);

if (!$garantie) {
    error_log('showGarantie.php: garantie not found id=' . $id);
    header('Location: garanties_back.php?error=not_found');
    exit;
}

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$nomGarantie = $garantie['nom_garantie'] ?? 'Garantie';
$description = trim((string)($garantie['description_garantie'] ?? ''));
$plafond = isset($garantie['plafond_couvert_garantie'])
    ? number_format((float)$garantie['plafond_couvert_garantie'], 2, '.', ' ') . ' DT'
    : '—';
$categorie = $garantie['nom_categorie'] ?? '—';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail garantie — Protex Admin</title>
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
            line-height: 1.45;
            word-break: break-word;
        }

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

            .show-grid {
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

    <div class="show-modal" role="dialog" aria-modal="true" aria-labelledby="showGarantieTitle">
        <div class="show-modal-header">
            <div class="show-title-wrap">
                <div class="show-icon"><i class="bi bi-shield-check"></i></div>
                <div>
                    <h1 class="show-title" id="showGarantieTitle">Détail de la garantie</h1>
                    <div class="show-subtitle">#<?= (int)$garantie['id_garantie'] ?> · <?= h($nomGarantie) ?></div>
                </div>
            </div>

            <a class="show-close" href="garanties_back.php" title="Fermer"><i class="bi bi-x"></i></a>
        </div>

        <div class="show-modal-body">
            <div class="show-section">
                <div class="show-section-title"><i class="bi bi-shield-check"></i> Informations garantie</div>

                <div class="show-grid">
                    <div class="show-field">
                        <span class="show-label">ID garantie</span>
                        <div class="show-value">#<?= (int)$garantie['id_garantie'] ?></div>
                    </div>

                    <div class="show-field">
                        <span class="show-label">Nom</span>
                        <div class="show-value"><?= h($nomGarantie) ?></div>
                    </div>

                    <div class="show-field full">
                        <span class="show-label">Description</span>
                        <div class="show-value"><?= $description !== '' ? nl2br(h($description)) : '—' ?></div>
                    </div>

                    <div class="show-field">
                        <span class="show-label">Plafond de couverture</span>
                        <div class="show-value"><?= h($plafond) ?></div>
                    </div>

                    <div class="show-field">
                        <span class="show-label">Catégorie</span>
                        <div class="show-value"><?= h($categorie) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="show-footer">
            <a href="garanties_back.php" class="show-btn btn-return"><i class="bi bi-arrow-left"></i> Retour</a>
            <a href="updateGarantie.php?id=<?= (int)$garantie['id_garantie'] ?>" class="show-btn btn-edit"><i class="bi bi-pencil"></i> Modifier</a>
        </div>
    </div>
</div>
    </main>
</div>
</body>
</html>
