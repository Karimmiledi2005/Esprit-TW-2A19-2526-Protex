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

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function slugify(string $text): string {
    $text = trim($text);
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'item';
}

function habNiveauLabel(string $niveau): string {
    $niveau = mb_strtolower(trim($niveau), 'UTF-8');
    if ($niveau === 'essentiel') return 'Essentiel';
    if ($niveau === 'intermédiaire' || $niveau === 'intermediaire') return 'Intermédiaire';
    if ($niveau === 'premium') return 'Premium';
    return ucfirst($niveau ?: 'Standard');
}

function habProfileLabel(string $niveau, string $nom): string {
    $niveau = mb_strtolower(trim($niveau), 'UTF-8');
    if ($niveau === 'essentiel') return 'Budget maîtrisé';
    if ($niveau === 'intermédiaire' || $niveau === 'intermediaire') return 'Usage régulier';
    if ($niveau === 'premium') return 'Protection maximale';

    $nom = mb_strtolower(trim($nom), 'UTF-8');
    if (str_contains($nom, 'eco')) return 'Budget maîtrisé';
    if (str_contains($nom, 'confort')) return 'Usage régulier';
    if (str_contains($nom, 'premium') || str_contains($nom, 'priv')) return 'Protection maximale';
    return 'Profil standard';
}

function habIconClass(int $index, string $niveau): string {
    $niveau = mb_strtolower(trim($niveau), 'UTF-8');
    if ($niveau === 'essentiel') return 'economique';
    if ($niveau === 'intermédiaire' || $niveau === 'intermediaire') return 'privilege';
    if ($niveau === 'premium') return 'privilege';
    return $index % 2 === 0 ? 'economique' : 'privilege';
}

function habIconBi(int $index, string $niveau): string {
    $niveau = mb_strtolower(trim($niveau), 'UTF-8');
    if ($niveau === 'essentiel') return 'bi-house-door-fill';
    if ($niveau === 'intermédiaire' || $niveau === 'intermediaire') return 'bi-building-check';
    if ($niveau === 'premium') return 'bi-shield-lock-fill';
    return $index % 2 === 0 ? 'bi-house-door-fill' : 'bi-building-check';
}

$db = config::getConnexion();

$categorie = null;
$formules = [];
$garantiesByFormule = [];
$formulePanels = [];

