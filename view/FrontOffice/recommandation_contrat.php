<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controller/ContratController.php';

// ===== ID client =====
// Remplace cette ligne selon ton systéme de session réel
$idClient = (int)($_SESSION['id_user'] ?? $_GET['id_user'] ?? $_POST['id_user'] ?? 1);

$controller = new ContratController();
$contrats = $controller->getByClient($idClient);
require_once __DIR__ . '/../../controller/CategorieController.php';

$categorieC = new CategorieController();
$categories = $categorieC->listCategories();
if ($categories instanceof PDOStatement) {
    $categories = $categories->fetchAll(PDO::FETCH_ASSOC);
}
if (!is_array($categories)) {
    $categories = [];
}

// ===== Helpers =====
function statusClass(?string $statut): string
{
    $s = strtolower(trim((string)$statut));

    return match ($s) {
        'actif', 'active' => 'active',
        'en attente', 'pending' => 'waiting',
        'expiré', 'expire', 'résilié', 'resilie', 'inactive' => 'expired',
        'refusé', 'refuse' => 'refused',
        default => 'waiting',
    };
}

function typeIcon(?string $type): array
{
    $t = strtolower(trim((string)$type));

    return match ($t) {
        'auto' => ['icon' => 'bi-car-front-fill', 'class' => 'auto'],
        'habitation' => ['icon' => 'bi-house-door-fill', 'class' => 'habitation'],
        'sante', 'santé' => ['icon' => 'bi-heart-pulse-fill', 'class' => 'sante'],
        'protection' => ['icon' => 'bi-shield-check', 'class' => 'protection'],
        default => ['icon' => 'bi-file-earmark-text', 'class' => 'default'],
    };
}

function formatDateFr(?string $date): string
{
    if (!$date) return '-';

    $timestamp = strtotime($date);
    if ($timestamp === false) return htmlspecialchars($date);

    return date('d/m/Y', $timestamp);
}


function normalizeCategoryName(?string $name): string
{
    $name = strtolower(trim((string)$name));
    return str_replace(['é', 'é', 'é', 'é', 'é', 'é', 'é', 'é', 'é'], ['e', 'e', 'e', 'a', 'u', 'o', 'i', 'i', 'a'], $name);
}

