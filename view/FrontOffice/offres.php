<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/SessionGuard.php';

$filtre = $filtre ?? ($_GET['type'] ?? 'tous');
$db     = config::getConnexion();

try {
    if (!isset($toutesLesOffres) && isset($offres) && is_array($offres) && count($offres) > 0) {
        $toutesLesOffres = $offres;
    } elseif (!isset($toutesLesOffres)) {
        $toutesLesOffres = $db->query("
            SELECT o.*, 
                   (SELECT AVG(note) FROM avis_offre WHERE id_offre = o.id_offre) as note_moyenne,
                   (SELECT COUNT(*) FROM avis_offre WHERE id_offre = o.id_offre) as nb_avis
            FROM offre o
            WHERE o.statut = 'active'
            ORDER BY o.prix_mensuel ASC
        ")->fetchAll();
    }
} catch (Exception $e) {
    $toutesLesOffres = [];
}

$typeIcons  = [
    'auto'       => 'bi-car-front',
    'sante'      => 'bi-heart-pulse',
    'habitation' => 'bi-house-check',
    'vie'        => 'bi-shield-heart',
];
$typeBadges = [
    'auto'       => 'Populaire',
    'sante'      => 'Recommandé',
    'habitation' => 'Économique',
    'vie'        => 'Premium',
];
$typeColors = [
    'auto'       => 'auto',
    'sante'      => 'sante',
    'habitation' => 'maison',
    'vie'        => 'vie',
];

$offres_filtrees = ($filtre === 'tous')
    ? $toutesLesOffres
    : array_values(array_filter(
        $toutesLesOffres,
        fn($o) => $o['type_offre'] === $filtre
    ));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Nos Offres — Protex</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Global CSS -->
    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/client.css">
    <link rel="stylesheet" href="assets/css/light-theme.css">

    <style>
        /* ═══════════════════════════════════════
           ANIMATIONS
        ═══════════════════════════════════════ */
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(20px); }
            to   { opacity:1; transform:translateY(0); }
        }

        @keyframes shimmerText {
            0%   { background-position: 0% center; }
            100% { background-position: 200% center; }
        }

        /* ═══════════════════════════════════════
           GLOBAL PAGE HEADER
        ═══════════════════════════════════════ */
        .page-header {
            padding: 24px 32px 0;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 8px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .page-title-main {
            font-family: 'Sora', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: #15233C;
        }

        .page-breadcrumb {
            font-size: 12px;
            color: rgba(21,35,60,0.5);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .page-breadcrumb span {
            color: #FF6B1A;
            font-weight: 600;
        }

        /* ═══════════════════════════════════════
           HERO
        ═══════════════════════════════════════ */
        .offer-hero {
            background: linear-gradient(135deg, #1A3A7A 0%, #0f2456 100%);
            border-radius: 22px;
            padding: 32px 36px;
            margin-bottom: 28px;
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            gap: 22px;
            flex-wrap: wrap;
            animation: fadeUp .45s ease both;
            position: relative;
            overflow: hidden;
        }

        .offer-hero::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -50px;
            width: 220px;
            height: 220px;
            background: rgba(255,107,26,0.12);
            border-radius: 50%;
        }

        .offer-hero::after {
            content: '';
            position: absolute;
            bottom: -70px;
            left: 44%;
            width: 170px;
            height: 170px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }

        .offer-hero-inner {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            flex-wrap: wrap;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .offer-hero-title {
            font-family: 'Sora', sans-serif;
            font-size: 30px;
            font-weight: 900;
            color: #fff;
            line-height: 1.15;
            margin-bottom: 10px;
        }

        .offer-hero-title span {
            background: linear-gradient(90deg, #FF6B1A, #ffd2b5, #FF6B1A);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmerText 4s linear infinite;
        }

        .offer-hero-sub {
            font-size: 14px;
            color: rgba(255,255,255,0.68);
            line-height: 1.7;
            max-width: 600px;
            margin-bottom: 18px;
        }

        .hero-chips {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .hero-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 13px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.15);
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.87);
            font-size: 12px;
            font-weight: 500;
        }

        .hero-chip i {
            color: #4ade80;
            font-size: 13px;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            min-width: 320px;
        }

        .hero-stat {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 18px;
            padding: 18px 16px;
            text-align: center;
            transition: all .25s ease;
            backdrop-filter: blur(4px);
        }

        .hero-stat:hover {
            background: rgba(255,255,255,0.12);
            transform: translateY(-2px);
        }

        .hero-stat-value {
            font-family: 'Sora', sans-serif;
            font-size: 24px;
            font-weight: 900;
            color: #fff;
            margin-bottom: 4px;
        }

        .hero-stat-label {
            font-size: 11px;
            color: rgba(255,255,255,0.65);
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        /* ═══════════════════════════════════════
           FILTER BAR
        ═══════════════════════════════════════ */
        .filter-wrap {
            background: #fff;
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: 0 4px 18px rgba(26,58,122,0.06);
            margin-bottom: 24px;
            animation: fadeUp .4s .08s ease both;
        }

        .filter-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-label {
            font-size: 13px;
            color: rgba(21,35,60,0.6);
            font-weight: 700;
            margin-right: 2px;
        }

        .filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 16px;
            border-radius: 999px;
            border: 1px solid rgba(26,58,122,0.12);
            background: rgba(26,58,122,0.03);
            color: rgba(21,35,60,0.60);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all .22s ease;
        }

        .filter-btn:hover {
            background: rgba(26,58,122,0.05);
            border-color: rgba(255,107,26,0.28);
            color: #15233C;
            transform: translateY(-1px);
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #FF6B1A, #e05a0f);
            border-color: #FF6B1A;
            color: #fff;
            box-shadow: 0 6px 18px rgba(255,107,26,0.25);
        }

        /* ═══════════════════════════════════════
           SECTION HEADER
        ═══════════════════════════════════════ */
        .section-header {
            margin-bottom: 18px;
            animation: fadeUp .4s .12s ease both;
        }

        .section-title {
            font-family: 'Sora', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: #15233C;
            margin-bottom: 4px;
        }

        .section-sub {
            font-size: 13px;
            color: rgba(21,35,60,0.55);
        }

        /* ═══════════════════════════════════════
           OFFERS GRID
        ═══════════════════════════════════════ */
        .offers-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
            margin-bottom: 28px;
            animation: fadeUp .45s .15s ease both;
        }

        .offer-card {
            background: #fff;
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: 22px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            box-shadow: 0 6px 24px rgba(26,58,122,0.07);
            transition: all .28s ease;
        }

        .offer-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 18px 38px rgba(26,58,122,0.13);
            border-color: rgba(255,107,26,0.20);
        }

        .offer-card.recommande {
            border-color: rgba(255,107,26,0.22);
            box-shadow: 0 10px 28px rgba(255,107,26,0.12);
        }

        .offer-card.recommande::before {
            content: '★ Recommandé';
            position: absolute;
            top: 17px;
            right: -34px;
            background: linear-gradient(135deg, #FF6B1A, #e05a0f);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 6px 40px;
            transform: rotate(45deg);
            letter-spacing: .4px;
            z-index: 2;
            box-shadow: 0 6px 18px rgba(255,107,26,0.28);
        }

        .offer-banner {
            padding: 22px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            min-height: 110px;
        }

        .offer-banner.auto {
            background: linear-gradient(135deg, rgba(26,58,122,0.95), rgba(45,92,196,0.78));
        }

        .offer-banner.sante {
            background: linear-gradient(135deg, rgba(5,150,105,0.95), rgba(52,211,153,0.78));
        }

        .offer-banner.maison {
            background: linear-gradient(135deg, rgba(255,107,26,0.95), rgba(255,154,92,0.80));
        }

        .offer-banner.vie {
            background: linear-gradient(135deg, rgba(124,58,237,0.95), rgba(167,139,250,0.82));
        }

        .offer-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            background: rgba(255,255,255,0.16);
            border: 1px solid rgba(255,255,255,0.18);
            color: #fff;
            font-size: 25px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.18);
        }

        .offer-badge {
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .35px;
            background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.16);
            color: #fff;
            backdrop-filter: blur(4px);
        }

        .offer-body {
            padding: 22px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .offer-name {
            font-family: 'Sora', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: #15233C;
            margin-bottom: 6px;
        }

        .offer-desc {
            color: rgba(21,35,60,0.58);
            font-size: 13px;
            line-height: 1.65;
            margin-bottom: 16px;
            flex: 1;
        }

        .offer-price-box {
            background: linear-gradient(135deg, #f8faff, #fff5f0);
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: 16px;
            padding: 16px 18px;
            margin-bottom: 16px;
        }

        .offer-price-row {
            display: flex;
            align-items: baseline;
            gap: 6px;
            margin-bottom: 5px;
            flex-wrap: wrap;
        }

        .offer-price {
            font-family: 'Sora', sans-serif;
            font-size: 31px;
            font-weight: 900;
            color: #15233C;
            line-height: 1;
        }

        .offer-price-note {
            font-size: 13px;
            color: rgba(21,35,60,0.50);
        }

        .offer-price-annual {
            font-size: 12px;
            color: #FF6B1A;
            font-weight: 600;
        }

        .offer-price-annual i {
            margin-right: 4px;
        }

        .offer-features {
            display: grid;
            gap: 9px;
            margin-bottom: 16px;
        }

        .offer-feature {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            font-size: 13px;
            color: #15233C;
            line-height: 1.45;
        }

        .offer-feature i {
            color: #059669;
            font-size: 14px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .offer-meta {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 14px;
            border-radius: 15px;
            border: 1px solid rgba(26,58,122,0.08);
            background: rgba(26,58,122,0.03);
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .offer-meta-item {
            min-width: 80px;
            flex: 1;
        }

        .offer-meta-item span:first-child {
            display: block;
            font-size: 10px;
            color: rgba(21,35,60,0.45);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: .55px;
        }

        .offer-meta-item span:last-child {
            font-size: 12px;
            font-weight: 700;
            color: #15233C;
        }

        .offer-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }

        /* ═══════════════════════════════════════
           EMPTY STATE
        ═══════════════════════════════════════ */
        .empty-offres {
            grid-column: 1 / -1;
            text-align: center;
            padding: 70px 20px;
            background: #fff;
            border-radius: 22px;
            border: 1px solid rgba(26,58,122,0.08);
            box-shadow: 0 4px 18px rgba(26,58,122,0.05);
        }

        .empty-offres i {
            font-size: 42px;
            display: block;
            margin-bottom: 12px;
            color: rgba(21,35,60,0.28);
        }

        .empty-offres strong {
            display: block;
            color: #15233C;
            font-size: 18px;
            margin-bottom: 8px;
            font-family: 'Sora', sans-serif;
        }

        .empty-offres p {
            color: rgba(21,35,60,0.56);
            font-size: 14px;
            margin: 0;
        }

        /* ═══════════════════════════════════════
           CTA
        ═══════════════════════════════════════ */
        .cta-banner {
            background: linear-gradient(135deg, #f8faff 0%, #fff5f0 100%);
            border: 1px solid rgba(26,58,122,0.08);
            border-radius: 22px;
            padding: 32px 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            animation: fadeUp .45s .25s ease both;
            box-shadow: 0 6px 22px rgba(26,58,122,0.05);
            position: relative;
            overflow: hidden;
            margin-top: 20px;
        }

        .cta-banner::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -30px;
            width: 180px;
            height: 180px;
            background: rgba(255,107,26,0.08);
            border-radius: 50%;
        }

        .cta-title {
            font-family: 'Sora', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: #15233C;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .cta-sub {
            font-size: 14px;
            color: rgba(21,35,60,0.58);
            line-height: 1.65;
            max-width: 620px;
            position: relative;
            z-index: 1;
        }

        /* ═══════════════════════════════════════
           BUTTONS
        ═══════════════════════════════════════ */
        .btn {
            padding: 10px 20px;
            border-radius: 11px;
            font-size: 13px;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: all 0.2s;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF6B1A, #e05a0f);
            color: #fff;
            box-shadow: 0 4px 14px rgba(255,107,26,0.24);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #e05a0f, #cc4f00);
            box-shadow: 0 6px 20px rgba(255,107,26,0.34);
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            color: #1A3A7A;
            border: 1px solid rgba(26,58,122,0.20);
        }

        .btn-outline:hover {
            background: rgba(26,58,122,0.06);
            border-color: #1A3A7A;
        }

        .btn-lg {
            padding: 14px 28px;
            font-size: 15px;
        }

        /* ═══════════════════════════════════════
           RESPONSIVE
        ═══════════════════════════════════════ */
        @media (max-width: 1100px) {
            .offers-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .hero-stats {
                grid-template-columns: repeat(2, 1fr);
                min-width: 280px;
            }
        }

        @media (max-width: 860px) {
            .page-header {
                padding: 16px 16px 0;
            }

            .offer-hero {
                padding: 24px 22px;
            }

            .offer-hero-title {
                font-size: 24px;
            }

            .hero-stats {
                width: 100%;
            }
        }

        @media (max-width: 700px) {
            .offers-grid {
                grid-template-columns: 1fr;
            }

            .hero-stats {
                grid-template-columns: 1fr 1fr;
            }

            .cta-banner {
                flex-direction: column;
                align-items: flex-start;
            }

            .offer-meta {
                flex-direction: column;
            }
        }

        @media (max-width: 560px) {
            .hero-stats {
                display: none;
            }

            .filter-wrap {
                padding: 16px;
            }

            .cta-banner {
                padding: 24px 20px;
            }
        }
        .partners-section{padding:2rem 0}.partners-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;margin-top:1.25rem}
        .partner-type-card{background:#fff;border:1px solid rgba(21,35,60,.08);border-radius:14px;padding:1.15rem;transition:all .25s;box-shadow:0 2px 12px rgba(0,0,0,.04)}
        .partner-type-card:hover{border-color:rgba(0,180,216,.3);transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.06)}
        .partner-type-header{display:flex;align-items:center;gap:10px;margin-bottom:.85rem}
        .partner-type-icon{width:40px;height:40px;border-radius:10px;background:rgba(0,180,216,.1);display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--accent,#00b4d8);flex-shrink:0}
        .partner-type-name{font-family:var(--font-display,'Sora',sans-serif);font-size:14px;font-weight:600;color:#15233C}
        .partner-type-desc{font-size:11.5px;color:rgba(21,35,60,.5);margin-top:1px}
        .partner-items{display:flex;flex-direction:column;gap:6px}
        .partner-item{display:flex;flex-wrap:wrap;align-items:center;gap:4px 8px;padding:6px 8px;border-radius:8px;background:#f8fafd;font-size:12px}
        .partner-item-name{font-weight:500;color:#15233C}
        .partner-item-ville{color:rgba(21,35,60,.5);font-size:11px}
        .partner-item-avantage{margin-left:auto;color:var(--success,#22c55e);font-weight:500;font-size:11px}
        .partner-more{text-align:center;font-size:11.5px;color:var(--accent,#00b4d8);padding:4px 0;cursor:default}
        .partners-footer{text-align:center;margin-top:1.5rem}
    </style>
</head>
<body>

<div class="background"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="layout">

<?php require_once __DIR__.'/assets/includes/navbar.php'; ?>

    <main class="main">
        <div class="page-header">
            <div>
                <div class="page-title-main">Nos offres d'assurance</div>
                <div class="page-breadcrumb">
                    <i class="bi bi-house"></i>
                    <a href="client.php" style="color:inherit;text-decoration:none;">Accueil</a>
                    <i class="bi bi-chevron-right" style="font-size:10px"></i>
                    <span>Nos offres</span>
                </div>
            </div>
        </div>

        <div class="content">

            <!-- HERO -->
            <section class="offer-hero">
                <div class="offer-hero-inner">
                    <div>
                        <div class="offer-hero-title">
                            Choisissez votre<br><span>protection idéale</span>
                        </div>
                        <div class="offer-hero-sub">
                            Des formules modernes, transparentes et adaptées à chaque profil.
                            Comparez les couvertures, consultez les prix et souscrivez en quelques clics
                            dans un parcours 100% digital et cohérent avec votre espace client.
                        </div>
                        <div class="hero-chips">
                            <span class="hero-chip"><i class="bi bi-shield-check"></i> Protection fiable</span>
                            <span class="hero-chip"><i class="bi bi-lightning-charge"></i> Souscription rapide</span>
                            <span class="hero-chip"><i class="bi bi-lock"></i> Paiement sécurisé</span>
                            <span class="hero-chip"><i class="bi bi-headset"></i> Support 24/7</span>
                        </div>
                    </div>

                    <div class="hero-stats">
                        <div class="hero-stat">
                            <div class="hero-stat-value"><?= count($toutesLesOffres) ?></div>
                            <div class="hero-stat-label">Formules</div>
                        </div>
                        <div class="hero-stat">
                            <div class="hero-stat-value">24/7</div>
                            <div class="hero-stat-label">Assistance</div>
                        </div>
                        <div class="hero-stat">
                            <div class="hero-stat-value">100%</div>
                            <div class="hero-stat-label">Digital</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FILTERS -->
            <div class="filter-wrap">
                <div class="filter-bar">
                    <span class="filter-label">Filtrer :</span>

                    <a href="offres.php?type=tous" class="filter-btn <?= $filtre==='tous' ? 'active' : '' ?>">
                        <i class="bi bi-grid-3x3-gap"></i> Toutes
                    </a>

                    <a href="offres.php?type=auto" class="filter-btn <?= $filtre==='auto' ? 'active' : '' ?>">
                        <i class="bi bi-car-front"></i> Auto
                    </a>

                    <a href="offres.php?type=sante" class="filter-btn <?= $filtre==='sante' ? 'active' : '' ?>">
                        <i class="bi bi-heart-pulse"></i> Santé
                    </a>

                    <a href="offres.php?type=habitation" class="filter-btn <?= $filtre==='habitation' ? 'active' : '' ?>">
                        <i class="bi bi-house-check"></i> Habitation
                    </a>

                    <a href="offres.php?type=vie" class="filter-btn <?= $filtre==='vie' ? 'active' : '' ?>">
                        <i class="bi bi-shield-heart"></i> Vie
                    </a>
                </div>
            </div>

            <!-- SECTION HEADER -->
            <div class="section-header" id="offres-section">
                <div>
                    <div class="section-title">Nos formules</div>
                    <div class="section-sub"><?= count($offres_filtrees) ?> offre(s) disponible(s)</div>
                </div>
            </div>

            <!-- GRID OFFRES -->
            <div class="offers-grid">
                <?php if (empty($offres_filtrees)): ?>
                    <div class="empty-offres">
                        <i class="bi bi-inbox"></i>
                        <strong>Aucune offre disponible</strong>
                        <p>Revenez bientôt pour découvrir nos nouvelles formules.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($offres_filtrees as $i => $o):
                        $type     = strtolower($o['type_offre'] ?? '');
                        $icon     = $typeIcons[$type]  ?? 'bi-tags';
                        $badge    = $typeBadges[$type] ?? 'Offre';
                        $color    = $typeColors[$type] ?? 'auto';
                        
                        // O2: Promotions / Ventes Flash
                        $isPromoActive = false;
                        $remise = (int)($o['remise_promo'] ?? 0);
                        $dateFin = $o['date_promo_fin'] ?? null;
                        $dateDebut = $o['date_promo_debut'] ?? null;
                        if ($remise > 0 && $dateFin) {
                            $now = new DateTime();
                            $fin = new DateTime($dateFin);
                            $debut = $dateDebut ? new DateTime($dateDebut) : new DateTime();
                            if ($now >= $debut && $now < $fin) {
                                $isPromoActive = true;
                            }
                        }
                        
                        $prixM_display = (float)($o['prix_mensuel'] ?? 0);
                        $prixA_display = (float)($o['prix_annuel'] ?? 0);
                        if ($isPromoActive) {
                            $prixM_display = $prixM_display * (1 - $remise / 100);
                            $prixA_display = $prixA_display * (1 - $remise / 100);
                        }

                        $eco      = round(($prixM_display * 12) - $prixA_display, 0);
                        $features = !empty($o['couverture'])
                            ? array_slice(array_map('trim', explode(',', $o['couverture'])), 0, 4)
                            : [];
                    ?>
                    <article class="offer-card <?= $badge==='Recommandé' ? 'recommande' : '' ?>"
                             style="animation:fadeUp .4s <?= $i * 0.08 ?>s ease both;">

                        <div class="offer-banner <?= $color ?>" style="position:relative;">
                            <div class="offer-icon">
                                <i class="bi <?= $icon ?>"></i>
                            </div>
                            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                                <span class="offer-badge"><?= htmlspecialchars($badge) ?></span>
                                <label style="display:flex; align-items:center; gap:6px; cursor:pointer; background:rgba(0,0,0,0.2); padding:4px 8px; border-radius:6px; color:#fff; font-size:11px; font-weight:700;">
                                    <input type="checkbox" class="compare-chk" data-id="<?= $o['id_offre'] ?>" onchange="updateCompareBar()" style="accent-color:#FF6B1A;">
                                    Comparer
                                </label>
                            </div>
                        </div>

                        <div class="offer-body">
                            <div class="offer-name"><?= htmlspecialchars($o['nom_offre']) ?></div>
                            
                            <!-- O4: Rating display -->
                            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                                <?php
                                $note = round((float)($o['note_moyenne'] ?? 0), 1);
                                $nb_avis = (int)($o['nb_avis'] ?? 0);
                                if ($nb_avis > 0) {
                                    for ($star = 1; $star <= 5; $star++) {
                                        if ($star <= round($note)) {
                                            echo '<i class="bi bi-star-fill" style="color: #f59e0b; font-size: 12px;"></i>';
                                        } else {
                                            echo '<i class="bi bi-star" style="color: #d1d5db; font-size: 12px;"></i>';
                                        }
                                    }
                                    echo '<span style="font-size: 11px; color: #6b7280; margin-left: 4px;">(' . $note . ' - ' . $nb_avis . ' avis)</span>';
                                } else {
                                    echo '<span style="font-size: 11px; color: #9ca3af;"><i class="bi bi-star" style="margin-right:4px;"></i>Nouveau</span>';
                                }
                                ?>
                            </div>

                            <div class="offer-desc"><?= htmlspecialchars($o['description'] ?? '') ?></div>

                            <?php if ($isPromoActive): ?>
                            <div class="promo-badge" data-endtime="<?= htmlspecialchars($dateFin) ?>" style="background: linear-gradient(135deg, #FF6B1A, #ff4e00); color: #fff; padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: bold; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                                <span><i class="bi bi-lightning-charge-fill"></i> -<?= $remise ?>% · Offre limitée</span>
                                <span class="promo-timer" style="font-family: monospace; font-size: 12px;">--:--:--</span>
                            </div>
                            <?php endif; ?>

                            <div class="offer-price-box">
                                <div class="offer-price-row">
                                    <?php if ($isPromoActive): ?>
                                    <div style="text-decoration: line-through; color: #9ca3af; font-size: 14px; margin-right: 8px;"><?= number_format((float)$o['prix_mensuel'], 0) ?></div>
                                    <?php endif; ?>
                                    <div class="offer-price"><?= number_format($prixM_display, 0) ?> TND</div>
                                    <div class="offer-price-note">/ mois</div>
                                </div>
                                <div class="offer-price-annual">
                                    <i class="bi bi-tag"></i>
                                    <?php if ($isPromoActive): ?>
                                    <span style="text-decoration: line-through; color: #fca5a5;"><?= number_format((float)$o['prix_annuel'], 0) ?></span>
                                    <?php endif; ?>
                                    <?= number_format($prixA_display, 0) ?> TND/an
                                    <?php if ($eco > 0): ?>
                                        — économisez <?= $eco ?> TND
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!empty($features)): ?>
                                <div class="offer-features">
                                    <?php foreach ($features as $f): ?>
                                        <div class="offer-feature">
                                            <i class="bi bi-check-circle-fill"></i>
                                            <?= htmlspecialchars($f) ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="offer-meta">
                                <div class="offer-meta-item">
                                    <span>Type</span>
                                    <span><?= ucfirst($type) ?></span>
                                </div>

                                <div class="offer-meta-item">
                                    <span>Plafond</span>
                                    <span><?= !empty($o['plafond']) ? number_format((float)$o['plafond'], 0, '.', ' ') . ' TND' : '—' ?></span>
                                </div>

                                <div class="offer-meta-item">
                                    <span>Durée min.</span>
                                    <span><?= (int)($o['duree_min'] ?? 1) ?> mois</span>
                                </div>
                            </div>

                            <div class="offer-actions">
                                <a href="wizard_souscription.php?id_offre=<?= (int)$o['id_offre'] ?>"
                                   class="btn btn-primary"
                                   style="flex:1;justify-content:center;">
                                    <i class="bi bi-check2-circle"></i> Souscrire
                                </a>

                                <a href="avis_offre.php?id=<?= (int)$o['id_offre'] ?>"
                                   class="btn btn-outline"
                                   title="Avis & Détails">
                                    <i class="bi bi-star"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- PARTENAIRES -->
            <?php
            try {
                $ptnDb = config::getConnexion();
                $partenaires = $ptnDb->query("
                    SELECT p.nom, p.type, p.avantage, p.ville, p.id_partenaire
                    FROM partenaire p
                    WHERE p.actif = 1
                    ORDER BY p.type, p.nom
                ")->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) { $partenaires = []; }
            ?>
            <?php if ($partenaires): ?>
            <section class="partners-section">
                <div class="section-header">
                    <div class="section-title">Avantages partenaires inclus</div>
                    <div class="section-sub">Profitez de réductions et services exclusifs chez nos partenaires agréés</div>
                </div>
                <?php
                $privelegeMap = [
                    'garage'         => ['icon' => 'bi-wrench',           'label' => 'Garages',        'desc' => 'Réparation, carrosserie, véhicule de prêt'],
                    'clinique'       => ['icon' => 'bi-heart-pulse',      'label' => 'Cliniques',      'desc' => 'Tiers payant, consultations sans avance'],
                    'pharmacie'      => ['icon' => 'bi-capsule',          'label' => 'Pharmacies',     'desc' => '-10% sur ordonnances post-sinistre'],
                    'hotel'          => ['icon' => 'bi-building',         'label' => 'Hôtels',         'desc' => 'Hébergement urgence tarif préférentiel'],
                    'avocat'         => ['icon' => 'bi-briefcase',        'label' => 'Avocats',        'desc' => '1ère consultation gratuite'],
                    'location_voiture' => ['icon' => 'bi-car-front',      'label' => 'Location',       'desc' => 'Véhicule remplacement offert'],
                    'serrurier'      => ['icon' => 'bi-key',              'label' => 'Serruriers',     'desc' => 'Dépannage urgence 24h/24'],
                    'telemedicine'   => ['icon' => 'bi-phone',            'label' => 'Télémédecine',   'desc' => 'Consultation à distance incluse'],
                ];
                $grouped = [];
                foreach ($partenaires as $p) {
                    $grouped[$p['type']][] = $p;
                }
                ?>
                <div class="partners-grid">
                    <?php foreach ($grouped as $type => $items): ?>
                    <?php $info = $privelegeMap[$type] ?? ['icon'=>'bi-geo-alt','label'=>$type,'desc'=>'Service partenaire']; ?>
                    <div class="partner-type-card">
                        <div class="partner-type-header">
                            <div class="partner-type-icon"><i class="bi <?= $info['icon'] ?>"></i></div>
                            <div>
                                <div class="partner-type-name"><?= $info['label'] ?></div>
                                <div class="partner-type-desc"><?= $info['desc'] ?></div>
                            </div>
                        </div>
                        <div class="partner-items">
                            <?php foreach (array_slice($items, 0, 3) as $ptn): ?>
                            <div class="partner-item">
                                <span class="partner-item-name"><?= htmlspecialchars($ptn['nom']) ?></span>
                                <span class="partner-item-ville"><?= htmlspecialchars($ptn['ville']) ?></span>
                                <span class="partner-item-avantage"><?= htmlspecialchars($ptn['avantage']) ?></span>
                            </div>
                            <?php endforeach; ?>
                            <?php if (count($items) > 3): ?>
                            <div class="partner-more">+<?= count($items)-3 ?> autres</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="partners-footer">
                    <a href="partenaires.php" class="btn btn-outline">Voir tous les partenaires <i class="bi bi-arrow-right"></i></a>
                </div>
            </section>
            <?php endif; ?>

            <!-- CTA -->
            <section class="cta-banner">
                <div>
                    <div class="cta-title">Prêt à souscrire ?</div>
                    <div class="cta-sub">
                        Choisissez l’offre qui vous convient puis finalisez votre souscription en ligne
                        dans une interface claire, sécurisée et cohérente avec le parcours de paiement.
                    </div>
                </div>

                <a href="wizard_souscription.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-arrow-right-circle"></i> Souscrire maintenant
                </a>
            </section>

        </div>
    </main>
</div>

<!-- O3: Sticky Compare Bar -->
<div id="stickyCompareBar" style="position: fixed; bottom: -100px; left: 0; width: 100%; background: #15233C; color: #fff; padding: 16px 32px; display: flex; justify-content: center; align-items: center; gap: 24px; box-shadow: 0 -4px 20px rgba(0,0,0,0.15); transition: bottom 0.3s ease; z-index: 1000;">
    <div style="font-family: 'Sora', sans-serif; font-weight: 600;">
        <span id="compareCount">0</span> offre(s) sélectionnée(s)
    </div>
    <button onclick="goToCompare()" class="btn btn-primary" style="background: #FF6B1A; border-color: #FF6B1A; font-weight: 700;">
        Comparer <i class="bi bi-arrow-right"></i>
    </button>
</div>

<script src="assets/js/main.js"></script>
<script>
    // O2: Promo Countdown
    setInterval(function() {
        document.querySelectorAll('.promo-badge').forEach(function(badge) {
            var endTime = new Date(badge.getAttribute('data-endtime')).getTime();
            var now = new Date().getTime();
            var distance = endTime - now;
            if (distance < 0) {
                badge.style.display = 'none'; // hide when expired
            } else {
                var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)) + Math.floor(distance / (1000 * 60 * 60 * 24)) * 24;
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                badge.querySelector('.promo-timer').innerText = (hours<10?"0":"")+hours + ":" + (minutes<10?"0":"")+minutes + ":" + (seconds<10?"0":"")+seconds;
            }
        });
    }, 1000);

    // O3: Sticky Compare Logic
    function updateCompareBar() {
        var checked = document.querySelectorAll('.compare-chk:checked');
        var count = checked.length;
        var bar = document.getElementById('stickyCompareBar');
        
        if (count > 3) {
            alert('Vous ne pouvez comparer que 3 offres au maximum.');
            event.target.checked = false;
            return;
        }
        
        document.getElementById('compareCount').innerText = count;
        if (count > 0) {
            bar.style.bottom = '0';
        } else {
            bar.style.bottom = '-100px';
        }
    }

    function goToCompare() {
        var checked = document.querySelectorAll('.compare-chk:checked');
        var ids = [];
        checked.forEach(function(chk) { ids.push(chk.getAttribute('data-id')); });
        if (ids.length > 0) {
            window.location.href = 'comparer_offres.php?offres=' + ids.join(',');
        }
    }
</script>
</body>
</html>