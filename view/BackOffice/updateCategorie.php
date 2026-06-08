<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
SessionGuard::requireRoles(['superadmin', 'admin']);

require_once __DIR__ . '/../../controller/CategorieController.php';
require_once __DIR__ . '/../../model/Categorie.php';
require_once __DIR__ . '/../../connexion.php';

$categorieC = new CategorieController();
$errors = [];

function normalizeText($value) {
    return trim(preg_replace('/\s+/', ' ', $value));
}

if (!isset($_GET['id'])) {
    die("ID catégorie manquant.");
}

$id = (int)$_GET['id'];
$categorieData = $categorieC->showCategorie($id);

if (!$categorieData) {
    die("Catégorie introuvable.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = normalizeText($_POST["nom_categorie"] ?? "");
    $description = normalizeText($_POST["description_categorie"] ?? "");

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

    // ===== ANTI-DOUBLON UPDATE =====
    // On bloque si une autre catégorie possède le même nom OU la même description.
    if (empty($errors)) {
        try {
            $db = config::getConnexion();
            $check = $db->prepare("
                SELECT COUNT(*)
                FROM categorie
                WHERE id_categorie <> :id
                  AND (
                        LOWER(TRIM(nom_categorie)) = LOWER(TRIM(:nom))
                     OR LOWER(TRIM(description_categorie)) = LOWER(TRIM(:description))
                  )
            ");
            $check->execute([
                'id' => $id,
                'nom' => $nom,
                'description' => $description
            ]);

            if ((int)$check->fetchColumn() > 0) {
                $errors[] = "Une autre catégorie possède déjà le même nom ou la même description.";
            }
        } catch (Exception $e) {
            $errors[] = "Erreur lors de la vérification du doublon : " . $e->getMessage();
        }
    }

    if (empty($errors)) {
        $categorie = new Categorie($nom, $description);
        $categorieC->updateCategorie($id, $categorie);

        header("Location: categories_back.php");
        exit;
    }
}

$currentNom = $_POST['nom_categorie'] ?? $categorieData['nom_categorie'];
$currentDescription = $_POST['description_categorie'] ?? ($categorieData['description_categorie'] ?? '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier catégorie</title>
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/admin-users.css">
    <link rel="stylesheet" href="assets/css/contrats.css">
    <link rel="stylesheet" href="assets/css/forms.css">
    <style>
        .field-error {
            color: #ff8f8f;
            font-size: 13px;
            margin-top: 6px;
            min-height: 18px;
        }
        .input-invalid {
            border-color: #ff6b6b !important;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.12) !important;
        }
        .input-valid {
            border-color: #22c55e !important;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.10) !important;
        }
        .error-box {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 14px;
            background: rgba(255,99,99,0.12);
            border: 1px solid rgba(255,99,99,0.35);
            color: #ffd6d6;
        }
    </style>
</head>
<body>
<div class="content" style="padding:40px;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Modifier la catégorie</div>
        </div>

        <form method="POST" id="categorieForm" style="padding:24px;" novalidate>
            <?php if (!empty($errors)) { ?>
                <div class="error-box">
                    <?php foreach ($errors as $error) { ?>
                        <div>• <?= htmlspecialchars($error) ?></div>
                    <?php } ?>
                </div>
            <?php } ?>

            <div class="form-group">
                <label for="nom_categorie">Nom catégorie <span style="color:red;">*</span></label>
                <input type="text"
                       id="nom_categorie"
                       class="form-control"
                       name="nom_categorie"
                       value="<?= htmlspecialchars($currentNom) ?>"
                       placeholder="Saisir le nom de la catégorie">
                <div class="field-error" id="error_nom_categorie"></div>
            </div>

            <div class="form-group">
                <label for="description_categorie">Description <span style="color:red;">*</span></label>
                <textarea id="description_categorie"
                          class="form-control"
                          name="description_categorie"
                          rows="4"
                          placeholder="Saisir la description de la catégorie"><?= htmlspecialchars($currentDescription) ?></textarea>
                <div class="field-error" id="error_description_categorie"></div>
            </div>

            <div class="modal-footer" style="padding:0; border-top:none;">
                <a href="categories_back.php" class="btn btn-outline">Annuler</a>
                <button type="submit" class="btn btn-primary">Modifier</button>
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

function blockNumbers(e) {
    if (/\d/.test(e.key)) {
        e.preventDefault();
    }
}

function blockPasteNumbers(e) {
    const paste = (e.clipboardData || window.clipboardData).getData('text');
    if (/\d/.test(paste)) {
        e.preventDefault();
    }
}

function validateNom() {
    const input = document.getElementById('nom_categorie');
    const error = document.getElementById('error_nom_categorie');
    const value = input.value.trim();

    if (value === '') {
        setError(input, error, 'Le nom est obligatoire.');
        return false;
    }

    if (value.length < 3) {
        setError(input, error, 'Le nom doit contenir au moins 3 caractères.');
        return false;
    }

    if (value.length > 100) {
        setError(input, error, 'Le nom ne doit pas dépasser 100 caractères.');
        return false;
    }

    if (/\d/.test(value)) {
        setError(input, error, 'Les chiffres sont interdits.');
        return false;
    }

    if (!/^[A-Za-zÀ-ÿ\s\-]+$/.test(value)) {
        setError(input, error, 'Utilisez seulement des lettres, espaces ou tirets.');
        return false;
    }

    setSuccess(input, error);
    return true;
}

function validateDescription() {
    const input = document.getElementById('description_categorie');
    const error = document.getElementById('error_description_categorie');
    const value = input.value.trim();

    if (value === '') {
        setError(input, error, 'La description est obligatoire.');
        return false;
    }

    if (value.length < 10) {
        setError(input, error, 'La description doit contenir au moins 10 caractères.');
        return false;
    }

    if (value.length > 500) {
        setError(input, error, 'La description ne doit pas dépasser 500 caractères.');
        return false;
    }

    if (/\d/.test(value)) {
        setError(input, error, 'Les chiffres sont interdits.');
        return false;
    }

    setSuccess(input, error);
    return true;
}

function validateCategorieForm() {
    const validNom = validateNom();
    const validDescription = validateDescription();
    return validNom && validDescription;
}

document.addEventListener('DOMContentLoaded', function () {
    const nom = document.getElementById('nom_categorie');
    const desc = document.getElementById('description_categorie');
    const form = document.getElementById('categorieForm');

    nom.addEventListener('keypress', blockNumbers);
    desc.addEventListener('keypress', blockNumbers);
    nom.addEventListener('paste', blockPasteNumbers);
    desc.addEventListener('paste', blockPasteNumbers);

    nom.addEventListener('input', validateNom);
    desc.addEventListener('input', validateDescription);

    form.addEventListener('submit', function(e) {
        if (!validateCategorieForm()) {
            e.preventDefault();
        }
    });
});
</script>
</body>
</html>
