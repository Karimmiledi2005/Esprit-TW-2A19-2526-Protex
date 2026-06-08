<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../connexion.php';

$db = config::getConnexion();
$id = (int)($_GET['id'] ?? 0);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

$idUser = (int)$_SESSION['user_id'];

if ($id <= 0) {
    header('Location: contrat.php?error=id');
    exit();
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function formatDateFr($date) {
    $t = strtotime((string)$date);
    return $t ? date('d/m/Y', $t) : h($date);
}

function labelize($key) {
    return mb_convert_case(str_replace('_', ' ', (string)$key), MB_CASE_TITLE, 'UTF-8');
}

function normTxtUpdate($v) {
    return mb_strtolower(trim((string)$v), 'UTF-8');
}

function columnExistsUpdate(PDO $db, string $table, string $column): bool {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column");
    $stmt->execute(['table' => $table, 'column' => $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function renderDetailField(string $key, $value, bool $canEdit): string {
    $label = h(labelize($key));
    $safeKey = h($key);
    $safeId = 'd_' . h($key);
    $v = h($value);
    $disabled = !$canEdit ? ' disabled' : '';
    $readonly = !$canEdit ? ' readonly' : '';
    $lk = normTxtUpdate($key);

    $selectOptions = [
        'civilite' => ['Monsieur', 'Madame'],
        'identite_adherent' => ['Monsieur', 'Madame'],
        'nationalite' => ['Tunisienne', 'Algérienne', 'Franéaise', 'Marocaine', 'Libyenne', 'Autre'],
        'situation_professionnelle' => ['étudiant', 'Salarié', 'Indépendant', 'Retraité', 'Sans emploi'],
        'situation_matrimoniale' => ['Célibataire', 'Marié(e)', 'Divorcé(e)', 'Veuf(ve)'],
        'revenu_annuel' => ['Moins de 10 000 DT', '10 000 é 20 000 DT', '20 000 é 40 000 DT', 'Plus de 40 000 DT'],
        'estimation_km' => ['Moins de 10 000 km', '10 000 é 20 000 km', '20 000 é 30 000 km', 'Plus de 30 000 km'],
        'kilometrage' => ['Moins de 10 000 km', '10 000 é 20 000 km', '20 000 é 30 000 km', 'Plus de 30 000 km'],
        'conducteurs' => ['Conducteur principal seul', 'Conducteur + conjoint', 'Plusieurs conducteurs'],
        'stationnement' => ['Garage privé', 'Parking collectif', 'Rue', 'Parking sécurisé'],
        'utilisation' => ['Usage occasionnel', 'Usage quotidien', 'Usage professionnel', 'Mixte'],
        'usage_vehicule' => ['Usage occasionnel', 'Usage quotidien', 'Usage professionnel', 'Mixte'],
        'trajets_prevus' => ['Ville', 'Route', 'Ville + route', 'Longs trajets'],
        'type_logement' => ['Appartement', 'Maison', 'Villa', 'Studio'],
        'statut_occupation' => ['Propriétaire', 'Locataire'],
        'zone_risque' => ['Oui', 'Non'],
        'hospitalisation' => ['Oui', 'Non'],
        'consultations_frequentes' => ['Oui', 'Non'],
        'traitement_regulier' => ['Oui', 'Non'],
        'besoin_optique' => ['Oui', 'Non'],
        'besoin_dentaire' => ['Oui', 'Non'],
    ];

    $matchedOptions = null;
    foreach ($selectOptions as $field => $options) {
        if ($lk === $field || str_contains($lk, $field)) {
            $matchedOptions = $options;
            break;
        }
    }

    if ($matchedOptions !== null) {
        $html = '<select class="detail-input detail-select" id="'.$safeId.'" name="details['.$safeKey.']"'.$disabled.'>';
        $html .= '<option value="">é Veuillez choisir une option é</option>';
        foreach ($matchedOptions as $option) {
            $selected = ((string)$value === $option) ? ' selected' : '';
            $html .= '<option value="'.h($option).'"'.$selected.'>'.h($option).'</option>';
        }
        if ((string)$value !== '' && !in_array((string)$value, $matchedOptions, true)) {
            $html .= '<option value="'.$v.'" selected>'.$v.'</option>';
        }
        $html .= '</select>';
        if (!$canEdit) {
            $html .= '<input type="hidden" name="details['.$safeKey.']" value="'.$v.'">';
        }
        return '<div class="detail-field"><label for="'.$safeId.'">'.$label.'</label>'.$html.'<div class="error-msg"></div></div>';
    }

    $type = 'text';
    if (str_contains($lk, 'date')) $type = 'date';
    elseif (str_contains($lk, 'email')) $type = 'email';
    elseif (str_contains($lk, 'commentaire') || str_contains($lk, 'precision') || str_contains($lk, 'précision')) {
        return '<div class="detail-field detail-field-wide"><label for="'.$safeId.'">'.$label.'</label><textarea class="detail-input detail-textarea" id="'.$safeId.'" name="details['.$safeKey.']"'.$readonly.'>'.$v.'</textarea><div class="error-msg"></div></div>';
    }

    return '<div class="detail-field"><label for="'.$safeId.'">'.$label.'</label><input type="'.$type.'" class="detail-input" id="'.$safeId.'" name="details['.$safeKey.']" value="'.$v.'"'.$readonly.'><div class="error-msg"></div></div>';
}

function statusClassUpdate($statut) {
    $s = strtolower(trim((string)$statut));
    return match ($s) {
        'actif', 'active' => 'active',
        'en attente', 'pending' => 'waiting',
        'expiré', 'expire', 'résilié', 'resilie', 'inactive' => 'expired',
        'refusé', 'refuse' => 'refused',
        default => 'waiting'
    };
}

function typeIconUpdate($type) {
    $t = strtolower(trim((string)$type));
    return match ($t) {
        'auto' => ['icon' => 'bi-car-front-fill', 'class' => 'auto'],
        'habitation' => ['icon' => 'bi-house-door-fill', 'class' => 'habitation'],
        'sante', 'santé' => ['icon' => 'bi-heart-pulse-fill', 'class' => 'sante'],
        'protection' => ['icon' => 'bi-shield-check', 'class' => 'protection'],
        default => ['icon' => 'bi-file-earmark-text', 'class' => 'default']
    };
}

$stmt = $db->prepare("SELECT c.*, cat.nom_categorie, f.nom_formule
                      FROM contrat c
                      LEFT JOIN categorie cat ON c.id_categorie = cat.id_categorie
                      LEFT JOIN formule f ON c.id_formule = f.id_formule
                      WHERE c.id_contrat = :id
                      LIMIT 1");
$stmt->execute(['id' => $id]);
$contrat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contrat) {
    header('Location: contrat.php?error=introuvable');
    exit();
}

$ownerId = (int)($contrat['id_user'] ?? $contrat['id_client'] ?? 0);
if ($ownerId !== 0 && $ownerId !== $idUser) {
    header('Location: contrat.php?error=unauthorized');
    exit();
}

$details = [];
if (!empty($contrat['details_contrat'])) {
    $decoded = json_decode($contrat['details_contrat'], true);
    if (is_array($decoded)) {
        $details = $decoded;
    }
}


$userColumn = columnExistsUpdate($db, 'contrat', 'id_user') ? 'id_user' : (columnExistsUpdate($db, 'contrat', 'id_client') ? 'id_client' : null);
$nbContratsClient = 0;
if ($userColumn !== null) {
    $countStmt = $db->prepare("SELECT COUNT(*) FROM contrat WHERE $userColumn = :id_user");
    $countStmt->execute(['id_user' => $idUser]);
    $nbContratsClient = (int)$countStmt->fetchColumn();
}

$selectedGaranties = [];
if (!empty($details['garanties']) && is_array($details['garanties'])) {
    $selectedGaranties = array_map('normTxtUpdate', $details['garanties']);
}

$garantiesFormule = [];
$hasIdFormule = columnExistsUpdate($db, 'contrat', 'id_formule');
$hasFormuleContrat = columnExistsUpdate($db, 'contrat', 'formule_contrat');
$joinCondition = $hasIdFormule ? 'c.id_formule = f.id_formule' : '1 = 0';
if ($hasFormuleContrat) {
    $joinCondition .= ' OR (c.formule_contrat = f.nom_formule AND c.id_categorie = f.id_categorie)';
}
$garStmt = $db->prepare("\n    SELECT DISTINCT\n        g.id_garantie,\n        g.nom_garantie,\n        g.description_garantie,\n        g.plafond_couvert_garantie,\n        fg.niveau_couvert_garantie,\n        f.nom_formule\n    FROM contrat c\n    INNER JOIN formule f ON ($joinCondition)\n    INNER JOIN formule_garantie fg ON f.id_formule = fg.id_formule\n    INNER JOIN garantie g ON fg.id_garantie = g.id_garantie\n    WHERE c.id_contrat = :id_contrat\n    ORDER BY\n        CASE\n            WHEN fg.niveau_couvert_garantie = 'basique' THEN 1\n            WHEN fg.niveau_couvert_garantie = 'option' THEN 2\n            ELSE 3\n        END,\n        g.nom_garantie ASC\n");
$garStmt->execute(['id_contrat' => $id]);
$garantiesFormule = $garStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$garantiesChoisies = [];
$garantiesConnues = [];
foreach ($garantiesFormule as $g) {
    $nom = (string)($g['nom_garantie'] ?? '');
    if ($nom === '') continue;
    $niveau = normTxtUpdate($g['niveau_couvert_garantie'] ?? 'basique');
    $isSelected = in_array(normTxtUpdate($nom), $selectedGaranties, true);
    $garantiesConnues[] = normTxtUpdate($nom);

    if ($niveau === 'basique') {
        $label = 'Incluse'; $class = 'basique'; $icon = 'bi-shield-check'; $editable = false; $checked = true;
    } elseif ($niveau === 'option' && $isSelected) {
        $label = 'Option choisie'; $class = 'option-selected'; $icon = 'bi-plus-circle-fill'; $editable = true; $checked = true;
    } elseif ($niveau === 'option') {
        $label = 'Option non choisie'; $class = 'option-off'; $icon = 'bi-circle'; $editable = true; $checked = false;
    } elseif ($niveau === 'non disponible' || $niveau === 'non_disponible') {
        $label = 'Non disponible'; $class = 'no'; $icon = 'bi-x-circle'; $editable = false; $checked = false;
    } else {
        $label = ucfirst((string)($g['niveau_couvert_garantie'] ?? 'Garantie')); $class = 'basique'; $icon = 'bi-shield-check'; $editable = false; $checked = true;
    }

    $garantiesChoisies[] = [
        'nom' => $nom,
        'description' => $g['description_garantie'] ?? '',
        'plafond' => number_format((float)($g['plafond_couvert_garantie'] ?? 0), 2) . ' DT',
        'niveau' => $label,
        'class' => $class,
        'icon' => $icon,
        'editable' => $editable,
        'checked' => $checked
    ];
}

foreach ($selectedGaranties as $garantieSaisie) {
    if ($garantieSaisie !== '' && !in_array($garantieSaisie, $garantiesConnues, true)) {
        $garantiesChoisies[] = [
            'nom' => mb_convert_case($garantieSaisie, MB_CASE_TITLE, 'UTF-8'),
            'description' => 'Garantie choisie dans le formulaire client.',
            'plafond' => '-',
            'niveau' => 'Option choisie',
            'class' => 'option-selected',
            'icon' => 'bi-plus-circle-fill',
            'editable' => true,
            'checked' => true
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newDetails = $_POST['details'] ?? [];
    if (!is_array($newDetails)) {
        $newDetails = [];
    }

    $clean = [];
    foreach ($newDetails as $key => $value) {
        $safeKey = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$key);
        if ($safeKey === '') continue;

        if (is_array($value)) {
            $items = [];
            foreach ($value as $item) {
                $item = trim((string)$item);
                if ($item !== '') {
                    $items[] = $item;
                }
            }
            $clean[$safeKey] = $items;
        } else {
            $clean[$safeKey] = trim((string)$value);
        }
    }

    $update = $db->prepare("UPDATE contrat
                            SET details_contrat = :details,
                                statut_contrat = 'en attente'
                            WHERE id_contrat = :id");
    $update->execute([
        'details' => json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'id' => $id
    ]);

    header('Location: contratshow.php?id=' . $id . '&success=update');
    exit();
}

$status = strtolower($contrat['statut_contrat'] ?? 'en attente');
$canEdit = in_array($status, ['en attente', 'refusé', 'refuse'], true);
$typeData = typeIconUpdate($contrat['type_contrat'] ?? '');
$clientInitials = strtoupper(substr($_SESSION['prenom'] ?? 'M', 0, 1) . substr($_SESSION['nom'] ?? 'B', 0, 1));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Modifier contrat é Protex</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">

<link rel="stylesheet" href="assets/css/variables.css">
<link rel="stylesheet" href="assets/css/base.css">
<link rel="stylesheet" href="assets/css/layout.css">
<link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
<link rel="stylesheet" href="assets/css/animations.css">
<link rel="stylesheet" href="user/assets_contrats/css/contrat.css">

<style>
.update-wrap{width:min(1280px,calc(100% - 72px))!important;max-width:1280px!important;margin:34px auto!important;padding:0!important}.update-card{width:100%;background:rgba(255,255,255,.88);border:1px solid rgba(226,232,240,.9);border-radius:30px;padding:28px;box-shadow:0 24px 70px rgba(10,25,49,.12);backdrop-filter:blur(18px)}.update-head{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:24px;padding:22px;border-radius:24px;background:linear-gradient(135deg,#0A1931,#123d70);color:#fff}.update-title{display:flex;align-items:center;gap:16px}.update-head h1{margin:0;color:#fff;font-size:32px}.update-head p{margin:6px 0 0;color:#dbeafe;font-weight:800}.update-icon{width:76px;height:76px;border-radius:23px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:32px;background:linear-gradient(135deg,#ff8a3d,#ff4f1a);box-shadow:0 18px 35px rgba(255,107,26,.25)}.update-icon.sante{background:linear-gradient(135deg,#2ecc71,#17b86a)}.update-icon.habitation{background:linear-gradient(135deg,#f5b21b,#d99000)}.update-icon.protection{background:linear-gradient(135deg,#5578ff,#2f5bff)}.update-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:20px 0}.update-info{border:1px solid rgba(226,232,240,.9);border-radius:20px;padding:16px;background:linear-gradient(180deg,#fff,#f8fbff)}.update-info span{display:block;color:#667085;font-size:12px;text-transform:uppercase;letter-spacing:.05em;margin-bottom:7px;font-weight:900}.update-info strong{font-size:16px;color:#0A1931}.section-block{margin-top:24px}.section-heading{display:flex;align-items:center;gap:10px;font-size:22px;font-weight:950;color:#0A1931;margin:0 0 14px}.section-heading i{color:#ff6b1a}.details-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:12px}.detail-field{border:1px solid rgba(226,232,240,.9);border-radius:18px;padding:15px;background:linear-gradient(180deg,#fff,#f8fbff)}.detail-field label{display:block;color:#667085;font-size:12px;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;font-weight:900}.detail-input{width:100%;border:1px solid rgba(226,232,240,.95);border-radius:14px;padding:13px 14px;background:#fff;color:#0A1931;font-weight:800;outline:none;min-height:48px}.detail-input:focus{border-color:#ff6b1a;box-shadow:0 0 0 4px rgba(255,107,26,.12)}.detail-input[readonly]{background:#f3f6fb;color:#64748b;cursor:not-allowed}.garanties-box{grid-column:1/-1;margin:0;border:1px solid rgba(226,232,240,.9);border-radius:20px;padding:16px;background:linear-gradient(180deg,#fff,#f8fbff)}.garanties-box>span{display:flex;align-items:center;gap:8px;color:#0A1931;font-size:18px;font-weight:950;margin-bottom:12px;text-transform:none;letter-spacing:0}.garanties-list{display:flex;flex-wrap:wrap;gap:10px}.garantie-pill{display:inline-flex;align-items:center;gap:7px;padding:9px 13px;border-radius:999px;background:rgba(24,160,88,.10);border:1px solid rgba(24,160,88,.25);color:#0b5d36;font-weight:850;font-size:13px}.garantie-pill i{color:#18a058}.status-badge{padding:9px 14px;border-radius:999px;font-weight:950;text-transform:capitalize;background:#eef2ff;color:#1e3a8a}.status-badge.active{background:#dcfce7;color:#166534}.status-badge.waiting{background:#fef3c7;color:#92400e}.status-badge.expired,.status-badge.refused{background:#fee2e2;color:#991b1b}.alert-update{padding:16px 18px;border-radius:18px;background:#fff7e8;color:#9a5a00;margin-bottom:18px;border:1px solid rgba(255,107,26,.18);font-weight:800}.error-msg{color:#e53935;font-size:13px;margin-top:6px;font-weight:800}.update-actions{display:flex;gap:12px;justify-content:flex-end;align-items:center;margin-top:24px}.btn-update{height:48px;min-width:112px;padding:0 22px;border-radius:16px;font-weight:950;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:8px;line-height:1;border:0;cursor:pointer;white-space:nowrap}.btn-update-back{background:#fff;color:#0A1931;border:1px solid rgba(15,31,58,.12)}.btn-update-save{background:linear-gradient(135deg,#ff7a2f,#ff4b1f);color:#fff;box-shadow:0 16px 30px rgba(255,107,26,.22)}.btn-update-save:hover,.btn-update-back:hover{transform:translateY(-1px)}.empty-note{grid-column:1/-1;padding:16px;border:1px dashed rgba(148,163,184,.7);border-radius:16px;color:#667085;background:#fff;font-weight:800}.detail-textarea{min-height:110px;resize:vertical}.detail-field-wide{grid-column:1/-1}.garanties-list{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px!important}.garantie-line{display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;gap:12px;padding:14px;border-radius:18px;background:#fff;border:1px solid rgba(226,232,240,.9);font-weight:800;color:#0A1931;box-shadow:0 12px 28px rgba(10,25,49,.06)}.garantie-line.basique i{color:#16a064}.garantie-line.option-selected i{color:#ff6b1a}.garantie-line.option-off{opacity:.65}.garantie-line.option-off i{color:#8894a8}.garantie-line.no{opacity:.72}.garantie-line.no i{color:#a1aabc}.garantie-line small{font-weight:950;color:#ff6b1a;margin-left:4px}.garantie-desc{display:block;margin-top:4px;color:#667085;font-size:12px;font-weight:700;line-height:1.35}.garantie-plafond{white-space:nowrap;padding:8px 10px;border-radius:999px;background:rgba(0,180,216,.12);border:1px solid rgba(0,180,216,.25);color:#0A1931;font-size:12px}.garantie-check{width:18px;height:18px;accent-color:#ff6b1a}.nav-badge.accent{margin-left:6px}@media(max-width:900px){.update-grid,.details-grid,.garanties-list{grid-template-columns:1fr}.update-head{align-items:flex-start;flex-direction:column}.update-actions{justify-content:flex-start;flex-wrap:wrap}}@media(max-width:600px){.update-grid,.details-grid{grid-template-columns:1fr}.btn-update{width:100%}.update-actions{flex-direction:column}}
</style>

    <!-- FrontOffice unifie - surcharge théme camarades dark-navy -->
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css"></head>
<body>
<div class="background"></div><div class="orb orb-1"></div><div class="orb orb-2"></div><div class="orb orb-3"></div>
<div class="layout">
<?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

<main class="update-wrap">
    <section class="update-card">
        <div class="update-head">
            <div class="update-title">
                <div class="update-icon <?= h($typeData['class']) ?>"><i class="bi <?= h($typeData['icon']) ?>"></i></div>
                <div>
                    <h1>Modifier contrat <?= h($contrat['type_contrat']) ?></h1>
                    <p>Né <?= h($contrat['numero_contrat']) ?></p>
                </div>
            </div>
            <span class="status-badge <?= h(statusClassUpdate($contrat['statut_contrat'] ?? '')) ?>"><?= h($contrat['statut_contrat']) ?></span>
        </div>

        <?php if (!$canEdit): ?>
            <div class="alert-update"><i class="bi bi-info-circle"></i> Ce contrat est déjé traité. La modification client est disponible seulement avant validation ou aprés refus.</div>
        <?php endif; ?>

        <div class="update-grid">
            <div class="update-info"><span>Catégorie</span><strong><?= h($contrat['nom_categorie'] ?? $contrat['type_contrat']) ?></strong></div>
            <div class="update-info"><span>Formule</span><strong><?= h($contrat['nom_formule'] ?? $contrat['formule_contrat'] ?? '-') ?></strong></div>
            <div class="update-info"><span>Date début</span><strong><?= formatDateFr($contrat['date_debut_contrat']) ?></strong></div>
            <div class="update-info"><span>Date fin</span><strong><?= formatDateFr($contrat['date_fin_contrat']) ?></strong></div>
            <div class="update-info"><span>Prime</span><strong><?= number_format((float)$contrat['prime_contrat'], 2) ?> DT</strong></div>
            <div class="update-info"><span>Franchise</span><strong><?= number_format((float)$contrat['franchise_contrat'], 2) ?> DT</strong></div>
        </div>

        <div class="section-block">
            <h2 class="section-heading"><i class="bi bi-pencil-square"></i>Informations du formulaire</h2>
            <form method="POST" novalidate onsubmit="return validateDetails()">
                <div class="details-grid">
                    <?php if (!empty($garantiesChoisies)): ?>
                        <div class="garanties-box">
                            <span><i class="bi bi-shield-check"></i>Garanties choisies dans le formulaire</span>
                            <div class="garanties-list">
                                <?php foreach ($garantiesChoisies as $gIndex => $g): ?>
                                    <label class="garantie-line <?= h($g['class'] ?? 'basique') ?>">
                                        <?php if (!empty($g['editable']) && $canEdit): ?>
                                            <input class="garantie-check" type="checkbox" name="details[garanties][]" value="<?= h($g['nom']) ?>" <?= !empty($g['checked']) ? 'checked' : '' ?>>
                                        <?php else: ?>
                                            <i class="bi <?= h($g['icon'] ?? 'bi-shield-check') ?>"></i>
                                            <?php if (!empty($g['checked'])): ?>
                                                <input type="hidden" name="details[garanties][]" value="<?= h($g['nom']) ?>">
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <span>
                                            <?= h($g['nom']) ?> <small>(<?= h($g['niveau']) ?>)</small>
                                            <?php if (!empty($g['description'])): ?>
                                                <em class="garantie-desc"><?= h($g['description']) ?></em>
                                            <?php endif; ?>
                                        </span>
                                        <strong class="garantie-plafond"><?= h($g['plafond'] ?? '-') ?></strong>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($details)): ?>
                        <?php foreach ($details as $key => $value): ?>
                            <?php if ($key === 'garanties') continue; ?>
                            <?php if (is_array($value)): ?>
                                <div class="garanties-box">
                                    <span><i class="bi bi-list-check"></i><?= h(labelize($key)) ?></span>
                                    <div class="garanties-list">
                                        <?php foreach ($value as $index => $item): ?>
                                            <span class="garantie-pill"><i class="bi bi-check-circle-fill"></i><?= h($item) ?></span>
                                            <input type="hidden" name="details[<?= h($key) ?>][<?= (int)$index ?>]" value="<?= h($item) ?>">
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?= renderDetailField((string)$key, $value, $canEdit) ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php elseif (empty($garantiesChoisies)): ?>
                        <div class="empty-note">Aucun détail enregistré pour ce contrat.</div>
                    <?php endif; ?>                </div>

                <div class="update-actions">
                    <a href="contratshow.php?id=<?= (int)$id ?>" class="btn-update btn-update-back"><i class="bi bi-arrow-left"></i>Retour</a>
                    <?php if ($canEdit): ?>
                        <button type="submit" class="btn-update btn-update-save"><i class="bi bi-check2-circle"></i>Enregistrer</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </section>
</main>
</div>

<!-- legacy script removed -->
<script src="user/assets_contrats/js/main.js"></script>
<script>
function detailTodayISO(){ const d=new Date(); d.setHours(0,0,0,0); return d.toISOString().slice(0,10); }
function detailYearsAgo(y){ const d=new Date(); d.setFullYear(d.getFullYear()-y); d.setHours(0,0,0,0); return d.toISOString().slice(0,10); }
const detailRules = {
    email: /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/,
    letters: /^[A-Za-zé-éé-éé-éA-?\u0600-\u06FF]+(?:[ '\-][A-Za-zé-éé-éé-éA-?\u0600-\u06FF]+)*$/u,
    address: /^[A-Za-zé-éé-éé-éA-?\u0600-\u06FF0-9\s,.'éé\-\/]+$/u,
    immatTN: /^\d{1,4}\s*TUN\s*\d{1,4}$/i,
    immatAr: /^??\s*\d{1,6}$/u,
    immatForeign: /^(?=.*[A-Za-z\u0600-\u06FF])(?=.*\d)[A-Za-z0-9\u0600-\u06FF\-\s]{3,15}$/u
};
function cleanNumber(v){ return Number(String(v).replace(',', '.').replace(/\s/g,'')); }
function setDetailState(input, msg){
    const err = input.parentElement.querySelector('.error-msg');
    if (err) err.textContent = msg || '';
    input.style.borderColor = msg ? '#e53935' : '#18a058';
    input.style.boxShadow = msg ? '0 0 0 3px rgba(229,57,53,.12)' : '0 0 0 3px rgba(24,160,88,.10)';
    return !msg;
}
function validateOneDetail(input){
    if (input.readOnly) return true;
    const label = (input.parentElement.querySelector('label')?.textContent || 'Champ').trim();
    const key = (input.id + ' ' + input.name + ' ' + label).toLowerCase();
    const value = input.value.trim();
    if (value === '') return setDetailState(input, label + ' obligatoire.');
    if (key.includes('email')) return setDetailState(input, detailRules.email.test(value) ? '' : 'Email invalide.');
    if (key.includes('telephone') || key.includes('téléphone')) return setDetailState(input, /^\d{8}$/.test(value.replace(/\s/g,'')) ? '' : 'Téléphone invalide : exactement 8 chiffres.');
    if (key.includes('nom') || key.includes('prenom') || key.includes('nationalite') || key.includes('nationalité')) return setDetailState(input, detailRules.letters.test(value) && value.length >= 2 ? '' : label + ' doit contenir seulement des lettres.');
    if (key.includes('adresse')) return setDetailState(input, detailRules.address.test(value) && value.length >= 5 ? '' : 'Adresse invalide : lettres, chiffres et ponctuation simple seulement.');
    if (key.includes('immatriculation')) { const compact = value.replace(/\s+/g,''); return setDetailState(input, (detailRules.immatTN.test(value) || detailRules.immatAr.test(compact) || detailRules.immatForeign.test(value)) ? '' : 'Immatriculation invalide. Exemples : 123TUN4567, ??225444, AB-123-CD.'); }
    if (key.includes('puissance')) { const n=cleanNumber(value); return setDetailState(input, Number.isFinite(n) && n>=1 && n<=100 ? '' : 'Puissance invalide : entre 1 et 100 CV.'); }
    if (key.includes('valeur venale') || key.includes('valeur_venale')) { const n=cleanNumber(value); return setDetailState(input, Number.isFinite(n) && n>=1000 && n<=1000000 ? '' : 'Valeur vénale invalide : entre 1 000 et 1 000 000 DT.'); }
    if (key.includes('date circulation') || key.includes('date_circulation')) return setDetailState(input, value <= detailTodayISO() && value >= '1980-01-01' ? '' : 'Date circulation invalide : pas future et pas avant 1980.');
    if (key.includes('date naissance') || key.includes('date_naissance')) return setDetailState(input, value <= detailYearsAgo(18) && value >= detailYearsAgo(100) ? '' : 'ége invalide : entre 18 et 100 ans.');
    return setDetailState(input, '');
}
function validateDetails(){
    let ok = true, first = null;
    document.querySelectorAll('.detail-input').forEach(input => {
        if (!validateOneDetail(input)) { ok = false; if (!first) first = input; }
    });
    if (!ok && first) first.focus();
    return ok;
}
document.addEventListener('DOMContentLoaded', function(){
    const form = document.querySelector('form[method="POST"]');
    if (form) form.setAttribute('novalidate','novalidate');
    document.querySelectorAll('.detail-input').forEach(input => {
        if (input.tagName === 'INPUT' && (input.type === 'email' || input.type === 'number')) input.setAttribute('type', 'text');
        input.addEventListener('input', () => validateOneDetail(input));
        input.addEventListener('change', () => validateOneDetail(input));
    });
});
</script>
<script src="assets/js/main.js"></script>
</body>
</html>



