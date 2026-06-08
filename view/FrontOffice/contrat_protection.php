<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../connexion.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

$idUser = (int) $_SESSION['user_id'];
$clientDb = config::getConnexion();
$userStmt = $clientDb->prepare("
    SELECT id_user, nom, prenom, email, telephone, adresse, date_naissance
    FROM `user`
    WHERE id_user = :id_user
    LIMIT 1
");
$userStmt->execute(['id_user' => $idUser]);
$userConnecte = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$userConnecte) {
    header('Location: login.html');
    exit();
}


// Nombre de contrats du client connecté pour le badge navbar.
$nbContratsClient = 0;
try {
    $contratOwnerColumn = 'id_user';
    $colStmt = $clientDb->prepare("\n        SELECT COUNT(*)\n        FROM information_schema.COLUMNS\n        WHERE TABLE_SCHEMA = DATABASE()\n          AND TABLE_NAME = 'contrat'\n          AND COLUMN_NAME = 'id_user'\n    ");
    $colStmt->execute();
    if ((int)$colStmt->fetchColumn() === 0) {
        $contratOwnerColumn = 'id_client';
    }

    $countStmt = $clientDb->prepare("SELECT COUNT(*) FROM contrat WHERE {$contratOwnerColumn} = :id_user");
    $countStmt->execute(['id_user' => $idUser]);
    $nbContratsClient = (int)$countStmt->fetchColumn();
} catch (Throwable $e) {
    $nbContratsClient = 0;
}

$clientNom = $userConnecte['nom'] ?? ($_SESSION['nom'] ?? '');
$clientPrenom = $userConnecte['prenom'] ?? ($_SESSION['prenom'] ?? '');
$clientEmail = $userConnecte['email'] ?? ($_SESSION['email'] ?? '');
$clientTelephone = preg_replace('/\D+/', '', (string)($userConnecte['telephone'] ?? ''));
if (strlen($clientTelephone) > 8) {
    $clientTelephone = substr($clientTelephone, -8);
}
$clientAdresse = $userConnecte['adresse'] ?? '';
$clientDateNaissance = $userConnecte['date_naissance'] ?? '';

$db = config::getConnexion();

