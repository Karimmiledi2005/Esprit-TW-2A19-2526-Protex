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

function niveauBadge(string $niveau): string {
    $niveau = mb_strtolower(trim($niveau), 'UTF-8');
    if ($niveau === 'essentiel') return 'Essentiel';
    if ($niveau === 'intermédiaire' || $niveau === 'intermediaire') return 'Intermédiaire';
    if ($niveau === 'premium') return 'Premium';
    return ucfirst($niveau ?: 'Standard');
}

function profileLabel(string $niveau, string $nom): string {
    $niveau = mb_strtolower(trim($niveau), 'UTF-8');
    if ($niveau === 'essentiel') return 'Budget limité';
    if ($niveau === 'intermédiaire' || $niveau === 'intermediaire') return 'Usage courant';
    if ($niveau === 'premium') return 'Protection maximale';

    $nom = mb_strtolower(trim($nom), 'UTF-8');
    if (str_contains($nom, 'eco')) return 'Budget limité';
    if (str_contains($nom, 'confort')) return 'Usage courant';
    if (str_contains($nom, 'premium')) return 'Protection maximale';
    return 'Profil standard';
}

function formuleIconClass(int $index, string $niveau): string {
    $niveau = mb_strtolower(trim($niveau), 'UTF-8');
    if ($niveau === 'essentiel') return 'icon-classique';
    if ($niveau === 'intermédiaire' || $niveau === 'intermediaire') return 'icon-tierce';
    if ($niveau === 'premium') return 'icon-risque';
    return match ($index % 3) {
        0 => 'icon-classique',
        1 => 'icon-tierce',
        default => 'icon-risque',
    };
}

function formuleIconBi(int $index, string $niveau): string {
    $niveau = mb_strtolower(trim($niveau), 'UTF-8');
    if ($niveau === 'essentiel') return 'bi-shield-check';
    if ($niveau === 'intermédiaire' || $niveau === 'intermediaire') return 'bi-heart-pulse-fill';
    if ($niveau === 'premium') return 'bi-stars';
    return match ($index % 3) {
        0 => 'bi-shield-check',
        1 => 'bi-heart-pulse-fill',
        default => 'bi-stars',
    };
}

$db = config::getConnexion();

$categorie = null;
$formules = [];
$garantiesByFormule = [];

