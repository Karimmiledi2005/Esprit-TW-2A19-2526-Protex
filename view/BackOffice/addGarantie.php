<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
require_once __DIR__ . '/../../controller/GarantieController.php';
require_once __DIR__ . '/../../controller/CategorieController.php';
require_once __DIR__ . '/../../model/Garantie.php';
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
$base = defined('BASE_URL') ? BASE_URL : '';

$garantieC = new GarantieController();
$categorieC = new CategorieController();

$categories = $categorieC->listCategories();
if ($categories instanceof PDOStatement) {
    $categories = $categories->fetchAll(PDO::FETCH_ASSOC);
}
if (!is_array($categories)) {
    $categories = [];
}

$errors = [];

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom_garantie'] ?? '');
    $description = trim($_POST['description_garantie'] ?? '');
    $plafondRaw = trim($_POST['plafond_couvert_garantie'] ?? '');
    $plafond = is_numeric($plafondRaw) ? (float)$plafondRaw : -1;
    $idCategorie = isset($_POST['id_categorie']) ? (int)$_POST['id_categorie'] : 0;

    if ($nom === '') {
        $errors[] = 'Le nom de la garantie est obligatoire.';
    } elseif (preg_match('/\d/', $nom)) {
        $errors[] = 'Le nom de la garantie ne doit pas contenir de chiffres.';
    }

    if ($description === '') {
        $errors[] = 'La description est obligatoire.';
    } elseif (preg_match('/\d/', $description)) {
        $errors[] = 'La description ne doit pas contenir de chiffres.';
    }

    if ($plafondRaw === '') {
        $errors[] = 'Le plafond est obligatoire.';
    } elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $plafondRaw)) {
        $errors[] = 'Le plafond est invalide.';
    } elseif ($plafond <= 0) {
        $errors[] = 'Le plafond doit être supérieur à 0.';
    }

    if ($idCategorie <= 0) {
        $errors[] = 'La catégorie est obligatoire.';
    }

    if (empty($errors)) {
        if ($garantieC->garantieExists($nom, $idCategorie)) {
            $errors[] = 'Cette garantie existe déjà dans cette catégorie.';
        } else {
            try {
                $garantie = new Garantie(
                    $nom,
                    $description,
                    $plafond,
                    $idCategorie
                );

                $garantieC->addGarantie($garantie);

                header('Location: garanties_back.php');
                exit();
            } catch (Exception $e) {
                error_log('addGarantie.php error: ' . $e->getMessage());
                $errors[] = 'Erreur lors de l\'ajout. Veuillez réessayer plus tard.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter garantie — Protex Admin</title>
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

    <div class="show-modal" role="dialog" aria-modal="true" aria-labelledby="addGarantieTitle">
        <div class="show-modal-header">
            <div class="show-title-wrap">
                <div class="show-icon"><i class="bi bi-shield-plus"></i></div>
                <div>
                    <h1 class="show-title" id="addGarantieTitle">Ajouter une garantie</h1>
                    <div class="show-subtitle">Créer une nouvelle garantie et l’associer à une catégorie</div>
                </div>
            </div>

            <a class="show-close" href="garanties_back.php" title="Fermer"><i class="bi bi-x"></i></a>
        </div>

        <form method="POST" id="garantieForm" novalidate>
            <div class="show-modal-body">
                <?php if (!empty($errors)) { ?>
                    <div class="show-alert-error">
                        <i class="bi bi-exclamation-triangle"></i>
                        <?php foreach ($errors as $error) { ?>
                            <div>• <?= h($error) ?></div>
                        <?php } ?>
                    </div>
                <?php } ?>

                <div class="show-section">
                    <div class="show-section-title"><i class="bi bi-list-check"></i> Informations garantie</div>

                    <div class="show-grid">
                        <div class="show-field">
                            <span class="show-label">Nom garantie <span style="color:#ff9b9b;">*</span></span>
                            <input type="text" id="nom_garantie" name="nom_garantie" placeholder="Saisir le nom de la garantie" value="<?= h($_POST['nom_garantie'] ?? '') ?>">
                            <small id="error_nom_garantie" class="field-error"></small>
                        </div>

                        <div class="show-field">
                            <span class="show-label">Plafond de couverture <span style="color:#ff9b9b;">*</span></span>
                            <input type="text" id="plafond_couvert_garantie" name="plafond_couvert_garantie" placeholder="Ex: 5000 ou 5000.00" value="<?= h($_POST['plafond_couvert_garantie'] ?? '') ?>">
                            <small id="error_plafond_couvert_garantie" class="field-error"></small>
                        </div>

                        <div class="show-field full">
                            <span class="show-label">Catégorie <span style="color:#ff9b9b;">*</span></span>
                            <select id="id_categorie" name="id_categorie">
                                <option value="">— Sélectionner une catégorie —</option>
                                <?php foreach ($categories as $cat) { ?>
                                    <option value="<?= (int)$cat['id_categorie'] ?>" <?= ((int)$cat['id_categorie'] === (int)($_POST['id_categorie'] ?? 0)) ? 'selected' : '' ?>>
                                        <?= h($cat['nom_categorie'] ?? '') ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <small id="error_id_categorie" class="field-error"></small>
                        </div>

                        <div class="show-field full">
                            <span class="show-label">Description <span style="color:#ff9b9b;">*</span></span>
                            <textarea id="description_garantie" name="description_garantie" rows="4" placeholder="Saisir la description de la garantie"><?= h($_POST['description_garantie'] ?? '') ?></textarea>
                            <small id="error_description_garantie" class="field-error"></small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="show-footer">
                <a href="garanties_back.php" class="show-btn btn-return"><i class="bi bi-arrow-left"></i> Annuler</a>
                <button type="submit" class="show-btn btn-edit"><i class="bi bi-plus-circle"></i> Ajouter</button>
            </div>
        </form>
    </div>
</div>

<script>
function setError(input, errorElement, message) {
    input.classList.remove('input-valid');
    input.classList.add('input-invalid');
    errorElement.textContent = message;
}
function setSuccess(input, errorElement) {
    input.classList.remove('input-invalid');
    input.classList.add('input-valid');
    errorElement.textContent = '';
}
function blockNumbersForText(e) {
    if (/\d/.test(e.key)) e.preventDefault();
}
function blockPasteNumbers(e) {
    const paste = (e.clipboardData || window.clipboardData).getData('text');
    if (/\d/.test(paste)) e.preventDefault();
}
function validateNomGarantie() {
    const input = document.getElementById('nom_garantie');
    const error = document.getElementById('error_nom_garantie');
    const value = input.value.trim();
    if (value === '') return setError(input, error, 'Nom obligatoire'), false;
    if (/\d/.test(value)) return setError(input, error, 'Les chiffres sont interdits'), false;
    return setSuccess(input, error), true;
}
function validateDescriptionGarantie() {
    const input = document.getElementById('description_garantie');
    const error = document.getElementById('error_description_garantie');
    const value = input.value.trim();
    if (value === '') return setError(input, error, 'Description obligatoire'), false;
    if (/\d/.test(value)) return setError(input, error, 'Les chiffres sont interdits'), false;
    return setSuccess(input, error), true;
}
function validatePlafondGarantie() {
    const input = document.getElementById('plafond_couvert_garantie');
    const error = document.getElementById('error_plafond_couvert_garantie');
    const value = input.value.trim();
    if (value === '') return setError(input, error, 'Plafond obligatoire'), false;
    if (!/^\d+(\.\d{1,2})?$/.test(value)) return setError(input, error, 'Plafond invalide'), false;
    if (parseFloat(value) <= 0) return setError(input, error, 'Le plafond doit être supérieur à 0'), false;
    return setSuccess(input, error), true;
}
function validateCategorieGarantie() {
    const input = document.getElementById('id_categorie');
    const error = document.getElementById('error_id_categorie');
    if (input.value.trim() === '') return setError(input, error, 'Catégorie obligatoire'), false;
    return setSuccess(input, error), true;
}
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('garantieForm');
    const nom = document.getElementById('nom_garantie');
    const description = document.getElementById('description_garantie');
    const plafond = document.getElementById('plafond_couvert_garantie');
    const categorie = document.getElementById('id_categorie');

    nom.addEventListener('keypress', blockNumbersForText);
    description.addEventListener('keypress', blockNumbersForText);
    nom.addEventListener('paste', blockPasteNumbers);
    description.addEventListener('paste', blockPasteNumbers);

    nom.addEventListener('input', validateNomGarantie);
    description.addEventListener('input', validateDescriptionGarantie);
    plafond.addEventListener('input', validatePlafondGarantie);
    categorie.addEventListener('change', validateCategorieGarantie);

    form.addEventListener('submit', function(e) {
        const nomOk = validateNomGarantie();
        const descriptionOk = validateDescriptionGarantie();
        const plafondOk = validatePlafondGarantie();
        const categorieOk = validateCategorieGarantie();

        if (!(nomOk && descriptionOk && plafondOk && categorieOk)) {
            e.preventDefault();
        }
    });
});
</script>
    </main>
</div>
</body>
</html>
