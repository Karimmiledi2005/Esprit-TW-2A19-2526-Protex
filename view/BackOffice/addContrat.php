<?php
require_once dirname(__DIR__, 2) . '/helpers/SessionGuard.php';
SessionGuard::requireBackoffice();
require_once __DIR__ . '/../../connexion.php';
require_once __DIR__ . '/../../controller/ContratController.php';
require_once __DIR__ . '/../../model/Contrat.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    $cfgPath = dirname(__DIR__, 2) . '/config.php';
    if (file_exists($cfgPath)) require_once $cfgPath;
}
$base = defined('BASE_URL') ? BASE_URL : '';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$db = config::getConnexion();
$contratC = new ContratController();
$errors = [];

try {
    $role = $_SESSION['role'] ?? '';
    $id_agence_session = $_SESSION['id_agence'] ?? null;

    $sql = "SELECT u.id_user, u.nom, u.prenom, u.email, u.telephone, u.adresse, u.date_naissance 
            FROM `user` u 
            LEFT JOIN client c ON u.id_user = c.id_user 
            WHERE u.role IN ('client', 'user')";
    $params = [];

    if ($role !== 'superadmin' && $id_agence_session) {
        $sql .= " AND c.id_agence = ?";
        $params[] = $id_agence_session;
    }

    $sql .= " ORDER BY u.prenom ASC, u.nom ASC";

    $clientsStmt = $db->prepare($sql);
    $clientsStmt->execute($params);
    $clients = $clientsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    if (empty($clients) && $role === 'superadmin') {
        // Fallback for superadmin if no specific role found
        $clientsStmt = $db->query("SELECT id_user, nom, prenom, email, telephone, adresse, date_naissance FROM `user` ORDER BY prenom ASC, nom ASC");
        $clients = $clientsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (Throwable $e) {
    $clients = [];
}

try {
    $catStmt = $db->query("SELECT id_categorie, nom_categorie FROM categorie ORDER BY nom_categorie ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $categories = [];
}

try {
    $formules = $contratC->getAllFormules();
    if ($formules instanceof PDOStatement) {
        $formules = $formules->fetchAll(PDO::FETCH_ASSOC);
    }
    if (!is_array($formules)) {
        $formules = [];
    }
} catch (Throwable $e) {
    $formules = [];
}

$garantiesParFormule = [];
try {
    $gStmt = $db->query("\n        SELECT\n            fg.id_formule,\n            g.id_garantie,\n            g.nom_garantie,\n            g.description_garantie,\n            g.plafond_couvert_garantie,\n            COALESCE(fg.niveau_couvert_garantie, 'basique') AS niveau_couvert_garantie\n        FROM formule_garantie fg\n        INNER JOIN garantie g ON g.id_garantie = fg.id_garantie\n        ORDER BY\n            fg.id_formule ASC,\n            CASE\n                WHEN COALESCE(fg.niveau_couvert_garantie, 'basique') = 'basique' THEN 1\n                WHEN COALESCE(fg.niveau_couvert_garantie, 'basique') = 'option' THEN 2\n                ELSE 3\n            END,\n            g.nom_garantie ASC\n    ");
    foreach ($gStmt->fetchAll(PDO::FETCH_ASSOC) as $g) {
        $idF = (int)$g['id_formule'];
        if (!isset($garantiesParFormule[$idF])) {
            $garantiesParFormule[$idF] = [];
        }
        $garantiesParFormule[$idF][] = $g;
    }
} catch (Throwable $e) {
    $garantiesParFormule = [];
}

function findFormuleById(array $formules, int $idFormule): ?array
{
    foreach ($formules as $f) {
        if ((int)($f['id_formule'] ?? 0) === $idFormule) {
            return $f;
        }
    }
    return null;
}

function normalizeCategory(string $cat): string
{
    $cat = strtolower(trim($cat));
    $cat = str_replace(['é', 'è', 'ê', 'à', 'â', 'ù', 'û', 'î', 'ï', 'ô'], ['e', 'e', 'e', 'a', 'a', 'u', 'u', 'i', 'i', 'o'], $cat);
    return $cat;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idClient = (int)($_POST['id_client'] ?? 0);
    $idCategorie = (int)($_POST['id_categorie'] ?? 0);
    $idFormule = (int)($_POST['id_formule'] ?? 0);
    $dateDebut = trim($_POST['date_debut_contrat'] ?? date('Y-m-d'));
    $dateFin = trim($_POST['date_fin_contrat'] ?? date('Y-m-d', strtotime('+1 year')));
    $statut = trim($_POST['statut_contrat'] ?? 'en attente');

    $formule = findFormuleById($formules, $idFormule);

    if ($idClient <= 0) {
        $errors[] = 'Veuillez choisir le client.';
    }
    if ($idCategorie <= 0) {
        $errors[] = 'Veuillez choisir la catégorie.';
    }
    if (!$formule) {
        $errors[] = 'Veuillez choisir une formule valide.';
    }
    if ($formule && (int)($formule['id_categorie'] ?? 0) !== $idCategorie) {
        $errors[] = 'La formule choisie ne correspond pas à la catégorie.';
    }
    if ($dateDebut === '') {
        $errors[] = 'La date de début est obligatoire.';
    }
    if ($dateFin === '' || $dateFin <= $dateDebut) {
        $errors[] = 'La date de fin doit être supérieure à la date de début.';
    }

    if (empty($errors) && $formule) {
        $categoryName = (string)($formule['nom_categorie'] ?? 'Contrat');
        $categoryKey = normalizeCategory($categoryName);
        $allGaranties = $garantiesParFormule[$idFormule] ?? [];
        $optionsChoisiesIds = array_map('intval', $_POST['garanties_optionnelles'] ?? []);
        $garantiesChoisies = [];
        $garantiesToutes = [];

        foreach ($allGaranties as $g) {
            $niveau = strtolower(trim((string)($g['niveau_couvert_garantie'] ?? 'basique')));
            $idG = (int)$g['id_garantie'];
            $isBasique = $niveau === 'basique' || $niveau === 'incluse';
            $isOptionChoisie = $niveau === 'option' && in_array($idG, $optionsChoisiesIds, true);
            $etat = $isBasique ? 'incluse' : ($isOptionChoisie ? 'option choisie' : ($niveau === 'option' ? 'option non choisie' : 'non disponible'));
            $item = [
                'id' => $idG,
                'nom' => (string)$g['nom_garantie'],
                'niveau' => $niveau,
                'etat' => $etat,
                'plafond' => (float)($g['plafond_couvert_garantie'] ?? 0),
            ];
            $garantiesToutes[] = $item;
            if ($isBasique || $isOptionChoisie) {
                $garantiesChoisies[] = $item;
            }
        }

        $details = [
            'source' => 'Ajout back-office agent',
            'identite' => trim($_POST['identite'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'nom' => trim($_POST['nom'] ?? ''),
            'prenom' => trim($_POST['prenom'] ?? ''),
            'telephone' => preg_replace('/\D+/', '', (string)($_POST['telephone'] ?? '')),
            'date_naissance' => trim($_POST['date_naissance'] ?? ''),
            'nationalite' => trim($_POST['nationalite'] ?? ''),
            'situation_professionnelle' => trim($_POST['situation_professionnelle'] ?? ''),
            'adresse' => trim($_POST['adresse'] ?? ''),
            'situation_matrimoniale' => trim($_POST['situation_matrimoniale'] ?? ''),
            'revenu_annuel' => trim($_POST['revenu_annuel'] ?? ''),
            'garanties' => implode(', ', array_column($garantiesChoisies, 'nom')),
            'garanties_choisies' => $garantiesChoisies,
            'garanties_toutes' => $garantiesToutes,
        ];

        if (str_contains($categoryKey, 'auto')) {
            $details += [
                'immatriculation' => trim($_POST['immatriculation'] ?? ''),
                'marque' => trim($_POST['marque'] ?? ''),
                'usage_vehicule' => trim($_POST['usage_vehicule'] ?? ''),
                'kilometrage' => trim($_POST['kilometrage'] ?? ''),
                'puissance' => trim($_POST['puissance'] ?? ''),
                'date_circulation' => trim($_POST['date_circulation'] ?? ''),
                'valeur_venale' => trim($_POST['valeur_venale'] ?? ''),
                'financement' => trim($_POST['financement'] ?? ''),
                'estimation_km' => trim($_POST['estimation_km'] ?? ''),
                'conducteurs' => trim($_POST['conducteurs'] ?? ''),
                'stationnement' => trim($_POST['stationnement'] ?? ''),
                'utilisation' => trim($_POST['utilisation'] ?? ''),
                'trajets_prevus' => trim($_POST['trajets_prevus'] ?? ''),
                'details_formule' => trim($_POST['details_formule'] ?? ''),
            ];
        } elseif (str_contains($categoryKey, 'habitation')) {
            $details += [
                'type_logement' => trim($_POST['type_logement'] ?? ''),
                'statut_occupation' => trim($_POST['statut_occupation'] ?? ''),
                'adresse_logement' => trim($_POST['adresse_logement'] ?? ''),
                'surface_logement' => trim($_POST['surface_logement'] ?? ''),
                'nb_pieces' => trim($_POST['nb_pieces'] ?? ''),
                'valeur_biens' => trim($_POST['valeur_biens'] ?? ''),
                'details_formule' => trim($_POST['details_formule'] ?? ''),
            ];
        } elseif (str_contains($categoryKey, 'sante')) {
            $details += [
                'type_couverture' => trim($_POST['type_couverture'] ?? ''),
                'nombre_beneficiaires' => trim($_POST['nombre_beneficiaires'] ?? ''),
                'antecedents' => trim($_POST['antecedents'] ?? ''),
                'frequence_soins' => trim($_POST['frequence_soins'] ?? ''),
                'couverture_dentaire' => trim($_POST['couverture_dentaire'] ?? ''),
                'couverture_optique' => trim($_POST['couverture_optique'] ?? ''),
                'details_formule' => trim($_POST['details_formule'] ?? ''),
            ];
        } elseif (str_contains($categoryKey, 'protection')) {
            $details += [
                'type_protection' => trim($_POST['type_protection'] ?? ''),
                'niveau_couverture' => trim($_POST['niveau_couverture'] ?? ''),
                'montant_couverture' => trim($_POST['montant_couverture'] ?? ''),
                'duree_contrat' => trim($_POST['duree_contrat'] ?? ''),
                'couvrir_famille' => isset($_POST['couvrir_famille']) ? 'oui' : 'non',
                'details_formule' => trim($_POST['details_formule'] ?? ''),
            ];
        }

        $contrat = new Contrat(
            $contratC->generateNumero(),
            $categoryName,
            $idClient,
            $idCategorie,
            (float)($formule['prix_formule'] ?? 0),
            (float)($formule['franchise_formule'] ?? 0),
            $dateDebut,
            $dateFin,
            $statut,
            $idFormule,
            (string)($formule['nom_formule'] ?? ''),
            json_encode($details, JSON_UNESCAPED_UNICODE)
        );

        try {
            if ($contratC->addContrat($contrat)) {
                header('Location: contrats_back.php?success=add');
                exit();
            }
            $errors[] = 'Erreur lors de l\'ajout du contrat.';
        } catch (Throwable $e) {
            error_log('addContrat.php error: ' . $e->getMessage());
            $errors[] = 'Erreur lors de l\'ajout. Veuillez réessayer plus tard.';
        }
    }
}

$clientsJson = json_encode($clients, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
$categoriesJson = json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
$formulesJson = json_encode($formules, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
$garantiesJson = json_encode($garantiesParFormule, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter contrat — Protex Admin</title>
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
        .show-page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:32px 18px;position:relative;overflow:hidden;background:#020817;color:#fff}
        .show-backdrop{position:fixed;inset:0;background:radial-gradient(circle at 15% 10%,rgba(0,198,255,.18),transparent 30%),radial-gradient(circle at 90% 80%,rgba(255,107,26,.22),transparent 34%),rgba(2,8,23,.72);backdrop-filter:blur(13px);z-index:1}
        .show-modal{position:relative;z-index:2;width:min(1120px,96vw);max-height:92vh;overflow:auto;background:linear-gradient(180deg,rgba(8,22,52,.98),rgba(5,17,42,.98));border:1px solid rgba(80,132,255,.24);border-radius:24px;box-shadow:0 32px 90px rgba(0,0,0,.42);padding:0;color:#fff}
        .show-modal-header{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:28px 32px 22px;border-bottom:1px solid rgba(255,255,255,.08)}
        .show-title-wrap{display:flex;gap:14px;align-items:center}.show-icon{width:48px;height:48px;border-radius:15px;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#ff6b1a,#ff3d3d);box-shadow:0 16px 34px rgba(255,107,26,.25);font-size:24px;flex:0 0 auto}.show-title{margin:0;font-size:26px;line-height:1.15;font-weight:800;color:#fff}.show-subtitle{margin-top:6px;font-size:13px;color:rgba(255,255,255,.65);font-weight:700}.show-close{width:38px;height:38px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;font-size:18px;transition:.2s ease;flex:0 0 auto}.show-close:hover{background:rgba(255,255,255,.12);transform:translateY(-1px)}
        .show-modal-body{padding:26px 32px 8px}.show-section{margin-bottom:24px}.show-section-title{display:flex;align-items:center;gap:10px;font-size:17px;font-weight:800;color:#fff;margin-bottom:16px}.show-section-title i{color:#00c6ff;font-size:20px}.show-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.show-field{background:rgba(255,255,255,.075);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:16px}.show-field label{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:rgba(255,255,255,.62);font-weight:800;margin-bottom:8px}.show-field input,.show-field select,.show-field textarea{width:100%;border:1px solid rgba(255,255,255,.13);background:rgba(255,255,255,.06);color:#fff;border-radius:13px;padding:13px 14px;outline:none;font-weight:700}.show-field textarea{min-height:92px;resize:vertical}.show-field input::placeholder,.show-field textarea::placeholder{color:rgba(255,255,255,.35)}.show-field select option{background:#0b1735;color:#fff}.show-span-2{grid-column:span 2}.show-muted{font-size:12px;color:rgba(255,255,255,.58);margin-top:8px}.show-error{background:rgba(239,68,68,.16);border:1px solid rgba(239,68,68,.32);color:#fecaca;border-radius:16px;padding:14px 16px;margin-bottom:18px;font-weight:700}.show-footer{display:flex;justify-content:flex-end;gap:10px;padding:22px 32px 28px;border-top:1px solid rgba(255,255,255,.08);flex-wrap:wrap}.show-btn{min-height:44px;padding:0 18px;border-radius:13px;border:1px solid rgba(255,255,255,.12);display:inline-flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;font-weight:800;color:#fff;cursor:pointer}.show-btn-back{background:rgba(255,255,255,.06)}.show-btn-primary{background:linear-gradient(135deg,#06b6d4,#00c6ff);border-color:rgba(0,198,255,.32)}.show-btn-reset{background:rgba(255,255,255,.06)}
        .guarantee-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.guarantee-card{display:flex;align-items:center;gap:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.065);border-radius:15px;padding:13px}.guarantee-card.disabled{opacity:.45}.guarantee-card input{width:18px;height:18px;accent-color:#00c6ff}.guarantee-title{font-weight:800}.guarantee-meta{font-size:12px;color:rgba(255,255,255,.58);margin-top:3px}.pill{display:inline-flex;align-items:center;border-radius:999px;padding:4px 9px;font-size:11px;font-weight:800;margin-left:8px}.pill-basic{background:rgba(16,185,129,.14);color:#6ee7b7}.pill-option{background:rgba(255,107,26,.16);color:#ffb383}.pill-off{background:rgba(148,163,184,.14);color:#cbd5e1}.category-fields{display:none}.category-fields.active{display:block}.check-line{display:flex!important;align-items:center!important;gap:10px!important;text-transform:none!important;letter-spacing:0!important;font-size:14px!important;color:#fff!important}.check-line input{width:18px!important;height:18px!important;accent-color:#00c6ff}.client-preview{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:12px}.mini-info{background:rgba(255,255,255,.055);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:10px}.mini-info span{display:block;font-size:10px;text-transform:uppercase;color:rgba(255,255,255,.5);font-weight:800}.mini-info strong{display:block;margin-top:3px;font-size:13px;color:#fff}.detail-error{display:block;color:#fca5a5;font-size:12px;font-weight:700;margin-top:6px;min-height:16px}
        @media(max-width:760px){.show-grid,.guarantee-list,.client-preview{grid-template-columns:1fr}.show-span-2{grid-column:auto}.show-modal-header,.show-modal-body,.show-footer{padding-left:20px;padding-right:20px}.show-title{font-size:22px}}
    </style>
</head>
<body>
<div class="layout">
    <?php require_once __DIR__.'/assets/includes/sidebar.php'; ?>
    <main class="main">
<div class="show-page">
    <div class="show-backdrop"></div>
    <form class="show-modal" method="POST" novalidate onsubmit="return validateAddContratAgent()">
        <div class="show-modal-header">
            <div class="show-title-wrap">
                <div class="show-icon"><i class="bi bi-file-earmark-plus"></i></div>
                <div>
                    <h1 class="show-title">Ajouter un contrat</h1>
                    <div class="show-subtitle">Demande saisie par agent pour un client</div>
                </div>
            </div>
            <a class="show-close" href="contrats_back.php"><i class="bi bi-x"></i></a>
        </div>

        <div class="show-modal-body">
            <?php if (!empty($errors)): ?>
                <div class="show-error">
                    <?php foreach ($errors as $error): ?>
                        <div><i class="bi bi-exclamation-triangle"></i> <?= h($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <section class="show-section">
                <div class="show-section-title"><i class="bi bi-person-badge"></i> Client</div>
                <div class="show-grid">
                    <div class="show-field show-span-2">
                        <label>Client *</label>
                        <select name="id_client" id="id_client">
                            <option value="">— Choisir un client —</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= (int)$client['id_user'] ?>" <?= ((int)($_POST['id_client'] ?? 0) === (int)$client['id_user']) ? 'selected' : '' ?>>
                                    #<?= (int)$client['id_user'] ?> — <?= h(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? '') . ' — ' . ($client['email'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="detail-error" id="error_id_client"></span>
                        <div class="client-preview" id="clientPreview" style="display:none;">
                            <div class="mini-info"><span>Email</span><strong id="clientEmail">—</strong></div>
                            <div class="mini-info"><span>Téléphone</span><strong id="clientTel">—</strong></div>
                            <div class="mini-info"><span>Adresse</span><strong id="clientAdresse">—</strong></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="show-section">
                <div class="show-section-title"><i class="bi bi-layers"></i> Catégorie et formule</div>
                <div class="show-grid">
                    <div class="show-field">
                        <label>Catégorie *</label>
                        <select name="id_categorie" id="id_categorie">
                            <option value="">— Choisir une catégorie —</option>
                            <?php foreach ($categories as $categorie): ?>
                                <option value="<?= (int)$categorie['id_categorie'] ?>" <?= ((int)($_POST['id_categorie'] ?? 0) === (int)$categorie['id_categorie']) ? 'selected' : '' ?>><?= h($categorie['nom_categorie'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="detail-error" id="error_id_categorie"></span>
                    </div>
                    <div class="show-field">
                        <label>Formule *</label>
                        <select name="id_formule" id="id_formule">
                            <option value="">— Choisir une formule —</option>
                        </select>
                        <span class="detail-error" id="error_id_formule"></span>
                    </div>
                    <div class="show-field">
                        <label>Prime automatique</label>
                        <input type="text" id="prime_preview" readonly placeholder="Depuis formule">
                    </div>
                    <div class="show-field">
                        <label>Franchise automatique</label>
                        <input type="text" id="franchise_preview" readonly placeholder="Depuis formule">
                    </div>
                </div>
            </section>

            <section class="show-section" id="garantiesSection" style="display:none;">
                <div class="show-section-title"><i class="bi bi-shield-check"></i> Garanties associées à la formule</div>
                <div class="guarantee-list" id="garantiesList"></div>
            </section>

            <section class="show-section">
                <div class="show-section-title"><i class="bi bi-calendar-check"></i> Dates et statut</div>
                <div class="show-grid">
                    <div class="show-field">
                        <label>Date début *</label>
                        <input type="date" name="date_debut_contrat" id="date_debut" value="<?= h($_POST['date_debut_contrat'] ?? date('Y-m-d')) ?>">
                        <span class="detail-error" id="error_date_debut"></span>
                    </div>
                    <div class="show-field">
                        <label>Date fin *</label>
                        <input type="date" name="date_fin_contrat" id="date_fin" value="<?= h($_POST['date_fin_contrat'] ?? date('Y-m-d', strtotime('+1 year'))) ?>">
                        <span class="detail-error" id="error_date_fin"></span>
                    </div>
                    <div class="show-field show-span-2">
                        <label>Statut</label>
                        <select name="statut_contrat" id="statut_contrat">
                            <?php foreach (['en attente','actif','expiré','résilié','refusé'] as $s): ?>
                                <option value="<?= h($s) ?>" <?= (($_POST['statut_contrat'] ?? 'en attente') === $s) ? 'selected' : '' ?>><?= h(ucfirst($s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </section>

            <section class="show-section">
                <div class="show-section-title"><i class="bi bi-person-lines-fill"></i> Coordonnées de l’assuré</div>
                <div class="show-grid">
                    <div class="show-field"><label>Identité *</label><select name="identite" id="identite"><option value="">— Veuillez choisir une option —</option><option value="Monsieur">Monsieur</option><option value="Madame">Madame</option></select><span class="detail-error"></span></div>
                    <div class="show-field"><label>Email *</label><input type="email" name="email" id="email" class="detail-input" placeholder="adresse@email.com"><span class="detail-error"></span></div>
                    <div class="show-field"><label>Nom *</label><input type="text" name="nom" id="nom" class="detail-input" placeholder="Nom"><span class="detail-error"></span></div>
                    <div class="show-field"><label>Prénom *</label><input type="text" name="prenom" id="prenom" class="detail-input" placeholder="Prénom"><span class="detail-error"></span></div>
                    <div class="show-field"><label>Téléphone *</label><input type="text" name="telephone" id="telephone" class="detail-input" placeholder="Ex: 93981981"><span class="detail-error"></span></div>
                    <div class="show-field"><label>Date naissance *</label><input type="date" name="date_naissance" id="date_naissance" class="detail-input"><span class="detail-error"></span></div>
                    <div class="show-field"><label>Nationalité *</label><select name="nationalite" id="nationalite" class="detail-input"><option value="">— Veuillez choisir une option —</option><option>Tunisienne</option><option>Française</option><option>Algérienne</option><option>Autre</option></select><span class="detail-error"></span></div>
                    <div class="show-field"><label>Situation professionnelle *</label><select name="situation_professionnelle" id="situation_professionnelle" class="detail-input"><option value="">— Veuillez choisir une option —</option><option>Salarié</option><option>Étudiant</option><option>Fonctionnaire</option><option>Indépendant</option><option>Retraité</option><option>Sans activité</option></select><span class="detail-error"></span></div>
                    <div class="show-field"><label>Adresse *</label><input type="text" name="adresse" id="adresse" class="detail-input" placeholder="Adresse"><span class="detail-error"></span></div>
                    <div class="show-field"><label>Situation matrimoniale *</label><select name="situation_matrimoniale" id="situation_matrimoniale" class="detail-input"><option value="">— Veuillez choisir une option —</option><option>Célibataire</option><option>Marié(e)</option><option>Divorcé(e)</option><option>Veuf / Veuve</option></select><span class="detail-error"></span></div>
                    <div class="show-field show-span-2"><label>Revenu annuel *</label><select name="revenu_annuel" id="revenu_annuel" class="detail-input"><option value="">— Veuillez choisir une option —</option><option>Moins de 10 000 DT</option><option>10 000 - 20 000 DT</option><option>20 000 - 40 000 DT</option><option>Plus de 40 000 DT</option></select><span class="detail-error"></span></div>
                </div>
            </section>

            <section class="show-section category-fields" id="fields-auto">
                <div class="show-section-title"><i class="bi bi-car-front"></i> Informations véhicule</div>
                <div class="show-grid">
                    <div class="show-field"><label>Immatriculation *</label><input type="text" name="immatriculation" class="category-input" placeholder="Ex : 123 TUN 4567"><span class="detail-error"></span></div>
                    <div class="show-field"><label>Marque *</label><select name="marque" class="category-input"><option value="">— Veuillez choisir une option —</option><option>Toyota</option><option>Hyundai</option><option>Kia</option><option>Peugeot</option><option>Renault</option><option>Volkswagen</option><option>Mercedes</option><option>BMW</option></select><span class="detail-error"></span></div>
                    <div class="show-field"><label>Usage du véhicule *</label><select name="usage_vehicule" class="category-input"><option value="">— Veuillez choisir une option —</option><option>Personnel</option><option>Professionnel</option><option>Mixte</option><option>Transport</option></select><span class="detail-error"></span></div>
                    <div class="show-field"><label>Kilométrage *</label><select name="kilometrage" class="category-input"><option value="">— Veuillez choisir une option —</option><option>Moins de 10 000 km</option><option>10 000 - 20 000 km</option><option>20 000 - 30 000 km</option><option>Plus de 30 000 km</option></select><span class="detail-error"></span></div>
                    <div class="show-field"><label>Puissance du véhicule (CV) *</label><input type="number" name="puissance" class="category-input" placeholder="Ex : 75"><span class="detail-error"></span></div>
                    <div class="show-field"><label>Date de première circulation *</label><input type="date" name="date_circulation" class="category-input"><span class="detail-error"></span></div>
                    <div class="show-field"><label>Valeur vénale *</label><input type="number" name="valeur_venale" class="category-input" placeholder="Valeur marchande en DT"><span class="detail-error"></span></div>
                    <div class="show-field"><label>Financement *</label><select name="financement" class="category-input"><option value="">— Veuillez choisir une option —</option><option>Achat comptant</option><option>Crédit bancaire</option><option>Leasing</option></select><span class="detail-error"></span></div>
                    <div class="show-field"><label>Estimation kilométrage annuel parcouru</label><select name="estimation_km"><option value="">— Veuillez choisir une option —</option><option>Moins de 10 000 km</option><option>10 000 - 20 000 km</option><option>20 000 - 30 000 km</option><option>Plus de 30 000 km</option></select></div>
                    <div class="show-field"><label>Le ou les conducteurs du véhicule</label><select name="conducteurs"><option value="">— Veuillez choisir une option —</option><option>Conducteur unique</option><option>Conducteur + conjoint</option><option>Conducteurs multiples</option></select></div>
                    <div class="show-field"><label>Mode de stationnement la nuit</label><select name="stationnement"><option value="">— Veuillez choisir une option —</option><option>Garage privé</option><option>Parking collectif</option><option>Voie publique</option></select></div>
                    <div class="show-field"><label>Utilisation du véhicule</label><select name="utilisation"><option value="">— Veuillez choisir une option —</option><option>Déplacements quotidiens</option><option>Usage occasionnel</option><option>Longs trajets</option></select></div>
                    <div class="show-field"><label>Trajets prévus</label><select name="trajets_prevus"><option value="">— Veuillez choisir une option —</option><option>Ville</option><option>Ville + route</option><option>National</option><option>International</option></select></div>
                    <div class="show-field show-span-2"><label>Commentaires / précisions</label><textarea name="details_formule" placeholder="Ajoutez des détails utiles sur le besoin du client..."></textarea></div>
                </div>
            </section>

            <section class="show-section category-fields" id="fields-habitation">
                <div class="show-section-title"><i class="bi bi-house"></i> Informations sur le logement</div>
                <div class="show-grid">
                    <div class="show-field"><label>Type de logement *</label><select name="type_logement" class="category-input"><option value="">— Veuillez choisir une option —</option><option>Appartement</option><option>Maison</option><option>Villa</option><option>Studio</option></select><span class="detail-error"></span></div>
                    <div class="show-field"><label>Statut d’occupation *</label><select name="statut_occupation" class="category-input"><option value="">— Veuillez choisir une option —</option><option>Propriétaire</option><option>Locataire</option><option>Occupant à titre gratuit</option></select><span class="detail-error"></span></div>
                    <div class="show-field"><label>Adresse du logement *</label><input type="text" name="adresse_logement" class="category-input" placeholder="Adresse complète"><span class="detail-error"></span></div>
                    <div class="show-field"><label>Surface (m²) *</label><input type="number" name="surface_logement" class="category-input" placeholder="Surface en m²"><span class="detail-error"></span></div>
                    <div class="show-field"><label>Nombre de pièces *</label><input type="number" name="nb_pieces" class="category-input" placeholder="Nombre de pièces"><span class="detail-error"></span></div>
                    <div class="show-field"><label>Valeur estimée des biens *</label><input type="number" name="valeur_biens" class="category-input" placeholder="Valeur en DT"><span class="detail-error"></span></div>
                    <div class="show-field show-span-2"><label>Commentaires / précisions</label><textarea name="details_formule" placeholder="Ajoutez des détails utiles sur le logement..."></textarea></div>
                </div>
            </section>

            <section class="show-section category-fields" id="fields-sante">
                <div class="show-section-title"><i class="bi bi-heart-pulse"></i> Informations santé</div>
                <div class="show-grid">
                    <div class="show-field"><label>Type de couverture souhaitée *</label><select name="type_couverture" class="category-input"><option value="">— Veuillez choisir une option —</option><option>Individuelle</option><option>Couple</option><option>Familiale</option></select><span class="detail-error"></span></div>
                    <div class="show-field"><label>Nombre de bénéficiaires</label><select name="nombre_beneficiaires"><option value="">— Veuillez choisir une option —</option><option>1</option><option>2</option><option>3</option><option>4 ou plus</option></select></div>
                    <div class="show-field"><label>Antécédents médicaux importants</label><select name="antecedents"><option value="">— Veuillez choisir une option —</option><option>Aucun</option><option>Diabète</option><option>Hypertension</option><option>Asthme</option><option>Autre</option></select></div>
                    <div class="show-field"><label>Fréquence estimée des soins</label><select name="frequence_soins"><option value="">— Veuillez choisir une option —</option><option>Faible</option><option>Moyenne</option><option>Élevée</option></select></div>
                    <div class="show-field"><label>Besoin d’une couverture dentaire ?</label><select name="couverture_dentaire"><option value="">— Veuillez choisir une option —</option><option>Oui</option><option>Non</option></select></div>
                    <div class="show-field"><label>Besoin d’une couverture optique ?</label><select name="couverture_optique"><option value="">— Veuillez choisir une option —</option><option>Oui</option><option>Non</option></select></div>
                    <div class="show-field show-span-2"><label>Commentaires / précisions</label><textarea name="details_formule" placeholder="Ajoutez des détails utiles sur le besoin santé..."></textarea></div>
                </div>
            </section>

            <section class="show-section category-fields" id="fields-protection">
                <div class="show-section-title"><i class="bi bi-shield-lock"></i> Informations protection</div>
                <div class="show-grid">
                    <div class="show-field"><label>Type de protection *</label><select name="type_protection" class="category-input"><option value="">— Veuillez choisir une option —</option><option>Protection juridique</option><option>Protection financière</option><option>Protection identité</option><option>Protection achat en ligne</option></select><span class="detail-error"></span></div>
                    <div class="show-field"><label>Niveau de couverture *</label><select name="niveau_couverture" class="category-input"><option value="">— Veuillez choisir une option —</option><option>Standard</option><option>Avancé</option><option>Premium</option></select><span class="detail-error"></span></div>
                    <div class="show-field"><label>Montant souhaité (DT)</label><input type="number" name="montant_couverture" placeholder="Ex : 5000"></div>
                    <div class="show-field"><label>Durée du contrat</label><select name="duree_contrat"><option value="">— Veuillez choisir une option —</option><option>1 an</option><option>2 ans</option><option>3 ans</option></select></div>
                    <div class="show-field show-span-2"><label class="check-line"><input type="checkbox" name="couvrir_famille" value="oui"> Couvrir aussi les membres de la famille</label></div>
                    <div class="show-field show-span-2"><label>Commentaires / précisions</label><textarea name="details_formule" placeholder="Ajoutez des détails utiles sur le besoin protection..."></textarea></div>
                </div>
            </section>
        </div>

        <div class="show-footer">
            <a href="contrats_back.php" class="show-btn show-btn-back"><i class="bi bi-arrow-left"></i> Annuler</a>
            <button type="reset" class="show-btn show-btn-reset"><i class="bi bi-arrow-clockwise"></i> Réinitialiser</button>
            <button type="submit" class="show-btn show-btn-primary"><i class="bi bi-plus-circle"></i> Ajouter</button>
</div>
    </main>
</div>

<script>
const clients = <?= $clientsJson ?>;
const categories = <?= $categoriesJson ?>;
const formules = <?= $formulesJson ?>;
const garanties = <?= $garantiesJson ?>;

// 1. Auto-fill client info
document.getElementById('id_client').addEventListener('change', function() {
    const id = parseInt(this.value);
    const preview = document.getElementById('clientPreview');
    const client = clients.find(c => parseInt(c.id_user) === id);

    if (client) {
        preview.style.display = 'grid';
        document.getElementById('clientEmail').textContent = client.email || '—';
        document.getElementById('clientTel').textContent = client.telephone || '—';
        document.getElementById('clientAdresse').textContent = client.adresse || '—';

        // Fill form fields
        document.getElementById('email').value = client.email || '';
        document.getElementById('nom').value = client.nom || '';
        document.getElementById('prenom').value = client.prenom || '';
        document.getElementById('telephone').value = client.telephone || '';
        document.getElementById('date_naissance').value = client.date_naissance || '';
        document.getElementById('adresse').value = client.adresse || '';
        
        // Bonus: try to match identity
        // (Just a placeholder logic)
    } else {
        preview.style.display = 'none';
    }
});

// 2. Filter Formulas by Category
document.getElementById('id_categorie').addEventListener('change', function() {
    const idCat = parseInt(this.value);
    const selFormule = document.getElementById('id_formule');
    selFormule.innerHTML = '<option value="">— Choisir une formule —</option>';
    
    const filtered = formules.filter(f => parseInt(f.id_categorie) === idCat);
    filtered.forEach(f => {
        const opt = document.createElement('option');
        opt.value = f.id_formule;
        opt.textContent = f.nom_formule + ' (' + f.prix_formule + ' DT)';
        selFormule.appendChild(opt);
    });

    // Hide all category fields
    document.querySelectorAll('.category-fields').forEach(sec => sec.classList.remove('active'));
    
    // Show correct fields
    const cat = categories.find(c => parseInt(c.id_categorie) === idCat);
    if (cat) {
        const catKey = normalizeCategory(cat.nom_categorie);
        if (catKey.includes('auto')) document.getElementById('fields-auto').classList.add('active');
        else if (catKey.includes('habitation')) document.getElementById('fields-habitation').classList.add('active');
        else if (catKey.includes('sante')) document.getElementById('fields-sante').classList.add('active');
        else if (catKey.includes('protection')) document.getElementById('fields-protection').classList.add('active');
    }

    resetFormulePreviews();
});

function normalizeCategory(cat) {
    cat = cat.toLowerCase().trim();
    cat = cat.replace(/[éèê]/g, 'e').replace(/[àâ]/g, 'a').replace(/[ùû]/g, 'u').replace(/[îï]/g, 'i').replace(/[ô]/g, 'o');
    return cat;
}

// 3. Update Previews & Garanties on Formula change
document.getElementById('id_formule').addEventListener('change', function() {
    const idF = parseInt(this.value);
    const f = formules.find(x => parseInt(x.id_formule) === idF);
    
    if (f) {
        document.getElementById('prime_preview').value = f.prix_formule + ' DT';
        document.getElementById('franchise_preview').value = f.franchise_formule + ' DT';
        renderGaranties(idF);
    } else {
        resetFormulePreviews();
    }
});

function resetFormulePreviews() {
    document.getElementById('prime_preview').value = '';
    document.getElementById('franchise_preview').value = '';
    document.getElementById('garantiesSection').style.display = 'none';
    document.getElementById('garantiesList').innerHTML = '';
}

function renderGaranties(idFormule) {
    const section = document.getElementById('garantiesSection');
    const list = document.getElementById('garantiesList');
    const gs = garanties[idFormule] || [];
    
    if (gs.length === 0) {
        section.style.display = 'none';
        return;
    }

    section.style.display = 'block';
    list.innerHTML = gs.map(g => {
        const niveau = (g.niveau_couvert_garantie || 'basique').toLowerCase();
        const isOption = niveau === 'option';
        const pillClass = isOption ? 'pill-option' : 'pill-basic';
        const pillText = isOption ? 'Option' : 'Incluse';
        const checkedAttr = !isOption ? 'checked disabled' : '';
        const nameAttr = isOption ? 'name="garanties_optionnelles[]"' : '';

        return `
            <div class="guarantee-card">
                <input type="checkbox" ${nameAttr} value="${g.id_garantie}" ${checkedAttr}>
                <div style="flex:1">
                    <div class="guarantee-title">${g.nom_garantie} <span class="pill ${pillClass}">${pillText}</span></div>
                    <div class="guarantee-meta">Plafond: ${g.plafond_couvert_garantie} DT</div>
                </div>
            </div>
        `;
    }).join('');
}

// 4. Validation
function validateAddContratAgent() {
    let hasError = false;
    const errs = document.querySelectorAll('.detail-error');
    errs.forEach(e => e.textContent = '');

    const check = (id, msg) => {
        const el = document.getElementById(id);
        if (!el || !el.value.trim()) {
            const err = document.getElementById('error_' + id) || (el ? el.nextElementSibling : null);
            if (err) err.textContent = msg;
            hasError = true;
        }
    };

    check('id_client', 'Veuillez choisir un client.');
    check('id_categorie', 'Veuillez choisir une catégorie.');
    check('id_formule', 'Veuillez choisir une formule.');
    check('date_debut', 'Date de début obligatoire.');
    check('date_fin', 'Date de fin obligatoire.');

    const d1 = document.getElementById('date_debut').value;
    const d2 = document.getElementById('date_fin').value;
    if (d1 && d2 && new Date(d1) >= new Date(d2)) {
        const err = document.getElementById('error_date_fin') || document.getElementById('date_fin').nextElementSibling;
        if (err) err.textContent = 'La date de fin doit être postérieure à la date de début.';
        hasError = true;
    }

    if (hasError) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return false;
    }
    return true;
}
</script>
</body>
</html>
