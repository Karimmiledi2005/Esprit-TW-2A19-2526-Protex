<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/SessionGuard.php';
require_once __DIR__ . '/../../helpers/RoleHelper.php';

SessionGuard::requireClient();

$userId = RoleHelper::getUserId();

// ── Fetch client's quotes ──────────────────────────────────────────────────────
$devis = [];
try {
    $db   = config::getConnexion();
    $stmt = $db->prepare("
        SELECT
            d.id_devis,
            d.type_assurance,
            d.nom,
            d.prenom,
            d.email,
            d.telephone,
            d.montant_estime,
            d.reponse_admin,
            d.statut,
            COALESCE(d.date_demande, d.created_at) AS date_creation,
            o.nom_offre,
            o.type_offre,
            o.prix_annuel
        FROM devis d
        LEFT JOIN offre o ON d.id_offre = o.id_offre
        WHERE d.id_user = :uid
        ORDER BY COALESCE(d.date_demande, d.created_at) DESC
    ");
    $stmt->execute([':uid' => $userId]);
    $devis = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Mes devis — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Consultez et suivez l'état de vos demandes de devis Protex.">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="<?= BASE_URL ?>/view/FrontOffice/assets/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/view/FrontOffice/assets/css/base.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/view/FrontOffice/assets/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/view/FrontOffice/assets/css/client.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/view/FrontOffice/assets/css/light-theme.css">
    <style>
        /* ── Hero ──────────────────────────────────────────── */
        .hero-banner {
            position: relative; overflow: hidden;
            padding: 34px; border-radius: 30px;
            background: radial-gradient(circle at 78% 18%, rgba(255,255,255,.10), transparent 22%),
                        linear-gradient(135deg, #1f3f86, #18336c);
            border: 1px solid rgba(255,255,255,.10);
            box-shadow: 0 24px 55px rgba(29,53,105,.16);
            margin-bottom: 28px;
        }
        .hero-inner {
            position: relative; z-index: 1;
            display: flex; align-items: center; gap: 20px;
        }
        .hero-icon-wrap {
            width: 64px; height: 64px; flex-shrink: 0;
            border-radius: 20px; background: rgba(255,255,255,.12);
            display: grid; place-items: center; font-size: 28px; color: #ffb07e;
        }
        .hero-title {
            font-family: var(--font-display);
            font-size: clamp(24px, 2.8vw, 40px);
            font-weight: 800; color: #fff; line-height: 1.1; margin-bottom: 8px;
        }
        .hero-sub { color: rgba(255,255,255,.78); font-size: 14px; line-height: 1.8; }
        .hero-cta {
            margin-left: auto; flex-shrink: 0;
            display: inline-flex; align-items: center; gap: 8px;
            padding: 13px 22px; border-radius: 16px; border: none;
            cursor: pointer; font-size: 14px; font-weight: 800;
            background: linear-gradient(135deg, #ff6b1a, #ef5d10);
            color: #fff; box-shadow: 0 10px 24px rgba(255,107,26,.22);
            text-decoration: none; transition: .2s;
        }
        .hero-cta:hover { transform: translateY(-1px); }

        /* ── Filters ──────────────────────────────────────── */
        .filter-bar {
            display: flex; gap: 10px; flex-wrap: wrap;
            align-items: center; margin-bottom: 22px;
        }
        .filter-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: 999px;
            border: 1px solid var(--glass-border);
            background: var(--glass-bg); color: var(--text-secondary);
            font-size: 13px; font-weight: 600; cursor: pointer;
            transition: .2s; white-space: nowrap;
        }
        .filter-btn:hover, .filter-btn.active {
            background: rgba(255,107,26,.10);
            border-color: rgba(255,107,26,.30);
            color: #ff6b1a;
        }
        .filter-search {
            flex: 1; min-width: 200px;
            display: flex; align-items: center; gap: 8px;
            padding: 9px 14px; border-radius: 12px;
            border: 1px solid var(--glass-border);
            background: var(--glass-bg);
        }
        .filter-search input {
            flex: 1; background: none; border: none; outline: none;
            color: var(--text-primary); font-size: 13px;
        }
        .filter-search input::placeholder { color: var(--text-secondary); }
        .filter-search i { color: var(--text-secondary); }

        /* ── Devis cards ──────────────────────────────────── */
        .devis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 18px;
        }
        .devis-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            overflow: hidden;
            transition: transform .25s, box-shadow .25s;
        }
        .devis-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 40px rgba(0,0,0,.18);
        }
        .devis-card-top {
            padding: 18px 20px;
            border-bottom: 1px solid var(--glass-border);
            display: flex; align-items: flex-start; gap: 14px;
        }
        .devis-type-icon {
            width: 46px; height: 46px; flex-shrink: 0;
            border-radius: 14px; display: grid; place-items: center;
            font-size: 20px; color: #fff;
        }
        .icon-auto      { background: linear-gradient(135deg, #00b4d8, #0077a8); }
        .icon-habitation{ background: linear-gradient(135deg, #a855f7, #7e22ce); }
        .icon-sante     { background: linear-gradient(135deg, #2ec4b6, #0d9488); }
        .icon-default   { background: linear-gradient(135deg, #ff6b1a, #ef5d10); }

        .devis-card-title {
            font-size: 15px; font-weight: 700; color: var(--text-primary);
            margin-bottom: 4px; line-height: 1.3;
        }
        .devis-card-ref {
            font-size: 11px; color: var(--text-secondary);
            font-family: monospace;
        }
        .devis-status {
            margin-left: auto; flex-shrink: 0;
        }
        .badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 11px; border-radius: 20px;
            font-size: 11px; font-weight: 700;
        }
        .badge-pending  { background: rgba(251,191,36,.15); color: #fbbf24; border: 1px solid rgba(251,191,36,.25); }
        .badge-approved { background: rgba(46,196,182,.15); color: #2ec4b6; border: 1px solid rgba(46,196,182,.25); }
        .badge-rejected { background: rgba(230,57,70,.15);  color: #e63946; border: 1px solid rgba(230,57,70,.25); }
        .badge-inreview { background: rgba(99,102,241,.15); color: #818cf8; border: 1px solid rgba(99,102,241,.25); }

        .devis-card-body { padding: 16px 20px; }
        .devis-info-row {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: var(--text-secondary);
            margin-bottom: 8px;
        }
        .devis-info-row i { font-size: 14px; width: 16px; text-align: center; color: #ff6b1a; }
        .devis-info-row strong { color: var(--text-primary); font-weight: 600; }

        .devis-offer-chip {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 12px; border-radius: 10px;
            background: rgba(255,107,26,.08);
            border: 1px solid rgba(255,107,26,.18);
            color: #ff6b1a; font-size: 12px; font-weight: 700;
            margin-bottom: 10px;
        }

        .devis-card-footer {
            padding: 12px 20px;
            border-top: 1px solid var(--glass-border);
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
        }
        .devis-date {
            font-size: 11px; color: var(--text-secondary);
            display: flex; align-items: center; gap: 5px;
        }
        .btn-view-devis {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: 10px;
            background: rgba(255,107,26,.10);
            border: 1px solid rgba(255,107,26,.20);
            color: #ff6b1a; font-size: 12px; font-weight: 700;
            cursor: pointer; transition: .2s; text-decoration: none;
        }
        .btn-view-devis:hover {
            background: rgba(255,107,26,.18);
            transform: translateX(2px);
        }

        /* ── Empty state ──────────────────────────────────── */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center; padding: 60px 20px;
            color: var(--text-secondary);
        }
        .empty-state i { font-size: 52px; opacity: .25; display: block; margin-bottom: 16px; }
        .empty-state h3 { font-size: 18px; color: var(--text-primary); margin-bottom: 8px; }
        .empty-state p { font-size: 14px; line-height: 1.7; margin-bottom: 22px; }

        /* ── Detail modal ─────────────────────────────────── */
        .modal-overlay {
            display: none; position: fixed; inset: 0; z-index: 9000;
            background: rgba(0,0,0,.55); backdrop-filter: blur(6px);
            align-items: center; justify-content: center; padding: 20px;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            width: min(600px, 96vw); max-height: 90vh; overflow-y: auto;
            background: var(--navy-mid, #0f1f3d);
            border: 1px solid var(--glass-border);
            border-radius: 22px;
            box-shadow: 0 30px 80px rgba(0,0,0,.5);
            animation: modalPop .22s ease;
        }
        @keyframes modalPop { from { transform: scale(.93); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .modal-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 22px 24px; border-bottom: 1px solid var(--glass-border);
        }
        .modal-title {
            font-size: 17px; font-weight: 700; color: var(--text-primary);
            display: flex; align-items: center; gap: 10px;
        }
        .modal-close {
            width: 34px; height: 34px; border-radius: 10px;
            background: rgba(255,255,255,.06); border: 1px solid var(--glass-border);
            color: var(--text-secondary); cursor: pointer; font-size: 18px;
            display: flex; align-items: center; justify-content: center;
            transition: .2s;
        }
        .modal-close:hover { background: rgba(255,255,255,.12); color: #fff; }
        .modal-body { padding: 22px 24px; }
        .modal-field {
            margin-bottom: 14px;
        }
        .modal-label {
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .5px; color: var(--text-secondary); margin-bottom: 5px;
        }
        .modal-value {
            font-size: 14px; color: var(--text-primary); font-weight: 500;
            background: rgba(255,255,255,.05); border: 1px solid var(--glass-border);
            border-radius: 10px; padding: 10px 14px;
        }
        .modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        /* ── Stats bar ────────────────────────────────────── */
        .stats-grid { grid-template-columns: repeat(4, 1fr); }
        @media (max-width: 900px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 500px) { .stats-grid { grid-template-columns: 1fr; } }

        @media (max-width: 640px) {
            .devis-grid { grid-template-columns: 1fr; }
            .hero-cta { display: none; }
        }
    </style>
</head>
<body>
<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">
    <?php require_once __DIR__ . '/assets/includes/navbar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title-main">Mes devis</div>
                <div class="page-breadcrumb">
                    <i class="bi bi-house"></i>
                    <a href="client.php" style="color:inherit;text-decoration:none;">Accueil</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <span>Mes devis</span>
                </div>
            </div>
        </div>

        <div class="content">

            <?php if (isset($dbError)): ?>
            <div style="padding:14px 18px; border-radius:14px; background:rgba(230,57,70,.12); border:1px solid rgba(230,57,70,.25); color:#e63946; margin-bottom:20px; font-size:13px; display:flex; align-items:center; gap:10px;">
                <i class="bi bi-exclamation-triangle-fill" style="font-size:18px;"></i>
                <div><strong>Erreur de chargement :</strong> <?= htmlspecialchars($dbError) ?></div>
            </div>
            <?php endif; ?>

            <!-- Hero -->
            <section class="hero-banner">
                <div class="hero-inner">
                    <div class="hero-icon-wrap"><i class="bi bi-files"></i></div>
                    <div>
                        <div class="hero-title">Mes demandes de devis</div>
                        <div class="hero-sub">Suivez l'état de toutes vos demandes en temps réel.</div>
                    </div>
                    <a href="ajoutdevis.php" class="hero-cta">
                        <i class="bi bi-file-earmark-plus"></i> Nouveau devis
                    </a>
                </div>
            </section>

            <!-- Stats KPI -->
            <?php
            $total    = count($devis);
            $pending  = count(array_filter($devis, fn($d) => in_array($d['statut'] ?? '', ['en_attente', 'en_cours'])));
            $approved = count(array_filter($devis, fn($d) => in_array($d['statut'] ?? '', ['traite', 'converti'])));
            $rejected = count(array_filter($devis, fn($d) => ($d['statut'] ?? '') === 'refuse'));
            ?>
            <div class="stats-grid" style="margin-bottom:24px;">
                <div class="stat-card blue">
                    <div class="stat-icon"><i class="bi bi-files"></i></div>
                    <div class="stat-value"><?= $total ?></div>
                    <div class="stat-label">Total devis</div>
                </div>
                <div class="stat-card gold">
                    <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                    <div class="stat-value"><?= $pending ?></div>
                    <div class="stat-label">En attente</div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                    <div class="stat-value"><?= $approved ?></div>
                    <div class="stat-label">Approuvés</div>
                </div>
                <div class="stat-card red">
                    <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
                    <div class="stat-value"><?= $rejected ?></div>
                    <div class="stat-label">Refusés</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-bar">
                <button class="filter-btn active" data-filter="all" onclick="filterDevis('all', this)">
                    <i class="bi bi-grid-3x2-gap"></i> Tous
                </button>
                <button class="filter-btn" data-filter="pending" onclick="filterDevis('pending', this)">
                    <i class="bi bi-hourglass-split"></i> En attente
                </button>
                <button class="filter-btn" data-filter="approved" onclick="filterDevis('approved', this)">
                    <i class="bi bi-check-circle"></i> Approuvés
                </button>
                <button class="filter-btn" data-filter="rejected" onclick="filterDevis('rejected', this)">
                    <i class="bi bi-x-circle"></i> Refusés
                </button>
                <div class="filter-search">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" placeholder="Rechercher un devis..." oninput="searchDevis()">
                </div>
            </div>

            <!-- Cards grid -->
            <div class="devis-grid" id="devisGrid">
                <?php if (empty($devis)): ?>
                <div class="empty-state">
                    <i class="bi bi-file-earmark-x"></i>
                    <h3>Aucune demande de devis</h3>
                    <p>Vous n'avez pas encore soumis de demande de devis.<br>Faites votre première demande en quelques minutes !</p>
                    <a href="ajoutdevis.php" class="hero-cta" style="display:inline-flex;">
                        <i class="bi bi-file-earmark-plus"></i> Faire un devis
                    </a>
                </div>
                <?php else: ?>
                <?php foreach ($devis as $d):
                    $type    = strtolower($d['type_assurance'] ?? $d['type_offre'] ?? 'default');
                    $iconClass = match(true) {
                        str_contains($type, 'auto')       => 'icon-auto',
                        str_contains($type, 'habitation') => 'icon-habitation',
                        str_contains($type, 'sante')      => 'icon-sante',
                        default                           => 'icon-default',
                    };
                    $icon = match(true) {
                        str_contains($type, 'auto')       => 'bi-car-front',
                        str_contains($type, 'habitation') => 'bi-house-door',
                        str_contains($type, 'sante')      => 'bi-heart-pulse',
                        default                           => 'bi-file-earmark',
                    };
                    $statut = strtolower($d['statut'] ?? '');
                    [$badgeClass, $badgeIcon, $badgeLabel] = match($statut) {
                        'traite', 'converti' => ['badge-approved', 'bi-check-circle-fill', 'Traité'],
                        'refuse'             => ['badge-rejected', 'bi-x-circle-fill', 'Refusé'],
                        'en_cours'           => ['badge-inreview', 'bi-eye-fill', 'En cours'],
                        default              => ['badge-pending', 'bi-hourglass-split', 'En attente'],
                    };
                    $dateCreation = !empty($d['date_creation']) ? date('d/m/Y', strtotime($d['date_creation'])) : '—';
                    $typLabel = ucfirst($type);
                    $filterAttr = match($statut) {
                        'traite', 'converti' => 'approved',
                        'refuse'             => 'rejected',
                        default              => 'pending',
                    };
                    $searchText = strtolower(($d['nom'] ?? '') . ' ' . ($d['prenom'] ?? '') . ' ' . ($d['type_assurance'] ?? '') . ' ' . ($d['nom_offre'] ?? ''));
                ?>
                <div class="devis-card"
                     data-filter="<?= $filterAttr ?>"
                     data-search="<?= htmlspecialchars($searchText, ENT_QUOTES) ?>"
                     data-id="<?= (int)$d['id_devis'] ?>">
                    <div class="devis-card-top">
                        <div class="devis-type-icon <?= $iconClass ?>">
                            <i class="bi <?= $icon ?>"></i>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div class="devis-card-title"><?= htmlspecialchars(!empty($d['nom_offre']) ? $d['nom_offre'] : 'Devis ' . $typLabel) ?></div>
                            <div class="devis-card-ref">#<?= str_pad($d['id_devis'], 5, '0', STR_PAD_LEFT) ?> · <?= $typLabel ?></div>
                        </div>
                        <div class="devis-status">
                            <span class="badge <?= $badgeClass ?>">
                                <i class="bi <?= $badgeIcon ?>"></i> <?= $badgeLabel ?>
                            </span>
                        </div>
                    </div>

                    <div class="devis-card-body">
                        <?php if (!empty($d['nom_offre'])): ?>
                        <div class="devis-offer-chip">
                            <i class="bi bi-tag"></i> <?= htmlspecialchars($d['nom_offre']) ?>
                            <?php if (!empty($d['prix_annuel'])): ?>
                            — <?= number_format($d['prix_annuel'], 2) ?> DT/an
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="devis-info-row">
                            <i class="bi bi-person"></i>
                            <span><?= htmlspecialchars(trim(($d['prenom'] ?? '') . ' ' . ($d['nom'] ?? ''))) ?></span>
                        </div>
                        <div class="devis-info-row">
                            <i class="bi bi-envelope"></i>
                            <span><?= htmlspecialchars($d['email'] ?? '—') ?></span>
                        </div>
                        <?php if (!empty($d['montant_estime'])): ?>
                        <div class="devis-info-row">
                            <i class="bi bi-cash"></i>
                            <span>Montant estimé : <strong><?= number_format($d['montant_estime'], 2) ?> DT</strong></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($d['reponse_admin'])): ?>
                        <div class="devis-info-row" style="align-items:flex-start;">
                            <i class="bi bi-chat-left-text" style="margin-top:2px;"></i>
                            <span style="line-height:1.5; color:var(--text-secondary);">
                                <?= htmlspecialchars(mb_strimwidth($d['reponse_admin'], 0, 90, '…')) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="devis-card-footer">
                        <div class="devis-date">
                            <i class="bi bi-calendar3"></i> <?= $dateCreation ?>
                        </div>
                        <button class="btn-view-devis" onclick="openModal(<?= (int)$d['id_devis'] ?>)">
                            <i class="bi bi-eye"></i> Voir détails
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Empty filter result -->
            <div id="noResults" style="display:none; text-align:center; padding:48px 20px; color:var(--text-secondary);">
                <i class="bi bi-search" style="font-size:36px; opacity:.3; display:block; margin-bottom:12px;"></i>
                <p style="font-size:14px;">Aucun devis ne correspond à votre recherche.</p>
            </div>

        </div><!-- /content -->
    </main>
</div><!-- /layout -->

<!-- Detail modal -->
<div class="modal-overlay" id="devisModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-file-earmark-text" style="color:#ff6b1a;"></i>
                Détails du devis
            </div>
            <button class="modal-close" onclick="closeModal()"><i class="bi bi-x"></i></button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- injected by JS -->
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
<script>
// ── Data from PHP ─────────────────────────────────────────────────────────────
const DEVIS_DATA = <?= json_encode($devis, JSON_UNESCAPED_UNICODE) ?>;

// ── Filter ─────────────────────────────────────────────────────────────────────
function filterDevis(filter, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const cards = document.querySelectorAll('.devis-card');
    let visible = 0;
    cards.forEach(card => {
        const match = filter === 'all' || card.dataset.filter === filter;
        card.style.display = match ? '' : 'none';
        if (match) visible++;
    });

    applySearch();
}

// ── Search ────────────────────────────────────────────────────────────────────
function searchDevis() {
    applySearch();
}

function applySearch() {
    const q = document.getElementById('searchInput').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.devis-card');
    let visible = 0;
    cards.forEach(card => {
        if (card.style.display === 'none') return;
        const text = (card.dataset.search || '').toLowerCase();
        const match = !q || text.includes(q);
        card.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('noResults').style.display = visible === 0 && DEVIS_DATA.length > 0 ? 'block' : 'none';
}

// ── Modal ─────────────────────────────────────────────────────────────────────
function openModal(id) {
    const d = DEVIS_DATA.find(x => x.id_devis == id);
    if (!d) return;

    const typeIcons = { auto: '🚗', habitation: '🏠', sante: '💊' };
    const type = (d.type_assurance || d.type_offre || '').toLowerCase();
    const icon = Object.keys(typeIcons).find(k => type.includes(k));

    const statut = (d.statut || '').toLowerCase();
    const badgeMap = {
        approved: { cls: 'badge-approved', lbl: 'Approuvé' },
        approuve:  { cls: 'badge-approved', lbl: 'Approuvé' },
        rejected:  { cls: 'badge-rejected', lbl: 'Refusé' },
        rejete:    { cls: 'badge-rejected', lbl: 'Refusé' },
        refused:   { cls: 'badge-rejected', lbl: 'Refusé' },
    };
    const badge = badgeMap[statut] || { cls: 'badge-pending', lbl: 'En attente' };

    const fmt = (val) => val || '—';
    const fmtDate = (val) => {
        if (!val) return '—';
        return new Date(val).toLocaleDateString('fr-FR', { day:'2-digit', month:'long', year:'numeric' });
    };

    document.getElementById('modalBody').innerHTML = `
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px; padding:14px; background:rgba(255,107,26,.08); border-radius:14px; border:1px solid rgba(255,107,26,.18);">
            <span style="font-size:32px;">${typeIcons[icon] || '📄'}</span>
            <div style="flex:1;">
                <div style="font-size:15px; font-weight:700; color:var(--text-primary);">${fmt(d.objet)}</div>
                <div style="font-size:12px; color:var(--text-secondary); margin-top:3px;">#${String(d.id_devis).padStart(5,'0')} · ${(d.type_assurance || '').toUpperCase()}</div>
            </div>
            <span class="badge ${badge.cls}">${badge.lbl}</span>
        </div>

        <div class="modal-grid" style="margin-bottom:14px;">
            <div class="modal-field">
                <div class="modal-label">Nom complet</div>
                <div class="modal-value">${fmt(d.prenom)} ${fmt(d.nom)}</div>
            </div>
            <div class="modal-field">
                <div class="modal-label">Email</div>
                <div class="modal-value">${fmt(d.email)}</div>
            </div>
            <div class="modal-field">
                <div class="modal-label">Téléphone</div>
                <div class="modal-value">${fmt(d.telephone)}</div>
            </div>
            <div class="modal-field">
                <div class="modal-label">Date de demande</div>
                <div class="modal-value">${fmtDate(d.date_creation)}</div>
            </div>
        </div>

        ${d.nom_offre ? `
        <div class="modal-field" style="margin-bottom:14px;">
            <div class="modal-label">Offre sélectionnée</div>
            <div class="modal-value" style="display:flex; align-items:center; gap:10px;">
                <i class="bi bi-tag-fill" style="color:#ff6b1a;"></i>
                ${d.nom_offre}
                ${d.prix_annuel ? `<span style="margin-left:auto; font-weight:800; color:#ff6b1a;">${parseFloat(d.prix_annuel).toFixed(2)} DT/an</span>` : ''}
            </div>
        </div>` : ''}

        ${d.montant_estime ? `
        <div class="modal-field" style="margin-bottom:14px;">
            <div class="modal-label">Montant estimé</div>
            <div class="modal-value" style="font-weight:800; color:#ff6b1a;">${parseFloat(d.montant_estime).toFixed(2)} DT</div>
        </div>` : ''}

        ${d.reponse_admin ? `
        <div class="modal-field" style="margin-bottom:14px;">
            <div class="modal-label">Réponse de l'agent</div>
            <div class="modal-value" style="white-space:pre-wrap; line-height:1.6;">${d.reponse_admin}</div>
        </div>` : ''}
    `;

    document.getElementById('devisModal').classList.add('open');
}

function closeModal() {
    document.getElementById('devisModal').classList.remove('open');
}

document.getElementById('devisModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Close on Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>
</body>
</html>