try {
    $catStmt = $db->prepare("
        SELECT *
        FROM categorie
        WHERE LOWER(nom_categorie) IN ('sante', 'santé')
        ORDER BY id_categorie DESC
        LIMIT 1
    ");
    $catStmt->execute();
    $categorie = $catStmt->fetch(PDO::FETCH_ASSOC);

    if (!$categorie) {
        $catStmt = $db->prepare("SELECT * FROM categorie WHERE id_categorie = 4 LIMIT 1");
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
    $categorie = $categorie ?: ['id_categorie' => 4, 'nom_categorie' => 'Santé'];
    $formules = [];
    $garantiesByFormule = [];
}

$formulePanels = [];
foreach ($formules as $formule) {
    $formulePanels[$formule['nom_formule']] = 'panel-' . slugify($formule['nom_formule']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Assurance Santé — Protex</title>
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

<script src="user/assets_contrats/js/main.js"></script>
    <link rel="stylesheet" href="user/assets_contrats/css/base.css">
    <link rel="stylesheet" href="user/assets_contrats/css/layout.css">
    <link rel="stylesheet" href="user/assets_contrats/css/client.css">
    <link rel="stylesheet" href="user/assets_contrats/css/contrat.css">

<script src="user/assets_contrats/js/main.js"></script>



<style>
/* ===== FIX HERO SANTÉ : même style que Auto, image santé ===== */
.auto-hero.sante-hero{
    position: relative !important;
    overflow: hidden !important;
    border-radius: 28px !important;
    padding: 38px !important;
    min-height: 280px !important;
    display: grid !important;
    grid-template-columns: 1.1fr 0.9fr !important;
    gap: 24px !important;
    align-items: center !important;
    margin: 28px 0 36px !important;
    background:
        linear-gradient(135deg, rgba(20,39,56,0.96), rgba(28,64,110,0.94)),
        url('https://images.unsplash.com/photo-1576091160550-2173dba999ef?q=80&w=1400&auto=format&fit=crop') center/cover no-repeat !important;
    border: 1px solid rgba(255,255,255,0.08) !important;
    box-shadow: 0 20px 50px rgba(9, 25, 48, 0.20) !important;
}

.auto-hero.sante-hero::after{
    content:"" !important;
    position:absolute !important;
    inset:0 !important;
    background: radial-gradient(circle at top right, rgba(255,255,255,0.16), transparent 35%) !important;
    pointer-events:none !important;
}

.auto-hero.sante-hero .hero-content{
    position: relative !important;
    z-index: 2 !important;
    color: #fff !important;
    max-width: 850px !important;
}

.auto-hero.sante-hero .hero-chip{
    width: fit-content !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 10px !important;
    background: rgba(255,255,255,0.10) !important;
    border: 1px solid rgba(255,255,255,0.15) !important;
    color: #fff !important;
    padding: 10px 16px !important;
    border-radius: 999px !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    margin-bottom: 18px !important;
    backdrop-filter: blur(8px) !important;
}

.auto-hero.sante-hero .hero-title{
    margin: 0 0 12px !important;
    font-size: 42px !important;
    line-height: 1.08 !important;
    font-weight: 800 !important;
    color: #fff !important;
    max-width: 760px !important;
}

.auto-hero.sante-hero .hero-text{
    margin: 0 0 22px !important;
    max-width: 650px !important;
    color: rgba(255,255,255,0.84) !important;
    font-size: 16px !important;
    line-height: 1.7 !important;
}

.auto-hero.sante-hero .hero-actions{
    display:flex !important;
    gap:14px !important;
    flex-wrap:wrap !important;
}

.auto-hero.sante-hero .hero-btn{
    min-height: 52px !important;
    padding: 0 22px !important;
    border-radius: 16px !important;
    font-size: 15px !important;
    font-weight: 800 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 10px !important;
}

.auto-hero.sante-hero .hero-side{
    position: relative !important;
    z-index: 2 !important;
    display:flex !important;
    justify-content:flex-end !important;
}

.auto-hero.sante-hero .hero-glass{
    width:100% !important;
    max-width:360px !important;
    margin-left:auto !important;
    background:rgba(255,255,255,0.10) !important;
    border:1px solid rgba(255,255,255,0.14) !important;
    backdrop-filter: blur(10px) !important;
    border-radius:26px !important;
    padding:24px !important;
    color:#fff !important;
}

@media (max-width: 1100px){
    .auto-hero.sante-hero{
        grid-template-columns: 1fr !important;
    }
    .auto-hero.sante-hero .hero-side{
        justify-content:flex-start !important;
    }
    .auto-hero.sante-hero .hero-glass{
        margin-left:0 !important;
        max-width:100% !important;
    }
}

@media (max-width: 768px){
    .auto-hero.sante-hero{
        padding:20px !important;
        min-height:260px !important;
    }
    .auto-hero.sante-hero .hero-title{
        font-size:34px !important;
    }
}
</style>

</head>
<body>

<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">
    <!-- ===== NAVBAR ===== -->
    <?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

    <main class="main auto-wrapper">
        <div class="page-header">
            <div>
                <div class="page-title-main">Assurance Santé</div>
                <div class="page-breadcrumb">
                    <i class="bi bi-house"></i>
                    <a href="client.php" style="color:inherit;text-decoration:none;">Accueil</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <a href="contrat.php" style="color:inherit;text-decoration:none;">Contrats</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <span>Santé</span>
                </div>
            </div>
        </div>

        <section class="auto-hero sante-hero">
            <div class="hero-content">
                <div class="hero-chip">
                    <i class="bi bi-heart-pulse"></i>
                    Protection santé flexible
                </div>

                <h1 class="hero-title">Choisissez la formule santé qui vous convient</h1>

                <p class="hero-text">
                    Comparez les niveaux de couverture, découvrez les garanties incluses
                    et sélectionnez la formule santé la plus adaptée à votre situation,
                    votre budget et vos besoins médicaux.
                </p>

                <div class="hero-actions">
                    <a href="#formules-sante" class="hero-btn primary">
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
                    <h3>Pourquoi cette offre ?</h3>
                    <ul class="hero-points">
                        <li><i class="bi bi-check2-circle"></i><span>Des formules mises à jour depuis votre back-office, sans modifier la page manuellement.</span></li>
                        <li><i class="bi bi-check2-circle"></i><span>Des garanties lisibles, un parcours clair et un formulaire moderne pour préparer la demande.</span></li>
                        <li><i class="bi bi-check2-circle"></i><span>Une expérience cohérente avec le style Protex, plus simple et plus rassurante.</span></li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="section-block" id="formules-sante">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Nos formules santé</h2>
                    <p class="section-subtitle">Comparez les garanties et choisissez la couverture qui correspond à votre besoin.</p>
                </div>
            </div>

            <div class="formules-grid">
                <?php if (!empty($formules)): ?>
                    <?php foreach ($formules as $index => $formule): ?>
                        <?php
                            $fid = (int)$formule['id_formule'];
                            $niveau = niveauBadge((string)($formule['niveau_formule'] ?? ''));
                            $profil = profileLabel((string)($formule['niveau_formule'] ?? ''), (string)$formule['nom_formule']);
                            $iconClass = formuleIconClass($index, (string)($formule['niveau_formule'] ?? ''));
                            $iconBi = formuleIconBi($index, (string)($formule['niveau_formule'] ?? ''));
                            $garanties = $garantiesByFormule[$fid] ?? [];
                            $isHighlight = true;
                        ?>
                        <article class="formule-card <?= $index === 1 ? 'highlight' : '' ?>">
                            <span class="badge-top"><?= h($niveau) ?></span>

                            <div class="formule-icon <?= h($iconClass) ?>">
                                <i class="bi <?= h($iconBi) ?>"></i>
                            </div>

                            <h3 class="formule-name"><?= h($formule['nom_formule']) ?></h3>
                            <p class="formule-desc"><?= h($formule['description_formule'] ?? 'Description indisponible.') ?></p>

                            <div class="mini-meta">
                                <div class="meta-box">
                                    <span class="meta-label">Profil conseillé</span>
                                    <span class="meta-value"><?= h($profil) ?></span>
                                </div>
                                <div class="meta-box">
                                    <span class="meta-label">Prix</span>
                                    <span class="meta-value"><?= number_format((float)($formule['prix_formule'] ?? 0), 2, '.', ' ') ?> DT/Mois </span>
                                </div>
                            </div>

                            <ul class="garantie-list">
                                <?php if (!empty($garanties)): ?>
                                    <?php foreach ($garanties as $garantie): ?>
                                        <?php
                                            $ng = mb_strtolower(trim((string)($garantie['niveau_couvert_garantie'] ?? 'basique')), 'UTF-8');
                                            if ($ng === 'basique') {
                                                $icon = 'bi-check2-circle';
                                            } elseif ($ng === 'option') {
                                                $icon = 'bi-plus-circle';
                                            } else {
                                                $icon = 'bi-x-circle';
                                            }
                                        ?>
                                        <li>
                                            <i class="bi <?= h($icon) ?>"></i>
                                            <?= h($garantie['nom_garantie']) ?>
                                            <?php if (!empty($garantie['niveau_couvert_garantie'])): ?><strong>(<?= h($garantie['niveau_couvert_garantie']) ?>)</strong><?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li><i class="bi bi-info-circle"></i> Aucune garantie configurée <strong>(à compléter)</strong></li>
                                <?php endif; ?>
                            </ul>

                            <div class="formule-footer">
                                <button type="button" class="choose-btn choose-sante-btn" data-formule="<?= h($formule['nom_formule']) ?>" onclick="openSanteModal(<?= json_encode($formule['nom_formule']) ?>)">
                                    Choisir cette formule
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <article class="formule-card">
                        <div class="formule-icon icon-classique">
                            <i class="bi bi-heart-pulse"></i>
                        </div>
                        <h3 class="formule-name">Aucune formule</h3>
                        <p class="formule-desc">Ajoutez d’abord des formules santé depuis le back-office pour les afficher ici.</p>
                    </article>
                <?php endif; ?>
            </div>

            <div class="explication-box">
                <div class="info-card">
                    <h3>Comment ça marche ?</h3>
                    <p>
                        Vous commencez par consulter les formules et leurs garanties. Une fois votre choix
                        effectué, un formulaire détaillé s’ouvre dans une fenêtre popup propre pour saisir
                        les informations de l’assuré et du besoin médical.
                    </p>
                </div>

                <div class="info-card">
                    <h3>Pourquoi cette approche ?</h3>
                    <p>
                        L’utilisateur comprend d’abord le produit avant de remplir sa demande.
                        Cela rend l’expérience plus claire, plus moderne et mieux organisée.
                    </p>
                </div>
            </div>
        </section>
    </main>
</div>

<div id="santeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div>
                <h2>Demande d’assurance santé</h2>
                <p>Complétez les informations nécessaires pour préparer votre contrat.</p>
            </div>
            <button type="button" class="close-btn" onclick="closeSanteModal()">&times;</button>
        </div>

        <div class="modal-body">
            <form id="contratSanteForm" method="post" action="saveContratClient.php" novalidate>
                <input type="hidden" name="type_contrat" value="Sante">
                <input type="hidden" name="id_categorie" value="<?= h($categorie['id_categorie'] ?? 4) ?>">

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
                                            <input type="checkbox"
                                                   name="garanties[]"
                                                   value="<?= h($garantie['nom_garantie']) ?>"
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
                                <?= h($formule['description_formule'] ?? 'Cette formule propose un niveau de couverture santé adapté à différents besoins médicaux.') ?>
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
                    <h2 class="form-section-title">II - Coordonnées de l’assuré</h2>
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

                <div class="form-section">
                    <h2 class="form-section-title">III - Besoin de couverture</h2>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="type_couverture">Type de couverture souhaitée <span class="req">*</span></label>
                            <select class="form-select" id="type_couverture" name="type_couverture">
                                <option value="">— Veuillez choisir une option —</option>
                                <option>Individuelle</option>
                                <option>Couple</option>
                                <option>Familiale</option>
                            </select>
                            <div class="error-message" id="error_type_couverture"></div>
                        </div>

                        <div class="form-group">
                            <label for="nombre_beneficiaires">Nombre de bénéficiaires</label>
                            <select class="form-select" id="nombre_beneficiaires" name="nombre_beneficiaires">
                                <option value="">— Veuillez choisir une option —</option>
                                <option>1</option>
                                <option>2</option>
                                <option>3</option>
                                <option>4 ou plus</option>
                            </select>
                            <div class="error-message" id="error_nombre_beneficiaires"></div>
                        </div>

                        <div class="form-group">
                            <label for="antecedents">Antécédents médicaux importants</label>
                            <select class="form-select" id="antecedents" name="antecedents">
                                <option value="">— Veuillez choisir une option —</option>
                                <option>Aucun</option>
                                <option>Diabète</option>
                                <option>Hypertension</option>
                                <option>Asthme</option>
                                <option>Autre</option>
                            </select>
                            <div class="error-message" id="error_antecedents"></div>
                        </div>

                        <div class="form-group">
                            <label for="frequence_soins">Fréquence estimée des soins</label>
                            <select class="form-select" id="frequence_soins" name="frequence_soins">
                                <option value="">— Veuillez choisir une option —</option>
                                <option>Faible</option>
                                <option>Moyenne</option>
                                <option>Élevée</option>
                            </select>
                            <div class="error-message" id="error_frequence_soins"></div>
                        </div>

                        <div class="form-group">
                            <label for="couverture_dentaire">Besoin d’une couverture dentaire ?</label>
                            <select class="form-select" id="couverture_dentaire" name="couverture_dentaire">
                                <option value="">— Veuillez choisir une option —</option>
                                <option>Oui</option>
                                <option>Non</option>
                            </select>
                            <div class="error-message" id="error_couverture_dentaire"></div>
                        </div>

                        <div class="form-group">
                            <label for="couverture_optique">Besoin d’une couverture optique ?</label>
                            <select class="form-select" id="couverture_optique" name="couverture_optique">
                                <option value="">— Veuillez choisir une option —</option>
                                <option>Oui</option>
                                <option>Non</option>
                            </select>
                            <div class="error-message" id="error_couverture_optique"></div>
                        </div>

                        <div class="form-group">
                            <label for="details_formule">Commentaires / précisions</label>
                            <textarea class="form-textarea" id="details_formule" name="details_formule" placeholder="Ajoutez des détails utiles sur votre besoin..."></textarea>
                            <div class="error-message" id="error_details_formule"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-protex btn-light-protex" onclick="closeSanteModal()">Annuler</button>
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

    function openSanteModal(formule = '') {
        const modal = document.getElementById('santeModal');
        const formuleSelect = document.getElementById('formule');

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

    function closeSanteModal() {
        const modal = document.getElementById('santeModal');
        if (!modal) return;
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }

    function toggleCoveragePanels() {
        const select = document.getElementById('formule');
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

    function setError(id, message) {
        const error = document.getElementById('error_' + id);
        if (error) error.textContent = message;
    }

    function clearError(id) {
        const error = document.getElementById('error_' + id);
        if (error) error.textContent = '';
    }

    function validateContratSanteForm(e) {
        let valid = true;

        const Fields = [
            'formule',
            'identite',
            'email',
            'nom',
            'prenom',
            'telephone',
            'date_naissance',
            'nationalite',
            'situation_professionnelle',
            'adresse',
            'type_couverture'
        ];

       Fields.forEach(id => {
            const field = document.getElementById(id);
            if (!field) return;

            clearError(id);

            if (!String(field.value || '').trim()) {
                setError(id, 'Veuillez renseigner ce champ.');
                valid = false;
            }
        });

        const emailField = document.getElementById('email');
        const email = emailField ? emailField.value.trim() : '';
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            setError('email', 'Adresse e-mail invalide.');
            valid = false;
        }

        const telephoneField = document.getElementById('telephone');
        const telephone = telephoneField ? telephoneField.value.trim() : '';
        if (telephone && !/^[0-9+\s]{8,20}$/.test(telephone)) {
            setError('telephone', 'Numéro de téléphone invalide.');
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
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
        const form = document.getElementById('contratSanteForm');
        if (form) {
            form.addEventListener('submit', validateContratSanteForm);
        }

        const modal = document.getElementById('santeModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target.id === 'santeModal') {
                    closeSanteModal();
                }
            });
        }

        document.querySelectorAll('.choose-sante-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                openSanteModal(this.getAttribute('data-formule') || '');
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

</body>
</html>
