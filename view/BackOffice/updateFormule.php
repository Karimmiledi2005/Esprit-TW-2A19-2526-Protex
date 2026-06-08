<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
SessionGuard::requireRoles(['superadmin', 'admin']);

require_once __DIR__ . '/../../controller/FormuleController.php';
require_once __DIR__ . '/../../controller/CategorieController.php';
require_once __DIR__ . '/../../model/Formule.php';
require_once __DIR__ . '/../../connexion.php';

$formuleC = new FormuleController();
$categorieC = new CategorieController();
$db = config::getConnexion();

$errors = [];

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID formule manquant.");
}

$id = (int)$_GET['id'];
$formuleData = $formuleC->showFormule($id);

if (!$formuleData) {
    die("Formule introuvable.");
}

$categories = $categorieC->listCategories();

$currentCategorie = (int)($formuleData['id_categorie'] ?? 0);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = trim($_POST["nom_formule"] ?? "");
    $description = trim($_POST["description_formule"] ?? "");
    $prixRaw = trim($_POST["prix_formule"] ?? "");
    $prix = is_numeric($prixRaw) ? (float)$prixRaw : -1;
    $franchiseRaw = trim($_POST["franchise_formule"] ?? "");
    $franchise = is_numeric($franchiseRaw) ? (float)$franchiseRaw : -1;
    $niveau = trim($_POST["niveau_formule"] ?? "");
    $currentCategorie = isset($_POST["id_categorie"]) ? (int)$_POST["id_categorie"] : $currentCategorie;

    $garantiesChoisies = $_POST['garanties'] ?? [];
    $niveauxChoisis = $_POST['niveau_garantie'] ?? [];

    if ($nom === '') {
        $errors[] = 'Le nom de la formule est obligatoire.';
    } elseif (preg_match('/\d/', $nom)) {
        $errors[] = 'Le nom de la formule ne doit pas contenir de chiffres.';
    }

    if ($description === '') {
        $errors[] = 'La description est obligatoire.';
    } elseif (preg_match('/\d/', $description)) {
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
    } elseif ($franchise <= 0) {
        $errors[] = 'La franchise doit être supérieure à 0.';
    }

    if ($niveau === '') {
        $errors[] = 'Le niveau est obligatoire.';
    }

    if ($currentCategorie <= 0) {
        $errors[] = 'La catégorie est invalide.';
    }

    // Contrôle anti-doublon dans la même catégorie, en excluant la formule actuelle
    if (empty($errors)) {
        $checkDoublon = $db->prepare("
            SELECT nom_formule, description_formule, prix_formule
            FROM formule
            WHERE id_categorie = :id_categorie
              AND id_formule != :id_formule
              AND (LOWER(nom_formule) = LOWER(:nom_formule)
                   OR LOWER(description_formule) = LOWER(:description_formule)
                   OR prix_formule = :prix_formule)
            LIMIT 1
        ");
        $checkDoublon->execute([
            'id_categorie' => $currentCategorie,
            'id_formule' => $id,
            'nom_formule' => $nom,
            'description_formule' => $description,
            'prix_formule' => $prix
        ]);
        $doublon = $checkDoublon->fetch(PDO::FETCH_ASSOC);

        if ($doublon) {
            if (mb_strtolower($doublon['nom_formule']) === mb_strtolower($nom)) {
                $errors[] = 'Une formule avec ce nom existe déjà dans cette catégorie.';
            }
            if (mb_strtolower($doublon['description_formule']) === mb_strtolower($description)) {
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

            $stmtUpdateFormule = $db->prepare("
                UPDATE formule
                SET nom_formule = :nom_formule,
                    description_formule = :description_formule,
                    prix_formule = :prix_formule,
                    franchise_formule = :franchise_formule,
                    niveau_formule = :niveau_formule,
                    id_categorie = :id_categorie
                WHERE id_formule = :id_formule
            " );

            $stmtUpdateFormule->execute([
                'nom_formule' => $nom,
                'description_formule' => $description,
                'prix_formule' => $prix,
                'franchise_formule' => $franchise,
                'niveau_formule' => $niveau,
                'id_categorie' => $currentCategorie,
                'id_formule' => $id
            ]);

            $deleteStmt = $db->prepare("DELETE FROM formule_garantie WHERE id_formule = :id_formule");
            $deleteStmt->execute(['id_formule' => $id]);

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
                    'id_categorie' => $currentCategorie
                ]);

                if ((int)$stmtCheckGarantie->fetchColumn() > 0) {
                    $stmtLinkGarantie->execute([
                        'id_formule' => $id,
                        'id_garantie' => $idGarantieSource,
                        'niveau_couvert_garantie' => $niveauGarantie
                    ]);
                }
            }

            $db->commit();

            header("Location: showCategorie.php?id=" . $currentCategorie);
            exit;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $errors[] = 'Erreur lors de la modification : ' . $e->getMessage();
        }
    }
}