function categoryConfig(?string $name): array
{
    $normalized = normalizeCategoryName($name);

    return match ($normalized) {
        'auto' => [
            'href' => 'contrat_auto.php',
            'icon' => 'bi-car-front-fill',
            'class' => 'auto',
            'default_description' => 'Assurance automobile et mobilité.',
        ],
        'habitation' => [
            'href' => 'contrat_habitation.php',
            'icon' => 'bi-house-door-fill',
            'class' => 'habitation',
            'default_description' => 'Protection du logement et du patrimoine.',
        ],
        'sante' => [
            'href' => 'contrat_sante.php',
            'icon' => 'bi-heart-pulse-fill',
            'class' => 'sante',
            'default_description' => 'Couverture santé et assistance médicale.',
        ],
        'protection' => [
            'href' => 'contrat_protection.php',
            'icon' => 'bi-shield-check',
            'class' => 'protection',
            'default_description' => 'Prévoyance, sécurité et assistance.',
        ],
        default => [
            'href' => '#',
            'icon' => 'bi-grid-1x2',
            'class' => 'default',
            'default_description' => 'Découvrez cette catégorie déassurance.',
        ],
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Contrats é Protex</title>
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
    <link rel="stylesheet" href="assets/css/contrat.css">

    <style>
        .toast-notif {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: var(--navy-mid);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--text-primary);
            z-index: 9999;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }

        .toast-notif.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast-success i { color: var(--success); font-size: 18px; }
        .toast-warning i { color: var(--gold); font-size: 18px; }
        .toast-danger i  { color: var(--danger); font-size: 18px; }

        .empty-contracts {
            padding: 26px;
            border: 1px dashed var(--border);
            border-radius: 18px;
            background: rgba(255,255,255,0.04);
            text-align: center;
            color: var(--text-secondary);
        }

        .contracts-tools {
            display: flex;
            gap: 14px;
            align-items: center;
            margin: 18px 0 24px;
            flex-wrap: wrap;
        }

        .contracts-search {
            flex: 1;
            min-width: 280px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 18px;
            border: 1px solid rgba(20, 39, 56, 0.12);
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 12px 30px rgba(10, 25, 49, 0.06);
        }

        .contracts-search i {
            color: #EE5828;
            font-size: 18px;
        }

        .contracts-search input {
            border: none;
            outline: none;
            width: 100%;
            font-weight: 700;
            color: #142738;
            background: transparent;
        }

        .contracts-search input::placeholder {
            color: rgba(20, 39, 56, 0.55);
        }

        .contracts-tools select,
        .contracts-tools button {
            padding: 14px 18px;
            border-radius: 16px;
            border: 1px solid rgba(20, 39, 56, 0.12);
            background: #ffffff;
            font-weight: 800;
            color: #142738;
            cursor: pointer;
            box-shadow: 0 12px 30px rgba(10, 25, 49, 0.06);
        }

        .contracts-tools button {
            color: #EE5828;
        }

        .contracts-feedback {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 14px 0 18px;
            padding: 14px 18px;
            border-radius: 18px;
            font-weight: 800;
            border: 1px solid rgba(20, 39, 56, 0.10);
        }

        .contracts-feedback.success {
            background: #ecfdf3;
            color: #15803d;
        }

        .contracts-feedback.error {
            background: #fff1f2;
            color: #be123c;
        }

        .contracts-empty-filter {
            display: none;
            padding: 24px;
            margin-top: 14px;
            border: 1px dashed rgba(238, 88, 40, 0.35);
            border-radius: 20px;
            background: rgba(238, 88, 40, 0.06);
            text-align: center;
            color: #142738;
            font-weight: 700;
        }

        @media (max-width: 900px) {
            .contracts-tools {
                flex-direction: column;
                align-items: stretch;
            }

            .contracts-search {
                min-width: 100%;
            }
        }

    </style>

    <!-- FrontOffice unifie - surcharge théme camarades dark-navy -->
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css"></head>
<body>

<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">
    <!-- ===== NAVBAR ===== -->
    <?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

    <!-- ===== MAIN ===== -->
    <main class="main">

        <div class="page-header">
            <div>
                <div class="page-title-main">Contrats</div>
                <div class="page-breadcrumb">
                    <i class="bi bi-house"></i>
                    <a href="client.php" style="color:inherit;text-decoration:none;">Accueil</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <span>Contrats</span>
                </div>
            </div>
        </div>

        <div class="contracts-intro">
            <div>
                <h2>Choisissez une catégorie</h2>
                <p>Sélectionnez le type déassurance avant de remplir votre contrat.</p>
            </div>
        </div>

        <div class="categories-grid">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $index => $categorie): ?>
                    <?php
                        $config = categoryConfig($categorie['nom_categorie'] ?? '');
                        $descriptionCategorie = trim((string)($categorie['description_categorie'] ?? ''));
                        $descriptionToShow = $descriptionCategorie !== ''
                            ? $descriptionCategorie
                            : $config['default_description'];
                    ?>
                    <a href="<?= htmlspecialchars($config['href']) ?>" class="category-card <?= $index === 0 ? 'active' : '' ?>">
                        <div class="category-icon <?= htmlspecialchars($config['class']) ?>">
                            <i class="bi <?= htmlspecialchars($config['icon']) ?>"></i>
                        </div>
                        <h3><?= htmlspecialchars($categorie['nom_categorie'] ?? 'Catégorie') ?></h3>
                        <p><?= htmlspecialchars($descriptionToShow) ?></p>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-contracts" style="grid-column: 1 / -1;">
                    <h3>Aucune catégorie trouvée</h3>
                    <p>Ajoutez déabord des catégories dans le back-office.</p>
                </div>
            <?php endif; ?>
        </div>

        <section class="content contracts-page">
            <div class="contracts-header">
                <div>
                    <h2>Mes contrats</h2>
                    <p>Consultez et gérez facilement tous vos contrats</p>
                </div>
            </div>

            <?php
                $feedbackMessage = '';
                $feedbackClass = 'success';
                $feedbackIcon = 'bi-check-circle';

                // Ancienne redirection : contrat.php?success=renewal&new_id=16
                if (isset($_GET['success']) && $_GET['success'] === 'renewal') {
                    $feedbackMessage = 'Demande de renouvellement créée avec succés. Elle est maintenant en attente de validation.';
                }

                // Nouvelle redirection propre : contrat.php?renewal=pending / approved / rejected
                if (isset($_GET['renewal'])) {
                    if ($_GET['renewal'] === 'pending') {
                        $feedbackMessage = 'Demande de renouvellement créée avec succés. Elle est maintenant en attente de validation.';
                    } elseif ($_GET['renewal'] === 'approved') {
                        $feedbackMessage = 'Votre renouvellement a été validé avec succés par l admin.';
                    } elseif ($_GET['renewal'] === 'rejected') {
                        $feedbackMessage = 'Votre demande de renouvellement a été refusée.';
                        $feedbackClass = 'error';
                        $feedbackIcon = 'bi-x-circle';
                    }
                }

                if (isset($_GET['error']) && $_GET['error'] === 'renouvellement_impossible') {
                    $feedbackMessage = 'Renouvellement impossible pour ce contrat.';
                    $feedbackClass = 'error';
                    $feedbackIcon = 'bi-exclamation-triangle';
                }
            ?>

            <?php if ($feedbackMessage !== ''): ?>
                <div id="renewalFeedback" class="contracts-feedback <?= htmlspecialchars($feedbackClass) ?>">
                    <i class="bi <?= htmlspecialchars($feedbackIcon) ?>"></i>
                    <?= htmlspecialchars($feedbackMessage) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($contrats)): ?>
                <div class="contracts-tools">
                    <div class="contracts-search">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchContrats" placeholder="Rechercher par type, formule, statut, numéro...">
                    </div>

                    <select id="filterStatut">
                        <option value="">Tous les statuts</option>
                        <option value="actif">Actif</option>
                        <option value="en attente">En attente</option>
                        <option value="résilié">Résilié</option>
                        <option value="expiré">Expiré</option>
                        <option value="refusé">Refusé</option>
                    </select>

                    <select id="sortContrats">
                        <option value="default">Tri par défaut</option>
                        <option value="date_desc">Date début récente</option>
                        <option value="date_asc">Date début ancienne</option>
                        <option value="prime_asc">Prime croissante</option>
                        <option value="prime_desc">Prime décroissante</option>
                        <option value="franchise_asc">Franchise croissante</option>
                        <option value="franchise_desc">Franchise décroissante</option>
                    </select>

                    <button type="button" id="resetContratFilters">
                        <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                    </button>
                </div>

                <div class="contracts-empty-filter" id="contractsEmptyFilter">
                    Aucun contrat ne correspond é votre recherche.
                </div>
            <?php endif; ?>

            

