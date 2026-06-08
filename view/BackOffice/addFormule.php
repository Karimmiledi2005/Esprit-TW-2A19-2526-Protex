<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
require_once __DIR__ . '/../../controller/CategorieController.php';
require_once __DIR__ . '/../../connexion.php';
if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
$base = defined('BASE_URL') ? BASE_URL : '';

$idCategorie = isset($_GET['id_categorie']) ? (int)$_GET['id_categorie'] : 0;
$errors = [];

if ($idCategorie <= 0) {
    header('Location: categories_back.php');
    exit();
}

$categorieC = new CategorieController();
$categorie = $categorieC->showCategorie($idCategorie);

if (!$categorie) {
    header('Location: categories_back.php');
    exit();
}

$db = config::getConnexion();

$garantiesCatalogue = [];
try {
    $stmtCatalogue = $db->prepare("
        SELECT id_garantie, nom_garantie, description_garantie, plafond_couvert_garantie
        FROM garantie
        WHERE id_categorie = :id_categorie
        ORDER BY nom_garantie ASC
    ");
    $stmtCatalogue->execute(['id_categorie' => $idCategorie]);
    $garantiesCatalogue = $stmtCatalogue->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $garantiesCatalogue = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom  = trim($_POST['nom_formule'] ?? '');
    $desc = trim($_POST['description_formule'] ?? '');
    $prixRaw = trim($_POST['prix_formule'] ?? '');
    $prix = is_numeric($prixRaw) ? (float)$prixRaw : -1;
    $franchiseRaw = trim($_POST['franchise_formule'] ?? '');
    $franchise = is_numeric($franchiseRaw) ? (float)$franchiseRaw : -1;
    $niveau = trim($_POST['niveau_formule'] ?? '');
    $cat  = isset($_POST['id_categorie']) ? (int)$_POST['id_categorie'] : 0;

    $garantiesChoisies = $_POST['garanties'] ?? [];
    $niveauxChoisis = $_POST['niveau_garantie'] ?? [];

    if ($nom === '') {
        $errors[] = 'Le nom de la formule est obligatoire.';
    } elseif (preg_match('/\d/', $nom)) {
        $errors[] = 'Le nom de la formule ne doit pas contenir de chiffres.';
    }

    if ($desc === '') {
        $errors[] = 'La description est obligatoire.';
    } elseif (preg_match('/\d/', $desc)) {
        $errors[] = 'La description ne doit pas contenir de chiffres.';
    }

    if ($prixRaw === '') {
        $errors[] = 'Le prix est obligatoire.';
    } elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $prixRaw)) {
        $errors[] = 'Le prix est invalide.';
    } elseif ($prix <= 0) {
        $errors[] = 'Le prix doit être supérieur à 0.';
    }

    if ($franchiseRaw === '') {
        $errors[] = 'La franchise est obligatoire.';
    } elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $franchiseRaw)) {
        $errors[] = 'La franchise est invalide.';
    } elseif ($franchise < 0) {
        $errors[] = 'La franchise doit être positive ou égale à 0.';
    }

    if ($niveau === '') {
        $errors[] = 'Le niveau de formule est obligatoire.';
    }

    if ($cat !== $idCategorie) {
        $errors[] = 'La catégorie est invalide.';
    }

    // Contrôle anti-doublon dans la même catégorie : nom, description ou prix déjà utilisé
    if (empty($errors)) {
        $checkDoublon = $db->prepare("
            SELECT nom_formule, description_formule, prix_formule
            FROM formule
            WHERE id_categorie = :id_categorie
              AND (LOWER(nom_formule) = LOWER(:nom_formule)
                   OR LOWER(description_formule) = LOWER(:description_formule)
                   OR prix_formule = :prix_formule)
            LIMIT 1
        ");
        $checkDoublon->execute([
            'id_categorie' => $cat,
            'nom_formule' => $nom,
            'description_formule' => $desc,
            'prix_formule' => $prix
        ]);
        $doublon = $checkDoublon->fetch(PDO::FETCH_ASSOC);

        if ($doublon) {
            if (mb_strtolower($doublon['nom_formule']) === mb_strtolower($nom)) {
                $errors[] = 'Une formule avec ce nom existe déjà dans cette catégorie.';
            }
            if (mb_strtolower($doublon['description_formule']) === mb_strtolower($desc)) {
                $errors[] = 'Une formule avec cette description existe déjà dans cette catégorie.';
            }
            if ((float)$doublon['prix_formule'] === (float)$prix) {
                $errors[] = 'Une formule avec ce prix existe déjà dans cette catégorie.';
            }
        }
    }

    if (empty($garantiesChoisies) || !is_array($garantiesChoisies)) {
        $errors[] = 'Choisis au moins une garantie.';
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $stmtFormule = $db->prepare("
                INSERT INTO formule (
                    nom_formule,
                    description_formule,
                    prix_formule,
                    franchise_formule,
                    niveau_formule,
                    id_categorie
                ) VALUES (
                    :nom_formule,
                    :description_formule,
                    :prix_formule,
                    :franchise_formule,
                    :niveau_formule,
                    :id_categorie
                )
            ");

            $stmtFormule->execute([
                'nom_formule' => $nom,
                'description_formule' => $desc,
                'prix_formule' => $prix,
                'franchise_formule' => $franchise,
                'niveau_formule' => $niveau,
                'id_categorie' => $cat
            ]);

            $idFormule = (int)$db->lastInsertId();

            $stmtCheckGarantie = $db->prepare("
                SELECT COUNT(*)
                FROM garantie
                WHERE id_garantie = :id_garantie
                  AND id_categorie = :id_categorie
            ");

            $stmtLinkGarantie = $db->prepare("
                INSERT INTO formule_garantie (
                    id_formule,
                    id_garantie,
                    niveau_couvert_garantie
                ) VALUES (
                    :id_formule,
                    :id_garantie,
                    :niveau_couvert_garantie
                )
            ");

            foreach ($garantiesChoisies as $idGarantieSource) {
                $idGarantieSource = (int)$idGarantieSource;
                $niveauGarantie = trim($niveauxChoisis[$idGarantieSource] ?? 'basique');

                if (!in_array($niveauGarantie, ['basique', 'option', 'non disponible'], true)) {
                    $niveauGarantie = 'basique';
                }

                $stmtCheckGarantie->execute([
                    'id_garantie' => $idGarantieSource,
                    'id_categorie' => $cat
                ]);

                if ((int)$stmtCheckGarantie->fetchColumn() > 0) {
                    $stmtLinkGarantie->execute([
                        'id_formule' => $idFormule,
                        'id_garantie' => $idGarantieSource,
                        'niveau_couvert_garantie' => $niveauGarantie
                    ]);
                }
            }

            $db->commit();

            header('Location: showCategorie.php?id=' . $cat);
            exit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('addFormule.php error: ' . $e->getMessage());
            $errors[] = 'Erreur lors de l\'ajout. Veuillez réessayer plus tard.';
        }
    }
}

$selectedGaranties = $_POST['garanties'] ?? [];
$niveauxPost = $_POST['niveau_garantie'] ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une formule</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= $base ?>/view/BackOffice/assets/css/layout.css">
    <style>
        :root {
            --show-bg: #06172b;
            --show-card: rgba(18, 34, 63, .92);
            --show-card-soft: rgba(255, 255, 255, .045);
            --show-border: rgba(255, 255, 255, .12);
            --show-border-strong: rgba(0, 194, 255, .28);
            --show-text: #ffffff;
            --show-muted: rgba(255, 255, 255, .62);
            --show-muted-2: rgba(255, 255, 255, .46);
            --show-accent: #00c2ff;
            --show-orange: #ff5b2e;
            --show-danger: #ff3b4f;
            --show-success: #21d07a;
            --show-warning: #ffd166;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #06101f;
            color: var(--show-text);
        }
        .show-page {
            min-height: 100vh;
            padding: 54px 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .show-backdrop {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 15% 20%, rgba(0, 194, 255, .26), transparent 34%),
                radial-gradient(circle at 85% 80%, rgba(255, 107, 26, .34), transparent 35%),
                linear-gradient(135deg, rgba(3, 10, 22, .96), rgba(6, 14, 29, .92));
            filter: blur(.1px);
        }
        .show-modal {
            width: min(960px, calc(100% - 24px));
            max-height: calc(100vh - 80px);
            position: relative;
            z-index: 2;
            background: linear-gradient(180deg, rgba(9, 26, 58, .98), rgba(5, 17, 39, .98));
            border: 1px solid rgba(0, 194, 255, .22);
            border-radius: 24px;
            box-shadow: 0 32px 90px rgba(0, 0, 0, .48);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .show-modal-header {
            padding: 34px 42px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
        }
        .show-title-wrap { display: flex; align-items: center; gap: 18px; }
        .show-icon {
            width: 62px;
            height: 62px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ff6b35, #ff3d22);
            color: #fff;
            font-size: 28px;
            box-shadow: 0 16px 32px rgba(255, 91, 46, .28);
            flex: 0 0 auto;
        }
        .show-title {
            margin: 0;
            font-size: clamp(24px, 3vw, 34px);
            line-height: 1.1;
            font-weight: 900;
            letter-spacing: -.7px;
        }
        .show-subtitle { margin-top: 6px; color: var(--show-muted); font-weight: 700; }
        .show-close {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, .12);
            color: #fff;
            background: rgba(255, 255, 255, .07);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 20px;
            transition: .18s ease;
        }
        .show-close:hover { transform: translateY(-2px); background: rgba(255, 255, 255, .12); }
        .show-modal-body {
            padding: 30px 42px;
            overflow: auto;
        }
        .show-section { margin-bottom: 28px; }
        .show-section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 0 18px;
            color: #fff;
            font-size: 20px;
            font-weight: 900;
        }
        .show-section-title i { color: var(--show-accent); }
        .show-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }
        .show-field {
            min-height: 98px;
            padding: 18px 18px;
            border: 1px solid var(--show-border);
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(255, 255, 255, .055), rgba(255, 255, 255, .032));
        }
        .show-field.full { grid-column: 1 / -1; }
        .show-label {
            display: block;
            margin-bottom: 10px;
            color: var(--show-muted-2);
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
        }
        .show-label .required { color: #ff6b35; }
        .show-control {
            width: 100%;
            height: 48px;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 14px;
            outline: none;
            background: rgba(255, 255, 255, .055);
            color: #fff;
            padding: 0 16px;
            font-weight: 800;
            font-size: 15px;
            transition: .18s ease;
        }
        textarea.show-control { height: 104px; padding: 14px 16px; resize: vertical; line-height: 1.55; }
        select.show-control option { background: #0b1932; color: #fff; }
        .show-control:focus {
            border-color: rgba(0, 194, 255, .7);
            box-shadow: 0 0 0 4px rgba(0, 194, 255, .12);
        }
        .show-control.input-invalid { border-color: rgba(255, 59, 79, .95) !important; }
        .show-control.input-valid { border-color: rgba(33, 208, 122, .95) !important; }
        .field-error { min-height: 18px; margin-top: 7px; color: #ff7d8a; font-size: 12px; font-weight: 700; }
        .show-alert {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 16px;
            background: rgba(255, 59, 79, .13);
            border: 1px solid rgba(255, 59, 79, .28);
            color: #ffd5da;
            font-weight: 700;
        }
        .garantie-checklist {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .garantie-item {
            min-height: 136px;
            border: 1px solid var(--show-border);
            border-radius: 18px;
            padding: 16px;
            background: linear-gradient(180deg, rgba(255,255,255,.055), rgba(255,255,255,.028));
        }
        .garantie-top {
            display: flex;
            gap: 12px;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        .garantie-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 900;
            color: #fff;
            line-height: 1.25;
            flex: 1 1 260px;
        }
        .garantie-checkbox {
            width: 19px;
            height: 19px;
            accent-color: var(--show-accent);
            flex: 0 0 auto;
        }
        .garantie-level { max-width: 190px; height: 42px; font-size: 13px; }
        .garantie-desc { color: var(--show-muted); margin: 10px 0 10px 29px; font-size: 13px; line-height: 1.45; }
        .garantie-meta {
            display: inline-flex;
            margin-left: 29px;
            padding: 7px 10px;
            border-radius: 999px;
            color: #bdf2ff;
            background: rgba(0, 194, 255, .11);
            border: 1px solid rgba(0, 194, 255, .2);
            font-size: 12px;
            font-weight: 900;
        }
        .show-footer {
            padding: 22px 42px 30px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
            border-top: 1px solid rgba(255, 255, 255, .08);
        }
        .show-btn {
            height: 48px;
            padding: 0 22px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, .12);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 900;
            color: #fff;
            cursor: pointer;
            font-size: 15px;
            line-height: 1;
            transition: .18s ease;
        }
        .show-btn:hover { transform: translateY(-2px); }
        .show-btn.back { background: rgba(255,255,255,.055); }
        .show-btn.save { background: linear-gradient(135deg, #00c2ff, #0ea5e9); border-color: rgba(0, 194, 255, .5); }
        .empty-garanties {
            padding: 16px;
            border-radius: 18px;
            border: 1px solid rgba(255, 209, 102, .25);
            background: rgba(255, 209, 102, .1);
            color: #ffe4a3;
            font-weight: 800;
        }
        @media (max-width: 760px) {
            .show-page { padding: 20px 10px; align-items: flex-start; }
            .show-modal { width: 100%; max-height: calc(100vh - 40px); border-radius: 20px; }
            .show-modal-header, .show-modal-body, .show-footer { padding-left: 20px; padding-right: 20px; }
            .show-grid, .garantie-checklist { grid-template-columns: 1fr; }
            .show-footer { flex-direction: column; align-items: stretch; }
            .show-btn { width: 100%; }
        }
    </style>
</head>
<body>
<div class="layout">
    <?php require_once __DIR__.'/assets/includes/sidebar.php'; ?>
    <main class="main">
<div class="show-page">
    <div class="show-backdrop"></div>

    <form method="POST" id="formuleForm" class="show-modal" novalidate>
        <div class="show-modal-header">
            <div class="show-title-wrap">
                <div class="show-icon"><i class="bi bi-layers"></i></div>
                <div>
                    <h1 class="show-title">Ajouter une formule</h1>
                    <div class="show-subtitle">Catégorie : <?= htmlspecialchars($categorie['nom_categorie']) ?></div>
                </div>
            </div>
            <a class="show-close" href="showCategorie.php?id=<?= (int)$idCategorie ?>" aria-label="Fermer"><i class="bi bi-x"></i></a>
        </div>

        <div class="show-modal-body">
            <input type="hidden" name="id_categorie" value="<?= (int)$idCategorie ?>">

            <?php if (!empty($errors)) { ?>
                <div class="show-alert">
                    <?php foreach ($errors as $error) { ?>
                        <div>• <?= htmlspecialchars($error) ?></div>
                    <?php } ?>
                </div>
            <?php } ?>

            <section class="show-section">
                <h2 class="show-section-title"><i class="bi bi-pencil-square"></i> Informations formule</h2>
                <div class="show-grid">
                    <div class="show-field">
                        <label class="show-label">Nom formule <span class="required">*</span></label>
                        <input type="text" id="nom_formule" class="show-control" name="nom_formule"
                               placeholder="Ex : Premium plus"
                               value="<?= htmlspecialchars($_POST['nom_formule'] ?? '') ?>">
                        <div id="error_nom_formule" class="field-error"></div>
                    </div>

                    <div class="show-field">
                        <label class="show-label">Niveau <span class="required">*</span></label>
                        <?php $niveauCourant = $_POST['niveau_formule'] ?? ''; ?>
                        <select class="show-control" id="niveau_formule" name="niveau_formule">
                            <option value="">-- Sélectionner un niveau --</option>
                            <option value="Essentiel" <?= $niveauCourant === 'Essentiel' ? 'selected' : '' ?>>Essentiel</option>
                            <option value="Intermédiaire" <?= $niveauCourant === 'Intermédiaire' ? 'selected' : '' ?>>Intermédiaire</option>
                            <option value="Premium" <?= $niveauCourant === 'Premium' ? 'selected' : '' ?>>Premium</option>
                        </select>
                        <div id="error_niveau_formule" class="field-error"></div>
                    </div>

                    <div class="show-field">
                        <label class="show-label">Prix mensuel <span class="required">*</span></label>
                        <input type="text" inputmode="decimal" id="prix_formule" class="show-control" name="prix_formule"
                               placeholder="Ex : 70"
                               value="<?= htmlspecialchars($_POST['prix_formule'] ?? '') ?>">
                        <div id="error_prix_formule" class="field-error"></div>
                    </div>

                    <div class="show-field">
                        <label class="show-label">Franchise <span class="required">*</span></label>
                        <input type="text" inputmode="decimal" id="franchise_formule" class="show-control" name="franchise_formule"
                               placeholder="Ex : 150"
                               value="<?= htmlspecialchars($_POST['franchise_formule'] ?? '') ?>">
                        <div id="error_franchise_formule" class="field-error"></div>
                    </div>

                    <div class="show-field full">
                        <label class="show-label">Description <span class="required">*</span></label>
                        <textarea class="show-control" id="description_formule" name="description_formule" placeholder="Saisir la description de la formule"><?= htmlspecialchars($_POST['description_formule'] ?? '') ?></textarea>
                        <div id="error_description_formule" class="field-error"></div>
                    </div>
                </div>
            </section>

            <section class="show-section">
                <h2 class="show-section-title"><i class="bi bi-shield-check"></i> Garanties de la catégorie <span class="required">*</span></h2>
                <div id="error_garanties" class="field-error"></div>

                <?php if (empty($garantiesCatalogue)) { ?>
                    <div class="empty-garanties">Aucune garantie catalogue n'est encore créée pour cette catégorie.</div>
                <?php } else { ?>
                    <div class="garantie-checklist">
                        <?php foreach ($garantiesCatalogue as $garantie): ?>
                            <?php
                                $idGarantie = (int)$garantie['id_garantie'];
                                $isChecked = in_array((string)$idGarantie, array_map('strval', $selectedGaranties), true);
                                $niveauSaved = $niveauxPost[$idGarantie] ?? 'basique';
                            ?>
                            <div class="garantie-item">
                                <div class="garantie-top">
                                    <label class="garantie-label">
                                        <input type="checkbox"
                                               class="garantie-checkbox"
                                               name="garanties[]"
                                               value="<?= $idGarantie ?>"
                                               <?= $isChecked ? 'checked' : '' ?>>
                                        <span><?= htmlspecialchars($garantie['nom_garantie']) ?></span>
                                    </label>

                                    <select class="show-control garantie-level"
                                            name="niveau_garantie[<?= $idGarantie ?>]">
                                        <option value="basique" <?= $niveauSaved === 'basique' ? 'selected' : '' ?>>basique</option>
                                        <option value="option" <?= $niveauSaved === 'option' ? 'selected' : '' ?>>option</option>
                                        <option value="non disponible" <?= $niveauSaved === 'non disponible' ? 'selected' : '' ?>>non disponible</option>
                                    </select>
                                </div>

                                <div class="garantie-desc"><?= htmlspecialchars($garantie['description_garantie']) ?></div>
                                <div class="garantie-meta">Plafond catalogue : <?= number_format((float)$garantie['plafond_couvert_garantie'], 2, '.', ' ') ?> DT</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php } ?>
            </section>
        </div>

        <div class="show-footer">
            <a href="showCategorie.php?id=<?= (int)$idCategorie ?>" class="show-btn back"><i class="bi bi-arrow-left"></i> Annuler</a>
            <button type="submit" class="show-btn save"><i class="bi bi-plus-circle"></i> Ajouter</button>
        </div>
    </form>
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
function validateNomFormule() {
    const input = document.getElementById('nom_formule');
    const error = document.getElementById('error_nom_formule');
    const value = input.value.trim();
    if (value === '') return setError(input, error, 'Nom obligatoire'), false;
    if (/\d/.test(value)) return setError(input, error, 'Les chiffres sont interdits'), false;
    return setSuccess(input, error), true;
}
function validateDescriptionFormule() {
    const input = document.getElementById('description_formule');
    const error = document.getElementById('error_description_formule');
    const value = input.value.trim();
    if (value === '') return setError(input, error, 'Description obligatoire'), false;
    if (/\d/.test(value)) return setError(input, error, 'Les chiffres sont interdits'), false;
    return setSuccess(input, error), true;
}
function validatePrixFormule() {
    const input = document.getElementById('prix_formule');
    const error = document.getElementById('error_prix_formule');
    const value = input.value.trim();
    if (value === '') return setError(input, error, 'Prix obligatoire'), false;
    if (!/^\d+(\.\d{1,2})?$/.test(value)) return setError(input, error, 'Prix invalide'), false;
    if (parseFloat(value) <= 0) return setError(input, error, 'Le prix doit être supérieur à 0'), false;
    return setSuccess(input, error), true;
}
function validateFranchiseFormule() {
    const input = document.getElementById('franchise_formule');
    const error = document.getElementById('error_franchise_formule');
    const value = input.value.trim();
    if (value === '') return setError(input, error, 'Franchise obligatoire'), false;
    if (!/^\d+(\.\d{1,2})?$/.test(value)) return setError(input, error, 'Franchise invalide'), false;
    if (parseFloat(value) < 0) return setError(input, error, 'La franchise doit être positive ou égale à 0'), false;
    return setSuccess(input, error), true;
}
function validateNiveauFormule() {
    const input = document.getElementById('niveau_formule');
    const error = document.getElementById('error_niveau_formule');
    if (input.value.trim() === '') return setError(input, error, 'Niveau obligatoire'), false;
    return setSuccess(input, error), true;
}
function validateGarantiesSelection() {
    const error = document.getElementById('error_garanties');
    const checked = document.querySelectorAll('.garantie-checkbox:checked').length;
    if (checked === 0) {
        error.textContent = 'Choisis au moins une garantie';
        return false;
    }
    error.textContent = '';
    return true;
}
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formuleForm');
    const nom = document.getElementById('nom_formule');
    const desc = document.getElementById('description_formule');
    const prix = document.getElementById('prix_formule');
    const franchise = document.getElementById('franchise_formule');
    const niveau = document.getElementById('niveau_formule');

    nom.addEventListener('keypress', blockNumbersForText);
    desc.addEventListener('keypress', blockNumbersForText);
    nom.addEventListener('paste', blockPasteNumbers);
    desc.addEventListener('paste', blockPasteNumbers);

    nom.addEventListener('input', validateNomFormule);
    desc.addEventListener('input', validateDescriptionFormule);
    prix.addEventListener('input', validatePrixFormule);
    franchise.addEventListener('input', validateFranchiseFormule);
    niveau.addEventListener('change', validateNiveauFormule);

    document.querySelectorAll('.garantie-checkbox').forEach(cb => {
        cb.addEventListener('change', validateGarantiesSelection);
    });

    form.addEventListener('submit', function(e) {
        const ok = validateNomFormule() &&
                   validateDescriptionFormule() &&
                   validatePrixFormule() &&
                   validateFranchiseFormule() &&
                   validateNiveauFormule() &&
                   validateGarantiesSelection();
        if (!ok) e.preventDefault();
    });
});
</script>
    </main>
</div>
</body>
</html>