$garantiesCatalogue = [];
try {
    $stmtCatalogue = $db->prepare("
        SELECT id_garantie, nom_garantie, description_garantie, plafond_couvert_garantie
        FROM garantie
        WHERE id_categorie = :id_categorie
        ORDER BY nom_garantie ASC
    ");
    $stmtCatalogue->execute(['id_categorie' => $currentCategorie]);
    $garantiesCatalogue = $stmtCatalogue->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $garantiesCatalogue = [];
}

$selectedGaranties = [];
$selectedLevels = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $currentNom = $_POST['nom_formule'] ?? '';
    $currentDescription = $_POST['description_formule'] ?? '';
    $currentPrix = $_POST['prix_formule'] ?? '0';
    $currentFranchise = $_POST['franchise_formule'] ?? '0';
    $currentNiveau = $_POST['niveau_formule'] ?? '';
    $selectedGaranties = array_map('strval', $_POST['garanties'] ?? []);
    $selectedLevels = $_POST['niveau_garantie'] ?? [];
} else {
    $currentNom = $formuleData['nom_formule'] ?? '';
    $currentDescription = $formuleData['description_formule'] ?? '';
    $currentPrix = $formuleData['prix_formule'] ?? '0';
    $currentFranchise = $formuleData['franchise_formule'] ?? '0';
    $currentNiveau = $formuleData['niveau_formule'] ?? '';

    $stmtLinked = $db->prepare("
        SELECT id_garantie, niveau_couvert_garantie
        FROM formule_garantie
        WHERE id_formule = :id_formule
    ");
    $stmtLinked->execute(['id_formule' => $id]);
    $linked = $stmtLinked->fetchAll(PDO::FETCH_ASSOC);

    foreach ($linked as $item) {
        $selectedGaranties[] = (string)$item['id_garantie'];
        $selectedLevels[(int)$item['id_garantie']] = $item['niveau_couvert_garantie'] ?? 'basique';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier formule — Protex Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/admin-users.css">
    <style>
        :root{--modal-navy:#071832;--modal-navy-2:#0b1f3e;--modal-card:#142442;--modal-border:rgba(148,163,184,.22);--modal-text:#f8fafc;--modal-muted:#aab6ca;--modal-accent:#00d4ff;--modal-orange:#ff9b45;--modal-danger:#ff4d57;--modal-success:#2ed573;}
        body{min-height:100vh;margin:0;font-family:var(--font-body,Inter,system-ui,sans-serif);background:radial-gradient(circle at 12% 18%,rgba(0,212,255,.12),transparent 32%),radial-gradient(circle at 88% 72%,rgba(255,107,26,.18),transparent 34%),#020817;color:var(--modal-text);overflow-x:hidden;}
        .modal-page-blur{position:fixed;inset:0;background:linear-gradient(135deg,rgba(2,8,23,.82),rgba(2,8,23,.68)),radial-gradient(circle at 15% 50%,rgba(0,212,255,.14),transparent 28%),radial-gradient(circle at 90% 55%,rgba(255,107,26,.2),transparent 28%);backdrop-filter:blur(9px);z-index:0;}
        .modal-shell{position:relative;z-index:1;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:34px 16px;}
        .admin-modal{width:min(980px,100%);max-height:calc(100vh - 68px);overflow:auto;background:linear-gradient(180deg,var(--modal-navy-2),var(--modal-navy));border:1px solid rgba(37,99,235,.42);box-shadow:0 28px 90px rgba(0,0,0,.55),inset 0 1px 0 rgba(255,255,255,.04);border-radius:24px;}
        .modal-top{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:34px 38px 28px;border-bottom:1px solid var(--modal-border);}
        .modal-title-wrap{display:flex;align-items:center;gap:18px;min-width:0;}
        .modal-icon{width:62px;height:62px;border-radius:18px;background:linear-gradient(135deg,#ff6b1a,#ff3b30);box-shadow:0 18px 35px rgba(255,107,26,.22);display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;flex:0 0 auto;}
        .modal-title{font-size:32px;font-weight:950;letter-spacing:-.8px;color:#fff;line-height:1.08;}
        .modal-sub{color:var(--modal-muted);font-weight:850;margin-top:6px;}
        .modal-close{width:46px;height:46px;border-radius:14px;border:1px solid rgba(148,163,184,.24);background:rgba(255,255,255,.06);color:#cbd5e1;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;font-size:21px;transition:.2s ease;flex:0 0 auto;}
        .modal-close:hover{background:rgba(255,255,255,.11);color:#fff;transform:translateY(-1px)}
        .modal-body{padding:34px 38px;}
        .section-title{display:flex;align-items:center;gap:12px;color:#fff;font-size:21px;font-weight:950;margin:0 0 20px;}
        .section-title i{color:var(--modal-accent);font-size:23px;}
        .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;}
        .form-card{border-radius:18px;background:rgba(255,255,255,.055);border:1px solid var(--modal-border);padding:18px 20px;min-width:0;}
        .form-card.full{grid-column:1/-1;}
        .form-card label{color:var(--modal-muted);font-weight:950;text-transform:uppercase;font-size:13px;letter-spacing:.2px;display:flex;align-items:center;gap:7px;margin:0 0 12px;}
        .required{color:#ff7a6c;font-weight:950;}
        .form-control,input,select,textarea{width:100%;box-sizing:border-box;border-radius:14px;border:1px solid rgba(148,163,184,.24);background:rgba(255,255,255,.065);color:#fff;padding:14px 15px;font-weight:800;outline:none;line-height:1.25;}
        textarea{resize:vertical;min-height:118px;}
        select option{color:#0f172a;background:#fff;}
        .form-control:focus,input:focus,select:focus,textarea:focus{border-color:rgba(0,212,255,.65);box-shadow:0 0 0 3px rgba(0,212,255,.12)}
        .field-error{display:block;color:#ff8f8f;font-size:12px;font-weight:800;margin-top:8px;min-height:15px;}
        .input-invalid{border-color:#ef4444!important;box-shadow:0 0 0 3px rgba(239,68,68,.10)!important;}
        .input-valid{border-color:#22c55e!important;}
        .alert-error{margin-bottom:22px;padding:15px 17px;border-radius:16px;background:rgba(255,99,99,.12);border:1px solid rgba(255,99,99,.35);color:#ffd6d6;font-weight:800;}
        .section-divider{height:1px;background:var(--modal-border);margin:30px 0;}
        .garantie-checklist{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;}
        .garantie-item{border-radius:18px;background:rgba(255,255,255,.055);border:1px solid var(--modal-border);padding:16px 18px;transition:.2s ease;}
        .garantie-item:has(.garantie-checkbox:checked){border-color:rgba(46,213,115,.36);background:rgba(46,213,115,.065);}
        .garantie-top{display:flex;gap:14px;justify-content:space-between;align-items:center;flex-wrap:wrap;}
        .garantie-label{display:flex;align-items:center;gap:12px;font-weight:950;color:#fff;line-height:1.25;}
        .garantie-label input{width:18px;height:18px;accent-color:var(--modal-success);flex:0 0 auto;}
        .garantie-desc{color:var(--modal-muted);font-weight:750;margin:10px 0 10px 30px;line-height:1.45;}
        .garantie-meta{margin-left:30px;color:var(--modal-accent);font-size:13px;font-weight:900;}
        .garantie-level{min-width:205px;max-width:240px;padding:11px 13px;}
        .modal-footer{display:flex;justify-content:flex-end;align-items:center;gap:12px;flex-wrap:wrap;padding:22px 38px 30px;border-top:1px solid var(--modal-border);}
        .show-btn{height:46px;padding:0 22px;border-radius:14px;border:1px solid rgba(148,163,184,.22);display:inline-flex;align-items:center;justify-content:center;gap:9px;text-decoration:none;font-weight:900;transition:.2s ease;line-height:1;cursor:pointer;}
        .show-btn:hover{transform:translateY(-1px);}
        .btn-back{background:rgba(255,255,255,.035);color:#cbd5e1;}
        .btn-back:hover{background:rgba(255,255,255,.08);color:#fff;}
        .btn-save{background:#06b6d4;border-color:#06b6d4;color:#fff;box-shadow:0 14px 28px rgba(6,182,212,.18);}
        .btn-save:hover{background:#0891b2;border-color:#0891b2;color:#fff;}
        @media(max-width:850px){.admin-modal{width:min(100%,720px)}.modal-top,.modal-body,.modal-footer{padding-left:22px;padding-right:22px}.form-grid,.garantie-checklist{grid-template-columns:1fr}.modal-title{font-size:26px}.garantie-level{max-width:100%;width:100%}.modal-footer{justify-content:stretch}.show-btn{flex:1;min-width:140px}}
    </style>
</head>
<body>
<div class="modal-page-blur"></div>
<main class="modal-shell">
<section class="admin-modal">
    <div class="modal-top">
        <div class="modal-title-wrap">
            <div class="modal-icon"><i class="bi bi-pencil-square"></i></div>
            <div>
                <div class="modal-title">Modifier la formule</div>
                <div class="modal-sub">Formule #<?= (int)$id ?></div>
            </div>
        </div>
        <a href="showCategorie.php?id=<?= (int)$formuleData['id_categorie'] ?>" class="modal-close" title="Fermer"><i class="bi bi-x"></i></a>
    </div>

    <form method="POST" id="formuleForm">
        <div class="modal-body">
            <?php if (!empty($errors)) { ?>
                <div class="alert-error">
                    <?php foreach ($errors as $error) { ?>
                        <div>• <?= htmlspecialchars($error) ?></div>
                    <?php } ?>
                </div>
            <?php } ?>

            <h2 class="section-title"><i class="bi bi-shield-check"></i> Informations formule</h2>

            <div class="form-grid">
                <div class="form-card">
                    <label for="nom_formule">Nom formule <span class="required">*</span></label>
                    <input type="text" id="nom_formule" class="form-control" name="nom_formule" value="<?= htmlspecialchars($currentNom) ?>">
                    <div id="error_nom_formule" class="field-error"></div>
                </div>

                <div class="form-card">
                    <label for="niveau_formule">Niveau <span class="required">*</span></label>
                    <select class="form-control" id="niveau_formule" name="niveau_formule">
                        <option value="">-- Sélectionner un niveau --</option>
                        <option value="Essentiel" <?= $currentNiveau === 'Essentiel' ? 'selected' : '' ?>>Essentiel</option>
                        <option value="Intermédiaire" <?= $currentNiveau === 'Intermédiaire' ? 'selected' : '' ?>>Intermédiaire</option>
                        <option value="Premium" <?= $currentNiveau === 'Premium' ? 'selected' : '' ?>>Premium</option>
                    </select>
                    <div id="error_niveau_formule" class="field-error"></div>
                </div>

                <div class="form-card">
                    <label for="prix_formule">Prix <span class="required">*</span></label>
                    <input type="text" inputmode="decimal" id="prix_formule" class="form-control" name="prix_formule" value="<?= htmlspecialchars((string)$currentPrix) ?>">
                    <div id="error_prix_formule" class="field-error"></div>
                </div>

                <div class="form-card">
                    <label for="franchise_formule">Franchise <span class="required">*</span></label>
                    <input type="text" inputmode="decimal" id="franchise_formule" class="form-control" name="franchise_formule" value="<?= htmlspecialchars((string)$currentFranchise) ?>">
                    <div id="error_franchise_formule" class="field-error"></div>
                </div>

                <div class="form-card">
                    <label for="id_categorie">Catégorie <span class="required">*</span></label>
                    <select class="form-control" id="id_categorie" name="id_categorie">
                        <option value="">-- Sélectionner une catégorie --</option>
                        <?php foreach ($categories as $cat) { ?>
                            <option value="<?= (int)$cat['id_categorie'] ?>" <?= ((int)$cat['id_categorie'] === $currentCategorie) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nom_categorie']) ?>
                            </option>
                        <?php } ?>
                    </select>
                    <div id="error_id_categorie" class="field-error"></div>
                </div>

                <div class="form-card full">
                    <label for="description_formule">Description <span class="required">*</span></label>
                    <textarea class="form-control" id="description_formule" name="description_formule" rows="4"><?= htmlspecialchars($currentDescription) ?></textarea>
                    <div id="error_description_formule" class="field-error"></div>
                </div>
            </div>

            <div class="section-divider"></div>

            <h2 class="section-title"><i class="bi bi-list-check"></i> Garanties de la catégorie <span class="required">*</span></h2>
            <div id="error_garanties" class="field-error"></div>

            <?php if (empty($garantiesCatalogue)) { ?>
                <div class="alert-error" style="background:rgba(255,193,7,0.10);border-color:rgba(255,193,7,0.25);color:#ffe4a3;">
                    Aucune garantie catalogue n'est encore créée pour cette catégorie.
                </div>
            <?php } else { ?>
                <div class="garantie-checklist">
                    <?php foreach ($garantiesCatalogue as $garantie): ?>
                        <?php
                            $idGarantie = (int)$garantie['id_garantie'];
                            $isChecked = in_array((string)$idGarantie, $selectedGaranties, true);
                            $niveauSaved = $selectedLevels[$idGarantie] ?? 'basique';
                        ?>
                        <div class="garantie-item" data-categorie="<?= (int)$currentCategorie ?>">
                            <div class="garantie-top">
                                <label class="garantie-label">
                                    <input type="checkbox" class="garantie-checkbox" name="garanties[]" value="<?= $idGarantie ?>" <?= $isChecked ? 'checked' : '' ?>>
                                    <span><?= htmlspecialchars($garantie['nom_garantie']) ?></span>
                                </label>

                                <select class="form-control garantie-level" name="niveau_garantie[<?= $idGarantie ?>]">
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
        </div>

        <div class="modal-footer">
            <a href="showCategorie.php?id=<?= (int)$formuleData['id_categorie'] ?>" class="show-btn btn-back"><i class="bi bi-arrow-left"></i> Annuler</a>
            <button type="submit" class="show-btn btn-save"><i class="bi bi-check2-circle"></i> Modifier</button>
        </div>
    </form>
</section>
</main>

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
    if (parseFloat(value) <= 0) return setError(input, error, 'La franchise doit être supérieure à 0'), false;
    return setSuccess(input, error), true;
}
function validateNiveauFormule() {
    const input = document.getElementById('niveau_formule');
    const error = document.getElementById('error_niveau_formule');
    if (input.value.trim() === '') return setError(input, error, 'Niveau obligatoire'), false;
    return setSuccess(input, error), true;
}
function validateCategorieFormule() {
    const input = document.getElementById('id_categorie');
    const error = document.getElementById('error_id_categorie');
    if (input.value.trim() === '') return setError(input, error, 'Catégorie obligatoire'), false;
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
    const categorie = document.getElementById('id_categorie');

    nom.addEventListener('keypress', blockNumbersForText);
    desc.addEventListener('keypress', blockNumbersForText);
    nom.addEventListener('paste', blockPasteNumbers);
    desc.addEventListener('paste', blockPasteNumbers);

    nom.addEventListener('input', validateNomFormule);
    desc.addEventListener('input', validateDescriptionFormule);
    prix.addEventListener('input', validatePrixFormule);
    franchise.addEventListener('input', validateFranchiseFormule);
    niveau.addEventListener('change', validateNiveauFormule);
    categorie.addEventListener('change', validateCategorieFormule);

    document.querySelectorAll('.garantie-checkbox').forEach(cb => {
        cb.addEventListener('change', validateGarantiesSelection);
    });

    form.addEventListener('submit', function(e) {
        const ok = validateNomFormule() &&
                   validateDescriptionFormule() &&
                   validatePrixFormule() &&
                   validateFranchiseFormule() &&
                   validateNiveauFormule() &&
                   validateCategorieFormule() &&
                   validateGarantiesSelection();
        if (!ok) e.preventDefault();
    });
});
</script>
</body>
</html>