<div class="ai-reco-card" style="
    width:100%;
    margin:28px 0 30px;
    padding:28px 32px;
    border-radius:24px;
    background:linear-gradient(135deg,#0A1931,#1e3a66);
    box-shadow:0 18px 45px rgba(10,25,49,0.18);
    color:#fff;
">
    <div style="
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:28px;
        flex-wrap:wrap;
    ">
        <div style="max-width:760px;">
            <h3 style="
                margin:0 0 10px;
                color:#fff;
                font-size:28px;
                font-weight:800;
            ">
                Trouver mon contrat idéal
            </h3>

            <p style="
                margin:0;
                color:rgba(255,255,255,0.86);
                font-size:16px;
                line-height:1.7;
            ">
                Répondez é quelques questions et notre systéme intelligent
                vous recommandera automatiquement la meilleure formule.
            </p>
        </div>

        <a href="offres.php" style="
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:270px;
            min-height:60px;
            padding:16px 28px;
            background:#FF6B1A;
            color:#fff;
            border-radius:16px;
            text-decoration:none;
            font-weight:800;
            font-size:15px;
            box-shadow:0 14px 28px rgba(255,107,26,0.28);
            white-space:nowrap;
        ">
            Recommandation intelligente
        </a>
    </div>
</div>

<div class="contracts-list">
                <?php if (!empty($contrats)): ?>
                    <?php foreach ($contrats as $loopIndex => $contrat): ?>
                        <?php
                            $typeData = typeIcon($contrat->getTypeContrat());
                            $badgeClass = statusClass($contrat->getStatutContrat());
                        ?>

                        <div class="contract-banner"
                             data-original-index="<?= (int)$loopIndex ?>"
                             data-search="<?= htmlspecialchars(strtolower(
                                 $contrat->getNumeroContrat() . ' ' .
                                 $contrat->getTypeContrat() . ' ' .
                                 $contrat->getNomCategorie() . ' ' .
                                 $contrat->getNomFormule() . ' ' .
                                 $contrat->getStatutContrat() . ' ' .
                                 $contrat->getDateDebutContrat() . ' ' .
                                 $contrat->getDateFinContrat()
                             )) ?>"
                             data-statut="<?= htmlspecialchars(strtolower(trim((string)$contrat->getStatutContrat()))) ?>"
                             data-prime="<?= htmlspecialchars((string)((float)$contrat->getPrimeContrat())) ?>"
                             data-franchise="<?= htmlspecialchars((string)((float)$contrat->getFranchiseContrat())) ?>"
                             data-date="<?= htmlspecialchars((string)$contrat->getDateDebutContrat()) ?>">
                            <div class="contract-banner-left">
                                <div class="contract-icon <?= htmlspecialchars($typeData['class']) ?>">
                                    <i class="bi <?= htmlspecialchars($typeData['icon']) ?>"></i>
                                </div>

                                <div>
                                    <h3>Contrat <?= htmlspecialchars($contrat->getTypeContrat()) ?></h3>
                                    <span class="contract-ref">
                                        Né <?= htmlspecialchars($contrat->getNumeroContrat()) ?>
                                    </span>
                                </div>
                            </div>
                            

                            <div class="contract-banner-center">
                                <div class="info-item">
                                    <span class="label">Date début</span>
                                    <strong><?= formatDateFr($contrat->getDateDebutContrat()) ?></strong>
                                </div>

                                <div class="info-item">
                                    <span class="label">Date fin</span>
                                    <strong><?= formatDateFr($contrat->getDateFinContrat()) ?></strong>
                                </div>

                                <div class="info-item">
                                    <span class="label">Prime</span>
                                    <strong><?= htmlspecialchars((string)$contrat->getPrimeContrat()) ?> DT</strong>
                                </div>

                                <div class="info-item">
                                    <span class="label">Franchise</span>
                                    <strong><?= htmlspecialchars((string)$contrat->getFranchiseContrat()) ?> DT</strong>
                                </div>
                            </div>
                            

                            <div class="contract-banner-right">
                                <span class="status-badge <?= htmlspecialchars($badgeClass) ?>">
                                    <?= htmlspecialchars($contrat->getStatutContrat()) ?>
                                </span>

                                <div class="contract-actions">
                                    <a href="contratshow.php?id=<?= urlencode((string)$contrat->getIdContrat()) ?>" class="action-btn">
                                        Voir
                                    </a>
                                    <a href="contrat_update_client.php?id=<?= urlencode((string)$contrat->getIdContrat()) ?>" class="action-btn secondary">
                                        Modifier
                                    </a>
                                    <a href="contratcancel.php?id=<?= urlencode((string)$contrat->getIdContrat()) ?>" class="action-btn secondary" onclick="return confirm('Résilier ce contrat ?')">
                                        Résilier
                                    </a>

                                    <?php if (in_array(strtolower(trim((string)$contrat->getStatutContrat())), ['actif', 'expiré', 'résilié'], true)): ?>
                                        <a href="renouvelerContrat.php?id=<?= urlencode((string)$contrat->getIdContrat()) ?>"
                                           class="action-btn secondary"
                                           onclick="return confirm('Voulez-vous renouveler ce contrat ? Une nouvelle demande sera créée en attente.');">
                                            Renouveler
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-contracts">
                        <h3>Aucun contrat trouvé</h3>
                        <p>Le client néa pas encore de contrats enregistrés.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </main>
