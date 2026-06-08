<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
require_once __DIR__ . '/../../controller/CategorieController.php';
require_once __DIR__ . '/../../model/Categorie.php';
require_once __DIR__ . '/../../connexion.php';
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
$base = defined('BASE_URL') ? BASE_URL : '';
$errors = [];
$oldNom = '';
$oldDescription = '';

function normalizeText($value) {
    return trim(preg_replace('/\s+/', ' ', $value));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = normalizeText($_POST["nom_categorie"] ?? "");
    $description = normalizeText($_POST["description_categorie"] ?? "");

    $oldNom = $nom;
    $oldDescription = $description;

    // ===== VALIDATION SERVEUR =====
    if ($nom === '') {
        $errors[] = "Le nom de la catégorie est obligatoire.";
    } elseif (mb_strlen($nom) < 3) {
        $errors[] = "Le nom doit contenir au moins 3 caractères.";
    } elseif (mb_strlen($nom) > 100) {
        $errors[] = "Le nom ne doit pas dépasser 100 caractères.";
    } elseif (!preg_match('/^[A-Za-zÀ-ÿ\s\-]+$/u', $nom)) {
        $errors[] = "Le nom doit contenir uniquement des lettres, espaces ou tirets.";
    }

    if ($description === '') {
        $errors[] = "La description est obligatoire.";
    } elseif (mb_strlen($description) < 10) {
        $errors[] = "La description doit contenir au moins 10 caractères.";
    } elseif (mb_strlen($description) > 500) {
        $errors[] = "La description ne doit pas dépasser 500 caractères.";
    } elseif (preg_match('/\d/', $description)) {
        $errors[] = "La description ne doit pas contenir de chiffres.";
    }

    // ===== ANTI-DOUBLON =====
    // On bloque une catégorie ayant le même nom OU la même description.
    if (empty($errors)) {
        try {
            $db = config::getConnexion();
            $check = $db->prepare("
                SELECT COUNT(*)
                FROM categorie
                WHERE LOWER(TRIM(nom_categorie)) = LOWER(TRIM(:nom))
                   OR LOWER(TRIM(description_categorie)) = LOWER(TRIM(:description))
            ");
            $check->execute([
                'nom' => $nom,
                'description' => $description
            ]);

            if ((int)$check->fetchColumn() > 0) {
                $errors[] = "Cette catégorie existe déjà avec le même nom ou la même description.";
            }
        } catch (Exception $e) {
            error_log('addCategorie.php duplicate check error: ' . $e->getMessage());
            $errors[] = "Erreur lors de la vérification. Veuillez réessayer plus tard.";
        }
    }

    if (empty($errors)) {
        $categorie = new Categorie($nom, $description);
        $categorieC = new CategorieController();
        $categorieC->addCategorie($categorie);

        header("Location: categories_back.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter catégorie — Protex Admin</title>
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
        .show-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 18px;
            position: relative;
            overflow: hidden;
            background: #020817;
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
            width: min(820px, 96vw);
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

        .show-field input,
        .show-field select,
        .show-field textarea {
            width: 100%;
            min-height: 44px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.10);
            background: rgba(255, 255, 255, 0.045);
            color: #fff;
            padding: 12px 14px;
            font-weight: 800;
            font-size: 14px;
            outline: none;
            box-sizing: border-box;
            margin-top: 8px;
        }

        .show-field textarea {
            min-height: 112px;
            resize: vertical;
            line-height: 1.45;
        }

        .show-field select option {
            color: #0f172a;
        }

        .show-field input:focus,
        .show-field select:focus,
        .show-field textarea:focus {
            border-color: rgba(0, 198, 255, 0.55);
            box-shadow: 0 0 0 3px rgba(0, 198, 255, 0.10);
        }

        .field-error,
        .error-message {
            display: block;
            min-height: 14px;
            color: #ff9b9b;
            font-size: 12px;
            font-weight: 700;
            margin-top: 7px;
        }

        .input-invalid {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12) !important;
        }

        .input-valid {
            border-color: rgba(46, 213, 115, 0.55) !important;
            box-shadow: 0 0 0 3px rgba(46, 213, 115, 0.10) !important;
        }

        .show-alert-error {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 16px;
            background: rgba(239, 68, 68, 0.13);
            border: 1px solid rgba(239, 68, 68, 0.34);
            color: #ffd4d4;
            font-weight: 800;
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

        .btn-edit,
        .btn-valid {
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

    <div class="show-modal" role="dialog" aria-modal="true" aria-labelledby="addCategorieTitle">
        <div class="show-modal-header">
            <div class="show-title-wrap">
                <div class="show-icon"><i class="bi bi-grid-3x3-gap"></i></div>
                <div>
                    <h1 class="show-title" id="addCategorieTitle">Ajouter une catégorie</h1>
                    <div class="show-subtitle">Créer une nouvelle catégorie d’assurance</div>
                </div>
            </div>

            <a class="show-close" href="categories_back.php" title="Fermer"><i class="bi bi-x"></i></a>
        </div>

        <form method="POST" id="categorieForm" novalidate>
            <div class="show-modal-body">
                <?php if (!empty($errors)) { ?>
                    <div class="show-alert-error">
                        <i class="bi bi-exclamation-triangle"></i>
                        <?php foreach ($errors as $error) { ?>
                            <div>• <?= htmlspecialchars($error) ?></div>
                        <?php } ?>
                    </div>
                <?php } ?>

                <div class="show-section">
                    <div class="show-section-title"><i class="bi bi-list-check"></i> Informations catégorie</div>

                    <div class="show-grid">
                        <div class="show-field full">
                            <span class="show-label">Nom catégorie <span style="color:#ff9b9b;">*</span></span>
                            <input type="text"
                                   id="nom_categorie"
                                   name="nom_categorie"
                                   value="<?= htmlspecialchars($oldNom) ?>"
                                   placeholder="Saisir le nom de la catégorie">
                            <small id="error_nom_categorie" class="field-error"></small>
                        </div>

                        <div class="show-field full">
                            <span class="show-label">Description <span style="color:#ff9b9b;">*</span></span>
                            <textarea id="description_categorie"
                                      name="description_categorie"
                                      rows="4"
                                      placeholder="Saisir la description de la catégorie"><?= htmlspecialchars($oldDescription) ?></textarea>
                            <small id="error_description_categorie" class="field-error"></small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="show-footer">
                <a href="categories_back.php" class="show-btn btn-return"><i class="bi bi-arrow-left"></i> Annuler</a>
                <button type="submit" class="show-btn btn-edit"><i class="bi bi-plus-circle"></i> Ajouter</button>
</div>
    </main>
</div>
</body>
</html>