function h(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function slugify(string $text): string {
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = strtolower((string) $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'formule';
}

function currentDatabase(PDO $db): string {
    return (string) $db->query("SELECT DATABASE()")->fetchColumn();
}

function columnExists(PDO $db, string $table, string $column): bool {
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = :db_name
          AND TABLE_NAME = :table_name
          AND COLUMN_NAME = :column_name
    ");
    $stmt->execute([
        'db_name' => currentDatabase($db),
        'table_name' => $table,
        'column_name' => $column,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function getFormulaLevelColumn(PDO $db): ?string {
    $possible = ['niveau_formule', 'niveau', 'niveau_couverture'];

    foreach ($possible as $column) {
        if (columnExists($db, 'formule', $column)) {
            return $column;
        }
    }

    return null;
}

function getGarantieFormulaColumn(PDO $db): ?string {
    $possible = ['id_formule', 'formule_id'];

    foreach ($possible as $column) {
        if (columnExists($db, 'garantie', $column)) {
            return $column;
        }
    }

    return null;
}

function normalizeGarantieLevel(?string $value): string {
    $value = strtolower(trim((string) $value));

    if ($value === '') {
        return 'basique';
    }

    if (in_array($value, ['basique', 'base', 'inclus', 'incluse', 'fixe', 'standard', 'obligatoire'], true)) {
        return 'basique';
    }

    if (in_array($value, ['option', 'optionnel', 'optionnelle', 'facultatif', 'facultative'], true)) {
        return 'option';
    }

    if (in_array($value, ['non disponible', 'indisponible', 'aucun', 'non inclus', 'exclu'], true)) {
        return 'non disponible';
    }

    return $value;
}

function garantieIcon(string $level): string {
    return match ($level) {
        'basique' => 'bi bi-check2-circle',
        'option' => 'bi bi-plus-circle',
        'non disponible' => 'bi bi-x-circle',
        default => 'bi bi-check2-circle',
    };
}

function garantieStatusTag(string $level): string {
    return match ($level) {
        'basique' => '<strong>(basique)</strong>',
        'option' => '<span>(option)</span>',
        'non disponible' => '<span>(non disponible)</span>',
        default => '<span>(' . h($level) . ')</span>',
    };
}

$formuleLevelColumn = getFormulaLevelColumn($db);

$categorieStmt = $db->prepare("SELECT * FROM categorie WHERE LOWER(nom_categorie) = 'protection' LIMIT 1");
$categorieStmt->execute();
$categorie = $categorieStmt->fetch(PDO::FETCH_ASSOC) ?: null;

$formules = [];
$garantiesByFormule = [];
$schemaMessage = null;

if ($categorie) {
    $sqlFormules = "SELECT * FROM formule WHERE id_categorie = :id_categorie ORDER BY id_formule ASC";
    $stmtFormules = $db->prepare($sqlFormules);
    $stmtFormules->execute(['id_categorie' => $categorie['id_categorie']]);
    $formules = $stmtFormules->fetchAll(PDO::FETCH_ASSOC);

    $sqlGaranties = "
        SELECT
            fg.id_formule,
            g.id_garantie,
            g.nom_garantie,
            g.description_garantie,
            g.plafond_couvert_garantie,
            fg.niveau_couvert_garantie,
            g.id_categorie
        FROM formule_garantie fg
        INNER JOIN garantie g ON g.id_garantie = fg.id_garantie
        INNER JOIN formule f ON f.id_formule = fg.id_formule
        WHERE f.id_categorie = :id_categorie
        ORDER BY f.id_formule ASC, g.id_garantie ASC
    ";
    $stmtGaranties = $db->prepare($sqlGaranties);
    $stmtGaranties->execute(['id_categorie' => $categorie['id_categorie']]);

    foreach ($stmtGaranties->fetchAll(PDO::FETCH_ASSOC) as $garantie) {
        $formuleId = (int) ($garantie['id_formule'] ?? 0);
        $garantiesByFormule[$formuleId][] = $garantie;
    }
}

$iconClasses = ['icon-classique', 'icon-tierce', 'icon-risque'];
$iconSymbols = ['bi bi-shield-check', 'bi bi-shield-plus', 'bi bi-stars'];
$profileDefaults = ['Protection de base', 'Usage régulier', 'Protection maximale'];

$highlightIndex = count($formules) >= 2 ? 1 : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Assurance Protection — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Style contrats spécifique (base) -->
    <link rel="stylesheet" href="user/assets_contrats/css/variables.css">
    <link rel="stylesheet" href="user/assets_contrats/css/base.css">
    <link rel="stylesheet" href="user/assets_contrats/css/layout.css">
    <link rel="stylesheet" href="user/assets_contrats/css/client.css">
    <link rel="stylesheet" href="user/assets_contrats/css/contrat.css">

    <!-- Style dashboard User : override navbar/avatar comme client.html -->
    <link rel="stylesheet" href="user/css/variables.css">
    <link rel="stylesheet" href="user/css/base.css">
    <link rel="stylesheet" href="user/css/layout.css">
    <link rel="stylesheet" href="user/css/client.css">
    <link rel="stylesheet" href="user/css/animations.css">

<script src="user/js/main.js"></script>
<script src="user/assets_contrats/js/main.js"></script>

</head>
<body>

<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">
    <!-- ===== NAVBAR ===== -->
    <?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

   <main class="main protection-page">
        <div class="page-header">
            <div>
                <div class="page-title-main">Assurance Protection</div>
                <div class="page-breadcrumb">
                    <i class="bi bi-house"></i>
                    <a href="client.php" style="color:inherit;text-decoration:none;">Accueil</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <a href="contrat.php" style="color:inherit;text-decoration:none;">Contrats</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <span>Protection</span>
                </div>
            </div>
        </div>

        <section class="auto-hero">
            <div class="hero-content">
                <div class="hero-chip">
                    <i class="bi bi-shield-lock"></i>
                    Protection personnelle flexible
                </div>

                <h1 class="hero-title">Choisissez la formule protection qui vous rassure</h1>

                <p class="hero-text">
                    Comparez les niveaux de couverture, découvrez les garanties incluses
                    et sélectionnez la formule protection la plus adaptée à votre profil,
                    à votre situation et à votre niveau de sécurité recherché.
                </p>

                <div class="hero-actions">
                    <a href="#formules-protection" class="hero-btn primary">
                        <i class="bi bi-lightning-charge-fill"></i>
                        Voir les formules
                    </a>

                    <a href="contrat.php" class="hero-btn secondary">
                        <i class="bi bi-arrow-left"></i>
                        Retour aux catégories
                    </a>
                </div>
            </div>

            <div class="hero-side">
                <div class="hero-glass">
                    <h3>Pourquoi choisir Protex Protection ?</h3>
                    <ul class="hero-points">
                        <li><i class="bi bi-check-circle-fill"></i><span>Des formules claires selon le niveau de protection souhaité.</span></li>
                        <li><i class="bi bi-check-circle-fill"></i><span>Des garanties visibles avant la demande.</span></li>
                        <li><i class="bi bi-check-circle-fill"></i><span>Un parcours simple avec ouverture du formulaire après le choix.</span></li>
                        <li><i class="bi bi-check-circle-fill"></i><span>Une expérience plus propre, plus moderne et mieux organisée.</span></li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="section-block" id="formules-protection">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Nos formules protection</h2>
                    <p class="section-subtitle">Comparez les formules disponibles et choisissez celle qui correspond à votre besoin.</p>
                </div>
            </div>

            <?php if (!$categorie): ?>
                <div class="empty-contracts" style="display:block; text-align:left;">
                    <strong>Catégorie introuvable.</strong><br>
                    La catégorie Protection n’existe pas encore dans la base.
                </div>
            <?php elseif (empty($formules)): ?>
                <div class="empty-contracts" style="display:block; text-align:left;">
                    <strong>Aucune formule trouvée.</strong><br>
                    Ajoutez des formules dans la catégorie Protection depuis le back-office.
                </div>
            <?php else: ?>
                <div class="formules-grid">
                    <?php foreach ($formules as $index => $formule): ?>
                        <?php
                            $formuleId = (int) $formule['id_formule'];
                            $isHighlight = ($index === $highlightIndex);
                            $iconClass = $iconClasses[$index % count($iconClasses)];
                            $iconSymbol = $iconSymbols[$index % count($iconSymbols)];
                            $slug = slugify($formule['nom_formule']);
                            $niveau = $formuleLevelColumn && !empty($formule[$formuleLevelColumn]) ? $formule[$formuleLevelColumn] : (($index === 0) ? 'Essentiel' : (($index === 1) ? 'Intermédiaire' : 'Premium'));
                            $profil = $profileDefaults[$index % count($profileDefaults)];
                            $garanties = $garantiesByFormule[$formuleId] ?? [];
                        ?>
                        <article class="formule-card<?= $isHighlight ? ' highlight' : '' ?>">
                            <?php if (!empty($niveau)): ?>
                                <span class="badge-top"><?= h($niveau) ?></span>
                            <?php endif; ?>

                            <div class="formule-icon <?= h($iconClass) ?>">
                                <i class="<?= h($iconSymbol) ?>"></i>
                            </div>

                            <h3 class="formule-name"><?= h($formule['nom_formule']) ?></h3>
                            <p class="formule-desc"><?= h($formule['description_formule']) ?></p>

                            <div class="mini-meta">
                                <div class="meta-box">
                                    <span class="meta-label">Profil conseillé</span>
                                    <span class="meta-value"><?= h($profil) ?></span>
                                </div>
                                <div class="meta-box">
                                    <span class="meta-label">Prix</span>
                                    <span class="meta-value"><?= number_format((float) ($formule['prix_formule'] ?? 0), 2, '.', ' ') ?> DT/Mois</span>
                                </div>
                            </div>

                            <ul class="garantie-list">
                                <?php if (!empty($garanties)): ?>
                                    <?php foreach ($garanties as $garantie): ?>
                                        <?php $niveauGarantie = normalizeGarantieLevel($garantie['niveau_couvert_garantie'] ?? ''); ?>
                                        <li>
                                            <i class="<?= h(garantieIcon($niveauGarantie)) ?>"></i>
                                            <?= h($garantie['nom_garantie'] ?? 'Garantie') ?>
                                            <?= garantieStatusTag($niveauGarantie) ?>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li><i class="bi bi-check2-circle"></i> Description <strong><?= h($formule['description_formule']) ?></strong></li>
                                    <li><i class="bi bi-check2-circle"></i> Catégorie <strong><?= h($categorie['nom_categorie']) ?></strong></li>
                                    <li><i class="bi bi-check2-circle"></i> Niveau <strong><?= h($niveau) ?></strong></li>
                                <?php endif; ?>
                            </ul>

                            <div class="formule-footer">
                                <button type="button" class="choose-btn" onclick="openProtectionModal('<?= h($formule['nom_formule']) ?>')">
                                    Choisir cette formule
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($schemaMessage): ?>
                    <div class="hint-box" style="margin-top:18px;">
                        <?= h($schemaMessage) ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <section class="explication-box">
            <article class="info-card">
                <h3>Comment ça marche ?</h3>
                <div class="info-steps">
                    <div class="info-step"><span>1</span><p>Comparez les formules protection disponibles.</p></div>
                    <div class="info-step"><span>2</span><p>Choisissez la formule qui correspond à votre besoin.</p></div>
                    <div class="info-step"><span>3</span><p>Remplissez votre demande dans le formulaire de souscription.</p></div>
                </div>
            </article>

            <article class="info-card">
                <h3>Pourquoi cette approche ?</h3>
                <p>
                    L’objectif est de vous laisser comparer clairement les niveaux de protection
                    avant d’ouvrir le formulaire, afin de rendre le parcours plus fluide,
                    plus lisible et plus rassurant.
                </p>
            </article>
        </section>
    </main>
</div>

<div id="protectionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div>
                <h1>Demande d’assurance protection</h1>
                <p>Complétez les informations nécessaires pour préparer votre contrat.</p>
            </div>
            <button type="button" class="close-btn" onclick="closeProtectionModal()">&times;</button>
        </div>

        <div class="modal-body">
            <form id="contratProtectionForm" method="post" action="saveContratClient.php" novalidate>
                <input type="hidden" name="type_contrat" value="Protection">
                <input type="hidden" name="id_categorie" value="<?= h($categorie['id_categorie'] ?? '') ?>">
                <input type="hidden" id="id_formule" name="id_formule">

                <div class="form-section">
                    <h2 class="form-section-title">I - Couvertures souhaitées</h2>

                    <div class="form-grid-1">
                        <div class="form-group">
                            <label for="formule">Formule choisie <span class="req">*</span></label>
                            <select class="form-select" id="formule" name="formule" onchange="toggleCoveragePanels(); updateFormuleContractInfo();">
                                <option value="">— Veuillez choisir une option —</option>
                                <?php foreach ($formules as $formule): ?>
                                    <option value="<?= h($formule['nom_formule']) ?>"><?= h($formule['nom_formule']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="error-message" id="error_formule"></div>
                        </div>
                    </div>

                    <?php foreach ($formules as $index => $formule): ?>
                        <?php
                            $formuleId = (int) $formule['id_formule'];
                            $slug = slugify($formule['nom_formule']);
                            $garanties = $garantiesByFormule[$formuleId] ?? [];
                        ?>
                        <div id="panel-<?= h($slug) ?>" class="coverage-panel">
                            <h3>Garanties de la formule <?= h($formule['nom_formule']) ?></h3>

                            <?php if (!empty($garanties)): ?>
                                <div class="check-grid">
                                    <?php foreach ($garanties as $garantie): ?>
                                        <?php
                                            $niveauGarantie = normalizeGarantieLevel($garantie['niveau_couvert_garantie'] ?? '');
                                            $isFixed = ($niveauGarantie === 'basique');
                                            $isDisabled = ($niveauGarantie === 'non disponible');
                                        ?>
                                        <label class="check-item<?= $isFixed ? ' fixed' : '' ?><?= $isDisabled ? ' disabled' : '' ?>">
                                            <?php if ($isFixed): ?>
                                                <input type="hidden" name="garanties[]" value="<?= h($garantie['nom_garantie']) ?>">
                                            <?php endif; ?>
                                            <input
                                                type="checkbox"
                                                <?= $isFixed ? 'checked' : '' ?>
                                                <?= $isFixed || $isDisabled ? 'disabled' : 'name="garanties[]" value="' . h($garantie['nom_garantie']) . '"' ?>
                                            >
                                            <?= h($garantie['nom_garantie']) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <div class="hint-box">
                                    <?= h($formule['description_formule']) ?>
                                </div>
                            <?php else: ?>
                                <div class="hint-box">
                                    <?= h($formule['description_formule']) ?><br>
                                    <?php if ($schemaMessage): ?>
                                        <?= h($schemaMessage) ?>
                                    <?php else: ?>
                                        Aucune garantie liée à cette formule pour le moment.
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>


                    <div class="selected-contract-info" id="selectedContractInfo">
                        <h3 class="selected-contract-title">Informations du contrat sélectionné</h3>
                        <div class="form-grid-2 contrat-contract-summary" style="margin-top:0;">
                            <div class="form-group">
                                <label for="date_debut_contrat">Date début <span class="req">*</span></label>
                                <input type="date" class="form-control" id="date_debut_contrat" name="date_debut_contrat">
                                <div class="error-message" id="error_date_debut_contrat"></div>
                            </div>

                            <div class="form-group">
                                <label for="date_fin_contrat">Date fin <span class="req">*</span></label>
                                <input type="date" class="form-control" id="date_fin_contrat" name="date_fin_contrat">
                                <small style="display:block;margin-top:6px;color:#7b8798;">Par défaut : après un an. Vous pouvez la modifier.</small>
                                <div class="error-message" id="error_date_fin_contrat"></div>
                            </div>

                            <div class="form-group">
                                <label for="prime_affichee">Prime</label>
                                <input type="text" class="form-control" id="prime_affichee" readonly placeholder="Automatique selon la formule">
                                <input type="hidden" id="prime_contrat" name="prime_contrat">
                            </div>

                            <div class="form-group">
                                <label for="franchise_affichee">Franchise</label>
                                <input type="text" class="form-control" id="franchise_affichee" readonly placeholder="Automatique selon la formule">
                                <input type="hidden" id="franchise_contrat" name="franchise_contrat">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="form-section-title">II - Informations de protection</h2>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="type_protection">Type de protection <span class="req">*</span></label>
                            <select class="form-select" id="type_protection" name="type_protection">
                                <option value="">— Veuillez choisir une option —</option>
                                <option>Protection juridique</option>
                                <option>Protection financière</option>
                                <option>Protection identité</option>
                                <option>Protection achat en ligne</option>
                            </select>
                            <div class="error-message" id="error_type_protection"></div>
                        </div>

                        <div class="form-group">
                            <label for="niveau_couverture">Niveau de couverture <span class="req">*</span></label>
                            <select class="form-select" id="niveau_couverture" name="niveau_couverture">
                                <option value="">— Veuillez choisir une option —</option>
                                <option>Standard</option>
                                <option>Avancé</option>
                                <option>Premium</option>
                            </select>
                            <div class="error-message" id="error_niveau_couverture"></div>
                        </div>

                        <div class="form-group">
                            <label for="montant_couverture">Montant souhaité (DT)</label>
                            <input type="text" class="form-control" id="montant_couverture" name="montant_couverture" placeholder="Ex : 5000">
                            <div class="error-message" id="error_montant_couverture"></div>
                        </div>

                        <div class="form-group">
                            <label for="duree_contrat">Durée du contrat</label>
                            <select class="form-select" id="duree_contrat" name="duree_contrat">
                                <option value="">— Veuillez choisir une option —</option>
                                <option>1 an</option>
                                <option>2 ans</option>
                                <option>3 ans</option>
                            </select>
                            <div class="error-message" id="error_duree_contrat"></div>
                        </div>

                        <div class="form-group">
                            <label class="check-item" style="min-height:auto;">
                                <input type="checkbox" name="couvrir_famille" value="oui">
                                Couvrir aussi les membres de la famille
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="form-section-title">III - Coordonnées de l’assuré</h2>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="identite">Identité de l’adhérent <span class="req">*</span></label>
                            <select class="form-select" id="identite" name="identite">
                                <option value="">— Veuillez choisir une option —</option>
                                <option>Monsieur</option>
                                <option>Madame</option>
                            </select>
                            <div class="error-message" id="error_identite"></div>
                        </div>

                        <div class="form-group">
                            <label for="email">E-mail <span class="req">*</span></label>
                            <input type="text" class="form-control" id="email" name="email" value="<?= h($clientEmail) ?>" placeholder="Adresse e-mail">
                            <div class="error-message" id="error_email"></div>
                        </div>

                        <div class="form-group">
                            <label for="nom">Nom <span class="req">*</span></label>
                            <input type="text" class="form-control" id="nom" name="nom" value="<?= h($clientNom) ?>" placeholder="Nom de famille">
                            <div class="error-message" id="error_nom"></div>
                        </div>

                        <div class="form-group">
                            <label for="prenom">Prénom <span class="req">*</span></label>
                            <input type="text" class="form-control" id="prenom" name="prenom" value="<?= h($clientPrenom) ?>" placeholder="Prénom">
                            <div class="error-message" id="error_prenom"></div>
                        </div>

                        <div class="form-group">
                            <label for="telephone">N° de téléphone <span class="req">*</span></label>
                            <input type="text" class="form-control" id="telephone" name="telephone" value="<?= h($clientTelephone) ?>" placeholder="Votre numéro de téléphone">
                            <div class="error-message" id="error_telephone"></div>
                        </div>

                        <div class="form-group">
                            <label for="date_naissance">Date de naissance <span class="req">*</span></label>
                            <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="<?= h($clientDateNaissance) ?>">
                            <div class="error-message" id="error_date_naissance"></div>
                        </div>

                        <div class="form-group">
                            <label for="nationalite">Nationalité <span class="req">*</span></label>
                            <select class="form-select" id="nationalite" name="nationalite">
                                <option value="">— Veuillez choisir une option —</option>
                                <option>Tunisienne</option>
                                <option>Française</option>
                                <option>Algérienne</option>
                                <option>Autre</option>
                            </select>
                            <div class="error-message" id="error_nationalite"></div>
                        </div>
                            <div id="nationalite_autre_group" class="form-group nationalite-autre-group">
                                <label for="nationalite_autre">Précisez la nationalité <span class="req">*</span></label>
                                <input type="text" class="form-control" id="nationalite_autre" name="nationalite_autre" placeholder="Ex : Italienne">
                                <div class="error-message" id="error_nationalite_autre"></div>
                            </div>

                        <div class="form-group">
                            <label for="situation_professionnelle">Situation professionnelle <span class="req">*</span></label>
                            <select class="form-select" id="situation_professionnelle" name="situation_professionnelle">
                                <option value="">— Veuillez choisir une option —</option>
                                <option>Salarié</option>
                                <option>Étudiant</option>
                                <option>Fonctionnaire</option>
                                <option>Indépendant</option>
                                <option>Retraité</option>
                                <option>Sans activité</option>
                            </select>
                            <div class="error-message" id="error_situation_professionnelle"></div>
                        </div>

                        <div class="form-group">
                            <label for="adresse">Adresse personnelle principale <span class="req">*</span></label>
                            <input type="text" class="form-control" id="adresse" name="adresse" value="<?= h($clientAdresse) ?>" placeholder="Votre adresse personnelle">
                            <div class="error-message" id="error_adresse"></div>
                        </div>

                        <div class="form-group">
                            <label for="situation_matrimoniale">Situation matrimoniale</label>
                            <select class="form-select" id="situation_matrimoniale" name="situation_matrimoniale">
                                <option value="">— Veuillez choisir une option —</option>
                                <option>Célibataire</option>
                                <option>Marié(e)</option>
                                <option>Divorcé(e)</option>
                                <option>Veuf / Veuve</option>
                            </select>
                            <div class="error-message" id="error_situation_matrimoniale"></div>
                        </div>

                        <div class="form-group">
                            <label for="revenu_annuel">Niveau de revenu annuel brut en Dinars</label>
                            <select class="form-select" id="revenu_annuel" name="revenu_annuel">
                                <option value="">— Veuillez choisir une option —</option>
                                <option>Moins de 10 000 DT</option>
                                <option>10 000 - 20 000 DT</option>
                                <option>20 000 - 40 000 DT</option>
                                <option>Plus de 40 000 DT</option>
                            </select>
                            <div class="error-message" id="error_revenu_annuel"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-protex btn-light-protex" onclick="closeProtectionModal()">Annuler</button>
                    <button type="reset" class="btn-protex btn-light-protex">Réinitialiser</button>
                    <button type="submit" class="btn-protex btn-primary-protex">Valider votre demande</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    
const formuleMeta = <?= json_encode(array_column(array_map(function($f) {
    return [
        'nom' => $f['nom_formule'] ?? '',
        'id' => $f['id_formule'] ?? '',
        'prix' => $f['prix_formule'] ?? 0,
        'franchise' => $f['franchise_formule'] ?? 0,
    ];
}, $formules), null, 'nom'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const formulaPanels = {
        <?php foreach ($formules as $formule): ?>
            <?= json_encode($formule['nom_formule']) ?>: <?= json_encode('panel-' . slugify($formule['nom_formule'])) ?>,
        <?php endforeach; ?>
    };

    function openProtectionModal(formule = '') {
        const modal = document.getElementById('protectionModal');
        const formuleSelect = document.getElementById('formule');

        modal.classList.add('show');
        document.body.style.overflow = 'hidden';

        if (formuleSelect && formule) {
            formuleSelect.value = formule;
        }

        toggleCoveragePanels();
        setDefaultContractDates();
        updateFormuleContractInfo();

    setDefaultContractDates();
    updateFormuleContractInfo();

    const formuleSelectForInfo = getFormuleSelectElement();
    if (formuleSelectForInfo) {
        formuleSelectForInfo.addEventListener('change', updateFormuleContractInfo);
    }

    const currentForm = document.querySelector('form[id^="contrat"]');
    if (currentForm) {
        currentForm.addEventListener('submit', function(e) {
            if (!validateContractDatesBeforeSubmit()) e.preventDefault();
        });
    }
    }

    function closeProtectionModal() {
        document.getElementById('protectionModal').classList.remove('show');
        document.body.style.overflow = '';
    }

    function toggleCoveragePanels() {
        const select = document.getElementById('formule');
        const value = select ? select.value : '';

        Object.values(formulaPanels).forEach(function(panelId) {
            const panel = document.getElementById(panelId);
            if (panel) {
                panel.classList.remove('active');
            }
        });

        if (value && formulaPanels[value]) {
            const activePanel = document.getElementById(formulaPanels[value]);
            if (activePanel) {
                activePanel.classList.add('active');
            }
        }
    }

    
function getFormuleSelectElement() {
    return document.getElementById('formule') || document.getElementById('formule_habitation');
}

function setDefaultContractDates() {
    const debut = document.getElementById('date_debut_contrat');
    const fin = document.getElementById('date_fin_contrat');
    if (!debut || !fin) return;
    const today = new Date();
    const todayValue = today.toISOString().slice(0, 10);
    if (!debut.value) debut.value = todayValue;
    const nextYear = new Date(today);
    nextYear.setFullYear(nextYear.getFullYear() + 1);
    if (!fin.value) fin.value = nextYear.toISOString().slice(0, 10);
}

function updateFormuleContractInfo() {
    const select = getFormuleSelectElement();
    const selected = select ? select.value : '';
    const meta = (typeof formuleMeta !== 'undefined' && formuleMeta[selected]) ? formuleMeta[selected] : null;
    const idInput = document.getElementById('id_formule');
    const primeHidden = document.getElementById('prime_contrat');
    const franchiseHidden = document.getElementById('franchise_contrat');
    const primeView = document.getElementById('prime_affichee');
    const franchiseView = document.getElementById('franchise_affichee');
    if (idInput) idInput.value = meta ? meta.id : '';
    if (primeHidden) primeHidden.value = meta ? meta.prix : '';
    if (franchiseHidden) franchiseHidden.value = meta ? meta.franchise : '';
    if (primeView) primeView.value = meta ? `${parseFloat(meta.prix || 0).toFixed(2)} DT` : '';
    if (franchiseView) franchiseView.value = meta ? `${parseFloat(meta.franchise || 0).toFixed(2)} DT` : '';
}

function validateContractDatesBeforeSubmit() {
    const debut = document.getElementById('date_debut_contrat');
    const fin = document.getElementById('date_fin_contrat');
    const errorFin = document.getElementById('error_date_fin_contrat');
    if (!debut || !fin) return true;
    if (errorFin) errorFin.textContent = '';
    if (!debut.value || !fin.value) {
        if (errorFin) errorFin.textContent = 'Veuillez remplir la date début et la date fin.';
        return false;
    }
    if (fin.value <= debut.value) {
        if (errorFin) errorFin.textContent = 'La date fin doit être après la date début.';
        fin.focus();
        return false;
    }
    return true;
}


document.addEventListener('DOMContentLoaded', function () {
        const overlay = document.getElementById('protectionModal');
        if (overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target.id === 'protectionModal') {
                    closeProtectionModal();
                }
            });
        }
    });
</script>

<style>.input-invalid{border-color:#ef4444!important;box-shadow:0 0 0 3px rgba(239,68,68,.12)!important}.input-valid{border-color:#22c55e!important;box-shadow:0 0 0 3px rgba(34,197,94,.10)!important}.error-message{color:#ef4444;font-size:12px;margin-top:6px;display:block}</style>

<style>
.input-invalid{border-color:#ef4444!important;box-shadow:0 0 0 3px rgba(239,68,68,.14)!important}.input-valid{border-color:#22c55e!important;box-shadow:0 0 0 3px rgba(34,197,94,.10)!important}.error-message{color:#ef4444;font-size:12px;font-weight:600;margin-top:6px;display:block;line-height:1.35}
</style>
<script>
(function(){
'use strict';
const rules={email:/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/,letters:/^[A-Za-zÀ-ÖØ-öø-ÿĀ-ſ\u0600-\u06FF]+(?:[ '\-][A-Za-zÀ-ÖØ-öø-ÿĀ-ſ\u0600-\u06FF]+)*$/u,address:/^[A-Za-zÀ-ÖØ-öø-ÿĀ-ſ\u0600-\u06FF0-9\s,.'°º\-\/]+$/u,immatTN:/^\d{1,4}\s*TUN\s*\d{1,4}$/i,immatAr:/^نت\s*\d{1,6}$/u,immatForeign:/^(?=.*[A-Za-z\u0600-\u06FF])(?=.*\d)[A-Za-z0-9\u0600-\u06FF\-\s]{3,15}$/u};
function today(){const d=new Date();d.setHours(0,0,0,0);return d.toISOString().slice(0,10)}
function yearsAgo(y){const d=new Date();d.setFullYear(d.getFullYear()-y);d.setHours(0,0,0,0);return d.toISOString().slice(0,10)}
function num(v){return Number(String(v||'').replace(',', '.').replace(/\s/g,''))}
function fieldLabel(el){return (el.closest('.form-group,.form-field,div')?.querySelector('label')?.textContent||el.getAttribute('placeholder')||el.name||'Champ').replace('*','').trim()}
function clearState(el){el.classList.remove('input-invalid','input-valid');let msg=el.parentElement.querySelector(':scope > .error-message');if(msg)msg.remove()}
function setState(el,msg){clearState(el);if(msg){el.classList.add('input-invalid');const s=document.createElement('span');s.className='error-message';s.textContent=msg;el.parentElement.appendChild(s);return false}el.classList.add('input-valid');return true}
function visible(el){return !!(el.offsetWidth||el.offsetHeight||el.getClientRects().length)&&el.type!=='hidden'&&!el.disabled&&!el.readOnly}
function isOptional(el){return ['details_formule','commentaires','commentaire','precision','precisions'].includes(el.name||'')||el.tagName==='TEXTAREA'}
function validateField(el){if(!visible(el))return true;const name=(el.name||el.id||'').toLowerCase();const label=fieldLabel(el);const value=(el.value||'').trim();if(el.tagName==='SELECT')return setState(el,value?'':'Veuillez choisir une option.');if(!value)return isOptional(el)?(clearState(el),true):setState(el,label+' obligatoire.');if(name.includes('email'))return setState(el,rules.email.test(value)?'':'Email invalide. Exemple : exemple@mail.com');if(name.includes('telephone')||name.includes('tel'))return setState(el,/^\d{8}$/.test(value)?'':'Téléphone invalide : exactement 8 chiffres.');if(name==='nom'||name.includes('[nom]'))return setState(el,rules.letters.test(value)&&value.length>=2?'':'Nom invalide : lettres seulement.');if(name==='prenom'||name.includes('[prenom]'))return setState(el,rules.letters.test(value)&&value.length>=2?'':'Prénom invalide : lettres seulement.');if(name.includes('nationalite_autre'))return setState(el,rules.letters.test(value)&&value.length>=3?'':'Précisez la nationalité avec des lettres seulement.');if(name.includes('adresse'))return setState(el,rules.address.test(value)&&value.length>=5?'':'Adresse invalide : lettres, chiffres et ponctuation simple seulement.');if(name.includes('immatriculation')){const compact=value.replace(/\s+/g,'');return setState(el,(rules.immatTN.test(value)||rules.immatAr.test(compact)||rules.immatForeign.test(value))?'':'Immatriculation invalide. Exemples : 123TUN4567, نت225444, AB-123-CD.')}if(name.includes('date_debut'))return setState(el,value>=today()?'':'La date début ne doit pas être avant aujourd’hui.');if(name.includes('date_fin')){const deb=document.querySelector('[name="date_debut_contrat"],#date_debut');return setState(el,(!deb||!deb.value||value>deb.value)?'':'La date fin doit être après la date début.')}if(name.includes('date_circulation'))return setState(el,(value<=today()&&value>='1980-01-01')?'':'Date de 1er usage invalide : elle ne doit pas dépasser aujourd’hui.');if(name.includes('date_naissance'))return setState(el,(value<=yearsAgo(18)&&value>=yearsAgo(100))?'':'Date naissance invalide : âge entre 18 et 100 ans.');if(name.includes('puissance')){const n=num(value);return setState(el,(Number.isFinite(n)&&n>=1&&n<=45)?'':'Puissance invalide : entre 1 et 45 CV.')}if(name.includes('valeur_venale')){const n=num(value);return setState(el,(Number.isFinite(n)&&n>=1000&&n<=1000000)?'':'Valeur vénale invalide : entre 1 000 et 1 000 000 DT.')}if(name.includes('surface')){const n=num(value);return setState(el,(Number.isFinite(n)&&n>=10&&n<=1000)?'':'Surface invalide : entre 10 et 1000 m².')}if(name.includes('nb_pieces')){const n=num(value);return setState(el,(Number.isInteger(n)&&n>=1&&n<=30)?'':'Nombre de pièces invalide : entre 1 et 30.')}if(name.includes('valeur_biens')){const n=num(value);return setState(el,(Number.isFinite(n)&&n>=500&&n<=2000000)?'':'Valeur des biens invalide.')}if(name.includes('montant_couverture')){const n=num(value);return setState(el,(Number.isFinite(n)&&n>=1000&&n<=1000000)?'':'Montant couverture invalide : entre 1 000 et 1 000 000 DT.')}return setState(el,'')}
function validateForm(form){let ok=true,first=null;form.querySelectorAll('input,select,textarea').forEach(el=>{if(!validateField(el)){ok=false;if(!first)first=el}});if(!ok&&first){first.scrollIntoView({behavior:'smooth',block:'center'});setTimeout(()=>first.focus(),250)}return ok}
function toggleNationaliteAutre(){const s=document.querySelector('[name="nationalite"]');const box=document.getElementById('nationaliteAutreBox')||document.querySelector('[name="nationalite_autre"]')?.closest('div');const input=document.querySelector('[name="nationalite_autre"]');if(!s||!input)return;const show=(s.value||'').toLowerCase()==='autre';if(box)box.style.display=show?'':'none';if(!show){input.value='';clearState(input)}}
document.addEventListener('DOMContentLoaded',function(){const form=document.querySelector('form[id^="contrat"], form[method="post"], form[method="POST"]');if(!form)return;form.setAttribute('novalidate','novalidate');form.querySelectorAll('[required],[min],[max],[pattern]').forEach(el=>{el.removeAttribute('required');el.removeAttribute('min');el.removeAttribute('max');el.removeAttribute('pattern')});toggleNationaliteAutre();const nat=document.querySelector('[name="nationalite"]');if(nat)nat.addEventListener('change',toggleNationaliteAutre);form.querySelectorAll('input,select,textarea').forEach(el=>{el.addEventListener('input',()=>validateField(el));el.addEventListener('change',()=>validateField(el))});form.addEventListener('submit',function(e){if(!validateForm(form)){e.preventDefault();e.stopImmediatePropagation();return false}},true)});
})();
</script>

<script>
// Fallback sûr : même si main.js ne se charge pas, l'avatar dropdown fonctionne.
document.addEventListener('DOMContentLoaded', function () {
    const avatarBtn = document.getElementById('avatarBtn');
    const avatarDropdown = document.getElementById('avatarDropdown');

    if (avatarBtn && avatarDropdown) {
        avatarBtn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            avatarDropdown.classList.toggle('open');
            avatarDropdown.classList.toggle('show');
        });

        avatarDropdown.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        document.addEventListener('click', function () {
            avatarDropdown.classList.remove('open', 'show');
        });
    }

});
</script>

</body>
</html>