</div>

<script>
(function () {
    const searchInput = document.getElementById('searchContrats');
    const filterStatut = document.getElementById('filterStatut');
    const sortContrats = document.getElementById('sortContrats');
    const resetButton = document.getElementById('resetContratFilters');
    const contractsList = document.querySelector('.contracts-list');
    const emptyMessage = document.getElementById('contractsEmptyFilter');

    if (!searchInput || !filterStatut || !sortContrats || !resetButton || !contractsList) {
        return;
    }

    function normalizeText(value) {
        return (value || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function getCards() {
        return Array.from(document.querySelectorAll('.contract-banner'));
    }

    function matchesSearch(card, words) {
        const data = normalizeText(card.dataset.search || '');
        return words.every(word => data.includes(word));
    }

    function sortCards(cards) {
        const sortValue = sortContrats.value;

        cards.sort((a, b) => {
            const primeA = parseFloat(a.dataset.prime || '0');
            const primeB = parseFloat(b.dataset.prime || '0');
            const franchiseA = parseFloat(a.dataset.franchise || '0');
            const franchiseB = parseFloat(b.dataset.franchise || '0');
            const dateA = new Date(a.dataset.date || '1970-01-01');
            const dateB = new Date(b.dataset.date || '1970-01-01');
            const indexA = parseInt(a.dataset.originalIndex || '0', 10);
            const indexB = parseInt(b.dataset.originalIndex || '0', 10);

            if (sortValue === 'prime_asc') return primeA - primeB;
            if (sortValue === 'prime_desc') return primeB - primeA;
            if (sortValue === 'franchise_asc') return franchiseA - franchiseB;
            if (sortValue === 'franchise_desc') return franchiseB - franchiseA;
            if (sortValue === 'date_asc') return dateA - dateB;
            if (sortValue === 'date_desc') return dateB - dateA;

            return indexA - indexB;
        });

        cards.forEach(card => contractsList.appendChild(card));
    }

    function applyContratFilters() {
        const words = normalizeText(searchInput.value).split(/\s+/).filter(Boolean);
        const statutValue = normalizeText(filterStatut.value);
        const cards = getCards();
        let visibleCount = 0;

        sortCards(cards);

        cards.forEach(card => {
            const cardStatut = normalizeText(card.dataset.statut || '');
            const searchOk = words.length === 0 || matchesSearch(card, words);
            const statutOk = statutValue === '' || cardStatut === statutValue;
            const visible = searchOk && statutOk;

            card.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });

        if (emptyMessage) {
            emptyMessage.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    function resetContratFilters() {
        searchInput.value = '';
        filterStatut.value = '';
        sortContrats.value = 'default';
        applyContratFilters();
    }

    searchInput.addEventListener('input', applyContratFilters);
    filterStatut.addEventListener('change', applyContratFilters);
    sortContrats.addEventListener('change', applyContratFilters);
    resetButton.addEventListener('click', resetContratFilters);
})();
</script>

<script>
(function () {
    const feedback = document.getElementById('renewalFeedback');
    if (!feedback) return;

    setTimeout(() => {
        feedback.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        feedback.style.opacity = '0';
        feedback.style.transform = 'translateY(-6px)';

        setTimeout(() => {
            feedback.remove();
        }, 450);

        const url = new URL(window.location.href);
        url.searchParams.delete('success');
        url.searchParams.delete('renewal');
        url.searchParams.delete('new_id');
        url.searchParams.delete('error');

        window.history.replaceState({}, document.title, url.pathname + (url.search ? url.search : ''));
    }, 5000);
})();
</script>

</body>
</html>