try {
    $catStmt = $db->prepare("
        SELECT *
        FROM categorie
        WHERE LOWER(nom_categorie) = 'habitation'
        ORDER BY id_categorie DESC
        LIMIT 1
    ");
    $catStmt->execute();
    $categorie = $catStmt->fetch(PDO::FETCH_ASSOC);

    if (!$categorie) {
        $catStmt = $db->prepare("SELECT * FROM categorie WHERE id_categorie = 3 LIMIT 1");
        $catStmt->execute();
        $categorie = $catStmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($categorie) {
        $formuleStmt = $db->prepare("
            SELECT *
            FROM formule
            WHERE id_categorie = :id_categorie
            ORDER BY id_formule ASC
        ");
        $formuleStmt->execute(['id_categorie' => $categorie['id_categorie']]);
        $formules = $formuleStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($formules)) {
            $ids = array_map(fn($row) => (int)$row['id_formule'], $formules);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $garantieStmt = $db->prepare("
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
                WHERE fg.id_formule IN ($placeholders)
                ORDER BY fg.id_formule ASC, g.id_garantie ASC
            ");
            $garantieStmt->execute($ids);

            foreach ($garantieStmt->fetchAll(PDO::FETCH_ASSOC) as $garantie) {
                $fid = (int)$garantie['id_formule'];
                if (!isset($garantiesByFormule[$fid])) {
                    $garantiesByFormule[$fid] = [];
                }
                $garantiesByFormule[$fid][] = $garantie;
            }
        }
    }
} catch (Exception $e) {
    $categorie = $categorie ?: ['id_categorie' => 3, 'nom_categorie' => 'Habitation'];
    $formules = [];
    $garantiesByFormule = [];
}

foreach ($formules as $formule) {
    $formulePanels[$formule['nom_formule']] = 'panel-' . slugify($formule['nom_formule']);
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Assurance Habitation — Protex</title>
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

    <main class="main hab-page">
        <div class="page-header">
            <div>
                <div class="page-title-main">Assurance Habitation</div>
                <div class="page-breadcrumb">
                    <i class="bi bi-house"></i>
                    <a href="client.php" style="color:inherit;text-decoration:none;">Accueil</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <a href="contrat.php" style="color:inherit;text-decoration:none;">Contrats</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <span>Habitation</span>
                </div>
            </div>
        </div>

        <section class="hab-hero">
    <div class="hab-hero-left">
        <div class="hab-chip">
            <i class="bi bi-house-heart-fill"></i>
            Protection habitation flexible
        </div>

        <h1 class="hab-title">Choisissez la formule habitation qui protège votre foyer</h1>

        <p class="hab-text">
            Comparez les niveaux de couverture, découvrez les garanties incluses
            et lancez votre demande en quelques clics avec une expérience simple,
            moderne et rassurante.
        </p>

        <div class="hab-hero-actions">
            <a href="#formules-habitation" class="hero-btn primary">
                <i class="bi bi-lightning-charge-fill"></i>
                Voir les formules
            </a>

            <a href="contrat.php" class="hero-btn secondary">
                <i class="bi bi-arrow-left"></i>
                Retour aux catégories
            </a>
        </div>
    </div>

    <div class="hab-hero-right">
        <div class="hero-glass">
            <h3>Pourquoi choisir Protex Habitation ?</h3>
            <ul class="hero-points">
                <li><i class="bi bi-check-circle-fill"></i><span>Des formules claires adaptées à votre logement.</span></li>
                <li><i class="bi bi-check-circle-fill"></i><span>Des garanties détaillées avant même de remplir votre demande.</span></li>
                <li><i class="bi bi-check-circle-fill"></i><span>Un formulaire guidé qui s’ouvre seulement après votre choix.</span></li>
                <li><i class="bi bi-check-circle-fill"></i><span>Une protection pensée pour les biens et les personnes du foyer.</span></li>
            </ul>
        </div>
    </div>
</section>

        <section class="hab-formules">
            <div class="hab-formules-header">
                <h2 class="hab-formules-title">Nos formules habitation</h2>
                <p class="hab-formules-subtitle">
                    Deux niveaux de couverture pour protéger votre logement, vos biens et votre famille contre les imprévus du quotidien.
                </p>
            </div>

            <div class="hab-cards-grid">
                <?php if (!empty($formules)): ?>
                    <?php foreach ($formules as $index => $formule): ?>
                        <?php
                            $fid = (int)$formule['id_formule'];
                            $niveau = habNiveauLabel((string)($formule['niveau_formule'] ?? ''));
                            $profil = habProfileLabel((string)($formule['niveau_formule'] ?? ''), (string)$formule['nom_formule']);
                            $iconClass = habIconClass($index, (string)($formule['niveau_formule'] ?? ''));
                            $iconBi = habIconBi($index, (string)($formule['niveau_formule'] ?? ''));
                            $garanties = $garantiesByFormule[$fid] ?? [];
                        ?>
                        <article class="hab-card <?= $index === 1 ? 'highlight' : '' ?>">
                            <span class="hab-badge-top"><?= h($niveau) ?></span>

                            <div class="hab-icon <?= h($iconClass) ?>">
                                <i class="bi <?= h($iconBi) ?>"></i>
                            </div>

                            <h3 class="hab-card-title"><?= h($formule['nom_formule']) ?></h3>
                            <p class="hab-card-desc"><?= h($formule['description_formule'] ?? 'Description indisponible.') ?></p>

                            <div class="mini-meta">
                                <div class="meta-box">
                                    <span class="meta-label">Profil conseillé</span>
                                    <span class="meta-value"><?= h($profil) ?></span>
                                </div>
                                <div class="meta-box">
                                    <span class="meta-label">Prix</span>
                                    <span class="meta-value"><?= number_format((float)($formule['prix_formule'] ?? 0), 2, '.', ' ') ?> DT/Mois</span>
                                </div>
                            </div>

                            <ul class="hab-list">
                                <?php if (!empty($garanties)): ?>
                                    <?php foreach ($garanties as $garantie): ?>
                                        <?php
                                            $ng = mb_strtolower(trim((string)($garantie['niveau_couvert_garantie'] ?? 'basique')), 'UTF-8');
                                            $icon = $ng === 'basique' ? 'bi-check2-circle' : ($ng === 'option' ? 'bi-plus-circle' : 'bi-x-circle');
                                        ?>
                                        <li>
                                            <i class="bi <?= h($icon) ?>"></i>
                                            <div>
                                                <?= h($garantie['nom_garantie']) ?>
                                                <?php if (!empty($garantie['niveau_couvert_garantie'])): ?><span>(<?= h($garantie['niveau_couvert_garantie']) ?>)</span><?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li>
                                        <i class="bi bi-info-circle"></i>
                                        <div>Aucune garantie configurée <span>(à compléter)</span></div>
                                    </li>
                                <?php endif; ?>
                            </ul>

                            <div class="hab-actions">
                                <button type="button"
                                        class="devis-btn choose-hab-btn"
                                        data-formule="<?= h($formule['nom_formule']) ?>"
                                        onclick="openHabitationModal(<?= json_encode($formule['nom_formule']) ?>)">
                                    Choisir cette formule
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <article class="hab-card">
                        <div class="hab-icon economique">
                            <i class="bi bi-house-door-fill"></i>
                        </div>
                        <h3 class="hab-card-title">Aucune formule</h3>
                        <p class="hab-card-desc">Ajoutez d’abord des formules habitation depuis le back-office pour les afficher ici.</p>
                    </article>
                <?php endif; ?>
            </div><div class="hab-bottom-note">
                <div class="hab-bottom-note-left">
                    <i class="bi bi-shield-check"></i>
                    <div>
                        <strong>Les deux formules protègent votre logement</strong>
                        <span>La formule Privilège ajoute une couverture plus large sur les biens et les personnes.</span>
                    </div>
                </div>

                <div class="hab-bottom-note-right">
                    <i class="bi bi-info-circle"></i>
                    <div>
                        <strong>Options supplémentaires possibles</strong>
                        <span>Vous pourrez compléter votre protection au moment de la demande de devis.</span>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- Modal -->
<div id="habitationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div>
                <h2>Demande d’assurance habitation</h2>
                <p>Complétez les informations nécessaires pour préparer votre contrat habitation.</p>
            </div>
            <button type="button" class="close-btn" onclick="closeHabitationModal()">&times;</button>
        </div>

        <div class="modal-body">
            <form id="contratHabitationForm" method="post" action="saveContratClient.php" novalidate>
                <input type="hidden" name="type_contrat" value="Habitation">
                <input type="hidden" name="id_categorie" value="<?= h($categorie['id_categorie'] ?? '') ?>">
                <input type="hidden" id="id_formule" name="id_formule">

                <div class="form-section">
    <h2 class="form-section-title">I - Formule choisie</h2>

    <div class="form-grid-1">
        <div class="form-group">
            <label for="formule_habitation">Formule habitation <span class="req">*</span></label>
            <select class="form-select" id="formule_habitation" name="formule_habitation" onchange="toggleCoveragePanels(); updateFormuleContractInfo();">
                <option value="">— Veuillez choisir une option —</option>
                <?php foreach ($formules as $formule): ?>
                    <option value="<?= h($formule['nom_formule']) ?>">
                        <?= h($formule['nom_formule']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="error-message" id="error_formule_habitation"></div>
        </div>
    </div>

    <!-- Panel Economique -->
    <?php foreach ($formules as $formule): ?>
                        <?php
                            $fid = (int)$formule['id_formule'];
                            $panelId = 'panel-' . slugify($formule['nom_formule']);
                            $garanties = $garantiesByFormule[$fid] ?? [];
                        ?>
                        <div id="<?= h($panelId) ?>" class="coverage-panel">
                            <h3>Garanties de la formule <?= h($formule['nom_formule']) ?></h3>

                            <div class="check-grid">
                                <?php if (!empty($garanties)): ?>
                                    <?php foreach ($garanties as $garantie): ?>
                                        <?php
                                            $niveauGarantie = mb_strtolower(trim((string)($garantie['niveau_couvert_garantie'] ?? 'basique')), 'UTF-8');
                                            $isFixed = $niveauGarantie === 'basique';
                                            $isDisabled = $niveauGarantie === 'non disponible';
                                            $labelClass = 'check-item';
                                            if ($isFixed) $labelClass .= ' fixed';
                                            if ($isDisabled) $labelClass .= ' disabled';
                                        ?>
                                        <label class="<?= h($labelClass) ?>">
                                            <?php if ($isFixed): ?>
                                                <input type="hidden" name="garanties[]" value="<?= h($garantie['nom_garantie']) ?>">
                                            <?php endif; ?>
                                            <input type="checkbox"
                                                   <?= !$isDisabled && !$isFixed ? 'name="garanties[]" value="' . h($garantie['nom_garantie']) . '"' : '' ?>
                                                   <?= $isFixed ? 'checked disabled' : '' ?>
                                                   <?= $isDisabled ? 'disabled' : '' ?>>
                                            <?= h($garantie['nom_garantie']) ?>
                                        </label>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <label class="check-item disabled">
                                        <input type="checkbox" disabled>
                                        Aucune garantie configurée
                                    </label>
                                <?php endif; ?>
                            </div>

                            <div class="hint-box">
                                <?= h($formule['description_formule'] ?? 'Cette formule propose un niveau de couverture habitation adapté à différents besoins de logement et de protection.') ?>
                            </div>
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
                    <h2 class="form-section-title">II - Informations sur le logement</h2>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="type_logement">Type de logement <span class="req">*</span></label>
                            <select class="form-select" id="type_logement" name="type_logement">
                                <option value="">— Veuillez choisir une option —</option>
                                <option>Appartement</option>
                                <option>Maison</option>
                                <option>Villa</option>
                                <option>Studio</option>
                            </select>
                            <div class="error-message" id="error_type_logement"></div>
                        </div>

                        <div class="form-group">
                            <label for="statut_occupation">Statut d’occupation <span class="req">*</span></label>
                            <select class="form-select" id="statut_occupation" name="statut_occupation">
                                <option value="">— Veuillez choisir une option —</option>
                                <option>Propriétaire</option>
                                <option>Locataire</option>
                                <option>Occupant à titre gratuit</option>
                            </select>
                            <div class="error-message" id="error_statut_occupation"></div>
                        </div>

                        <div class="form-group">
                            <label for="adresse_logement">Adresse du logement <span class="req">*</span></label>
                            <input type="text" class="form-control" id="adresse_logement" name="adresse_logement" placeholder="Adresse complète">
                            <div class="error-message" id="error_adresse_logement"></div>
                        </div>

                        <div class="form-group">
                            <label for="surface_logement">Surface (m²) <span class="req">*</span></label>
                            <input type="text" class="form-control" id="surface_logement" name="surface_logement" placeholder="Surface en m²">
                            <div class="error-message" id="error_surface_logement"></div>
                        </div>

                        <div class="form-group">
                            <label for="nb_pieces">Nombre de pièces <span class="req">*</span></label>
                            <input type="text" class="form-control" id="nb_pieces" name="nb_pieces" placeholder="Nombre de pièces">
                            <div class="error-message" id="error_nb_pieces"></div>
                        </div>

                        <div class="form-group">
                            <label for="valeur_biens">Valeur estimée des biens <span class="req">*</span></label>
                            <input type="text" class="form-control" id="valeur_biens" name="valeur_biens" placeholder="Valeur en DT">
                            <div class="error-message" id="error_valeur_biens"></div>
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
                    <button type="button" class="btn-protex btn-light-protex" onclick="closeHabitationModal()">Annuler</button>
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
const formulePanels = <?= json_encode($formulePanels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function openHabitationModal(formule = '') {
        const modal = document.getElementById('habitationModal');
        const formuleSelect = document.getElementById('formule_habitation');

        if (!modal) return;

        modal.classList.add('show');
        document.body.style.overflow = 'hidden';

        if (formuleSelect) {
            formuleSelect.value = formule || '';
        }

        toggleCoveragePanels();
        setDefaultContractDates();
        updateFormuleContractInfo();
    }

    function closeHabitationModal() {
        const modal = document.getElementById('habitationModal');
        if (!modal) return;
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }

    function toggleCoveragePanels() {
        const select = document.getElementById('formule_habitation');
        const value = select ? select.value : '';

        Object.values(formulePanels || {}).forEach(panelId => {
            const panel = document.getElementById(panelId);
            if (panel) panel.classList.remove('active');
        });

        if (value && formulePanels && formulePanels[value]) {
            const activePanel = document.getElementById(formulePanels[value]);
            if (activePanel) activePanel.classList.add('active');
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
        const modal = document.getElementById('habitationModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target.id === 'habitationModal') {
                    closeHabitationModal();
                }
            });
        }

        document.querySelectorAll('.choose-hab-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                openHabitationModal(this.getAttribute('data-formule') || '');
            });
        });

        toggleCoveragePanels();

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
